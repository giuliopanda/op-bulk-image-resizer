<?php 
/**
 * Le modifiche che posso fare sulle immagini
 * ```
 *  +rename($image_id, $new_name, $debug = false) 
 *  +find_image_reference()
 *  +find_posts()
 *  +find_postmeta()
 *  +replace_occurrences()
 *  +replace_post_image_in_db()
 * 
 *  self:rename -> wp_get_attachment_metadata
 *  self.rename -> self.rename_img
 * 
 * ```
 * 
 * @since      2.0.0
 *
 * @package    bulk-image-resizer
 * @subpackage bulk-image-resizer/includes
 */

 namespace bulk_image_resizer;

class Bir_rename_functions extends Bir_extends_functions {

    static $uniq_array = [];
    static $current_uniq = null;
    /**
     * rinomina un file immagine tutti i dati dell'immagine 
     * thumbs comprese e aggiorna i dati nel db dell'immagine
     * 
     * @param int $image_id
     * @param string $new_name il nuovo nome del file senza estensione
     * @param bool $debug se true stampa i messaggi di debug e non converte l'immagine
     * @param bool $rebuilt_name Se false mette il nome così com'è senza rielaborarlo
     * 
     * @return string|bool il nuovo nome dell'immagine con percorso relativo o false se c'è un errore
     */
    public static function rename($image_id, $new_name, $debug = false, $rebuilt_name = true) {
        global $bir_options;
        if ($bir_options == null) $bir_options = new Bir_options_var();
        if ($bir_options == null) $bir_options = new Bir_options_var();
        self::$last_error = '';
        self::$debug_msgs[basename(__FILE__).':'.__LINE__.'@'.count(self::$debug_msgs)] = 'function rename';
        // aggiungo un metadato e lo salvo su postmeta con il nome originale dell'immagine
        if (empty($new_name) || $new_name == '') {
            $new_name = '[image_name]';
		}
        // Recupera l'immagine dal suo ID.
		$image = wp_get_attachment_metadata($image_id);
        if (!$image) return;
        
        if ($rebuilt_name) {
            $new_name = self::get_name_from_attachment($image_id, $new_name);
        }
        $new_name_title = str_replace(["'",'"'], "", strip_tags(str_replace(['-',"_"], ' ', $new_name)));
        $original_name =  basename($image['file']);
        $original_name = preg_replace('/\.[^.]+$/', '', $original_name);
        if ($original_name == $new_name) return $image['file'];
        if (!get_post_meta( $image_id, '_bir_attachment_originalname', true ))  {
            add_post_meta( $image_id, '_bir_attachment_originalname', $original_name, true );
        } 

		// Verifica se l'immagine esiste.
		if (!$image) {
            self::$last_error = 'The image with ID '.$image.' specified does not exist.';
            return false;
		}
        
		// Rinomina l'immagine originale es: 2023/05/753_parigi_thb.jpg
		$old_name = $image['file'];
        // divido il percorso 2023/05/ dal nome del file 753_parigi_thb.jpg
        $old_name_dir = dirname($old_name);
        // add slash if not present
        if (substr($old_name_dir, -1) != '/') $old_name_dir .= '/';
        $old_name_image_file = basename($old_name);

        self::$debug_msgs[basename(__FILE__).':'.__LINE__.'@'.count(self::$debug_msgs)] = ['old_name_dir'=>$old_name_dir, 'old_name_file'=>$old_name_image_file, 'new_name'=>$new_name];

        // esistono due immagini una presa da $image['file'] che è l'immagine originale e uno preso da get_attached_file($image_id) che è l'immagine compressa. 
        $old_attached_path = get_attached_file($image_id); 
		$old_attached_file_base_name = basename($old_attached_path);

        // Rinomina il file allegato commpresso
        self::$debug_msgs[basename(__FILE__).':'.__LINE__.'@'.count(self::$debug_msgs)] = ['old_name_dir'=>$old_name_dir, 'old_name_file'=>$old_attached_file_base_name, 'new_name'=>$new_name];

        $image['file'] = $new_path =  $old_name_dir . self::rename_img($old_attached_file_base_name, $new_name,  $old_name_dir, $debug);
		if (!$debug) {
			update_post_meta( $image_id, '_wp_attached_file', $new_path);
		}


		// Rinomina tutte le occorrenze del nome del file nelle proprietà dell'array $image. (Sono le thumbs)
		foreach ($image['sizes'] as &$size) {
            self::$debug_msgs[basename(__FILE__).':'.__LINE__.'@'.count(self::$debug_msgs).'@'.count(self::$debug_msgs)] = ['old_name_dir'=>$old_name_dir, 'old_name'=>$size['file'], 'new_name'=>$new_name.'-'.$size['width'].'x'.$size['height']];

            $size['file'] = self::rename_img($size['file'],  $new_name.'-'.$size['width'].'x'.$size['height'], $old_name_dir, $debug);
		}

		// Aggiorna i metadati dell'immagine.
		if (!$debug) {
            wp_update_attachment_metadata($image_id, $image);
            // cambio il titolo del post con id = $image_id
          
            if ($bir_options->rename_change_title == 1) {
                // trovo il titolo del post
                $post = get_post($image_id);

                if (!get_post_meta( $image_id, '_bir_attachment_originaltitle', true ))  {
                    add_post_meta( $image_id, '_bir_attachment_originaltitle', $post->post_title, true );
                } 
                Bir_functions::update_nice_title($image_id);
            }
          
        }
        Bir_functions::update_post_guid_and_mime($image_id);
        return $new_path;
    }


