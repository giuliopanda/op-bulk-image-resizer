<?php
/**
 * Il popup di benvenuto quando installi il plugin
 * 
 * @since      1.1.0
 *
 * @package    bulk-image-resizer
 * @subpackage bulk-image-resizer/admin
 */
namespace bulk_image_resizer;

if (!defined('WPINC')) die;
?>
<div class="wrap">
    <div class="notice notice-info is-dismissible">
        <div class="op-row-notice">
            <div class="op-row-notice-half">
                <h4 class="op-notice-half"><?php _e('Thank you for downloading Bulk images', 'bulk-image-resizer'); ?></h4>
            </div>
            <div class="op-info-box">
                <p><?php _e("The plugin is opensource and without additional paid plans.", 'bulk-image-resizer'); ?>
                </p>
                <p>Go to the Tools > <a href="<?php echo admin_url('admin.php?page=bulk-images'); ?>">Bulk images</a> menu to get started</p>
            </div>
        </div>
    </div>
</div>