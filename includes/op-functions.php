<?php 
namespace opBulkImageResizer\Includes\OpFunctions;

/**
 * Tutte le funzioni che servono per gestire il plugin
 * 
 * @since      0.9.0
 *
 * @package    bulk-image-resizer
 * @subpackage bulk-image-resizer/includes
 */

if (!defined('WPINC')) die;

/**
 * @var String $global_bulk_image_resizer_check_editor; for check_image_editor cache empty string for true
 * @since 1.2.6
 */
$global_bulk_image_resizer_check_editor; 

/**
 * Genera il select per le dimensioni preset
 * @param string $val 1280x720|1920x1080|2560x1440|2100x2100| custom values widthxheight
 * @return string Html
 */
function html_select_dimension($val) {
    $dim = array('1280x720'=>'HD', '1920x1080'=>'FULL HD', '2560x1440'=>'QUAD HD', '2100x2100'=>'STAMPA 13x18cm', ''=>'CUSTOM');
    ?>
    <select id="selectPresetDimension" name="op-preset-dim" class="js-running-input-disable">
    <?php
        $find_selected = false;
        foreach ($dim as $key=>$label) {
            $label =  $label. (($key != "") ? " (".$key."px)" : ""); 
            if (($key == $val) || (!$find_selected && $key == "")) {
                $find_selected = true;
                $result_sel = $label;
                $selected =  ' selected="selected"'; 
            } else {
                $selected = "";
            }
            ?><option value="<?php echo esc_attr($key); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_html($label); ?></option><?php
        }
    ?>
    </select>
    <?php
}

/**
 * Genera il select per la qualità delle immagini
 * @param string $val 60|75|88
 * @return string Html
 */
function html_select_quality($val = 75) {
    $dim = array('60'=>'LOW', '75'=>'MEDIUM', '88'=>'HIGHT');
    ?>
    <select name="op_resize[quality]" id="settingQuality" class="js-running-input-disable">
    <?php
        foreach ($dim as $key=>$label) {
            $selected = ($key == $val) ? ' selected="selected"' : ""; 
            ?><option value="<?php echo esc_attr($key); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_html($label); ?></option><?php
        }
    ?>
    </select>
    <?php
}

/**
 * Calcolo il totale delle immagini salvate sul database
 * @return int|false
 */
function get_total_img() {
    global $wpdb;
    return $wpdb->get_var("SELECT count(ID) as tot FROM `" . $wpdb->prefix . "posts` WHERE `post_mime_type` LIKE (\"image%\")  AND post_type = \"attachment\" ");
}



/**
 * Ottimizza e fa il resize di una singola immagine
 * @param int $attachment_id
 * @return Wp_Error|Array ['path'=>string, 'file'=>string, 'width'=>int, 'height'=>int, 'mime-type'=>string]
 */
