# [Grav](http://getgrav.org) Resize Images Plugin

Automatically resizes images that are uploaded through the
[Grav admin](https://github.com/getgrav/grav-plugin-admin) to a set of
predetermined widths. This frees Grav from the need to do this when pages are requested, and frees you from the need to do it manually before uploading images. Win-win!

Moreover, this plugin doesn't support just GD, but also Imagick, which means you'll get higher quality results when resizing.

## Configuration

You're able to customize the set of widths that your images will be resized to. By default they are 640, 1000, 1500, 2500, 3500 pixels in width. Images will never be scaled up, however, so only the widths that are smaller than the original image's will be used.

For every width, you're also able to set the compression quality. A good rule of thumb is to lower that number at higher widths - the result will still be good!
