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
*Esempi e usi degli hooks*

 # CHANGELOG e BACKUP
 Il backup sta dentro la cartella github al momento sto facendo il backup dentro il mio drive personale ma è da passare su github

# TODO

- Multilingua - **FATTO**
- Pulizia codice (rivedere la struttura delle cartelle)
- GitHub (31 maggio)
- [nuova funzionalità] Aggiungere colonna su media **FATTO**
- [nuova funzionalità] resize sul caricamento
- Istallazione/disinstalla **in lavorazione** (quando disistalli rimuovi le option)
- [nuova funzionalità] Hooks 
- Guida **in lavorazione**
- Revisione traduzione  (6 giugno)
- Multisite test
- Gestione degli errori durante la conversione. **FATTO**
- [nuova funzionalità] Grafico dello spazio occupato nel tempo.
- Test sui tipi di dati che si possono convertire

Caricamento su wordpress (13 giugno)





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


