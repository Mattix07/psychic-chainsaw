<?php
/**
 * Controller Recensioni
 * Gestisce creazione, modifica e eliminazione delle recensioni eventi
 *
 * Solo gli utenti che hanno acquistato un biglietto possono recensire.
 * Ogni utente puo lasciare una sola recensione per evento.
 */

require_once __DIR__ . '/../models/Recensione.php';

/**
 * Router interno per le azioni sulle recensioni
 *
 * @param string $action Azione da eseguire
 */
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

/**
 * Aggiunge una nuova recensione
 * Verifica che l'utente abbia acquistato un biglietto e non abbia gia recensito
 */
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

    // Voto deve essere tra 1 e 5
    if ($voto < 1 || $voto > 5) {
        redirect("index.php?action=view_evento&id={$idEvento}", null, 'Voto non valido (1-5)');
    }

    // Requisito: aver acquistato un biglietto
    if (!hasAcquistatoBiglietto($pdo, $idEvento, $idUtente)) {
        redirect("index.php?action=view_evento&id={$idEvento}", null, 'Devi aver acquistato un biglietto per poter recensire questo evento');
    }

    // Vincolo: una sola recensione per utente/evento
    if (hasRecensito($pdo, $idEvento, $idUtente)) {
        redirect("index.php?action=view_evento&id={$idEvento}", null, 'Hai gia\' recensito questo evento');
    }

    try {
        createRecensione($pdo, $idEvento, $idUtente, $voto, $messaggio ?: null);
        redirect("index.php?action=view_evento&id={$idEvento}", 'Recensione aggiunta con successo');
    } catch (Exception $e) {
        logError("Errore creazione recensione: " . $e->getMessage());
        redirect("index.php?action=view_evento&id={$idEvento}", null, 'Errore durante l\'aggiunta della recensione');
    }
}

/**
 * Modifica una recensione esistente
 * L'utente puo modificare solo le proprie recensioni
 */
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
        redirect("index.php?action=view_evento&id={$idEvento}", null, 'Voto non valido (1-5)');
    }

    try {
        updateRecensione($pdo, $idEvento, $idUtente, $voto, $messaggio ?: null);
        redirect("index.php?action=view_evento&id={$idEvento}", 'Recensione aggiornata con successo');
    } catch (Exception $e) {
        logError("Errore aggiornamento recensione: " . $e->getMessage());
        redirect("index.php?action=view_evento&id={$idEvento}", null, 'Errore durante l\'aggiornamento');
    }
}

/**
 * Elimina una recensione
 * L'utente puo eliminare solo le proprie recensioni
 */
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
        redirect("index.php?action=view_evento&id={$idEvento}", 'Recensione eliminata');
    } catch (Exception $e) {
        logError("Errore eliminazione recensione: " . $e->getMessage());
        redirect("index.php?action=view_evento&id={$idEvento}", null, 'Errore durante l\'eliminazione');
    }
}
