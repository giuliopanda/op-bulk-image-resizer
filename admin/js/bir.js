var save_config_timeout = null;
var bir_pause = false;
jQuery(document).ready(function () {
    document.querySelectorAll('#bir_tabs .bir-tab').forEach((tab) => {
        tab.addEventListener('click', (e) => {
            //if (get_plugin_status() != '') {
            //    alert('Please wait, the plugin is busy');
            //    return false;
            //}
            e.preventDefault();
            document.querySelector('#bir_tabs .bir-tab.bir-tab-active').classList.remove('bir-tab-active');
            tab.classList.add('bir-tab-active');
            const idContent = tab.dataset.idcontent;
            document.querySelectorAll('#bir_tab_contents .bir-tab-content').forEach((content) => {
               content.classList.remove('bir-tab-content-active');
            });
            document.getElementById(idContent).classList.add('bir-tab-content-active');
            //console.log ("idContent "+idContent);
            if (idContent == 'bir_cont_stat') {
                load_stat();
            }
           
        });
    });
    load_stat();
});

function get_tab_active() {
   return document.querySelector('#bir_tabs .bir-tab.bir-tab-active').dataset.idcontent;
}


/**
* ROW CONFIG
*/
jQuery(document).ready(function () {
    jQuery('.js-config-active-row-checkbox').change(function () {
        activ_row_checkbox(this);
    });
    // inizializzo tutti
    jQuery('.js-config-active-row-checkbox').each(function () {
        activ_row_checkbox(this);
    });

});


function activ_row_checkbox(el) {
    $box = jQuery(el).parents('.js-config-box');
    if (jQuery(el).is(':checked')) {
        $box.addClass('bir-active-opt');
    } else {
        $box.removeClass('show-advanced-opt');
        $box.removeClass('bir-active-opt');
       
    }
}

/**
 * END ROW CONFIG
 */


/**
 * Config optimize form
 */
jQuery(document).ready(function () {
    jQuery('#selectPresetDimension').change(function () {
        selectPresetDimension();
        jQuery('#resizeMaxWidth').change();
    });
    selectPresetDimension();
});

function selectPresetDimension() {
    const el = jQuery('#selectPresetDimension');
    if (jQuery(el).val() == '') {
        jQuery('.js-custom-dimension').show();
    } else {
        jQuery('.js-custom-dimension').hide();
        const xy = jQuery(el).val().split('x');
        jQuery('#resizeMaxWidth').val(xy[0]);
        jQuery('#resizeMaxHeight').val(xy[1]);
    }
}

/**
 * END Config optimize form
 */

/**
 * Config Rename form
 */

jQuery(document).ready(function () {
    jQuery('#selectSettingRename').change(function () {
        selectChangeRename();
    });
    setRenameConfig();
});

function selectChangeRename() {
    const el = jQuery('#selectSettingRename');
    if (jQuery(el).val() == '') {
        jQuery('.js-custom-rename').show();
    } else {
        jQuery('.js-custom-rename').hide();
        jQuery('#birRealRename').val(jQuery(el).val());
    }
}

function setRenameConfig() {
    const select = jQuery('#selectSettingRename');
    const real = jQuery('#birRealRename');
    // se real è un valore di select
    if (real.val() != '' && jQuery(select).find('option[value="'+jQuery(real).val()+'"]').length > 0) {
        jQuery(select).val(jQuery(real).val());
        jQuery('.js-custom-rename').hide();
    } else {
        jQuery(select).val('');
        jQuery('.js-custom-rename').show();
    }
}



/**
 * END Config Rename form
 */

/**
 * Salvataggio dei dati della configurazione
 */


jQuery(document).ready(function () {
    // al cambio di valore di un qualunque input, select o checkbox dentro il form #opBulkImageResizerSetup
    jQuery('#opBulkImageResizerSetup input, #opBulkImageResizerSetup select, #opBulkImageResizerSetup checkbox').change(function () {
        clearTimeout(save_config_timeout);
        save_config_timeout = setTimeout( () => { save_config(); }, 2000);
    });
    jQuery('#opBulkImageResizerSetup input').keyup(function () {
        clearTimeout(save_config_timeout);
        save_config_timeout = setTimeout( () => { save_config(); }, 2000);
    });
});

