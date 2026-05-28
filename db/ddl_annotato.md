# DDL Annotato — `5cit_eventsmaster` (versione migliorata)

Analisi riga per riga del file `ddl_migliorato.sql`.

---

## Prologo

```sql
DROP DATABASE IF EXISTS `5cit_eventsmaster`;
```
Elimina il database se esiste già. `IF EXISTS` è la variante "safe": senza di esso
MySQL lancerebbe un errore se il db non esiste. Utile per rieseguire lo script
da zero in fase di sviluppo.

```sql
CREATE DATABASE `5cit_eventsmaster` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```
- `DEFAULT CHARSET=utf8mb4`: imposta il charset di default per tutte le tabelle.
  `utf8mb4` (e non `utf8`) è il charset corretto in MySQL: supporta l'intero Unicode
  inclusi emoji e caratteri a 4 byte. `utf8` in MySQL è in realtà `utf8mb3` (solo 3 byte)
  e non copre tutti i caratteri Unicode.
- `COLLATE=utf8mb4_general_ci`: la collation definisce le regole di confronto tra stringhe.
  `ci` = case-insensitive (A = a), `general` = regole semplificate ma veloci.
  Adatta per ricerche testuali in italiano.

```sql
USE `5cit_eventsmaster`;
```
Seleziona il database appena creato come contesto per i successivi `CREATE TABLE`.

```sql
SET NAMES utf8mb4;
```
Forza la connessione client↔server a usare utf8mb4, allineandola al charset del db.
Senza questa riga, i dati con caratteri multi-byte potrebbero essere corrotti in transito.

```sql
SET FOREIGN_KEY_CHECKS=0;
```
**Scelta cruciale**: disabilita temporaneamente i controlli sulle chiavi esterne.
Permette di creare le tabelle in qualsiasi ordine senza dover rispettare la sequenza
"prima la tabella referenziata, poi quella che la referenzia". Viene riabilitato
alla fine con `SET FOREIGN_KEY_CHECKS=1`.

---

## Tabella `tipo`

```sql
CREATE TABLE `tipo` (
```
Contiene le tipologie di biglietto (es. Intero, Ridotto, VIP). Deve essere creata
**prima** di `biglietti` perché questa tabella la referenzia con una FK.

```sql
  `id` int NOT NULL AUTO_INCREMENT,
```
- `int`: intero a 4 byte (range 0–~2 miliardi): più che sufficiente per un catalogo
  di tipologie.
- `NOT NULL`: una PK non può mai essere NULL.
- `AUTO_INCREMENT`: MySQL assegna automaticamente un valore crescente a ogni INSERT,
  senza bisogno di specificarlo nell'applicazione.

```sql
  `nome` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
```
- `varchar(50)`: stringa variabile fino a 50 caratteri. Alloca solo lo spazio
  effettivamente usato (a differenza di `char(50)` che occupa sempre 50 byte).
- `COLLATE` esplicito su ogni colonna: non si affida al default della tabella, rende
  il comportamento esplicito e portabile.

```sql
  `ModificatorePrezzo` decimal(6,2) NOT NULL DEFAULT '0.00',
```
- `decimal(6,2)`: tipo a precisione esatta — 6 cifre totali di cui 2 decimali
  (range max: ±9999.99). Si usa `decimal` e non `float`/`double` perché questi usano
  rappresentazione binaria che introduce errori di arrotondamento: `0.1 + 0.2 ≠ 0.3`.
  Per valori monetari `decimal` è sempre la scelta corretta.
- `DEFAULT '0.00'`: nessuna modifica di prezzo per il tipo standard.

```sql
  PRIMARY KEY (`id`),
```
Dichiara `id` come chiave primaria. Crea automaticamente un indice UNIQUE + NOT NULL
sul campo. Ogni riga è identificata univocamente da questo valore.

```sql
  UNIQUE KEY `nome` (`nome`),
```
Impedisce l'inserimento di tipi con lo stesso nome (es. due righe "VIP"). Il vincolo
è garantito a livello di database, non solo applicativo — resistente a race condition.

