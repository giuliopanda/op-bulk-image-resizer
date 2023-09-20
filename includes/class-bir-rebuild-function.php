<?php
/**
 * Le funzioni sulle thumbnails
 * @since      2.0.0
 */
namespace bulk_image_resizer;

class Bir_rebuild_functions extends Bir_extends_functions {
    /**
     * Rebuild thumbnails
     * @param int $image_id
     */
    public static function thumbnails($image_id, $debug = false) {
        
        // cancello le thumbs attuali
        clean_attachment_cache($image_id);         
        $image = wp_get_attachment_metadata($image_id);
        if (!$image) {
            self::$last_error = 'L\'immagine con ID '.$image.' specificata non esiste.';
            return false;
		}

        // Rinomina l'immagine originale es: 2023/05/753_parigi_thb.jpg
        $image_dir = dirname($image['file']);
        if (substr($image_dir, -1) != '/') $image_dir .= '/';
        // aggiungo a image_dir il percorso della cartella dell'upload
        $image_dir = wp_upload_dir()['basedir'].'/'.$image_dir;
       
        // Elimina le miniature corrispondenti
        self::remove_thumbs($image_id, $debug);

        // rigenera le thumbs dell'immagine
        $registered_sizes = wp_get_registered_image_subsizes();
        foreach ($registered_sizes as $rg) {
            self::$debug_msgs[basename(__FILE__).':'.__LINE__.'@'.count(self::$debug_msgs)] = "Thumbs Regenerated: " . $rg['width']."x".$rg['height'] . "\n";
        }
        //
        if (!$debug) {       
            wp_generate_attachment_metadata($image_id, get_attached_file($image_id, true));
        }
        return pathinfo($image['file'], PATHINFO_FILENAME);
    }

    /**
     * Rimuove tutte le miniature di un'immagine
     * @param $image int l'id dell'immagine 
     */
    public static function remove_thumbs($image_id, $debug = false) {
    
        $image = wp_get_attachment_metadata($image_id);
        $image_dir = wp_upload_dir()['basedir'].'/'.dirname($image['file']);
        if (substr($image_dir, -1) != '/') $image_dir .= '/';
        $image_name = pathinfo($image['file'], PATHINFO_FILENAME);
        $pattern = '/^' . preg_quote($image_name , '/') . '-\d+x\d+\.\w+$/';
        if (!is_dir($image_dir)) {
            self::$last_error = 'I can\'t remove images. '.$image_dir.' specified doesn\'t exist.';
            return false;
        }
        if ($handle = opendir($image_dir)) {
            while (false !== ($file = readdir($handle))) {
                // Ignora le cartelle
                if ($file != "." && $file != "..") {
                    // Verifica se il file corrisponde all'espressione regolare
                    if (preg_match($pattern, $file)) {
                        // Elimina la miniatura
                        if (!$debug) unlink($image_dir . $file);
                        self::$debug_msgs[basename(__FILE__).':'.__LINE__.'@'.count(self::$debug_msgs)] = "Thumbs Removed: " . $file . "\n";
                    }
                }
            }
            closedir($handle);
        }
        return true;
    }

}