<?php
/**
* La classe facade per le azioni eseguibili dal plugin
*/
namespace bulk_image_resizer;

class Bir_facade extends Bir_extends_functions
{
    static $new_named = '';
    static public function process_image($image_id) {
        global $bir_options;
		if ($bir_options == null) $bir_options = new Bir_options_var();
        self::$last_error = '';
        self::$new_named = '';
        // trovo il mime type dell'immagine
        $mime_type = get_post_mime_type($image_id);
        if (strpos($mime_type, 'image') === false) {
            self::$last_error = "Not an image";
            return;
        }
        $old_images = Bir_rename_functions::find_image_reference($image_id);
        if (count($old_images) == 0)  {
            self::$last_error = "Image not found";
            return;
        } 
      
        $renamed = false;
        if ($bir_options->resize_active == 1) {
            Bir_optimize_functions::optimize($image_id, $bir_options->max_width, $bir_options->max_height, $bir_options->quality, ($bir_options->webp_active == 1));
        }  else if ($bir_options->optimize_active == 1) {
            Bir_optimize_functions::optimize($image_id, 5000, 5000, $bir_options->quality, ($bir_options->webp_active == 1));
        }  else if ($bir_options->webp_active == 1) {
            Bir_optimize_functions::optimize($image_id, 5000, 5000, 75, 1);
        } 

        if (Bir_optimize_functions::is_error()) {
            self::$last_error = Bir_optimize_functions::get_last_error();
        } else if ($bir_options->webp_active == 1) {
            Bir_rebuild_functions::thumbnails($image_id);
            $renamed = true;
        }

        if ($bir_options->rename_active == 1) {
            self::$new_named = Bir_rename_functions::rename($image_id, $bir_options->rename);
            $renamed =  true;
        }
        
        if ($renamed && self::$last_error == '') {
            Bir_rename_functions::replace_post_image_in_db($image_id, $old_images);
        }
        return (self::$last_error == '');
    }

    static public function restore($image_id) {
        self::$last_error = '';
        $old_images = Bir_rename_functions::find_image_reference($image_id);
        if (count($old_images) == 0) return false;
        //print("restore");
        if (Bir_optimize_functions::restore($image_id)) {
            if (Bir_optimize_functions::get_last_error() != '') {
                // se fa lo skip dell'immagine
                self::$last_error =  Bir_optimize_functions::get_last_error();
                //die (self::$last_error);
                return false;
            } 
            Bir_rename_functions::replace_post_image_in_db($image_id, $old_images);
        } else {
            self::$last_error = Bir_optimize_functions::get_last_error();
          //  die (self::$last_error);
            return false;
        }
       // die("OK");
        return true;
    }

    static public function get_new_named() {
        return self::$new_named;
    }


}