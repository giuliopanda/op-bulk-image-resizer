<?php 
/**
 * Le azioni per le chiamate Ajax
 * @since      1.1.0
 *
 * @package    bulk-image-resizer
 * @subpackage bulk-image-resizer/includes
 */
use opBulkImageResizer\Includes\OpFunctions AS Opfn;
if (!is_admin()) return;

class Bulk_image_resizer_loader_ajax {
	/**
	 * Inizializzo tutti i loader per gli ajax
	 */
	public function __construct() {
			//Salva i parametri di configurazione. Viene chiamata quando si clicca sul bottone salva configurazione
			add_action( 'wp_ajax_op_save_configuration'	, [$this, 'save_configuration'] );
			// Per avere le statistiche. Viene chiamata subito dopo che la pagina è stata caricata
			add_action( 'wp_ajax_op_calc_stats'			, [$this, 'calc_stats'] ); 
			//  Fa il resize di un gruppo di immagini. $_REQUEST[start] per decidere da quale immagini si cominicia
			add_action('wp_ajax_op_resize_all' 			, [$this, 'ajax_resize_all']);
			// Viene chiamato durante il resize ogni tot minuti. Ritorna il grafico aggiornato e a che punto si è dell'aggiornamento. Serve per verificare che non si sia impallato nulla.
			add_action('wp_ajax_op_check_resizing'		, [$this, 'ajax_check_resizing']);
			//  Viene chiamata alla fine del resize per chiudere il ciclo e calcolare com'è andata.
			add_action('wp_ajax_op_end_resize_all'		, [$this, 'end_resize_all']);
			// Fa il resize di una singola immagine (lo chiamo da elenco media)
			add_action('wp_ajax_op_resize_single'		, [$this, 'resize_single']);
	}
	/**
	 * Aggiorno i parametri delle opzioni
	 * @link /wp-admin/admin-ajax.php?action=op_save_configuration&op_resize_max_width=2560&op_resize_max_height=1440&op_resize_quality=60&op_resize_on_upload=0
	* $_REQUEST ['op_resize_max_width'=>'int','op_resize_max_height'=>'int', 'op_resize_quality'=>'range,10,100', 'op_resize_on_upload'=>'range,0,1'])
	*/
	public function save_configuration() {
		global $wpdb;
		$result = ['updated' =>1, 'msg'=>__('Setting saved','bulk_image_resizer')];
		$resize = $_REQUEST['op_resize'];
		if (!array_key_exists('delete_original', $resize)) {
			$resize['delete_original'] = 0; 
		} 
		if (!array_key_exists('on_upload', $resize)) {
			$resize['on_upload'] = 0; 
		} 
		update_option('bulk_image_resizer', json_encode($resize), false);	
		wp_send_json($result);
	}
	/**
	 *  Calcolo le statistiche
	 * Stampa un json con questa struttura:
	 * {"data_size":{"[timestamp]":[bytes]},"tot_images":[number],"images_size":[bytes],"last_update":[timestamp],"scatter":{"datasets":[{"label":"jpeg","data":[{"x":[Number],"y":[Number],"img":"[Text]","tot":[Number],"gap":[Number],"r":[Number]}]},"data_size_graph":{"labels":["TEXT"],"datasets":[{"data":[[NUMBER]]}]}}
	 * @link /wp-admin/admin-ajax.php?action=op_calc_stats
	*/
	public function calc_stats() {
		global $wpdb;
		$stat = get_option('op_resize_statistics', '[]');
		$images_file_size = 0;
		$jstat = json_decode($stat, true);
		if (!is_array($jstat)) {
			$jstat = array();
		}
		// carico i dati a partire dai post images 
		list($tot_img, $images_file_size, $datasets) = Opfn\prepare_images_stat();
		$jstat['data_size'][time()] = $images_file_size;
		$jstat['data_size'] 		= Opfn\op_clean_space_chart($jstat['data_size']);
		$jstat['tot_images'] 		= $tot_img;
		$jstat['images_size'] 		= $images_file_size;
		$jstat['last_update'] 		= time();
		update_option('op_resize_statistics', json_encode($jstat), false);
		$jstat['scatter'] 			= ['datasets'=>$datasets];
		$jstat['data_size_graph'] 	= Opfn\op_convert_space_to_graph($jstat['data_size']);
		wp_send_json($jstat);
		die;
	}
	/**
	 * La chiamata in ajax che si ripete fin tanto che non sono stati aggiornati tutti i dati
	 * Aggiorno in bach tutte le immagini
	 * $_REQUEST['start']
	 */
	public function ajax_resize_all() {
		global $wpdb;
		$done = absint($_REQUEST['start']);
		$start = microtime(true);
		$post_ids = $wpdb->get_results("SELECT ID , guid FROM `" . $wpdb->prefix . "posts` WHERE `post_mime_type` LIKE (\"image%\") AND post_type = \"attachment\" ORDER by ID DESC LIMIT " . $done . ", 200");

		foreach ($post_ids as $post) {
			if ((microtime(true) - $start) > 20) {
				break;
			}
			$done++;
			Opfn\op_optimize_single_img($post->ID);
		}
		update_option('op_resize_images_done',  $done, false);
		wp_send_json(['done' => $done]);
	}
	/**
	 *  Aggiorno in bach tutte le immagini
	 */ 
	public function ajax_check_resizing() {
		$stat = get_option('op_resize_statistics', '[]');
		$jstat = json_decode($stat, true);
		$done = get_option('op_resize_images_done',  0);
		list($tot_img, $images_file_size, $datasets) = Opfn\prepare_images_stat();
		//update_option('op_resize_images_done',  $done);
		wp_send_json(['done'=> $done, 'file_size' => $images_file_size, 'scatter' => ['datasets' => $datasets], 'data_size_graph'=>  Opfn\op_convert_space_to_graph($jstat['data_size']) ]);
	}
	
