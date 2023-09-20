<?php namespace bulk_image_resizer; ?>
<h3>BULK</h3>
<?php 
$info = Bir_list_functions::status();

if ($info['status'] == 'RUNNING' && $info['action'] == 'resize') {
    ?>
    <div class="bir-config-warning" id="opConfigWarning" >
    <?php _e('I found an unfinished bulk optimization, would you like to complete it?', 'bulk-image-resizer'); ?>
        
        <div  id="opRunBulk" class="button button-primary js-running-btn-disable" onClick="next_bulk()"><?php _e('Resume', 'bulk-image-resizer'); ?></div>
    </div>
    <?php
}
if ($info['status'] == 'RUNNING' && $info['action'] != 'resize') {
    $info['percent'] = 0;
}
?>

<div class="bir-config-info">
<?php _e('Perform image optimization. To change the settings click on the "configuration" tab.', 'bulk-image-resizer'); ?>
</div>

<div id="bulkSuccessAlert" class="bir-config-success" style="display:none">
<span class="dashicons dashicons-yes-alt"></span> <span id="bulkSuccessAlertMsg"><?php _e('The optimization process is completed', 'bulk-image-resizer'); ?></span>
</div>

    
<div class="op-form-field">
    <div  id="opRunBulk" class="button button-primary js-running-btn-disable" onClick="startBulk()"><?php _e('Process Images', 'bulk-image-resizer'); ?></div>
</div>

<div class="bir-progress-box">
    <div class="bir-progress">
        <div class="bir-progress-bar bir-progress-disabled" id="progress_bar" role="progressbar" style="width: <?php echo $info['percent']; ?>%;" aria-valuenow="<?php echo $info['percent']; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $info['percent']; ?>%</div>
    </div>
</div>

<div id="birBulkInfo"></div>
<div id="birBulkLog" class="bir-box-log"></div>
<br>
<div class="bir-config-info">
<?php _e('By default the original image that was uploaded is retained. Clicking on the Restore button can then restore the original images and their names.  If you have space problems you can instead decide to remove the original images. This operation is irreversible! '); ?>
</div>
<div class="op-form-field">
    <div  id="opRunBulk" class="button button-danger js-running-btn-disable" onClick="startRestore()"><?php _e('Restore', 'bulk-image-resizer'); ?></div>
    <div  id="opRunBulk" class="button button-danger js-running-btn-disable" onClick="startRemoveOriginal()"><?php _e('Delete Original Images', 'bulk-image-resizer'); ?></div>
</div>
