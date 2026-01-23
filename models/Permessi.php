<?php
/**
 * Model Permessi
 * Gestisce i permessi per eventi, locations e manifestazioni
 */

require_once __DIR__ . '/../lib/EmailService.php';

/**
 * Verifica se un utente può modificare un evento
 * - Admin e mod possono modificare qualsiasi evento
 * - Promoter può modificare solo eventi che ha creato o a cui collabora
 */
function canEditEvento(PDO $pdo, int $userId, int $eventoId): bool
{
    // Ottieni ruolo utente
    $stmt = $pdo->prepare("SELECT ruolo FROM Utenti WHERE id = ?");
    $stmt->execute([$userId]);
    $ruolo = $stmt->fetchColumn();

    // Admin e mod possono modificare tutto
    if ($ruolo === 'admin' || $ruolo === 'mod') {
        return true;
    }

    // Promoter può modificare solo se è il creatore o collaboratore
    if ($ruolo === 'promoter') {
        // Verifica se è il creatore
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM CreatoriEventi WHERE idEvento = ? AND idUtente = ?");
        $stmt->execute([$eventoId, $userId]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }

        // Verifica se è collaboratore con stato accepted
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM CollaboratoriEventi WHERE idEvento = ? AND idUtente = ? AND status = 'accepted'");
        $stmt->execute([$eventoId, $userId]);
        return $stmt->fetchColumn() > 0;
    }

    return false;
}

/**
 * Verifica se un utente può modificare una location
 */
function canEditLocation(PDO $pdo, int $userId, int $locationId): bool
{
    $stmt = $pdo->prepare("SELECT ruolo FROM Utenti WHERE id = ?");
    $stmt->execute([$userId]);
    $ruolo = $stmt->fetchColumn();

    if ($ruolo === 'admin' || $ruolo === 'mod') {
        return true;
    }

    if ($ruolo === 'promoter') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM CreatoriLocations WHERE idLocation = ? AND idUtente = ?");
        $stmt->execute([$locationId, $userId]);
        return $stmt->fetchColumn() > 0;
    }

    return false;
}

/**
 * Verifica se un utente può modificare una manifestazione
 */
function canEditManifestazione(PDO $pdo, int $userId, int $manifestazioneId): bool
{
    $stmt = $pdo->prepare("SELECT ruolo FROM Utenti WHERE id = ?");
    $stmt->execute([$userId]);
    $ruolo = $stmt->fetchColumn();

    if ($ruolo === 'admin' || $ruolo === 'mod') {
        return true;
    }

    if ($ruolo === 'promoter') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM CreatoriManifestazioni WHERE idManifestazione = ? AND idUtente = ?");
        $stmt->execute([$manifestazioneId, $userId]);
        return $stmt->fetchColumn() > 0;
    }

    return false;
}

/**
 * Registra la creazione di un evento da parte di un utente
 */
function registerEventoCreator(PDO $pdo, int $eventoId, int $userId): bool
{
    try {
        $stmt = $pdo->prepare("INSERT INTO CreatoriEventi (idEvento, idUtente) VALUES (?, ?)");
        return $stmt->execute([$eventoId, $userId]);
    } catch (Exception $e) {
        error_log("Errore registrazione creatore evento: " . $e->getMessage());
        return false;
    }
}

/**
 * Registra la creazione di una location
 */
