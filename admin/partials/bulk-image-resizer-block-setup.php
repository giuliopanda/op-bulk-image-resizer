<?php
/**
 * Il setup 
 * 
 * @since      1.1.0
 *
 * @package    bulk-image-resizer
 * @subpackage bulk-image-resizer/admin
 */
use opBulkImageResizer\Includes\OpFunctions AS Opfn;
if (!defined('WPINC')) die;

?>
<div class="op-form-title"><?php _e('SETTINGS', 'bulk-image-resizer'); ?></div>
<form id="opBulkImageResizerSetup">
    <input type="hidden" name="action" value="op_save_configuration">
    <input type="hidden" name="op_resize[version]" value="1.2.0">
    
    <div class="op-grid-2-col" id="opSettingsBlock">
        <?php 
        /**
         * Puoi aggiungere html nel form setting
         *
         * @since 1.2.0
         */
        do_action( 'bulk-image-resizer-before-setup-form');
        ?>
        <div class="op-form-row">
            <div class="op-form-label"><?php _e('Dimensions', 'bulk-image-resizer'); ?></div>
            <div class="op-form-field">
                <?php Opfn\html_select_dimension($options['max_width'] . "x" . $options['max_height']); ?>
            </div>
        </div>
        <div class="op-form-row">
            <div class="op-form-label-big"><?php _e('Compression Quality', 'bulk-image-resizer'); ?></div>
            <div class="op-form-field-small">
                <?php Opfn\html_select_quality($options['quality']); ?>
            </div>
        </div>
        <div class="op-form-row js-custom-show">
            <div class="op-form-label-big"><?php _e('Max width', 'bulk-image-resizer'); ?></div>
            <div class="op-form-field-small">
                <input class="js-running-input-disable" name="op_resize[max_width]" type="number" id="resizeMaxWidth" value="<?php echo absint($options['max_width']); ?>">
            </div>
        </div>
        <div class="op-form-row js-custom-show">
            <div class="op-form-label-big"><?php _e('Max height', 'bulk-image-resizer'); ?></div>
            <div class="op-form-field-small">
                <input class="js-running-input-disable" name="op_resize[max_height]" type="number"  id="resizeMaxHeight" value="<?php echo absint( $options['max_height'] ); ?>">
            </div>
        </div>
        <div class="op-form-row">
            <div class="op-form-label-big"><?php _e('Resize when images are uploaded', 'bulk-image-resizer'); ?></div>
            <div class="op-form-field-small">
                <div style="float:right" class="switch-content">
                    <label class="switch js-running-switch-disable" style="margin-right:1rem;">
                        <input type="checkbox" id="resizeOnUpload" value="1" name="op_resize[on_upload]" class="js-running-input-disable" <?php echo (absint($options['on_upload']) == 1) ? 'checked="checked"' : ""; ?>>
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
        </div>

        <div class="op-form-row">
            <div class="op-form-label-big"><?php _e('Delete Original', 'bulk-image-resizer'); ?></div>
            <div class="op-form-field-small">
                <div style="float:right" class="switch-content">
                    <label class="switch js-running-switch-disable" style="margin-right:1rem;">
                        <input type="checkbox" id="resizeDeleteOriginal" value="1" name="op_resize[delete_original]" class="js-running-input-disable" <?php echo (absint($options['delete_original']) == 1) ? 'checked="checked"' : ""; ?>>
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
        </div>
        <?php 
         /**
         * Puoi aggiungere html nel form setting
         *
         * @since 1.2.0
         */
         do_action( 'bulk-image-resizer-after-setup-form');
        ?>
        <div class="op-form-row">
            <div class="op-form-label"></div>
            <div class="op-form-field">
                <div name="submit" id="opSubmitSetting" class="button button-primary js-running-btn-disabled"><?php _e('Save your changes', 'bulk-image-resizer'); ?></div>
            </div>
        </div> 
    </div>
    <div id="delete_image_yes_msg" class="op-alert-warning" style="margin:1rem 0; display:none">
    <?php _e("The original images will be resized and overwritten, so it's always best to make a backup before starting the script!", 'bulk-image-resizer'); ?><br>
     <?php _e("If you don't have space problems on your server, you can first try to keep the original images and possibly delete them later."); ?>
    </div>
    <div id="delete_image_no_msg" class="op-alert-info" style="margin:1rem 0; display:none">
    <?php _e("You are not deleting the original images. If you don't have space problems on the server, this is the best choice!"); ?>
    </div>
    <div id="setup_message" style="width:100%"></div>
</form>