info_min_500 = false;
function save_config() {
    if (get_plugin_status() != '') {
        clearTimeout(save_config_timeout);
        jQuery('#birInfo').html('Not saved '+ (new Date()).toLocaleTimeString());
        save_config_timeout = setTimeout( () => { save_config(); }, 2000);
        set_plugin_status('');
        return false;
    }
   
    let max_width = parseInt(jQuery('#resizeMaxWidth').val());
    let max_height = parseInt(jQuery('#resizeMaxHeight').val());
    conf_dim_yes = true;
    // find checkbox name =resize_active
   
    if ((max_height < 500 || max_width < 500) &&  jQuery('#opBulkImageResizerSetup input[name="resize_active"]').is(':checked') && !info_min_500) {
        alert('Are you sure you want to resize images with a size smaller than 500px?');
        info_min_500 = true;
    }
    
    let data = jQuery('#opBulkImageResizerSetup').serialize();
    // after serialize
    set_plugin_status('setting-submit');
    jQuery('#birInfo').html('Saving...');
    
    // Salvo i dati
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: data
    }).done(function (ris) {
        console.log ("CONFIG SAVED");
        set_plugin_status('');
        jQuery('#birInfo').html('Saved');
        setTimeout( () => { 
            if (jQuery('#birInfo').html() == 'Saved') jQuery('#birInfo').html(''); 
        }, 4000);
        jQuery(document).trigger('bir_config_saved');
    })
}

/**
 * END Salvataggio dei dati della configurazione
 */

/**
 * BULK
 */

/**
 * Eseguo il bulk dal bottone Execute Bulk
 */
function birExecuteBtn() {
    if (get_plugin_status() != '') return false;
    console.log ("birExecuteBtn get_tab_active() "+get_tab_active());
    if (get_tab_active() == 'bir_cont_config') {
        //  jQuery(document).trigger('bir_config_saved');
        jQuery(document).on('bir_config_saved', function (e) {
            console.log ("birExecuteBtn START");
            jQuery('#birTabBulk').click();
            jQuery(document).off('bir_config_saved');
            startBulk();
        });
        save_config();
    } else {
        jQuery('#birTabBulk').click();
        startBulk();
    }
}

function birPauseBtn() {
    bir_pause = true;
    set_plugin_status('bulk-pause');
    
}

function birResumetBtn() {
    bir_pause = false;
    set_plugin_status('bulk-running');
    if (get_tab_active() == 'bir_cont_config') {
        //  jQuery(document).trigger('bir_config_saved');
        jQuery(document).on('bir_config_saved', function (e) {
            jQuery('#birTabBulk').click();
            jQuery(document).off('bir_config_saved');
            next_bulk();
        });
        save_config();
    } else {
        jQuery('#birTabBulk').click();
        next_bulk();
    }
}

function birStopBtn() {
    bir_pause = false;
    set_plugin_status('bulk-stop');
    if (get_tab_active() == 'bir_cont_config') {
        //  jQuery(document).trigger('bir_config_saved');
        jQuery(document).on('bir_config_saved', function (e) {
            jQuery('#birTabBulk').click();
            jQuery(document).off('bir_config_saved');
        });
        save_config();
    } else {
        jQuery('#birTabBulk').click();
    }
    jQuery('#opConfigWarning').hide();
    data = {action:"bir_stop"};
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: data
    }).done(function (ris) {
        set_plugin_status('');
        jQuery('#birInfo').html('Stopped');
        showSuccessAlert('The process was interrupted');
        setTimeout( () => { 
            if (jQuery('#birInfo').html() == 'Stopped') jQuery('#birInfo').html(''); 
        }, 4000);
    })
}


function startBulk() {
    if (get_plugin_status() != '') {
        alert('Please wait, the plugin is busy');
        return false;
    }
    set_plugin_status('bulk-running');
    // sto eseugendo il bulk di tutte le immagini e lo scrivo su birinfo
    jQuery('#birInfo').html('Bulk running...');
    clear_info();
    jQuery('#opConfigWarning').hide();
    data = {action:"bir_start_bulk"};
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: data
    }).done(function (ris) {
        next_bulk();
        print_info(ris);
    })
}
function next_bulk() {
    data = {action:"bir_next_bulk"};
    set_plugin_status('bulk-running');
    jQuery('#opConfigWarning').hide();
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: data
    }).done(function (ris) {
        print_info(ris);
        if (ris['done'] < ris['total']) {
            if (!bir_pause) {
                next_bulk();
            }
        } else {
            load_last_bulk_stat();
        }
        
    }).error(function (ris) {
        alert ("Maybe you have some plugin that is interfering, because I didn't get a valid json back. Try disabling the debug mode of the site or press ok to try to continue." );
        if (!bir_pause) {
            next_bulk();
        }
    });

}