```sql
  CONSTRAINT `tipo_chk_1` CHECK (`ModificatorePrezzo` >= 0)
```
- `CONSTRAINT nome`: dà un nome leggibile al vincolo, utile nei messaggi di errore.
- `CHECK (...)`: vincolo di integrità su valori. Supportato nativamente da MySQL 8.0+.
  Il modificatore non può essere negativo (un biglietto non può costare meno di zero).

```sql
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```
- `ENGINE=InnoDB`: motore di storage transazionale di MySQL. Supporta FK, transazioni
  ACID, row-level locking. L'alternativa storica `MyISAM` non supporta FK né transazioni.
- `AUTO_INCREMENT=6`: il prossimo id generato sarà 6 (ci sono già 5 righe nel dump).
- `DEFAULT CHARSET=utf8mb4 COLLATE=...`: fallback per colonne che non specificano
  il charset esplicitamente.

---

## Tabella `utenti`

```sql
CREATE TABLE `utenti` (
```
Deve essere creata **prima** di quasi tutto: locations, manifestazioni, eventi,
biglietti, ordini, notifiche, collaborazioni la referenziano con FK.

```sql
  `ruolo` enum('admin','mod','promoter','user') COLLATE utf8mb4_general_ci DEFAULT 'user',
```
- `ENUM(...)`: tipo che limita i valori ammessi a un insieme fisso. Internamente
  MySQL lo memorizza come intero 1-2 byte, più efficiente di un `varchar`. Garantisce
  che valori non previsti non possano essere inseriti.
- `DEFAULT 'user'`: i nuovi utenti hanno il ruolo minimo automaticamente.

```sql
  `verificato` tinyint(1) DEFAULT '0',
```
Flag booleano per la verifica email. MySQL non ha un tipo `BOOLEAN` nativo: `tinyint(1)`
è la convenzione standard (0 = false, 1 = true). La cifra `(1)` non limita il range del
valore (è un hint di display), ma indica l'uso booleano.

```sql
  `verificato_at` datetime DEFAULT NULL,
```
Timestamp di quando è avvenuta la verifica. `NULL` finché non verificato. Permette
di sapere non solo *se* ma anche *quando* l'account è stato attivato — utile per
audit e per statistiche di conversion del funnel di registrazione.

```sql
  `Avatar` mediumblob DEFAULT NULL,
```
Immagine profilo salvata come binary large object. `mediumblob` supporta fino a 16 MB.
**Scelta progettuale**: salvare blob in DB semplifica i backup (tutto in un posto)
ma appesantisce le query e impedisce la cache HTTP. L'alternativa è salvare solo il
path del file su filesystem.

```sql
  `DataRegistrazione` datetime DEFAULT CURRENT_TIMESTAMP,
```
`CURRENT_TIMESTAMP` come default: MySQL popola il campo automaticamente con la
data/ora del server al momento dell'INSERT. Non serve passarlo dall'applicazione.

```sql
  `reset_token` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
```
Coppia per il flusso di reset password: token casuale univoco inviato via email +
timestamp di scadenza. L'`expiry` impedisce che link datati rimangano validi.

```sql
  `email_verification_token` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email_verification_token_expiry` datetime DEFAULT NULL,
```
Analogo al reset password ma per la verifica email alla registrazione. Separato per
chiarezza semantica: i due flussi si gestiscono indipendentemente.

```sql
  `deleted_at` datetime DEFAULT NULL,
```
**Soft delete**: anziché un `DELETE` fisico, si imposta questo timestamp.
L'utente "sparisce" dalle query normali (filtrate su `deleted_at IS NULL`) ma i dati
rimangono — fondamentale per preservare lo storico di biglietti e ordini senza rompere
le FK, e per eventuale ripristino dell'account.

```sql
  UNIQUE KEY `Email` (`Email`),
  UNIQUE KEY `reset_token` (`reset_token`),
```
- Email unica: impedisce doppia registrazione con lo stesso indirizzo.
- Token reset unico: garantisce che due utenti non abbiano mai lo stesso token attivo
  (falla di sicurezza critica se accadesse).

```sql
  KEY `idx_ruolo` (`ruolo`)
