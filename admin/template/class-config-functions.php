<?php
/**
 * Elementi grafici per il config
 */
namespace bulk_image_resizer;

class Bir_config_fn {
    /**
     * Genera il select per le dimensioni preset
     * @param string $val 1280x720|1920x1080|2560x1440|2100x2100| custom values widthxheight
     * @return string Html
     */
    static function html_select_dimensions($val) {
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
                ?><option value="<?php echo esc_attr($key); ?>"<?php echo $selected
                ; ?>><?php echo esc_html($label); ?></option><?php
            }
        ?>
        </select>
        <?php
    }

    /**
     * Genera il select per la qualità delle immagini
     * @param string $val 
     * @return string Html
     */
    static function html_select_quality($val = BIR_QUALITY_MEDIUM) {
        $dim = array(BIR_QUALITY_LOW=>'LOW', BIR_QUALITY_MEDIUM=>'MEDIUM', BIR_QUALITY_HIGHT=>'HIGHT');
        ?>
        <select name="quality" id="settingQuality" class="js-running-input-disable">
        <?php
            foreach ($dim as $key=>$label) {
                $selected = ($key == $val) ? ' selected="selected"' : ""; 
                ?><option value="<?php echo esc_attr($key); ?>"<?php echo $selected; ?>><?php echo esc_html($label); ?></option><?php
            }
        ?>
        </select>
        <?php
    }
    /**
     * Genera il select per rinominare le immagini
     */
    static function html_select_rename($rename = '[image_name]') {
        $dim = array('[image_name]'=>'SANITIZE ORIGINAL NAME', '[uniqid]'=>'Unique ID', ''=>'CUSTOM');
      
        ?>
        <select name="rename_type" id="selectSettingRename" class="js-running-input-disable">
        <?php
            foreach ($dim as $key=>$label) {
                $selected = ($key == $rename || ($rename == '' && $key == '[image_name]')) ? ' selected="selected"' : ""; 
                ?><option value="<?php echo esc_attr($key); ?>"<?php echo $selected; ?>><?php echo esc_html($label); ?></option><?php
            }
        ?>
        </select>
        <?php
    }
    
}