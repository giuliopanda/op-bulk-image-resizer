<?php
/**
 * Gestisco il filtri e hook
 * 
 * @since      2.0.0
 */
namespace bulk_image_resizer;

if (!defined('WPINC')) die;

class Bir_loader {
	/**
	 * Inizializzo tutti i loader per gli ajax
	 */
	public function __construct() {
		if ((isset($_REQUEST['page']) && $_REQUEST['page'] == 'bulk-images')) {
			add_action('admin_head', [$this, 'transalte_javascript']);
		}
        //Salva i parametri di configurazione. Viene chiamata quando si clicca sul bottone salva configurazione
        add_action( 'wp_ajax_bir_save_configuration', [$this, 'save_configuration'] );
		add_action( 'wp_ajax_bir_start_bulk', [$this, 'start_bulk'] );
		add_action( 'wp_ajax_bir_next_bulk', [$this, 'next_bulk'] );

		add_action( 'wp_ajax_bir_start_restore', [$this, 'start_restore'] );
		add_action( 'wp_ajax_bir_next_restore', [$this, 'next_restore'] );
		//bir_delete_orginal
		add_action( 'wp_ajax_bir_start_delete_orginal', [$this, 'start_delete_orginal'] );
		add_action( 'wp_ajax_bir_next_delete_orginal', [$this, 'next_delete_orginal'] );

		add_action( 'wp_ajax_bir_stop', [$this, 'process_stop'] );

		add_action( 'wp_ajax_bir_get_stat', [$this, 'get_stat'] );

		add_action('admin_notices', [$this,'welcome_message']);
    }   



    /**
	 * Aggiorno i parametri delle opzioni
	 * @link /wp-admin/admin-ajax.php?action=op_save_configuration&op_resize_max_width=2560&op_resize_max_height=1440&op_resize_quality=60&op_resize_on_upload=0
	* $_REQUEST ['op_resize_max_width'=>'int','op_resize_max_height'=>'int', 'op_resize_quality'=>'range,10,100', 'op_resize_on_upload'=>'range,0,1'])
	*/
	public function save_configuration() {
		global $bir_options;
		if ($bir_options == null) $bir_options = new Bir_options_var();
		$bir_options->max_width = $_REQUEST['max_width'];
		$bir_options->max_height = $_REQUEST['max_height'];
		$bir_options->quality = $_REQUEST['quality'];
		$bir_options->resize_active = (isset($_REQUEST['resize_active'])) ? 1 : 0;
		if ($bir_options->resize_active == 0) {
			$bir_options->resize_on_upload = 0;
		}
		$bir_options->resize_on_upload = (isset($_REQUEST['resize_on_upload'])) ? 1 : 0;
		$bir_options->optimize_active = (isset($_REQUEST['optimize_active'])) ? 1 : 0;
		$bir_options->optimize_on_upload = (isset($_REQUEST['optimize_on_upload'])) ? 1 : 0;
		if ($bir_options->optimize_active == 0) {
			$bir_options->optimize_on_upload = 0;
		}
		$bir_options->webp_active = (isset($_REQUEST['webp_active'])) ? 1 : 0;
		$bir_options->webp_on_upload = (isset($_REQUEST['webp_on_upload'])) ? 1 : 0;
		
		$bir_options->rename_active = (isset($_REQUEST['rename_active'])) ? 1 : 0;
		$bir_options->rename_on_upload = (isset($_REQUEST['rename_on_upload'])) ? 1 : 0;
		$bir_options->rename = $_REQUEST['rename'];
		$bir_options->rename_change_title = (isset($_REQUEST['rename_change_title'])) ? 1 : 0;


		$bir_options->save();
		wp_send_json_success();
	}

	/**
	 * Avvio il bulk
	 * @link /wp-admin/admin-ajax.php?action=op_start_bulk
	 */
	public function start_bulk() {
		Bir_list_functions::reset_status();
		$info = Bir_list_functions::status(false);
		wp_send_json($info);
	}