```
Indice semplice sul ruolo: ottimizza query come `WHERE ruolo = 'admin'` usate in ogni
controllo di autorizzazione.

---

## Tabella `locations`

```sql
  `Capienza` int DEFAULT NULL,  -- limite fisico massimo: SUM(settori.PostiTotali) non deve superarlo
```
Capienza totale fisica del venue. `NULL` = non specificata. Il commento inline
documenta la regola di business: la somma dei PostiTotali dei settori non dovrebbe
superare questo valore. È una regola applicativa (non un CHECK db) perché coinvolge
un aggregato su un'altra tabella.

```sql
  `Lat` decimal(10,8) DEFAULT NULL,
  `Lng` decimal(11,8) DEFAULT NULL,
```
Coordinate geografiche. Scelta dei tipi:
- `decimal(10,8)` per Lat: 2 cifre intere (range -90…90) + 8 decimali (~1 mm precisione).
- `decimal(11,8)` per Lng: 3 cifre intere (range -180…180) + 8 decimali.
Si usa `decimal` e non `float` per evitare imprecisioni nelle query geospaziali.

```sql
  CONSTRAINT `locations_chk_1` CHECK (`Capienza` IS NULL OR `Capienza` > 0),
```
Se la capienza è valorizzata deve essere positiva. Il pattern `IS NULL OR condizione`
permette di lasciare il campo vuoto senza violare il vincolo.

```sql
  CONSTRAINT `locations_ibfk_1` FOREIGN KEY (`idCreatore`) REFERENCES `utenti` (`id`) ON DELETE RESTRICT
```
`ON DELETE RESTRICT`: impedisce di cancellare un utente che ha creato almeno una
location. La cancellazione deve essere gestita esplicitamente dall'applicazione
(es. trasferimento di proprietà prima della cancellazione dell'account).

---

## Tabella `manifestazioni`

```sql
  `Descrizione` text COLLATE utf8mb4_general_ci,
```
`text` per contenuto potenzialmente lungo (fino a 65.535 byte). Si preferisce a
`varchar` quando la lunghezza è arbitraria e non si vuole un limite fisso.

```sql
  KEY `idx_date` (`DataInizio`,`DataFine`),
```
Indice composto su entrambe le date. Ottimizza query con filtri su range temporali
(es. "manifestazioni attive oggi"). MySQL può usare il prefisso sinistro dell'indice
composto anche per query su sola `DataInizio`.

```sql
  CONSTRAINT `manifestazioni_chk_1` CHECK (`DataFine` IS NULL OR `DataFine` >= `DataInizio`),
```
Vincolo di coerenza temporale: la fine non può precedere l'inizio. `IS NULL OR` permette
manifestazioni senza data di fine (a tempo indeterminato, es. stagione in corso).

---

## Tabella `settori`

Rappresenta le zone fisiche di una location (Platea, Tribuna Nord, Area VIP…).

```sql
  `NumFile` smallint DEFAULT NULL,
  `PostiPerFila` smallint DEFAULT NULL,
```
`smallint` occupa 2 byte (range 0–65535): sufficiente per righe e posti di qualsiasi
teatro/stadio. Più leggero di `int` (4 byte). Entrambi nullable per settori senza
posti numerati (area standing).

```sql
  `MoltiplicatorePrezzo` decimal(5,2) DEFAULT '1.00',
```
Moltiplicatore applicato al prezzo base dell'evento per questo settore:
- `1.00` = prezzo invariato
- `1.50` = +50% (settore premium)
- `0.80` = -20% (settore ridotto)
Il prezzo finale è: `PrezzoNoMod × MoltiplicatorePrezzo × tipo.ModificatorePrezzo`.

```sql
  `PostiTotali` int NOT NULL DEFAULT '0',
```
Capienza fissa del settore: `NumFile × PostiPerFila` per settori numerati,
oppure la capienza totale per aree standing. Serve come riferimento fisso per
calcolare la disponibilità rimanente. `NOT NULL` perché è un dato strutturale.

```sql
  CONSTRAINT `settori_chk_1` CHECK (`MoltiplicatorePrezzo` > 0),
  CONSTRAINT `settori_chk_2` CHECK (`PostiTotali` >= 0)
