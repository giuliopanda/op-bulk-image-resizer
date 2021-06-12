<?php
/**
 * Gestisco il filtri e hook
 * 
 * @since      1.1.0
 *
 * @package    op-bulk-image-resizer
 * @subpackage op-bulk-image-resizer/includes
 */
use opBulkImageResizer\Includes\OpFunctions AS Opfn;
if (!defined('WPINC')) die;

/****************************************************
 *  GESTISCO IL FILTRI PER LA PAGINA MEDIA LIBRARY  *
 ****************************************************/
class Bulk_image_resizer_loader {

	public function __construct() {
		add_filter('manage_media_columns', [$this, 'media_columns_filesize']);
		// aggiungo nell'header dell'amministrazione la funzione js per tradurre i js
		if ((isset($_REQUEST['page']) && $_REQUEST['page'] == 'op-bulk-image-resizer')) {
			add_action('admin_head', [$this, 'transalte_javascript']);
		}
		// aggiunge nell'head della pagina upload
		add_action('admin_print_styles-upload.php', [$this, 'filesize_column_filesize']);
		// aggiunge una colonna su media library
		add_action('manage_media_custom_column', [$this, 'media_custom_column_filesize'], 10, 2);
		// quando si carica un file
		add_filter( 'wp_handle_upload', [$this, 'handle_upload'], 10, 2  );
		// add_action( 'admin_menu',  [$this, 'opbir_add_admin_menu'] ); Questo è spostato dentro ../admin/bulk-image-resizer-admin.php
		// aggiunge la voce per il select in media library
		add_filter('bulk_actions-upload',  [$this, 'bulk_action_upload'] );
		// il bulk del select di media library
		add_filter('handle_bulk_actions-upload',  [$this, 'handle_bulk_actions_upload'] , 10, 3);
		// Aggiungo un testo che avverte mentre carichi se c'è il resize attivo
		add_action('post-plupload-upload-ui', [$this, 'info_upload']);

		add_action('admin_notices', [$this,'welcome_message']);
		
		// uninstall e activate vengono chiamate da op-bulk-image-resizer.php
	}

	/**
	 * Aggiorno il select del media library bulk con la nuova opzione
	 */
	public function bulk_action_upload ($bulk_actions) {
		list($width, $height, $quality) = Opfn\op_get_resize_options();
		$bulk_actions['op-resize-original-images'] = sprintf(__('Resize: (%s)', 'op-bir'), $width . "x" . $height);
		return $bulk_actions;
	}

