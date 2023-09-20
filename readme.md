Creare  cli x Rename, thumbs, optimize, convert webp:  in 26 ore? 

CLI:
19/05/2023 al 31/05/2023 20 ore lavorate

GRAFICA:
01/06/2023 1 ora lavorata 
05/06/2023 1 ora lavorata
06/06/2023 1 ora
08/06/2023 1 ora
09/06/2023 1 ora

15/06/2023 1 ora start bulk
18/06/2023 3 ore config & bulk
19/06/2023 1 ora bulk test e fix

22/06/2023 1 test sul rename e convert  BUG: SI perde il 
             dato all'immagine originale ad un certo punto!!!
             test su restore se non c'è l'immagine originale sembra funzionare. Da testare se c'è l'immagine originale!
26/06/2023 1 restore image testato e funzionante

07/07/2023 1 

09/07/2023 Inizio STAT

05/08/2023 3 optimize non rinomina più l'immagine, ma rinomino l'immagine originale, testato anche il restore

06/08/2023 2 Delete Original, testato e funzionante

10-18 10 ore
19/08/2023 4
20/08/2023 4


## WORKING ON
BUG STAT:
l'ultima stat del grafico è sbagliata, deve tenere le info attuali
Aggiungere il grafico con la distribuzione delle dimensioni

Faccio un unico meta per 
_bir_attachment_originalname, _bir_attachment_originaltitle, _bir_attachment_uniqid
Lascio separato _bir_attachment_originalfilesize


## TODO 
Verificare file non immagini tipo pdf, zip
Pubblicizzare il fatto che rigenera le thumbs!
Pubblicizzare sulle singole immagini che sono ottimizzate

test webp su gif e png
risistemare la CLI!

Faccio un unico meta (_bir_attachment) per: _bir_attachment_originalname, _bir_attachment_originaltitle, _bir_attachment_uniqid
Lascio separato _bir_attachment_originalfilesize


Il restore è molto lento bisogna fare che in bir-rename-function > replace_post_image_in_db se i nomi delle immagini coincidono non si va avanti nel cercare tra i post!

[post_name] stampa l'id se non trova il nome?!

Note:
al momento il resize lo faccio su wp_update_attachment_metadata ... ma non mi piace, vorrei farlo su wp_generate_attachment_metadata oppure proprio sull'upload!
------------------------------

## DONE
bulk Delete original
fixbug: l'originale con l'estensione se il nome è da modificare es: f02f89.webp-original-1.png
Restore images: Non fa il restore del titolo!
Rename title non l'ho ancora scritto, per ora rinomina sempre anche il titolo!
Rename images: Rinomina il titolo ?!? non salva il vecchio nome da nessuna parte!
restore rename images: da Fare a seconda del vecchio nome!

Quando ottimizzo un'immagine che non viene rinominata o non si cambia formato, cambio il nome dell'immagine originale non il nome dell'immagine ottimizzata!!!! è molto più efficiente!

Quando faccio l'optimize non aggiorno Dimensione del file tra i metadati!

Process Done 100%: sistemare i messaggi di successo

webp uload sbaglia il nome dell'immagine originale. lascia il suffisso webp

Se l'immagine salvata è minore di dimensione di quella ottimizzata in webp allora la risalvo.
includes/class-bir-optimize-function.php
-----------------

## TEST: 
- Verifico di avere delle immagini da poter usare per i test nel sito 
- Apro il file class-bir-cli.php o class-bir-debug-cli.php a seconda del tipo di test che voglio fare
- mi leggo i commenti delle due classi con gli esempi di come eseguire le linee di comando
- Apri il terminale all'interno della cartella principale del progetto
- scrivo la linea di comando che voglio. Ad esempio:
- $ wp bir rename {image_id} {new_name} --allow-root 


## VERSIONE 3
Attiva Remove image (single) quando si  elimina un'immagine e le rimuove dagli articoli oppure non si possono rimuovere le immagini presenti negli articoli
Regenerate collega
Regenerate thumbs

Possibilità di resize personalizzati a seconda del tipo di articolo (tipo per i prodotti)
Verifica delle immagini troppo piccole
Trovare le immagini già caricate
Possibilità di sostituire un'immagine
backup & restore
Ottimizzazione dal server
woocommerce support

Shortcode per rinominare le immagini se sono collegate ad un post
[post_name] (già scritto) <?php _e('It is replaced with the slug of the post to which the image is attached', 'bulk-image-resizer'); ?><br>
Shortcode categorie e tag dai post e gestione dei post_parent

Regenerate thumbs aggiungere TAB
Libreria media quando ottimizzi o rigeneri le immagini visualizza i messaggi (di errore o di successo)

## APPUNTI


if (function_exists('imageavif')) {



    ---
I plugin che convertono le immagini in webp molto spesso duplicano le immagini e creano delle sovrastrutture nel sito per gestire questa duplicazione. Perfino alcuni plugin che dicono di non farlo in realtà creano delle cartelle 'nascoste' con le immagini duplicate. In questo modo appesantiscono il sito di una nuova sovrastruttura, occupano spazio sul server duplicando non solo le immagini originali, ma anche tutte le thumbs, alcuni addirittura in più formati (webp e avif). 
Tutto questo per due motivi: 1 sei costretto a mantenere il plugin attivo e a pagare il rinnovo altrimenti il sito perde tutte le ottimizzazioni, se il sito dopo le ottimizzazioni non funziona più bene o qualche immagine non si è convertita come si voleva, ti basta disinstallare il plugin per ritornare alla condizione iniziale.

Questo plugin modifica le immagini che hai caricato, ne cambia il nome il formato o le dimensioni a seconda delle tue esigenze. 
Le immagini alterate vengono ricercate e sostituite nei contenuti del sito per continuare a funzionare, tuttavia è importante che fai un backup del sito prima di usarlo e che verifichi che tutti i link alle immagini siano stati sostituiti correttamente. Forse richiede un po' più di lavoro e consapevolezza, ma il risultato è un sito più leggero e più veloce, senza sovrastrutture e senza dipendenze da plugin. E alla fine è poi la differenza tra un sito fatto da un professionista o un sito improvvisato fatto funzionare a forza di plugin installati a caso.
