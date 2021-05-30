<?php 

/**
 * Le azioni per le chiamate Ajax
 * op_calc_stats Per avere le statistiche. Viene chiamata subito dopo che la pagina è stata caricata
 * op_save_configuration: Salva i parametri di configurazione. Viene chiamata quando si clicca sul bottone salva configurazione
 * fn_ajax_resize_all: Fa il resize di un gruppo di immagini. $_REQUEST[start] per decidere da quale immagini si cominicia
 * fn_ajax_check_resizing: Viene chiamato durante il resize ogni tot minuti. Ritorna il grafico aggiornato e a che punto si è dell'aggiornamento. Serve per verificare che non si sia impallato nulla.
 * fn_end_resize_all: Viene chiamata alla fine del resize per chiudere il ciclo e calcolare com'è andata.
 * 
 * @since      0.9.0
 *
 * @package    op-bulk-image-resizer
 * @subpackage op-bulk-image-resizer/includes
 */

if (!is_admin()) return;

/**
 * Aggiorno i parametri delle opzioni
* $_REQUEST ['op_resize_max_width'=>'int','op_resize_max_height'=>'int', 'op_resize_quality'=>'range,10,100', 'op_resize_on_upload'=>'range,0,1'])
*/
function op_save_configuration() {
	global $wpdb;
	$result = ['updated' =>1, 'msg'=>''];
	if (@$_REQUEST['op_resize_quality'] < 10 || $_REQUEST['op_resize_quality'] > 100) {
		$result = ['updated' =>0, 'msg'=>'La qualità delle immagini deve essere compresa tra 10 e 100'];
	} else if (@$_REQUEST['op_resize_on_upload'] != 1 && @$_REQUEST['op_resize_on_upload'] != 0 && isset($_REQUEST['op_resize_on_upload'])) {
		$_REQUEST['op_resize_on_upload'] = 0;
	}
	if (@$_REQUEST['op_resize_max_width'] >= 400 && @$_REQUEST['op_resize_max_height'] >= 400 && $result['updated'] == 1) {
		update_option('op_resize_max_width', $_REQUEST['op_resize_max_width'], false);	
		update_option('op_resize_max_height', $_REQUEST['op_resize_max_height'], false);	
		update_option('op_resize_quality', $_REQUEST['op_resize_quality'], false);	
		update_option('op_resize_on_upload', $_REQUEST['op_resize_on_upload'], false);		
	} else {
		$result = ['updated' =>0, 'msg'=>'Altezza e larghezza devono essere maggiori di 400px'];
	}
    wp_send_json($result);
}
add_action( 'wp_ajax_op_save_configuration', 'op_save_configuration' );

/**
 *  Calcolo le statistiche
 * LA STRUTTURA DEL JSON che salva su op_resize_statistics è:
{"tot_images":4751,"files_size":2511986974,"images_size":2415397165,"data_bar":["timestamp":bytes,"timestamp":bytes]}
 * l'output è un json con questa struttura
 * {"tot_images":int,"files_size":bytes,"images_size":bytes,"data_pie":{"labels":["jpeg","png","tiff"],"datasets":[{"data":[4717,30,4],"backgroundColor":["rgb(54, 162, 235)","rgb(255, 99, 132)","rgb(255, 205, 86)","rgb(43, 177, 164)","rgb(125, 54, 204)","rgb(142, 31, 31)","rgb(77, 77, 77)"]}]},"data_bar":{"labels":["01-01","01-01","01-01"],"datasets":[{"data":[4565397165,4555397165,2435397165],"backgroundColor":["rgb(255, 205, 86)","rgb(255, 205, 86)","rgb(75, 192, 192)"]}]}}
 * @link /wp-admin/admin-ajax.php?action=op_calc_stats
*/
 
function op_calc_stats() {
	global $wpdb;
	$stat = get_option('op_resize_statistics','[]');
	
	$images_file_size = 0;
	$jstat = json_decode($stat, true);
	if (!is_array($jstat)) {
		$jstat = array();
	} else {
		// cache	
	}
	
	// carico i dati a partire dai post images 
	list($tot_img, $images_file_size, $datasets) = prepare_images_stat();
	$jstat['data_size'][time()] = $images_file_size;
	$jstat['data_size'] = op_clean_space_chart($jstat['data_size']);
	$jstat['tot_images'] 	= $tot_img;
	$jstat['images_size'] 	= $images_file_size;
	$jstat['last_update'] 	= time();
	update_option('op_resize_statistics', json_encode($jstat));
	
	$jstat['scatter'] 		= ['datasets'=>$datasets];
 	wp_send_json($jstat);
	die;
}

