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
 * Version:           2.0.0
 * Requires at least: 5.3
 * Requires PHP:      7.3
 * Author:            Giulio Pandolfelli
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: 	  bulk-image-resizer
 * Domain Path: 	  /languages
 */

 namespace bulk_image_resizer;

 if (!defined('WPINC')) die;
 define('bulk-image-resizer_VERSION', '2.0.0');
 define('BULK_IMAGE_RESIZER_DIR', plugin_dir_path( __FILE__ ) );

require __DIR__ . '/includes/class-bir-extends-function.php';
require __DIR__ . '/includes/class-bir-rename-function.php';
require __DIR__ . '/includes/class-bir-rebuild-function.php';
require __DIR__ . '/includes/class-bir-optimize-function.php';
require __DIR__ . '/includes/class-bir-list-function.php';
require __DIR__ . '/includes/class-bir-options-vars.php';
require __DIR__ . '/includes/class-bir-loader.php';
require __DIR__ . '/includes/class-bir-upload-new-file.php';
require __DIR__ . '/includes/class-bir-loader-media-library.php';
require __DIR__ . '/includes/class-bir-facade.php';
require __DIR__ . '/includes/class-bir-functions.php';
require __DIR__ . "/admin/class-bulk-image-resiers-admin.php";

$bir_options = new Bir_options_var();
$admin = new Bulk_image_resizer_admin();


// Chiamo la funzione op_activate quando il plugin viene attivato

// Carico i file della lingua
//load_plugin_textdomain('bulk-image-resizer', false, BULK_IMAGE_RESIZER_DIR . 'languages');


/**
 * Quando viene rimosso il plugin
 */
 function bulk_image_resizer_loader_uninstall() {
     global $wpdb;
     // v. 1.2.0
    delete_option('op_resize_statistics');
    delete_option('bulk_image_resizer');
    delete_option('op_resize_images_done');
    delete_option('bulk_image_resizer_welcome');
    // v. 2.0
    // rimuovo tutte le info meta
    $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = '_bir_attachment_originalname'");
    $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = '_bir_attachment_originaltitle'");
    $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = '_bir_attachment_uniqid'");
    $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = '_bir_attachment_originalfilesize'");

}

/**
 * Quando viene attivato il plugin
 */
function bulk_image_resizer_loader_activate() {
    update_option('bulk_image_resizer_welcome', 1, false);
}



\register_uninstall_hook(__FILE__, '\bulk_image_resizer\bulk_image_resizer_loader_uninstall');
\register_activation_hook( __FILE__,  '\bulk_image_resizer\bulk_image_resizer_loader_activate' );