    /**
     * Trova gli url di un'immagine e delle sue thumbs in modo da avere un array con tutti gli url dell'immagine da cercare poi nel db 
     * @param int $image_id l'id dell'immagine
     * @return array un array con tutti gli url dell'immagine [original, compress, 300x200, 600x400, ...]
     */
    public static function find_image_reference($image_id) {
        self::$debug_msgs[basename(__FILE__).':'.__LINE__.'@'.count(self::$debug_msgs)] = 'function find_image_reference';
        $image = wp_get_attachment_metadata($image_id);
        if (!$image || !isset($image['file'])) {
            return [];
        }
        $name_dir = dirname($image['file']);
        // add slash if not present
        if (substr($name_dir, -1) != '/') $name_dir .= '/';
        $name_image_file = basename($image['file']);

        $attached = get_attached_file($image_id);
        $attached = basename($attached);

        $image_ref = ['original' => $image['file']];

        if ($attached != $name_image_file) {
            $image_ref['attached'] = $name_dir.$attached;
        }
        foreach ($image['sizes'] as &$size) {
            $image_ref[$size['width'].'x'.$size['height']] = $name_dir.$size['file'];
        }
        $image_ref['url'] = wp_get_attachment_url($image_id);
        return $image_ref;
    }

    /**
     * Trovo le occorrenze di un'immagine nei post
     * @param $ref_images array un array con tutti gli url dell'immagine [original, compress, 300x200, 600x400, ...]
     * @return array un array con tutti i post che contengono l'immagine ['id'=>'content', ...]
     */
    public static function find_posts($ref_images) {
        global $wpdb;
        $likes = [];
        foreach ($ref_images as $value) {
            $likes[] = 'post_content LIKE "%'.esc_sql($value).'%"';
        }
        $sql = "SELECT ID, post_content FROM $wpdb->posts WHERE ".implode(' OR ', $likes);
        $posts = $wpdb->get_results($sql);
        $post_array = [];
        foreach ($posts as $post) {
            $post_array[$post->ID] = $post->post_content;
        }
        return $post_array;
    }

     /**
     * Trovo le occorrenze di un'immagine nei post
     * @param $ref_images array un array con tutti gli url dell'immagine [original, compress, 300x200, 600x400, ...]
     * @return array un array con tutti i post che contengono l'immagine ['meta_id'=>'meta_value', ...]
     */
    public static function find_postmeta($ref_images) {
        global $wpdb;
        $likes = [];
        foreach ($ref_images as $key => $value) {
            $likes[] = 'meta_value LIKE "%'.esc_sql($value).'%"';
        }
        $sql = "SELECT meta_id, meta_value FROM $wpdb->postmeta WHERE ".implode(' OR ', $likes);
        $posts = $wpdb->get_results($sql);
        $post_array = [];
        foreach ($posts as $post) {
            $post_array[$post->meta_id] = $post->meta_value;
        }
        return $post_array;
    }

    /**
     * Trovo le occorrenze di un'immagine all'interno di un testo verificando se è un serializzato  
     * @param string $string il testo in cui cercare l'immagine
     * @param array $images_search un array con tutti gli url dell'immagine [original, compress, 300x200, 600x400, ...]
     * @param array $images_replace un array con tutti i nuovi url dell'immagine [original, compress, 300x200, 600x400, ...]
     * 
      */
    public static function replace_occurrences($string, $images_search, $images_replace) {
        // verifico se $string è un serializzato
        if (is_serialized($string)) {
            $string = unserialize($string);
            foreach ($images_search as $key => $value) {
                if (!isset($images_replace[$key])) continue;
                $string = self::search_and_replace_array($value, $images_replace[$key], $string);
            }
            $string = serialize($string);
        } else {
            foreach ($images_search as $key => $value) {
                if (!isset($images_replace[$key])) continue;
                $string = str_replace($value, $images_replace[$key], $string);
            }
        }
        return $string;
    }

