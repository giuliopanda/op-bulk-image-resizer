<?php
/**
 * "bulk image resizer" is a plugin for resizing images uploaded to wordpress
 * 
 *
 * @package           bulk-image-resizer
 *
 * @wordpress-plugin
 * Plugin Name:       Bulk image resizer
 * Plugin URI:        https://github.com/giuliopanda/bulk-image-resizer
 * Description:       Optimize images uploaded to the server. Go to "tools" to configure the plugin or to perform batch optimization. Go to "Media library (list view)" to optimize individual images.
 * Version:           1.3.2
 * Requires at least: 5.3
 * Requires PHP:      7.3
 * Author:            Giulio Pandolfelli
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: 	  bulk-image-resizer
 * Domain Path: 	  /languages
 */

if (!defined('WPINC')) die;
define('bulk-image-resizer_VERSION', '1.3.2');
define('BULK_IMAGE_RESIZER_DIR', plugin_dir_path( __FILE__ ) );
require_once(BULK_IMAGE_RESIZER_DIR . "includes/op-functions.php");
require_once(BULK_IMAGE_RESIZER_DIR . "includes/class-bulk-image-resizer-loader.php");
$bulk_image_resizer_loader = new Bulk_image_resizer_loader();
// Chiamo la funzione op_activate quando il plugin viene attivato

register_uninstall_hook(__FILE__, 'bulk_image_resizer_loader_uninstall');
register_activation_hook( __FILE__,  'bulk_image_resizer_loader_activate' );

if (!is_admin()) return;
require_once(BULK_IMAGE_RESIZER_DIR . "admin/class-bulk-image-resiers-admin.php");
require_once(BULK_IMAGE_RESIZER_DIR. "includes/class-bulk-image-resizer-loader-ajax.php");
// Carico i file della lingua
load_plugin_textdomain('bulk-image-resizer', false, BULK_IMAGE_RESIZER_DIR . 'languages');

$bulk_image_resizer_ajax = new Bulk_image_resizer_loader_ajax();
$admin = new Bulk_image_resizer_admin();



/**
 * Quando viene rimosso il plugin
 */
 function bulk_image_resizer_loader_uninstall() {
    delete_option('op_resize_statistics');
    delete_option('bulk_image_resizer');
    delete_option('op_resize_images_done');
    delete_option('bulk_image_resizer_welcome');
}

/**
 * Quando viene attivato il plugin
 */
function bulk_image_resizer_loader_activate() {
    update_option('bulk_image_resizer_welcome', 1, false);
}