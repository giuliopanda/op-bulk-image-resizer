<?php
/**
 * Gestisco il filtri e hook
 * 
 * @since      1.1.0
 *
 * @package    bulk-image-resizer
 * @subpackage bulk-image-resizer/includes
 */
use opBulkImageResizer\Includes\OpFunctions AS Opfn;
if (!defined('WPINC')) die;

/****************************************************
 *  GESTISCO IL FILTRI PER LA PAGINA MEDIA LIBRARY  *
 ****************************************************/
class Bulk_image_resizer_loader {

	public function __construct() {
		
		// I add in the administration header the js function to translate the js
		if ((isset($_REQUEST['page']) && $_REQUEST['page'] == 'bulk-image-resizer')) {
			add_action('admin_head', [$this, 'transalte_javascript']);
		}
		// adds in the head of the page upload
		add_action('admin_print_styles-upload.php', [$this, 'filesize_column_filesize']);
		// adds a column on media library
		$options = Opfn\op_get_resize_options();
		if ($options['on_upload']) {
			add_filter('manage_media_columns', [$this, 'media_columns_filesize']);
			add_action('manage_media_custom_column', [$this, 'media_custom_column_filesize'], 10, 2);
		}
		// when uploading a file
		//add_filter( 'wp_handle_upload', [$this, 'handle_upload'], 10, 2  );
		// add_action( 'admin_menu',  [$this, 'opbir_add_admin_menu'] ); Questo è spostato dentro ../admin/class-bulk-image-resizer-admin.php
		// adds the entry for the select in media library
		add_filter('bulk_actions-upload',  [$this, 'bulk_action_upload'] );
		// the bulk of the media library select
		add_filter('handle_bulk_actions-upload',  [$this, 'handle_bulk_actions_upload'] , 10, 3);
		// Aggiungo un testo che avverte mentre carichi se c'è il resize attivo
		add_action('post-plupload-upload-ui', [$this, 'info_upload']);

		add_action('admin_notices', [$this,'welcome_message']);
		// TEST di gestione inserimenti nuovo
		add_filter( 'wp_generate_attachment_metadata',  [$this, 'wp_generate_attachment_metadata'], 10,2 ) ;
		//viene chiamato ogni volta che un plugin viene aggiornato
		//add_action( 'upgrader_process_complete', [$this,  'upgrade_completed'], 10, 2 );
	}

	/**
	 * I update the "media library bulk" select with the new option
	 */
	public function bulk_action_upload ($bulk_actions) {
		$json_option = Opfn\op_get_resize_options();
		$bulk_actions['op-resize-original-images'] = sprintf(__('Resize: (%s)', 'bulk-image-resizer'), $json_option['max_width'] . "x" . $json_option['max_height']);
		$bulk_actions['op-revert-original-images'] = sprintf(__('Revert back to the original image', 'bulk-image-resizer'));
		return $bulk_actions;
	}

	/**
	 * I do the bulk added in the select
	 */
	public function handle_bulk_actions_upload  ($redirect_url, $action, $post_ids) {
		if ($action == 'op-resize-original-images') {
			foreach ($post_ids as $post_id) {
				Opfn\op_optimize_single_img($post_id);
			}
		}

		if ($action == 'op-revert-original-images') {
			foreach ($post_ids as $post_id) {
				Opfn\op_optimize_revert_original_img($post_id);
			}
		}

		return $redirect_url;
	}

	/**
	 * Filter the Media list table columns to add a File Size column.
	 *
	 * @param array $posts_columns Existing array of columns displayed in the Media list table.
	 * @return array Amended array of columns to be displayed in the Media list table.
	 */
	public function media_columns_filesize($posts_columns) {
		if (Opfn\check_image_editor() == '') {
			$posts_columns['bir'] = __('Bulk image resizer', 'bulk-image-resizer');
		}
		return $posts_columns;
	}
	
