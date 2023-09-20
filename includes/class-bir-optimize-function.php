<?php 
/**
 * Fa il resize delle immagini e le ottimizza
 */
namespace bulk_image_resizer;

class Bir_optimize_functions extends Bir_extends_functions {

    /**
     * Ottimizza e fa il resize di una singola immagine
     * @param int $attachment_id
     * @param int $width max-width
     * @param int $height max-height
     * @param int $quality
     * @param bool $convert_webp se true salva l'immagine in webp.
     * @return Wp_Error|Array ['path'=>string, 'file'=>string, 'width'=>int, 'height'=>int, 'mime-type'=>string]
     */
    public static function optimize($attachment_id, $width, $height, $quality = BIR_QUALITY_MEDIUM, $convert_webp = false) {
        self::$last_error = '';
        $quality = absint($quality) > 0 ? absint($quality) : BIR_QUALITY_MEDIUM;
        $width = absint($width) > 0 ? absint($width) : 1920;
        $height = absint($height) > 0 ? absint($height) : 1080;
        $attachment_id = absint($attachment_id);
        $path_attached = get_attached_file($attachment_id);
        //WP_CLI::line('Attacment_id '.$attachment_id . ' Optimizing: '.$path_attached);
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
                if (array_key_exists('quality', $ris_filter) &&  $ris_filter['quality'] > 0 && $ris_filter['quality'] <= 100) {
                    $quality = $ris_filter['quality'];
                }
            } elseif (is_bool($ris_filter)) {
                $resize = $ris_filter;
            }
            /**
             * @since 1.2.5 
             * I don't compress animated gif
             */
            if ( stripos($path_attached,'.gif') !== false) {
                $resize = !Bir_functions::op_gif_animated($path_attached);
            }


            if (!$resize) return false;

            // se esiste l'original
            $new_file_name = "";
            $path_original =  wp_get_original_image_path($attachment_id);
           
