<?php
/**
 * Il template della pagina amministrativa
 * Lo spazio dei grafici è impostato qui, e poi verrà disegnato in javascript
 * l'html del setup e del resize bulk invece è caricato sui due html a parte
 * 
 * @since      1.1.0
 *
 * @package    op-bulk-image-resizer
 * @subpackage op-bulk-image-resizer/admin
 */
if (!defined('WPINC')) die;
?>
<div class="wrap">
    <div id="opContainer" class="op-container">
        <h1 class="wp-heading-inline"><?php _e('BULK IMAGE RESIZE', 'op-bulk-image-resizer'); ?></h1>
        <div class="op-grid-2-col op-first-grid">
            <div class="op-block">
                <?php require(dirname(__FILE__)."/bulk-image-resizer-block-setup.php"); ?>
            </div>
            <div class="op-block">
                <div class="op-form-title "><?php _e('HDD SPACE OCCUPIED', 'op-bulk-image-resizer'); ?></div>
                <div class="spinner js-op-spin-preload" style="visibility:inherit;float:initial"></div>
                <div class="op-info-box js-op-spin-loaded" style="display:none">
                    <ul>
                        <li><?php _e('Space occupied by images:', 'op-bulk-image-resizer'); ?> <span id="statDiskSpaceImg">-</span></li>
                        <li><?php _e('Saved images:', 'op-bulk-image-resizer'); ?> <span id="staIimages">-</span></li>
                        <div class="op-chart-pie">
                            <canvas id="chart_space_disk"></canvas>
                        </div>
                    </ul>
                </div>
            </div>
            <div class="op-block">
                <?php require(dirname(__FILE__)."/bulk-image-resizer-block-bulk.php"); ?>
            </div>
            <div class="op-block">
                <div class="op-form-title"><?php _e('DISTRIBUTION OF IMAGES', 'op-bulk-image-resizer'); ?></div>
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