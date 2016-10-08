<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use RocketTheme\Toolbox\Event\Event;

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
     * Resizes an image using either Imagick or GD
     * @param  string $source         - Source image path
     * @param  string $target         - Target image path
     * @param  float $width           - Target width
     * @param  float $height          - Target height
     * @param  int [$quality]         - Compression quality for target image
     * @param  float [$source_width]  - Width of source image. Only necessary if using GD, and will be calculated if not supplied
     * @param  float [$source_height] - Height of source image. Only necessary if using GD, and will be calculated if not supplied
     * @return bool                   - Returns true on success, otherwise false
     */
    protected function resizeImage($source, $target, $width, $height, $quality, $source_width, $source_height)
    {
        $imagick_exists = class_exists('\Imagick');
        $gd_exists = extension_loaded('gd');
        $use_imagick = $imagick_exists ? $this->adapter == 'imagick' : false;
        $use_gd = $gd_exists ? $this->adapter == 'gd' : false;

        if ($use_imagick) {

            $image = new \Imagick($source);
            $image->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 1);
            $image->setCompressionQuality($quality);
            $result = $image->writeImage($target);
            $image->clear();
            return (bool) $result;

        } else if ($use_gd) {

            if (!$source_width || !$source_height) {
                $size = getimagesize($source);
                $source_width = $size[0];
                $source_height = $size[1];
            }

            $source_image = imagecreatefromjpeg($source);
            $dest_image = imagecreatetruecolor($width, $height);
            imagecopyresampled($dest_image, $source_image, 0, 0, 0, 0, $width, $height, $source_width, $source_height);
            imagedestroy($source_image);
            $result = imagejpeg($dest_image, $target, $quality);
            imagedestroy($dest_image);
            return $result;

        } else {

            return false;

        }
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
                $count++;
                rename($source_path, "{$info['dirname']}/{$info['filename']}@{$count}x.{$info['extension']}");
                rename("{$info['dirname']}/{$info['filename']}@1x.{$info['extension']}", $source_path);
            }

            $this->grav['admin']->setMessage("Resized $filename $count times", 'info');
        }
    }
}
