<?php
/**
 * Questa è la classe da estendere per le funzioni 
 * Gestisce gli errori e i debug
 */
namespace bulk_image_resizer;

class Bir_extends_functions {
    
    public static $last_error = '';
    public static $debug_msgs = [];
    
    /**
     * @return bool true se c'è un errore
     */
    public static function is_error() {
        return self::$last_error != '';
    }

    /**
     * @return string l'ultimo errore
     */
    public static function get_last_error() {
        return self::$last_error;
    }

    /**
     * @example: self::$debug_msgs[basename(__FILE__).':'.__LINE__.'@'.count(self::$debug_msgs)] = 'messaggio';
     * @return array debug
     */
    public static function get_msgs() {
        ob_start();
        foreach (self::$debug_msgs as $key => $msg) {
            $key = explode('@', $key)[0];
            if (is_array($msg) || is_object($msg)) {
                echo $key.": ".json_encode($msg).PHP_EOL;
            } else {
                echo $key.": ".$msg.PHP_EOL;
            }
        }
        return ob_get_clean();
    }

}