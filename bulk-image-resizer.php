<?php
/**
 * op bulk image resizer è un plugin per il resize delle immagini caricate su wordpress
 * 
 * This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @package           op-bulk-image-resizer
 *
 * @wordpress-plugin
 * Plugin Name:       Op Bulk image resizer
 * Plugin URI:        https://github.com/giuliopanda/op-bulk-image-resizer
 * Description:       Optimize images uploaded to the server. Go to "tools" to configure the plugin or to perform batch optimization. Go to "Media library (list view)" to optimize individual images.
 * Version:           1.1.0
 * Requires at least: 5.3
 * Requires PHP:      5.6
 * Author:            Giulio Pandolfelli
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: 	  op-bulk-image-resizer
 * Domain Path: 	  /languages
 */

if (!defined('WPINC')) die;
define('OP-BULK-IMAGE-RESIZER_VERSION', '1.1.0');

require_once(plugin_dir_path( __FILE__ ) . "includes/op-functions.php");
require_once(plugin_dir_path( __FILE__ ) . "includes/class-bulk-image-resizer-loader.php");
$bulk_image_resizer_ajax_loader = new Bulk_image_resizer_loader();
// Chiamo la funzione op_activate quando il plugin viene attivato

register_uninstall_hook(__FILE__, [$bulk_image_resizer_ajax_loader, 'uninstall']);
register_activation_hook( __FILE__,  [$bulk_image_resizer_ajax_loader, 'activate'] );

if (!is_admin()) return;
require_once(plugin_dir_path( __FILE__ ) . "admin/class-bulk-image-resiers-admin.php");
require_once(plugin_dir_path( __FILE__ ) . "includes/class-bulk-image-resizer-loader-ajax.php");
// Carico i file della lingua
load_plugin_textdomain('op-bulk-image-resizer', false, plugin_dir_path( __FILE__ ) . 'languages');

$bulk_image_resizer_ajax = new Bulk_image_resizer_loader_ajax();
$admin = new Bulk_image_resizer_admin();

/**
 * Activation
 */



/**
 * Aggiunge una option per segnalare che il plugin è stato appena attivato.
 */