```
Due vincoli separati (con nomi distinti) per messaggi di errore più precisi:
- il moltiplicatore deve essere **strettamente positivo** (0 annullerebbe il prezzo)
- i posti totali possono essere zero ma non negativi

---

## Tabella `intrattenitori`

```sql
  KEY `idx_categoria` (`Categoria`)
```
Indice sulla categoria (es. "Band", "Comico") per ricerche filtrate. Tabella
semplice e autonoma: gli intrattenitori esistono indipendentemente dagli eventi
e sono riutilizzabili su più eventi.

---

## Tabella `eventi`

```sql
  `PrezzoNoMod` decimal(7,2) NOT NULL,
```
Prezzo base dell'evento **prima** dei modificatori. Il nome "NoMod" (senza modificatori)
è una scelta semantica esplicita che documenta nel DDL stesso che non è il prezzo finale.

```sql
  `idManifestazione` int DEFAULT NULL,
```
FK nullable: un evento può esistere senza appartenere a una manifestazione
(evento standalone). `DEFAULT NULL` rende il campo opzionale.

```sql
  `Categoria` enum('concerti','teatro','sport','comedy','cinema','famiglia','eventi') ... DEFAULT 'eventi',
```
Enum fisso per le categorie: garantisce coerenza dei dati (niente varianti come
"Concerto" vs "concerti") e ottimizza i filtri. `'eventi'` come default è la
categoria generica/catch-all.

```sql
  KEY `idx_data` (`Data`),
  KEY `idx_location` (`idLocation`),
  KEY `idx_manifestazione` (`idManifestazione`),
  KEY `idx_categoria` (`Categoria`),
  KEY `idx_creatore` (`idCreatore`),
```
Cinque indici separati sulle colonne di filtro più frequenti. Ogni indice
occupa spazio ma accelera le SELECT corrispondenti. La scelta di indici separati
(anziché un indice composto su tutte) è corretta: le query tipiche filtrano
su una sola di queste colonne alla volta.

```sql
  CONSTRAINT `eventi_ibfk_2` FOREIGN KEY (`idManifestazione`) REFERENCES `manifestazioni` (`id`) ON DELETE SET NULL
```
**`ON DELETE SET NULL`**: se si elimina una manifestazione, gli eventi non vengono
cancellati ma diventano standalone (`idManifestazione → NULL`). Preserva lo storico
degli eventi. Contrasta intenzionalmente con il `RESTRICT` usato per location e creatore,
dove la perdita sarebbe più grave.

```sql
  CONSTRAINT `eventi_chk_2` CHECK (`OraF` IS NULL OR `OraF` > `OraI`)
```
Vincolo di coerenza oraria: l'ora di fine deve essere successiva a quella di inizio.
Il pattern `IS NULL OR` permette eventi senza orario preciso.

---

## Tabella `biglietti`

```sql
  `Nome` varchar(100) ... DEFAULT NULL,
  `Cognome` varchar(100) ... DEFAULT NULL,
```
Dati dell'intestatario del biglietto. Può differire dall'utente acquirente (acquisto
per conto terzi, es. regalo). Nullable perché mentre il biglietto è in carrello
l'intestatario potrebbe non essere ancora specificato.

```sql
  `idTipo` int NOT NULL DEFAULT '1',
```
FK intera verso `tipo.id` (a differenza di versioni precedenti dove era una stringa
libera). `DEFAULT '1'` assegna automaticamente il tipo standard (id=1) se non
specificato — garantisce integrità referenziale.

```sql
  `Stato` enum('carrello','acquistato','validato') ... DEFAULT 'carrello',
```
Macchina a stati del biglietto:
- `carrello`: aggiunto ma non pagato
- `acquistato`: pagamento completato
- `validato`: usato all'ingresso (scansione QR)
L'ENUM garantisce che non esistano stati non previsti. Il DEFAULT `carrello` è
corretto: ogni biglietto nasce in carrello.

```sql
  `DataCarrello` datetime DEFAULT CURRENT_TIMESTAMP,
  `DataAcquisto` datetime DEFAULT NULL,
