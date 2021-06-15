<?php
/**
 * Tutti i blocchi grafici. 
 * 
 * @since      1.1.0
 *
 * @package    op-bulk-image-resizer
 * @subpackage op-bulk-image-resizer/admin
 */
use opBulkImageResizer\Includes\OpFunctions AS Opfn;
if (!defined('WPINC')) die;
?>
<div class="op-form-title"><?php _e('SETTINGS', 'op-bulk-image-resizer'); ?></div>
<div class="op-grid-2-col" id="opSettingsBlock">
    <div class="op-form-row">
        <div class="op-form-label"><?php _e('Dimensions', 'op-bulk-image-resizer'); ?></div>
        <div class="op-form-field">
            <?php Opfn\html_select_dimension(absint(get_option('op_resize_max_width', 1920)) . "x" . absint(get_option('op_resize_max_height', 1080))); ?>
        </div>
    </div>
    <div class="op-form-row">
        <div class="op-form-label-big"><?php _e('Compression Quality', 'op-bulk-image-resizer'); ?></div>
        <div class="op-form-field-small">
            <?php Opfn\html_select_quality(absint(get_option('op_resize_quality', 75))); ?>
        </div>
    </div>
    <div class="op-form-row js-custom-show">
        <div class="op-form-label-big"><?php _e('Max width', 'op-bulk-image-resizer'); ?></div>
        <div class="op-form-field-small">
            <input class="js-running-input-disable" name="" type="number" min="400" id="resizeMaxWidth" value="<?php echo absint(get_option('op_resize_max_width', 1920)); ?>">
        </div>
    </div>
    <div class="op-form-row js-custom-show">
        <div class="op-form-label-big"><?php _e('Max height', 'op-bulk-image-resizer'); ?></div>
        <div class="op-form-field-small">
            <input class="js-running-input-disable" name="" type="number" min="400" id="resizeMaxHeight" value="<?php echo absint(get_option('op_resize_max_height', 1080)); ?>">
        </div>
    </div>
    <div class="op-form-row">
        <div class="op-form-label-big"><?php _e('Resize when images are loaded', 'op-bulk-image-resizer'); ?></div>
        <div class="op-form-field-small">
            <div style="float:right" class="switch-content">
                <label class="switch js-running-switch-disable" style="margin-right:1rem;">
                    <input type="checkbox" id="resizeOnUpload" value="1" class="js-running-input-disable" <?php echo (absint(get_option('op_resize_on_upload')) == 1) ? 'checked="checked"' : ""; ?>>
                    <span class="slider round"></span>
                </label>
            </div>
        </div>
    </div>
    <div class="op-form-row">
        <div class="op-form-label"></div>
        <div class="op-form-field">
            <div name="submit" id="opSubmitSetting" class="button button-primary js-running-btn-disabled"><?php _e('Save your changes', 'op-bulk-image-resizer'); ?></div>
        </div>
    </div>
</div>