<?php
/**
 * Model Biglietto
 * Gestisce operazioni CRUD sui biglietti, validazione e calcolo prezzi
 *
 * I biglietti rappresentano i titoli di accesso agli eventi.
 * Ogni biglietto e associato a un evento, una tipologia (Standard/VIP/Premium)
 * e opzionalmente a un settore con posto assegnato.
 */

require_once __DIR__ . '/../config/database_schema.php';
require_once __DIR__ . '/../lib/QueryBuilder.php';

/**
 * Recupera tutte le tipologie di biglietto disponibili
 * Le tipologie definiscono il sovrapprezzo applicato al prezzo base
 *
 * @return array Lista tipologie ordinate per modificatore prezzo crescente
 */
function getAllTipiBiglietto(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM " . TABLE_TIPO . " ORDER BY " . COL_TIPO_MODIFICATORE_PREZZO)->fetchAll();
}

/**
 * Recupera una tipologia biglietto tramite nome
 *
 * @param string $nome Nome della tipologia (es. "VIP", "Standard")
 * @return array|null Dati tipologia o null se non trovata
 */
function getTipoByNome(PDO $pdo, string $nome): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM " . TABLE_TIPO . " WHERE " . COL_TIPO_NOME . " = ?");
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
        SELECT b.*, e." . COL_EVENTI_NOME . " as EventoNome, t." . COL_TIPO_MODIFICATORE_PREZZO . "
        FROM " . TABLE_BIGLIETTI . " b
        JOIN " . TABLE_EVENTI . " e ON b." . COL_BIGLIETTI_ID_EVENTO . " = e." . COL_EVENTI_ID . "
        JOIN " . TABLE_TIPO . " t ON b." . COL_BIGLIETTI_ID_CLASSE . " = t." . COL_TIPO_NOME . "
        WHERE b." . COL_BIGLIETTI_ID . " = ?
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
        SELECT b.*, t." . COL_TIPO_MODIFICATORE_PREZZO . ", sb." . COL_SETTORE_BIGLIETTI_FILA . ", sb." . COL_SETTORE_BIGLIETTI_NUMERO . ", s." . COL_SETTORI_ID . " as idSettore
        FROM " . TABLE_BIGLIETTI . " b
        JOIN " . TABLE_TIPO . " t ON b." . COL_BIGLIETTI_ID_CLASSE . " = t." . COL_TIPO_NOME . "
        LEFT JOIN " . TABLE_SETTORE_BIGLIETTI . " sb ON b." . COL_BIGLIETTI_ID . " = sb." . COL_SETTORE_BIGLIETTI_ID_BIGLIETTO . "
        LEFT JOIN " . TABLE_SETTORI . " s ON sb." . COL_SETTORE_BIGLIETTI_ID_SETTORE . " = s." . COL_SETTORI_ID . "
        WHERE b." . COL_BIGLIETTI_ID_EVENTO . " = ?
        ORDER BY b." . COL_BIGLIETTI_ID . "
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
        SELECT b.*, e." . COL_EVENTI_NOME . " as EventoNome, e." . COL_EVENTI_DATA . ", e." . COL_EVENTI_ORA_INIZIO . ", t." . COL_TIPO_MODIFICATORE_PREZZO . "
        FROM " . TABLE_BIGLIETTI . " b
        JOIN " . TABLE_ORDINE_BIGLIETTI . " ob ON b." . COL_BIGLIETTI_ID . " = ob.idBiglietto
        JOIN " . TABLE_EVENTI . " e ON b." . COL_BIGLIETTI_ID_EVENTO . " = e." . COL_EVENTI_ID . "
        JOIN " . TABLE_TIPO . " t ON b." . COL_BIGLIETTI_ID_CLASSE . " = t." . COL_TIPO_NOME . "
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
    $stato = $data['Stato'] ?? STATO_BIGLIETTO_ACQUISTATO;
    $idUtente = $data['idUtente'] ?? null;

    $stmt = $pdo->prepare("
        INSERT INTO " . TABLE_BIGLIETTI . " (" . COL_BIGLIETTI_ID_EVENTO . ", " . COL_BIGLIETTI_ID_CLASSE . ", " . COL_BIGLIETTI_NOME . ", " . COL_BIGLIETTI_COGNOME . ", " . COL_BIGLIETTI_SESSO . ", " . COL_BIGLIETTI_QRCODE . ", " . COL_BIGLIETTI_STATO . ", " . COL_BIGLIETTI_ID_UTENTE . ")
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
        INSERT INTO " . TABLE_SETTORE_BIGLIETTI . " (" . COL_SETTORE_BIGLIETTI_ID_SETTORE . ", " . COL_SETTORE_BIGLIETTI_ID_BIGLIETTO . ", " . COL_SETTORE_BIGLIETTI_FILA . ", " . COL_SETTORE_BIGLIETTI_NUMERO . ")
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
    $stmt = $pdo->prepare("UPDATE " . TABLE_BIGLIETTI . " SET `Check` = TRUE WHERE " . COL_BIGLIETTI_ID . " = ?");
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
    $stmt = $pdo->prepare("SELECT `Check` FROM " . TABLE_BIGLIETTI . " WHERE " . COL_BIGLIETTI_ID . " = ?");
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
    $stmt = $pdo->prepare("DELETE FROM " . TABLE_BIGLIETTI . " WHERE " . COL_BIGLIETTI_ID . " = ?");
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
        SELECT e." . COL_EVENTI_PREZZO_NO_MOD . ", t." . COL_TIPO_MODIFICATORE_PREZZO . ", s." . COL_SETTORI_MOLTIPLICATORE_PREZZO . "
        FROM " . TABLE_EVENTI . " e
        CROSS JOIN " . TABLE_TIPO . " t
        CROSS JOIN " . TABLE_SETTORI . " s
        WHERE e." . COL_EVENTI_ID . " = ? AND t." . COL_TIPO_NOME . " = ? AND s." . COL_SETTORI_ID . " = ?
    ");
    $stmt->execute([$idEvento, $idClasse, $idSettore]);
    $result = $stmt->fetch();

    if (!$result) {
        return 0.0;
    }

    return ($result[COL_EVENTI_PREZZO_NO_MOD] + $result[COL_TIPO_MODIFICATORE_PREZZO]) * $result[COL_SETTORI_MOLTIPLICATORE_PREZZO];
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
        SELECT DISTINCT b.*, e." . COL_EVENTI_NOME . " as EventoNome, e." . COL_EVENTI_DATA . ", e." . COL_EVENTI_ORA_INIZIO . ", e." . COL_EVENTI_ORA_FINE . ",
               e." . COL_EVENTI_IMMAGINE . " as EventoImmagine,
               l." . COL_LOCATIONS_NOME . " as LocationName,
               sb." . COL_SETTORE_BIGLIETTI_ID_SETTORE . ", sb." . COL_SETTORE_BIGLIETTI_FILA . ", sb." . COL_SETTORE_BIGLIETTI_NUMERO . " as PostoNumero,
               o." . COL_ORDINI_ID . " as idOrdine,
               (e." . COL_EVENTI_PREZZO_NO_MOD . " + t." . COL_TIPO_MODIFICATORE_PREZZO . ") * COALESCE(s." . COL_SETTORI_MOLTIPLICATORE_PREZZO . ", 1) as PrezzoFinale
        FROM " . TABLE_BIGLIETTI . " b
        JOIN " . TABLE_EVENTI . " e ON b." . COL_BIGLIETTI_ID_EVENTO . " = e." . COL_EVENTI_ID . "
        JOIN " . TABLE_LOCATIONS . " l ON e." . COL_EVENTI_ID_LOCATION . " = l." . COL_LOCATIONS_ID . "
        JOIN " . TABLE_TIPO . " t ON b." . COL_BIGLIETTI_ID_CLASSE . " = t." . COL_TIPO_NOME . "
        LEFT JOIN " . TABLE_SETTORE_BIGLIETTI . " sb ON b." . COL_BIGLIETTI_ID . " = sb." . COL_SETTORE_BIGLIETTI_ID_BIGLIETTO . "
        LEFT JOIN " . TABLE_SETTORI . " s ON sb." . COL_SETTORE_BIGLIETTI_ID_SETTORE . " = s." . COL_SETTORI_ID . "
        LEFT JOIN " . TABLE_ORDINE_BIGLIETTI . " ob ON b." . COL_BIGLIETTI_ID . " = ob.idBiglietto
        LEFT JOIN " . TABLE_ORDINI . " o ON ob.idOrdine = o." . COL_ORDINI_ID . "
        LEFT JOIN " . TABLE_UTENTE_ORDINI . " uo ON o." . COL_ORDINI_ID . " = uo.idOrdine
        WHERE e." . COL_EVENTI_DATA . " >= CURDATE()
          AND (b." . COL_BIGLIETTI_STATO . " = '" . STATO_BIGLIETTO_ACQUISTATO . "' OR b." . COL_BIGLIETTI_STATO . " IS NULL)
          AND (uo.idUtente = ? OR b." . COL_BIGLIETTI_ID_UTENTE . " = ?)
        ORDER BY e." . COL_EVENTI_DATA . ", e." . COL_EVENTI_ORA_INIZIO . "
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
        SELECT DISTINCT b.*, e." . COL_EVENTI_NOME . " as EventoNome, e." . COL_EVENTI_DATA . ", e." . COL_EVENTI_ORA_INIZIO . ",
               e." . COL_EVENTI_IMMAGINE . " as EventoImmagine,
               l." . COL_LOCATIONS_NOME . " as LocationName,
               sb." . COL_SETTORE_BIGLIETTI_ID_SETTORE . ", sb." . COL_SETTORE_BIGLIETTI_FILA . ", sb." . COL_SETTORE_BIGLIETTI_NUMERO . " as PostoNumero,
               o." . COL_ORDINI_ID . " as idOrdine,
               (e." . COL_EVENTI_PREZZO_NO_MOD . " + t." . COL_TIPO_MODIFICATORE_PREZZO . ") * COALESCE(s." . COL_SETTORI_MOLTIPLICATORE_PREZZO . ", 1) as PrezzoFinale
        FROM " . TABLE_BIGLIETTI . " b
        JOIN " . TABLE_EVENTI . " e ON b." . COL_BIGLIETTI_ID_EVENTO . " = e." . COL_EVENTI_ID . "
        JOIN " . TABLE_LOCATIONS . " l ON e." . COL_EVENTI_ID_LOCATION . " = l." . COL_LOCATIONS_ID . "
        JOIN " . TABLE_TIPO . " t ON b." . COL_BIGLIETTI_ID_CLASSE . " = t." . COL_TIPO_NOME . "
        LEFT JOIN " . TABLE_SETTORE_BIGLIETTI . " sb ON b." . COL_BIGLIETTI_ID . " = sb." . COL_SETTORE_BIGLIETTI_ID_BIGLIETTO . "
        LEFT JOIN " . TABLE_SETTORI . " s ON sb." . COL_SETTORE_BIGLIETTI_ID_SETTORE . " = s." . COL_SETTORI_ID . "
        LEFT JOIN " . TABLE_ORDINE_BIGLIETTI . " ob ON b." . COL_BIGLIETTI_ID . " = ob.idBiglietto
        LEFT JOIN " . TABLE_ORDINI . " o ON ob.idOrdine = o." . COL_ORDINI_ID . "
        LEFT JOIN " . TABLE_UTENTE_ORDINI . " uo ON o." . COL_ORDINI_ID . " = uo.idOrdine
        WHERE e." . COL_EVENTI_DATA . " < CURDATE()
          AND (b." . COL_BIGLIETTI_STATO . " = '" . STATO_BIGLIETTO_ACQUISTATO . "' OR b." . COL_BIGLIETTI_STATO . " IS NULL)
          AND (uo.idUtente = ? OR b." . COL_BIGLIETTI_ID_UTENTE . " = ?)
        ORDER BY e." . COL_EVENTI_DATA . " DESC, e." . COL_EVENTI_ORA_INIZIO . " DESC
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
        FROM " . TABLE_BIGLIETTI . " b
        LEFT JOIN " . TABLE_ORDINE_BIGLIETTI . " ob ON b." . COL_BIGLIETTI_ID . " = ob.idBiglietto
        LEFT JOIN " . TABLE_ORDINI . " o ON ob.idOrdine = o." . COL_ORDINI_ID . "
        LEFT JOIN " . TABLE_UTENTE_ORDINI . " uo ON o." . COL_ORDINI_ID . " = uo.idOrdine
        WHERE b." . COL_BIGLIETTI_ID_EVENTO . " = ?
          AND (b." . COL_BIGLIETTI_STATO . " = '" . STATO_BIGLIETTO_ACQUISTATO . "' OR b." . COL_BIGLIETTI_STATO . " IS NULL)
          AND (uo.idUtente = ? OR b." . COL_BIGLIETTI_ID_UTENTE . " = ?)
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
        FROM " . TABLE_BIGLIETTI . "
        WHERE " . COL_BIGLIETTI_ID_EVENTO . " = ?
          AND LOWER(TRIM(" . COL_BIGLIETTI_NOME . ")) = LOWER(TRIM(?))
          AND LOWER(TRIM(" . COL_BIGLIETTI_COGNOME . ")) = LOWER(TRIM(?))
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
        INSERT INTO " . TABLE_BIGLIETTI . " (" . COL_BIGLIETTI_ID_EVENTO . ", " . COL_BIGLIETTI_ID_CLASSE . ", " . COL_BIGLIETTI_NOME . ", " . COL_BIGLIETTI_COGNOME . ", " . COL_BIGLIETTI_SESSO . ", " . COL_BIGLIETTI_QRCODE . ", " . COL_BIGLIETTI_STATO . ", " . COL_BIGLIETTI_ID_UTENTE . ", " . COL_BIGLIETTI_DATA_CARRELLO . ")
        VALUES (?, ?, '', '', '" . SESSO_ALTRO . "', ?, '" . STATO_BIGLIETTO_CARRELLO . "', ?, NOW())
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
    $stmt = $pdo->prepare("SELECT " . COL_SETTORI_POSTI_DISPONIBILI . " FROM " . TABLE_SETTORI . " WHERE " . COL_SETTORI_ID . " = ?");
    $stmt->execute([$idSettore]);
    $settore = $stmt->fetch();

    if (!$settore) {
        return false;
    }

    $postiTotali = $settore[COL_SETTORI_POSTI_DISPONIBILI];

    // Trova i posti già occupati per questo settore e questo evento
    $stmt = $pdo->prepare("
        SELECT sb." . COL_SETTORE_BIGLIETTI_FILA . ", sb." . COL_SETTORE_BIGLIETTI_NUMERO . "
        FROM " . TABLE_SETTORE_BIGLIETTI . " sb
        JOIN " . TABLE_BIGLIETTI . " b ON sb." . COL_SETTORE_BIGLIETTI_ID_BIGLIETTO . " = b." . COL_BIGLIETTI_ID . "
        WHERE sb." . COL_SETTORE_BIGLIETTI_ID_SETTORE . " = ? AND b." . COL_BIGLIETTI_ID_EVENTO . " = ?
        ORDER BY sb." . COL_SETTORE_BIGLIETTI_FILA . ", sb." . COL_SETTORE_BIGLIETTI_NUMERO . "
    ");
    $stmt->execute([$idSettore, $idEvento]);
    $postiOccupati = $stmt->fetchAll();

    // Crea un set di posti occupati
    $occupati = [];
    foreach ($postiOccupati as $po) {
        $occupati[$po[COL_SETTORE_BIGLIETTI_FILA] . '-' . $po[COL_SETTORE_BIGLIETTI_NUMERO]] = true;
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
        SELECT b.*, e." . COL_EVENTI_NOME . " as EventoNome, e." . COL_EVENTI_DATA . ", e." . COL_EVENTI_ORA_INIZIO . ", e." . COL_EVENTI_PREZZO_NO_MOD . ", e." . COL_EVENTI_IMMAGINE . ",
               t." . COL_TIPO_MODIFICATORE_PREZZO . ",
               sb." . COL_SETTORE_BIGLIETTI_ID_SETTORE . ", sb." . COL_SETTORE_BIGLIETTI_FILA . ", sb." . COL_SETTORE_BIGLIETTI_NUMERO . " as PostoNumero,
               s." . COL_SETTORI_MOLTIPLICATORE_PREZZO . ",
               (e." . COL_EVENTI_PREZZO_NO_MOD . " + t." . COL_TIPO_MODIFICATORE_PREZZO . ") * COALESCE(s." . COL_SETTORI_MOLTIPLICATORE_PREZZO . ", 1) as PrezzoFinale
        FROM " . TABLE_BIGLIETTI . " b
        JOIN " . TABLE_EVENTI . " e ON b." . COL_BIGLIETTI_ID_EVENTO . " = e." . COL_EVENTI_ID . "
        JOIN " . TABLE_TIPO . " t ON b." . COL_BIGLIETTI_ID_CLASSE . " = t." . COL_TIPO_NOME . "
        LEFT JOIN " . TABLE_SETTORE_BIGLIETTI . " sb ON b." . COL_BIGLIETTI_ID . " = sb." . COL_SETTORE_BIGLIETTI_ID_BIGLIETTO . "
        LEFT JOIN " . TABLE_SETTORI . " s ON sb." . COL_SETTORE_BIGLIETTI_ID_SETTORE . " = s." . COL_SETTORI_ID . "
        WHERE b." . COL_BIGLIETTI_ID_UTENTE . " = ? AND b." . COL_BIGLIETTI_STATO . " = '" . STATO_BIGLIETTO_CARRELLO . "'
        ORDER BY b." . COL_BIGLIETTI_DATA_CARRELLO . " DESC
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
        FROM " . TABLE_BIGLIETTI . "
        WHERE " . COL_BIGLIETTI_ID_UTENTE . " = ? AND " . COL_BIGLIETTI_STATO . " = '" . STATO_BIGLIETTO_CARRELLO . "'
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
function updateBigliettoCart(PDO $pdo, int $idBiglietto, string $nome, string $cognome, string $sesso = null): bool
{
    if ($sesso === null) {
        $sesso = SESSO_ALTRO;
    }
    $stmt = $pdo->prepare("
        UPDATE " . TABLE_BIGLIETTI . "
        SET " . COL_BIGLIETTI_NOME . " = ?, " . COL_BIGLIETTI_COGNOME . " = ?, " . COL_BIGLIETTI_SESSO . " = ?
        WHERE " . COL_BIGLIETTI_ID . " = ? AND " . COL_BIGLIETTI_STATO . " = '" . STATO_BIGLIETTO_CARRELLO . "'
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
        UPDATE " . TABLE_BIGLIETTI . "
        SET " . COL_BIGLIETTI_ID_CLASSE . " = ?
        WHERE " . COL_BIGLIETTI_ID . " = ? AND " . COL_BIGLIETTI_STATO . " = '" . STATO_BIGLIETTO_CARRELLO . "'
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
        DELETE FROM " . TABLE_BIGLIETTI . "
        WHERE " . COL_BIGLIETTI_ID . " = ? AND " . COL_BIGLIETTI_ID_UTENTE . " = ? AND " . COL_BIGLIETTI_STATO . " = '" . STATO_BIGLIETTO_CARRELLO . "'
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
        DELETE FROM " . TABLE_BIGLIETTI . "
        WHERE " . COL_BIGLIETTI_ID_UTENTE . " = ? AND " . COL_BIGLIETTI_STATO . " = '" . STATO_BIGLIETTO_CARRELLO . "'
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
        UPDATE " . TABLE_BIGLIETTI . "
        SET " . COL_BIGLIETTI_STATO . " = '" . STATO_BIGLIETTO_ACQUISTATO . "'
        WHERE " . COL_BIGLIETTI_ID . " IN ($placeholders) AND " . COL_BIGLIETTI_STATO . " = '" . STATO_BIGLIETTO_CARRELLO . "'
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
            SELECT COUNT(*) as count FROM " . TABLE_BIGLIETTI . " WHERE " . COL_BIGLIETTI_ID_EVENTO . " = ?
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM " . TABLE_BIGLIETTI . "
            WHERE " . COL_BIGLIETTI_ID_EVENTO . " = ? AND " . COL_BIGLIETTI_STATO . " = '" . STATO_BIGLIETTO_ACQUISTATO . "'
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
    // Usa i settori per calcolare la disponibilità
    require_once __DIR__ . '/EventoSettori.php';
    $maxBiglietti = calcolaBigliettiDisponibili($pdo, $idEvento);

    if ($maxBiglietti === 0) {
        return true; // Nessun limite (nessun settore configurato)
    }

    $attuali = countBigliettiEvento($pdo, $idEvento, true);
    return ($attuali + $quantita) <= $maxBiglietti;
}

/**
 * Recupera biglietti disponibili per un evento
 *
 * @return int Numero biglietti ancora disponibili (null = illimitati)
 */
function getBigliettiDisponibili(PDO $pdo, int $idEvento): ?int
{
    // Usa i settori per calcolare la disponibilità
    require_once __DIR__ . '/EventoSettori.php';
    $maxBiglietti = calcolaBigliettiDisponibili($pdo, $idEvento);

    if ($maxBiglietti === 0) {
        return null; // Illimitati (nessun settore configurato)
    }

    $venduti = countBigliettiEvento($pdo, $idEvento, true);
    return max(0, $maxBiglietti - $venduti);
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
        DELETE FROM " . TABLE_BIGLIETTI . "
        WHERE " . COL_BIGLIETTI_STATO . " = '" . STATO_BIGLIETTO_CARRELLO . "'
        AND " . COL_BIGLIETTI_DATA_CARRELLO . " < DATE_SUB(NOW(), INTERVAL ? HOUR)
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
        UPDATE " . TABLE_BIGLIETTI . "
        SET " . COL_BIGLIETTI_ID_UTENTE . " = ?
        WHERE " . COL_BIGLIETTI_ID . " IN ($placeholders) AND " . COL_BIGLIETTI_STATO . " = '" . STATO_BIGLIETTO_CARRELLO . "'
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
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM " . TABLE_SETTORE_BIGLIETTI . " WHERE " . COL_SETTORE_BIGLIETTI_ID_BIGLIETTO . " = ?");
    $stmt->execute([$idBiglietto]);
    $existing = $stmt->fetch();
    if ($existing && $existing['count'] > 0) {
        return true; // Già assegnato, considera successo
    }

    // Recupera l'evento del biglietto
    $stmt = $pdo->prepare("
        SELECT b." . COL_BIGLIETTI_ID_EVENTO . ", e." . COL_EVENTI_ID_LOCATION . "
        FROM " . TABLE_BIGLIETTI . " b
        JOIN " . TABLE_EVENTI . " e ON b." . COL_BIGLIETTI_ID_EVENTO . " = e." . COL_EVENTI_ID . "
        WHERE b." . COL_BIGLIETTI_ID . " = ?
    ");
    $stmt->execute([$idBiglietto]);
    $info = $stmt->fetch();

    if (!$info) {
        return false;
    }

    $idEvento = $info[COL_BIGLIETTI_ID_EVENTO];
    $idLocation = $info[COL_EVENTI_ID_LOCATION];

    // Recupera i settori della location (ordinati per prezzo decrescente)
    $stmt = $pdo->prepare("SELECT " . COL_SETTORI_ID . ", " . COL_SETTORI_POSTI_DISPONIBILI . " FROM " . TABLE_SETTORI . " WHERE " . COL_SETTORI_ID_LOCATION . " = ? ORDER BY " . COL_SETTORI_MOLTIPLICATORE_PREZZO . " DESC");
    $stmt->execute([$idLocation]);
    $settori = $stmt->fetchAll();

    if (empty($settori)) {
        return false;
    }

    // Per ogni settore, cerca un posto disponibile
    foreach ($settori as $settore) {
        $idSettore = $settore[COL_SETTORI_ID];
        $postiTotali = $settore[COL_SETTORI_POSTI_DISPONIBILI];

        // Trova i posti già occupati per questo settore e questo evento
        $stmt = $pdo->prepare("
            SELECT sb." . COL_SETTORE_BIGLIETTI_FILA . ", sb." . COL_SETTORE_BIGLIETTI_NUMERO . "
            FROM " . TABLE_SETTORE_BIGLIETTI . " sb
            JOIN " . TABLE_BIGLIETTI . " b ON sb." . COL_SETTORE_BIGLIETTI_ID_BIGLIETTO . " = b." . COL_BIGLIETTI_ID . "
            WHERE sb." . COL_SETTORE_BIGLIETTI_ID_SETTORE . " = ? AND b." . COL_BIGLIETTI_ID_EVENTO . " = ?
            ORDER BY sb." . COL_SETTORE_BIGLIETTI_FILA . ", sb." . COL_SETTORE_BIGLIETTI_NUMERO . "
        ");
        $stmt->execute([$idSettore, $idEvento]);
        $postiOccupati = $stmt->fetchAll();

        // Crea un set di posti occupati
        $occupati = [];
        foreach ($postiOccupati as $po) {
            $occupati[$po[COL_SETTORE_BIGLIETTI_FILA] . '-' . $po[COL_SETTORE_BIGLIETTI_NUMERO]] = true;
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