    /**
     * Aggiorno i post che contengono l'immagine
     */
    public static function replace_post_image_in_db($image_id, $old_images) {
        global $wpdb;
        $new_images = Bir_rename_functions::find_image_reference($image_id);
        //var_dump ($old_images, $new_images );
        //die;
        // verifico se le old images sono differenti dalle new images
        $diff = array_diff($old_images, $new_images);
        if (empty($diff)) {
            return;
        }
        $posts = Bir_rename_functions::find_posts($old_images);
        foreach ($posts as $post_id => $post_content) {
            $post_content1 = $post_content;
            $post_content = Bir_rename_functions::replace_occurrences($post_content, $old_images, $new_images);
            // aggiorno il post
            if ($post_content != $post_content1) {
                wp_update_post( ['ID' => $post_id, 'post_content' => $post_content] );
            }
        }
        // cerco tutte le occorrenze nei vari post meta e le sostituisco con il nuovo nome
        $postmeta = Bir_rename_functions::find_postmeta($old_images);
        foreach ($postmeta as $post_meta_id => $post_meta_value) {
            $post_meta_value = Bir_rename_functions::replace_occurrences($post_meta_value, $old_images, $new_images);
            // aggiorno il postmeta
            $wpdb->update( $wpdb->postmeta, ['meta_value' => $post_meta_value], ['meta_id' => $post_meta_id] );
        }
    }

    public static function get_name_from_attachment($image_id, $string) {
        $image = wp_get_attachment_metadata($image_id);
        if (!$image) return false;
        $image_path = get_attached_file($image_id);
        return self:: get_name_from_file($string, $image_path, $image_id);
    }

    /**
     * Genero il nome dell'immagine senza estensione
     * @param $image_path absolute path dell'immagine
     * 
    * il nome nuovo è composto da una stringa con shortcode
    * [uniqid] che viene sostituito con un id univoco
    * [md5] che viene sostituito con l'md5 del nome originale
    * [date] che viene sostituito con la data di upload dell'immagine
    * [time] che viene sostituito con l'ora di upload dell'immagine
    * [timestamp] che viene sostituito con il timestamp di upload dell'immagine
    * [image_name] che viene sostituito con il nome originale dell'immagine sanificato
    * accetta qualsiasi altro shortcode di wordpress
     */
    public static function get_name_from_file($string, $image_path, $image_id = 0, $post_id = 0, $newfilename = '') {
        $alph = $alph2 = "0123456789abcdefghijklmnopqrstuvwxyz";
        $alph2 = str_shuffle($alph2);
        self::$current_uniq = null;
        if (empty($string) || (is_string($string) && trim($string) == '') || !is_string($string)) {
            $string = time()."_".rand(1000, 9999);
            // converto in esadecimali
            $string = dechex($string);

        } else if( strpos($string, '[') !== false) {
            $string = strip_tags($string);
            //[img_name]
            // load image with get_content_file 
            if (stripos($string, '[md5]') !== false) {
                $image_content = file_get_contents($image_path);
                $string = str_replace("[md5]", md5($image_content), $string);
            }
            if ($image_id > 0) {
                $string = str_replace("[id]", $image_id, $string);
            } else {
                $string = str_replace("[id]", uniqid(), $string);
            }
            $string = str_replace("[date]", date('Y-m-d'), $string);
            $string = str_replace("[time]", date('H-i-s'), $string);
            $string = str_replace("[timestamp]", time(), $string);

            $uniq = $get_post_meta = '';
            if ($image_id > 0) {
                $get_post_meta = $uniq = get_post_meta( $image_id, '_bir_attachment_uniqid', true );
            }
            if ($uniq == '' || $uniq == null) {
                $uniq = $tmp_uniq = uniqid();
                $temp_count_unique =  0;
                while (in_array($uniq, self::$uniq_array)) {
                    if (strlen($uniq) == 0) {
                        $temp_count_unique++;
                        $uniq = $tmp_uniq . str_pad($temp_count_unique, 3, '0', STR_PAD_LEFT);
                    } else {
                        $rand_alph = substr($alph2, 0, 1);
                        $alph2 = substr($alph2, 1);
                        $uniq = $tmp_uniq . $rand_alph;
                    }
                }
            }
            if ($image_id > 0 && $get_post_meta == '') {
                // salvo nei post meta
                add_post_meta( $image_id, '_bir_attachment_uniqid', $uniq, true );
            }
            self::$current_uniq = (stripos($string, '[uniqid]') !== false) ? $uniq : null;
            self::$uniq_array[] = $uniq;
            $string = str_replace("[uniqid]", $uniq, $string);
            $rand_alph = substr(str_shuffle($alph), 0, 1);
            $string = str_replace("[rand]", $rand_alph, $string);
            $image_filename = wp_basename($image_path);
            // rimuovo l'estensione
            $image_filename = preg_replace('/\.[^.]+$/', '', $image_filename);
            if (stripos($string, '[image_name]') !== false)  {
                $image_filename = preg_replace('/\.[^.]+$/', '',wp_basename($newfilename));
                if ($image_id > 0) {
                    $name_temp = get_post_meta( $image_id, '_bir_attachment_originalname', true );
                    if ($name_temp != '') {
                        $image_filename = preg_replace('/\.[^.]+$/', '',wp_basename($name_temp));
                    }
                }
            }
            // se sono più di 32 caratteri li taglio
            if (stripos($string, '[post_name]') !== false) {
                $image_filename = substr($image_filename, 0, 32);
            } else {
                $image_filename = substr($image_filename, 0, 64);
            }
          
            $string = str_replace("[image_name]", sanitize_file_name($image_filename), $string);
            /*
            // Versione 3 
            if (stripos($string, '[post_name]') !== false && ($image_id > 0 || $post_id > 0)) {
                $post = null;
                if ($image_id > 0) {
                    $post_image = get_post($image_id);
                    if ($post_image->post_parent) {
                        $post = get_post($post_image->post_parent);
                    }
                } else if ($post_id > 0) {
                    $post = get_post($post_id);
                }
                if ($post != null && isset($post->post_title)) {
                    $string = str_replace("[post_name]", substr(sanitize_file_name(strip_tags($post->post_title)),0, 32), $string);
                } else {
                    $string = str_replace("[post_name]", '', $string);
                }
            }
            */
        }
        // rimuovo i caratteri speciali
        $string = str_replace(["   ", "  ", " "], '-', trim($string));
        $string = strtolower(preg_replace('/[^A-Za-z0-9\-_]/', '', $string));
        if (strlen($string) > 200) {
            $string = substr($string, 0, 200);
        }
        $string = str_replace(["--","__"], ['-','_'], $string);
        // eseguo gli shortcode di wordpress
        $string = do_shortcode($string);

        $extension = pathinfo($image_path, PATHINFO_EXTENSION);
        $new_name = $string;
        $count = 0;

        $original_name =  wp_basename($image_path);
        $original_name = preg_replace('/\.[^.]+$/', '', $original_name);
        if ($original_name == $new_name) return $new_name;

        while (file_exists($image_path . '/' . $new_name . '.' . $extension)) {
            $count++;
            $new_name = $string . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
            
        } 
        return $new_name;
    }


