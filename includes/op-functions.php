<?php 
namespace opBulkImageResizer\Includes\OpFunctions;
/**
 * Tutte le funzioni che servono per gestire il plugin
 * 
 * @since      0.9.0
 *
 * @package    op-bulk-image-resizer
 * @subpackage op-bulk-image-resizer/includes
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
function html_select_quality($val) {
    $dim = array('60'=>'LOW', '75'=>'MEDIUM', '88'=>'HIGHT');
    ?>
    <select name="op_resize_quality" id="settingQuality" class="js-running-input-disable">
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
    list($width, $height, $quality) = op_get_resize_options();
   
   // TODO: qualche volta  wp_get_original_image_path($attachment_id); 
   // get_attached_file invece ritorna l'immagine lavorata!
   // IN una versione futura si puà far scegliere se ridimensionare l'originale o una copia
    $path_img = get_attached_file($attachment_id);
    if (file_is_valid_image($path_img)) {
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
        $ris_filter = apply_filters( 'op_bir_resize_image_bulk', wp_basename($path_img), $attachment_id);
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
        $img = wp_get_image_editor($path_img);

        if (!is_wp_error($img)) {
            if ($width > 100 && $height > 100) {
                $img->resize($width, $height); 
            }
            $img->set_quality($quality);
            $save = $img->save($path_img);
            $meta = wp_get_attachment_metadata($attachment_id, true);
            $meta['width'] = $save['width'];
            $meta['height'] = $save['height'];
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
    $upload_dir = wp_upload_dir();
    $attacment_meta = array();
    foreach ($temp_attacment_meta as $am) {
        $temp = maybe_unserialize($am->meta_value);
        if (isset($temp['width']) && isset($temp['height']) && isset($temp['file']) && is_file($upload_dir['basedir'] . "/" . $temp['file'])) {
            $attacment_meta[$am->post_id] = ['width' => $temp['width'], 'height' => $temp['height'], 'file' => $temp['file'], 'filesize' => filesize($upload_dir['basedir'] . "/" . $temp['file'])];
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
 * Ritorna le opzioni o i default
 * list($width,$height,$quality) = gp_get_resize_options();
 * @return Array [width,height,quality]
 */
function op_get_resize_options() {
    $width = (int)get_option('op_resize_max_width', '1920');
    $height = (int)get_option('op_resize_max_height', '1080');
    $quality = (int)get_option('op_resize_quality', '75');
    return array($width, $height, $quality);
}