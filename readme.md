Bulk image resizer for wordpress

# Description

Bulk image resize allows you to optimize images uploaded to wordpress.

- You can resize all images with just one click.
- It is optimized to speed up the bulk process. 1000 images take a few minutes on a normal server.
- You can enable the option to optimize images when uploaded to the server.
- Allows you to decide the maximum size of the images and the quality in which they should be compressed.
- Adds to media-library (list version) the possibility to select the images to be optimized
- Still on media-library (list version) it adds an additional information column on the image.
- Through graphics it allows you to monitor the status of the images on the server
- Ability to use specific hooks to customize optimization options.


# Installation

After installing the plugin, **go to Tools** > **Bulk image resize** to set up the plugin.
You can resize single images or groups from media library (mode list).

# Frequently Asked Questions

### Why use Bulk image resizer?
Because it is opensource and you have no limits in use. It will allow you to make your site faster and will save you space.

### What formats does it support?
It supports jpg and png formats in accordance with wordpress directives. In fact By default you can only upload JPG and PNG to your pages and posts.

### Is it possible to decide not only the position but also the quality of the images?
Yes, you can decide whether to compress high, medium or low quality images.

### Can I go back once resized?
No, the optimized images overwrite the original images so if you don't make a backup you can't go back.

### Can I decide which images to optimize?
Yes, you can select from the media library (list version) the images to be optimized, or use the hooks to extend the script.

### What about Bulk image resizer
When you upload an image to wordpress, thumbs are created for the template, but the uploaded image is saved and sometimes used.
Bulk image resizer resizes uploaded images to optimize site speed and server space.

**Be careful**
Images are overwritten at the size you set, so it's important to make a backup first.
They assume no responsibility for any malfunctions or loss of information resulting from the use of the plugin.

# Customize the code with filters
You can customize which images to optimize and how through two filters

```php
/**
 * Only resize images uploaded by articles
 * @return  Boolean|Array [width:int,height:int]
 */
function fn_bir_resize_image_bulk ($filename, $attachment_id) {
	$parent_id = wp_get_post_parent_id( $attachment_id);
	if ($parent_id > 0) {
		$post_type = get_post_type( $parent_id );
		if ($post_type == "post") {
			return true;
		}
	}
	return false;
}
// Called during bulk.
add_filter( 'op_bir_resize_image_bulk', 'fn_bir_resize_image', 10, 2);


/**
 * Only resize images uploaded by articles when they are uploaded
 * @return  Boolean|Array [width:int,height:int]
 */
function fn_bir_resize_image_uploading ($filename, $post_id) {
	$post_type = get_post_type( $post_id );
	if ($post_type == "post") {
		return true;
	}
	return false;
}
// Called when a new image is loaded
add_filter( 'op_bir_resize_image_uploading', 'fn_bir_resize_image_uploading', 10, 2);

```

# Screenshots
 
![The appearance of the page for the resize bulk](https://raw.githubusercontent.com/giuliopanda/bulk-image-resizer/main/assets/screenshot-1.jpg)


![The column added to the media library](https://raw.githubusercontent.com/giuliopanda/bulk-image-resizer/main/assets/screenshot-2.jpg)


# Changelog

### 1.0.1 - 2021-06-04
* Traslation review 

### 1.0.0 - 2021-06-02 
* Fixed: complete bulk messages
* Added: HHD Space Graph
* Test: On wordpress 5.3 and fix code for PHP 5.6
* Fixed: Post upload resize doesn't work

### 0.9.0 - 2021-05-20 
* first working version
* Added: language Translate


# Credits
The Bulk image resizer was started in 2021 by [Giulio Pandolfelli](giuliopanda@gmail.com) 

for graphs I use https://www.chartjs.org/

for translation I use Loco Translate 