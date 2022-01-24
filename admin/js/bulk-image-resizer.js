/**
* RESIZE DI TUTTE LE IMMAGINI
*/
var time_start = 0;
var time_to_ajax = 0;
var real_total = 0;
var chart_img_dim = false;
var chart_img_space_disk = false;
var check_process_count = 0;
var check_processing_timeout = 0;

jQuery(document).ready(function () {
    jQuery('#btnResizeProcessing').click(function () {
        if (start_resize_all()) {
            jQuery('#opContainer').data('resize_step', 0);
            resize_all(0);
        }
    });
    jQuery('#btnResizeProcessing2').click(function () {
        if (start_resize_all()) {
            pointer = jQuery('#opContainer').data('resize_step');
            time_to_ajax = 0;
            resize_all(pointer);
        } 
        
    });
    jQuery('#btnResizeRest').click(function () {
        if (start_resize_all()) {
            let pointer = jQuery(this).data('start');
            time_to_ajax = 0;
            resize_all(pointer);
        } 
        
    });

    jQuery('#btnResizeStop').click(function () {
        jQuery('#opContainer').removeClass('js-state-resize-processing');
        jQuery('#opContainer').addClass('js-state-resize-pause');
        toggle_setting();
        jQuery('#opInfoBox').append('<div class="op-alert-warning">' + t9n_pause + '</div>');
    });
   
    /**
     * SELECT DIMENSIONI PRESET: se viene cambiato e viene scelto custom devono apparire i campi width e height
     */
    jQuery('#selectPresetDimension').change(function() {
        let val = jQuery(this).val();
        if (val == "") {
            // custom è uno stato
            jQuery('#opSettingsBlock').addClass('js-state-setting-custom');
        } else {
            jQuery('#opSettingsBlock').removeClass('js-state-setting-custom');
            let explode = val.split("x");
            jQuery('#resizeMaxWidth').val(explode[0]);
            jQuery('#resizeMaxHeight').val(explode[1]);
        }
        toggle_setting();
    });
    jQuery('#selectPresetDimension').change();

    jQuery('#resizeDeleteOriginal').change(function () {
        check_message_delete_Original();
    });

    
    check_message_delete_Original();
    /**
     * SALVO I DATI QUANDO SI FA SUBMIT
     */ 
    jQuery('#opSubmitSetting').click(function() {  
        if (jQuery('#opContainer').hasClass('js-state-resize-processing')) {
            return false;
        }
        
        let max_width = parseInt(jQuery('#resizeMaxWidth').val());
        conf_width_yes = true;
        if (max_width < 500) {
            conf_width_yes = confirm(t9n_confirm_1);
        }
        let max_height = parseInt(jQuery('#resizeMaxHeight').val());
        conf_height_yes = true;
        if (max_height < 500) {
            conf_height_yes = confirm(t9n_confirm_2);
        }
        if (conf_width_yes && conf_height_yes) {
            let data = jQuery('#opBulkImageResizerSetup').serialize();
            jQuery('#opSettingsBlock').addClass('js-state-submit');
            toggle_setting();
            
            // Salvo i dati
            jQuery.ajax({
                method: "GET",
                url: admin_ajax,
                dataType: "json",
                data: data
            }).done(function (ris) {
                jQuery('#opSettingsBlock').removeClass('js-state-submit');
                toggle_setting();
                if (! ris.updated ) {
                    jQuery('#setup_message').empty().append('<div class="op-alert-warning">' + ris.msg + '</div>');
                } else {
                    jQuery('#setup_message').empty().append('<div class="op-alert-info">' + ris.msg + '</div>');
                }
            });
        } 
    });

    /**
     *  carico le statistiche al completamento della pagina
     */ 
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: { action: "op_calc_stats" }
    }).done(function (ris) {
        jQuery('.js-op-spin-preload').remove();
        jQuery('.js-op-spin-loaded').css('display','block');
        op_chart_config_dimension.data = ris.scatter;

        chart_img_dim = new Chart(
            document.getElementById('chart_dim'),
            op_chart_config_dimension
        );

        op_config_space.data = ris.data_size_graph;
        if (op_config_space.data.datasets[0]) {
            op_config_space.data.datasets[0].backgroundColor = '#3696d7';
            op_config_space.data.datasets[0].borderColor = '#3696d7';
            op_config_space.data.datasets[0].fill = 'rgba(63, 155, 217, .5 )';
            op_config_space.data.datasets[0].fill = { target: 'origin', above: 'rgba(63, 155, 217, .5 )'  };

            chart_img_space_disk = new Chart(
                document.getElementById('chart_space_disk'),
                op_config_space
            );
        }

        jQuery('#statDiskSpaceImg').html(op_file_size(ris.images_size));
        jQuery('#staIimages').html(ris.tot_images);

    });
    
    jQuery('a').click(function () { 
        if (jQuery('#opSettingsBlock').hasClass('js-state-submit') || jQuery('#opContainer').hasClass('js-state-resize-processing')) {
            return confirm(t9n_confirm_3);
        }
        return true;
    })

});