function registerLocationCreator(PDO $pdo, int $locationId, int $userId): bool
{
    try {
        $stmt = $pdo->prepare("INSERT INTO CreatoriLocations (idLocation, idUtente) VALUES (?, ?)");
        return $stmt->execute([$locationId, $userId]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Registra la creazione di una manifestazione
 */
function registerManifestazioneCreator(PDO $pdo, int $manifestazioneId, int $userId): bool
{
    try {
        $stmt = $pdo->prepare("INSERT INTO CreatoriManifestazioni (idManifestazione, idUtente) VALUES (?, ?)");
        return $stmt->execute([$manifestazioneId, $userId]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Invita un promoter a collaborare su un evento
 */
function inviteCollaborator(PDO $pdo, int $eventoId, int $invitedUserId, int $invitedBy): bool
{
    // Verifica che l'utente invitato sia un promoter
    $stmt = $pdo->prepare("SELECT ruolo FROM Utenti WHERE id = ?");
    $stmt->execute([$invitedUserId]);
    $ruolo = $stmt->fetchColumn();

    if ($ruolo !== 'promoter') {
        return false;
    }

    // Genera token univoco
    $token = bin2hex(random_bytes(32));

    try {
        $stmt = $pdo->prepare("
            INSERT INTO CollaboratoriEventi (idEvento, idUtente, invitato_da, token)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$eventoId, $invitedUserId, $invitedBy, $token]);

        // Invia email di invito
        $stmtEvento = $pdo->prepare("SELECT Nome FROM Eventi WHERE id = ?");
        $stmtEvento->execute([$eventoId]);
        $nomeEvento = $stmtEvento->fetchColumn();

        $emailService = new EmailService($pdo, false); // false = solo log, no invio reale
        $emailService->sendCollaborationInvite($invitedUserId, $invitedBy, $eventoId, $nomeEvento, $token);

        return true;
    } catch (Exception $e) {
        error_log("Errore invito collaboratore: " . $e->getMessage());
        return false;
    }
}

/**
 * Accetta un invito a collaborare
 */
function acceptCollaborationInvite(PDO $pdo, string $token): bool
{
    try {
        $stmt = $pdo->prepare("UPDATE CollaboratoriEventi SET status = 'accepted', updated_at = NOW() WHERE token = ? AND status = 'pending'");
        return $stmt->execute([$token]) && $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Rifiuta un invito a collaborare
 */
function declineCollaborationInvite(PDO $pdo, string $token): bool
{
    try {
        $stmt = $pdo->prepare("UPDATE CollaboratoriEventi SET status = 'declined', updated_at = NOW() WHERE token = ? AND status = 'pending'");
        return $stmt->execute([$token]) && $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Ottieni lista collaboratori di un evento
 */
function getEventCollaborators(PDO $pdo, int $eventoId): array
{
    $stmt = $pdo->prepare("
        SELECT u.id, u.Nome, u.Cognome, u.Email, ce.status, ce.created_at,
               inviter.Nome as InvitatoDaNome, inviter.Cognome as InvitatoDaCognome
        FROM CollaboratoriEventi ce
        JOIN Utenti u ON ce.idUtente = u.id
        JOIN Utenti inviter ON ce.invitato_da = inviter.id
        WHERE ce.idEvento = ?
        ORDER BY ce.created_at DESC
    ");
    $stmt->execute([$eventoId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Ottieni il creatore di un evento
 */
function getEventCreator(PDO $pdo, int $eventoId): ?array
{
    $stmt = $pdo->prepare("
        SELECT u.id, u.Nome, u.Cognome, u.Email
        FROM CreatoriEventi ce
        JOIN Utenti u ON ce.idUtente = u.id
        WHERE ce.idEvento = ?
        LIMIT 1
    ");
    $stmt->execute([$eventoId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

/**
 * Invia notifica email al creatore dell'evento per modifica
 */
function notifyEventModification(PDO $pdo, int $eventoId, int $modifiedBy, array $modifiche): void
{
    // Ottieni il creatore
    $creator = getEventCreator($pdo, $eventoId);
    if (!$creator || $creator['id'] == $modifiedBy) {
        return; // Non notificare se è il creatore stesso a modificare
    }

    // Ottieni info evento
    $stmt = $pdo->prepare("SELECT Nome FROM Eventi WHERE id = ?");
    $stmt->execute([$eventoId]);
    $nomeEvento = $stmt->fetchColumn();

    // Invia email
    $emailService = new EmailService($pdo, false);
    $emailService->sendEventModifiedNotification($creator['id'], $modifiedBy, $eventoId, $nomeEvento, $modifiche);
}

/**
 * Ottieni tutti gli eventi creati da un utente
 */
function getEventiCreatiDaUtente(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT e.*, l.Nome as LocationNome, m.Nome as ManifestazioneName
        FROM Eventi e
        JOIN CreatoriEventi ce ON e.id = ce.idEvento
        JOIN Locations l ON e.idLocation = l.id
        JOIN Manifestazioni m ON e.idManifestazione = m.id
        WHERE ce.idUtente = ?
        ORDER BY e.Data DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Ottieni tutti gli eventi a cui un utente collabora
 */
function getEventiCollaborazione(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT e.*, l.Nome as LocationNome, m.Nome as ManifestazioneName
        FROM Eventi e
        JOIN CollaboratoriEventi ce ON e.id = ce.idEvento
        JOIN Locations l ON e.idLocation = l.id
        JOIN Manifestazioni m ON e.idManifestazione = m.id
        WHERE ce.idUtente = ? AND ce.status = 'accepted'
        ORDER BY e.Data DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Ottieni locations create da un promoter
 */
function getLocationsCreatoDaPromoter(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT l.*
        FROM Locations l
        JOIN CreatoriLocations cl ON l.id = cl.idLocation
        WHERE cl.idUtente = ?
        ORDER BY l.Nome
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Ottieni manifestazioni create da un promoter
 */
function getManifestazioniCreateDaPromoter(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT m.*
        FROM Manifestazioni m
        JOIN CreatoriManifestazioni cm ON m.id = cm.idManifestazione
        WHERE cm.idUtente = ?
        ORDER BY m.Nome
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