add_action( 'wp_ajax_op_calc_stats', 'op_calc_stats' ); 


// La chiamata in ajax che si ripete fin tanto che non sono stati aggiornati tutti i dati
// Aggiorno in bach tutte le immagini
function fn_ajax_resize_all()
{
	
	global $wpdb;
	$done = $_REQUEST['start'] ;
	
	//wp_send_json(['done' => $done ]);
	//die;
	$start = microtime(true);
	$post_ids = $wpdb->get_results("SELECT ID , guid FROM `" . $wpdb->prefix . "posts` WHERE `post_mime_type` LIKE (\"image%\") AND post_status = \"inherit\" AND post_type = \"attachment\" ORDER by ID DESC LIMIT " . $done . ", 200");

	foreach ($post_ids as $post) {
		if ((microtime(true) - $start) > 20) {
			break;
		}
		$done++;
		op_optimize_single_img($post->ID);
	}
	update_option('op_resize_images_done',  $done);
	wp_send_json(['done' => $done]);
}
add_action('wp_ajax_op_resize_all', 'fn_ajax_resize_all');

// Aggiorno in bach tutte le immagini
function fn_ajax_check_resizing()
{
	$done = get_option('op_resize_images_done',  0);
	list($tot_img, $images_file_size, $datasets) = prepare_images_stat();
	//update_option('op_resize_images_done',  $done);
	wp_send_json(['done'=> $done, 'file_size' => $images_file_size, 'scatter' => ['datasets' => $datasets]]);
}
add_action('wp_ajax_op_check_resizing', 'fn_ajax_check_resizing');

// chiusura dell'aggiornamento batch
function fn_end_resize_all()
{
//	global $wpdb;
	$stat = get_option('op_resize_statistics', '[]');
	$jstat = json_decode($stat, true);
	$old_file_size = 0;
	if (is_array($jstat)) {
		$old_file_size = array_shift(array_pop($jstat));
	}
	list($tot_img, $images_file_size, $datasets) = prepare_images_stat();
	if (is_array($jstat['data_size'])) {
		$old_file_size = end($jstat['data_size']);
	}
	$jstat['data_size'][time()] = $images_file_size;
	$jstat['data_size'] = op_clean_space_chart($jstat['data_size']);
	$jstat['tot_images'] 	= $tot_img;
	$jstat['images_size'] 	= $images_file_size;
	$jstat['last_update'] 	= time();
	update_option('op_resize_statistics', json_encode($jstat));
	wp_send_json(['file_size' => $images_file_size, 'old_file_size' => $old_file_size, 'scatter' => ['datasets' => $datasets]]);
}
add_action('wp_ajax_op_end_resize_all', 'fn_end_resize_all');

/**
 * TEST PER VERIFICARE CHE FUNZIONI BENE IL SISTEMA DELLE STATISTICHE
 * @link /wp-admin/admin-ajax.php?action=op_test_data_size
 */
function fn_test_data_size() {
	$stat = get_option('op_resize_statistics', '[]');
	
	$jstat = json_decode($stat, true);
	$jstat['data_size'] = op_clean_space_chart($jstat['data_size']);

	var_dump ($jstat['data_size']);
}
add_action('wp_ajax_op_test_data_size', 'fn_test_data_size');



/**
 * Fa il resize di una singola immagine. Lo si usa nei bottoni resize della media library
 */
function  op_resize_single() {
	$post_id = $_REQUEST['post_id'];
	if ($post_id > 0) {
		$img = op_optimize_single_img($post_id);
		if (is_wp_error($img)){
			wp_send_json(['response'=>'error', 'msg'=> $img->get_error_message(), 'post_id' => $post_id]);
		} else {
			$size = filesize($img['path']); 
			wp_send_json(['response'=>'ok', 'width'=> $img['width'], 'height'=>$img['height'], 'post_id'=>$post_id, 'size'=> size_format($size), 'old_size'=> $_REQUEST['old_size'], 'old_dim'=>$_REQUEST['old_dim']]);
		}
	} else {
		wp_send_json(['response' => 'error', 'msg' => 'no post_id passed']);
	}
	//['path' => string, 'file' => string, 'width' => int, 'height' => int, 'mime-type' => string]
}
add_action('wp_ajax_op_resize_single', 'op_resize_single');