function load_last_bulk_stat() {
    data = {action:"bir_get_stat"};
    // quando carico le statistiche fa anche l'update.
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: data
    }).done(function (jsonData) {
        set_plugin_status('');
        showSuccessAlert('The optimization process is completed');
        jQuery('#configMsgStat').html('').removeClass('bir-config-warning');
        // verifico se ris è un oggetto
        if (typeof jsonData == 'object') {
            //console.log (jsonData);
            var risparmio_perc = (jsonData.file_size['total_file_size_original'] - jsonData.file_size['total_file_size']) / jsonData.file_size['total_file_size_original'] * 100;
            showSuccessAlert('The optimization process is completed. <b>You have saved '+ risparmio_perc.toFixed(2)+ ' %</b>');
        }
    });
}


var bir_status = '';
/**
 * 
 * @param {*} status bulk-running | setting-submit | bulk-restore
 */
function set_plugin_status(status) {
    if (bir_status == status) return;
    bir_status = status;
    jQuery('#bulkSuccessAlert').hide();
    if (status == 'bulk-running') {
        jQuery('#birInfo').html('Bulk running...');
        jQuery('#progress_bar').removeClass('bir-progress-disabled');
        jQuery('#statusof_bulk_image_container').addClass('js-state-running-bulk');
        jQuery('#btnPause').css('display', 'inline-block');
        jQuery('#btnPause').removeClass('bir-progress-disabled');
    } else {
        jQuery('#statusof_bulk_image_container').removeClass('js-state-running-bulk');
        jQuery('#btnPause').css('display', 'none');
    }
    if (status == 'bulk-pause') {
        jQuery('#birInfo').html('Bulk pause...');
        jQuery('#progress_bar').addClass('bir-progress-disabled');
        jQuery('#btnPause').css('display', 'inline-block');
        jQuery('#btnPause').addClass('bir-btn-disabled');
        jQuery('#btnResume').css('display', 'inline-block');
        jQuery('#btnStop').css('display', 'inline-block');
        
    } else {
        jQuery('#btnPause').removeClass('bir-btn-disabled');
        jQuery('#btnResume').css('display', 'none');
        jQuery('#btnStop').css('display', 'none');
    }

    if (status == 'bulk-restore') {
        jQuery('#progress_bar').removeClass('bir-progress-disabled');
        jQuery('#birInfo').html('images restore...');
        jQuery('#statusof_bulk_image_container').addClass('js-state-restore-bulk');
    } else {
        jQuery('#statusof_bulk_image_container').removeClass('js-state-restore-bulk');
    }
    if (status == 'bulk-delete-original') {
        jQuery('#progress_bar').removeClass('bir-progress-disabled');
        jQuery('#birInfo').html('I am removing the original images...');
        jQuery('#statusof_bulk_image_container').addClass('js-state-delete-original-bulk');
    } else {
        jQuery('#statusof_bulk_image_container').removeClass('js-state-delete-original-bulk');
    }

    if (status == 'setting-submit') {
        jQuery('#statusof_bulk_image_container').addClass('js-state-submit');
    } else {
        jQuery('#statusof_bulk_image_container').removeClass('js-state-submit');
    }

    if (status != '') {
        jQuery('.js-running-input-disable').prop('disabled', true);
        jQuery('.js-running-btn-disable').addClass('bir-btn-disabled');
     } else {
        jQuery('.js-running-input-disable').prop('disabled', false);
        jQuery('.js-running-btn-disable').removeClass('bir-btn-disabled');
    }
}

function get_plugin_status() {
    return bir_status;
}


function clear_info() {
    jQuery('#birBulkInfo').html('starting...');
    jQuery('#birBulkLog').html('');
}

