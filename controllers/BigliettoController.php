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
 * Gestisce l'acquisto di un biglietto
 * Esegue tutte le operazioni in una transazione per garantire consistenza
 */
function acquistaBiglietto(PDO $pdo): void
{
    requireAuth();

    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        redirect('index.php', null, 'Richiesta non valida');
    }

    $idEvento = (int) $_POST['idEvento'];
    $idClasse = sanitize($_POST['idClasse']);
    $idSettore = (int) $_POST['idSettore'];
    $metodo = sanitize($_POST['metodo']);
    $nome = sanitize($_POST['nome']);
    $cognome = sanitize($_POST['cognome']);
    $sesso = sanitize($_POST['sesso']);

    // Validazione dati intestatario
    if (empty($nome) || empty($cognome) || empty($sesso)) {
        redirect('index.php?action=view_evento&id=' . $idEvento, null, 'Compila tutti i campi');
    }

    // Validazione metodo pagamento
    if (!in_array($metodo, ['Carta', 'PayPal', 'Bonifico'])) {
        redirect('index.php?action=view_evento&id=' . $idEvento, null, 'Metodo di pagamento non valido');
    }

    if (!in_array($sesso, ['M', 'F', 'Altro'])) {
        redirect('index.php?action=view_evento&id=' . $idEvento, null, 'Sesso non valido');
    }

    // Previene acquisti duplicati per stessa persona
    if (esisteBigliettoDuplicato($pdo, $idEvento, $nome, $cognome)) {
        redirect('index.php?action=view_evento&id=' . $idEvento, null,
            'Esiste già un biglietto intestato a ' . $nome . ' ' . $cognome . ' per questo evento');
    }

    // Verifica disponibilita posti nel settore
    $postiDisponibili = getPostiDisponibiliSettore($pdo, $idSettore, $idEvento);
    if ($postiDisponibili <= 0) {
        redirect('index.php?action=view_evento&id=' . $idEvento, null, 'Posti esauriti nel settore selezionato');
    }

    try {
        $pdo->beginTransaction();

        // Crea il biglietto con QR code automatico
        $idBiglietto = createBiglietto($pdo, [
            'idEvento' => $idEvento,
            'idClasse' => $idClasse,
            'Nome' => $nome,
            'Cognome' => $cognome,
            'Sesso' => $sesso
        ]);

        // Assegnazione posto semplificata (fila A, numero progressivo)
        $fila = 'A';
        $numero = $postiDisponibili;
        assegnaPosto($pdo, $idBiglietto, $idSettore, $fila, $numero);

        // Crea ordine e associa biglietto e utente
        $idOrdine = createOrdine($pdo, $metodo);
        associaOrdineBiglietto($pdo, $idOrdine, $idBiglietto);
        associaOrdineUtente($pdo, $idOrdine, $_SESSION['user_id']);

        $pdo->commit();

        $prezzo = calcolaPrezzoFinale($pdo, $idEvento, $idClasse, $idSettore);
        redirect('index.php?action=view_ordine&id=' . $idOrdine,
            'Biglietto acquistato con successo! Totale: ' . formatPrice($prezzo));

    } catch (Exception $e) {
        $pdo->rollBack();
        logError("Errore acquisto biglietto: " . $e->getMessage());
        redirect('index.php?action=view_evento&id=' . $idEvento, null, 'Errore durante l\'acquisto');
    }
}

/**
 * Valida un biglietto all'ingresso (solo admin)
 * Marca il biglietto come utilizzato
 */
function validaBigliettoAction(PDO $pdo): void
{
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
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
