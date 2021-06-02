=== Plugin Name ===
Contributors: giuliopanda 
Donate link: giuliopanda@gmail.com
Tags: convert,image,Optimize,resize
Requires at least: 5.3
Tested up to: 5.7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Bulk image resizer.

== Description ==

Bulk image resize permette di comprimere e fare il resize delle immagini caricate su wordpress.

- Puoi fare il resize delle immagini con un solo click.
- È ottimizzato per velocizzare il processo di bulk. 1000 immagini richiedono pochi minuti su di un normale server.
- Puoi abilitare l'opzione di ottimizzare le immagini quando vengono caricate sul server. 
- Permette di decidere le dimensioni massime delle immagini e la qualità in cui devono essere compresse.
- Aggiunge su media-library (versione lista) la possibilità di selezionare le immagini che si vuole ottimizzare
- Sempre su media-library (versione lista) aggiunge una colonna informazioni aggiuntive sull'immagine.
- Tramite grafici permette di monitorare lo stato delle immagini sul server
- Possibilità di usare hook specifici per personalizzare le opzioni di ottimizzazione.

== Installation ==

Dopo aver installato il plugin, vai su Tools > optica press bulk image resize per impostare il plugin.
Puoi ridimensionare singole immagini o a gruppi da media library mode list.


= What about Optica Press Bulk image resizer =

Quando carichi un'immagine su wordpress vengono create le thumbs per il template, ma l'immagine caricata viene salvata e talvolta usata. 
Bulk image resizer ridimensiona le immagini caricate così da ottimizzare la velocità del sito e lo spazio nel server.

Attenzione
Le immagini vengono sovrascritte alle dimensioni impostate, per cui è importante prima fare un backup. 
Non si assumono responsabilità per qualsiasi malfunzionamento o perdita di informazioni derivanti dall'uso del plugin.

== Hook  ==
È possibile personalizzare quali immagini ottimizzare e come attraverso due hook


fn_bir_resize_image_bulk($filename, $attachment_id) 
return  Boolean|Array [width:int,height:int]
Viene chiamato durante il bulk.

fn_bir_resize_image_uploading ($filename, $post_id)
return  Boolean|Array [width:int,height:int]
Viene chiamato quando viene caricata una nuova immagine

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 1.0 =
* Fixbug complete resize message
* Add space Graph
* Test on wordpress 5.3 and fix code for PHP 5.6
* FixBug resize on post ulpoad.

= 0.9 =
* Work version Bulk image resize 
* Add language Translate