            if ($path_original != "" && $path_original != $path_attached) {
                $meta_original_image = $path_original;
                
                $img = wp_get_image_editor($path_original);
                $old_file_path = $path_attached;
                if (is_wp_error($img)) {
                    self::$last_error = "ERROR! try path attached: ".$path_attached."\n";
                    $img = wp_get_image_editor($path_attached);
                    $old_file_path = $path_attached;
                }
            } else {
                // non devo sovrascrivere l'originale
                $img = wp_get_image_editor($path_attached);
                $old_file_path = $path_attached;
                if ($path_original == "") {
                }
            }
            if (!is_wp_error($img)) { 
                if ($width > 1 && $height > 1) {
                    $img->resize($width, $height); 
                }
              
                $img->set_quality($quality);

                $original_image = self::save_original_image($attachment_id, $old_file_path);
                /**
                 * Allow to convert image to webp
                 * @since 2.0
                 */
                $meta = wp_get_attachment_metadata($attachment_id, true);
                if ($convert_webp) {
                    $save = $img->save($old_file_path, 'image/webp');
                    // 
                    if (!is_wp_error($save)) {   
                        $ext = pathinfo($old_file_path, PATHINFO_EXTENSION);
                        if ($ext != 'webp') {
                            unlink($old_file_path);
                        }
                        // FIXBUG wordpress ricarico il file e lo comprimo con la qualità impostata in php puro
                        if ($quality != BIR_QUALITY_HIGHT) {
                            $original_file_get_contents =  file_get_contents($save['path']);
                            $get_image = imagecreatefromwebp($save['path']); 
                            imagewebp($get_image, $save['path'], $quality);
                            $file_get_contents = file_get_contents($save['path']);
                            if (strlen($file_get_contents) > strlen($original_file_get_contents)) {
                                file_put_contents($save['path'], $original_file_get_contents);
                            }
                        }
                        $old_file_path = str_replace('.'.$ext, '', $old_file_path);
                        $old_file_path .= '.webp';
                        $meta['name'] = wp_basename($old_file_path);
                        $meta['file'] = str_replace('.'.$ext, '', $meta['file']);
                        $meta['file'] .= '.webp';
                        $meta['mime-type'] = 'image/webp'; 
                    }
                } else {
                    $save = $img->save($old_file_path);
                  
                }
               
                $meta['original_image'] = $original_image;
                $meta['filesize'] = filesize($old_file_path);
              
                if (!is_wp_error($save)) {   
                    update_attached_file($attachment_id, $old_file_path);
                    $meta['width'] = $save['width'];
                    $meta['height'] = $save['height'];
                    wp_update_attachment_metadata($attachment_id, $meta);
                    Bir_functions::update_post_guid_and_mime($attachment_id);
                }
                return $save;
            } else {
                return $img;
            }
        } else {
            self::$last_error = "ERROR! invalid_image";
            return new \WP_Error('invalid_image');
        }
    }

    /**
     * Ricarica l'immagine originale
     * // TODO non fa il restore del titolo!
     * @param int $attachment_id
     * @since 1.3.0
     * @return Boolean
     */
    public static function restore($attachment_id)
    {
        self::$last_error = "";
        $attachment_id = absint($attachment_id);
        $path_original =  wp_get_original_image_path($attachment_id);
        $path_original_rename_check = basename($path_original);
        $path_original_rename_check = preg_replace('/\.[^.]*$/', '', basename($path_original_rename_check));

        $path_attached = get_attached_file($attachment_id);
        
        $original_name = get_post_meta( $attachment_id, '_bir_attachment_originalname', true );
        
        if ($path_attached == $path_original) {
            if ($original_name !=  $path_original_rename_check && $original_name != "") {
                self::restore_rename($attachment_id, $original_name);
                return true;
            }
            // salto l'immagine
            self::$last_error = "skipped";
            return false;
        }
        
        $return = true;
      
        if (is_file($path_original) ) {
            // ricarico l'immagine originale
            //print "\n path_attached ".$path_attached."\n<br>";
            //print "\n path_original ".$path_original."\n<br>";
            //die;
         
            $meta = wp_get_attachment_metadata($attachment_id, true);
            // Visto che la gestione dell'original image passa per un filtro
            // se voglio rimuovere original image devo passare il valore vuoto!
            $meta['original_image'] = '';
            
            $img = wp_get_image_editor($path_original);
            if (is_wp_error($img)) {
                self::$last_error = "ERROR! try path attached: ".$path_attached."\n";
                $img = wp_get_image_editor($path_attached);
                if (is_wp_error($img)) {
                    return false;
                }
            }
            $img2 = $img->get_size();	
            $result['width'] = $img2['width']; 
            $result['height'] = $img2['height']; 
            update_attached_file($attachment_id, $path_original);
            $meta['width'] = $img2['width']; 
            $meta['height'] =  $img2['height'];
            if (is_file($path_attached) && file_is_valid_image($path_attached)) {
                unlink($path_attached);
            }
            
            wp_update_attachment_metadata($attachment_id, $meta); 
          
            Bir_rebuild_functions::thumbnails($attachment_id);
            // 
          
            // rimuovo l'estensione dalla stringa $path_original
         
            $return = true;
        } else {
            // rimuovo l'immagine originale
            $meta = wp_get_attachment_metadata($attachment_id, true);
            if (isset($meta['original_image'])) {
                unset($meta['original_image']);
            }
            wp_update_attachment_metadata($attachment_id, $meta); 

            self::$last_error = "invalid_image";
            $return = false;
        }
       
        if ($original_name != $path_original_rename_check && $original_name != "") {
            self::restore_rename($attachment_id, $original_name);
            $return = true;
        }

        Bir_functions::update_post_guid_and_mime($attachment_id);

        delete_post_meta( $attachment_id, '_bir_attachment_originalname');
        delete_post_meta( $attachment_id, '_bir_attachment_originalpostname');
        delete_post_meta( $attachment_id, '_bir_attachment_originaltitle');
        return $return;
    }
   

    /**
     * @param string $original_name nome originale dell'immagine
     */
    private static function restore_rename($attachment_id, $original_name) {
        $attachment_id = absint($attachment_id);
        // rimuovo l'estensione da $original_name mantenendo il full path
        $original_name = str_replace(".".pathinfo($original_name, PATHINFO_EXTENSION), '', $original_name);

        Bir_rename_functions::rename($attachment_id, $original_name, false, false);
        delete_post_meta( $attachment_id, '_bir_attachment_originalname');

        $postitle = get_post_meta( $attachment_id, '_bir_attachment_originaltitle', true );
        $array_post_update = array(
            'ID' => $attachment_id,
            'post_title' => $postitle
        );
        $postname = get_post_meta( $attachment_id, '_bir_attachment_originalpostname', true );
        if ($postname != "") {
            $array_post_update['post_name'] = $postname;
        }

        if ($postitle != "") {
            wp_update_post( $array_post_update );
        }
    }

    /**
     * Rimuove l'immagine originale da un attachment
     */
    public static function delete_original($image_id) {
        $return = false;
        $image_id = absint($image_id);
        $meta = wp_get_attachment_metadata($image_id, true);
        $upload_dir = wp_upload_dir();
        $upload_dir = $upload_dir['basedir'];
        if (isset($meta['original_image'])) {
            $file = $meta['file'];
            // ritorno il nome del file dal percorso $file
            $filename = wp_basename($file);
            // ritorno il percorso delle cartelle da $file senza il nome del file
            $path = str_replace($filename, '', $file);
            $meta['original_image'] = wp_basename($meta['original_image']);
            $path_original = str_replace("//","/", $upload_dir.'/'.$path.'/'.$meta['original_image']);
            if (is_file($path_original)) {
                $return = unlink($path_original);  
            }
            unset($meta['original_image']);
            wp_update_attachment_metadata($image_id, $meta);
        } 
        if ($return) {
            // rimuovere il nome originale
            delete_post_meta( $image_id, '_bir_attachment_originalname');
            delete_post_meta( $image_id, '_bir_attachment_originaltitle');
        }
        return $return;
    }

    /**
     * Salvo l'immagine originale
     * @param $attachment_id int
     * @param $old_file_path string path completo del file originale 
     */
    public static function save_original_image($attachment_id, $old_file_path) {
        // se esiste già un file originale non faccio nulla
        if (get_post_meta( $attachment_id, '_bir_attachment_originalname', true ))  {
            $meta = wp_get_attachment_metadata($attachment_id, true);
            if (isset($meta['original_image']) && $meta['original_image'] != '') {
                return $meta['original_image'];
            }
        }
        // devo rinominare il file originale mettendo un suffisso -original
        $suffix = apply_filters( 'op_bir_resize_image_bulk_suffix', 'original');
        $suffix_k = 0;
        $ext = pathinfo($old_file_path, PATHINFO_EXTENSION);
        $meta_original_image = str_ireplace(".".$ext, "", $old_file_path);
        $meta_original_image .= "-".$suffix.".".$ext;
        while (file_exists( $meta_original_image ) && $suffix_k < 999 ) {
            $meta_original_image = str_ireplace(".".$ext, '', $old_file_path);
            $meta_original_image .= "-".$suffix.".".$ext;
            $suffix_k++;
        }
        // copy $old_file_path to $meta_original_image
        $original_name = wp_basename($old_file_path);
        copy($old_file_path, $meta_original_image);       
        add_post_meta( $attachment_id, '_bir_attachment_originalname', $original_name, true );
        add_post_meta( $attachment_id, '_bir_attachment_originalfilesize',  filesize($meta_original_image), true );
        return wp_basename($meta_original_image);
    }


}