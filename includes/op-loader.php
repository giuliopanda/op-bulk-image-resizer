<?php
/**
 * Gestisco il filtri e hook
 * 
 * @since      0.9.0
 *
 * @package    op-bulk-image-resizer
 * @subpackage op-bulk-image-resizer/includes
 */

 if (!defined('WPINC')) die;

/****************************************************
 *  GESTISCO IL FILTRI PER LA PAGINA MEDIA LIBRARY  *
 ****************************************************/
add_filter('bulk_actions-upload', function ($bulk_actions) {
	list($width, $height, $quality) = op_get_resize_options();
	$bulk_actions['gp-resize-original-images'] = sprintf(__('Resize: (%s)', 'op-bir'), $width . "x" . $height);
	return $bulk_actions;
});

add_filter('handle_bulk_actions-upload', function ($redirect_url, $action, $post_ids) {
	if ($action == 'gp-resize-original-images') {
		foreach ($post_ids as $post_id) {
			op_optimize_single_img($post_id);
		}
	}
	return $redirect_url;
}, 10, 3);

/**
 * Filter the Media list table columns to add a File Size column.
 *
 * @param array $posts_columns Existing array of columns displayed in the Media list table.
 * @return array Amended array of columns to be displayed in the Media list table.
 */
function op_media_columns_filesize($posts_columns)
{
	$posts_columns['filesize'] = __('File Size', 'my-theme-text-domain');
	return $posts_columns;
}
add_filter('manage_media_columns', 'op_media_columns_filesize');


/**
 * Display File Size custom column in the Media list table.
 *
 * @param string $column_name Name of the custom column.
 * @param int    $post_id Current Attachment ID.
 */
function op_media_custom_column_filesize($column_name, $post_id)
{
	if ('filesize' !== $column_name) {
		return;
	}
	list($width, $height, $quality) = op_get_resize_options();
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

			echo '<div id="op_info_td_' . $post_id . '">';
			echo "<div class=\"" . $class . "\">" . $img2['width'] . "px X " . $img2['height'] . "px</div>";
			echo "<div class=\"" . $class2 . "\">" . size_format($bytes, 2) . "</div>";
			if ($show_btn) {
				echo '<div class="button button-primary button-small" onclick="op_single(' . $post_id . ', \'' . size_format($bytes, 2) . '\', \'' .  $img2['width'] . "px X " . $img2['height'] . 'px\')">' . __('Optimize') . '</div>';
			}
			echo '</div>';
		}
	}
}
add_action('manage_media_custom_column', 'op_media_custom_column_filesize', 10, 2);

/**
 * Adjust File Size column on Media Library page in WP admin
 */
function op_filesize_column_filesize()
{
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
add_action('admin_print_styles-upload.php', 'op_filesize_column_filesize');


/*********************************************************************
 *  GESTISCO IL FILTRI PER IL RESIZE DELLE IMMAGINI APPENA CARICATE  *
 *********************************************************************/

 /**
  * Viene chiamata ogni volta che si carica un file
  */
function  op_handle_upload( $upload, $context) {
	$post_id = 0;
	if (array_key_exists('post', $_REQUEST)) {
		$post_id = (int)$_REQUEST['post'];
	}
	if (is_array($upload) && array_key_exists('type', $upload) && strpos($upload['type'], 'image') !== false) {
		if (get_option('op_resize_on_upload', 0) == 1) {
			//TODO Un filtro che se torna false non fa l'upload, se torna un array width, height fa il resize. (file_name, post_id)
			
			$size = wp_getimagesize( $upload['file'] );
			if (! empty( $size )) {
				list($width, $height, $quality) = op_get_resize_options();
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
add_filter( 'wp_handle_upload', 'op_handle_upload', 10, 2  );

/**
 * Quando viene rimosso il plugin
 */
function op_uninstall() {
	delete_option('op_resize_statistics');
	delete_option('op_resize_max_width');
	delete_option('op_resize_max_height');
	delete_option('op_resize_quality');
	delete_option('op_resize_on_upload');
	delete_option('op_resize_images_done');
}