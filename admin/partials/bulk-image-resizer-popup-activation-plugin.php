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
if (!defined('WPINC')) die;
?>
<div class="wrap">
        <div class="notice notice-info is-dismissible">
            <div class="op-row-notice">
                <div class="op-row-notice-half">
                    <h4 class="op-notice-half"><?php _e('Thank you for downloading Bulk image resize', 'bulk-image-resizer'); ?></h4>
                </div>
                <div class="op-info-box">
                    <p><?php _e("The plugin is opensource and without additional paid plans. Using this plugin you have three features:", 'bulk-image-resizer'); ?>
                    </p>
                    <ul class="ul-disc">
                        <li><?php _e("<b>Inside Tools > Bulk image resizer </b> set the settings and you can start the bulk to resize all images", 'bulk-image-resizer'); ?></li>
                        <li><?php _e("Among the settings you can decide to allow the plugin to resize images when they are loaded.", 'bulk-image-resizer'); ?></li>
                        <li><?php _e("Finally On <b> Media Library in list view </b> you have a new column that shows you the image data and the possibility to resize for groups of selected images.", 'bulk-image-resizer'); ?></li>
                    </ul>
                    <br>
                    <a href="<?php echo admin_url('admin.php?page=bulk-image-resizer'); ?>" class="button button-primary"><?php _e('Go to the plugin', 'bulk-image-resizer'); ?></a>
                    <br><br>
                </div>
            </div>
        </div>
    </div>