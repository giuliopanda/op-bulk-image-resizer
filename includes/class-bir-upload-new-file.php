<?php
/**
 * Gestisco il filtri e hook per il caricamento dinamico delle immagini
 * 
 * @since      2.0.0
 */
namespace bulk_image_resizer;

if (!defined('WPINC')) die;

class Bir_loader_upload {
    /**
	 * Inizializzo tutti i loader per gli ajax
	 */
    static $original_image = null;
    static $original_image_name = null;
    static $uniq_id = null;
	public function __construct() {
        add_filter('wp_handle_upload_prefilter', [$this, 'handle_upload_prefilter']);
        add_filter('wp_generate_attachment_metadata', [$this, 'wp_generate_attachment_metadata'], 10, 3);
       //add_filter('wp_handle_upload', [$this, 'wp_handle_upload'], 10, 2);
    }

    function handle_upload_prefilter($file) {
        global $bir_options;
        if (substr($file['type'], 0, 5) != 'image') return $file;
		if ($bir_options == null) $bir_options = new Bir_options_var();
        self::$original_image = null;
        self::$original_image_name = null;
        self::$uniq_id = null;
        // $file => array(5) { ["name"]=> string(17) "584c3506d69d2.jpg" ["type"]=> string(10) "image/jpeg" ["tmp_name"]=> string(14) "/tmp/phpkNhFHO" ["error"]=> int(0) ["size"]=> int(107243) }

        // Specifica le dimensioni desiderate per l'immagine ridimensionata
        $resize = false;
        $quality = 100;
        if ($bir_options->resize_active == 1) {
            $max_width = $bir_options->max_width;
            $max_height = $bir_options->max_height;
            $quality = $bir_options->quality;
            $resize = true;
        } else if ($bir_options->optimize_active == 1 || $bir_options->webp_active == 1) {
            $max_width = 5000;
            $max_height = 5000;
            $quality = $bir_options->quality;
            $resize = true;
        }
        // Percorso completo del file temporaneo
        $file_path = $file['tmp_name'];
        
        /**
         * Resize image while upload. 
         * Check whether to resize the image. You can choose custom width and height.
         *
         * @since 2.0.0
         *
         * @param string $filename
         * @param int $attachment_id  0
         * @return boolean|array [width,height] boolean (false) se non vuoi ridimensionare l'immagine
         */
        $ris_filter = apply_filters( 'op_bir_resize_image_bulk', wp_basename($file_path), 0);
        if (is_array($ris_filter) && count($ris_filter) == 2) {
            if (array_key_exists('width', $ris_filter) && array_key_exists('height', $ris_filter)) {
                $max_width = $ris_filter['width'];
                $max_height = $ris_filter['height'];
            } else {
                $max_width = array_shift($ris_filter);
                $max_height = array_shift($ris_filter);
            }	
            if (array_key_exists('quality', $ris_filter) &&  $ris_filter['quality'] > 0 && $ris_filter['quality'] <= 100) {
                $quality = $ris_filter['quality'];
            }
        } elseif (is_bool($ris_filter)) {
            $resize = $ris_filter;
        }

       if (substr($file['name'], -4) == '.gif' || $file["type"] == 'image/gif') {
            $resize = false;
        }
        self::$original_image_name = $file['name'];
        if ($resize) {
            // original
            $ext2 = pathinfo($file['tmp_name'], PATHINFO_EXTENSION);
            $file_original = str_replace(".".$ext2, '', $file['tmp_name']);
            $file_original .= '-original.'.$ext2;
            copy($file['tmp_name'], $file_original);
            self::$original_image = $file_original;
           
            //print "ORIGINAL IMAGE: ".self::$original_image;

            $source_image = wp_get_image_editor($file_path);
            if (!is_wp_error($source_image)) {
                $source_image->resize($max_width, $max_height);
                if ($quality != 100) $source_image->set_quality($quality);
                $mime_type = $file["type"];
                if ($bir_options->webp_active == 1) {
                    $mime_type = 'image/webp';
                    $file["type"] = $mime_type;
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $file['name'] = str_replace('.'.$ext, '', $file['name']);
                    $file['name'] .= '.webp';
                }
                $new_image = $source_image->save($file["tmp_name"], $mime_type);
                if ($bir_options->webp_active == 1) {
                    $get_image = imagecreatefromwebp($new_image['path']); 
                    imagewebp($get_image, $new_image['path'], $quality);
                }
                // file convertito
                unlink($file["tmp_name"]);
                rename($new_image['path'], $file["tmp_name"]);
                // Sovrascrive il percorso del file temporaneo con il nuovo percorso
                $file['size'] = $new_image['filesize'];
            }
                
        }
      
        if ($bir_options->rename_active == 1) {
            // prendo l'estensione dal $file['name'] 
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $post_id = (isset($_REQUEST['post'])) ? absint($_REQUEST['post']) : 0;
            $file['name'] = Bir_rename_functions::get_name_from_file($bir_options->rename, $file["tmp_name"], 0, $post_id, $file["name"]).'.'.$ext;
            self::$uniq_id = Bir_rename_functions::$current_uniq;
           // var_dump ($file);
           // die;
        }
        //print "<p>FILE2 " . self::$original_image . "</p>";
        //var_dump ($file);
        return $file;
    }

