This is a fork of the (Grav Resize Images Plugin)[https://github.com/fredrikekelund/grav-plugin-resize-images] with a slightly different approach.

## Problem solved
Grav doesn't allow url parameters to generate resized images on the fly.

## Goal
The final purpose of the plugin is to have different images sizes generated on page save.
In my case, Grav is only used as an admin panel to generate a json file ready to be consumed by my front-end app (weither it's React, Angular, VueJs). So I wanted to have pre-generated images, with defined size/names (and not @{size}x names, who wasn't making sens), ready to be displayed in my app. 
**note** : It isn't really intended to be used within Grav ecosystem as it's already handling smart caching and as lots of built-in fonctionnalities to display media files.

## Usage
Out of the box the plugin is generating sizes : THUMB (300), SMALL (560), MEDIUM (770), LARGE (1024), FULLSCREEN (1400). But sizes and names can be changed/added/removed depending on your needs.

On the front end-side you can now simply use `<img src="myimage-THUMB.ext" />`.

### Extra
It's great to use it in combinaison with the `resolution.min` attribute of your field options (in your `.yaml` file) to make sure the desired generated image size is available. There isn't any official documentation about it, but it's already available in Grav Admin Plugin `v1.7.3`.

For exemple, with the default sizes, if you want to have all sizes available, here is your options : 
```yaml
header.custom.image:
    type: file
    label: "Image"
    destination: 'self@'
    accept:
        - image/*
    resolution:
        min:
            width: 1400
```

### Roadmap
- Try to hook onAdminAfterAddMedia/onAdminAfterDelMedia events for on-the-fly actions instead of waiting for page save event (it's taking a while for saving mutliples images at once)
- Add a `force regenerate all images` button for when changing plugin options (sizes and/or names)