```
Doppio timestamp per tracciare sia l'aggiunta al carrello (automatica) che il
pagamento (impostata dall'applicazione al checkout). Permette di misurare
il tempo di conversione carrello→acquisto e di pulire i carrelli abbandonati.

```sql
  `QRCode` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  UNIQUE KEY `QRCode` (`QRCode`),
```
- `varchar(64)`: dimensione appropriata per un hash SHA-256 (64 caratteri hex).
- `UNIQUE KEY`: due biglietti non possono avere lo stesso QR — falla di sicurezza critica.
- `DEFAULT NULL`: generato solo al momento dell'acquisto, non in fase di carrello.

```sql
  KEY `idx_utente_stato` (`idUtente`,`Stato`),
  KEY `idx_evento_stato` (`idEvento`,`Stato`),
```
Indici **composti** pensati per le query applicative più frequenti:
- "biglietti acquistati dell'utente X": `WHERE idUtente=? AND Stato='acquistato'`
- "biglietti venduti per l'evento Y": `WHERE idEvento=? AND Stato!='carrello'`
L'ordine delle colonne nell'indice mette prima l'id (alta selettività) poi lo stato
(bassa selettività) per massimizzare l'efficacia del filtro.

---

## Tabella `ordini`

```sql
  `stato` enum('pending','completato','rimborsato') ... DEFAULT 'pending',
```
Ciclo di vita dell'ordine:
- `pending`: pagamento in corso
- `completato`: pagamento confermato
- `rimborsato`: ordine rimborsato senza cancellarlo (storico finanziario preservato)

```sql
  `idUtente` int NOT NULL,
  KEY `idx_utente` (`idUtente`),
```
FK diretta verso `utenti`: ogni ordine appartiene a un utente specifico. Più semplice
ed efficiente rispetto a una tabella di join separata `utente_ordini` (presente in
versioni precedenti del DDL).

---

## Tabella `collaboratorieventi`

Sistema di invito collaboratori agli eventi.

```sql
  `invitato_da` int DEFAULT NULL,
```
Nullable: traccia chi ha inviato l'invito. `DEFAULT NULL` + `ON DELETE SET NULL`
permette di preservare la collaborazione anche se l'invitante viene cancellato.

```sql
  `status` enum('pending','accepted','declined','revoked') ... DEFAULT 'pending',
```
Ciclo di vita dell'invito con quattro stati:
- `pending`: inviato, in attesa di risposta
- `accepted`: accettato dal collaboratore
- `declined`: rifiutato dal collaboratore
- `revoked`: revocato dall'organizzatore (in più rispetto alle versioni precedenti)

```sql
  `token` varchar(100) ... DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
```
Token per il link di accettazione via email, con scadenza — identico al pattern
usato per reset password e verifica email. Coerenza progettuale.

```sql
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
```
`ON UPDATE CURRENT_TIMESTAMP`: MySQL aggiorna automaticamente questo campo a ogni
modifica della riga. Non richiede logica applicativa. Permette di vedere quando
è cambiato lo stato di un invito.

```sql
  UNIQUE KEY `unique_collaborazione` (`idEvento`,`idUtente`),
```
Un utente può avere al massimo una collaborazione per evento. Vincolo business
fondamentale garantito a livello di database.

```sql
  CONSTRAINT `collaboratorieventi_ibfk_1` FOREIGN KEY (`idEvento`) REFERENCES `eventi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `collaboratorieventi_ibfk_2` FOREIGN KEY (`idUtente`) REFERENCES `utenti` (`id`) ON DELETE CASCADE,
```
`CASCADE`: se l'evento o l'utente vengono eliminati, le loro collaborazioni scompaiono
automaticamente — ha senso perché senza evento o utente la relazione è priva di
significato.

