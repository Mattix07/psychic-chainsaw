<?php
/**
 * Controller Biglietti
 * Gestisce acquisto, validazione e visualizzazione biglietti
 *
 * Il processo di acquisto include:
 * - Verifica disponibilita posti
 * - Controllo duplicati (stesso intestatario per stesso evento)
 * - Creazione biglietto con QR code
 * - Assegnazione posto e creazione ordine
 */

require_once __DIR__ . '/../models/Biglietto.php';
require_once __DIR__ . '/../models/Ordine.php';
require_once __DIR__ . '/../models/Evento.php';
require_once __DIR__ . '/../models/Location.php';

/**
 * Router interno per le azioni sui biglietti
 *
 * @param string $action Azione da eseguire
 */
function handleBiglietto(PDO $pdo, string $action): void
{
    switch ($action) {
        case 'acquista':
            acquistaBiglietto($pdo);
            break;

        case 'valida':
            requireAdmin();
            validaBigliettoAction($pdo);
            break;

        case 'view_biglietto':
            viewBiglietto($pdo);
            break;
    }
}

/**
 * Gestisce l'acquisto di biglietti dal carrello
 * Supporta sia biglietti da DB (utenti loggati) che da localStorage
 * Esegue tutte le operazioni in una transazione per garantire consistenza
 */
function acquistaBiglietto(PDO $pdo): void
{
    requireAuth();

    // Blocca acquisto per promoter/admin/mod
    $ruolo = $_SESSION['user_ruolo'] ?? 'user';
    if (in_array($ruolo, ['admin', 'mod', 'promoter'])) {
        redirect('index.php?action=checkout', null, 'Gli organizzatori non possono acquistare biglietti');
    }

    if (!verifyCsrf()) {
        redirect('index.php?action=checkout', null, 'Richiesta non valida');
    }

    $metodo = sanitize($_POST['metodo'] ?? '');
    $cartData = json_decode($_POST['cart_data'] ?? '[]', true);

    // Validazione metodo pagamento
    $metodiValidi = ['Carta di credito', 'PayPal', 'Bonifico'];
    if (!in_array($metodo, $metodiValidi)) {
        redirect('index.php?action=checkout', null, 'Metodo di pagamento non valido');
    }

    // Verifica se i dati vengono dal server (biglietti gia nel DB)
    $fromServer = isset($cartData['fromServer']) && $cartData['fromServer'] === true;

    if ($fromServer) {
        // Acquisto da carrello DB
        acquistaFromServerCart($pdo, $cartData, $metodo);
    } else {
        // Acquisto da localStorage (vecchio metodo)
        acquistaFromLocalCart($pdo, $cartData, $metodo);
    }
}

/**
 * Acquista biglietti gia presenti nel DB (carrello server-side)
 */