	/**
	 * Vado avanti sul bulk
	 */
	public function next_bulk() {
		global $bir_options;
		if ($bir_options == null) $bir_options = new Bir_options_var();
		ini_set('display_errors', 0);
		ini_set('display_startup_errors', 0);
		error_reporting(E_ALL);
		$limit = 100;
		$limit_start = BIR_list_functions::get_done();
		$logs = [];
		$rows = BIR_list_functions::get_results($limit_start, $limit);
		$start = microtime(true);
		$count_rows = 0;
		foreach ($rows as $image_id) {
			if ((microtime(true) - $start) > 15) break;
			$old_images = Bir_rename_functions::find_image_reference($image_id);
			if (count($old_images) == 0)  {
				$log = ['count'=> ($limit_start + ($count_rows++)), 'date'=> date('Y-m-d H:i:s'), 'id'=>$image_id, 'image'=>''];
				$log['error'] = "error: Image not found";
				$logs[] = self::log_html($log, $image_id);
				continue;
			}
			$log = ['count'=> ($limit_start + ($count_rows++)), 'date'=> date('Y-m-d H:i:s'), 'id'=>$image_id, 'image'=>$old_images["original"]];
			$renamed = false;
			// TODO verifico che le immagini esistano e che siano scrivibili!
			Bir_facade::process_image($image_id);
		
			if (Bir_facade::is_error()) {
				$log['error'] = "error: ".Bir_facade::get_last_error();
			} else {
				$log['success'] = "optimized";
				if ($bir_options->webp_active == 1 && $bir_options->resize_active == 0) {
					$log['success'] = "converted to WEBP";
				} else if ($bir_options->resize_active == 1) {
					$log['resize'] = "resize: ".$bir_options->max_width."x".$bir_options->max_height;
					$log['quality'] = "quality: ".$bir_options->quality;
				}
				if ($bir_options->rename_active == 1) {
					$new_name = Bir_facade::get_new_named();
					$log['rename'] = "rename: ".basename($new_name);
				}
			}
			$logs[] = self::log_html($log, $image_id);
		}
		if (count($rows) == 0) {
			$info = Bir_list_functions::status();
			Bir_list_functions::set_done($info['total'], 'resize');
			wp_send_json($info);
		}
		BIR_list_functions::set_done($limit_start + $count_rows, 'resize');
		$info = Bir_list_functions::status(false);
		$info['logs'] = $logs;
		wp_send_json($info);
	}



	/**
	 * Avvio il restore delle immagini
	 * @link /wp-admin/admin-ajax.php?action=op_start_bulk
	 */
	public function start_restore() {
		Bir_list_functions::reset_status();
		$info = Bir_list_functions::status(false);
		wp_send_json($info);
	}

	/**
	 * Vado avanti sul restore
	 */
	public function next_restore() {
		global $bir_options;
		if ($bir_options == null) $bir_options = new Bir_options_var();
		// nascondo gli errori di php
		ini_set('display_errors', 0);
		ini_set('display_startup_errors', 0);
		error_reporting(E_ALL);
		$limit = 100;
		$limit_start = BIR_list_functions::get_done();
		$logs = [];
		$rows = BIR_list_functions::get_results($limit_start, $limit);
		$start = microtime(true);
		$count_rows = 0;
		foreach ($rows as $image_id) {
			if ((microtime(true) - $start) > 15) break;
			$old_images = Bir_rename_functions::find_image_reference($image_id);
			if (count($old_images) == 0) continue;
			$log = ['count'=> ($limit_start + ($count_rows++)), 'date'=> date('Y-m-d H:i:s'), 'id'=>$image_id, 'image'=>$old_images["original"]];
			
			if (Bir_optimize_functions::restore($image_id)) {
				if (Bir_optimize_functions::get_last_error() != '') {
					// se fa lo skip dell'immagine
					$log['success'] = Bir_optimize_functions::get_last_error();
				} else {
					$log['success'] = "restored";
				}
				Bir_rename_functions::replace_post_image_in_db($image_id, $old_images);
			} else {
				if (Bir_optimize_functions::get_last_error() == 'skipped') {
					$log['success'] = "skipped";
				} else {
					$log['error'] = "error: ".Bir_optimize_functions::get_last_error();
				}
			}
			$logs[] = self::log_html($log, $image_id);

		}
		if (count($rows) == 0) {
			$info = Bir_list_functions::status();
			Bir_list_functions::set_done($info['total'], 'restore');
			wp_send_json($info);
		}
		BIR_list_functions::set_done($limit_start + $count_rows, 'restore' );
		$info = Bir_list_functions::status(false);
		$info['logs'] = $logs;
		wp_send_json($info);
		die();
	}