function start_resize_all() {
   
    jQuery('#opInfoBox > div:not(.op-no-delete)').remove();
    jQuery('#opInfoProcessed').empty();
    if (jQuery('#opSettingsBlock').hasClass('js-state-submit')) {
        jQuery('#opInfoBox').append('<div class="op-alert-warning">' + t9n_wait_settings + '</div>');
        return false;
    } else {
        jQuery('#opContainer').addClass('js-state-resize-processing');
        jQuery('#opContainer').removeClass('js-state-resize-pause');
        toggle_setting();
        jQuery('#opInfoUpdate').html(t9n_analisys);
        time_start = Date.now();
        clearTimeout(check_processing_timeout);
        check_processing_timeout = setTimeout(function () { check_resize_processing(); }, 180000);
        jQuery('#opInfoBox').append('<div class="op-alert-info">' + t9n_start_resize + '</div>');
        jQuery('#opInfoBox').append('<div class="op-alert-warning js-running-hide-pause">' + t9n_warning_resize + '</div>');

        return true;
    }
}


/**
 * Il javascript che si occupa di fare le chiamate per ridimensionare le immagini
 */
function resize_all(start) {
    if (!jQuery('#opContainer').hasClass('js-state-resize-processing')) {
        return;
    }
    if (time_to_ajax == 0 && start > 0) {
        real_total = start;
    }
    time_to_ajax++;
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: { start: start, action: "op_resize_all" }
    }).done(function (ris) {
        if (ris.done >= total_images) {
            jQuery('#opInfoUpdate').empty().html(t9n_we_are_almost_there);
            jQuery('#OpBar').css('width', "99%");
            jQuery('#OpBarInfo').html("99%");
            jQuery('#opContainer').data('resize_step', 0);
            // chiude e cancello la option
            jQuery.ajax({
                method: "GET",
                url: admin_ajax,
                dataType: "json",
                data: { action: "op_end_resize_all" }
            })
            .done(function (ris) {
                check_resize_processing();
                jQuery('#OpBar').css('width', "0%");
                jQuery('#OpBarInfo').html( "0%");
                let time = (Date.now() - time_start) / 1000;
               
                update = "";
                if (ris.old_file_size - ris.file_size > 1000) {
                    update = t9n_sprintf(t9n_spared, op_file_size(ris.old_file_size - ris.file_size));
                }
                jQuery('#opInfoBox').html("<h5>" + t9n_end_1 + "</h5><br>" + update + " " + t9n_sprintf(t9n_end_2, op_file_size(ris.file_size)) + "<br>" + t9n_sprintf(t9n_end_3, secondsToHms(time)));
                jQuery('#opContainer').removeClass('js-state-resize-processing');
                jQuery('#opContainer').removeClass('js-state-resize-pause');
                jQuery('#opContainer').addClass('js-state-resize-end');
                toggle_setting();
            }).error(function () {
             
            });
        } else {
            let time = (Date.now() - time_start) / 1000;
            console.log("DONE " + ris.done);
            let perc = Math.floor((ris.done / total_images) * 100);
            let real_perc = Math.round(((ris.done - real_total) / (total_images - real_total)) * 100);
            time = (time / real_perc) * (100 - real_perc);
            console.log("NEW TIME " + time);
            let left_time = "";
            if (time_to_ajax > 2) {
                left_time = t9n_sprintf(t9n_time_remaining, secondsToHms(time));
            } else {
                left_time = t9n_time_analisys;
            }
            jQuery('#OpBar').css('width', perc + "%");
            jQuery('#OpBarInfo').html(perc + "%");
            if (jQuery('#opInfoProcessed').length == 0) {
                jQuery('#opInfoBox').append('<div class="op-alert-info js-running-hide-pause" id="opInfoProcessed"></div>');
            }
            jQuery('#opInfoProcessed').html(t9n_sprintf(t9n_img_left, (total_images - ris.done)));
            if (jQuery('#opInfoUpdate').length == 0) {
                jQuery('#opInfoBox').append('<div class="op-alert-info js-running-hide-pause" id="opInfoUpdate"></div>');
            }          
            jQuery('#opInfoUpdate').html(left_time);
            if (ris.done > 0 && ris.done < total_images) {
                jQuery('#opContainer').data('resize_step', ris.done);
                resize_all(ris.done);
            } else {
                jQuery('#opInfoUpdate').empty().html(t9n_none);
            }
        }

    }).error(function() {
        if (jQuery('#alertDanger').length > 0) {
            jQuery('#alertDanger').remove();
        }
        jQuery('#opInfoBox').html('<div class="op-alert-danger" id"alertDanger">' + t9n_ops + '</span>');

    });
}

