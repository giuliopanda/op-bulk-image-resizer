<?php
/**
 * Gestisco le funzioni per calcolare le statistiche
 * Ancora non usato!
 * 
 * @since      2.0.0
 */
namespace bulk_image_resizer;

if (!defined('WPINC')) die;

class Bir_statistic {
	public function __construct() {
    }

    /**
     * Calcola il numero di immagini e la dimensione totale per mese
     */
    public static function count_data_filesize() {
        global $wpdb;

        $sql = "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_bir_attachment_originalfilesize' LIMIT 50000";
        $attachment_original = $wpdb->get_results($sql);
        $total_file_size_original = 0;
        $total_files_original = 0;
        $original_ids = [];
        foreach ($attachment_original as $at) {
            $filesize = $at->meta_value;
            $total_file_size_original += absint($filesize);
            $total_files_original++;
            $original_ids[] = $at->post_id;
        }


        $sql = "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_wp_attachment_metadata' LIMIT 50000";
        //print $sql.PHP_EOL;
        $attachment = $wpdb->get_results($sql);
        $total_file_size = 0;
        $total_files = 0;
        foreach ($attachment as $at) {
            
            $meta = unserialize($at->meta_value);
            if (!isset($meta['filesize']) || !isset($meta['width'])  || !isset($meta['height'])) continue;
            $total_files++;
            if (in_array($at->post_id, $original_ids)) {
                $total_file_size += absint($meta['filesize']);
            }
        }
        //_wp_attachment_metadata [filesize]
        // _bir_attachment_originalfilesize
      

        // Ottieni la data corrente nel formato "anno-mese"
        $month = date('Ym');
        // Crea un array con i dati del mese corrente
        $month_data = array(
            'total_file_size' => $total_file_size,
            'total_files' => $total_files,
            'total_file_size_original' => $total_file_size_original,
            'total_files_original' => $total_files_original,
        );

        // Ottieni l'array di dati salvati per tutti i mesi
        $all_data = get_option('bir_monthly_stats', true);

        // Se l'array di dati non esiste, crealo
        if (!$all_data || !is_array($all_data)) {
            $all_data = array();
        }

        // Aggiungi i dati del mese corrente all'array di dati
        //print "alldata".PHP_EOL;
        //var_dump ($all_data);
        //die;
        $all_data[(string)$month] = $month_data;

        // Salva l'array di dati come metakey serializzata
        update_option('bir_monthly_stats', $all_data);
      
        return [
            'current' => $month_data,
            'all' => $all_data
        ];

    }
}