	/**
	 * Display File Size custom column in the Media list table.
	 *
	 * @param string $column_name Name of the custom column.
	 * @param int    $post_id Current Attachment ID.
	 */
	public function media_custom_column_filesize($column_name, $post_id)
	{
		if ('bir' !== $column_name || Opfn\check_image_editor() != '' ) {
			return;
		}
		
		$path_attached = get_attached_file($post_id);
		$img_attached = Opfn\op_get_image_info($path_attached);
		if ($img_attached['is_valid'] && $img_attached['is_writable']) {
			$path_original = wp_get_original_image_path($post_id);
			$img_original =  Opfn\op_get_image_info($path_original);

			 // Documentation in op-function:98
			$resize = true;
			$options = Opfn\op_get_resize_options();
			$override_options = false;
			$ris_filter = apply_filters( 'op_bir_resize_image_bulk', wp_basename($path_attached), $post_id);
			if (is_array($ris_filter) && count($ris_filter) == 2) {
				if (array_key_exists('width', $ris_filter) && array_key_exists('height', $ris_filter)) {
					$options['max_width'] = $ris_filter['width'];
					$options['max_height'] = $ris_filter['height'];
					$override_options = true;
				} else {
					$options['max_width'] = array_shift($ris_filter);
					$options['max_height'] = array_shift($ris_filter);
					$override_options = true;
				}	
				
			} elseif (is_bool($ris_filter)) {
				$resize = $ris_filter;
			}
			echo '<div id="op_info_td_' . esc_attr($post_id) . '">';
			if (($path_original == $path_attached && $path_attached != '') || $path_original == "") {
				echo '<div class="' . esc_attr($img_attached['class_resize']) . '">' . esc_html($img_attached['width'] . 'px X ' . $img_attached['height']) . 'px</div>';
				echo '<div class="' . esc_attr($img_attached['class_size']) . '">' . size_format($img_attached['file_size'], 2) . '</div>';
			} else if ($img_original['is_valid']) {
				
				if ($img_attached['width'] > $options['max_width'] || $img_attached['height'] > $options['max_height'] ) {
					$img_attached['show_btn'] = true;
					$img_attached['class_resize'] = 'gp_color_warning';
				} else {
					$img_attached['show_btn'] = false;
					$img_attached['class_resize'] = 'gp_color_ok';
				}
				if ($options['delete_original'] == 1) {
					$img_attached['show_btn'] = true;
					$img_original['class_resize'] = 'gp_color_warning';
				} else {
					$img_original['class_resize'] = 'gp_color_ok';
				}
				echo '<div class="' . esc_attr($img_original['class_resize']) . '">'. __('Original:', 'bulk-image-resizer') . ' ' . esc_html($img_original['width'] . 'px X ' . $img_original['height']) . 'px ' .  __('size:','bulk-image-resizer'). ' ' .  size_format($img_original['file_size'], 2)  . '</div>';
				echo '<div class="' . esc_attr($img_attached['class_resize']) . '">'. __('Compressed:', 'bulk-image-resizer') . ' ' . esc_html($img_attached['width'] . 'px X ' . $img_attached['height']) . 'px ' .  __('size:','bulk-image-resizer') . ' ' .   size_format($img_attached['file_size'], 2) . '</div>';
				if ($override_options ) {
					_e("A filter has overwritten the maximum image size");
				}
				
			} else {
				echo '<div class="button button-primary button-small" onclick="op_single(' . esc_attr($post_id) . ', \'' . size_format($img_attached['file_size'], 2) . '\', \'' .  esc_attr($img_attached['width'] . 'px X ' . $img_attached['height']) . 'px\')">' . __('FIXED', 'bulk-image-resizer') . '</div>';

			}
			if (get_post_meta($post_id, '_bulk_image_resizer_non_optimized', true)) {
				_e("The image is compressed very well");
			} else {
				if ($img_attached['show_btn'] ) {
					if (!$resize) {
						_e("A filter has been added that inhibits the resizing of this image");
					} else {
						$exists_original = ($path_original != $path_attached && $path_attached != '' && $path_original != "");
						if ($exists_original && $img_original['is_valid'] &&  $options['delete_original'] == 1) { 
							$btn_msg = "Remove Original and resize (%s)";
						} else {
							$btn_msg = "Optimize (%s)";
						}
						echo '<div class="button button-primary button-small" onclick="op_single(' . esc_attr($post_id) . ', \'' . size_format($img_attached['file_size'], 2) . '\', \'' .  esc_attr($img_attached['width'] . 'px X ' . $img_attached['height']) . 'px\')">' . sprintf(__($btn_msg, 'bulk-image-resizer'), $options['max_width'] ."px X ". $options['max_height']."px"). '</div>';
						
			
					}
				}
			}
			echo '</div>';
		} else if (!$img_attached['is_writable']) {
			echo __("Attention the image is not writable", 'bulk-image-resizer');
		} else if (isset($img_attached['msg'])) {
			echo __($img_attached['msg'], 'bulk-image-resizer');
		}
	}
	

