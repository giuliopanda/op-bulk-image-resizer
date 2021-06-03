# DESCRIZIONE
Bulk image resize permette di comprimere e fare il resize delle immagini caricate su wordpress.

- Puoi fare il resize delle immagini con un solo click.
- È ottimizzato per velocizzare il processo di bulk. 1000 immagini richiedono pochi minuti su di un normale server.
- Puoi abilitare l'opzione di ottimizzare le immagini quando vengono caricate sul server. 
- Permette di decidere le dimensioni massime delle immagini e la qualità in cui devono essere compresse.
- Aggiunge su media-library (versione lista) la possibilità di selezionare le immagini che si vuole ottimizzare
- Sempre su media-library (versione lista) aggiunge una colonna informazioni aggiuntive sull'immagine.
- Tramite grafici permette di monitorare lo stato delle immagini sul server
- Possibilità di usare hook specifici per personalizzare le opzioni di ottimizzazione.

# ISTRUZIONI OPERATIVE
*Una volta preso in mano questo codice la descrizione delle azioni che si possono compiere*

 ## Istallazione e uso
 *Descrizione su come si usa il codice descritto.* 

 ## HOOKS
#### fn_bir_resize_image_bulk($filename, $attachment_id);
Personalizza il resize durante il bulk
ritorna Boolean|Array. 
Se torna true fa il resize dell'immagine se torna false non lo fa.
Se invece torna un array con width,height (o 0,1) fa il resize alla dimensione scelta.

#### fn_bir_resize_image_uploading ($filename, $post_id)
Personalizza il resize quando si carica un'immagine. $post_id può essere 0 se si sta caricando un'immagine direttamente da media.
ritorna Boolean|Array. 
Se torna true fa il resize dell'immagine se torna false non lo fa.
Se invece torna un array con width,height (o 0,1) fa il resize alla dimensione scelta.

```php
/**
 * Ridimensiona solo le immagini caricate dagli articoli
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
add_filter( 'op_bir_resize_image_bulk', 'fn_bir_resize_image', 10, 2);


/**
 * Ridimensiona solo le immagini caricate dagli articoli quando vengono caricati
 */
function fn_bir_resize_image_uploading ($filename, $post_id) {
	$post_type = get_post_type( $post_id );
	if ($post_type == "post") {
		return true;
	}
	return false;
}
add_filter( 'op_bir_resize_image_uploading', 'fn_bir_resize_image_uploading', 10, 2);

```

 # CHANGELOG e BACKUP
 Caricato su github la versione 0.9.0
 Inizio a lavorare la nuova versione (0.9.1)
 Il flusso di pubblicazione su github è:
- lo lavoro su un wordpress.
- A fine lavorazione o inizio nuova lavorazione lo copio in locale di github.
- Quando sto ad un punto fermo e lo voglio caricare online prima provo a installarlo su una nuova versione di wordpress temporanea per provare che funzioni.
- Se tutto va bene faccio il commit su github.

# TODO
- Multilingua - **FATTO**
- Pulizia codice (rivedere la struttura delle cartelle) **FATTO**
- GitHub (31 maggio) **FATTO**
- [nuova funzionalità] Aggiungere colonna su media **FATTO**
- Gestione degli errori durante la conversione. **FATTO**
- [nuova funzionalità] resize sul caricamento **FATTO** ok
- Istallazione/disinstalla **FATTO** (quando disistalli rimuove le option)
- [nuova funzionalità] Hooks  **FATTO Da testare**
- Readme.txt **in lavorazione**
- Spiegazioni dettagliate in italiano/inglese (Entro il 6 giugno)
- Verificare cosa bisogna fare per caricare il plugin su wordpress (Entro il 6 giugno)
- Revisione traduzione  (inizio 6 giugno)
- Multisite test
- [nuova funzionalità] Grafico dello spazio occupato nel tempo.  **FATTO**
- Test sui tipi di dati che si possono convertire (jpg e png) **FATTO**
- Screenshot **FATTO**

Caricamento su wordpress (6 giugno)


# IDEE PER LE PROSSIME VERSIONI:
- resize: min-width min-height valori minimi del resize che non devono essere superati
- Una volta aggiunto min-width e min-height posso avvertire se ci sono immagini sotto quelle dimensioni.
- Esportazione csv per excel dei dati convertiti (o delle immagini più piccole di... o delle immagini più grandi di...)

- CONVERTI FORMATI!

- resize delle thumbs

- Test sulla conversione
- filtra i tipi di dati da escludere nel resize (post_type, o non collegato)
- permetti di ordinare i media per filesize



# BUG
- i metadata sono ancora da verificare se vengono aggiornati bene
- Se trovo metadata sbagliati come lo gestisco?

# VALIDAZIONE
*Descrizione su come testare il lavoro o/e l'elenco delle operazioni fatte per documentare che il codice funziona*
## Test
## Log


# NOTE
https://developer.wordpress.org/plugins/plugin-basics/
https://developer.wordpress.org/coding-standards/wordpress-coding-standards/



PLUGIN STRUTTURA
https://github.com/DevinVinson/WordPress-Plugin-Boilerplate/blob/master/plugin-name/plugin-name.php


https://wordpress.org/plugins/developers/