```sql
  CONSTRAINT `collaboratorieventi_ibfk_3` FOREIGN KEY (`invitato_da`) REFERENCES `utenti` (`id`) ON DELETE SET NULL
```
Asimmetria intenzionale: se l'invitante viene eliminato la collaborazione rimane
valida (l'invitato continua a collaborare), ma il tracciamento dell'origine dell'invito
viene perso (`invitato_da → NULL`).

---

## Tabella `eventisettori`

Tabella di join N:N tra eventi e settori, con dato aggiuntivo.

```sql
  PRIMARY KEY (`idEvento`,`idSettore`),
```
PK composta: un evento può avere ogni settore al massimo una volta. Nessun campo
`id` surrogato — la combinazione è naturalmente univoca.

```sql
  `PostiDisponibili` int NOT NULL DEFAULT '0',
```
Dato **di join**: la disponibilità posti per quella specifica combinazione evento-settore.
Non appartiene né a `eventi` né a `settori` da soli, ma alla loro relazione.
Viene decrementato ad ogni acquisto e incrementato ad ogni cancellazione (logica
applicativa, in transazione atomica).

```sql
  CONSTRAINT `eventisettori_ibfk_1` FOREIGN KEY (`idEvento`) REFERENCES `eventi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `eventisettori_ibfk_2` FOREIGN KEY (`idSettore`) REFERENCES `settori` (`id`) ON DELETE RESTRICT,
```
Asimmetria intenzionale:
- Se l'evento viene eliminato → le configurazioni dei settori spariscono (`CASCADE`)
- Se il settore viene eliminato → non è possibile se è configurato per un evento (`RESTRICT`)
L'evento "guida" il ciclo di vita; il settore è infrastruttura stabile.

---

## Tabella `evento_intrattenitori`

Join N:N con dati aggiuntivi sull'esibizione.

```sql
  `OraInizio` time DEFAULT NULL,
  `OraFine` time DEFAULT NULL,
  `Ordine` tinyint DEFAULT NULL,
```
Dati specifici dell'esibizione nell'evento. `tinyint` per `Ordine` (1 byte, range
0-127): più che sufficiente per il numero di artisti in scaletta. Tutti nullable
perché non tutti gli eventi hanno una scaletta strutturata.

```sql
  PRIMARY KEY (`idEvento`,`idIntrattenitore`),
```
PK composta: un intrattenitore può esibirsi in un dato evento una sola volta.
Se si vuole gestire due set dello stesso artista, il modello andrebbe esteso.

---

## Tabella `notifiche`

```sql
  `tipo` varchar(50) ... NOT NULL,
```
Tipo come stringa libera (es. "invito_collaboratore", "acquisto_biglietto").
Si è scelto `varchar` anziché `ENUM` per permettere l'aggiunta di nuovi tipi
di notifica **senza ALTER TABLE** — flessibilità a scapito di garanzie di integrità.

```sql
  `metadata` json DEFAULT NULL,
```
Campo JSON nativo (supportato da MySQL 5.7+): dati extra specifici per tipo di
notifica (es. `{"idEvento": 42, "nomeEvento": "Rock Night"}`). Permette query
sui campi interni con `JSON_EXTRACT()` o l'operatore `->`.

```sql
  KEY `idx_destinatario_letta` (`destinatario_id`,`letta`),
```
Indice composto progettato per la query più comune: "notifiche non lette dell'utente X"
→ `WHERE destinatario_id = ? AND letta = 0`. Il `destinatario_id` precede `letta`
perché ha cardinalità più alta (molti utenti diversi vs solo 0/1).

---

## Tabella `ordine_biglietti`

```sql
  PRIMARY KEY (`idOrdine`,`idBiglietto`),
  UNIQUE KEY `idBiglietto` (`idBiglietto`),
```
Doppio vincolo fondamentale:
- PK composta: un ordine contiene più biglietti
- UNIQUE su solo `idBiglietto`: **un biglietto appartiene a un solo ordine**.
  Impedisce la vendita doppia dello stesso biglietto.

---

## Tabella `recensioni`

```sql
  `idUtente` int DEFAULT NULL,
```
Nullable con `ON DELETE SET NULL`: se l'utente viene eliminato, la recensione rimane
come feedback anonimo (utile per la storicità e la valutazione media dell'evento).

```sql
  UNIQUE KEY `unique_recensione` (`idEvento`,`idUtente`),
```
Un utente può lasciare **una sola recensione per evento**. Vincolo business
fondamentale per prevenire review bombing garantito a livello di database.

```sql
  CONSTRAINT `recensioni_chk_1` CHECK ((`Voto` between 1 and 5))
```
`BETWEEN 1 AND 5` è equivalente a `Voto >= 1 AND Voto <= 5`. Validazione a livello
db: anche aggirando l'applicazione, voti fuori range non vengono accettati.

```sql
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
```
Traccia le modifiche: se un utente cambia la sua recensione, `updated_at` viene
aggiornato automaticamente. Permette di mostrare "(modificata il …)" nell'interfaccia.

---

## Tabella `settore_biglietti`

Associa ogni biglietto a un posto specifico in un settore.

```sql
  PRIMARY KEY (`idBiglietto`),
```
PK su solo `idBiglietto` (non sulla coppia): modella una relazione 1:1 con `biglietti`.
Ogni biglietto ha al massimo un posto assegnato. Non serve un campo `id` surrogato.

```sql
  UNIQUE KEY `unique_posto` (`idSettore`,`Fila`,`NumPosto`),
```
Vincolo antiduplicato fondamentale: due biglietti non possono occupare lo stesso posto
nello stesso settore (stessa fila, stesso numero). Impedisce il double-booking.
MySQL tratta i NULL come non-uguali negli indici UNIQUE, quindi biglietti senza posto
(standing) non violano questo vincolo.

```sql
  `Fila` varchar(10) ... DEFAULT NULL,
  `NumPosto` smallint DEFAULT NULL,
```
Nullable per settori standing (in piedi): il biglietto è nel settore ma senza
un posto numerato specifico. `varchar` per `Fila` perché le file possono essere
lettere ("A", "B"…) o numeri.

---

## Epilogo

```sql
SET FOREIGN_KEY_CHECKS=1;
```
Riabilita i controlli FK. Da questo punto ogni INSERT/UPDATE/DELETE rispetterà
tutti i vincoli referenziali. Se si dimentica questa riga, il db rimane con i
controlli disattivati per tutta la sessione corrente — un rischio grave.

---

## Riepilogo delle scelte progettuali

| Scelta | Motivazione |
|--------|-------------|
| `utf8mb4` ovunque | Supporto completo Unicode, inclusi emoji |
| `decimal` per prezzi e coordinate | Nessun errore di arrotondamento floating point |
| `ENUM` per stati e categorie | Coerenza dati, efficienza storage (1-2 byte vs stringa) |
| Soft delete (`deleted_at`) | Preserva storico, non rompe FK, permette ripristino |
| `ON DELETE RESTRICT` per entità core | Previene perdita dati accidentale su entità principali |
| `ON DELETE CASCADE` per relazioni dipendenti | Pulizia automatica dati orfani su join table |
| `ON DELETE SET NULL` per riferimenti opzionali | Mantiene la riga, anonimizza il riferimento perso |
| FK diretta `idCreatore` in ogni entità | Sostituisce le tabelle `creatori*` separate: meno JOIN, più semplice |
| FK `idTipo int` in biglietti | Sostituisce `idClasse varchar`: integrità referenziale garantita |
| `JSON` per metadata notifiche | Flessibilità senza ALTER TABLE per nuovi tipi |
| Indici composti `(id, stato)` | Ottimizza le query applicative più frequenti |
| PK composte su join table | Nessun id surrogato superfluo, unicità naturale |
| `UNIQUE KEY idBiglietto` in `ordine_biglietti` | Impedisce vendita doppia dello stesso biglietto |
| `PostiDisponibili` in `eventisettori` (non in `settori`) | Disponibilità per evento specifico, non globale per il settore |
| `CHECK` constraints su tutti i campi numerici critici | Validazione business a livello db, indipendente dall'applicazione |
| `SET FOREIGN_KEY_CHECKS=0/1` | Permette ordine di creazione tabelle libero senza errori di FK |