	/**
	 * Interrompo definitivamente il processo
	 */
	public function process_stop() {
		$info = Bir_list_functions::status();
		Bir_list_functions::set_done($info['total'], 'stopped');
		wp_send_json($info);
		die();
	}

	/**
	 * Trasforma l'array del log in un html da stampare
	 */
	private function log_html($log, $image_id) {
		$html_log = '';
		if (isset($log['error'])) {
			$html_log = '<a href="'.get_edit_post_link($image_id).'" class="bir-error-link" target="_blank">'.implode("\t", $log).'</a>';
		} else 	if (isset($log['success']) && $log['success'] == 'skipped') {
			$html_log = '<a href="'.get_edit_post_link($image_id).'" class="bir-skipped-link" target="_blank">'.implode("\t", $log).'</a>';
		} else {
			$html_log = '<a href="'.get_edit_post_link($image_id).'" class="bir-success-link" target="_blank">'.implode("\t", $log).'</a>';
		}
		return $html_log;
	}


	/**
	 * Quì traduco i vari file della lingua per il javascript. 
	 * Non uso il metodo consigliato da wordpess perché l'ho trovato troppo complicato.
	 */
	public function transalte_javascript() {
		?>
			<script>
				var t9n_pause = "<?php (_e("Updates are paused. Click on resume to continue.", "bulk-image-resizer")); ?>";
				var t9n_confirm_1 = "<?php echo (__("Are you sure you want to resize images with a width less than 500px?", "bulk-image-resizer")); ?>";
				var t9n_confirm_2 = "<?php echo (__("Are you sure you want to resize images with a height less than 500px?", "bulk-image-resizer")); ?>";
				var t9n_confirm_3 = "<?php echo (__("Are you sure you want to leave the page?", "bulk-image-resizer")); ?>";
				var t9n_wait_settings = "<?php echo (__("Wait for the end of saving the settings", "bulk-image-resizer")); ?>";
				var t9n_analisys = "<?php echo (__("Analyzing the update in progress ...", "bulk-image-resizer")); ?>";
				var t9n_start_resize = "<?php echo (__("I start resizing the images", "bulk-image-resizer")); ?>";
				var t9n_warning_resize = "<?php echo (__("Do not leave the page while you are resizing your images", "bulk-image-resizer")); ?>";
				var t9n_we_are_almost_there = "<?php echo (__("We are almost there", "bulk-image-resizer")); ?>";
				var t9n_spared = "<?php echo (__("They were spared: <b> %s </b>.", "bulk-image-resizer")); ?>";
				var t9n_end_1 = "<?php echo (__("The update has finished", "bulk-image-resizer")); ?>";
				var t9n_end_2 = "<?php echo (__("Now the used space is: <b>%s</b>", "bulk-image-resizer")); ?>";
				var t9n_end_3 = "<?php echo (__("The elapsed time is: %s", "bulk-image-resizer")); ?>";
				var t9n_time_remaining = "<?php echo (__("Estimated time remaining: %s", "bulk-image-resizer")); ?>";
				var t9n_time_analisys = "<?php echo (__("Estimated time analysis in progress.", "bulk-image-resizer")); ?>";
				var t9n_img_left = "<?php echo (__("Still to be processed: %s", "bulk-image-resizer")); ?>";
				var t9n_none = "<?php echo (__("No images found to update", "bulk-image-resizer")); ?>";
				var t9n_ops = "<?php echo (__("I am having difficulty contacting the server.", "bulk-image-resizer")); ?>";
				function t9n_sprintf(string) {
					var args = Array.prototype.slice.call(arguments);
					a = args.splice(0,1);
					if (args.length > 0) {
						for (x in args) {
							string = string.replace("%s", args[x]);
						}
					}
					return string;
				}
			</script>
		<?php
	}