	/**
	 * Adjust File Size column on Media Library page in WP admin
	 */
	public function filesize_column_filesize() {
	?><style>
			.fixed .column-filesize {width:10%}
			.gp_color_warning {color: #A00}
		</style>
		<script>
			function op_single(postId, old_size, old_dim) {
				jQuery('#op_info_td_' + postId).empty().append('<div class="spinner" style="visibility:inherit;float:initial"></div>');
				jQuery.ajax({
					method: "GET",
					url: '<?php echo admin_url('admin-ajax.php'); ?>',
					dataType: "json",
					data: {
						action: "op_resize_single",
						post_id: postId,
						old_size: old_size,
						old_dim: old_dim

					}
				}).done(function(ris) {
					if (ris.response == 'error') {
						alert(ris.msg);
					} else {
						jQuery('#op_info_td_' + ris.post_id).empty().append('<div style="text-decoration:line-through;color:#999">' + ris.old_dim + "</div>");
						jQuery('#op_info_td_' + ris.post_id).append('<div style="text-decoration:line-through;color:#999">' + ris.old_size + "</div>");
						jQuery('#op_info_td_' + ris.post_id).append('<div>' + ris.width + "px X " + ris.height + "px</div>");
						jQuery('#op_info_td_' + ris.post_id).append('<div >' + ris.size + "</div>");
					}
				}).error(function() {
					alert('Unexpected server error');
				});
			}
		</script>
	<?php
	}
	
	public function info_upload() {
		echo "<p>";
		if (Opfn\op_get_resize_options('on_upload', 0) == 1) {
			$json_option = Opfn\op_get_resize_options();
			echo "Images larger than ".$json_option['max_width']."X".$json_option['max_height']."px will be resized.";
		} else {
			echo 'Do you want to resize too large images during upload?';
		}
		echo ' <a href="'.admin_url('admin.php?page=bulk-image-resizer').'" >'.__('go to Bulk image resizer', 'bulk-image-resizer').'</a></p>';
	}

	/*********************************************************************
	 *  GESTISCO IL FILTRI PER IL RESIZE DELLE IMMAGINI APPENA CARICATE  *
	 *********************************************************************/

	/**
	 * Viene chiamata ogni volta che si carica un file
	*/
	/*
	public function  handle_upload( $upload, $context) {
		$post_id = 0;
		if (array_key_exists('post', $_REQUEST)) {
			$post_id = absint($_REQUEST['post']);
		}
		if (is_array($upload) && array_key_exists('type', $upload) && strpos($upload['type'], 'image') !== false) {
			if (Opfn\op_get_resize_options('on_upload', 0) == 1 && $json_option['delete_original'] == 1) {
				//TODO Un filtro che se torna false non fa l'upload, se torna un array width, height fa il resize. (file_name, post_id)
				
				$size = wp_getimagesize( $upload['file'] );
				if (! empty( $size )) {
					$json_option = Opfn\op_get_resize_options();
					$width      = $json_option['max_width'];
					$height     = $json_option['max_height'];
					$quality    = $json_option['quality'];

					$ris_filter = apply_filters( 'op_bir_resize_image_uploading', wp_basename($upload['file']), $post_id);
					$resize = true;
					if (is_array($ris_filter) && count($ris_filter) == 2) {
						if (array_key_exists('width', $ris_filter) && array_key_exists('height', $ris_filter)) {
							$width = $ris_filter['width'];
							$height = $ris_filter['height'];
						} else {
							$width = array_shift($ris_filter);
							$height = array_shift($ris_filter);
						}	
					} elseif (is_bool($ris_filter)) {
						$resize = $ris_filter;
					}
					if (!$resize) return $upload;
					$img = wp_get_image_editor($upload['file']);
					if (!is_wp_error($img)) {
						if ($width > 99 && $height > 99) {
							$img->resize($width, $height); 
						}
						$img->set_quality($quality);
						$save = $img->save($upload['file']);
						
						return $upload;
					} else {
						return $img;
					}	
				}
			}
		}
		
		return $upload;
	}
*/


	/**
	 * Preferisco rispetto a handle_upload
	 */
	public function wp_generate_attachment_metadata( $attachment, $id) {
		if (Opfn\op_get_resize_options('on_upload',0) == 1) {
			Opfn\op_optimize_single_img($id);
			$return_attachment = wp_get_attachment_metadata($id, true);
			if ($return_attachment !== false) {
				$attachment = $return_attachment;
			}
		}
		
		return $attachment;
	}
	



	/**
	 * Evento chiamato ogni volta che un plugin o un tema viene aggiornato
	 * @deprecated v.1.3.1
	 */
	function upgrade_completed( $upgrader_object, $options ) {
		// The path to our plugin's main file
		$our_plugin = 'bulk-image-resizer';
		// If an update has taken place and the updated type is plugins and the plugins element exists
		if( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) && in_array('bulk-image-resizer',$options['plugins'] ) ) {
		// Iterate through the plugins being updated and check if ours is there
			if (get_option('op_resize_max_width', 0) > 0 && (int)get_option('op_resize_max_height', 0) > 0 && get_option('bulk_image_resizer', '') == '') {
				$resizer = array();
		
				$resizer['max_width'] = (int)get_option('op_resize_max_width', '1920');
				$resizer['max_height'] = (int)get_option('op_resize_max_height', '1080');
				$resizer['quality'] = (int)get_option('op_resize_quality', '75');
				$resizer['version'] = '1.3.1';

				update_option('bulk_image_resizer', json_encode($resizer), false);	
				delete_option('op_resize_max_width');
				delete_option('op_resize_max_height');
				delete_option('op_resize_quality');
			}
		}
	}

