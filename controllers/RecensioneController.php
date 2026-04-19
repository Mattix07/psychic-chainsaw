<?php
/**
 * Controller Recensioni
 * Gestisce creazione, modifica e eliminazione delle recensioni eventi
 *
 * Solo gli utenti che hanno acquistato un biglietto possono recensire.
 * Ogni utente puo lasciare una sola recensione per evento.
 */

require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/messages.php';
require_once __DIR__ . '/../lib/Validator.php';
require_once __DIR__ . '/../lib/QueryBuilder.php';
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

        case 'hide_recensione':
            hideRecensioneAction($pdo);
            break;

        case 'restore_recensione':
            restoreRecensioneAction($pdo);
            break;

        case 'flag_recensione':
            flagRecensioneAction($pdo);
            break;

        case 'get_recensioni_admin':
            getRecensioniAdminAction($pdo);
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

    if (!verifyCsrf()) {
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

    if (!verifyCsrf()) {
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

    if (!verifyCsrf()) {
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

// ============================================================
// F8 — Moderazione recensioni
// ============================================================

if (!function_exists('jsonResponse')) {
    function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

function hideRecensioneAction(PDO $pdo): void
{
    requireRole(RUOLO_MOD);
    if (!verifyCsrf()) { jsonResponse(['error' => ERR_INVALID_CSRF], 403); return; }

    $id = (int)($_POST['id'] ?? 0);
    table($pdo, TABLE_RECENSIONI)
        ->where(COL_RECENSIONI_ID, $id)
        ->update([
            COL_RECENSIONI_STATO       => STATO_RECENSIONE_NASCOSTA,
            COL_RECENSIONI_MODERATA_DA => $_SESSION['user_id'],
            COL_RECENSIONI_MODERATA_AT => date('Y-m-d H:i:s'),
        ]);
    jsonResponse(['success' => true]);
}

function restoreRecensioneAction(PDO $pdo): void
{
    requireRole(RUOLO_MOD);
    if (!verifyCsrf()) { jsonResponse(['error' => ERR_INVALID_CSRF], 403); return; }

    $id = (int)($_POST['id'] ?? 0);
    table($pdo, TABLE_RECENSIONI)
        ->where(COL_RECENSIONI_ID, $id)
        ->update([
            COL_RECENSIONI_STATO       => STATO_RECENSIONE_VISIBILE,
            COL_RECENSIONI_MODERATA_DA => $_SESSION['user_id'],
            COL_RECENSIONI_MODERATA_AT => date('Y-m-d H:i:s'),
        ]);
    jsonResponse(['success' => true]);
}

function flagRecensioneAction(PDO $pdo): void
{
    if (!isLoggedIn()) { jsonResponse(['error' => ERR_LOGIN_REQUIRED], 401); return; }
    if (!verifyCsrf()) { jsonResponse(['error' => ERR_INVALID_CSRF], 403); return; }

    $id = (int)($_POST['id'] ?? 0);
    $rec = table($pdo, TABLE_RECENSIONI)->where(COL_RECENSIONI_ID, $id)->first();

    if ($rec && $rec[COL_RECENSIONI_STATO] === STATO_RECENSIONE_VISIBILE) {
        table($pdo, TABLE_RECENSIONI)
            ->where(COL_RECENSIONI_ID, $id)
            ->update([COL_RECENSIONI_STATO => STATO_RECENSIONE_SEGNALATA]);

        // Notifica mod/admin
        $mods = table($pdo, TABLE_UTENTI)
            ->select([COL_UTENTI_ID])
            ->whereIn(COL_UTENTI_RUOLO, [RUOLO_MOD, RUOLO_ADMIN])
            ->get();
        foreach ($mods as $mod) {
            table($pdo, TABLE_NOTIFICHE)->insert([
                COL_NOTIFICHE_DESTINATARIO_ID => $mod[COL_UTENTI_ID],
                COL_NOTIFICHE_TIPO            => 'moderazione',
                COL_NOTIFICHE_MESSAGGIO       => 'Recensione #' . $id . ' segnalata come inappropriata',
                COL_NOTIFICHE_LETTA           => 0,
                COL_NOTIFICHE_CREATED_AT      => date('Y-m-d H:i:s'),
            ]);
        }
    }
    jsonResponse(['success' => true]);
}

function getRecensioniAdminAction(PDO $pdo): void
{
    requireRole(RUOLO_MOD);
    $stato = $_GET['stato'] ?? null;

    $sql = "SELECT r.*, u." . COL_UTENTI_NOME . ", u." . COL_UTENTI_COGNOME . ",
                   e." . COL_EVENTI_NOME . " AS EventoNome
            FROM " . TABLE_RECENSIONI . " r
            JOIN " . TABLE_UTENTI . " u ON r." . COL_RECENSIONI_ID_UTENTE . " = u." . COL_UTENTI_ID . "
            JOIN " . TABLE_EVENTI . " e ON r." . COL_RECENSIONI_ID_EVENTO . " = e." . COL_EVENTI_ID;

    $params = [];
    if ($stato) {
        $sql .= " WHERE r." . COL_RECENSIONI_STATO . " = ?";
        $params[] = $stato;
    }
    $sql .= " ORDER BY FIELD(r." . COL_RECENSIONI_STATO . ", 'segnalata', 'nascosta', 'visibile'), r." . COL_RECENSIONI_CREATED_AT . " DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    jsonResponse(['recensioni' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}