	/**
	 * Avvio il restore delle immagini
	 * @link /wp-admin/admin-ajax.php?action=op_start_bulk
	 */
	public function start_delete_orginal() {
		Bir_list_functions::reset_status();
		$info = Bir_list_functions::status(false);
		wp_send_json($info);
		die();
	}

	/**
	 * Vado avanti sul restore
	 */
	public function next_delete_orginal() {
		global $bir_options;
		if ($bir_options == null) $bir_options = new Bir_options_var();
		$limit = 100;
		$limit_start = BIR_list_functions::get_done();
		$logs = [];
		$rows = BIR_list_functions::get_results($limit_start, $limit);
		$start = microtime(true);
		$count_rows = 0;
		foreach ($rows as $image_id) {
			if ((microtime(true) - $start) > 15) break;
			$log = ['count'=> ($limit_start + ($count_rows++)), 'date'=> date('Y-m-d H:i:s'), 'id'=>$image_id, 'image'=>''];
			
			if (Bir_optimize_functions::delete_original($image_id)) {
				$log['success'] = Bir_optimize_functions::get_last_error();
			} else {
				$log['success'] = "skipped";
			}
			$logs[] = self::log_html($log, $image_id);
		}
		if (count($rows) == 0) {
			$info = Bir_list_functions::status();
			Bir_list_functions::set_done($info['total'], 'delete_original');
			wp_send_json($info);
		}
		BIR_list_functions::set_done($limit_start + $count_rows, 'delete_original' );
		$info = Bir_list_functions::status(false);
		$info['logs'] = $logs;
		wp_send_json($info);
		die();
	}

	/**
	 * Preparazione delle statistiche
	 */
	public function get_stat() {
		require_once BULK_IMAGE_RESIZER_DIR.'includes/class-bir-stat.php';
		$data = [];
		$count_data_filesize = Bir_statistic::count_data_filesize();
		$file_size = $count_data_filesize['current'];
		$msg = "";
		
		if ($file_size['total_files'] > $file_size['total_files_original']) {
			$msg = sprintf(__("There are %s images that have been not optimized.", "bulk-image-resizer"), ($file_size['total_files'] - $file_size['total_files_original']));
		} 
		
		$data['file_size'] = ['total_file_size' => $file_size['total_file_size'], 'total_file_size_original' => $file_size['total_file_size_original'], 'msg'=>$msg];
		$data['file_numbers'] = ['total_files'=>$file_size['total_files'], 'total_files_original'=>$file_size['total_files_original']];

		$labels = [];
		$dataset_1 = [];
		$dataset_2 = [];
		foreach ($count_data_filesize['all'] as $key => $cdfa) {
			$labels[] = substr($key, 0,4)."-".substr($key , 4,2);
			// trasformo il valore in MB
			//$dataset_1[] = $cdfa['total_file_size_original'];
			//$dataset_2[] = $cdfa['total_file_size'];
			$dataset_1[] = round($cdfa['total_file_size_original'] / 1024 / 1024, 2);
			$dataset_2[] = round($cdfa['total_file_size'] / 1024 / 1024, 2);
		}
		
		$data['history'] = ['labels'=>$labels, 'dataset_1'=>$dataset_1, 'dataset_2'=>$dataset_2];

		wp_send_json($data);
		die();
	}

	/**
	 * Carica un popup al caricamento della pagina dei plugin amministrativi
	 */
	function welcome_message() {
		if (is_admin()) {
			$screen = get_current_screen();
			$op_bulk_image_resizer = absint(get_option('bulk_image_resizer_welcome', 0));
			if ($screen->id == 'plugins' && $op_bulk_image_resizer > 0) {
				require(plugin_dir_path(plugin_dir_path( __FILE__ )) . 'admin/partials/bulk-image-resizer-popup-activation-plugin.php');
				$op_bulk_image_resizer = $op_bulk_image_resizer -1;
				if ($op_bulk_image_resizer > 0) {
					update_option('bulk_image_resizer_welcome', $op_bulk_image_resizer, false);
				} else {
					delete_option('bulk_image_resizer_welcome'); 
				}
			}
		}
	}

}

new Bir_loader();