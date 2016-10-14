# [Grav](http://getgrav.org) Resize Images Plugin

**This plugin is still young! If you encounter any issues, please don't hesitate
to [report
them](https://github.com/fredrikekelund/grav-plugin-resize-images/issues).**

> Resize images at upload time in the Grav admin

Grav provides some nifty built-in features for editing images on the fly through
the use of [Gregwar/Image](https://github.com/Gregwar/Image). But there's no
support yet for automatically generating responsive image alternatives at upload
time rather than at request time. This plugin fixes that! It will automatically
resize images that are uploaded through the [Grav
admin](https://github.com/getgrav/grav-plugin-admin) to a set of predetermined
widths. This means improved performance for Grav, and less manual resizing work
for you. Win-win! :tada:

Moreover, this plugin doesn't support just GD, but also Imagick, which means
you'll get higher quality results than with the
[ImageMedium#derivatives](https://learn.getgrav.org/content/media#sizes-with-media-queries)
method that can be used to generate image alternatives in theme templates.

Images that already have responsive alternatives won't be resized.

## Configuration

You can customize the set of widths that your images will be resized to. By
default they are 640, 1000, 1500, 2500, 3500 pixels in width. Images will never
be scaled up, however, so only the widths that are smaller than the original
image's will be used.

For every width, you're also able to set the JPEG compression quality.  A good
rule of thumb is to lower that number at higher widths - the result will still
be good!

This plugin won't convert PNG's to JPEG's, so the quality number only applies to
JPEG images.

## Installation

Download the [ZIP
archive](https://github.com/fredrikekelund/grav-plugin-resize-images/archive/master.zip)
from GitHub and extract it to the `user/plugins` directory in your Grav
installation.

## CLI

I'm aiming to add support for CLI commands to this plugin as well, to make it
easy to generate responsive image alternatives for already uploaded images.
