<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Common\Page\Page;
use RocketTheme\Toolbox\Event\Event;

require_once 'adapters/imagick.php';
require_once 'adapters/gd.php';

/**
 * TODO :
    - Separate create and delete actions
    - Try to use onAdminAfterAddMedia/onAdminAfterDelMedia for on-the-fly actions
    - Delete images when page is deleted ?
 */

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
     * Determine whether a particular dependency is installed.
     * @param  string $adapter Either 'gd' or 'imagick'
     * @return bool
     */
    protected function dependencyCheck($adapter = 'gd')
    {
        if ($adapter === 'gd') {
            return extension_loaded('gd');
        }

        if ($adapter === 'imagick') {
            return class_exists('\Imagick');
        }
    }

    /**
     * Determine which adapter is preferred and whether or not it's available.
     * Construct an instance of that adapter and return it.
     * @param  string $source - Source image path
     * @return mixed          - Either an instance of ImagickAdapter, GDAdapter or false if none of the extensions were available
     */
    protected function getImageAdapter($source)
    {
        $imagick_exists = $this->dependencyCheck('imagick');
        $gd_exists = $this->dependencyCheck('gd');

        if ($this->adapter === 'imagick') {
            if ($imagick_exists) {
                return new ImagickAdapter($source);
            } else if ($gd_exists) {
                return new GDAdapter($source);
            }
        } else if ($this->adapter === 'gd') {
            if ($gd_exists) {
                return new GDAdapter($source);
            } else if ($imagick_exists) {
                return new ImagickAdapter($source);
            }
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

        if (!$this->dependencyCheck('imagick') && !$this->dependencyCheck('gd')) {
            $this->grav['admin']->setMessage('Neither Imagick nor GD seem to be installed. The resize-images plugin needs one of them to work.', 'warning');
            return;
        }

        $this->sizes = (array) $this->config->get('plugins.resize-images.sizes');
        $this->sizesNames = array_map(function($size) {
                return $size['name'];
            }, $this->sizes);

        $this->adapter = $this->config->get('plugins.resize-images.adapter', 'imagick');
        $mediaCount = count($page->media()->images());
        $allImages = $page->media()->images();
        foreach ($allImages as $filename => $medium) {
            $message = '';
            $srcset = $medium->srcset(false);
            $sizesNames = implode('|', $this->sizesNames);

            // $message .= "{$filename} ";

            // We can't rely on the path returned from the image's own path
            // method, since it points to the directory where the image is saved
            // rather than where the original is stored. This means it could
            // point to the global image cache directory.
            $page_path = $page->path();
            $source_path = "$page_path/$filename";
            $info = pathinfo($source_path);
            $count = 0;
            $sizeRegexp = "/-({$sizesNames})\.{$info['extension']}$/";
            $isResizedFile = preg_match($sizeRegexp, $filename);

            // Stop here is it's a resized file
            if ( $isResizedFile ) {
                $hasOriginalFile = false;
                $orginialFileName = preg_replace($sizeRegexp, ".{$info['extension']}", $filename);

                // Try to find the original/non-resized file
                foreach ($allImages as $subFilename => $subMedium) {
                    if ( $orginialFileName == $subFilename )
                    {
                        $hasOriginalFile = true;
                        break;
                    }
                }

                // If the orignal file is missing then also delete this resized version
                if ( !$hasOriginalFile ){
                    unlink($source_path);
                    $message .= "$filename - deleted, because original file was missing";
                } else {
                    // $message .= "$filename - skip, it's a resized file";
                }

                $this->grav['admin']->setMessage($message, 'info');
                continue;
            }

            $hasResizedFiles = false;
            $resizedFileRegexp = "/{$info['filename']}-({$sizesNames})\.{$info['extension']}$/";

            foreach ($allImages as $subFilename => $subMedium) {
                if ( preg_match($resizedFileRegexp, $subFilename) )
                {
                    $hasResizedFiles = true;
                    break;
                }
            }

            // Stop here if the file already has resized version(s)
            if ( $hasResizedFiles ) {
                // $message .= "$filename - skip, it already has resized file(s)";
                $this->grav['admin']->setMessage($message, 'info');
                continue;
            }

            foreach ($this->sizes as $i => $size) {
                if ($size['width'] >= $medium->width) {
                    continue;
                }

                $count++;
                $sizeName = $size['name'] ?: "SIZE{$count}";
                $dest_path = "{$info['dirname']}/{$info['filename']}-{$sizeName}.{$info['extension']}";
                $width = $size['width'];
                $quality = $size['quality'];
                $height = ($width / $medium->width) * $medium->height;
        
                $this->resizeImage($source_path, $dest_path, $width, $height, $quality, $medium->width, $medium->height);
            }

            $remove_original = $this->config->get('plugins.resize-images.remove_original');

            if ($count > 0) {
                $original_index = $count + 1;

                $message .= " Resized $filename $count times";

                if ($remove_original) {
                    unlink($source_path);

                    $message .= ' (and removed the original image)';
                }
            }

            if ( $message && $message != '' ) {
                $this->grav['admin']->setMessage($message, 'info');
            }
        }
    }
}
