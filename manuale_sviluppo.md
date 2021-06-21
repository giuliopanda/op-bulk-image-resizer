
# Struttura dei file
    bulk-image-resizer.php (è caricato sempre nel sito()
    |- includes
    |   |- op-functions.php (Sono le funzioni di sistema)
    |   |- class-bulk-image-resizer-loader.php 
    |      (Sono raggruppati quasi tutti gli hook)
    |   |- class-bulk-image-resizer-loader-ajax.php 
    |       (Sono raggruppate tutte le risposte Ajax)


# API
Documento solo le funzioni che penso possano essere riutilizzabili

##  get_total_img()
[./includes/op-function.php]()

Calcolo il totale delle immagini salvate sul database

## op_optimize_single_img($attachment_id)
[./includes/op-function.php]()

Ottimizza e fa il resize di una singola immagine

## op_clean_space_chart($jstat)
[./includes/op-function.php]()

Ogni volta che vengono ricaricate le statistiche viene salvato un nuovo punto. Questa funzione cancella quelli vecchi.

## op_convert_space_to_graph($data_size)
[./includes/op-function.php]()

Converte le statistiche dello spazio nei dati per il grafico

## convert_data_option_to_graph($data, $key)
[./includes/op-function.php]()

NON LO USO! (Da verificare)

## prepare_images_stat()
[./includes/op-function.php]()

Prepara le statistiche e ne ritorna i dati principali

## op_get_resize_options($key = "", $default = false)
[./includes/op-function.php]()

Ritorna l'array delle opzioni. 
Se key è impostato ritorna un singolo valore. Se non lo trova ritorna il default.



## op_get_image_info($path_img) 
[./includes/op-function.php]()

Torna tutte le info che possono servirmi di un'immagine

**Ritorna** 
```json
{"is_valid":false, "width":0, "height":0, "file_size":0, "class_resize":"gp_color_ok", "class_size":"gp_color_ok","show_btn":false, "is_writable": true}
```

**esempio**
```php
$path_attached = get_attached_file($post_id);
$img_attached = opBulkImageResizer\Includes\OpFunctions\op_get_image_info($path_attached);
```