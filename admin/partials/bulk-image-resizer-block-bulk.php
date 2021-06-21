<?php
/**
 * Tutti i blocchi grafici. 
 * 
 * @since      1.1.0
 *
 * @package    bulk-image-resizer
 * @subpackage bulk-image-resizer/admin
 */
use opBulkImageResizer\Includes\OpFunctions AS Opfn;
if (!defined('WPINC')) die;
$total_images = Opfn\get_total_img();
$done = get_option('op_resize_images_done', 0);
?>
<div class="op-vertical-block">
    <div class="op-vert-item">
        <div class="op-form-title"><?php _e('BULK IMAGE RESIZER', 'bulk-image-resizer'); ?></div>
      
        <div id="btnResizeProcessing" class="button button-primary button-hero js-running-hide js-running-hide-pause">
            <?php _e('Start the resize', 'bulk-image-resizer'); ?>
        </div>

        <div id="btnResizeProcessing2" class="button button-primary button-hero js-running-show-pause"><?php _e('Resume', 'bulk-image-resizer'); ?></div>
        <div id="btnResizeStop" class="button op-button-cancel button-hero js-running-show-inline"><?php _e('Pause', 'bulk-image-resizer'); ?></div>

        <div class="op-bar-container js-running-show">
            <div id="OpBar" class="op-bar" style="width:0%">
                <div id="OpBarInfo" class="op-bar-info">0%</div>
            </div>
        </div>
        <h4><?php _e('Notifications', 'bulk-image-resizer'); ?></h4>
        <div class="op-info-box" id="opInfoBox">
            <div class="op-alert-info">
                <?php _e('Remember that you can optimize a group of images from media> library list view. Just select the images and resize them from group actions > Resize (widthXheight)', 'bulk-image-resizer'); ?>
            </div>
            <?php if ($done > $total_images / 100 && $done * 1.2 < $total_images) : ?>
                <div class="op-alert-warning ">
                    <?php printf(__("We found %s images to update out of %s total", "bulk-image-resizer"),  ($total_images - $done), esc_attr($total_images)); ?> 
                    <span id="btnResizeRest" class="button button-primary" data-start="<?php echo $done; ?>"><?php _e("Resume the update", 'bulk-image-resizer'); ?></span>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>
<script>
    var admin_ajax = '<?php echo admin_url('admin-ajax.php'); ?>';
    var total_images = <?php echo $total_images; ?>;
</script>