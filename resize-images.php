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
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onAdminSave' => ['onAdminSave', 0]
        ];
    }

    /**
     * Initialize the plugin
     */
    public function onAdminSave($event)
    {
        $page = $event['object'];
        $sizes = (array) $this->config->get('plugins.resize-images.sizes');

        if (!$page instanceof Page) {
            return false;
        }

        foreach ($page->media()->images() as $filename => $medium) {
            $srcset = $medium->srcset(false);

            if ($srcset != '') {
                continue;
            }

            $path = $medium->path(false);
            $info = pathinfo($path);
            $count = 0;

            foreach ($sizes as $i => $size) {
                if ($size['width'] >= $medium->width) {
                    continue;
                }

                $count++;
                $image = new \Imagick($path);
                $outname = "{$info['dirname']}/{$info['filename']}@{$count}x.{$info['extension']}";
                $height = ($size['width'] / $medium->width) * $medium->height;
                $this->grav['log']->info($size['width']);

                $image->resizeImage($size['width'], $height, \Imagick::FILTER_LANCZOS, 1);
                $image->setCompressionQuality($size['quality']);
                $image->writeImage($outname);
            }

            if ($count > 0) {
                $count++;
                rename($path, "{$info['dirname']}/{$info['filename']}@{$count}x.{$info['extension']}");
                rename("{$info['dirname']}/{$info['filename']}@1x.{$info['extension']}", $path);
            }
        }
    }
}