    private static function search_and_replace_array($search, $replace, $array) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                // Se il valore è un array, richiamo la funzione ricorsivamente
                $array[$key] = self::search_and_replace_array($search, $replace, $value);
            } else if (is_string($value)) {
                // Se il valore è una stringa, cerco e sostituisco la stringa
                $array[$key] = str_replace($search, $replace, $value);
            }
        }
        return $array;
    }

    /**
     * rinomina un file immagine dentro upload 
     * @param string $old_name solo il nome del file
     * @param string $new_name solo il nome del file senza estensione
     * @param string $upload_dir la directory di upload es: 2023/02
     * @return string old_name se non è stato possibile rinominare l'immagine
     */
    private static function rename_img($old_name, $new_name, $upload_dir, $debug = false) {
        $base_dir = wp_upload_dir()['basedir']."/";
        $ext = pathinfo($old_name, PATHINFO_EXTENSION);
        $new_name_ext = pathinfo($new_name, PATHINFO_EXTENSION);
        if ($new_name_ext != $ext) $new_name = $new_name.".".$ext;
        $new_name = sanitize_file_name($new_name);
        if ($old_name == $new_name) return $old_name;
        $old_name_complete = $base_dir.'/'.$upload_dir.'/'.str_replace([$base_dir,$upload_dir], '', $old_name);
		$new_path_complete = $base_dir.'/'.$upload_dir.'/'.str_replace([$base_dir,$upload_dir] , '', $new_name);
        $add = 0;
        $new_name_temp = str_replace(".".$ext, "", $new_name);
        while (file_exists($new_path_complete) && $add < 100) {
            $add++;
            $new_name = $new_name_temp."-" . str_pad($add, 2, "0", STR_PAD_LEFT).".".$ext;
            $new_path_complete = $base_dir.'/'.$upload_dir.'/'.str_replace([$base_dir,$upload_dir] , '', $new_name);
        }
        if (file_exists($new_path_complete)) return $old_name;
        
        self::$debug_msgs[basename(__FILE__).':'.__LINE__.'@'.count(self::$debug_msgs)] = 'old_name_complete: '.$old_name_complete. ', new_path_complete: '.$new_path_complete;
        if ($debug) return $new_name;
        if (!file_exists($old_name_complete)) return $old_name;
        if (rename($old_name_complete,  $new_path_complete)) {
			return $new_name;
		}
        return $old_name;
    }


}
