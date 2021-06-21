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
   // TODO: qualche volta  wp_get_original_image_path($attachment_id); 
   // get_attached_file invece ritorna l'immagine lavorata!
   // IN una versione futura si puà far scegliere se ridimensionare l'originale o una copia
    $path_attached = get_attached_file($attachment_id);
    if (file_is_valid_image($path_attached)) {
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
        if (!$resize) return $upload;

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
            // TSE LA COMPRESSIONE NON HA MIGLIORATO LA SITUAZIONE!  
            
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
 * NON LO USO!
 * C'è differenza tra come vengono salvati i dati e come vengono rappresentati.
 * Questa funzione converte i dati ricavati con quelli da visualizzare
 * @param Array $data I dati che si ricevono. $data[$key] è  [label:value, label:value] 
 * in data_bar label:timestamp value:bytes
 * in data_pie label:tipo file  value:numero_file
 * @param String $key Serve a definire la gestione dei colori data_pie|data_bar
 * @return Array  esempio ['labels'=>['a','b'], 'datasets'=> [['data'=> [10,20],'backgroundColor'=> ['rgb(255,99,132)','rgb(54,162,235)']]]];
 */
function convert_data_option_to_graph($data, $key) {
    $label_bar = array();
    $data_values = array();
    if ($key == "data_pie") {
        $label_bar = array_keys($data[$key]);
        $data_values = array_values($data[$key]);
        $background_color = ['rgb(54, 162, 235)', 'rgb(255, 99, 132)', 'rgb(255, 205, 86)', 'rgb(43, 177, 164)', 'rgb(125, 54, 204)', 'rgb(142, 31, 31)', 'rgb(77, 77, 77)'];
    } else {

        // bar spazio
        $background_color = array();
        $array_jstat = array();
        $time_bar = array();
        $media = 1;
        $count_media = 0;
        // dkey è un timestamp
        // dvalue sono i bytes occupati
        // BUG: faccio substr di un TIMESTAMP!
        foreach ($data[$key] as $dkey => $dvalue) {
            $time_key = (new \DateTime())->setTimestamp($dkey)->format('m-d');
            if (!isset($time_bar[$time_key])) {
                $time_bar[$time_key] = 0;
            }
            $time_bar[$time_key]++;
            $media += $dvalue;
            $count_media++;
        }
        $media = ceil($media / $count_media);
        $data[$key] = array_reverse($data[$key], true);
        $temp_count_foreach = 0;
        foreach ($data[$key] as $dkey => $dvalue) {
            if ($dvalue != end($data_values)) {
                $temp_count_foreach++;
                if ($temp_count_foreach > 10) break;
                $time_key = (new \DateTime())->setTimestamp($dkey);
                $time = $time_key->format('m-d');
                if (array_key_exists($time, $time_bar) && $time_bar[$time] > 1) {
                    $label_bar[] = $time_key->format('m-d H:i');
                } else {
                    $label_bar[] = $time;
                }
                $data_values[] = $dvalue;
                if ($dvalue <= $media) {
                    $background_color[] = 'rgb(75, 192, 192)';
                } else if ($dvalue <= $media * 1.5) {
                    $background_color[] = 'rgb(255, 205, 86)';
                } else if ($dvalue <= $media * 2) {
                    $background_color[] = 'rgb(255, 159, 64)';
                } else {
                    $background_color[] = 'rgb(255, 99, 132)';
                }
            }
        }
        $data_values = array_reverse($data_values);
        $label_bar = array_reverse($label_bar);
        $background_color = array_reverse($background_color);
    }
    return ['labels' => $label_bar, 'datasets' => [['data' => $data_values, 'backgroundColor' => $background_color]]];
}

/**
 * Prepara le statistiche e ne ritorna i dati principali
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

            if (!is_array($chart_scatter[$post_mime_type])) {
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
 * Ritorna l'array delle opzioni o se non impostati i default
 * @param String $key Optional ritorna direttamente una variabile invece dell'array
 * @param String $default Se non esiste la variabile ritorna un default invece di false
 * @return String|Array [width,height,quality, delete_original, on_upload] Può ritornare altri valori se presenti hook
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
 * Torna tutte le info che possono servirmi di un'immagine
 * @param Number $path_img
 * @return Array  {"is_valid":false, "width":0, "height":0, "file_size":0, "class_resize":"gp_color_ok", "class_size":"gp_color_ok","show_btn":false, "is_writable": true}
 */
function op_get_image_info($path_img) {
    $result  = array('is_valid'=> false, 'width'=>0, 'height'=>0, 'file_size'=>0, 'class_resize'=>'gp_color_ok', 'class_size'=>'gp_color_ok','show_btn'=>false, 'is_writable'=> true, 'max_quality'=>0);
    if (file_is_valid_image($path_img)) {
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
        if ($width < $img2['width'] || $height < $img2['height']) {
            $result['show_btn'] = true;
            $result['class_resize'] = "gp_color_warning";
        } 
        if ($max_quality < $bytes && stripos($path_img,'.jpg') !== false) {
            $result['show_btn']= true;
            $result['class_size']  = "gp_color_warning";
        }
        if (!wp_is_writable($path_img)) {
            $result['show_btn']= false;
            $result['is_writable'] = false;
        }
        if ( stripos($path_img,'.jpg') !== false) {
            $result['max_quality'] = ($width * $height * .6) * ($quality / 150); // quanto dovrebbe essere al massimo l'immagine
        } 
    }
    return $result;
}