function acquistaFromServerCart(PDO $pdo, array $cartData, string $metodo): void
{
    $tickets = $cartData['tickets'] ?? [];

    if (empty($tickets)) {
        redirect('index.php?action=checkout', null, 'Nessun biglietto da acquistare');
    }

    // Valida che tutti i biglietti abbiano i dati completi
    foreach ($tickets as $ticket) {
        if (empty($ticket['nome']) || empty($ticket['cognome'])) {
            redirect('index.php?action=checkout', null, 'Compila i dati di tutti i biglietti');
        }
    }

    try {
        $pdo->beginTransaction();

        // Aggiorna i dati dei biglietti e conferma l'acquisto
        $bigliettiIds = [];
        foreach ($tickets as $ticket) {
            $idBiglietto = (int) $ticket['bigliettoId'];
            if ($idBiglietto <= 0) continue;

            // Verifica che il biglietto esista e sia nel carrello dell'utente
            $stmt = $pdo->prepare("SELECT id FROM Biglietti WHERE id = ? AND idUtente = ? AND Stato = 'carrello'");
            $stmt->execute([$idBiglietto, $_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                throw new Exception("Biglietto non valido: " . $idBiglietto);
            }

            // Aggiorna dati intestatario
            $stmt = $pdo->prepare("UPDATE Biglietti SET Nome = ?, Cognome = ?, Sesso = ? WHERE id = ?");
            $stmt->execute([
                sanitize($ticket['nome']),
                sanitize($ticket['cognome']),
                sanitize($ticket['sesso'] ?? 'Altro'),
                $idBiglietto
            ]);

            $bigliettiIds[] = $idBiglietto;
        }

        // Conferma l'acquisto (cambia stato da 'carrello' a 'acquistato')
        if (!empty($bigliettiIds)) {
            confirmPurchase($pdo, $bigliettiIds);
            // Assegna automaticamente un posto a ogni biglietto
            assegnaPostiAutomatici($pdo, $bigliettiIds);
        }

        // Crea ordine e associa biglietti
        $idOrdine = createOrdine($pdo, $metodo);
        foreach ($bigliettiIds as $idBiglietto) {
            associaOrdineBiglietto($pdo, $idOrdine, $idBiglietto);
        }
        associaOrdineUtente($pdo, $idOrdine, $_SESSION['user_id']);

        $pdo->commit();

        redirect('index.php?action=view_ordine&id=' . $idOrdine,
            count($bigliettiIds) . ' bigliett' . (count($bigliettiIds) > 1 ? 'i acquistati' : 'o acquistato') . ' con successo!');

    } catch (Exception $e) {
        $pdo->rollBack();
        logError("Errore acquisto biglietti: " . $e->getMessage());
        redirect('index.php?action=checkout', null, 'Errore durante l\'acquisto: ' . $e->getMessage());
    }
}

/**
 * Acquista biglietti da localStorage (vecchio metodo per utenti non loggati)
 */
function acquistaFromLocalCart(PDO $pdo, array $cartData, string $metodo): void
{
    if (empty($cartData)) {
        redirect('index.php?action=checkout', null, 'Nessun biglietto da acquistare');
    }

    // Valida che tutti i biglietti abbiano i dati completi
    foreach ($cartData as $ticket) {
        if (empty($ticket['nome']) || empty($ticket['cognome'])) {
            redirect('index.php?action=checkout', null, 'Compila i dati di tutti i biglietti');
        }
    }

    try {
        $pdo->beginTransaction();

        $bigliettiIds = [];
        foreach ($cartData as $ticket) {
            $idEvento = (int) ($ticket['eventoId'] ?? 0);
            $idClasse = sanitize($ticket['tipoId'] ?? $ticket['ticketType'] ?? 'Standard');
            $nome = sanitize($ticket['nome']);
            $cognome = sanitize($ticket['cognome']);
            $sesso = sanitize($ticket['sesso'] ?? 'Altro');

            if ($idEvento <= 0) continue;

            // Crea il biglietto con stato 'acquistato'
            $idBiglietto = createBiglietto($pdo, [
                'idEvento' => $idEvento,
                'idClasse' => $idClasse,
                'Nome' => $nome,
                'Cognome' => $cognome,
                'Sesso' => $sesso,
                'Stato' => 'acquistato',
                'idUtente' => $_SESSION['user_id']
            ]);

            $bigliettiIds[] = $idBiglietto;
        }

        // Assegna automaticamente un posto a ogni biglietto
        if (!empty($bigliettiIds)) {
            assegnaPostiAutomatici($pdo, $bigliettiIds);
        }

        // Crea ordine e associa biglietti
        $idOrdine = createOrdine($pdo, $metodo);
        foreach ($bigliettiIds as $idBiglietto) {
            associaOrdineBiglietto($pdo, $idOrdine, $idBiglietto);
        }
        associaOrdineUtente($pdo, $idOrdine, $_SESSION['user_id']);

        $pdo->commit();

        redirect('index.php?action=view_ordine&id=' . $idOrdine,
            count($bigliettiIds) . ' bigliett' . (count($bigliettiIds) > 1 ? 'i acquistati' : 'o acquistato') . ' con successo!');

    } catch (Exception $e) {
        $pdo->rollBack();
        logError("Errore acquisto biglietti: " . $e->getMessage());
        redirect('index.php?action=checkout', null, 'Errore durante l\'acquisto');
    }
}

/**
 * Valida un biglietto all'ingresso (solo admin)
 * Marca il biglietto come utilizzato
 */
function validaBigliettoAction(PDO $pdo): void
{
    if (!verifyCsrf()) {
        redirect('index.php', null, 'Richiesta non valida');
    }

    $id = (int) $_POST['id'];

    // Previene doppia validazione
    if (isBigliettoValidato($pdo, $id)) {
        redirect('index.php', null, 'Biglietto gia\' validato');
    }

    try {
        validaBiglietto($pdo, $id);
        redirect('index.php', 'Biglietto validato con successo');
    } catch (Exception $e) {
        logError("Errore validazione biglietto: " . $e->getMessage());
        redirect('index.php', null, 'Errore durante la validazione');
    }
}

/**
 * Mostra dettaglio di un biglietto
 * Richiede autenticazione
 */
function viewBiglietto(PDO $pdo): void
{
    requireAuth();

    $id = (int) ($_GET['id'] ?? 0);
    $biglietto = getBigliettoById($pdo, $id);

    if (!$biglietto) {
        redirect('index.php', null, 'Biglietto non trovato');
    }

    $_SESSION['biglietto_corrente'] = $biglietto;
    setPage('biglietto_dettaglio');
}
