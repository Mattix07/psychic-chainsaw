<?php

/**
 * Controller per la gestione delle Recensioni
 */

require_once __DIR__ . '/../models/Recensione.php';

function handleRecensione(PDO $pdo, string $action): void
{
    switch ($action) {
        case 'add_recensione':
            addRecensioneAction($pdo);
            break;

        case 'update_recensione':
            updateRecensioneAction($pdo);
            break;

        case 'delete_recensione':
            deleteRecensioneAction($pdo);
            break;
    }
}

function addRecensioneAction(PDO $pdo): void
{
    requireAuth();

    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        redirect('index.php', null, 'Richiesta non valida');
    }

    $idEvento = (int) $_POST['idEvento'];
    $idUtente = $_SESSION['user_id'];
    $voto = (int) $_POST['voto'];
    $messaggio = sanitize($_POST['messaggio'] ?? '');

    // Validazione voto
    if ($voto < 1 || $voto > 5) {
        redirect('index.php?action=view_evento&id=' . $idEvento, null, 'Voto non valido (1-5)');
    }

    // Verifica se ha acquistato un biglietto per questo evento
    if (!hasAcquistatoBiglietto($pdo, $idEvento, $idUtente)) {
        redirect('index.php?action=view_evento&id=' . $idEvento, null, 'Devi aver acquistato un biglietto per poter recensire questo evento');
    }

    // Verifica se ha gia' recensito
    if (hasRecensito($pdo, $idEvento, $idUtente)) {
        redirect('index.php?action=view_evento&id=' . $idEvento, null, 'Hai gia\' recensito questo evento');
    }

    try {
        createRecensione($pdo, $idEvento, $idUtente, $voto, $messaggio ?: null);
        redirect('index.php?action=view_evento&id=' . $idEvento, 'Recensione aggiunta con successo');
    } catch (Exception $e) {
        logError("Errore creazione recensione: " . $e->getMessage());
        redirect('index.php?action=view_evento&id=' . $idEvento, null, 'Errore durante l\'aggiunta della recensione');
    }
}

function updateRecensioneAction(PDO $pdo): void
{
    requireAuth();

    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        redirect('index.php', null, 'Richiesta non valida');
    }

    $idEvento = (int) $_POST['idEvento'];
    $idUtente = $_SESSION['user_id'];
    $voto = (int) $_POST['voto'];
    $messaggio = sanitize($_POST['messaggio'] ?? '');

    if ($voto < 1 || $voto > 5) {
        redirect('index.php?action=view_evento&id=' . $idEvento, null, 'Voto non valido (1-5)');
    }

    try {
        updateRecensione($pdo, $idEvento, $idUtente, $voto, $messaggio ?: null);
        redirect('index.php?action=view_evento&id=' . $idEvento, 'Recensione aggiornata con successo');
    } catch (Exception $e) {
        logError("Errore aggiornamento recensione: " . $e->getMessage());
        redirect('index.php?action=view_evento&id=' . $idEvento, null, 'Errore durante l\'aggiornamento');
    }
}

function deleteRecensioneAction(PDO $pdo): void
{
    requireAuth();

    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        redirect('index.php', null, 'Richiesta non valida');
    }

    $idEvento = (int) $_POST['idEvento'];
    $idUtente = $_SESSION['user_id'];

    try {
        deleteRecensione($pdo, $idEvento, $idUtente);
        redirect('index.php?action=view_evento&id=' . $idEvento, 'Recensione eliminata');
    } catch (Exception $e) {
        logError("Errore eliminazione recensione: " . $e->getMessage());
        redirect('index.php?action=view_evento&id=' . $idEvento, null, 'Errore durante l\'eliminazione');
    }
}
