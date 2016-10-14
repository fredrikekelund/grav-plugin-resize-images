<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use RocketTheme\Toolbox\Event\Event;

require_once 'adapters/imagick.php';
require_once 'adapters/gd.php';

/**
 * Class ResizeImagesPlugin
 * @package Grav\Plugin
 */
class ResizeImagesPlugin extends Plugin
{
    /**
     * @var string
     */
    protected $adapter;

    /**
     * @var array
     */
    protected $sizes;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onAdminSave' => ['onAdminSave', 0]
        ];
    }

    /**
     * Determine which adapter is preferred and whether or not it's available.
     * Construct an instance of that adapter and return it.
     * @param  string $source - Source image path
     * @return mixed          - Either an instance of ImagickAdapter, GDAdapter or false if none of the extensions were available
     */
    protected function getImageAdapter($source)
    {
        $imagick_exists = class_exists('\Imagick');
        $gd_exists = extension_loaded('gd');

        $use_imagick = $imagick_exists ? $this->adapter == 'imagick' : false;
        $use_gd = $gd_exists ? $this->adapter == 'gd' : false;

        if ($use_imagick) {
            return new ImagickAdapter($source);
        } else if ($use_gd) {
            return new GDAdapter($source);
        } else {
            return false;
        }
    }

    /**
     * Resizes an image using either Imagick or GD
     * @param  string $source    - Source image path
     * @param  string $target    - Target image path
     * @param  float $width      - Target width
     * @param  float $height     - Target height
     * @param  int [$quality=95] - Compression quality for target image
     * @return bool              - Returns true on success, otherwise false
     */
    protected function resizeImage($source, $target, $width, $height, $quality = 95)
    {
        $adapter = $this->getImageAdapter($source);
        $adapter->resize($width, $height);
        $adapter->setQuality($quality);

        return $adapter->save($target);
    }

    /**
     * Called when a page is saved from the admin plugin. Will generate
     * responsive image alternatives for image that don't have any.
     */
    public function onAdminSave($event)
    {
        $page = $event['object'];

        if (!$page instanceof Page) {
            return false;
        }

        $this->sizes = (array) $this->config->get('plugins.resize-images.sizes');
        $this->adapter = $this->config->get('plugins.resize-images.adapter', 'imagick');

        foreach ($page->media()->images() as $filename => $medium) {
            $srcset = $medium->srcset(false);

            if ($srcset != '') {
                continue;
            }

            $source_path = $medium->path(false);
            $info = pathinfo($source_path);
            $count = 0;

            foreach ($this->sizes as $i => $size) {
                if ($size['width'] >= $medium->width) {
                    continue;
                }

                $count++;
                $dest_path = "{$info['dirname']}/{$info['filename']}@{$count}x.{$info['extension']}";
                $width = $size['width'];
                $quality = $size['quality'];
                $height = ($width / $medium->width) * $medium->height;

                $this->resizeImage($source_path, $dest_path, $width, $height, $quality, $medium->width, $medium->height);
            }

            if ($count > 0) {
                $original_index = $count + 1;
                rename($source_path, "{$info['dirname']}/{$info['filename']}@{$original_index}x.{$info['extension']}");
                rename("{$info['dirname']}/{$info['filename']}@1x.{$info['extension']}", $source_path);
            }

            $this->grav['admin']->setMessage("Resized $filename $count times", 'info');
        }
    }
}