function op_optimize_single_img($attachment_id)
{
    $json_option = op_get_resize_options();
    $width      = $json_option['max_width'];
    $height     = $json_option['max_height'];
    $quality    = $json_option['quality'];
    $path_attached = get_attached_file($attachment_id);
    if (is_file($path_attached) && file_is_valid_image($path_attached)) {
        $resize = true;
        /**
         * Resize image while bulk. 
         * Check whether to resize the image. You can choose custom width and height.
         *
         * @since 0.9.5
         *
         * @param string $filename
         * @param int $attachment_id
         * @return boolean|array [width,height]
         */
        $ris_filter = apply_filters( 'op_bir_resize_image_bulk', wp_basename($path_attached), $attachment_id);
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
        /**
         * @since 1.2.5 
         * I don't compress animated gif
         */
        if ( stripos($path_attached,'.gif') !== false) {
            $resize = !op_gif_animated($path_attached);
        }


        if (!$resize) return false;

        // se esiste l'original
        $new_file_name = "";
        $old_file_size = 0;
        $path_original =  wp_get_original_image_path($attachment_id);
        if ($path_original != "" && $path_original != $path_attached) {
           
            $meta_original_image = $path_original;
            $img = wp_get_image_editor($path_original);
            $old_file_path = $path_original;
            if (is_wp_error($img)) {
                $img = wp_get_image_editor($path_attached);
                $old_file_path = $path_attached;
                $new_file_name = $path_attached;
                $meta_original_image = $path_attached;
            } else {
                if ($json_option['delete_original'] == 1) {
                    $new_file_name = $path_original;
                    unlink($path_attached);
                } else {

                    $new_file_name = $path_attached;
                  
                }
            }
        } else {
            // NON ESISTE L'ORIGINAL oppure hanno lo stesso nome
            $meta_original_image = $path_attached;
            if ($json_option['delete_original'] == 1) {
                $new_file_name = $path_attached;
            } else {
                // non devo sovrascrivere l'originale
                 $new_file_name = "";
            }
            $img = wp_get_image_editor($path_attached);
            $old_file_path = $path_attached;
            if ($path_original == "") {
               $meta_original_image = $path_attached;
            }
        }
        if (!is_wp_error($img)) {
            
            if ($width > 99 && $height > 99) {
                $img->resize($width, $height); 
            }
            $img->set_quality($quality);
            if ($json_option['delete_original'] == 0 && $new_file_name == "") {
                $suffix = apply_filters( 'op_bir_resize_image_bulk_suffix', 'compress');
                $new_file_name = $img->generate_filename( $suffix );
                $suffix_k = 0;
                while (file_exists( $new_file_name ) && $suffix_k < 999 ) {
                    $new_file_name = $img->generate_filename( $suffix."-".$suffix_k);
                    $suffix_k++;
                }
                
            }
            $old_file = file_get_contents($old_file_path);
            $save = $img->save($new_file_name);
            // SE LA COMPRESSIONE NON HA MIGLIORATO LA SITUAZIONE!  
            
            $meta = wp_get_attachment_metadata($attachment_id, true);
            $meta['original_image'] = wp_basename($meta_original_image);
            clearstatcache();
            if ( filesize($old_file_path) < filesize($new_file_name)) {
               
                
                // TORNO ALLA CONDIZIONE DI PARTENZA!
                if ($new_file_name != "" && $path_original != $new_file_name ) {
                    unlink($new_file_name);
                    $new_file_name = $path_original;
                }
                file_put_contents($new_file_name, $old_file);
                // TODO AGGIUNGO UN METADATO DI INFO!s
                update_post_meta( $attachment_id, '_bulk_image_resizer_non_optimized', '1');
                
            } else if (!is_wp_error($save)) {   
                update_attached_file($attachment_id, $new_file_name);
                $meta['width'] = $save['width'];
                $meta['height'] = $save['height'];

            }
             wp_update_attachment_metadata($attachment_id, $meta);
            // 
            return $save;
        } else {
            return $img;
        }
    } else {
        return new \WP_Error('invalid_image');
    }
}



/**
 * Ricarica l'immagine originale
 * @param int $attachment_id
 * @since 1.3.0
 * @return Boolean
 */
