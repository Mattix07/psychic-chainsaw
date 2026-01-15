<?php

/**
 * Controller per la gestione dei Biglietti
 */

require_once __DIR__ . '/../models/Biglietto.php';
require_once __DIR__ . '/../models/Ordine.php';
require_once __DIR__ . '/../models/Evento.php';
require_once __DIR__ . '/../models/Location.php';

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

    // Validazione
    if (empty($nome) || empty($cognome) || empty($sesso)) {
        redirect('index.php?action=view_evento&id=' . $idEvento, null, 'Compila tutti i campi');
    }

    if (!in_array($metodo, ['Carta', 'PayPal', 'Bonifico'])) {
        redirect('index.php?action=view_evento&id=' . $idEvento, null, 'Metodo di pagamento non valido');
    }

    if (!in_array($sesso, ['M', 'F', 'Altro'])) {
        redirect('index.php?action=view_evento&id=' . $idEvento, null, 'Sesso non valido');
    }

    // Verifica disponibilita' posti
    $postiDisponibili = getPostiDisponibiliSettore($pdo, $idSettore, $idEvento);
    if ($postiDisponibili <= 0) {
        redirect('index.php?action=view_evento&id=' . $idEvento, null, 'Posti esauriti nel settore selezionato');
    }

    try {
        $pdo->beginTransaction();

        // Crea biglietto
        $idBiglietto = createBiglietto($pdo, [
            'idEvento' => $idEvento,
            'idClasse' => $idClasse,
            'Nome' => $nome,
            'Cognome' => $cognome,
            'Sesso' => $sesso
        ]);

        // Assegna posto (semplificato - fila A, numero progressivo)
        $fila = 'A';
        $numero = $postiDisponibili;
        assegnaPosto($pdo, $idBiglietto, $idSettore, $fila, $numero);

        // Crea ordine
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

function validaBigliettoAction(PDO $pdo): void
{
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        redirect('index.php', null, 'Richiesta non valida');
    }

    $id = (int) $_POST['id'];

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
