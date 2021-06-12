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
        wp_enqueue_style( 'op-bulk-image-resizer-style' , plugin_dir_url( __FILE__ ) . 'admin/css/op-bulk-image-resizer.css');
 	    wp_enqueue_script( 'op-bulk-image-resizer-chart', plugin_dir_url( __FILE__ ) . 'admin/js/chart.js');
 	    wp_enqueue_script( 'op-bulk-image-resizer-js'   , plugin_dir_url( __FILE__ ) . 'admin/js/op-bulk-image-resizer.js');
        require (plugin_dir_path(__FILE__)."/admin/partials/bulk-image-resizer-container.php");
    }
    /**
	 * Importo la voce di menu nell'amministrazione
	 */
	public function opbir_add_admin_menu() {
		$menu_id = add_management_page(
			__('Bulk image resizer', 'op-bir'),
			'Bulk image resizer',
			'manage_options',
			'op-bulk-image-resizer',
			[$this, 'template_page']
		);
	}
}