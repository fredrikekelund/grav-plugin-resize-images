# [Grav](http://getgrav.org) Resize Images Plugin

> Resize images at upload time in the Grav admin

Grav provides some nifty built-in image editing features through the use of
[Gregwar/Image](https://github.com/Gregwar/Image). But there's no native support
yet for automatically generating responsive image alternatives as images are
uploaded in the admin. This plugin fixes that! It will automatically resize
images that are uploaded through the [Grav
admin](https://github.com/getgrav/grav-plugin-admin) to a set of predetermined
widths. This means improved performance for Grav, and less manual resizing work
for you. Win-win!

Moreover, this plugin doesn't support just GD, but also Imagick, which means
you'll get higher quality results than with the ImageMedium#derivatives method
that can be used to generate image alternatives in theme templates.

Images that already have responsive alternatives won't be resized.

## Configuration

You can to customize the widths that your images will be resized to. By default
they are 640, 1000, 1500, 2500, 3500 pixels in width. Images will never be
scaled up, however, so only the widths that are smaller than the original
image's will be used.

For every width, you're also able to set the compression quality. A good rule of
thumb is to lower that number at higher widths - the result will still be good!

## Installation

Download the [ZIP
archive](https://github.com/fredrikekelund/grav-plugin-resize-images/archive/master.zip)
from GitHub and extract it to the `user/plugins` directory in your Grav
installation.

## CLI

I'm aiming to add support for CLI commands to this plugin as well, to make it
easy to generate responsive image alternatives for already uploaded images.
