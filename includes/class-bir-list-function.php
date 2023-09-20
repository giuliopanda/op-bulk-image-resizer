<?php
/**
 * Gestisce le liste per il caricamento delle immagini
 * Gestisco uno stato per definire se c'è stato un avvio di un processo di resize in bulk oppure no.
 * Se c'è un processo di resize in bulk in corso, e faccio la richiesa di resize carica il gruppo successivo
 * Se non c'è un processo di resize in bulk in corso, e faccio la richiesa di resize carica il primo gruppo
 * Ci deve essere una richiesta per definire se c'è un resize in corso, e una funzione per il riavvio
 */
namespace bulk_image_resizer;

 class BIR_list_functions extends Bir_extends_functions {

   static $limit = 200;
   static $limit_start = 0;
   static $total_rows = 0;

   /**
    * Ritorna l'elenco delle immagini da elaborare
   * Ritorna un array di oggetti con ID e guid
   * @return array [ID] 
    */
   public static function get_results( $limit_start, $limit = -1) {
      global $wpdb;

      $start = microtime(true);
      if ($limit == -1) {
         $limit = 200;
      }
      self::$limit = $limit;
      self::$limit_start = $limit_start;
      $post_ids = $wpdb->get_results("SELECT ID FROM `" . $wpdb->prefix . "posts` WHERE `post_mime_type` LIKE (\"image%\") AND post_type = \"attachment\" ORDER by ID ASC LIMIT " . absint($limit_start) . ", ". absint($limit));
      $post_ids = array_map(function($item) {
         return $item->ID;
      }, $post_ids);
      return $post_ids;
   }

   /**
    * Restituisce il numero di immagini già elaborate
    */
   public static function get_done() {
      $done = get_option('op_resize_images_done',  0);
      if (is_numeric($done)) {
         return absint($done);
      } else {
         $done = json_decode($done, true);
         if (is_array($done) && isset($done['done'])) {
            return absint($done['done']);
         }
      }
   }



   /**
    * Setto il numero di immagini già elaborate
    * @param $action string 'resize' | 'restore' | delete_original | ''
    */
   public static function set_done($done = 0, $action = 'resize') {
      update_option('op_resize_images_done',  json_encode(['done'=>$done,'action'=>$action]), false);
   }

   /**
    * Restituisce lo stato del processo di resize
    * @return array ['done':0, 'total':0, 'percent' : 0.00, 'status' => 'NOT_STARTED|RUNNING|FINISHED', 'action' => 'resize|restore|'']
    */
   public static function status($cache = true) {
      $done_status = get_option('op_resize_images_done');
      $done_json = json_decode($done_status, true);
      if (is_array($done_json) && isset($done_json['done'])) {
         $done = $done_json['done'];
         $action = $done_json['action'];
      } else {
         $done = 0;
         $action = '';
      }
      $total = self::total($cache);
      $status_string = "FINISHED";
      if ($done < $total) {
         $status_string = "RUNNING";
      }
      if ($done == 0) {
         $status_string = "NOT_STARTED";
      }
      $status = array(
         'done' => $done,
         'total' => $total,
         'percent' => ($total != 0) ? round(($done / $total) * 100, 2) : 0,
         'status' => $status_string,
         'action' => $action
      );
      if ($status['percent'] >= 100 || $status_string == "FINISHED") {
         $status['percent'] = 100;
         self::reset_status();
      }
      return $status;
   }

   /**
    * Resetta lo status 
    */
   public static function reset_status() {
      self::set_done(0, '');
      self::$total_rows = 0;
   }

   /**
    * Restituisce il numero totale di immagini
    */
   public static function total($cache = true) {
      global $wpdb;
      if (self::$total_rows > 0 && $cache) {
         return self::$total_rows;
      }
      self::$total_rows = absint($wpdb->get_var("SELECT count(ID) FROM `" . $wpdb->prefix . "posts` WHERE `post_mime_type` LIKE (\"image%\")  AND post_type = \"attachment\" "));
      return self::$total_rows;
   }


   /**
    * Prepare the statistics and return the main data
    * @return Array [$tot_imgages, $images_file_size, $datasets] 
    * $datasets =  ['label' => [], 'data' => [],  'backgroundColor' => '']
    */
   public static function prepare_images_stat() {
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
                  if (isset($chart_scatter[$post_mime_type][$xkey]['width']) && isset($chart_scatter[$post_mime_type][$xkey]['height'])) {
                     $width = round((($chart_scatter[$post_mime_type][$xkey]['width'] * ($tot - 1)) + $att['width']) / $tot);
                     $height = round((($chart_scatter[$post_mime_type][$xkey]['height'] * ($tot - 1)) + $att['height']) / $tot);
                  } else {
                     $width = round((( $att['width'] * ($tot - 1)) + $att['width']) / $tot);
                     $height = round((( $att['height'] * ($tot - 1)) + $att['height']) / $tot);
                  }
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

}