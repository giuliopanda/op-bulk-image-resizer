<?php
/**
 * op bulk image resizer è un plugin per il resize delle immagini caricate su wordpress
 * 
 * This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @since             0.9.0
 * @package           op-bulk-image-resizer
 *
 * @wordpress-plugin
 * Plugin Name:       Optica Press: bulk image resizer
 * Description:       Bulk image resizer and uload resize
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Giulio Pandolfelli
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: 	  op-bir
 * Domain Path: 	  /languages
 */

if (!defined('WPINC')) die;
if (!is_admin()) return;
/**
 * Currently plugin version. https://semver.org
 */
define('OP-BULK-IMAGE-RESIZER_VERSION', '0.9.0');

// verifico se si sta caricando la pagina del plugin o altre pagine del sito
$op_plugin_basename = str_replace(".php", "", basename(__FILE__));
$op_execute_plugin = (isset($_REQUEST['page']) && @$_REQUEST['page'] == $op_plugin_basename);

require_once(plugin_dir_path( __FILE__ ) . "op-activation.php");
require_once(plugin_dir_path( __FILE__ ) . "includes/op-loader-ajax.php");
require_once(plugin_dir_path( __FILE__ ) . "includes/op-functions.php");
require_once(plugin_dir_path( __FILE__ ) . "includes/op-loader-add-image-column.php");
require_once(plugin_dir_path( __FILE__ ) . "includes/op-i18n.php");
require_once(plugin_dir_path( __FILE__ ) . "admin/partials/op-partials.php");
require_once(plugin_dir_path( __FILE__ ) . "admin/op-admin.php");

// Chiamo la funzione op_activate quando il plugin viene attivato
register_activation_hook(__FILE__, 'op_activate');



