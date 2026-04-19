<?php
/**
 * Controller Collaborazione
 * Gestisce inviti e collaboratori per eventi
 */

require_once __DIR__ . '/../config/database_schema.php';
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/messages.php';
require_once __DIR__ . '/../lib/Validator.php';
require_once __DIR__ . '/../lib/QueryBuilder.php';
require_once __DIR__ . '/../models/Permessi.php';

/**
 * Invita un collaboratore a un evento
 * POST: idEvento, emailCollab or atore
 */
function inviteCollaboratorApi(PDO $pdo): void
{
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'Devi effettuare il login'], 401);
        return;
    }

    if (!verifyCsrf()) {
        jsonResponse(['error' => 'Token CSRF non valido'], 403);
        return;
    }

    $idEvento = (int)($_POST['idEvento'] ?? 0);
    $emailCollaboratore = trim($_POST['emailCollaboratore'] ?? '');

    if (!$idEvento || !$emailCollaboratore) {
        jsonResponse(['error' => 'Dati mancanti'], 400);
        return;
    }

    $userId = $_SESSION['user_id'];

    // Verifica che l'utente possa modificare l'evento
    if (!canEditEvento($pdo, $userId, $idEvento)) {
        jsonResponse(['error' => 'Non hai i permessi per invitare collaboratori'], 403);
        return;
    }

    // Trova l'utente da invitare
    $stmt = $pdo->prepare("SELECT " . COL_UTENTI_ID . ", " . COL_UTENTI_RUOLO . " FROM " . TABLE_UTENTI . " WHERE " . COL_UTENTI_EMAIL . " = ?");
    $stmt->execute([$emailCollaboratore]);
    $utente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$utente) {
        jsonResponse(['error' => 'Utente non trovato'], 404);
        return;
    }

    if ($utente[COL_UTENTI_RUOLO] !== RUOLO_PROMOTER) {
        jsonResponse(['error' => 'Puoi invitare solo altri promoter'], 400);
        return;
    }

    // Verifica che non sia già collaboratore
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . TABLE_COLLABORATORI_EVENTI . " WHERE " . COL_COLLABORATORI_EVENTI_ID_EVENTO . " = ? AND " . COL_COLLABORATORI_EVENTI_ID_UTENTE . " = ?");
    $stmt->execute([$idEvento, $utente[COL_UTENTI_ID]]);
    if ($stmt->fetchColumn() > 0) {
        jsonResponse(['error' => 'Questo utente è già stato invitato'], 400);
        return;
    }

    // Invia invito
    if (inviteCollaborator($pdo, $idEvento, $utente[COL_UTENTI_ID], $userId)) {
        jsonResponse([
            'success' => true,
            'message' => 'Invito inviato con successo'
        ]);
    } else {
        jsonResponse(['error' => 'Errore durante l\'invio dell\'invito'], 500);
    }
}

/**
 * Accetta un invito a collaborare
 * GET: token
 * Richiede autenticazione: verifica che l'utente loggato sia il destinatario dell'invito
 */
function acceptCollaborationApi(PDO $pdo): void
{
    if (!isLoggedIn()) {
        redirect('index.php?action=show_login', null, 'Devi effettuare il login per accettare l\'invito.');
    }

    $token = $_GET['token'] ?? '';

    if (!$token) {
        header('Location: index.php?error=token_mancante');
        exit;
    }

    // Verifica che il token appartenga all'utente loggato
    $stmt = $pdo->prepare("SELECT " . COL_COLLABORATORI_EVENTI_ID_UTENTE . " FROM " . TABLE_COLLABORATORI_EVENTI . " WHERE " . COL_COLLABORATORI_EVENTI_TOKEN . " = ? AND " . COL_COLLABORATORI_EVENTI_STATUS . " = 'pending'");
    $stmt->execute([$token]);
    $invito = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invito || (int)$invito[COL_COLLABORATORI_EVENTI_ID_UTENTE] !== (int)$_SESSION['user_id']) {
        header('Location: index.php?error=invito_non_valido');
        exit;
    }

    if (acceptCollaborationInvite($pdo, $token)) {
        header('Location: index.php?success=invito_accettato');
    } else {
        header('Location: index.php?error=invito_non_valido');
    }
    exit;
}

/**
 * Rifiuta un invito a collaborare
 * GET: token
 * Richiede autenticazione: verifica che l'utente loggato sia il destinatario dell'invito
 */
function declineCollaborationApi(PDO $pdo): void
{
    if (!isLoggedIn()) {
        redirect('index.php?action=show_login', null, 'Devi effettuare il login per rifiutare l\'invito.');
    }

    $token = $_GET['token'] ?? '';

    if (!$token) {
        header('Location: index.php?error=token_mancante');
        exit;
    }

    // Verifica che il token appartenga all'utente loggato
    $stmt = $pdo->prepare("SELECT " . COL_COLLABORATORI_EVENTI_ID_UTENTE . " FROM " . TABLE_COLLABORATORI_EVENTI . " WHERE " . COL_COLLABORATORI_EVENTI_TOKEN . " = ? AND " . COL_COLLABORATORI_EVENTI_STATUS . " = 'pending'");
    $stmt->execute([$token]);
    $invito = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invito || (int)$invito[COL_COLLABORATORI_EVENTI_ID_UTENTE] !== (int)$_SESSION['user_id']) {
        header('Location: index.php?error=invito_non_valido');
        exit;
    }

    if (declineCollaborationInvite($pdo, $token)) {
        header('Location: index.php?success=invito_rifiutato');
    } else {
        header('Location: index.php?error=invito_non_valido');
    }
    exit;
}

/**
 * Ottieni lista collaboratori di un evento
 * GET: idEvento
 */
function getCollaboratorsApi(PDO $pdo): void
{
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'Devi effettuare il login'], 401);
        return;
    }

    $idEvento = (int)($_GET['idEvento'] ?? 0);

    if (!$idEvento) {
        jsonResponse(['error' => 'ID evento mancante'], 400);
        return;
    }

    $userId = $_SESSION['user_id'];

    // Verifica permessi
    if (!canEditEvento($pdo, $userId, $idEvento)) {
        jsonResponse(['error' => 'Non hai i permessi'], 403);
        return;
    }

    $collaborators = getEventCollaborators($pdo, $idEvento);
    $creator = getEventCreator($pdo, $idEvento);

    // Aggiungi flag is_owner a ogni collaboratore
    $collaboratorsWithOwner = array_map(function($c) use ($pdo, $idEvento) {
        $record = table($pdo, TABLE_COLLABORATORI_EVENTI)
            ->select([COL_COLLABORATORI_EVENTI_IS_OWNER])
            ->where(COL_COLLABORATORI_EVENTI_ID_EVENTO, $idEvento)
            ->where(COL_COLLABORATORI_EVENTI_ID_UTENTE, $c['id'])
            ->first();
        $c['is_owner'] = (bool)($record[COL_COLLABORATORI_EVENTI_IS_OWNER] ?? false);
        return $c;
    }, $collaborators);

    jsonResponse([
        'success' => true,
        'creator' => $creator,
        'collaborators' => $collaboratorsWithOwner
    ]);
}

if (!function_exists('jsonResponse')) {
    function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
