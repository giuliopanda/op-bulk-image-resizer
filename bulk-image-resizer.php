<?php
/**
 * "bulk image resizer" is a plugin for resizing images uploaded to wordpress
 * 
 *
 * @package           bulk-image-resizer
 *
 * @wordpress-plugin
 * Plugin Name:       Op Bulk image resizer
 * Plugin URI:        https://github.com/giuliopanda/bulk-image-resizer
 * Description:       Optimize images uploaded to the server. Go to "tools" to configure the plugin or to perform batch optimization. Go to "Media library (list view)" to optimize individual images.
 * Version:           1.2.0
 * Requires at least: 5.3
 * Requires PHP:      5.6
 * Author:            Giulio Pandolfelli
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: 	  bulk-image-resizer
 * Domain Path: 	  /languages
 */

if (!defined('WPINC')) die;
define('bulk-image-resizer_VERSION', '1.2.0');

require_once(plugin_dir_path( __FILE__ ) . "includes/op-functions.php");
require_once(plugin_dir_path( __FILE__ ) . "includes/class-bulk-image-resizer-loader.php");
$bulk_image_resizer_loader = new Bulk_image_resizer_loader();
// Chiamo la funzione op_activate quando il plugin viene attivato

register_uninstall_hook(__FILE__, [$bulk_image_resizer_loader, 'uninstall']);
register_activation_hook( __FILE__,  [$bulk_image_resizer_loader, 'activate'] );

if (!is_admin()) return;
require_once(plugin_dir_path( __FILE__ ) . "admin/class-bulk-image-resiers-admin.php");
require_once(plugin_dir_path( __FILE__ ) . "includes/class-bulk-image-resizer-loader-ajax.php");
// Carico i file della lingua
load_plugin_textdomain('bulk-image-resizer', false, plugin_dir_path( __FILE__ ) . 'languages');

$bulk_image_resizer_ajax = new Bulk_image_resizer_loader_ajax();
$admin = new Bulk_image_resizer_admin();



