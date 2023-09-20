<?php
/**
 * Gestisco i dati salvati nelle opzioni in una classe
 */
namespace bulk_image_resizer;

// definisco una costante BIR_QUALITY_HIGHT 
if (!defined('BIR_QUALITY_HIGHT')) {
    define('BIR_QUALITY_HIGHT', 90);
}
if (!defined('BIR_QUALITY_MEDIUM')) {
    define('BIR_QUALITY_MEDIUM', 82);
}
if (!defined('BIR_QUALITY_LOW')) {
    define('BIR_QUALITY_LOW', 75);
}

class Bir_options_var
{
    private $resize_active = 0;
    private $max_width = 0;
    private $max_height = 0;
    private $optimize_active = 0;
    private $quality = 0;
    private $webp_active = 0;
    private $rename_active = 0;
    private $rename = '';
    private $rename_change_title = 0;
    private $version = 20;

    function __construct() {
        $this->load();
    }

    function save() {
        $json = json_encode($this->array());
        update_option('bulk_image_resizer', $json, false);
    }

    function load() {
        $json = get_option('bulk_image_resizer', '[]');
        $json  = json_decode($json, true);
        if((isset($json["version"]) && ($json["version"] == "1.2.0" || $json["version"] == "1.3.0")) || !isset($json["version"])) {
            $json['resize_active'] = 1;
            $json['optimize_active'] = 1;
            $json["version"] = 1;
           
        }
        if (!isset($json["quality"])) {
            $json["quality"] = BIR_QUALITY_MEDIUM;
        }
        if (!isset($json["max_width"])) {
            $json["max_width"] = 1920;
        }
        if (!isset($json["max_height"])) {
            $json["max_height"] = 1080;
        }
        if (json_last_error() != JSON_ERROR_NONE) {
             $json  = array();
        }
        foreach ($json as $key => $value) {
            $this->$key = $value;
        }
    }

    public function __get($name) {
        if (property_exists($this, $name)) {
            if ($name == 'rename') {
                if ($this->rename == '') {
                    return '[image_name]';
                }
            }
            if ($name == "quality") {
                if ($this->optimize_active == 0) {
                    return BIR_QUALITY_MEDIUM;
                }
            }
            return $this->$name;
        } else {
            return null;
        }
    }

    public function __set($name, $value) {
        if ($name == 'max_width') {
            $this->$name = (absint($value) > 1) ? absint($value) : 1920;
        }
        if ($name == 'max_height') {
            $this->$name = (absint($value) > 1) ? absint($value) : 1080;
        }
        if ($name == 'quality') {
            $this->$name = (absint($value) > 10 && absint($value) < 100) ? absint($value) : BIR_QUALITY_MEDIUM;
        }
        if ($name == 'rename') {
            $this->$name = sanitize_text_field($value);
        }

        if (in_array($name, ['resize_active',  'optimize_active', 'webp_active', 'rename_active', 'rename_change_title'])) {
            $this->$name = (absint($value) == 1) ? 1 : 0;
        } 
    }

    public function plugin_active() {
        return $this->resize_active == 1 || $this->optimize_active == 1 || $this->webp_active == 1 || $this->rename_active == 1;
    }

    /**
     * Restituisce un array con tutte le variabili della classe
     */
    public function array() {
        $vars = get_object_vars($this);
        foreach ($vars as $key=>$v) {
            if (empty($v) && $v !== 0 && $v !== '0' && $v !== false) {
                unset($vars[$key]);
            }
        }
        return $vars;
    }
}

