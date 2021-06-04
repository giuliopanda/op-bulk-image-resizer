<?php

/**
 * Tutti i blocchi grafici. 
 * 
 * @since      0.9.0
 *
 * @package    op-bulk-image-resizer
 * @subpackage op-bulk-image-resizer/admin
 */

if (!defined('WPINC')) die;

function op_view_container()
{
?>
    <div class="wrap">
        <div id="opContainer" class="op-container">
            <h1 class="wp-heading-inline"><?php _e('BULK IMAGE RESIZE', 'op-bir'); ?></h1>
            <div class="op-grid-2-col op-first-grid">
                <div class="op-block">
                    <?php op_block_setup(); ?>
                </div>
                <div class="op-block">
                    <div class="op-form-title "><?php _e('HDD SPACE OCCUPIED', 'op-bir'); ?></div>
                    <div class="spinner js-op-spin-preload" style="visibility:inherit;float:initial"></div>
                    <div class="op-info-box js-op-spin-loaded" style="display:none">
                        <ul>
                            <li><?php _e('Space occupied by images:', 'op-bir'); ?> <span id="statDiskSpaceImg">-</span></li>
                            <li><?php _e('Saved images:', 'op-bir'); ?> <span id="staIimages">-</span></li>
                            <div class="op-chart-pie">
                                <canvas id="chart_space_disk"></canvas>
                            </div>
                        </ul>
                    </div>
                </div>
                <div class="op-block">
                    <?php op_block_bulk(); ?>
                </div>
                <div class="op-block">
                    <div class="op-form-title"><?php _e('DISTRIBUTION OF IMAGES', 'op-bir'); ?></div>
                    <div class="spinner js-op-spin-preload" style="visibility:inherit;float:initial"></div>
                    <div class="grid-second-row-chart js-op-spin-loaded"  style="display:none">
                        <div class="op-chart-pie">
                            <canvas id="chart_dim"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
<?php
}

/**
 * Il blocco delle impostazioni
 */
function op_block_setup()
{
?>
    <div class="op-form-title"><?php _e('SETTINGS', 'op-bir'); ?></div>
    <div class="op-grid-2-col" id="opSettingsBlock">
        <div class="op-form-row">
            <div class="op-form-label"><?php _e('Dimensions', 'op-bir'); ?></div>
            <div class="op-form-field">
                <?php html_select_dimension(get_option('op_resize_max_width', 1920) . "x" . get_option('op_resize_max_height', 1080)); ?>
            </div>
        </div>
        <div class="op-form-row">
            <div class="op-form-label-big"><?php _e('Compression Quality', 'op-bir'); ?></div>
            <div class="op-form-field-small">
                <?php html_select_quality(get_option('op_resize_quality', 75)); ?>
            </div>
        </div>
        <div class="op-form-row js-custom-show">
            <div class="op-form-label-big"><?php _e('Max width', 'op-bir'); ?></div>
            <div class="op-form-field-small">
                <input class="js-running-input-disable" name="" type="number" id="resizeMaxWidth" value="<?php echo get_option('op_resize_max_width', 1920); ?>">
            </div>
        </div>
        <div class="op-form-row js-custom-show">
            <div class="op-form-label-big"><?php _e('Max height', 'op-bir'); ?></div>
            <div class="op-form-field-small">
                <input class="js-running-input-disable" name="" type="number" id="resizeMaxHeight" value="<?php echo get_option('op_resize_max_height', 1080); ?>">
            </div>
        </div>
        <div class="op-form-row">
            <div class="op-form-label-big"><?php _e('Resize when images are loaded', 'op-bir'); ?></div>
            <div class="op-form-field-small">
                <div style="float:right" class="switch-content">

                    <label class="switch js-running-switch-disable" style="margin-right:1rem;">
                        <input type="checkbox" id="resizeOnUpload" value="1" class="js-running-input-disable" <?php echo (get_option('op_resize_on_upload') == 1) ? 'checked="checked"' : ""; ?>>
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
        </div>
        <div class="op-form-row">
            <div class="op-form-label"></div>
            <div class="op-form-field">
                <div name="submit" id="opSubmitSetting" class="button button-primary js-running-btn-disabled"><?php _e('Save your changes', 'op-bir'); ?></div>
            </div>
        </div>
    </div>
<?php
}



