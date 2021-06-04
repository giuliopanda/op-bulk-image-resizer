<?php

/**
 * The admin-specific functionality of the plugin.
 * 
 * @since      0.9.0
 *
 * @package    op-bulk-image-resizer
 * @subpackage op-bulk-image-resizer/admin
 */

 if (!defined('WPINC')) die;

/**
 * Importo la voce di menu nell'amministrazione
 */
function opbir_add_admin_menu() {
	$menu_id = add_management_page(
		__('Bulk image resizer', 'op-bir'),
		'Bulk image resizer',
		'manage_options',
		'op-bulk-image-resizer',
		'op_view_container'
	);
}
add_action( 'admin_menu', 'opbir_add_admin_menu' );

/**
 * Carico i css personalizzati
 */
if ($op_execute_plugin) {
 	wp_enqueue_style( 'op-bulk-image-resizer-style', plugin_dir_url( __FILE__ ) . 'css/op-bulk-image-resizer.css');
 	wp_enqueue_script( 'op-bulk-image-resizer-chart', plugin_dir_url( __FILE__ ) . 'js/chart.js');
 	wp_enqueue_script( 'op-bulk-image-resizer-js', plugin_dir_url( __FILE__ ) . 'js/op-bulk-image-resizer.js');
}

