<?php
/**
 * Model Biglietto
 * Gestisce operazioni CRUD sui biglietti, validazione e calcolo prezzi
 *
 * I biglietti rappresentano i titoli di accesso agli eventi.
 * Ogni biglietto e associato a un evento, una tipologia (Standard/VIP/Premium)
 * e opzionalmente a un settore con posto assegnato.
 */

/**
 * Recupera tutte le tipologie di biglietto disponibili
 * Le tipologie definiscono il sovrapprezzo applicato al prezzo base
 *
 * @return array Lista tipologie ordinate per modificatore prezzo crescente
 */
function getAllTipiBiglietto(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM Tipo ORDER BY ModificatorePrezzo")->fetchAll();
}

/**
 * Recupera una tipologia biglietto tramite nome
 *
 * @param string $nome Nome della tipologia (es. "VIP", "Standard")
 * @return array|null Dati tipologia o null se non trovata
 */
function getTipoByNome(PDO $pdo, string $nome): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM Tipo WHERE nome = ?");
    $stmt->execute([$nome]);
    return $stmt->fetch() ?: null;
}

/**
 * Recupera un biglietto con dati evento e tipologia
 *
 * @return array|null Dati biglietto arricchiti o null se non trovato
 */
function getBigliettoById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT b.*, e.Nome as EventoNome, t.ModificatorePrezzo
        FROM Biglietti b
        JOIN Eventi e ON b.idEvento = e.id
        JOIN Tipo t ON b.idClasse = t.nome
        WHERE b.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Recupera tutti i biglietti di un evento con informazioni sul posto
 * Include dati settore se il biglietto ha un posto assegnato
 *
 * @return array Lista biglietti con fila e numero posto
 */
