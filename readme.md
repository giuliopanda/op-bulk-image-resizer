Bulk image resizer for wordpress

# Description

Bulk image resize permette di ottimizzare le immagini caricate su wordpress.

- Puoi fare il resize di tutte le immagini con un solo click.
- È ottimizzato per velocizzare il processo di bulk. 1000 immagini richiedono pochi minuti su di un normale server.
- Puoi abilitare l'opzione di ottimizzare le immagini quando vengono caricate sul server. 
- Permette di decidere le dimensioni massime delle immagini e la qualità in cui devono essere compresse.
- Aggiunge su media-library (versione lista) la possibilità di selezionare le immagini che si vuole ottimizzare
- Sempre su media-library (versione lista) aggiunge una colonna informazioni aggiuntive sull'immagine.
- Tramite grafici permette di monitorare lo stato delle immagini sul server
- Possibilità di usare hook specifici per personalizzare le opzioni di ottimizzazione.

 The GitHub repo can be found at [https://github.com/WebDevStudios/custom-post-type-ui](https://github.com/WebDevStudios/custom-post-type-ui). Please use the Support tab for potential bugs, issues, or enhancement ideas.


# Installation

Dopo aver installato il plugin, vai su Tools > optica press bulk image resize per impostare il plugin.
Puoi ridimensionare singole immagini o a gruppi da media library mode list.

# Frequently Asked Questions

### Perché usare Bulk image resizer? 
Perché è opensource e non hai limiti nell'uso. Ti permetterà di rendere il tuo sito più veloce e ti farà risparmiare spazio. 

### Che formati supporta? 
Supporta i formati jpg e png in accordo con le direttive di wordpress. Infatti By default you can only upload JPG and PNG to your pages and posts. 

### È possibile decidere oltre alla posizione anche la qualità delle immagini? 
Si, si può decidere se comprimere le immagini ad alta qualità, media o bassa.

### Una volta ridimensionate si può tornare indietro?
No, le immagini ottimizzate sovrascivono le immagini originali per cui se non si fa un backup non è possibile tornare indietro.

### Posso decidere quali immagini ottimizzare?=
Sì, puoi selezionare da media library (versione lista) le immagini da ottimizzare, oppure utilizzare gli hook per estendere lo script.

### What about Optica Press Bulk image resizer

Quando carichi un'immagine su wordpress vengono create le thumbs per il template, ma l'immagine caricata viene salvata e talvolta usata. 
Bulk image resizer ridimensiona le immagini caricate così da ottimizzare la velocità del sito e lo spazio nel server.

**Attenzione**
Le immagini vengono sovrascritte alle dimensioni impostate, per cui è importante prima fare un backup. 
Non si assumono responsabilità per qualsiasi malfunzionamento o perdita di informazioni derivanti dall'uso del plugin.

# Personalizzare il codice con i filtri
È possibile personalizzare quali immagini ottimizzare e come attraverso due filtri

```php
/**
 * Ridimensiona solo le immagini caricate dagli articoli
 * @return  Boolean|Array [width:int,height:int]
 */
function fn_bir_resize_image_bulk ($filename, $attachment_id) {
	$parent_id = wp_get_post_parent_id( $attachment_id);
	if ($parent_id > 0) {
		$post_type = get_post_type( $parent_id );
		if ($post_type == "post") {
			return true;
		}
	}
	return false;
}
// Viene chiamato durante il bulk.
add_filter( 'op_bir_resize_image_bulk', 'fn_bir_resize_image', 10, 2);


/**
 * Ridimensiona solo le immagini caricate dagli articoli quando vengono caricati
 * @return  Boolean|Array [width:int,height:int]
 */
function fn_bir_resize_image_uploading ($filename, $post_id) {
	$post_type = get_post_type( $post_id );
	if ($post_type == "post") {
		return true;
	}
	return false;
}
// Viene chiamato quando viene caricata una nuova immagine
add_filter( 'op_bir_resize_image_uploading', 'fn_bir_resize_image_uploading', 10, 2);

```

# Screenshots
 
![L'aspetto della pagina per il bulk del resize](https://raw.githubusercontent.com/giuliopanda/op-bulk-image-resizer/main/assets/screenshot-1.jpg)


![Il menu da cui si accede a questa pagina](https://raw.githubusercontent.com/giuliopanda/op-bulk-image-resizer/main/assets/screenshot-3.jpg)


# Changelog

### 1.0.0 - 2021-06-02 
* Fixed: complete bulk messages
* Added: HHD Space Graph
* Test: On wordpress 5.3 and fix code for PHP 5.6
* Fixed: Resize on post ulpoad don't work.

### 0.9.0 - 2021-05-20 
* Work version Bulk image resize 
* Added: language Translate


# Credits
The OP Bulk image resizer was started in 2021 by [Giulio Pandolfelli](giuliopanda@gmail.com) 

https://www.chartjs.org/ per i grafici.