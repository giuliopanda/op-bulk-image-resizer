<?php
/**
 * Il template della pagina amministrativa
 * Lo spazio dei grafici è impostato qui, e poi verrà disegnato in javascript
 * l'html del setup e del resize bulk invece è caricato sui due html a parte
 * 
 * @since      1.1.0
 *
 * @package    bulk-image-resizer
 * @subpackage bulk-image-resizer/admin
 */
namespace bulk_image_resizer;

if (!defined('WPINC')) die;
$info = Bir_list_functions::status();
?>
<div class="wrap" id="statusof_bulk_image_container"> 
    <h1 class="wp-heading-inline"><?php _e('BULK IMAGES', 'bulk-image-resizer'); ?></h1>
    <span class="page-title-action js-running-btn-disable" onclick="birExecuteBtn()"><?php _e('Process Images', 'bulk-image-resizer'); ?></span>
    <span id="btnPause" class="page-title-action" onclick="birPauseBtn()" style="display:none"><?php _e('Pause', 'bulk-image-resizer'); ?></span>
    <span id="btnResume" class="page-title-action" onclick="birResumetBtn()" style="<?php echo ($info['status'] == 'RUNNING' && $info['action'] == 'resize') ? 'display:inline-block"' : 'display:none' ?>"><?php _e('Resume', 'bulk-image-resizer'); ?></span>
    <span id="btnStop" class="page-title-action dbp-submit" onclick="birStopBtn()" style="display:none"><?php _e('Stop', 'bulk-image-resizer'); ?></span>

    <hr class="wp-header-end">
    <?php if ($check_fn_editor != '') : ?>
        <div class="op-alert-danger"><?php _e("There is something about the site configuration that worries me:", 'bulk-image-resizer'); ?><b><?php echo $check_fn_editor; ?></b><br>
        <?php _e("You probably won't be able to optimize images with this setup.", 'bulk-image-resizer'); ?>
    </div>
    <?php endif; ?>
    <dic class="bir-container">
        <ul class="bir-tabs" id="bir_tabs">
            <li class="bir-tab bir-tab-active" id="birTabConfig" data-idcontent="bir_cont_config"><span class="dashicons dashicons-admin-generic"></span> <?php _e('Configuration', 'bulk-image-resizer'); ?></li>
            <li class="bir-tab" id="birTabBulk" data-idcontent="bir_cont_bulk"><span class="dashicons dashicons-images-alt"></span> <?php _e('Image processing', 'bulk-image-resizer'); ?></li>
            <li class="bir-tab"  data-idcontent="bir_cont_stat"><span class="dashicons dashicons-chart-area"></span> <?php _e('Statistic', 'bulk-image-resizer'); ?></li>
            <li class="bir-tab"  data-idcontent="bir_cont_doc"><span class="dashicons dashicons-book-alt"></span> <?php _e('Documentation', 'bulk-image-resizer'); ?></li>
            <li class="bir-info" id="birInfo"></li>
        </ul>
        <div id="bir_tab_contents">
            <div id="bir_cont_config" class="bir-tab-content bir-tab-content-active">
                <?php require_once(plugin_dir_path(__FILE__) . "part-config.php"); ?>
            </div>
            <div id="bir_cont_bulk" class="bir-tab-content">
                <?php require_once(plugin_dir_path(__FILE__) . "part-bulk.php"); ?>
            </div>
            <div id="bir_cont_stat" class="bir-tab-content">
                <?php require_once(plugin_dir_path(__FILE__) . "part-stat.php"); ?>
            </div>
            <div id="bir_cont_doc" class="bir-tab-content">
                <?php require_once(plugin_dir_path(__FILE__) . "part-doc.php"); ?>
            </div>

        </div>
    </div>
