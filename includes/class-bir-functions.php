<?php
/**
 * Funzioni generiche usate nel plugin
 * 
 */
namespace bulk_image_resizer;

class Bir_functions
{
    static $global_bulk_image_resizer_check_editor = '';
    static function check_image_editor() {
       
        if (is_bool(self::$global_bulk_image_resizer_check_editor)) {
            return self::$global_bulk_image_resizer_check_editor;
        }
        $path_check_img_dir = BULK_IMAGE_RESIZER_DIR.'1px.jpg';
        $path_check_img_url = plugins_url('bulk-image-resizer/1px.jpg' );
        if (!file_exists($path_check_img_dir)) {
            if ( (! extension_loaded( 'gd' ) || ! function_exists( 'gd_info' )) && ( ! extension_loaded( 'imagick' ) || ! class_exists( 'Imagick', false ) || ! class_exists( 'ImagickPixel', false )) ) {
                self::$global_bulk_image_resizer_check_editor =  __('There seems to be no php library for manipulating images.', 'bulk-image-resizer');
            } else {
                self::$global_bulk_image_resizer_check_editor =  '';
            }
            return self::$global_bulk_image_resizer_check_editor;
        }
        $img = wp_get_image_editor($path_check_img_url);
        if (is_wp_error($img)) {
            self::$global_bulk_image_resizer_check_editor =  $img->get_error_message();
            if ($img->get_error_code() == 'image_no_editor') {
                if ( (! extension_loaded( 'gd' ) || ! function_exists( 'gd_info' )) && ( ! extension_loaded( 'imagick' ) || ! class_exists( 'Imagick', false ) || ! class_exists( 'ImagickPixel', false )) ) {
                    self::$global_bulk_image_resizer_check_editor =  __('There seems to be no php library for manipulating images.', 'bulk-image-resizer');
                } 
            }
            return self::$global_bulk_image_resizer_check_editor;
        } else {
            self::$global_bulk_image_resizer_check_editor = '';
            return '';
        }
    }

    static function op_get_image_info($path_img) {
        global $bir_options;
		if ($bir_options == null) $bir_options = new Bir_options_var(); 
		$result  = array('is_valid'=> false, 'width'=>0, 'height'=>0, 'file_size'=>0, 'class_resize'=>'gp_color_ok', 'class_size'=>'gp_color_ok','show_btn'=>false, 'is_writable'=> true, 'max_quality'=>0);
		if (is_file($path_img) && file_is_valid_image($path_img)) {	
			$width      = $bir_options->max_width;
			$height     = $bir_options->max_height;
			$quality    = $bir_options->quality;
			$img = wp_get_image_editor($path_img);
			if (is_wp_error($img)) {
				return $result;
			}
			$result['is_valid'] = true;
			$img2 = $img->get_size();	
			$result['width'] = $img2['width']; 
			$result['height'] = $img2['height']; 
			$result['file_size'] = filesize($path_img);
			if (($width < $img2['width'] || $height < $img2['height']) && stripos($path_img,'.jpg') !== false) {
				$result['show_btn'] = true;
				$result['class_resize'] = "gp_color_warning";
			} 
		 
			if (!wp_is_writable($path_img)) {
				$result['show_btn']= false;
				$result['is_writable'] = false;
			}
			if ( stripos($path_img,'.jpg') !== false) {
				$result['max_quality'] = ($width * $height) * ($quality / 150); // quanto dovrebbe essere al massimo l'immagine
			} 
			/** 
			 * @since 1.2.5
			 * I don't compress animated gif
			 */
			if ( stripos($path_img,'.gif') !== false) {
				if (self::op_gif_animated($path_img) ) {
					$result['show_btn']= false;
					$result['is_valid'] = false;
					$result['msg'] = "Animated gif images are not resizable";
				}
			}
		}
		return $result;
	}

    static function op_gif_animated($path_img) {
        if(!($fh = @fopen($path_img, 'rb')))
        return 0;
        $count = 0;
        while(!feof($fh) && $count < 2) {
            $chunk = fread($fh, 1024 * 100); //read 100kb at a time
            $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches);
        }
        return ($count > 1);
    }

    /**
     * 
     */
    static function update_post_guid_and_mime($attachment_id) {
        global $wpdb;
        wp_cache_delete($attachment_id);
        // Aggiorno guid e mime del post
        $meta = wp_get_attachment_metadata($attachment_id, true);
        $upload_dir = wp_upload_dir();
        $postname = wp_basename($meta['file']);
        $ext = pathinfo($postname, PATHINFO_EXTENSION);
        $postname = str_replace('.'.$ext, '', $postname);
        $postname = sanitize_title($postname);
        $guid = $upload_dir['baseurl'] .'/'.  $meta['file'];
        // trovo il mime type dal file
        $mime_type = wp_check_filetype($guid);
        $mime_type = $mime_type['type'];


         // aggiorno guid e mime type
        $wpdb->update($wpdb->posts, ['guid' =>  $guid, 'post_name' => $postname, 'post_mime_type' => $mime_type], ['ID' => $attachment_id]);
    }

    /**
     * @param string $name il nome del file 
     */
    static function update_nice_title($attachment_id) {
        global $bir_options;
        if ($bir_options == null) $bir_options = new Bir_options_var();
        $attachment_id = absint($attachment_id);
        if ($attachment_id == 0) return '';
        $name = get_post_meta( $attachment_id, '_bir_attachment_originalname', true );
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $name = str_replace('.'.$ext, '', wp_basename($name));
        $name = ucfirst($name);
        $name = str_replace(['-','_'], ' ', $name);
        $name = str_replace('"', "'", $name);
        $name = str_replace('`', "'", $name);
        $name = strip_tags($name);
        $name = wp_trim_words($name, 15, '');
        if ($bir_options->rename_change_title == 1 && $name != '') {
            remove_action( 'post_updated', 'wp_save_post_revision' );
            remove_action('pre_post_update', 'wp_save_post_revision');// stop revisions
            wp_update_post(['ID'=>$attachment_id, 'post_title'=>$name]);
            add_action('pre_post_update', 'wp_save_post_revision');//  enable revisions 	
            add_action( 'post_updated', 'wp_save_post_revision' );
        }
        return $name;
    }
}