# DDL — Log delle modifiche

Confronto tra `ddl_server.sql` (originale) e `ddl_migliorato.sql` (corretto).
Ogni modifica riporta la motivazione e l'impatto sul codice applicativo.

---

## Ordine delle tabelle

| Originale | Migliorato |
|-----------|------------|
| `biglietti` prima di `tipo` e `utenti` | Le tabelle vengono dichiarate nell'ordine corretto rispetto alle dipendenze FK: `tipo` → `utenti` → `locations` → `manifestazioni` → `settori` → `eventi` → `biglietti` → `ordini` → ... |

Con `FOREIGN_KEY_CHECKS=0` l'ordine non influisce sull'esecuzione, ma rende il DDL leggibile e corretto come documento.

---

## `tipo`

| Campo / Vincolo | Prima | Dopo | Motivazione |
|-----------------|-------|------|-------------|
| `ModificatorePrezzo` | `decimal(10,2) DEFAULT '0.00'` | `decimal(6,2) NOT NULL DEFAULT '0.00'` | `DECIMAL(10,2)` permette valori fino a 99.999.999,99 — assurdo per un modificatore di prezzo. `(6,2)` copre fino a 9.999,99 €. `NOT NULL` perché il modificatore zero è un valore semantico preciso, non assenza di dato. |
| `tipo_chk_1` | assente | `CHECK (ModificatorePrezzo >= 0)` | Impedisce modificatori negativi che ridurrebbero il prezzo base al di sotto di zero. |

---

## `utenti`

| Campo / Vincolo | Prima | Dopo | Motivazione |
|-----------------|-------|------|-------------|
| `verificato_at` | assente | `datetime DEFAULT NULL` | Traccia il momento esatto della verifica email. Utile per analytics e audit. |
| `email_verification_token_expiry` | assente | `datetime DEFAULT NULL` | La costante `VERIFICATION_TOKEN_EXPIRY_HOURS = 24` esisteva già in `app_config.php` e veniva persino citata nell'email all'utente ("il link scade in 24 ore"), ma non c'era la colonna su cui basare il controllo. Ora il codice può aggiungere `AND email_verification_token_expiry > NOW()` nella query di verifica. |
| `UNIQUE KEY reset_token` | assente | aggiunto | Il token di reset è già univoco per costruzione (random), ma la garanzia DB impedisce collisioni in scenari estremi. |
| `KEY idx_email` | presente | **rimosso** | `UNIQUE KEY Email` crea già un indice B-tree su `Email`. L'indice `idx_email` era ridondante: occupava spazio extra e rallentava ogni INSERT/UPDATE su quella colonna senza fornire alcun beneficio aggiuntivo. |

---

## `locations`

| Campo / Vincolo | Prima | Dopo | Motivazione |
|-----------------|-------|------|-------------|
| `Citta` | `DEFAULT NULL` | `NOT NULL` | La città è il campo geografico minimo indispensabile per qualsiasi funzione di filtro o visualizzazione. Ammettere `NULL` rendeva inutilizzabile l'indice `idx_citta`. |
| `Capienza` | `int DEFAULT '0'` | `int DEFAULT NULL` + `CHECK (Capienza IS NULL OR Capienza > 0)` | `DEFAULT 0` era ambiguo: significava "capienza non specificata" o "capienza zero"? `NULL` esprime correttamente l'assenza del dato. Il `CHECK` impedisce valori negativi o zero quando il campo è compilato. |
| `Lat`, `Lng` | assenti | `decimal(10,8)`, `decimal(11,8) DEFAULT NULL` | Coordinate geografiche per integrazione con mappe (Google Maps, Leaflet). `DECIMAL(10,8)` offre precisione al centimetro (~1.1 mm) più che sufficiente. Opzionali per retrocompatibilità. |

---

## `manifestazioni`

| Campo / Vincolo | Prima | Dopo | Motivazione |
|-----------------|-------|------|-------------|
| `Immagine` | assente | `varchar(500) DEFAULT NULL` | Gli eventi hanno una copertina; le manifestazioni (festival, tour) ne avevano bisogno altrettanto per la pagina di dettaglio. Coerenza con `eventi.Immagine`. |
| `manifestazioni_chk_1` | assente | `CHECK (DataFine IS NULL OR DataFine >= DataInizio)` | Impedisce a livello DB che una manifestazione abbia la data di fine antecedente a quella di inizio. |

---

## `settori`