function print_info(info) {
    if (info['status'] == 'NOT_STARTED') {
        jQuery('#birBulkInfo').html('');
        jQuery('#birBulkInfo').html(info['done']+"/"+info['total']+" "+info['percent']+"%");
    } else {
        jQuery('#birBulkInfo').html(info['done']+"/"+info['total']+" "+info['percent']+"% " + info['status']);
    }
    jQuery('#progress_bar').css('width', info['percent'] + '%');
    jQuery('#progress_bar').attr("aria-valuenow", info['percent'] + '%');
    jQuery('#progress_bar').text(info['percent'] + '%');
    if (info['logs'] != undefined && info['logs'].length > 0) {
        for (let i = 0; i < info['logs'].length; i++) {
            jQuery('#birBulkLog').append(info['logs'][i]+"<br>");
        }
    }
}


function showSuccessAlert(msg) {
    jQuery('#birInfo').html(msg);
    setTimeout( () => { 
        if (get_plugin_status() == '') {
            jQuery('#birInfo').html('');
        }
     }, 5000);
    jQuery('#bulkSuccessAlert').show();
    jQuery('#bulkSuccessAlertMsg').empty().html(msg);
    jQuery('#opRunBulk').hide();
}

/**
 * END BULK
 */

/**
 * RESTORE ALL IMAGES
 */
function startRestore() {
    if (get_plugin_status() != '') {
        alert('Please wait, the plugin is busy');
        return false;
    }
    if (confirm('All optimizations will be undone. Are you sure you want to restore all images to their original state? The process can take quite some time.') == false) return false;
   
    set_plugin_status('bulk-restore');
    clear_info();
    data = {action:"bir_start_restore"};
    jQuery('#opConfigWarning').hide();
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: data
    }).done(function (ris) {
        next_restore();
        print_info(ris);
    }).error(function (ris) {
        alert ("Maybe you have some plugin that is interfering, because I didn't get a valid json back. Try disabling the debug mode of the site or press ok to try to continue." );
        next_restore();
    });
}

function next_restore() {
    data = {action:"bir_next_restore"};
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: data
    }).done(function (ris) {
        // verifico se ris è un oggetto
        if (typeof ris != 'object') {
            alert ("There was an error of your!" );
            next_restore();
        } else {
            print_info(ris);
            if (ris['done'] < ris['total']) {
                next_restore();
            } else {
                set_plugin_status('');
                showSuccessAlert('The restore process is completed');
            }
        }
    }).error(function (ris) {
        alert ("Maybe you have some plugin that is interfering, because I didn't get a valid json back. Try disabling the debug mode of the site or press ok to try to continue." );
        next_restore();
    });
}

/**
 * END RESTORE ALL IMAGES
 */


/**
 * REMOVE ORIGINAL IMAGES
 */
function startRemoveOriginal() {
    if (get_plugin_status() != '') {
        alert('Please wait, the plugin is busy');
        return false;
    }
    if (confirm('Removing the original images will gain server space and the site will continue to function normally, but you will no longer be able to restore the optimized images.') == false) return false;
   
    set_plugin_status('bulk-delete-original');
    clear_info();
    data = {action:"bir_start_delete_orginal"};
    jQuery('#opConfigWarning').hide();
    
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: data
    }).done(function (ris) {
        next_removeOriginal();
        print_info(ris);
    }).error(function (ris) {
        alert ("Maybe you have some plugin that is interfering, because I didn't get a valid json back. Try disabling the debug mode of the site or press ok to try to continue." );
        next_removeOriginal();
    });
}


function next_removeOriginal() {
    data = {action:"bir_next_delete_orginal"};
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: data
    }).done(function (ris) {
        // verifico se ris è un oggetto
        if (typeof ris != 'object') {
            alert ("There was an error of your!" );
            next_removeOriginal();
        } else {
            print_info(ris);
            if (ris['done'] < ris['total']) {
                next_removeOriginal();
            } else {
                set_plugin_status('');
                showSuccessAlert('All original images have been removed');
            }
        }
    }).error(function (ris) {
        alert ("Maybe you have some plugin that is interfering, because I didn't get a valid json back. Try disabling the debug mode of the site or press ok to try to continue." );
        next_removeOriginal();
    });
}

/**
 * END REMOVE ORIGINAL IMAGES
 */


/**
 * STAT
 */

function update_stat() {
    data = {action:"bir_get_stat"};
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: data
    }).done(function (jsonData) {
    });
}