/**
 * La grafica del bulk
 */
function op_block_bulk()
{
    $total_images = get_total_img();
    $done = get_option('op_resize_images_done', 0);
?>
    <div class="op-vertical-block">
        <div class="op-vert-item">
            <div class="op-form-title"><?php _e('BULK IMAGE RESIZER', 'op-bir'); ?></div>
            <div class="op-alert">
                <p>
                    <?php _e('<b> Warning: </b> The original images will be resized and overwritten, so it is always best to backup before running the script.', 'op-bir'); ?>
                </p>
            </div>
            <div id="btnResizeProcessing" class="button button-primary button-hero js-running-hide js-running-hide-pause">
                <?php _e('Start the resize', 'op-bir'); ?>
            </div>

            <div id="btnResizeProcessing2" class="button button-primary button-hero js-running-show-pause"><?php _e('Resume', 'op-bir'); ?></div>
            <div id="btnResizeStop" class="button op-button-cancel button-hero js-running-show-inline"><?php _e('Pause', 'op-bir'); ?></div>

            <div class="op-bar-container js-running-show">
                <div id="OpBar" class="op-bar" style="width:0%">
                    <div id="OpBarInfo" class="op-bar-info">0%</div>
                </div>
            </div>
            <h4><?php _e('Notifications', 'op-bir'); ?></h4>
            <div class="op-info-box" id="opInfoBox">
                <div class="op-alert-info">
                    <?php _e('Remember that you can optimize a group of images from media> library list view. Just select the images and resize them from group actions > Resize (widthXheight)', 'op-bir'); ?>
                </div>
                <?php if ($done > $total_images / 100 && $done * 1.2 < $total_images) : ?>
                    <div class="op-alert-warning ">
                        <?php printf(__("We found %s images to update out of %s total", "op-bir"),  ($total_images - $done), $total_images); ?> 
                        <span id="btnResizeRest" class="button button-primary" data-start="<?php echo $done; ?>"><?php _e("Resume the update", 'op-bir'); ?></span>
                    </div>
                <?php endif; ?>
               
            </div>
        </div>
    </div>
    <script>
        var admin_ajax = '<?php echo admin_url('admin-ajax.php'); ?>';
        var total_images = <?php echo $total_images; ?>;
    </script>
<?php

}


/**
 * Il popup visualizzato nella pagina con l'elenco dei plugin quando viene attivato chiamato da op-activation 
 */
function op_popup_activation_plugin()
{
?>
    <div class="wrap">
        <div class="notice notice-info is-dismissible">
            <div class="op-row-notice">
                <div class="op-row-notice-half">
                    <h4 class="op-notice-half"><?php _e('Thank you for downloading Bulk image resize', 'op-bir'); ?></h4>
                </div>
                <div class="op-info-box">
                    <p><?php _e("The plugin is opensource and without additional paid plans. Using this plugin you have three features:", 'op-bir'); ?>
                    </p>
                    <ul class="ul-disc">
                        <li><?php _e("<b>Inside Tools > Bulk image resizer </b> set the settings and you can start the bulk to resize all images", 'op-bir'); ?></li>
                        <li><?php _e("Among the settings you can decide to allow the plugin to resize images when they are loaded.", 'op-bir'); ?></li>
                        <li><?php _e("Finally On <b> Media Library in list view </b> you have a new column that shows you the image data and the possibility to resize for groups of selected images.", 'op-bir'); ?></li>
                    </ul>
                    <br>
                    <a href="<?php echo admin_url('admin.php?page=op-bulk-image-resizer'); ?>" class="button button-primary"><?php _e('Go to the plugin', 'op-bir'); ?></a>
                    <br><br>
                </div>
            </div>
        </div>
    </div>
<?php
}