| Campo / Vincolo | Prima | Dopo | Motivazione |
|-----------------|-------|------|-------------|
| `Fila` | `varchar(10)` — significato ambiguo | **rinominato** `NumFile int` | Il vecchio `Fila` era ambiguo: fila del posto o numero di file totali del settore? `NumFile int` chiarisce che è il conteggio delle file del settore. |
| `Posto` | `int DEFAULT NULL` — significato ambiguo | **rinominato** `PostiPerFila int` | Stessa ambiguità: `PostiPerFila` chiarisce che è il numero di posti per ciascuna fila, non un numero di posto specifico. |
| `PostiDisponibili` | `int DEFAULT '0'` | **rinominato** `PostiTotali int NOT NULL DEFAULT '0'` | Il campo originale si chiamava `PostiDisponibili` ma non veniva mai aggiornato dopo le vendite (verificato nel codice). Era di fatto un dato stale. Rinominato in `PostiTotali` per riflettere il suo reale significato: la capienza fissa del settore. La disponibilità reale si calcola on-the-fly: `PostiTotali - COUNT(biglietti venduti per quell'evento-settore)`. |
| `settori_chk_1` | assente | `CHECK (MoltiplicatorePrezzo > 0)` | Impedisce moltiplicatori negativi o zero che azzererebbero il prezzo dei biglietti. |
| `settori_chk_2` | assente | `CHECK (PostiTotali >= 0)` | Impedisce capienza negativa. |
| FK `idLocation` | `ON DELETE CASCADE` | `ON DELETE RESTRICT` | Eliminare una location non deve eliminare automaticamente i suoi settori: potrebbe esserci storia di biglietti collegati. `RESTRICT` forza una gestione esplicita. |

---

## `intrattenitore` → `intrattenitori`

| Modifica | Motivazione |
|----------|-------------|
| Tabella rinominata da `intrattenitore` a `intrattenitori` | Tutte le altre tabelle del progetto sono al plurale (`eventi`, `biglietti`, `utenti`, `settori`…). Il singolare era un'anomalia di naming. |

> **Impatto sul codice:** tutti i riferimenti a `intrattenitore` in PHP (models, controllers, views, `database_schema.php`) vanno aggiornati.

---

## `eventi`

| Campo / Vincolo | Prima | Dopo | Motivazione |
|-----------------|-------|------|-------------|
| `Categoria` | `varchar(50) DEFAULT 'eventi'` | `enum('concerti','teatro','sport','comedy','cinema','famiglia','eventi')` | La stringa libera permetteva valori arbitrari e inconsistenti tra record (es. `'Concerti'` vs `'concerti'`). L'ENUM forza i valori al set definito, coerente con i filtri già implementati nella homepage. |
| FK `idLocation` | `ON DELETE CASCADE` | `ON DELETE RESTRICT` | Eliminare una location non deve cancellare automaticamente tutti i suoi eventi: potrebbero esserci biglietti venduti. `RESTRICT` impone gestione esplicita prima della cancellazione. |
| `eventi_chk_1` | assente | `CHECK (PrezzoNoMod >= 0)` | Impedisce prezzi base negativi. |
| `eventi_chk_2` | assente | `CHECK (OraF IS NULL OR OraF > OraI)` | Impedisce eventi con ora di fine antecedente all'inizio. |

---

## `biglietti`

| Campo / Vincolo | Prima | Dopo | Motivazione |
|-----------------|-------|------|-------------|
| `idClasse varchar(50)` | stringa libera, nessuna FK | **rinominato** `idTipo int NOT NULL DEFAULT '1'` + FK → `tipo(id)` | **Modifica più importante del DDL.** Il vecchio `idClasse` era una stringa libera (es. `'Standard'`) che il codice confrontava con `tipo.nome` tramite JOIN, senza garanzia di integrità. Un valore inesistente passava silenziosamente. `idTipo` è una FK intera che garantisce a livello DB che ogni biglietto abbia un tipo valido. `DEFAULT '1'` assume che il tipo con id=1 sia 'Standard'. |
| `QRCode varchar(255)` | `varchar(255)` | `varchar(64)` | I QR code generati sono hash (es. SHA-256 = 64 caratteri). `varchar(255)` era sovradimensionato di 4×. |
| `DataAcquisto` | assente | `datetime DEFAULT NULL` | Traccia il momento esatto del checkout. Precedentemente ricostruibile solo indirettamente dall'ordine. `NULL` finché il biglietto è in carrello. |
| `KEY idx_utente`, `KEY idx_evento` | separati | **sostituiti** da `KEY idx_utente_stato (idUtente, Stato)` e `KEY idx_evento_stato (idEvento, Stato)` | Le query più frequenti filtrano sempre per utente+stato o evento+stato (es. `WHERE idUtente=? AND Stato='acquistato'`). Gli indici composti coprono entrambe le condizioni con un solo accesso all'indice. |
| FK `idEvento` | `ON DELETE CASCADE` | `ON DELETE RESTRICT` | **Modifica critica** [verificato]. `deleteEvento()` nel codice esegue un DELETE diretto senza verificare se esistono biglietti acquistati/validati. Con CASCADE, eliminare un evento cancellava silenziosamente tutti i biglietti già pagati. `RESTRICT` forza il codice a gestire esplicitamente la cancellazione. |
| FK `idTipo` | assente | `ON DELETE RESTRICT` | Un tipo di biglietto non può essere eliminato se ci sono biglietti che vi fanno riferimento. |

