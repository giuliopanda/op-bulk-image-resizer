<?php
class Bulk_image_resizer_admin 
{
    public function __construct() {
      
        add_action( 'admin_menu',  [$this, 'opbir_add_admin_menu'] );
    }
    /**
     * Carico il template della pagina
     * Questa funzione viene caricata solo se sei nella pagina del plugin!
     */
    function template_page() {
        wp_enqueue_style( 'bulk-image-resizer-style' , plugin_dir_url( __FILE__ ) . 'css/bulk-image-resizer.css');
 	    wp_enqueue_script( 'bulk-image-resizer-chart', plugin_dir_url( __FILE__ ) . 'js/chart.js');
 	    wp_enqueue_script( 'bulk-image-resizer-js'   , plugin_dir_url( __FILE__ ) . 'js/bulk-image-resizer.js');
		$options = opBulkImageResizer\Includes\OpFunctions\op_get_resize_options();
		$check_fn_editor = opBulkImageResizer\Includes\OpFunctions\check_image_editor();
        require (plugin_dir_path(__FILE__)."partials/bulk-image-resizer-container.php");
    }
    /**
	 * Importo la voce di menu nell'amministrazione
	 */
	public function opbir_add_admin_menu() {
		$menu_id = add_management_page(
			__('Bulk image resizer', 'bulk-image-resizer'),
			'Bulk image resizer',
			'manage_options',
			'bulk-image-resizer',
			[$this, 'template_page']
		);
	}
}