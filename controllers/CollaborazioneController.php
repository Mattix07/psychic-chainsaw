<?php
/**
 * Controller Collaborazione
 * Gestisce inviti e collaboratori per eventi
 */

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
    $stmt = $pdo->prepare("SELECT id, ruolo FROM Utenti WHERE Email = ?");
    $stmt->execute([$emailCollaboratore]);
    $utente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$utente) {
        jsonResponse(['error' => 'Utente non trovato'], 404);
        return;
    }

    if ($utente['ruolo'] !== 'promoter') {
        jsonResponse(['error' => 'Puoi invitare solo altri promoter'], 400);
        return;
    }

    // Verifica che non sia già collaboratore
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM CollaboratoriEventi WHERE idEvento = ? AND idUtente = ?");
    $stmt->execute([$idEvento, $utente['id']]);
    if ($stmt->fetchColumn() > 0) {
        jsonResponse(['error' => 'Questo utente è già stato invitato'], 400);
        return;
    }

    // Invia invito
    if (inviteCollaborator($pdo, $idEvento, $utente['id'], $userId)) {
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
 */
function acceptCollaborationApi(PDO $pdo): void
{
    $token = $_GET['token'] ?? '';

    if (!$token) {
        header('Location: index.php?error=token_mancante');
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
 */
function declineCollaborationApi(PDO $pdo): void
{
    $token = $_GET['token'] ?? '';

    if (!$token) {
        header('Location: index.php?error=token_mancante');
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

    jsonResponse([
        'success' => true,
        'creator' => $creator,
        'collaborators' => $collaborators
    ]);
}

function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
