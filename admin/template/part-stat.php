<?php
// gestisco le statistiche
namespace bulk_image_resizer;
?>
<div class="stat-info-row-2-cols">
    <div class="stat-col">
        <h3>Optimization total</h3>
        <div class="stat-info-box" id="stat_box_filesize_info"></div>
        <div id="stat_box_filesize"></div>
        <div>
            <div style="margin:10px 0">
            <div style="width: 30px; height: 12px; background-color: #ffe0e6; display: inline-block; border: 2px solid #ff6384; vertical-align: sub;">&nbsp;</div> <b>Non-optimized images</b>: The size of the images if the plugin had not been used
            </div>
            <div style="margin:10px 0">
            <div style="width: 30px;height: 12px;background-color: #86c7f3; display:inline-block;border: 2px solid #36a2eb;vertical-align: sub;">&nbsp;</div> <b>Optimized images</b>: The size of the images on your site after they have been optimized by the plugin
            </div>
        </div>
    </div>
    <div>
        <h3>Optimization total history</h3>
        <div class="stat-info-box" id="stat_box_filesize_history_info"></div>
        <div id="stat_box_filesize_history">
            <canvas id="filesize_history_info_chart"></canvas>
        </div>
    </div>
</div>