	/**
	 * Eseguo il bulk aggunto nel select
	 */
	public function handle_bulk_actions_upload  ($redirect_url, $action, $post_ids) {
		if ($action == 'op-resize-original-images') {
			foreach ($post_ids as $post_id) {
				Opfn\op_optimize_single_img($post_id);
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
		$posts_columns['filesize'] = __('File Size', 'my-theme-text-domain');
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
		if ('filesize' !== $column_name) {
			return;
		}
		list($width, $height, $quality) = Opfn\op_get_resize_options();
		$path_img = wp_get_original_image_path($post_id);
		if (file_is_valid_image($path_img)) {
			$img = wp_get_image_editor($path_img);
			if (!is_wp_error($img)) {
				$img2 = $img->get_size();
				$bytes = filesize(get_attached_file($post_id));
				$max_quality = ($width * $height * .6) * ($quality / 150); // quanto dovrebbe essere al massimo l'immagine
				$show_btn = false;
				if ($width < $img2['width'] || $height < $img2['height']) {
					$show_btn = true;
					$class = "gp_color_warning";
				} else {
					$class = " gp_color_ok";
				}
				if ($max_quality < $bytes && stripos($path_img,'.jpg') !== false) {
					$show_btn = true;
					$class2 = "gp_color_warning";
				} else {
					$class2 = " gp_color_ok";
				}

				echo '<div id="op_info_td_' . esc_attr($post_id) . '">';
				echo "<div class=\"" . esc_attr($class) . "\">" . esc_html($img2['width'] . "px X " . $img2['height']) . "px</div>";
				echo "<div class=\"" . esc_attr($class2) . "\">" . size_format($bytes, 2) . "</div>";
				if ($show_btn) {
					echo '<div class="button button-primary button-small" onclick="op_single(' . esc_attr($post_id) . ', \'' . size_format($bytes, 2) . '\', \'' .  esc_attr($img2['width'] . "px X " . $img2['height']) . 'px\')">' . __('Optimize') . '</div>';
				}
				echo '</div>';
			}
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
		if (get_option('op_resize_on_upload', 0) == 1) {
			list($width, $height, $quality) = Opfn\op_get_resize_options();
			echo "Images larger than ".$width."X".$height."px will be resized.";
		} else {
			echo 'Do you want to resize too large images during upload?';
		}
		echo ' <a href="'.admin_url('admin.php?page=op-bulk-image-resizer').'" >'.__('go to Bulk image resizer', 'op-bir').'</a></p>';
	}

	/*********************************************************************
	 *  GESTISCO IL FILTRI PER IL RESIZE DELLE IMMAGINI APPENA CARICATE  *
	 *********************************************************************/

	/**
	 * Viene chiamata ogni volta che si carica un file
	*/
	public function  handle_upload( $upload, $context) {
		$post_id = 0;
		if (array_key_exists('post', $_REQUEST)) {
			$post_id = absint($_REQUEST['post']);
		}
		if (is_array($upload) && array_key_exists('type', $upload) && strpos($upload['type'], 'image') !== false) {
			if (get_option('op_resize_on_upload', 0) == 1) {
				//TODO Un filtro che se torna false non fa l'upload, se torna un array width, height fa il resize. (file_name, post_id)
				
				$size = wp_getimagesize( $upload['file'] );
				if (! empty( $size )) {
					list($width, $height, $quality) = Opfn\op_get_resize_options();
					/**
					 * resize image while uploading.
					 * * Check whether to resize the image. You can choose custom width and height.
					 *
					 * @since 0.9.5
					 *
					 * @param string $filename
					 * @param int $attachment_id
					 * @return boolean|array  [width,height]
					 */
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
						if ($width > 100 && $height > 100) {
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
	
	/**
	 * Quando viene rimosso il plugin
	 */
	public function uninstall() {
		delete_option('op_resize_statistics');
		delete_option('op_resize_max_width');
		delete_option('op_resize_max_height');
		delete_option('op_resize_quality');
		delete_option('op_resize_on_upload');
		delete_option('op_resize_images_done');
		delete_option('op_bulk_image_resizer');
	}
	/**
	 * Quando viene attivato il plugin
	 */
	function activate() {
		update_option('op_bulk_image_resizer', 2, false);
	}
	/**
	 * Carica un popup al caricamento della pagina dei plugin amministrativi
	 */
	function welcome_message() {
		if (is_admin()) {
			$screen = get_current_screen();
			$op_bulk_image_resizer = absint(get_option('op_bulk_image_resizer', 0));
			if ($screen->id == 'plugins' && get_option('op_bulk_image_resizer', '') > 0) {
				require(plugin_dir_path(plugin_dir_path( __FILE__ )) . 'admin/partials/bulk-image-resizer-popup-activation-plugin.php');
				$op_bulk_image_resizer = $op_bulk_image_resizer -1;
				if ($op_bulk_image_resizer > 0) {
					update_option('op_bulk_image_resizer', $op_bulk_image_resizer);
				} else {
					delete_option('op_bulk_image_resizer'); 
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
				var t9n_pause = "<?php (_e("Updates are paused. Click on resume to continue.", "op-bir")); ?>";
				var t9n_confirm_1 = "<?php echo (__("Are you sure you want to resize images with a width less than 500px?", "op-bir")); ?>";
				var t9n_confirm_2 = "<?php echo (__("Are you sure you want to resize images with a height less than 500px?", "op-bir")); ?>";
				var t9n_confirm_3 = "<?php echo (__("Are you sure you want to leave the page?", "op-bir")); ?>";
				var t9n_wait_settings = "<?php echo (__("Wait for the end of saving the settings", "op-bir")); ?>";
				var t9n_analisys = "<?php echo (__("Analyzing the update in progress ...", "op-bir")); ?>";
				var t9n_start_resize = "<?php echo (__("I start resizing the images", "op-bir")); ?>";
				var t9n_warning_resize = "<?php echo (__("Do not leave the page while you are resizing your images", "op-bir")); ?>";
				var t9n_we_are_almost_there = "<?php echo (__("We are almost there", "op-bir")); ?>";
				var t9n_spared = "<?php echo (__("They were spared: <b> %s </b>.", "op-bir")); ?>";
				var t9n_end_1 = "<?php echo (__("The update has finished", "op-bir")); ?>";
				var t9n_end_2 = "<?php echo (__("Now the used space is: <b>%s</b>", "op-bir")); ?>";
				var t9n_end_3 = "<?php echo (__("The elapsed time is: %s", "op-bir")); ?>";
				var t9n_time_remaining = "<?php echo (__("Estimated time remaining: %s", "op-bir")); ?>";
				var t9n_time_analisys = "<?php echo (__("Estimated time analysis in progress.", "op-bir")); ?>";
				var t9n_img_left = "<?php echo (__("Still to be processed: %s", "op-bir")); ?>";
				var t9n_none = "<?php echo (__("No images found to update", "op-bir")); ?>";
				var t9n_ops = "<?php echo (__("I am having difficulty contacting the server.", "op-bir")); ?>";
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