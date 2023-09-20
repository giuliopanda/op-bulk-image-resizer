<?php
/**
* La gestione degli hook riguardanti la media library
* @since      2.0.0
 */
namespace bulk_image_resizer;

if (!defined('WPINC')) die;

class Bir_loader_media_library extends Bir_loader {

	/**
	 * Inizializzo tutti i loader per la media library
	 */
	public function __construct() {
		// adds the entry for the select in media library
		add_filter('bulk_actions-upload',  [$this, 'bulk_action_upload'] );
		// the bulk of the media library select
		add_filter('handle_bulk_actions-upload',  [$this, 'handle_bulk_actions_upload'] , 10, 3);
		// Aggiungo un testo che avverte mentre carichi se c'è il resize attivo
		add_action('post-plupload-upload-ui', [$this, 'info_upload']);
		// ripristino il nome dell'immagine originale se c'è stato un generate attachment metadata perché wordpress lo cancella
		add_filter('wp_update_attachment_metadata',  [$this, 'generate_attachment_metadata'], 10, 2);
		// Aggiungo il filtro per la qualità delle immagini
		add_filter( 'jpeg_quality',  [$this, 'jpeg_quality'] );
	}

	/**
	 * I do the bulk added in the select
	 */
	public function handle_bulk_actions_upload  ($redirect_url, $action, $post_ids) {
		if ($action == 'op-resize-original-images') {
			foreach ($post_ids as $post_id) {
				Bir_facade::process_image($post_id);
			}
		}

		if ($action == 'op-revert-original-images') {
			foreach ($post_ids as $post_id) {
				Bir_facade::restore($post_id);
			}
		}

		return $redirect_url;
	}

	/**
	 * I update the "media library bulk" select with the new option
	 */
	public function bulk_action_upload ($bulk_actions) {
		global $bir_options;
		if ($bir_options == null) $bir_options = new Bir_options_var();
		if ($bir_options->plugin_active()) {
			$bulk_actions['op-resize-original-images'] = __('Optimize (From bulk images plugin)', 'bulk-image-resizer');
		}
		$bulk_actions['op-revert-original-images'] = __('Restore (From bulk images plugin)', 'bulk-image-resizer');
		return $bulk_actions;
	}

	/**
	 * Aggiungo un testo che avverte mentre carichi se c'è il resize attivo
	 */
	public function info_upload() {
		global $bir_options;
		if ($bir_options == null) $bir_options = new Bir_options_var();
		//var_dump ($bir_options);
		echo '<div style="border: 1px solid #d3d3d3; background: #e5e5e5; padding: 1rem; margin: 1rem 0;">';
		if ($bir_options->optimize_active == 1 || $bir_options->webp_active == 1) {
			echo 'Images are optimized using the plugin <strong><a href="'.admin_url('admin.php?page=bulk-images').'" >Bulk Image Resizer</a></strong>';
		} else if ($bir_options->resize_active == 1) {
			echo '<strong><a href="'.admin_url('admin.php?page=bulk-images').'" >Bulk Image Resizer plugin</a></strong>: Images larger than '.$bir_options->max_width.'X'.$bir_options->max_height.'px will be resized.';
		} else {
			echo 'Activate the <strong><a href="'.admin_url('admin.php?page=bulk-images').'" >Bulk Image Resizer </a></strong> plugin to optimize images';
		}
		echo '</div>';
	}

	/**
	 * ripristino il nome dell'immagine originale se c'è stato un generate attachment metadata perché wordpress lo cancella
	 * Per rimuovere original image la imposto a ''
	 */
	public function generate_attachment_metadata($metadata, $attachment_id) {
		$meta_old = wp_get_attachment_metadata($attachment_id);
		
		if (array_key_exists('original_image', $metadata) && $metadata['original_image'] === '') {
			unset($metadata['original_image']);
		} else if (isset($meta_old['original_image']) && !isset($metadata['original_image'])) {
			// verifico se esiste l'immagine
			$upload_dir = wp_upload_dir();
			$original_image = $upload_dir['basedir']. "/" .dirname($metadata['file']).'/'.$meta_old['original_image'];
			if (is_file($original_image)) {
				$metadata['original_image'] = $meta_old['original_image'];
			}
		}
		
		return $metadata;
	}

	/**
	 * Aggiungo il filtro per la qualità delle immagini
	 */
	public function jpeg_quality() {
		global $bir_options;
		if ($bir_options == null) $bir_options = new Bir_options_var();
		return $bir_options->quality;
	}
	
}

new Bir_loader_media_library();