function getBigliettiByEvento(PDO $pdo, int $idEvento): array
{
    $stmt = $pdo->prepare("
        SELECT b.*, t.ModificatorePrezzo, sb.Fila, sb.Numero, s.id as idSettore
        FROM Biglietti b
        JOIN Tipo t ON b.idClasse = t.nome
        LEFT JOIN Settore_Biglietti sb ON b.id = sb.idBiglietto
        LEFT JOIN Settori s ON sb.idSettore = s.id
        WHERE b.idEvento = ?
        ORDER BY b.id
    ");
    $stmt->execute([$idEvento]);
    return $stmt->fetchAll();
}

/**
 * Recupera i biglietti associati a un ordine
 * Utilizzato per visualizzare il riepilogo ordine
 *
 * @return array Lista biglietti con dettagli evento e prezzo
 */
function getBigliettiByOrdine(PDO $pdo, int $idOrdine): array
{
    $stmt = $pdo->prepare("
        SELECT b.*, e.Nome as EventoNome, e.Data, e.OraI, t.ModificatorePrezzo
        FROM Biglietti b
        JOIN Ordine_Biglietti ob ON b.id = ob.idBiglietto
        JOIN Eventi e ON b.idEvento = e.id
        JOIN Tipo t ON b.idClasse = t.nome
        WHERE ob.idOrdine = ?
    ");
    $stmt->execute([$idOrdine]);
    return $stmt->fetchAll();
}

/**
 * Crea un nuovo biglietto con QR code univoco
 * Il QR code viene generato automaticamente per la validazione all'ingresso
 *
 * @param array $data Dati intestatario: idEvento, idClasse, Nome, Cognome, Sesso, Stato, idUtente
 * @return int ID del biglietto creato
 */
function createBiglietto(PDO $pdo, array $data): int
{
    $qrcode = generateQRCode();
    $stato = $data['Stato'] ?? 'acquistato';
    $idUtente = $data['idUtente'] ?? null;

    $stmt = $pdo->prepare("
        INSERT INTO Biglietti (idEvento, idClasse, Nome, Cognome, Sesso, QRcode, Stato, idUtente)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['idEvento'],
        $data['idClasse'],
        $data['Nome'],
        $data['Cognome'],
        $data['Sesso'],
        $qrcode,
        $stato,
        $idUtente
    ]);
    return (int) $pdo->lastInsertId();
}

/**
 * Assegna un posto specifico a un biglietto
 * Collega il biglietto a un settore con fila e numero posto
 *
 * @param string $fila Lettera della fila (es. "A", "B")
 * @param int $numero Numero del posto nella fila
 * @return bool Esito operazione
 */
function assegnaPosto(PDO $pdo, int $idBiglietto, int $idSettore, string $fila, int $numero): bool
{
    $stmt = $pdo->prepare("
        INSERT INTO Settore_Biglietti (idSettore, idBiglietto, Fila, Numero)
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([$idSettore, $idBiglietto, $fila, $numero]);
}

/**
 * Marca il biglietto come validato all'ingresso
 * Imposta il flag Check a TRUE per indicare che il biglietto e stato utilizzato
 *
 * @return bool Esito operazione
 */
function validaBiglietto(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("UPDATE Biglietti SET `Check` = TRUE WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Verifica se un biglietto e gia stato validato
 * Utile per prevenire riutilizzo fraudolento
 *
 * @return bool True se il biglietto e stato gia usato
 */
function isBigliettoValidato(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("SELECT `Check` FROM Biglietti WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    return $result && $result['Check'];
}

/**
 * Elimina un biglietto dal database
 * Le relazioni vengono eliminate in cascade dal DB
 *
 * @return bool Esito operazione
 */
function deleteBiglietto(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM Biglietti WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Genera un codice QR univoco per il biglietto
 * Utilizza random_bytes per garantire unicita crittografica
 *
 * @return string Stringa esadecimale di 64 caratteri
 */
function generateQRCode(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Calcola il prezzo finale di un biglietto
 * Formula: (PrezzoBase + ModificatoreTipo) * MoltiplicatoreSettore
 *
 * @param string $idClasse Nome della tipologia biglietto
 * @return float Prezzo finale calcolato, 0 se dati non trovati
 */
function calcolaPrezzoFinale(PDO $pdo, int $idEvento, string $idClasse, int $idSettore): float
{
    $stmt = $pdo->prepare("
        SELECT e.PrezzoNoMod, t.ModificatorePrezzo, s.MoltiplicatorePrezzo
        FROM Eventi e
        CROSS JOIN Tipo t
        CROSS JOIN Settori s
        WHERE e.id = ? AND t.nome = ? AND s.id = ?
    ");
    $stmt->execute([$idEvento, $idClasse, $idSettore]);
    $result = $stmt->fetch();

    if (!$result) {
        return 0.0;
    }

    return ($result['PrezzoNoMod'] + $result['ModificatorePrezzo']) * $result['MoltiplicatorePrezzo'];
}

/**
 * Recupera i biglietti futuri di un utente
 * Mostra solo eventi non ancora svolti, ordinati per data
 * Include calcolo prezzo finale per ogni biglietto
 * Cerca sia tramite ordine (vecchio sistema) che tramite idUtente (nuovo sistema)
 *
 * @return array Lista biglietti per eventi futuri
 */
function getBigliettiUtenteFuturi(PDO $pdo, int $idUtente): array
{
    $stmt = $pdo->prepare("
        SELECT DISTINCT b.*, e.Nome as EventoNome, e.Data, e.OraI, e.OraF,
               e.Locandina as EventoLocandina,
               l.Nome as LocationName,
               sb.idSettore, sb.Fila, sb.Numero as PostoNumero,
               o.id as idOrdine,
               (e.PrezzoNoMod + t.ModificatorePrezzo) * COALESCE(s.MoltiplicatorePrezzo, 1) as PrezzoFinale
        FROM Biglietti b
        JOIN Eventi e ON b.idEvento = e.id
        JOIN Locations l ON e.idLocation = l.id
        JOIN Tipo t ON b.idClasse = t.nome
        LEFT JOIN Settore_Biglietti sb ON b.id = sb.idBiglietto
        LEFT JOIN Settori s ON sb.idSettore = s.id
        LEFT JOIN Ordine_Biglietti ob ON b.id = ob.idBiglietto
        LEFT JOIN Ordini o ON ob.idOrdine = o.id
        LEFT JOIN Utente_Ordini uo ON o.id = uo.idOrdine
        WHERE e.Data >= CURDATE()
          AND (b.Stato = 'acquistato' OR b.Stato IS NULL)
          AND (uo.idUtente = ? OR b.idUtente = ?)
        ORDER BY e.Data, e.OraI
    ");
    $stmt->execute([$idUtente, $idUtente]);
    return $stmt->fetchAll();
}

/**
 * Recupera i biglietti passati di un utente
 * Mostra eventi gia svolti, ordinati dal piu recente
 * Utile per lo storico acquisti
 * Cerca sia tramite ordine (vecchio sistema) che tramite idUtente (nuovo sistema)
 *
 * @return array Lista biglietti per eventi passati
 */
function getBigliettiUtentePassati(PDO $pdo, int $idUtente): array
{
    $stmt = $pdo->prepare("
        SELECT DISTINCT b.*, e.Nome as EventoNome, e.Data, e.OraI,
               e.Locandina as EventoLocandina,
               l.Nome as LocationName,
               sb.idSettore, sb.Fila, sb.Numero as PostoNumero,
               o.id as idOrdine,
               (e.PrezzoNoMod + t.ModificatorePrezzo) * COALESCE(s.MoltiplicatorePrezzo, 1) as PrezzoFinale
        FROM Biglietti b
        JOIN Eventi e ON b.idEvento = e.id
        JOIN Locations l ON e.idLocation = l.id
        JOIN Tipo t ON b.idClasse = t.nome
        LEFT JOIN Settore_Biglietti sb ON b.id = sb.idBiglietto
        LEFT JOIN Settori s ON sb.idSettore = s.id
        LEFT JOIN Ordine_Biglietti ob ON b.id = ob.idBiglietto
        LEFT JOIN Ordini o ON ob.idOrdine = o.id
        LEFT JOIN Utente_Ordini uo ON o.id = uo.idOrdine
        WHERE e.Data < CURDATE()
          AND (b.Stato = 'acquistato' OR b.Stato IS NULL)
          AND (uo.idUtente = ? OR b.idUtente = ?)
        ORDER BY e.Data DESC, e.OraI DESC
    ");
    $stmt->execute([$idUtente, $idUtente]);
    return $stmt->fetchAll();
}

/**
 * Verifica se un utente possiede almeno un biglietto per un evento
 * Utilizzato per abilitare la scrittura di recensioni
 * Cerca sia tramite ordine che tramite idUtente diretto
 *
 * @return bool True se l'utente ha biglietti per l'evento
 */
function hasBigliettoPerEvento(PDO $pdo, int $idUtente, int $idEvento): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM Biglietti b
        LEFT JOIN Ordine_Biglietti ob ON b.id = ob.idBiglietto
        LEFT JOIN Ordini o ON ob.idOrdine = o.id
        LEFT JOIN Utente_Ordini uo ON o.id = uo.idOrdine
        WHERE b.idEvento = ?
          AND (b.Stato = 'acquistato' OR b.Stato IS NULL)
          AND (uo.idUtente = ? OR b.idUtente = ?)
    ");
    $stmt->execute([$idEvento, $idUtente, $idUtente]);
    $result = $stmt->fetch();
    return $result && $result['count'] > 0;
}

/**
 * Verifica se esiste gia un biglietto con stesso intestatario per l'evento
 * Previene acquisti duplicati per la stessa persona
 * Il confronto e case-insensitive e ignora spazi
 *
 * @return bool True se esiste un duplicato
 */
function esisteBigliettoDuplicato(PDO $pdo, int $idEvento, string $nome, string $cognome): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM Biglietti
        WHERE idEvento = ?
          AND LOWER(TRIM(Nome)) = LOWER(TRIM(?))
          AND LOWER(TRIM(Cognome)) = LOWER(TRIM(?))
    ");
    $stmt->execute([$idEvento, $nome, $cognome]);
    $result = $stmt->fetch();
    return $result && $result['count'] > 0;
}

/* ============================================
   GESTIONE CARRELLO (Biglietti con Stato)
   ============================================ */

/**
 * Aggiunge un biglietto al carrello (stato='carrello')
 * Il biglietto viene salvato nel DB ma non è ancora acquistato
 * Se viene fornito un idSettore, assegna subito un posto nel settore
 *
 * @param int $idUtente ID utente (può essere null per guest)
 * @param int|null $idSettore ID settore selezionato (opzionale)
 * @return int ID del biglietto creato
 */
function addBigliettoToCart(PDO $pdo, int $idEvento, string $idClasse, ?int $idUtente = null, ?int $idSettore = null): int
{
    $stmt = $pdo->prepare("
        INSERT INTO Biglietti (idEvento, idClasse, Nome, Cognome, Sesso, QRcode, Stato, idUtente, DataCarrello)
        VALUES (?, ?, '', '', 'Altro', ?, 'carrello', ?, NOW())
    ");
    $qrcode = generateQRCode();
    $stmt->execute([$idEvento, $idClasse, $qrcode, $idUtente]);
    $idBiglietto = (int) $pdo->lastInsertId();

    // Se è stato selezionato un settore, assegna subito il posto
    if ($idSettore !== null && $idSettore > 0) {
        assegnaPostoInSettore($pdo, $idBiglietto, $idEvento, $idSettore);
    }

    return $idBiglietto;
}

/**
 * Assegna un posto nel settore specificato
 * Trova il primo posto disponibile nel settore selezionato
 *
 * @return bool True se l'assegnazione ha avuto successo
 */
function assegnaPostoInSettore(PDO $pdo, int $idBiglietto, int $idEvento, int $idSettore): bool
{
    // Recupera info settore
    $stmt = $pdo->prepare("SELECT Posti FROM Settori WHERE id = ?");
    $stmt->execute([$idSettore]);
    $settore = $stmt->fetch();

    if (!$settore) {
        return false;
    }

    $postiTotali = $settore['Posti'];

    // Trova i posti già occupati per questo settore e questo evento
    $stmt = $pdo->prepare("
        SELECT sb.Fila, sb.Numero
        FROM Settore_Biglietti sb
        JOIN Biglietti b ON sb.idBiglietto = b.id
        WHERE sb.idSettore = ? AND b.idEvento = ?
        ORDER BY sb.Fila, sb.Numero
    ");
    $stmt->execute([$idSettore, $idEvento]);
    $postiOccupati = $stmt->fetchAll();

    // Crea un set di posti occupati
    $occupati = [];
    foreach ($postiOccupati as $po) {
        $occupati[$po['Fila'] . '-' . $po['Numero']] = true;
    }

    // Calcola file e posti per settore (10 posti per fila)
    $postiPerFila = 10;
    $numFile = ceil($postiTotali / $postiPerFila);

    // Cerca il primo posto libero
    for ($fila = 0; $fila < $numFile; $fila++) {
        $letteraFila = chr(65 + $fila); // A, B, C, ...
        $postiInQuestaFila = min($postiPerFila, $postiTotali - ($fila * $postiPerFila));

        for ($numero = 1; $numero <= $postiInQuestaFila; $numero++) {
            $chiave = $letteraFila . '-' . $numero;
            if (!isset($occupati[$chiave])) {
                // Posto libero trovato! Assegnalo
                return assegnaPosto($pdo, $idBiglietto, $idSettore, $letteraFila, $numero);
            }
        }
    }

    return false;
}

/**
 * Recupera i biglietti nel carrello di un utente
 * Include info settore e calcola prezzo con moltiplicatore settore
 *
 * @return array Lista biglietti nel carrello con dettagli evento
 */
function getCartByUtente(PDO $pdo, int $idUtente): array
{
    $stmt = $pdo->prepare("
        SELECT b.*, e.Nome as EventoNome, e.Data, e.OraI, e.PrezzoNoMod, e.Locandina,
               t.ModificatorePrezzo,
               sb.idSettore, sb.Fila, sb.Numero as PostoNumero,
               s.MoltiplicatorePrezzo,
               (e.PrezzoNoMod + t.ModificatorePrezzo) * COALESCE(s.MoltiplicatorePrezzo, 1) as PrezzoFinale
        FROM Biglietti b
        JOIN Eventi e ON b.idEvento = e.id
        JOIN Tipo t ON b.idClasse = t.nome
        LEFT JOIN Settore_Biglietti sb ON b.id = sb.idBiglietto
        LEFT JOIN Settori s ON sb.idSettore = s.id
        WHERE b.idUtente = ? AND b.Stato = 'carrello'
        ORDER BY b.DataCarrello DESC
    ");
    $stmt->execute([$idUtente]);
    return $stmt->fetchAll();
}

/**
 * Conta i biglietti nel carrello di un utente
 *
 * @return int Numero biglietti nel carrello
 */
function countCartItems(PDO $pdo, int $idUtente): int
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM Biglietti
        WHERE idUtente = ? AND Stato = 'carrello'
    ");
    $stmt->execute([$idUtente]);
    $result = $stmt->fetch();
    return $result ? (int)$result['count'] : 0;
}

/**
 * Aggiorna i dati di un biglietto nel carrello (nome, cognome, sesso)
 *
 * @return bool Esito operazione
 */
function updateBigliettoCart(PDO $pdo, int $idBiglietto, string $nome, string $cognome, string $sesso = 'Altro'): bool
{
    $stmt = $pdo->prepare("
        UPDATE Biglietti
        SET Nome = ?, Cognome = ?, Sesso = ?
        WHERE id = ? AND Stato = 'carrello'
    ");
    return $stmt->execute([$nome, $cognome, $sesso, $idBiglietto]);
}

/**
 * Cambia il tipo/classe di un biglietto nel carrello
 *
 * @return bool Esito operazione
 */
function updateBigliettoTipo(PDO $pdo, int $idBiglietto, string $nuovoTipo): bool
{
    $stmt = $pdo->prepare("
        UPDATE Biglietti
        SET idClasse = ?
        WHERE id = ? AND Stato = 'carrello'
    ");
    return $stmt->execute([$nuovoTipo, $idBiglietto]);
}

/**
 * Rimuove un biglietto dal carrello
 *
 * @return bool Esito operazione
 */
function removeFromCart(PDO $pdo, int $idBiglietto, int $idUtente): bool
{
    $stmt = $pdo->prepare("
        DELETE FROM Biglietti
        WHERE id = ? AND idUtente = ? AND Stato = 'carrello'
    ");
    return $stmt->execute([$idBiglietto, $idUtente]);
}

/**
 * Svuota il carrello di un utente
 *
 * @return bool Esito operazione
 */
function clearCart(PDO $pdo, int $idUtente): bool
{
    $stmt = $pdo->prepare("
        DELETE FROM Biglietti
        WHERE idUtente = ? AND Stato = 'carrello'
    ");
    return $stmt->execute([$idUtente]);
}

/**
 * Conferma l'acquisto: cambia stato da 'carrello' a 'acquistato'
 *
 * @param array $idBiglietti Lista ID biglietti da confermare
 * @return bool Esito operazione
 */
function confirmPurchase(PDO $pdo, array $idBiglietti): bool
{
    if (empty($idBiglietti)) return false;

    $placeholders = implode(',', array_fill(0, count($idBiglietti), '?'));
    $stmt = $pdo->prepare("
        UPDATE Biglietti
        SET Stato = 'acquistato'
        WHERE id IN ($placeholders) AND Stato = 'carrello'
    ");
    return $stmt->execute($idBiglietti);
}

/**
 * Conta biglietti venduti/nel carrello per un evento
 * Utile per verificare disponibilità
 *
 * @param bool $includiCarrello Se true, conta anche biglietti nel carrello
 * @return int Numero biglietti
 */
function countBigliettiEvento(PDO $pdo, int $idEvento, bool $includiCarrello = true): int
{
    if ($includiCarrello) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM Biglietti WHERE idEvento = ?
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM Biglietti
            WHERE idEvento = ? AND Stato = 'acquistato'
        ");
    }
    $stmt->execute([$idEvento]);
    $result = $stmt->fetch();
    return $result ? (int)$result['count'] : 0;
}

/**
 * Verifica se è possibile acquistare altri biglietti per un evento
 *
 * @param int $quantita Numero biglietti da aggiungere
 * @return bool True se c'è disponibilità
 */
function checkDisponibilitaBiglietti(PDO $pdo, int $idEvento, int $quantita = 1): bool
{
    // Recupera limite evento
    $stmt = $pdo->prepare("SELECT MaxBiglietti FROM Eventi WHERE id = ?");
    $stmt->execute([$idEvento]);
    $evento = $stmt->fetch();

    if (!$evento || $evento['MaxBiglietti'] === null) {
        return true; // Nessun limite
    }

    $attuali = countBigliettiEvento($pdo, $idEvento, true);
    return ($attuali + $quantita) <= $evento['MaxBiglietti'];
}

/**
 * Recupera biglietti disponibili per un evento
 *
 * @return int Numero biglietti ancora disponibili (null = illimitati)
 */
function getBigliettiDisponibili(PDO $pdo, int $idEvento): ?int
{
    $stmt = $pdo->prepare("SELECT MaxBiglietti FROM Eventi WHERE id = ?");
    $stmt->execute([$idEvento]);
    $evento = $stmt->fetch();

    if (!$evento || $evento['MaxBiglietti'] === null) {
        return null; // Illimitati
    }

    $venduti = countBigliettiEvento($pdo, $idEvento, true);
    return max(0, $evento['MaxBiglietti'] - $venduti);
}

/**
 * Pulisce carrelli abbandonati (biglietti nel carrello da più di X ore)
 *
 * @param int $ore Ore dopo cui considerare il carrello abbandonato
 * @return int Numero biglietti eliminati
 */
function cleanAbandonedCarts(PDO $pdo, int $ore = 24): int
{
    $stmt = $pdo->prepare("
        DELETE FROM Biglietti
        WHERE Stato = 'carrello'
        AND DataCarrello < DATE_SUB(NOW(), INTERVAL ? HOUR)
    ");
    $stmt->execute([$ore]);
    return $stmt->rowCount();
}

/**
 * Trasferisce carrello da sessione guest a utente loggato
 * Utile quando un utente fa login dopo aver aggiunto biglietti
 *
 * @param array $guestCartIds IDs biglietti dalla sessione guest
 * @return int Numero biglietti trasferiti
 */
function transferCartToUser(PDO $pdo, array $guestCartIds, int $idUtente): int
{
    if (empty($guestCartIds)) return 0;

    $placeholders = implode(',', array_fill(0, count($guestCartIds), '?'));
    $params = array_merge([$idUtente], $guestCartIds);

    $stmt = $pdo->prepare("
        UPDATE Biglietti
        SET idUtente = ?
        WHERE id IN ($placeholders) AND Stato = 'carrello'
    ");
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Trova e assegna automaticamente il prossimo posto disponibile per un biglietto
 * Cerca nei settori della location dell'evento e assegna il primo posto libero
 * Salta se il biglietto ha già un posto assegnato
 *
 * @param int $idBiglietto ID del biglietto a cui assegnare il posto
 * @return bool True se l'assegnazione ha avuto successo (o se già assegnato)
 */
function assegnaPostoAutomatico(PDO $pdo, int $idBiglietto): bool
{
    // Verifica se il biglietto ha già un posto assegnato
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM Settore_Biglietti WHERE idBiglietto = ?");
    $stmt->execute([$idBiglietto]);
    $existing = $stmt->fetch();
    if ($existing && $existing['count'] > 0) {
        return true; // Già assegnato, considera successo
    }

    // Recupera l'evento del biglietto
    $stmt = $pdo->prepare("
        SELECT b.idEvento, e.idLocation
        FROM Biglietti b
        JOIN Eventi e ON b.idEvento = e.id
        WHERE b.id = ?
    ");
    $stmt->execute([$idBiglietto]);
    $info = $stmt->fetch();

    if (!$info) {
        return false;
    }

    $idEvento = $info['idEvento'];
    $idLocation = $info['idLocation'];

    // Recupera i settori della location (ordinati per prezzo decrescente)
    $stmt = $pdo->prepare("SELECT id, Posti FROM Settori WHERE idLocation = ? ORDER BY MoltiplicatorePrezzo DESC");
    $stmt->execute([$idLocation]);
    $settori = $stmt->fetchAll();

    if (empty($settori)) {
        return false;
    }

    // Per ogni settore, cerca un posto disponibile
    foreach ($settori as $settore) {
        $idSettore = $settore['id'];
        $postiTotali = $settore['Posti'];

        // Trova i posti già occupati per questo settore e questo evento
        $stmt = $pdo->prepare("
            SELECT sb.Fila, sb.Numero
            FROM Settore_Biglietti sb
            JOIN Biglietti b ON sb.idBiglietto = b.id
            WHERE sb.idSettore = ? AND b.idEvento = ?
            ORDER BY sb.Fila, sb.Numero
        ");
        $stmt->execute([$idSettore, $idEvento]);
        $postiOccupati = $stmt->fetchAll();

        // Crea un set di posti occupati
        $occupati = [];
        foreach ($postiOccupati as $po) {
            $occupati[$po['Fila'] . '-' . $po['Numero']] = true;
        }

        // Calcola file e posti per settore (10 posti per fila)
        $postiPerFila = 10;
        $numFile = ceil($postiTotali / $postiPerFila);

        // Cerca il primo posto libero
        for ($fila = 0; $fila < $numFile; $fila++) {
            $letteraFila = chr(65 + $fila); // A, B, C, ...
            $postiInQuestaFila = min($postiPerFila, $postiTotali - ($fila * $postiPerFila));

            for ($numero = 1; $numero <= $postiInQuestaFila; $numero++) {
                $chiave = $letteraFila . '-' . $numero;
                if (!isset($occupati[$chiave])) {
                    // Posto libero trovato! Assegnalo
                    return assegnaPosto($pdo, $idBiglietto, $idSettore, $letteraFila, $numero);
                }
            }
        }
    }

    // Nessun posto disponibile in nessun settore
    return false;
}

/**
 * Assegna posti automatici a una lista di biglietti
 *
 * @param array $bigliettiIds Lista di ID biglietti
 * @return int Numero di posti assegnati con successo
 */
function assegnaPostiAutomatici(PDO $pdo, array $bigliettiIds): int
{
    $assegnati = 0;
    foreach ($bigliettiIds as $idBiglietto) {
        if (assegnaPostoAutomatico($pdo, $idBiglietto)) {
            $assegnati++;
        }
    }
    return $assegnati;
}
