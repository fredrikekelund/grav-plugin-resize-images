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
     * Determine which adapter is preferred and whether or not it's available
     * @return string - Either 'imagick' or 'gd'
     */
    protected function getImageAdapter()
    {
        $imagick_exists = class_exists('\Imagick');
        $gd_exists = extension_loaded('gd');

        $use_imagick = $imagick_exists ? $this->adapter == 'imagick' : false;
        $use_gd = $gd_exists ? $this->adapter == 'gd' : false;

        return $use_imagick ? 'imagick' : $use_gd ? 'gd' : false;
    }

    /**
     * Resize an image using Imagick
     * @param  string $source         - Source image path
     * @param  string $target         - Target image path
     * @param  float $width           - Target width
     * @param  float $height          - Target height
     * @param  int [$quality]         - Compression quality for target image
     * @return bool                   - Returns true on success, otherwise false
     */
    protected function resizeImageImagick($source, $target, $width, $height, $quality)
    {
        $image = new \Imagick($source);
        $image->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 1);
        $image->setImageCompressionQuality($quality);
        $format = strtolower($image->getImageFormat());

        if ($format == 'jpeg') {
            $image->setImageCompression(\Imagick::COMPRESSION_JPEG);
        } else if ($format == 'png') {
            $image->setImageCompression(\Imagick::COMPRESSION_ZIP);
        }

        $result = $image->writeImage($target);
        $image->clear();

        return (bool) $result;
    }

    /**
     * Resize an image using GD
     * @param  string $source         - Source image path
     * @param  string $target         - Target image path
     * @param  float $width           - Target width
     * @param  float $height          - Target height
     * @param  int [$quality]         - Compression quality for target image
     * @param  float [$source_width]  - Width of source image. Only necessary if using GD, and will be calculated if not supplied
     * @param  float [$source_height] - Height of source image. Only necessary if using GD, and will be calculated if not supplied
     * @return bool                   - Returns true on success, otherwise false
     */
    protected function resizeImageGd($source, $target, $width, $height, $quality, $source_width, $source_height)
    {
        if (!$source_width || !$source_height) {
            $size = getimagesize($source);
            $source_width = $size[0];
            $source_height = $size[1];
        }

        $target_info = pathinfo($target);
        $dest_image = imagecreatetruecolor($width, $height);

        if (preg_match('/jpe?g/', $target_info['extension'])) {
            $source_image = imagecreatefromjpeg($source);
            imagecopyresampled($dest_image, $source_image, 0, 0, 0, 0, $width, $height, $source_width, $source_height);

            $result = imagejpeg($dest_image, $target, $quality);
        } else if ($target_info['extension'] == 'png') {
            $source_image = imagecreatefrompng($source);
            $transparent = imagecolorallocatealpha($dest_image, 255, 255, 255, 127);

            imagealphablending($dest_image, false);
            imagesavealpha($dest_image, true);
            imagefilledrectangle($dest_image, 0, 0, $width, $height, $transparent);
            imagecopyresampled($dest_image, $source_image, 0, 0, 0, 0, $width, $height, $source_width, $source_height);

            $result = imagepng($dest_image, $target, 9);
        }

        imagedestroy($source_image);
        imagedestroy($dest_image);

        return $result;
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
        $adapter = $this->getImageAdapter();

        if ($adapter == 'imagick') {
            return $this->resizeImageImagick($source, $target, $width, $height, $quality);
        } else if ($adapter == 'gd') {
            return $this->resizeImageGd($source, $target, $width, $height, $quality, $source_width, $source_height);
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
                $original_index = $count + 1;
                rename($source_path, "{$info['dirname']}/{$info['filename']}@{$original_index}x.{$info['extension']}");
                rename("{$info['dirname']}/{$info['filename']}@1x.{$info['extension']}", $source_path);
            }

            $this->grav['admin']->setMessage("Resized $filename $count times", 'info');
        }
    }
}
