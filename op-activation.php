<?php
/**
 * Gestisce gli eventi quando viene installato/attivato/disattivato/rimosso il plugin
 * La registrazione degli hook è scritta sul file principale
 * @since      0.9.0
 *
 * @package    op-bulk-image-resizer
 */

if (!defined('WPINC')) die;

/**
 * Carica un popup al caricamento della pagina dei plugin amministrativi
 */
function op_welcome_message() {
	if (is_admin()) {
		$screen = get_current_screen();
		if ($screen->id == 'plugins' && get_option('op_bulk_image_resizer', '') != '') {
			op_popup_activation_plugin();
		//	delete_option('op_bulk_image_resizer'); // cancella il popup dopo che è stato visualizzato la prima volta
		}
	}
}
add_action('admin_notices', 'op_welcome_message');

/**
 * Aggiunge una option per segnalare che il plugin è stato appena attivato.
 */
function op_activate() {
	add_option('op_bulk_image_resizer', 'activate');
}