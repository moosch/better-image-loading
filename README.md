# Wordpress plugin - Better Image Loading

This plugin was inspired by [Medium](https://medium.com), it makes images load better on page paint. Providing a 'blurred' version of the image at the correct scale, then fading the true image in once loaded.
It works by default on all post featured images leveraging the Wordpress post_thumbnail_html filter.


### Installation

1. Download the plugin and unzip it.
2. Upload the folder better-image-loading/ to your /wp-content/plugins/ folder.
3. Activate the plugin from your WordPress admin panel.
4. Installation finished.
5. I recommend you regenerate all thumbnails if your media library is not empty.

### Changelog

###### 0.3.7
* Fixed warning with newer versions of WooCommerce

###### 0.3.6
* Added CSS blur effect to loading images

###### 0.3.5
* Further performance improvements

###### 0.3.4
* Performance improvements

###### 0.3.3
* Removed neglected code

###### 0.3.2
* Fixed issue with getting attachment id from url

###### 0.3.1
* JavaScript update to fix margins moving things around

###### 0.3.0
* Partial rewrite
* Added support for WooCommerce
* Improved support for captions (specifically the standard Wordpress caption shortcode)

###### 0.2.1
* Added support for images within links

###### 0.2.0
* Added support for image captions
* General code cleanup

###### 0.1.2
* Added support for existing width/height attributes of images

###### 0.1.1
* Fixed bug displaying images in post content

###### 0.1.0
* Plugin released

### Upgrade Notice

###### 0.3.0
* Support added for WooCommerce introduced an array of new image sizes. For the best experience we recommend you regenerate thumbnails (https://en-gb.wordpress.org/plugins/regenerate-thumbnails/)

###### 0.2.0
* Fixed an issue with images being inside a link

###### 0.2.0
* Upgrade to add support for image captions

###### 0.1.2
* Upgrade to add support for existing width/height attributes of images

###### 0.1.1
* Upgrade to fix issues displaying images within post content

###### 0.1.0
* This is the first version of the plugin

### Roadmap

###### 0.3.1
* Ability to turn off better image loading for post/page content

###### 0.3.2
* Ability to select if you want a blurred image or a single color image like Google Images

###### 0.3.3
* Load larger images only when in view