function load_stat() {
    
    jQuery('#birStatInfo').html('loading stat...');
    data = {action:"bir_get_stat"};
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: data
    }).done(function (jsonData) {
        // verifico se ris è un oggetto
        if (typeof jsonData != 'object') {
            alert ("There was an error of your!" );
        } else {
            console.log (jsonData);
            if (jsonData.file_size['msg'] != '') {
                jQuery('#stat_box_filesize_info').html(jsonData.file_size['msg']).css('display', 'block');
            }
            delete jsonData.file_size['msg'];
            drawFileSizeTable(jsonData) ;
            chart_history_filesize(jsonData.history.labels, jsonData.history.dataset_1, jsonData.history.dataset_2);
            jQuery('#configMsgStat').html('').removeClass('bir-config-warning');
            if (jsonData.file_numbers.total_files  > jsonData.file_numbers.total_files_original *1.2 ) {
                var img_to_optimize = jsonData.file_numbers.total_files - jsonData.file_numbers.total_files_original;
                // Ci sono più di xxx immagini da ottimizzare!
                jQuery('#configMsgStat').html('There are <b>'+img_to_optimize+'</b> images to optimize!').css('display', 'block');
                // jQuery('#configMsgStat').html('Le immagini del sito necessitano di essere ottimizzate!').css('display', 'block');
                jQuery('#configMsgStat').addClass('bir-config-warning');
            } else {

            }
        }
    }).error(function (jsonData) {
        //alert ("Maybe you have some plugin that is interfering, because I didn't get a valid json back. Try disabling the debug mode of the site or press ok to try to continue." );
    });
}

function drawFileSizeTable(jsonData) {
    // Ottieni il riferimento al div in cui disegnare la tabella
    var tableDiv = jQuery("#stat_box_filesize");
  
    // Crea la tabella
    var table = jQuery('<table class="bir-table">');

    // Aggiungi una riga per ogni titolo e valore
    for (var key in jsonData.file_size) {
      var row = jQuery("<tr>");
      var titleCell = jQuery("<td>").text(getTitle(key));
      var valueCell = jQuery("<td>").text(formatSize(jsonData.file_size[key]));
      row.append(titleCell, valueCell);
      table.append(row);
    }

    var row = jQuery("<tr>");
    var titleCell = jQuery("<td>").text("You saved");
    var risparmio_perc = (jsonData.file_size['total_file_size_original'] - jsonData.file_size['total_file_size']) / jsonData.file_size['total_file_size_original'] * 100;
   
    // formatto il numero
    var risparmio_perc = risparmio_perc.toFixed(2);
    var valueCell = jQuery("<td>").text( risparmio_perc+"%  of the space");
    row.append(titleCell, valueCell);
    table.append(row);
    // Aggiungi la tabella al div
    tableDiv.html(table);
  }
  
  // Funzione per ottenere il titolo in italiano
  function getTitle(key) {
    switch (key) {
      case "total_file_size":
        return "Total file size after being optimized";
      case "total_file_size_original":
        return "Total size of original files before optimization";
      default:
        return key;
    }
  }
  
  // Funzione per formattare la dimensione in KB, MB, GB, ecc.
  function formatSize(size) {
    var units = ["B", "KB", "MB", "GB", "TB"];
    var unitIndex = 0;
    while (size >= 1024 && unitIndex < units.length - 1) {
      size /= 1024;
      unitIndex++;
    }
    return size.toFixed(2) + " " + units[unitIndex];
  }

  /**
   * END STAT
   */


  /**
   * Chart1
   */

var  filesize_history_info_chart =  null;  
function chart_history_filesize(labels, dataset_1, dataset_2) {
  const ctx = document.getElementById('filesize_history_info_chart');

  const data = {
    labels: labels,
    datasets: [
        {
            label: 'optimized images',
            data: dataset_2,
            fill: true,
            borderColor: 'rgb(54, 162, 235)',
            backgroundColor: 'rgba(54, 162, 235, 0.6)'
        },
        {
        label: 'Non-optimized images',
        data: dataset_1,
        fill: true,
        borderColor: 'rgb(255, 99, 132)',
        backgroundColor: 'rgba(255, 99, 132, 0.2)'
        }

    ]
  };
  if (filesize_history_info_chart != null) filesize_history_info_chart.destroy();
  filesize_history_info_chart = new Chart(ctx, {
    type: 'line',
    data: data,
    options: {
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
                callback: function(value, index, values) {
                  return value + ' MB'; // Aggiunge il suffisso "MB" ai valori dell'asse y
                }
              }
          }
        }
      }
  });
}