---

## `ordini`

| Campo / Vincolo | Prima | Dopo | Motivazione |
|-----------------|-------|------|-------------|
| `idUtente` | assente (relazione tramite `utente_ordini`) | `int NOT NULL` + FK → `utenti(id) ON DELETE RESTRICT` | [verificato] Nel codice `associaOrdineUtente()` associa sempre e solo un utente per ordine. La tabella `utente_ordini` M:N era ridondante. Spostare `idUtente` direttamente in `ordini` elimina un JOIN in ogni query "ordini dell'utente". `ON DELETE RESTRICT` impedisce di eliminare un utente con ordini storici. |
| `Totale` | `decimal(10,2) DEFAULT '0.00'` | `decimal(10,2) NOT NULL DEFAULT '0.00'` | Il totale è sempre presente: anche un ordine vuoto ha totale 0.00. `NOT NULL` è corretto. |
| `stato` | assente | `enum('pending','completato','rimborsato') DEFAULT 'pending'` | Lo stato dell'ordine era implicito e non tracciato. Permette di distinguere ordini in corso, completati e rimborsati senza interrogare la tabella biglietti. |
| `KEY idx_utente` | assente | aggiunto | Indice per le query `WHERE idUtente=?` (storico ordini utente). |

---

## `utente_ordini`

| Modifica | Motivazione |
|----------|-------------|
| **Tabella eliminata** | [verificato] Con `idUtente` ora in `ordini`, questa tabella di relazione M:N è completamente ridondante. La relazione è sempre stata 1:1 nel codice. |

> **Impatto sul codice:** `Ordine.php::associaOrdineUtente()` e le query che fanno JOIN su `utente_ordini` vanno aggiornate per usare `ordini.idUtente`.

---

## `collaboratorieventi`

| Campo / Vincolo | Prima | Dopo | Motivazione |
|-----------------|-------|------|-------------|
| `status` ENUM | `'pending','accepted','declined'` | aggiunto `'revoked'` | Permette al promoter di revocare un invito già inviato senza eliminare il record, preservando l'audit trail. |
| `token_expiry` | assente | `datetime DEFAULT NULL` | Il token di invito non aveva scadenza. Analogamente al reset password, un link di invito dovrebbe scadere dopo N ore. |
| `KEY idx_token` | presente | **rimosso** | `UNIQUE KEY token` crea già un indice. `idx_token` era ridondante (stesso problema di `utenti.idx_email`). |
| FK `invitato_da` | `ON DELETE CASCADE` | `ON DELETE SET NULL` + `invitato_da int DEFAULT NULL` | Eliminare il promoter che ha fatto l'invito non deve cancellare la collaborazione già accettata. `SET NULL` preserva il record storico. |

---

## `creatorieventi`, `creatorilocations`, `creatorimanifestazioni`

| FK | Prima | Dopo | Motivazione |
|----|-------|------|-------------|
| FK su `idUtente` | `ON DELETE CASCADE` | `ON DELETE RESTRICT` | Eliminare un utente che ha creato eventi/locations/manifestazioni non deve avvenire silenziosamente. `RESTRICT` forza una gestione esplicita (trasferimento proprietà o anonimizzazione). |

---

## `eventisettori`

| Campo / Vincolo | Prima | Dopo | Motivazione |
|-----------------|-------|------|-------------|
| `PostiDisponibili` | assente | `int NOT NULL DEFAULT '0'` | Spostato da `settori` (dove era globale e mai aggiornato) a `eventisettori` (dove è per-evento). Ogni evento può avere una capienza diversa per lo stesso settore (es. stesso stadio, configurazioni di palco diverse). Questo campo va aggiornato transazionalmente ad ogni vendita/cancellazione biglietto. |
| FK `idSettore` | `ON DELETE CASCADE` | `ON DELETE RESTRICT` | Un settore non può essere eliminato se è ancora associato a eventi. |

---

## `evento_intrattenitore` → `evento_intrattenitori`