    /**
     * Gestisce solo nella creazione di un nuovo attachment
     * @param array $metadata
     * @param int $attachment_id
     * @param string $context
     * @return array
     */
    function wp_generate_attachment_metadata($metadata, $attachment_id, $context) {
        global $bir_options;
        if ($context != 'create') return $metadata;
        if ($bir_options == null) $bir_options = new Bir_options_var();
        // TODO verifico che sia un'immagine 
        $attachment_id = absint($attachment_id);
        if (self::$original_image != null && is_file(self::$original_image)) {
            // trovo il percorso dell'immagine da $metadata["file"] 
            $path = $metadata["file"];
            $path = str_replace(basename($path), '', $path);
            //echo "<p>PATH ".$path."</p>";
            // trovo la directory dell'upload
            $upload_dir = wp_upload_dir();
            $upload_dir = $upload_dir['basedir']."/".$path;
            // cambio il nome di $metadata["name"] aggiungendo prima dell'estensione -original
            $ext_old = pathinfo($metadata['file'], PATHINFO_EXTENSION);
            $ext = pathinfo(self::$original_image_name, PATHINFO_EXTENSION);
            $original_name = str_replace('.'.$ext_old, '', wp_basename($metadata['file']));
            $original_name .= '-original.'.$ext;
            $k = 0;
            while (file_exists($upload_dir.$original_name) and $k < 100) {
                $k++;
                $original_name = str_ireplace('.'.$ext_old, '', wp_basename($metadata['file']));
                $original_name .= '-original-'.$k.'.'.$ext;

            }
            // sposto il file da $metadata['original_image'] a $upload_dir
            rename(self::$original_image, $upload_dir.$original_name);
            if (is_file($upload_dir.$original_name)) {
                $metadata['original_image'] = $original_name;
                if ($attachment_id > 0) {
                  
                    add_post_meta( $attachment_id, '_bir_attachment_originalfilesize',  filesize($upload_dir.$original_name), true );
                }
                self::$original_image = null;
                
            }
        } 
        if (isset(self::$original_image_name) && self::$original_image_name != null && $attachment_id > 0) {
            add_post_meta( $attachment_id, '_bir_attachment_originalname', self::$original_image_name, true );
            Bir_functions::update_nice_title($attachment_id);
        }
        self::$original_image_name = null;
        if (self::$uniq_id != null) {
            if ($attachment_id > 0) {
                // verifico se già esiste
                $uniq_id = get_post_meta( $attachment_id, '_bir_attachment_uniqid', true );
                if ($uniq_id == null || $uniq_id == '') {
                    add_post_meta( $attachment_id, '_bir_attachment_uniqid', self::$uniq_id, true );
                }
            }
            self::$uniq_id = null;
        }
        //print "<p>Metadata</p>";
        //var_dump ($metadata);
        return $metadata;
    }
}


new Bir_loader_upload();