	/**
	 * Carica un popup al caricamento della pagina dei plugin amministrativi
	 */
	function welcome_message() {
		if (is_admin()) {
			$screen = get_current_screen();
			$op_bulk_image_resizer = absint(get_option('bulk_image_resizer_welcome', 0));
			if ($screen->id == 'plugins' && $op_bulk_image_resizer > 0) {
				require(plugin_dir_path(plugin_dir_path( __FILE__ )) . 'admin/partials/bulk-image-resizer-popup-activation-plugin.php');
				$op_bulk_image_resizer = $op_bulk_image_resizer -1;
				if ($op_bulk_image_resizer > 0) {
					update_option('bulk_image_resizer_welcome', $op_bulk_image_resizer, false);
				} else {
					delete_option('bulk_image_resizer_welcome'); 
				}
			}
		}
	}
	/**
	 * Quì traduco i vari file della lingua per il javascript. 
	 * Non uso il metodo consigliato da wordpess perché l'ho trovato troppo complicato.
	 */
	public function transalte_javascript() {
		?>
			<script>
				var t9n_pause = "<?php (_e("Updates are paused. Click on resume to continue.", "bulk-image-resizer")); ?>";
				var t9n_confirm_1 = "<?php echo (__("Are you sure you want to resize images with a width less than 500px?", "bulk-image-resizer")); ?>";
				var t9n_confirm_2 = "<?php echo (__("Are you sure you want to resize images with a height less than 500px?", "bulk-image-resizer")); ?>";
				var t9n_confirm_3 = "<?php echo (__("Are you sure you want to leave the page?", "bulk-image-resizer")); ?>";
				var t9n_wait_settings = "<?php echo (__("Wait for the end of saving the settings", "bulk-image-resizer")); ?>";
				var t9n_analisys = "<?php echo (__("Analyzing the update in progress ...", "bulk-image-resizer")); ?>";
				var t9n_start_resize = "<?php echo (__("I start resizing the images", "bulk-image-resizer")); ?>";
				var t9n_warning_resize = "<?php echo (__("Do not leave the page while you are resizing your images", "bulk-image-resizer")); ?>";
				var t9n_we_are_almost_there = "<?php echo (__("We are almost there", "bulk-image-resizer")); ?>";
				var t9n_spared = "<?php echo (__("They were spared: <b> %s </b>.", "bulk-image-resizer")); ?>";
				var t9n_end_1 = "<?php echo (__("The update has finished", "bulk-image-resizer")); ?>";
				var t9n_end_2 = "<?php echo (__("Now the used space is: <b>%s</b>", "bulk-image-resizer")); ?>";
				var t9n_end_3 = "<?php echo (__("The elapsed time is: %s", "bulk-image-resizer")); ?>";
				var t9n_time_remaining = "<?php echo (__("Estimated time remaining: %s", "bulk-image-resizer")); ?>";
				var t9n_time_analisys = "<?php echo (__("Estimated time analysis in progress.", "bulk-image-resizer")); ?>";
				var t9n_img_left = "<?php echo (__("Still to be processed: %s", "bulk-image-resizer")); ?>";
				var t9n_none = "<?php echo (__("No images found to update", "bulk-image-resizer")); ?>";
				var t9n_ops = "<?php echo (__("I am having difficulty contacting the server.", "bulk-image-resizer")); ?>";
				function t9n_sprintf(string) {
					var args = Array.prototype.slice.call(arguments);
					a = args.splice(0,1);
					if (args.length > 0) {
						for (x in args) {
							string = string.replace("%s", args[x]);
						}
					}
					return string;
				}
			</script>
		<?php
	}
}