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
    <div id="opContainer" class="op-container">
        <h1><?php _e('BULK IMAGE RESIZER', 'bulk-image-resizer'); ?></h1>
        <?php if (!$check_fn_editor) : ?>
            <div class="op-alert-danger"><?php _e("Attention it is not possible to start the instance of WP_Image_Editor. This plugin cannot work until the problem is fixed. Even if you don't want to use this plugin, this kind of error can create problems.<br> Often to solve this problem just activate one of the two graphic modules from php.ini imagick or GD. <br>To do this you can try to go to php.ini and delete the; from the line; extension = gd. <br> Once done, save the file and restart the server.", 'bulk-image-resizer'); ?></div>
        <?php else : ?>
            <div class="op-grid-2-col op-first-grid">
                <div class="op-block op-block-order-1">
                    <?php require(dirname(__FILE__)."/bulk-image-resizer-block-setup.php"); ?>
                </div>
                <div class="op-block op-block-order-3">
                    <div class="op-form-title "><?php _e('HDD SPACE OCCUPIED', 'bulk-image-resizer'); ?></div>
                    <div class="spinner js-op-spin-preload" style="visibility:inherit;float:initial"></div>
                    <div class="op-info-box js-op-spin-loaded" style="display:none">
                        <ul>
                            <li><?php _e('Space occupied by images:', 'bulk-image-resizer'); ?> <span id="statDiskSpaceImg">-</span></li>
                            <li><?php _e('Saved images:', 'bulk-image-resizer'); ?> <span id="staIimages">-</span></li>
                            <div class="op-chart-pie">
                                <canvas id="chart_space_disk"></canvas>
                            </div>
                        </ul>
                    </div>
                </div>
                <div class="op-block op-block-order-2">
                    <?php require(dirname(__FILE__)."/bulk-image-resizer-block-bulk.php"); ?>
                </div>
                <div class="op-block op-block-order-4">
                    <div class="op-form-title"><?php _e('DISTRIBUTION OF IMAGES', 'bulk-image-resizer'); ?></div>
                    <div class="spinner js-op-spin-preload" style="visibility:inherit;float:initial"></div>
                    <div class="grid-second-row-chart js-op-spin-loaded"  style="display:none">
                        <div class="op-chart-pie">
                            <canvas id="chart_dim"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>