function check_resize_processing() {
    jQuery.ajax({
        method: "GET",
        url: admin_ajax,
        dataType: "json",
        data: { action: "op_check_resizing" }
    }).done(function (ris) {
        if (ris.done == check_process_count) {
            if (jQuery('#opContainer').hasClass('js-state-resize-processing')) {
                resize_all(check_process_count+10);
                if (jQuery('#alertDanger').length > 0) {
                    jQuery('#alertDanger').remove();
                }
                jQuery('#opInfoUpdate').html('<div class="op-alert-danger">' + t9n_ops + '</span>');
            }
            check_processing_timeout = setTimeout(function () { check_resize_processing(); }, 180000);
        } else {
            check_processing_timeout = setTimeout(function () { check_resize_processing(); }, 120000);
            check_process_count = ris.done;
        }
        if (chart_img_dim) {
            chart_img_dim.data = ris.scatter;
            chart_img_dim.update();
        }
        if (chart_img_space_disk) {
            chart_img_space_disk.data = ris.data_size_graph;
            if (chart_img_space_disk.data.datasets[0]) {
                chart_img_space_disk.data.datasets[0].backgroundColor = '#3696d7';
                chart_img_space_disk.data.datasets[0].borderColor = '#3696d7';
                chart_img_space_disk.data.datasets[0].fill = 'rgba(63, 155, 217, .5 )';
                chart_img_space_disk.data.datasets[0].fill = { target: 'origin', above: 'rgba(63, 155, 217, .5 )' };
                chart_img_space_disk.update();
            }
        }
        
    }).error(function() {
        if (jQuery('#alertDanger').length > 0) {
            jQuery('#alertDanger').remove();
        }
        jQuery('#opInfoBox').html('<div class="op-alert-danger" id"alertDanger">' + t9n_ops + '</span>');

        check_processing_timeout = setTimeout(function () { check_resize_processing(); }, 60000);
    });
}



/**
 * Abilita disabilita gli input
 */
function toggle_setting() {
    if (jQuery('#opContainer').hasClass('js-state-resize-processing') || jQuery('#opSettingsBlock').hasClass('js-state-submit')) {
        jQuery('.js-running-input-disable').prop('disabled', true);
     } else {
        jQuery('.js-running-input-disable').prop('disabled', false);
    }
}

function check_message_delete_Original() {
    console.log('check_message_delete_Original ' + jQuery('#resizeDeleteOriginal').is(':checked'))
    if (jQuery('#resizeDeleteOriginal').is(':checked')) {
        // TODO
        jQuery('#delete_image_yes_msg').css('display', 'block');
        jQuery('#delete_image_no_msg').css('display', 'none');
    } else {
        jQuery('#delete_image_yes_msg').css('display', 'none');
        jQuery('#delete_image_no_msg').css('display', 'block');
    }
}


function op_file_size(size) {
    if (size == 0) return "0 kB";
    var i = Math.floor(Math.log(size) / Math.log(1024));
    return (size / Math.pow(1024, i)).toFixed(2) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i];
}

/**
   * Converte un timestamp in ore minuti secondi
    */

function secondsToHms(d) {
    d = Number(d);
    var h = Math.floor(d / 3600);
    var m = Math.floor(d % 3600 / 60);
    var s = Math.floor(d % 3600 % 60);
    var hDisplay = (h < 10) ? "0" + h : h;
    var mDisplay = (m < 10) ? "0" + m : m;
    var sDisplay = (s < 10) ? "0" + s : s;
    return hDisplay + ":" + mDisplay +":"+ sDisplay;
}



const op_chart_config_dimension = {
    type: 'bubble',
    options: {
        aspectRatio: 1.5,
        animation: {
            duration: 0
        },
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function (tooltipItem) {
                        return tooltipItem.raw.img;
                    },
                    footer: function (tooltipItems) {
                        if (tooltipItems[0]['raw']) {
                            if (tooltipItems[0]['raw'].gap) {
                                return tooltipItems[0]['raw'].x + "X" + tooltipItems[0]['raw'].y + "px ~" + tooltipItems[0]['raw'].gap + "px";
                            } else {
                                return tooltipItems[0]['raw'].x + "X" + tooltipItems[0]['raw'].y + "px";
                            }
                        }
                    }
                }
            }
        },
        scales: {
            x: {
                title: {
                    display: true, text: 'WIDTH (PX)'
                },
            },
            y: {
                title: {
                    display: true, text: 'HEIGHT (PX)'
                },
            },
        }
    }
};


const op_config_space = {
    type: 'line',
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false,
            },
            title: {
                display: false, 
            }
        },
         scales: {
            x: {
                title: {
                    display: true, text: 'TIME'
                },
            },
            y: {
                title: {
                    display: true, text: 'MB'
                },
            },
        }
    },
};