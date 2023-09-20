<?php
/**
 * La form di configurazione 
 * @var Bir_options_var $options i parametri stanno dentro 
 */
namespace bulk_image_resizer;
?>
<script>
    var admin_ajax = '<?php echo admin_url('admin-ajax.php'); ?>';
    //var total_images = <?php //echo $total_images; ?>;
</script>
<div id="configMsgStat"></div>
<div class="bir-config-info"><?php _e('Choose how to optimize your images. No need to save. The settings are also applied to newly uploaded images.<br>Don\'t disable the plugin when you are done optimizing images, so the settings will also be applied to newly uploaded images.', 'bulk-image-resizer'); ?></div>
<form id="opBulkImageResizerSetup">
    <input type="hidden" name="action" value="bir_save_configuration">
    <input type="hidden" name="version" value="2.0.0">
    <?php 
    /**
     * RESIZE CONFIGURATION
     */
    ?>
    <div class="bir-config-box js-config-box">
        <div class="bir-config-row">
            <div class="op-form-field-small">
                <div class="switch-content">
                <label class="switch js-running-switch-disable" style="margin-right:1rem;">
                    <input type="checkbox" value="1" name="resize_active" class="js-config-active-row-checkbox js-running-input-disable" <?php echo ($options->resize_active == 1) ? 'checked="checked"' : ""; ?>>
                    <span class="slider round"></span>
                </label>
                <span class="bir-switch-label-active">(<?php _e('active', 'bulk-image-resizer'); ?>)</span>
                <span class="bir-switch-label-noactive">(<?php _e('non active', 'bulk-image-resizer'); ?>)</span>
            </div>

            </div>
            <div class="bir-config-title"><?php _e('Resize', 'bulk-image-resizer'); ?></div>
        
            <div class="op-form-row bir-default-configuration">
                <div class="op-form-label"><?php _e('Dimensions', 'bulk-image-resizer'); ?></div>
                <div class="op-form-field">
                    <?php Bir_config_fn::html_select_dimensions($options->max_width . "x" . $options->max_height); ?>
                </div>
            </div>

        </div>
    

        <div class="bir-config-advanced-row js-config-advanced">
        <div class="bir-advanced-row-info"><?php _e('Crop uploaded images if higher than the selected settings', 'bulk-image-resizer'); ?></div>
            <div class="op-grid-3-col">
                <div class="op-form-row js-custom-dimension">
                    <div class="op-form-label-big"><?php _e('Max width', 'bulk-image-resizer'); ?></div>
                    <div class="op-form-field-small">
                        <input class="js-running-input-disable" name="max_width" type="number" id="resizeMaxWidth" value="<?php echo $options->max_width; ?>">
                    </div>
                </div>
                <div class="op-form-row js-custom-dimension">
                    <div class="op-form-label-big"><?php _e('Max height', 'bulk-image-resizer'); ?></div>
                    <div class="op-form-field-small">
                        <input class="js-running-input-disable" name="max_height" type="number" id="resizeMaxHeight" value="<?php echo $options->max_height; ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php 
    /**
     * WEBP IMAGE
     */
    ?>

    <div class="bir-config-box js-config-box">
        <div class="bir-config-row">
            <div class="op-form-field-small">
                <div class="switch-content">
                <label class="switch js-running-switch-disable" style="margin-right:1rem;">
                    <input type="checkbox" value="1" name="webp_active" class="js-config-active-row-checkbox js-running-input-disable" <?php echo (absint($options->webp_active) == 1) ? 'checked="checked"' : ""; ?>>
                    <span class="slider round"></span>
                </label>
                <span class="bir-switch-label-active">(<?php _e('active', 'bulk-image-resizer'); ?>)</span>
                <span class="bir-switch-label-noactive">(<?php _e('non active', 'bulk-image-resizer'); ?>)</span>
            </div>

            </div>
            <div class="bir-config-title"><?php _e('Convert To WEBP', 'bulk-image-resizer'); ?></div>
            <div class="op-form-row bir-default-configuration"></div>
           
        </div>
        <div class="bir-config-advanced-row js-config-advanced">
            <div class="bir-advanced-row-info"><?php _e('Converts images to WEBP. The WebP format improves image compression at the same quality and allows PNG images to be compressed', 'bulk-image-resizer'); ?></div>
        </div>
    </div>




    <?php 
    /**
     * OPTIMIZE IMAGE
     */
    ?>

    <div class="bir-config-box js-config-box">
        <div class="bir-config-row">
            <div class="op-form-field-small">
                <div class="switch-content">
                <label class="switch js-running-switch-disable" style="margin-right:1rem;">
                    <input type="checkbox" value="1" name="optimize_active" class="js-config-active-row-checkbox js-running-input-disable" <?php echo (absint($options->optimize_active) == 1) ? 'checked="checked"' : ""; ?>>
                    <span class="slider round"></span>
                </label>
                <span class="bir-switch-label-active">(<?php _e('active', 'bulk-image-resizer'); ?>)</span>
                <span class="bir-switch-label-noactive">(<?php _e('non active', 'bulk-image-resizer'); ?>)</span>
            </div>

            </div>
            <div class="bir-config-title"><?php _e('Optimize', 'bulk-image-resizer'); ?></div>
        
            <div class="op-form-row bir-default-configuration">
                <div class="op-form-label"><?php _e('Compression Quality', 'bulk-image-resizer'); ?></div>
                <div class="op-form-field">
                    <?php Bir_config_fn::html_select_quality($options->quality); ?>
                </div>
            </div>

        </div>
        <div class="bir-config-advanced-row js-config-advanced">
            <div class="bir-advanced-row-info"><?php _e('Optimize image compression. Low has lower quality, but lighter images, hight maximum quality, but heavier images.<br><b>If you don\'t have any particular needs, medium is fine.</b>', 'bulk-image-resizer'); ?>
                </div>
        </div>
    </div>

    <?php 
    /**
     * RENAME IMAGE
     */
    ?>

    <div class="bir-config-box js-config-box">
        <div class="bir-config-row">
            <div class="op-form-field-small">
                <div class="switch-content">
                <label class="switch js-running-switch-disable" style="margin-right:1rem;">
                    <input type="checkbox" value="1" name="rename_active" class="js-config-active-row-checkbox js-running-input-disable" <?php echo (absint($options->rename_active) == 1) ? 'checked="checked"' : ""; ?>>
                    <span class="slider round"></span>
                </label>
                <span class="bir-switch-label-active">(<?php _e('active', 'bulk-image-resizer'); ?>)</span>
                <span class="bir-switch-label-noactive">(<?php _e('non active', 'bulk-image-resizer'); ?>)</span>
            </div>

            </div>
            <div class="bir-config-title"><?php _e('Rename File', 'bulk-image-resizer'); ?></div>
        
            <div class="op-form-row bir-default-configuration">
                <div class="op-form-label"><?php _e('New Name', 'bulk-image-resizer'); ?></div>
                <div class="op-form-field">
                    <?php Bir_config_fn::html_select_rename($options->rename); ?>
                </div>
            </div>

            <div class="">
                <label class="adfo-checkbox bir-checkbox-new-imgs" style="margin-left: 1rem;">
                    <input type="checkbox" name="rename_change_title" value="1" <?php echo (absint($options->rename_change_title) == 1) ? 'checked="checked"' : ""; ?>>
                    <div class="adfo-checbox-box-bg"></div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="presentation" class="components-checkbox-control__checked" aria-hidden="true" focusable="false"><path d="M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"></path></svg>
                    <span><?php _e('Nice title', 'bulk-image-resizer'); ?></span>
                </label>
            </div>  
        </div>
        <div class="bir-config-advanced-row js-config-advanced">
            <div class="bir-advanced-row-info"><?php _e('Rename the images. See the documentation for more information', 'bulk-image-resizer'); ?></div>
            <div class="op-grid-3-col" >
                <div class="op-form-row js-custom-rename">
                    <div class="op-form-label-big"><?php _e('Custom', 'bulk-image-resizer'); ?></div>
                    <div class="op-form-field">
                        <input id="birRealRename" class="js-running-input-disable bir-input-long" name="rename" type="text"  value="<?php echo $options->rename; ?>">
                    </div>
                </div>

                <div class="op-form-row js-custom-rename">
                    <b>[uniqid]</b> <?php _e('Generates a unique id', 'bulk-image-resizer'); ?><br>
                    <b>[md5]</b> <?php _e('Is replaced with the md5 of the original name', 'bulk-image-resizer'); ?><br>
                    <b>[id]</b> <?php _e('Is replaced with a sequential number', 'bulk-image-resizer'); ?> <br>
                    <b>[image_name]</b> <?php _e('Is replaced with the original sanitized image name', 'bulk-image-resizer'); ?><br>
                    <b>[rand]</b> <?php _e('Generates a random character a-z0-9', 'bulk-image-resizer'); ?><br>
                  
                   
                </div>
                <div class="op-form-row js-custom-rename">
                    <b>[date]</b> <?php _e('is replaced with the date the image was uploaded', 'bulk-image-resizer'); ?><br>
                    <b>[time]</b> <?php _e('Is replaced with the time the image was uploaded', 'bulk-image-resizer'); ?><br>
                    <b>[timestamp]</b> <?php _e('Is replaced with the upload timestamp of the image', 'bulk-image-resizer'); ?><br>
                
                </div>
                
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

</form>

<div class="bir-config-info" style="font-size:.8rem"><?php _e('Be sure to back up your site before using the plugin!', 'bulk-image-resizer'); ?></div>