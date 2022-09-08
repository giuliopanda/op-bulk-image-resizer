=== Bulk image resizer ===
Contributors: giuliopanda 
Donate link: https://www.paypal.com/donate/?cmd=_donations&business=giuliopanda%40gmail.com&item_name=wordpress+plugin+Bulk+image+resizer
Tags: convert,image,optimize,resize,attachment,photo
Requires at least: 5.3
Tested up to: 6.0
Requires PHP: 7.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Stable tag: 1.3.2

You can automatically resize uploaded images. You can choose the maximum size, quality and whether to keep the original image or overwrite it.

== Description ==

Bulk image resize allows you to optimize images uploaded to wordpress.

- You can resize all images with just one click.
- It is optimized to speed up the bulk process. 1000 images take a few minutes on a normal server.
- You can enable the option to optimize images when uploaded to the server.
- Allows you to decide the maximum size of the images and the quality in which they should be compressed.
- Adds to media-library (list version) the possibility to select the images to be optimized.
- Still on media-library (list version) it adds an additional information column on the image.
- Through graphics it allows you to monitor the status of the images on the server.
- Ability to use specific hooks to customize optimization options.
- It also resizes webp images.

 The GitHub repo can be found at [https://github.com/giuliopanda/op-bulk-image-resizer](https://github.com/giuliopanda/op-bulk-image-resizer). Please use the Support tab for potential bugs, issues, or enhancement ideas.

== Installation ==

After installing the plugin, **go to Tools** > **Bulk image resize** to set up the plugin.
You can resize single images or groups from media library (mode list).

== Frequently Asked Questions ==

= Why use Bulk image resizer? =
Because it is opensource and you have no limits in use. It will allow you to make your site faster and will save you space. 

= Can I resize images when uploaded? =
Yes, when you are in the setting activate "Resize when images are uploaded"

= What formats does it support? =
It supports jpg, webp, (gif not animated) and png formats in accordance with wordpress directives.

= Is it possible to decide not only the size but also the quality of the images? =
Yes, you can decide whether to compress high, medium or low quality images.

= Can I go back once resized? =
Yes if you haven't removed the original image. Otherwise no.

= Can I decide which images to optimize? =
Yes, you can select from the media library (list version) the images to be optimized, or use the hooks to extend the script.

= How can I add a filter? =
You can customize which images to optimize and how, through 'op_bir_resize_image_bulk' filter.

Example

`<?php 
/**
 * resize images uploaded
 * If it is a post it resizes to 800x800 pixels, if in the title there is no_compress it does not compress it.
 * @return  Boolean|Array [width:int,height:int]
 */
function fn_bir_resize_image ($filename, $attachment_id) {
    if (stripos($filename,"no_compress")) {
        return false;
    }
    $parent_id = wp_get_post_parent_id( $attachment_id);
    if ($parent_id > 0) {
        $post_type = get_post_type( $parent_id );
        if ($post_type == "post") {
            return [800,800];
        }
    }
    return true;
}
// Called during bulk.
add_filter( 'op_bir_resize_image_bulk', 'fn_bir_resize_image', 10, 2);
?>`

Hooks: 
op_bir_resize_image_bulk_suffix returns the suffix to be added to the image if the original is not deleted
bulk-image-resizer-before-setup-form adds html to the beginning of the setting form
bulk-image-resizer-after-setup-form adds html to the end of the setting form

= What about Bulk image resizer =
When you upload an image to wordpress, thumbs are created for the template, but the uploaded image is saved and sometimes used.
Bulk image resizer resizes uploaded images to optimize site speed and server space.

**Be careful**
If you remove the original images, The images are overwritten at the size you set, so it's important to make a backup first.
They assume no responsibility for any malfunctions or loss of information resulting from the use of the plugin.
From version 1.3 if you have kept the original image you can select the images from the media library and restore them.


== Screenshots ==

1. The appearance of the page for the resize bulk
2. The bulk added to the media library

== Changelog ==

= 1.3.2 - 2022-09-08 =
* fixed bug: space recalculation with dirsize_cache. Thanks to Praul from GitHub

= 1.3.1 - 2022-06-07 =
* fixed setting update did not save checkboxes
* improvement: when you deactivate the option "Resize when images are uploaded" it no longer shows the column with dimensions in the media library
* fixed: Skip images that have a link as a path  
* improvement:The upgrader_process_complete action is no longer used

= 1.3.0 - 2022-03-31 =
* Feat. Bulk revert back to original image from media library

= 1.2.8 - 2022-02-07 =
* Fixed: After resizing the images, the page froze

= 1.2.7 - 2022-01-23 =
* Fixed: missing graphics library in php and messages.
* Updated: chart.js 3.7.0
* Fixed: calculates the 'remaining time' faster

= 1.2.6 - 2022-01-23 =
* Fixed: install uninstall function 
* Fixed: return error in wp_generate_attachment_metadata
* Fixed: missing GD extension in php.ini
* Fixed: warning

= 1.2.5 - 2021-07-19 =
* Fixed: bug with animated gif
* Test: images with a webp extension

= 1.2.0 - 2021-06-22 =
* Text: corrections
* Rewritten the setting system
* Added hooks in the settings form
* Added deleting original option

= 1.1.0 - 2021-06-15 =
* Sanitize all input
* Validate all data 
* Escape allprint

= 1.0.0 - 2021-06-02 =
* Fixed: Complete bulk messages
* Added: HHD Space Graph
* Test: On wordpress 5.3 and fix code for PHP 5.6
* Fixed:  Post upload resize doesn't work

= 0.9.0 - 2021-05-20 =
* Work version Bulk image resize 
* Added: language Translate


== Credits ==
The Bulk image resize was started in 2021 by [Giulio Pandolfelli](giuliopanda@gmail.com) 
for graphs I use https://www.chartjs.org/
for translation I use Loco Translate 