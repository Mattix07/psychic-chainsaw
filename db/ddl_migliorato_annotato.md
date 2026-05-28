# DDL Migliorato Annotato — `5cit_eventsmaster`

Commento riga per riga di `ddl_migliorato.sql`.
Per le motivazioni delle modifiche rispetto all'originale vedere [ddl_modifiche.md](ddl_modifiche.md).

---

## Preambolo

```sql
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;
```

| Riga | Commento |
|------|----------|
| `SET NAMES utf8mb4` | Imposta l'encoding della connessione. `utf8mb4` è il superset di UTF-8 che supporta anche i caratteri Unicode a 4 byte (emoji, caratteri CJK). Necessario affinché i dati inseriti durante l'esecuzione dello script siano interpretati correttamente. |
| `SET FOREIGN_KEY_CHECKS=0` | Disabilita temporaneamente la verifica dei vincoli FK. Indispensabile perché le tabelle sono dichiarate in ordine logico di dipendenza, ma alcune FK puntano a tabelle successive (es. `biglietti` → `eventi`, `ordini` → `utenti`). Riabilitato alla fine. |

---

## `tipo`

```sql
DROP TABLE IF EXISTS `tipo`;
CREATE TABLE `tipo` (
  `id`                 int NOT NULL AUTO_INCREMENT,
  `nome`               varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `ModificatorePrezzo` decimal(6,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`),
  CONSTRAINT `tipo_chk_1` CHECK (`ModificatorePrezzo` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

| Riga | Commento |
|------|----------|
| `DROP TABLE IF EXISTS` | Elimina la tabella se esiste già, rendendo lo script rieseguibile senza errori. Corretto per script di (re)installazione. |
| `id int NOT NULL AUTO_INCREMENT` | Chiave surrogata intera. `NOT NULL` implicito con `AUTO_INCREMENT` ma esplicitato per chiarezza. |
| `nome varchar(50) NOT NULL` | Nome della tipologia biglietto (es. `Standard`, `VIP`, `Premium`). `NOT NULL` corretto: un tipo senza nome non ha senso. Lunghezza 50 ampiamente sufficiente. |
| `UNIQUE KEY nome` | Impedisce tipologie con lo stesso nome. L'unicità è a livello DB, non solo applicativo. Genera automaticamente un indice su `nome`, utile per le ricerche per nome tipo. |
| `ModificatorePrezzo decimal(6,2) NOT NULL DEFAULT '0.00'` | Sovrapprezzo fisso in euro da sommare al prezzo base dell'evento. `DECIMAL` evita errori di arrotondamento floating-point nei calcoli monetari. `(6,2)` copre valori fino a `9.999,99 €`. `NOT NULL` con `DEFAULT 0.00`: il tipo Standard ha modificatore zero, non assenza di valore. |
| `CHECK (ModificatorePrezzo >= 0)` | Garantisce a livello DB che nessun tipo abbia un modificatore negativo, il che ridurrebbe il prezzo base creando potenziali prezzi negativi. |
| `tipo` dichiarata per prima | Necessario perché `biglietti.idTipo` è FK verso questa tabella. Anche con `FOREIGN_KEY_CHECKS=0` l'ordine logico rende il DDL più leggibile. |

---

## `utenti`

```sql
DROP TABLE IF EXISTS `utenti`;
CREATE TABLE `utenti` (
  `id`                              int NOT NULL AUTO_INCREMENT,
  `Nome`                            varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `Cognome`                         varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `Email`                           varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Password`                        varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ruolo`                           enum('admin','mod','promoter','user') COLLATE utf8mb4_general_ci DEFAULT 'user',
  `verificato`                      tinyint(1) DEFAULT '0',
  `verificato_at`                   datetime DEFAULT NULL,
  `Avatar`                          mediumblob,
  `DataRegistrazione`               datetime DEFAULT CURRENT_TIMESTAMP,
  `reset_token`                     varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reset_token_expiry`              datetime DEFAULT NULL,
  `email_verification_token`        varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email_verification_token_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `Email` (`Email`),
  UNIQUE KEY `reset_token` (`reset_token`),
  KEY `idx_ruolo` (`ruolo`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

| Riga | Commento |
|------|----------|
| `id int NOT NULL AUTO_INCREMENT` | PK surrogata standard. |
| `Nome`, `Cognome varchar(100) NOT NULL` | Dati anagrafici obbligatori. 100 caratteri coprono qualsiasi nome reale. `NOT NULL` corretto: un account deve sempre avere un intestatario identificabile. |
| `Email varchar(255) NOT NULL` | 255 è il limite massimo definito dalla RFC 5321 per gli indirizzi email. `NOT NULL` perché l'email è l'identificatore primario di autenticazione. |
| `UNIQUE KEY Email` | Garantisce che non possano esistere due account con la stessa email. Crea implicitamente un indice B-tree su `Email`, sufficiente per le query di login (`WHERE Email = ?`). |
| `Password varchar(255) NOT NULL` | Contiene l'hash bcrypt della password (`password_hash()` in PHP). I hash bcrypt sono 60 caratteri, ma `varchar(255)` lascia margine per futuri algoritmi di hashing più lunghi. `NOT NULL` corretto. |
| `ruolo enum('admin','mod','promoter','user') DEFAULT 'user'` | Gerarchia a 4 livelli. L'ENUM garantisce che non possano essere inseriti ruoli non definiti. `DEFAULT 'user'` corretto: ogni nuovo registrato è utente base. L'ENUM occupa 1-2 byte contro potenziali decine per una stringa. |
| `verificato tinyint(1) DEFAULT '0'` | Flag booleano: `0` = email non verificata, `1` = verificata. `tinyint(1)` è la convenzione MySQL per i booleani (non esiste tipo `BOOLEAN` nativo). |
| `verificato_at datetime DEFAULT NULL` | Timestamp del momento in cui l'utente ha verificato la propria email. `NULL` finché non verificato. Utile per analytics (tempo medio tra registrazione e verifica) e audit. |
| `Avatar mediumblob` | Immagine profilo salvata direttamente nel DB come binario. `MEDIUMBLOB` supporta fino a 16 MB. Il codice applica resize a max 1024px e compressione JPEG 90% prima del salvataggio, limitando la dimensione effettiva. |
| `DataRegistrazione datetime DEFAULT CURRENT_TIMESTAMP` | Timestamp automatico alla creazione del record. Non aggiornabile (`ON UPDATE` assente), registra il momento esatto della registrazione. |
| `reset_token varchar(100) DEFAULT NULL` | Token casuale per il reset della password. `NULL` quando non è attiva una richiesta di reset. |
| `UNIQUE KEY reset_token` | Garantisce unicità del token a livello DB, prevenendo collisioni in scenari estremi (probabilità astronomicamente bassa ma possibile). Crea anche l'indice per la ricerca rapida del token al momento del reset. |
| `reset_token_expiry datetime DEFAULT NULL` | Scadenza del token reset. Il codice controlla `reset_token_expiry > NOW()` prima di accettare il reset. `NULL` quando non c'è reset attivo. |
| `email_verification_token varchar(100) DEFAULT NULL` | Token per la verifica dell'email al momento della registrazione. `NULL` dopo la verifica (il campo viene azzerato). |
| `email_verification_token_expiry datetime DEFAULT NULL` | Scadenza del token di verifica. La costante `VERIFICATION_TOKEN_EXPIRY_HOURS = 24` in `app_config.php` e il testo dell'email promettevano scadenza a 24h, ma senza questa colonna il controllo non era mai eseguito. Ora il codice può implementare `AND email_verification_token_expiry > NOW()` nella query di verifica. |
| `KEY idx_ruolo` | Indice sul ruolo per query come `WHERE ruolo = 'promoter'` (lista promoter in admin). Non necessario per il login (che usa `Email`), ma utile per le dashboard di gestione. |
| `utenti` dichiarata seconda | Quasi tutte le tabelle hanno FK verso `utenti`. Dichiararla subito dopo `tipo` è corretto nell'ordine di dipendenze. |

---

## `locations`

```sql
DROP TABLE IF EXISTS `locations`;
CREATE TABLE `locations` (
  `id`        int NOT NULL AUTO_INCREMENT,
  `Nome`      varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Indirizzo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Citta`     varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `CAP`       varchar(10)  COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Regione`   varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Capienza`  int DEFAULT NULL,
  `Lat`       decimal(10,8) DEFAULT NULL,
  `Lng`       decimal(11,8) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_citta` (`Citta`),
  CONSTRAINT `locations_chk_1` CHECK (`Capienza` IS NULL OR `Capienza` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

| Riga | Commento |
|------|----------|
| `Nome varchar(255) NOT NULL` | Nome del venue. `NOT NULL` corretto: una location senza nome è inutilizzabile. |
| `Indirizzo varchar(255) DEFAULT NULL` | Opzionale: alcuni venue hanno solo un nome comune (es. "Arena di Verona"). |
| `Citta varchar(100) NOT NULL` | Resa obbligatoria rispetto all'originale. La città è il dato geografico minimo: senza di essa l'indice `idx_citta` e i filtri per area geografica sarebbero inutilizzabili. |
| `CAP varchar(10) DEFAULT NULL` | `VARCHAR` e non `INT`: i CAP italiani iniziano con zero (es. `01100`), che un intero troncherebbe. `10` caratteri coprono anche formati internazionali (es. UK `SW1A 2AA`). |
| `Regione varchar(100) DEFAULT NULL` | Opzionale. Utile per raggruppamenti geografici ma non sempre disponibile. |
| `Capienza int DEFAULT NULL` | `NULL` significa "capienza non specificata". Il `DEFAULT 0` originale era ambiguo. Il `CHECK` impedisce valori esplicitamente inseriti che siano zero o negativi. |
| `Lat decimal(10,8) DEFAULT NULL` | Latitudine in gradi decimali. `DECIMAL(10,8)` fornisce precisione di ~1.1 mm a livello del suolo: più che sufficiente per localizzare un edificio. Range: -90.00000000 a +90.00000000. `NULL` se le coordinate non sono note. |
| `Lng decimal(11,8) DEFAULT NULL` | Longitudine in gradi decimali. `DECIMAL(11,8)` con 11 cifre totali per coprire il range -180 a +180 (3 cifre intere + segno). |
| `KEY idx_citta` | Indice sulla città per filtri geografici (`WHERE Citta = 'Milano'`). Utile anche per l'autocomplete nella ricerca di location. |
| `CHECK (Capienza IS NULL OR Capienza > 0)` | Ammette `NULL` (non specificata) ma impedisce 0 o valori negativi se il campo è valorizzato. |

---

## `manifestazioni`

```sql
DROP TABLE IF EXISTS `manifestazioni`;
CREATE TABLE `manifestazioni` (
  `id`          int NOT NULL AUTO_INCREMENT,
  `Nome`        varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Descrizione` text COLLATE utf8mb4_general_ci,
  `DataInizio`  date DEFAULT NULL,
  `DataFine`    date DEFAULT NULL,
  `Immagine`    varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_date` (`DataInizio`,`DataFine`),
  CONSTRAINT `manifestazioni_chk_1` CHECK (`DataFine` IS NULL OR `DataFine` >= `DataInizio`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

| Riga | Commento |
|------|----------|
| `Nome varchar(255) NOT NULL` | Nome del festival/tour/rassegna. Obbligatorio. |
| `Descrizione text DEFAULT NULL` | Campo libero per la descrizione della manifestazione. `TEXT` supporta fino a 65.535 byte, adeguato per testi descrittivi lunghi. `NULL` ammesso: la descrizione è opzionale. |
| `DataInizio`, `DataFine date DEFAULT NULL` | Tipo `DATE` (senza ora) corretto per date di manifestazioni. Entrambi opzionali: alcuni contenitori (es. stagioni teatrali) non hanno date fisse. |
| `Immagine varchar(500) DEFAULT NULL` | Path o URL dell'immagine copertina della manifestazione. Aggiunto per coerenza con `eventi.Immagine`. `500` caratteri per URL potenzialmente lunghi (CDN con query string). |
| `KEY idx_date (DataInizio, DataFine)` | Indice composto per query su range temporali: "manifestazioni in corso oggi" (`DataInizio <= NOW() AND DataFine >= NOW()`). L'indice composto serve entrambe le condizioni. |
| `CHECK (DataFine IS NULL OR DataFine >= DataInizio)` | Impedisce date invertite. `DataFine IS NULL` permette manifestazioni con fine non definita. Validazione a livello DB, non solo applicativo. |

---

## `settori`

```sql
DROP TABLE IF EXISTS `settori`;
CREATE TABLE `settori` (
  `id`                   int NOT NULL AUTO_INCREMENT,
  `Nome`                 varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `NumFile`              int DEFAULT NULL,
  `PostiPerFila`         int DEFAULT NULL,
  `idLocation`           int NOT NULL,
  `MoltiplicatorePrezzo` decimal(5,2) DEFAULT '1.00',
  `PostiTotali`          int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `idx_location` (`idLocation`),
  CONSTRAINT `settori_ibfk_1` FOREIGN KEY (`idLocation`) REFERENCES `locations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `settori_chk_1` CHECK (`MoltiplicatorePrezzo` > 0),
  CONSTRAINT `settori_chk_2` CHECK (`PostiTotali` >= 0)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

| Riga | Commento |
|------|----------|
| `Nome varchar(100) NOT NULL` | Nome del settore (es. `Platea`, `Tribuna Nord`, `VIP`). Obbligatorio. |
| `NumFile int DEFAULT NULL` | Numero di file del settore (es. 20 file dalla A alla T). `NULL` per settori non numerati (es. prato libero). Rinominato dall'ambiguo `Fila` originale. |
| `PostiPerFila int DEFAULT NULL` | Numero di posti per ciascuna fila (es. 30 posti per fila). `NULL` per settori non numerati. Rinominato dall'ambiguo `Posto` originale. Insieme a `NumFile` definisce la griglia del settore: `NumFile × PostiPerFila = PostiTotali`. |
| `idLocation int NOT NULL` | Ogni settore appartiene a una location. `NOT NULL` corretto. |
| `MoltiplicatorePrezzo decimal(5,2) DEFAULT '1.00'` | Fattore moltiplicativo applicato al prezzo base dell'evento. `1.00` = prezzo invariato, `1.50` = +50%, `2.00` = raddoppio. `DECIMAL(5,2)` permette valori fino a `999.99`. |
| `PostiTotali int NOT NULL DEFAULT '0'` | Capienza fissa del settore. Rinominato da `PostiDisponibili` che non veniva mai aggiornato nel codice e rappresentava di fatto una capienza statica. La disponibilità reale per ogni evento si calcola: `eventisettori.PostiDisponibili` (inizializzato da questo valore e decrementato a ogni vendita). |
| `KEY idx_location` | Indice per `WHERE idLocation = ?`: recupera tutti i settori di una location (usato nella pagina di gestione location e nel form creazione evento). |
| `ON DELETE RESTRICT` su `idLocation` | Una location con settori non può essere eliminata direttamente. Il promoter deve prima gestire i settori (e i biglietti correlati). Più sicuro del `CASCADE` originale. |
| `CHECK (MoltiplicatorePrezzo > 0)` | Strettamente maggiore di zero: un moltiplicatore di 0 azzererebbe tutti i prezzi. |
| `CHECK (PostiTotali >= 0)` | Ammette 0 (settore con capienza non ancora definita) ma impedisce valori negativi. |

---

## `intrattenitori`

```sql
DROP TABLE IF EXISTS `intrattenitori`;
CREATE TABLE `intrattenitori` (
  `id`        int NOT NULL AUTO_INCREMENT,
  `Nome`      varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Categoria` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_categoria` (`Categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

| Riga | Commento |
|------|----------|
| Nome tabella `intrattenitori` | Rinominato da `intrattenitore` (singolare) per coerenza con tutte le altre tabelle del progetto che usano il plurale. |
| `Nome varchar(255) NOT NULL` | Nome dell'artista o della band. `255` caratteri per nomi compositi o band con nomi lunghi. `NOT NULL` corretto. |
| `Categoria varchar(100) DEFAULT NULL` | Tipologia artistica (es. `band rock`, `DJ`, `comico`, `orchestra`). `NULL` ammesso. Rimane stringa libera: la varietà di categorie artistiche rende difficile un ENUM esaustivo. |
| `KEY idx_categoria` | Indice per filtrare intrattenitori per categoria (es. "tutti i DJ" nella ricerca artisti). |

---

## `eventi`

```sql
DROP TABLE IF EXISTS `eventi`;
CREATE TABLE `eventi` (
  `id`               int NOT NULL AUTO_INCREMENT,
  `Nome`             varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Data`             date NOT NULL,
  `OraI`             time DEFAULT NULL,
  `OraF`             time DEFAULT NULL,
  `Programma`        text COLLATE utf8mb4_general_ci,
  `PrezzoNoMod`      decimal(10,2) NOT NULL,
  `idLocation`       int NOT NULL,
  `idManifestazione` int DEFAULT NULL,
  `Immagine`         varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Categoria`        enum('concerti','teatro','sport','comedy','cinema','famiglia','eventi') COLLATE utf8mb4_general_ci DEFAULT 'eventi',
  PRIMARY KEY (`id`),
  KEY `idx_data` (`Data`),
  KEY `idx_location` (`idLocation`),
  KEY `idx_manifestazione` (`idManifestazione`),
  KEY `idx_categoria` (`Categoria`),
  CONSTRAINT `eventi_ibfk_1` FOREIGN KEY (`idLocation`) REFERENCES `locations` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `eventi_ibfk_2` FOREIGN KEY (`idManifestazione`) REFERENCES `manifestazioni` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eventi_chk_1` CHECK (`PrezzoNoMod` >= 0),
  CONSTRAINT `eventi_chk_2` CHECK (`OraF` IS NULL OR `OraF` > `OraI`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

| Riga | Commento |
|------|----------|
| `Nome varchar(255) NOT NULL` | Titolo dell'evento. Obbligatorio. `255` per titoli compositi. |
| `Data date NOT NULL` | Data dell'evento. Tipo `DATE` corretto (senza ora, che è separata). `NOT NULL`: un evento deve avere sempre una data. |
| `OraI`, `OraF time DEFAULT NULL` | Ora di inizio e fine. `NULL` per eventi "tutto il giorno" o senza orario definito. Tipo `TIME` corretto per orari intra-day. |
| `Programma text DEFAULT NULL` | Descrizione testuale del programma dell'evento. `NULL` ammesso. |
| `PrezzoNoMod decimal(10,2) NOT NULL` | Prezzo base dell'evento prima di applicare i modificatori di tipo e settore. `DECIMAL` per precisione monetaria. `NOT NULL` corretto: il prezzo è sempre definito (può essere 0 per eventi gratuiti). |
| `idLocation int NOT NULL` | FK obbligatoria: ogni evento ha una location. |
| `idManifestazione int DEFAULT NULL` | FK opzionale: un evento può essere standalone o parte di una manifestazione. `NULL` per eventi indipendenti. |
| `Immagine varchar(500) DEFAULT NULL` | Path dell'immagine copertina. `500` per URL con query string. `NULL` se non caricata. |
| `Categoria enum(...)` | Convertito da `varchar` libero a `ENUM` con i valori effettivamente usati nella homepage (concerti, teatro, sport, comedy, cinema, famiglia, eventi). L'ENUM impedisce valori inconsistenti e occupa 1-2 byte vs ~10 della stringa. `DEFAULT 'eventi'` come categoria generica. |
| `KEY idx_data` | Indice sulla data per query temporali: eventi futuri, eventi del mese, prossimi eventi. Fondamentale per la homepage e la lista eventi ordinata per data. |
| `KEY idx_location` | Per recuperare tutti gli eventi di una location. Usato anche per il `JOIN` con `locations`. |
| `KEY idx_manifestazione` | Per recuperare tutti gli eventi di una manifestazione. |
| `KEY idx_categoria` | Per il filtro per categoria in homepage e lista eventi. Su `ENUM` l'indice è particolarmente efficiente. |
| `ON DELETE RESTRICT` su `idLocation` | Una location non può essere eliminata se ha eventi. Impedisce la perdita accidentale di dati storici o la cancellazione di eventi con biglietti venduti. Era `CASCADE` nell'originale: eliminare una location avrebbe cancellato tutti i suoi eventi e, a cascata, tutti i biglietti. |
| `ON DELETE SET NULL` su `idManifestazione` | Eliminare una manifestazione non cancella gli eventi: li rende semplicemente standalone (`idManifestazione = NULL`). Comportamento corretto e invariato dall'originale. |
| `CHECK (PrezzoNoMod >= 0)` | Ammette eventi gratuiti (prezzo 0) ma impedisce prezzi negativi. |
| `CHECK (OraF IS NULL OR OraF > OraI)` | Impedisce che l'ora di fine sia uguale o antecedente all'ora di inizio. `OraF IS NULL` permette eventi senza ora di fine definita. |

---

## `biglietti`

```sql
DROP TABLE IF EXISTS `biglietti`;
CREATE TABLE `biglietti` (
  `id`           int NOT NULL AUTO_INCREMENT,
  `Nome`         varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Cognome`      varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Sesso`        enum('M','F','Altro') COLLATE utf8mb4_general_ci DEFAULT 'Altro',
  `idEvento`     int NOT NULL,
  `idTipo`       int NOT NULL DEFAULT '1',
  `Stato`        enum('carrello','acquistato','validato') COLLATE utf8mb4_general_ci DEFAULT 'carrello',
  `idUtente`     int DEFAULT NULL,
  `DataCarrello` datetime DEFAULT CURRENT_TIMESTAMP,
  `DataAcquisto` datetime DEFAULT NULL,
  `QRCode`       varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `QRCode` (`QRCode`),
  KEY `idx_stato` (`Stato`),
  KEY `idx_utente_stato` (`idUtente`,`Stato`),
  KEY `idx_evento_stato` (`idEvento`,`Stato`),
  CONSTRAINT `biglietti_ibfk_1` FOREIGN KEY (`idEvento`) REFERENCES `eventi` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `biglietti_ibfk_2` FOREIGN KEY (`idUtente`) REFERENCES `utenti` (`id`) ON DELETE SET NULL,
  CONSTRAINT `biglietti_ibfk_3` FOREIGN KEY (`idTipo`) REFERENCES `tipo` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

| Riga | Commento |
|------|----------|
| `Nome`, `Cognome varchar(100) DEFAULT NULL` | Intestatario del biglietto. `NULL` finché il biglietto è in carrello: l'intestazione viene compilata al checkout. Permette acquisti per conto terzi (il titolare dell'account compra biglietti per altre persone). |
| `Sesso enum('M','F','Altro') DEFAULT 'Altro'` | Campo demografico opzionale per statistiche interne. `DEFAULT 'Altro'` è la scelta più inclusiva. |
| `idEvento int NOT NULL` | FK obbligatoria: ogni biglietto appartiene a un evento specifico. |
| `idTipo int NOT NULL DEFAULT '1'` | FK verso `tipo.id`. Sostituisce il vecchio `idClasse varchar` che non aveva vincolo referenziale. `DEFAULT '1'` presuppone che il tipo Standard abbia `id=1`. Garantisce a livello DB che ogni biglietto abbia un tipo valido: un `INSERT` con un `idTipo` inesistente genera errore. |
| `Stato enum('carrello','acquistato','validato') DEFAULT 'carrello'` | Macchina a stati del ciclo di vita del biglietto. `DEFAULT 'carrello'` corretto: ogni biglietto nasce nel carrello. La transizione da `carrello` a `acquistato` avviene al checkout; da `acquistato` a `validato` all'ingresso dell'evento. |
| `idUtente int DEFAULT NULL` | `NULL` ammesso per due motivi: 1) biglietti anonimi in carrello, 2) `ON DELETE SET NULL` — se l'utente cancella l'account, il biglietto rimane per la storicità degli ordini. |
| `DataCarrello datetime DEFAULT CURRENT_TIMESTAMP` | Timestamp di aggiunta al carrello. Usato dal cron job per pulire i carrelli abbandonati dopo 24h. |
| `DataAcquisto datetime DEFAULT NULL` | Timestamp del momento del checkout. `NULL` finché non acquistato. Aggiunto rispetto all'originale: permette di sapere esattamente quando è avvenuta la transazione senza interrogare la tabella `ordini`. |
| `QRCode varchar(64) DEFAULT NULL` | Codice univoco per la validazione all'ingresso, generato al momento dell'acquisto. `64` caratteri per hash SHA-256 (64 hex digits). `NULL` finché non acquistato. Ridotto da `varchar(255)` — il codice non può essere più lungo di 64 caratteri nel formato attuale. |
| `UNIQUE KEY QRCode` | Impedisce duplicati. Due biglietti con lo stesso QR code consentirebbero a una persona di entrare due volte allo stesso evento. |
| `KEY idx_stato` | Indice semplice su `Stato` per query che filtrano solo per stato (es. tutti i biglietti in carrello nel cron di pulizia). |
| `KEY idx_utente_stato (idUtente, Stato)` | Indice composto. La query più frequente è `WHERE idUtente = ? AND Stato = 'acquistato'` (biglietti dell'utente). L'indice composto la serve con un singolo range scan, più efficiente di due indici separati. |
| `KEY idx_evento_stato (idEvento, Stato)` | Analogo al precedente per `WHERE idEvento = ? AND Stato = 'acquistato'` (biglietti venduti per un evento, usato per statistiche e validazione). |
| `ON DELETE RESTRICT` su `idEvento` | Un evento non può essere eliminato se ha biglietti associati (in qualunque stato). Era `CASCADE` nell'originale: eliminare un evento cancellava silenziosamente tutti i biglietti acquistati, inclusi quelli già pagati. `RESTRICT` forza il codice a verificare e gestire esplicitamente la situazione. |
| `ON DELETE SET NULL` su `idUtente` | Coerente con la scelta di permettere `idUtente NULL`: se l'utente viene eliminato, il biglietto rimane per la storicità degli ordini. |
| `ON DELETE RESTRICT` su `idTipo` | Un tipo di biglietto non può essere eliminato se ci sono biglietti che vi fanno riferimento. Impedisce di rompere la storicità dei prezzi. |

---

## `ordini`

```sql
DROP TABLE IF EXISTS `ordini`;
CREATE TABLE `ordini` (
  `id`              int NOT NULL AUTO_INCREMENT,
  `idUtente`        int NOT NULL,
  `MetodoPagamento` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `DataOrdine`      datetime DEFAULT CURRENT_TIMESTAMP,
  `Totale`          decimal(10,2) NOT NULL DEFAULT '0.00',
  `stato`           enum('pending','completato','rimborsato') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `idx_data` (`DataOrdine`),
  KEY `idx_utente` (`idUtente`),
  CONSTRAINT `ordini_ibfk_1` FOREIGN KEY (`idUtente`) REFERENCES `utenti` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ordini_chk_1` CHECK (`Totale` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

| Riga | Commento |
|------|----------|
| `idUtente int NOT NULL` | FK diretta verso l'utente che ha effettuato l'ordine. Sostituisce la tabella `utente_ordini` M:N che nel codice era sempre usata come relazione 1:1. Semplifica ogni query "ordini di un utente" eliminando un JOIN. `NOT NULL` corretto: ogni ordine ha sempre un proprietario. |
| `MetodoPagamento varchar(50) DEFAULT NULL` | Metodo di pagamento (es. `carta`, `paypal`). `NULL` finché l'ordine non è completato. |
| `DataOrdine datetime DEFAULT CURRENT_TIMESTAMP` | Timestamp automatico alla creazione dell'ordine. |
| `Totale decimal(10,2) NOT NULL DEFAULT '0.00'` | Totale dell'ordine in euro. `DECIMAL` per precisione monetaria. `NOT NULL`: il totale è sempre definito (0.00 per ordini gratuiti). |
| `stato enum('pending','completato','rimborsato') DEFAULT 'pending'` | Ciclo di vita dell'ordine. `pending` mentre si sta processando il pagamento, `completato` dopo la conferma, `rimborsato` in caso di cancellazione con rimborso. Aggiunto rispetto all'originale che non tracciava lo stato. |
| `KEY idx_data` | Per query sullo storico ordini per periodo. |
| `KEY idx_utente` | Per `WHERE idUtente = ?`: storico ordini di un utente. Indice fondamentale dato che questa query è frequente (pagina "i miei ordini"). |
| `ON DELETE RESTRICT` su `idUtente` | Un utente con ordini storici non può essere eliminato direttamente. Protegge lo storico finanziario. |
| `CHECK (Totale >= 0)` | Impedisce totali negativi. |

---

## `collaboratorieventi`

```sql
DROP TABLE IF EXISTS `collaboratorieventi`;
CREATE TABLE `collaboratorieventi` (
  `id`           int NOT NULL AUTO_INCREMENT,
  `idEvento`     int NOT NULL,
  `idUtente`     int NOT NULL,
  `invitato_da`  int DEFAULT NULL,
  `status`       enum('pending','accepted','declined','revoked') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `token`        varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `created_at`   datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_collaborazione` (`idEvento`,`idUtente`),
  UNIQUE KEY `token` (`token`),
  KEY `idUtente` (`idUtente`),
  KEY `invitato_da` (`invitato_da`),
  KEY `idx_status` (`status`),
  CONSTRAINT `collaboratorieventi_ibfk_1` FOREIGN KEY (`idEvento`) REFERENCES `eventi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `collaboratorieventi_ibfk_2` FOREIGN KEY (`idUtente`) REFERENCES `utenti` (`id`) ON DELETE CASCADE,
  CONSTRAINT `collaboratorieventi_ibfk_3` FOREIGN KEY (`invitato_da`) REFERENCES `utenti` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

| Riga | Commento |
|------|----------|
| `id int AUTO_INCREMENT` | PK surrogata. La coppia `(idEvento, idUtente)` è già unica (vedi `unique_collaborazione`), ma la PK surrogata semplifica eventuali riferimenti esterni. |
| `idEvento`, `idUtente int NOT NULL` | FK obbligatorie: ogni collaborazione lega un utente a un evento specifico. |
| `invitato_da int DEFAULT NULL` | Chi ha inviato l'invito. Ora `DEFAULT NULL` (era `NOT NULL`): questo permette `ON DELETE SET NULL` — se il promoter che ha invitato viene eliminato, il record della collaborazione non viene perso ma `invitato_da` diventa `NULL`. |
| `status enum('pending','accepted','declined','revoked') DEFAULT 'pending'` | Macchina a stati dell'invito. Aggiunto `'revoked'`: il promoter può ritirare un invito inviato senza eliminare il record, preservando l'audit trail. |
| `token varchar(100) DEFAULT NULL` | Token inviato via email per accettare l'invito senza login. `NULL` per inviti diretti in-app. |
| `token_expiry datetime DEFAULT NULL` | Scadenza del link di invito. Analogo al `reset_token_expiry` degli utenti: un link di invito non deve essere valido per sempre. |
| `created_at`, `updated_at datetime` | `created_at`: quando è stato creato l'invito. `updated_at` con `ON UPDATE CURRENT_TIMESTAMP`: si aggiorna automaticamente quando lo stato cambia (es. da `pending` a `accepted`), permettendo di sapere quando è avvenuta la risposta. |
| `UNIQUE KEY unique_collaborazione (idEvento, idUtente)` | Un utente può essere collaboratore di un evento al massimo una volta. Impedisce inviti duplicati. |
| `UNIQUE KEY token` | Il token è univoco. L'indice implicito permette la ricerca rapida per token (`WHERE token = ?`). L'indice ridondante `idx_token` dell'originale è stato rimosso. |
| `KEY idx_status` | Per query come `WHERE status = 'pending'` (inviti in attesa di risposta). |
| `ON DELETE CASCADE` su `idEvento` e `idUtente` | Se l'evento o il collaboratore vengono eliminati, la collaborazione non ha più senso e viene rimossa. Corretto. |
| `ON DELETE SET NULL` su `invitato_da` | Preserva la collaborazione anche se chi ha invitato viene eliminato. Il record storico rimane leggibile. |

---

## `creatorieventi`

```sql
DROP TABLE IF EXISTS `creatorieventi`;
CREATE TABLE `creatorieventi` (
  `idEvento`   int NOT NULL,
  `idUtente`   int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idEvento`,`idUtente`),
  KEY `idUtente` (`idUtente`),
  CONSTRAINT `creatorieventi_ibfk_1` FOREIGN KEY (`idEvento`) REFERENCES `eventi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `creatorieventi_ibfk_2` FOREIGN KEY (`idUtente`) REFERENCES `utenti` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

| Riga | Commento |
|------|----------|
| PK composta `(idEvento, idUtente)` | Tabella di relazione M:N pura. La PK composta è corretta: non serve PK surrogata perché non ci sono riferimenti esterni a questa tabella. |
| `created_at` | Traccia quando il promoter ha assunto la paternità dell'evento. |
| `KEY idUtente` | Indice per `WHERE idUtente = ?`: recupera tutti gli eventi creati da un promoter (pagina "i miei eventi"). |
| `ON DELETE CASCADE` su `idEvento` | Se l'evento viene eliminato (dopo aver gestito i biglietti), la paternità scompare automaticamente. Corretto. |
| `ON DELETE RESTRICT` su `idUtente` | Modifica rispetto all'originale `CASCADE`. Un utente che ha creato eventi non può essere eliminato direttamente: bisogna prima trasferire la proprietà degli eventi o eliminarli. Impedisce eventi orfani. |

---

## `creatorilocations`

```sql
DROP TABLE IF EXISTS `creatorilocations`;
CREATE TABLE `creatorilocations` (
  `idLocation` int NOT NULL,
  `idUtente`   int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idLocation`,`idUtente`),
  KEY `idUtente` (`idUtente`),
  CONSTRAINT `creatorilocations_ibfk_1` FOREIGN KEY (`idLocation`) REFERENCES `locations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `creatorilocations_ibfk_2` FOREIGN KEY (`idUtente`) REFERENCES `utenti` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

| Riga | Commento |
|------|----------|
| Struttura identica a `creatorieventi` | Stessa logica applicata alle locations. |
| `ON DELETE CASCADE` su `idLocation` | Se la location viene eliminata (dopo aver gestito i settori ed eventi), la paternità scompare. |
| `ON DELETE RESTRICT` su `idUtente` | Stessa logica di `creatorieventi`: impedisce eliminazione di utenti con locations attive. |

---

## `creatorimanifestazioni`

```sql
DROP TABLE IF EXISTS `creatorimanifestazioni`;
CREATE TABLE `creatorimanifestazioni` (
  `idManifestazione` int NOT NULL,
  `idUtente`         int NOT NULL,
  `created_at`       datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idManifestazione`,`idUtente`),
  KEY `idUtente` (`idUtente`),
  CONSTRAINT `creatorimanifestazioni_ibfk_1` FOREIGN KEY (`idManifestazione`) REFERENCES `manifestazioni` (`id`) ON DELETE CASCADE,
  CONSTRAINT `creatorimanifestazioni_ibfk_2` FOREIGN KEY (`idUtente`) REFERENCES `utenti` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

| Riga | Commento |
|------|----------|
| Struttura identica alle altre tabelle `creatori*` | Pattern uniforme per la gestione della paternità delle entità. |
| `ON DELETE RESTRICT` su `idUtente` | Coerente con le altre tabelle `creatori*`. |

---

## `eventisettori`

```sql
DROP TABLE IF EXISTS `eventisettori`;
CREATE TABLE `eventisettori` (
  `idEvento`        int NOT NULL,
  `idSettore`       int NOT NULL,
  `PostiDisponibili` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`idEvento`,`idSettore`),
  KEY `idSettore` (`idSettore`),
  CONSTRAINT `eventisettori_ibfk_1` FOREIGN KEY (`idEvento`) REFERENCES `eventi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `eventisettori_ibfk_2` FOREIGN KEY (`idSettore`) REFERENCES `settori` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `eventisettori_chk_1` CHECK (`PostiDisponibili` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

| Riga | Commento |
|------|----------|
| PK composta `(idEvento, idSettore)` | Un settore può essere usato in più eventi (la stessa platea per concerti diversi), e un evento può usare più settori. Relazione M:N corretta. |
| `PostiDisponibili int NOT NULL DEFAULT '0'` | Dato chiave: quanti posti rimangono disponibili in questo settore per questo specifico evento. Spostato da `settori.PostiDisponibili` (globale, mai aggiornato) a qui (per-evento). Va inizializzato a `settori.PostiTotali` quando il settore viene associato all'evento, e decrementato/incrementato transazionalmente ad ogni acquisto/cancellazione. `NOT NULL` con `DEFAULT 0` perché il valore è sempre definito. |
| `CHECK (PostiDisponibili >= 0)` | Impedisce posti negativi a livello DB: un ulteriore barrier rispetto alla logica applicativa. |
| `ON DELETE CASCADE` su `idEvento` | Se l'evento viene eliminato, le associazioni settore-evento scompaiono. Corretto (l'evento porta con sé la sua configurazione). |
| `ON DELETE RESTRICT` su `idSettore` | Un settore non può essere eliminato se è ancora associato a eventi attivi. Era `CASCADE` nell'originale. |

---

## `evento_intrattenitori`

```sql
DROP TABLE IF EXISTS `evento_intrattenitori`;
CREATE TABLE `evento_intrattenitori` (
  `idEvento`         int NOT NULL,
  `idIntrattenitore` int NOT NULL,
  `OraInizio`        time DEFAULT NULL,
  `OraFine`          time DEFAULT NULL,
  `Ordine`           tinyint DEFAULT NULL,
  PRIMARY KEY (`idEvento`,`idIntrattenitore`),
  KEY `idIntrattenitore` (`idIntrattenitore`),
  CONSTRAINT `evento_intrattenitori_ibfk_1` FOREIGN KEY (`idEvento`) REFERENCES `eventi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `evento_intrattenitori_ibfk_2` FOREIGN KEY (`idIntrattenitore`) REFERENCES `intrattenitori` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

| Riga | Commento |
|------|----------|
| Nome tabella `evento_intrattenitori` | Aggiornato per riflettere la rinomina di `intrattenitore` → `intrattenitori`. |
| `OraInizio`, `OraFine time DEFAULT NULL` | Fasce orarie dell'esibizione dell'artista nell'evento (es. opening act 20:00-21:00, headliner 21:30-23:30). `NULL` se l'orario non è pianificato. Sostituisce il campo testuale libero `eventi.Programma` per la struttura della scaletta. |
| `Ordine tinyint DEFAULT NULL` | Posizione nella scaletta (1 = primo ad esibirsi, 2 = secondo…). Permette di ordinare gli artisti indipendentemente dagli orari. `tinyint` (1 byte) perché difficilmente un evento ha più di 127 artisti. `NULL` se l'ordine non è definito. |
| `ON DELETE CASCADE` su entrambi | Se l'evento o l'artista vengono eliminati, la relazione scompare automaticamente. Corretto. |

---

## `notifiche`

```sql
DROP TABLE IF EXISTS `notifiche`;
CREATE TABLE `notifiche` (
  `id`              int NOT NULL AUTO_INCREMENT,
  `tipo`            varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `destinatario_id` int NOT NULL,
  `mittente_id`     int DEFAULT NULL,
  `oggetto`         varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `messaggio`       text COLLATE utf8mb4_general_ci,
  `email_inviata`   tinyint(1) DEFAULT '0',
  `letta`           tinyint(1) DEFAULT '0',
  `letta_at`        datetime DEFAULT NULL,
  `metadata`        json DEFAULT NULL,
  `created_at`      datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `mittente_id` (`mittente_id`),
  KEY `idx_destinatario_letta` (`destinatario_id`,`letta`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `notifiche_ibfk_1` FOREIGN KEY (`destinatario_id`) REFERENCES `utenti` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notifiche_ibfk_2` FOREIGN KEY (`mittente_id`) REFERENCES `utenti` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

| Riga | Commento |
|------|----------|
| `tipo varchar(50) NOT NULL` | Tipologia della notifica (es. `invito_collaborazione`, `biglietto_acquistato`, `nuova_recensione`). Stringa libera: la varietà di tipi rende un ENUM difficile da mantenere nel tempo. |
| `destinatario_id int NOT NULL` | Chi riceve la notifica. `NOT NULL`: ogni notifica deve avere un destinatario. |
| `mittente_id int DEFAULT NULL` | Chi ha generato la notifica. `NULL` per notifiche di sistema (es. conferma ordine automatica). |
| `oggetto varchar(255) DEFAULT NULL` | Titolo/oggetto della notifica. Usato anche come subject dell'email corrispondente. |
| `messaggio text DEFAULT NULL` | Corpo della notifica. |
| `email_inviata tinyint(1) DEFAULT '0'` | Flag: `1` se l'email corrispondente è già stata inviata. Usato per evitare invii doppi in caso di retry e per sistemi di accodamento email. |
| `letta tinyint(1) DEFAULT '0'` | Flag: `1` se l'utente ha visualizzato la notifica. |
| `letta_at datetime DEFAULT NULL` | Timestamp di quando la notifica è stata letta. `NULL` finché non letta. Utile per analytics sull'engagement (tempo medio di lettura, tasso di apertura). |
| `metadata json DEFAULT NULL` | Dati aggiuntivi specifici del tipo di notifica (es. `{"idEvento": 42, "nomeEvento": "Concerto X"}`). Tipo `JSON` nativo di MySQL (disponibile da 5.7): garantisce validità sintattica e permette query su campi specifici con l'operatore `->`. Più robusto del `TEXT` originale. `NULL` se non necessario. |
| `KEY idx_destinatario_letta (destinatario_id, letta)` | Indice composto che ottimizza la query più frequente: `WHERE destinatario_id = ? AND letta = 0` (notifiche non lette di un utente, usata per il badge del campanellino). Più efficiente dei due indici separati dell'originale. |
| `KEY idx_created` | Per query sullo storico notifiche ordinate per data. |
| `ON DELETE CASCADE` su `destinatario_id` | Se l'utente viene eliminato, le sue notifiche vengono rimosse. Corretto: non c'è motivo di conservarle. |
| `ON DELETE SET NULL` su `mittente_id` | Se il mittente viene eliminato, la notifica rimane leggibile dal destinatario ma senza mittente identificato. |

---

## `ordine_biglietti`

```sql
DROP TABLE IF EXISTS `ordine_biglietti`;
CREATE TABLE `ordine_biglietti` (
  `idOrdine`   int NOT NULL,
  `idBiglietto` int NOT NULL,
  PRIMARY KEY (`idOrdine`,`idBiglietto`),
  UNIQUE KEY `idBiglietto` (`idBiglietto`),
  CONSTRAINT `ordine_biglietti_ibfk_1` FOREIGN KEY (`idOrdine`) REFERENCES `ordini` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ordine_biglietti_ibfk_2` FOREIGN KEY (`idBiglietto`) REFERENCES `biglietti` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

| Riga | Commento |
|------|----------|
| PK composta `(idOrdine, idBiglietto)` | Un ordine contiene più biglietti, e la coppia è la PK. |
| `UNIQUE KEY idBiglietto` | Vincolo fondamentale aggiunto rispetto all'originale: un biglietto può appartenere a **un solo ordine**. Senza questo vincolo il DB avrebbe permesso di inserire lo stesso biglietto in due ordini diversi (doppia fatturazione). L'indice implicito dell'UNIQUE KEY sostituisce il vecchio `KEY idBiglietto` non univoco. |
| `ON DELETE CASCADE` su `idOrdine` | Eliminare un ordine rimuove le righe di dettaglio. Corretto. |
| `ON DELETE CASCADE` su `idBiglietto` | Eliminare un biglietto rimuove il riferimento nell'ordine. Coerente con la gestione dei biglietti. |

---

## `recensioni`

```sql
DROP TABLE IF EXISTS `recensioni`;
CREATE TABLE `recensioni` (
  `id`         int NOT NULL AUTO_INCREMENT,
  `idEvento`   int NOT NULL,
  `idUtente`   int DEFAULT NULL,
  `Voto`       tinyint NOT NULL,
  `Commento`   text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_recensione` (`idEvento`,`idUtente`),
  KEY `idUtente` (`idUtente`),
  KEY `idx_evento` (`idEvento`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `recensioni_ibfk_1` FOREIGN KEY (`idEvento`) REFERENCES `eventi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recensioni_ibfk_2` FOREIGN KEY (`idUtente`) REFERENCES `utenti` (`id`) ON DELETE SET NULL,
  CONSTRAINT `recensioni_chk_1` CHECK ((`Voto` between 1 and 5))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

| Riga | Commento |
|------|----------|
| `idEvento int NOT NULL` | Ogni recensione si riferisce a un evento specifico. `NOT NULL` corretto. |
| `idUtente int DEFAULT NULL` | Ora `DEFAULT NULL` (era `NOT NULL`). Permette `ON DELETE SET NULL`: se l'utente cancella l'account, la recensione rimane come feedback anonimo. La media voti dell'evento non crolla. |
| `Voto tinyint NOT NULL` | Ridotto da `int` a `tinyint` (1 byte vs 4): i valori sono 1-5, il risparmio è netto. `NOT NULL` corretto: il voto è obbligatorio, il commento no. |
| `CHECK (Voto BETWEEN 1 AND 5)` | Vincolo a livello DB. Impedisce voti fuori range anche aggirando la validazione PHP. |
| `Commento text DEFAULT NULL` | Commento opzionale. `NULL` ammesso: la recensione può essere solo un voto numerico. |
| `created_at datetime DEFAULT CURRENT_TIMESTAMP` | Timestamp di pubblicazione. |
| `updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | Aggiornato automaticamente a ogni modifica. Permette di verificare se e quando una recensione è stata editata dopo la pubblicazione. Aggiunto rispetto all'originale. |
| `UNIQUE KEY unique_recensione (idEvento, idUtente)` | Un utente può recensire un evento una sola volta. Garantito a livello DB, fondamentale per l'integrità del sistema di rating. |
| `ON DELETE CASCADE` su `idEvento` | Se l'evento viene eliminato, anche le sue recensioni scompaiono. Coerente. |
| `ON DELETE SET NULL` su `idUtente` | La recensione diventa anonima invece di essere eliminata. Il voto contribuisce ancora alla media dell'evento. |

---

## `settore_biglietti`

```sql
DROP TABLE IF EXISTS `settore_biglietti`;
CREATE TABLE `settore_biglietti` (
  `idBiglietto` int NOT NULL,
  `idSettore`   int NOT NULL,
  `Fila`        varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `NumPosto`    int DEFAULT NULL,
  PRIMARY KEY (`idBiglietto`),
  UNIQUE KEY `unique_posto` (`idSettore`,`Fila`,`NumPosto`),
  CONSTRAINT `settore_biglietti_ibfk_1` FOREIGN KEY (`idBiglietto`) REFERENCES `biglietti` (`id`) ON DELETE CASCADE,
  CONSTRAINT `settore_biglietti_ibfk_2` FOREIGN KEY (`idSettore`) REFERENCES `settori` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

| Riga | Commento |
|------|----------|
| `idBiglietto int NOT NULL` — PK semplice | La PK è solo `idBiglietto`, non la coppia. Questo forza la relazione 1:1: ogni biglietto ha esattamente un posto assegnato. Corretto. |
| `idSettore int NOT NULL` | Il settore in cui si trova il posto. |
| `Fila varchar(10) DEFAULT NULL` | Identificatore della fila (es. `A`, `B`, `12`). `VARCHAR` per coprire sia lettere che numeri. `NULL` per settori senza posti numerati (es. prato). |
| `NumPosto int DEFAULT NULL` | Numero del posto nella fila. `NULL` per settori liberi. |
| `UNIQUE KEY unique_posto (idSettore, Fila, NumPosto)` | Il vincolo più importante di questa tabella. Garantisce che la combinazione settore+fila+numero sia univoca: lo stesso posto fisico non può essere venduto a due persone diverse. Anche se il codice applicativo ha una race condition nella lettura dei posti liberi, il DB rifiuterà il secondo INSERT con un errore di chiave duplicata, che il codice può gestire mostrando un messaggio di "posto non disponibile". |
| `ON DELETE CASCADE` su `idBiglietto` | Se il biglietto viene eliminato (es. cancellazione dal carrello), il posto viene liberato automaticamente. |
| `ON DELETE RESTRICT` su `idSettore` | Un settore con posti assegnati non può essere eliminato. Era `CASCADE` nell'originale. |

---

## Postambolo

```sql
SET FOREIGN_KEY_CHECKS=1;
```

Riabilita i controlli sulle FK. Fondamentale: tutte le relazioni dichiarate nel DDL vengono ora verificate su ogni INSERT, UPDATE e DELETE. Senza questa riga la sessione rimarrebbe senza vincoli referenziali.