| Campo / Vincolo | Prima | Dopo | Motivazione |
|-----------------|-------|------|-------------|
| Nome tabella | `evento_intrattenitore` | `evento_intrattenitori` | Coerenza con la rinomina della tabella `intrattenitori`. |
| `OraInizio`, `OraFine` | assenti | `time DEFAULT NULL` | Senza questi campi la scaletta degli artisti era solo testo libero in `eventi.Programma`. Ora è strutturata e interrogabile. |
| `Ordine` | assente | `tinyint DEFAULT NULL` | Permette di ordinare gli artisti nella scaletta (headliner, opening act…). |
| FK `idIntrattenitore` | → `intrattenitore(id)` | → `intrattenitori(id)` | Aggiornata per puntare alla tabella rinominata. |

---

## `notifiche`

| Campo / Vincolo | Prima | Dopo | Motivazione |
|-----------------|-------|------|-------------|
| `letta_at` | assente | `datetime DEFAULT NULL` | Traccia quando la notifica è stata letta. Utile per analytics sull'engagement e per eventuali notifiche con scadenza. |
| `metadata text` | `text` | `json` | MySQL supporta il tipo `JSON` nativo dalla versione 5.7. Garantisce validità sintattica del JSON e permette query su sotto-campi con l'operatore `->`. Il costo di migrazione è nullo (il formato dati è lo stesso). |
| `KEY idx_letta`, `KEY idx_destinatario` | separati | **uniti** in `KEY idx_destinatario_letta (destinatario_id, letta)` | La query più frequente è `WHERE destinatario_id=? AND letta=0`. L'indice composto la copre con un singolo accesso; i due indici separati erano meno efficienti. |

---

## `ordine_biglietti`

| Campo / Vincolo | Prima | Dopo | Motivazione |
|-----------------|-------|------|-------------|
| `KEY idBiglietto` | indice non univoco | `UNIQUE KEY idBiglietto` | Un biglietto deve appartenere a un solo ordine. Il vincolo di unicità su `idBiglietto` garantisce questa regola a livello DB, impedendo che lo stesso biglietto venga inserito in due ordini diversi. |

---

## `recensioni`

| Campo / Vincolo | Prima | Dopo | Motivazione |
|-----------------|-------|------|-------------|
| `Voto int` | `int NOT NULL` | `tinyint NOT NULL` | I voti sono 1-5. `tinyint` usa 1 byte contro 4 di `int`. Il `CHECK (Voto BETWEEN 1 AND 5)` garantisce già i limiti; il tipo più piccolo è corretto. |
| `idUtente` | `int NOT NULL` + `ON DELETE CASCADE` | `int DEFAULT NULL` + `ON DELETE SET NULL` | Con `CASCADE`, eliminare un account cancellava tutte le sue recensioni, impoverendo il feedback aggregato dell'evento. Con `SET NULL` le recensioni rimangono come feedback anonimo; la media voti dell'evento non crolla alla cancellazione di ogni utente. |
| `updated_at` | assente | `datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | Permette di sapere se e quando una recensione è stata modificata dopo la pubblicazione. |

---

## `settore_biglietti`

| Campo / Vincolo | Prima | Dopo | Motivazione |
|-----------------|-------|------|-------------|
| `Fila`, `NumPosto` | assenti | `varchar(10)`, `int DEFAULT NULL` | **Modifica critica** [verificato]. Senza questi campi, `assegnaPostoInSettore()` faceva READ→WRITE senza lock, esponendo a race condition: due acquisti contemporanei potevano ricevere lo stesso posto. Ora il posto specifico è memorizzato sul biglietto. |
| `UNIQUE KEY unique_posto (idSettore, Fila, NumPosto)` | assente | aggiunto | Impedisce a livello DB la doppia assegnazione dello stesso posto (idSettore + fila + numero). Anche se il codice applicativo ha un bug di concorrenza, il DB rifiuterà il secondo INSERT con un errore di duplicato chiave. |
| FK `idSettore` | `ON DELETE CASCADE` | `ON DELETE RESTRICT` | Un settore con biglietti assegnati non può essere eliminato. |

---

## Riepilogo delle tabelle eliminate / rinominate

| Azione | Tabella | Motivazione |
|--------|---------|-------------|
| **Eliminata** | `utente_ordini` | Sostituita da `ordini.idUtente` (relazione sempre 1:1 nel codice) |
| **Rinominata** | `intrattenitore` → `intrattenitori` | Coerenza naming con il resto dello schema |
| **Rinominata** | `evento_intrattenitore` → `evento_intrattenitori` | Conseguenza della rinomina sopra |