function op_optimize_revert_original_img($attachment_id)
{
    $path_attached = get_attached_file($attachment_id);
    if (is_file($path_attached) && file_is_valid_image($path_attached)) {

        $path_original =  wp_get_original_image_path($attachment_id);
        if (is_file($path_original) && file_is_valid_image($path_original) && $path_original != $path_attached) {
          
            unlink($path_attached);
            $meta = wp_get_attachment_metadata($attachment_id, true);
            if (isset($meta['original_image'])) {
                unset($meta['original_image']);
            }
            clearstatcache();
            $img = wp_get_image_editor($path_original);
            $img2 = $img->get_size();	
            $result['width'] = $img2['width']; 
            $result['height'] = $img2['height']; 
            update_attached_file($attachment_id, $path_original);
            $meta['width'] = $img2['width']; 
            $meta['height'] =  $img2['height']; 
 
             wp_update_attachment_metadata($attachment_id, $meta);
            // 
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/**
 * Ogni volta che vengono ricaricate le statistiche viene salvato un nuovo punto. Questa funzione cancella quelli vecchi.
 * @param Array $jstat ['timestamp'=>bytes, ] è il jstat['data_bar']
 * @return Array
 */
function op_clean_space_chart($jstat)
{
    $old    =  new \DateTime();
    $old->modify("-1 day");

    $tooold = new \DateTime();
    $tooold->modify("-30 days");
 
    $jstatnew = array();
    // inverto l'array perché non memorizzo tutti i valori e preferisco avere gli ultimi e non i primi.
 
    $jstat = array_reverse($jstat, true);
    $adding_dates = array();

 
    foreach ($jstat as $jk => $v) {
        $key_date = (new \DateTime())->setTimestamp($jk);
        if ($key_date == false || $jk == 0) continue;
        $string_date = $key_date->format('YmdHis');
        if ($key_date < $tooold) {
            // se sono passati più di 30 giorni tengo l'ultimo risultato del mese purché non differente dal mese successivo (perché ho invertito l'array)
            if (!isset($adding_dates[substr($string_date, 0, 6)]) && $v != end($jstatnew)) {
                $jstatnew[$jk] = $v;
                $adding_dates[substr($string_date, 0, 6) ] = $v;
            }
        } else if ($key_date <= $old) {
            // se è passato più di un giorno, ma meno di 30 ne memorizzo uno al giorno
            if (!isset($adding_dates[substr($string_date, 0, 8)])) {
                $jstatnew[$jk] = $v;
                $adding_dates[substr($string_date, 0, 8) ] = $v;
            }
        } else {
            // Oggi e ieri li memorizzo tutti purché diversi dall'ultimo valore registrato
            if ($v != end($jstatnew)) {
                $jstatnew[$jk] = $v;
            }
        }
    }
    return array_reverse($jstatnew, true);
}

/**
 * Converte le statistiche dello spazio nei dati per il grafico
 * @param array $data_size [timestamp:bytes]
 * @return array ['labels'=>[],'datasets'=>[0=>['data'=>[]]]]
 */
function op_convert_space_to_graph($data_size) {
    $dataset_size = ['labels'=>[],'datasets'=>[0=>['data'=>[]]]];
	// "data_size":["timestamp":bytes]
	foreach ($data_size as $timestamp =>$ds) {
		$dataset_size['labels'][] = date_i18n( 'd-M H:i', $timestamp );
		$dataset_size['datasets'][0]['data'][] = round($ds /(1024*1024),2);
	}
	return $dataset_size;
}

/**
 * Prepare the statistics and return the main data
 * @return Array [$tot_imgages, $images_file_size, $datasets] 
 * $datasets =  ['label' => [], 'data' => [],  'backgroundColor' => '']
 */
function prepare_images_stat() {
    global $wpdb;
    $images_file_size = 0;
    $tot_img = $wpdb->get_results("SELECT ID, post_mime_type, post_title FROM `" . $wpdb->prefix . "posts` WHERE `post_mime_type` LIKE (\"image%\")  AND post_type = \"attachment\" ");
    $temp_attacment_meta = $wpdb->get_results("SELECT *  FROM `" . $wpdb->prefix . "postmeta` WHERE `meta_key` = \"_wp_attachment_metadata\"");
   /*   foreach ($temp_attacment_meta as $am) {
    var_dump (maybe_unserialize($am->meta_value));
      }
    die;
    */
    $upload_dir = wp_upload_dir();
    $attacment_meta = array();
    foreach ($temp_attacment_meta as $am) {
        $temp = maybe_unserialize($am->meta_value);
        if (is_array($temp) && isset($temp['width']) && isset($temp['height']) && isset($temp['file']) && is_file($upload_dir['basedir'] . "/" . $temp['file'])) {
            $filesize = filesize($upload_dir['basedir'] . "/" .$temp['file']);
            if (isset ($temp['original_image'])) {
                $ori_path = $upload_dir['basedir'] . "/" .dirname($temp['file'])."/". $temp['original_image'];
                // print "<p>". $ori_path . "</p>";
                $filesize += filesize($ori_path);
            }
            $attacment_meta[$am->post_id] = ['width' => $temp['width'], 'height' => $temp['height'], 'file' => $temp['file'], 'filesize' =>$filesize];
        }
    }

    unset($temp_attacment_meta);
    $array_unique = array();
    $chart_scatter = [];
    $gap = floor(count($tot_img) / 100);
    if ($gap == 0) $gap = 1;
    foreach ($tot_img as $img) {
        $post_mime_type = str_replace("image/", "", $img->post_mime_type);
        if (!isset($array_unique[$post_mime_type])) {
            $array_unique[$post_mime_type] = 0;
        }
        $array_unique[$post_mime_type]++;
        if (isset($attacment_meta[$img->ID]) && $attacment_meta[$img->ID]['width'] > 0 && $attacment_meta[$img->ID]['height'] > 0) {
            $att = $attacment_meta[$img->ID];
            $images_file_size += $att['filesize'];

            if (array_key_exists($post_mime_type, $chart_scatter) && !is_array($chart_scatter[$post_mime_type])) {
                $chart_scatter[$post_mime_type] = [];
            }
            $xkey = (floor($att['width'] / $gap) * $gap) . "x" . (floor($att['height'] / $gap) * $gap);
            if (count($tot_img) < 100) {
                $chart_scatter[$post_mime_type][] = ['x' => $att['height'], 'y' => $att['width'], 'img' => ($img->post_title), 'tot' => 1, 'r' => 2];
            } elseif (!isset($chart_scatter[$post_mime_type][$xkey])) {
                $chart_scatter[$post_mime_type][$xkey] = ['x' => $att['height'], 'y' => $att['width'], 'img' => ($img->post_title), 'tot' => 1, 'r' => 2];
            } else {
                $tot = $chart_scatter[$post_mime_type][$xkey]['tot'] + 1;
                $img = $tot . " images";
                $r = 3 +  floor($tot / 10);
                if ($r > 10) {
                    $r = 10;
                }
                $width = round((($chart_scatter[$post_mime_type][$xkey]['width'] * ($tot - 1)) + $att['width']) / $tot);
                $height = round((($chart_scatter[$post_mime_type][$xkey]['height'] * ($tot - 1)) + $att['height']) / $tot);
                $chart_scatter[$post_mime_type][$xkey] = ['x' => $height, 'y' => $width, 'img' => $img, 'tot' => $tot, 'gap' => $gap, 'r' => $r];
            }
        }
    }
    foreach ($chart_scatter as $key => $value) {
        $chart_scatter[$key] = array_values($value);
    }
        
    $color_array = array('jpeg' => '#36a2eb', 'jpg' => '#36a2eb', 'tiff' => '#ff6384', 'png' => '#ff6384', 'gif' => '#ff6384', 'bmp' => '#ff6384', 'webp' => '#2e9460');

    $datasets = [];
    foreach ($chart_scatter as $key => $value) {

        if (!isset($color_array[$key])) {
            $color = '#' . dechex(rand(0, 8) + 5) . '0' . dechex(rand(0, 8) + 5) . '0' . dechex(rand(0, 8) + 5) . '0';
        } else {
            $color = $color_array[$key];
        }
        $datasets[] = ['label' => $key, 'data' => $value,  'backgroundColor' => $color];
    }

    return [count($tot_img), $images_file_size, $datasets];
}

/**
 * Returns the array of options or defaults if not set
 * @param String $key Optional If present, it extracts a variable instead of the array
 * @param String $default If the variable does not exist, it returns a default value instead of false
 * @return String|Array [width,height,quality, delete_original, on_upload] It can return other values if hooks are present (??)
 */
function op_get_resize_options($key = "", $default = false) {
    $json = get_option('bulk_image_resizer', '[]');
    $json  = json_decode($json, true);
    if (json_last_error() != JSON_ERROR_NONE) {
         $json  = array();
    }
    if (!array_key_exists('max_width', $json)) {
        $json['max_width'] = 1920; 
    } else {
        $json['max_width'] = absint($json['max_width']);
    }
    if (!array_key_exists('max_height', $json)) {
       $json['max_height'] = 1080; 
    } else {
        $json['max_height'] = absint($json['max_height']);
    }
    if (!array_key_exists('height', $json)) {
        $json['quality'] = 75; 
    } else {
        $json['quality'] = absint($json['quality']);
    }
    if (!array_key_exists('delete_original', $json)) {
        $json['delete_original'] = 0; 
    } 
    if (!array_key_exists('on_upload', $json)) {
        $json['on_upload'] = 1; 
    } 

   
    if ($key != "") {
        if (array_key_exists($key, $json)) {
            return $json[$key];
        } else {
            return $default;
        }
    } else {
        return $json;
    }
}


/**
 * Check if the internal wordpress editor can be used or not
 * @since      1.2.6
 * @return String Error  (empty string = true)
 */
function check_image_editor() {
    global $global_bulk_image_resizer_check_editor;
    if (is_bool($global_bulk_image_resizer_check_editor)) {
        return $global_bulk_image_resizer_check_editor;
    }
    $path_check_img_dir = BULK_IMAGE_RESIZER_DIR.'1px.jpg';
    $path_check_img_url = plugins_url('bulk-image-resizer/1px.jpg' );
    if (!file_exists($path_check_img_dir)) {
        if ( (! extension_loaded( 'gd' ) || ! function_exists( 'gd_info' )) && ( ! extension_loaded( 'imagick' ) || ! class_exists( 'Imagick', false ) || ! class_exists( 'ImagickPixel', false )) ) {
            $global_bulk_image_resizer_check_editor =  __('There seems to be no php library for manipulating images.', 'bulk-image-resizer');
        } else {
            $global_bulk_image_resizer_check_editor =  '';
        }
        return $global_bulk_image_resizer_check_editor;
    }
    $img = wp_get_image_editor($path_check_img_url);
    if (is_wp_error($img)) {
        $global_bulk_image_resizer_check_editor =  $img->get_error_message();
        if ($img->get_error_code() == 'image_no_editor') {
            if ( (! extension_loaded( 'gd' ) || ! function_exists( 'gd_info' )) && ( ! extension_loaded( 'imagick' ) || ! class_exists( 'Imagick', false ) || ! class_exists( 'ImagickPixel', false )) ) {
                $global_bulk_image_resizer_check_editor =  __('There seems to be no php library for manipulating images.', 'bulk-image-resizer');
            } 
        }
        return $global_bulk_image_resizer_check_editor;
    } else {
        $global_bulk_image_resizer_check_editor = '';
        return '';
    }
}

/**
 * Back all the information that can help me with an image
 * @param String $path_img
 * @return Array  {"is_valid":false, "width":0, "height":0, "file_size":0, "class_resize":"gp_color_ok", "class_size":"gp_color_ok","show_btn":false, "is_writable": true}
 */
function op_get_image_info($path_img) {
    $result  = array('is_valid'=> false, 'width'=>0, 'height'=>0, 'file_size'=>0, 'class_resize'=>'gp_color_ok', 'class_size'=>'gp_color_ok','show_btn'=>false, 'is_writable'=> true, 'max_quality'=>0);
    if (is_file($path_img) && file_is_valid_image($path_img)) {
      
        $json_option = op_get_resize_options();
        $width      = $json_option['max_width'];
        $height     = $json_option['max_height'];
        $quality    = $json_option['quality'];
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
            if (op_gif_animated($path_img) ) {
                $result['show_btn']= false;
                $result['is_valid'] = false;
                $result['msg'] = "Animated gif images are not resizable";
            }
        }
    }
    return $result;
}

/**
 * Calculate the number of frames of a gif and return true if it is an animation
 * @since 1.2.5 
 * @param String $path_img The absolute path of the image
 * @return Boolean
 */
function op_gif_animated($path_img) {
    if(!($fh = @fopen($path_img, 'rb')))
    return 0;
    $count = 0;
    while(!feof($fh) && $count < 2) {
        $chunk = fread($fh, 1024 * 100); //read 100kb at a time
        $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches);
    }
    return ($count > 1);
}