	/**
	 * Chiusura dell'aggiornamento batch
	 */
	public function end_resize_all() {
	//	global $wpdb;
		$stat = get_option('op_resize_statistics', '[]');
		$jstat = json_decode($stat, true);
		$old_file_size = 0;
		if (is_array($jstat)) {
			$array_pop = array_pop($jstat);
			if (is_array($array_pop)) {
				$old_file_size = array_shift(array_pop($jstat));
			}
		}
		list($tot_img, $images_file_size, $datasets) = Opfn\prepare_images_stat();
		if (is_array($jstat['data_size'])) {
			$old_file_size = end($jstat['data_size']);
		}
		$jstat['data_size'][time()] = $images_file_size;
		$jstat['data_size'] 	= Opfn\op_clean_space_chart($jstat['data_size']);
		$jstat['tot_images'] 	= $tot_img;
		$jstat['images_size'] 	= $images_file_size;
		$jstat['last_update'] 	= time();
		delete_transient('dirsize_cache');
		update_option('op_resize_statistics', json_encode($jstat), false);
		wp_send_json(['file_size' => $images_file_size, 'old_file_size' => $old_file_size, 'scatter' => ['datasets' => $datasets], 'data_size_graph'=>  Opfn\op_convert_space_to_graph($jstat['data_size'])]);
	}
	/**
	 * Fa il resize di una singola immagine. Lo si usa nei bottoni resize della media library
	 * $_REQUEST['post_id]
	 */
	public function  resize_single() {
		$post_id = absint($_REQUEST['post_id']);
		if ($post_id > 0) {
			$img = Opfn\op_optimize_single_img($post_id);
			if (is_wp_error($img)){
				wp_send_json(['response'=>'error', 'msg'=> $img->get_error_message(), 'post_id' => $post_id]);
			} else {
				$size = filesize($img['path']); 
				wp_send_json(['response'=>'ok', 'width'=> $img['width'], 'height'=>$img['height'], 'post_id'=>$post_id, 'size'=> size_format($size), 'old_size'=> sanitize_text_field($_REQUEST['old_size']), 'old_dim'=>sanitize_text_field($_REQUEST['old_dim'])]);
			}
		} else {
			wp_send_json(['response' => 'error', 'msg' => 'no post_id passed']);
		}
		//['path' => string, 'file' => string, 'width' => int, 'height' => int, 'mime-type' => string]
	}
}