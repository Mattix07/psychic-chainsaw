<?php
/**
 * Model Permessi
 * Gestisce i permessi per eventi, locations e manifestazioni
 */

require_once __DIR__ . '/../config/database_schema.php';
require_once __DIR__ . '/../lib/QueryBuilder.php';
require_once __DIR__ . '/../lib/EmailService.php';

/**
 * Verifica se un utente può modificare un evento
 * - Admin e mod possono modificare qualsiasi evento
 * - Promoter può modificare solo eventi che ha creato o a cui collabora
 */
function canEditEvento(PDO $pdo, int $userId, int $eventoId): bool
{
    // Ottieni ruolo utente
    $stmt = $pdo->prepare("SELECT " . COL_UTENTI_RUOLO . " FROM " . TABLE_UTENTI . " WHERE " . COL_UTENTI_ID . " = ?");
    $stmt->execute([$userId]);
    $ruolo = $stmt->fetchColumn();

    // Admin e mod possono modificare tutto
    if ($ruolo === RUOLO_ADMIN || $ruolo === RUOLO_MOD) {
        return true;
    }

    // Promoter può modificare solo se è il creatore o collaboratore
    if ($ruolo === RUOLO_PROMOTER) {
        // Verifica se è il creatore
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . TABLE_CREATORI_EVENTI . " WHERE idEvento = ? AND idUtente = ?");
        $stmt->execute([$eventoId, $userId]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }

        // Verifica se è collaboratore con stato accepted
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . TABLE_COLLABORATORI_EVENTI . " WHERE idEvento = ? AND idUtente = ? AND " . COL_COLLABORATORI_EVENTI_STATUS . " = '" . STATUS_ACCEPTED . "'");
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
    $stmt = $pdo->prepare("SELECT " . COL_UTENTI_RUOLO . " FROM " . TABLE_UTENTI . " WHERE " . COL_UTENTI_ID . " = ?");
    $stmt->execute([$userId]);
    $ruolo = $stmt->fetchColumn();

    if ($ruolo === RUOLO_ADMIN || $ruolo === RUOLO_MOD) {
        return true;
    }

    if ($ruolo === RUOLO_PROMOTER) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . TABLE_CREATORI_LOCATIONS . " WHERE idLocation = ? AND idUtente = ?");
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
    $stmt = $pdo->prepare("SELECT " . COL_UTENTI_RUOLO . " FROM " . TABLE_UTENTI . " WHERE " . COL_UTENTI_ID . " = ?");
    $stmt->execute([$userId]);
    $ruolo = $stmt->fetchColumn();

    if ($ruolo === RUOLO_ADMIN || $ruolo === RUOLO_MOD) {
        return true;
    }

    if ($ruolo === RUOLO_PROMOTER) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . TABLE_CREATORI_MANIFESTAZIONI . " WHERE idManifestazione = ? AND idUtente = ?");
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
        $stmt = $pdo->prepare("INSERT INTO " . TABLE_CREATORI_EVENTI . " (idEvento, idUtente) VALUES (?, ?)");
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
        $stmt = $pdo->prepare("INSERT INTO " . TABLE_CREATORI_LOCATIONS . " (idLocation, idUtente) VALUES (?, ?)");
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
        $stmt = $pdo->prepare("INSERT INTO " . TABLE_CREATORI_MANIFESTAZIONI . " (idManifestazione, idUtente) VALUES (?, ?)");
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
    $stmt = $pdo->prepare("SELECT " . COL_UTENTI_RUOLO . " FROM " . TABLE_UTENTI . " WHERE " . COL_UTENTI_ID . " = ?");
    $stmt->execute([$invitedUserId]);
    $ruolo = $stmt->fetchColumn();

    if ($ruolo !== RUOLO_PROMOTER) {
        return false;
    }

    // Genera token univoco
    $token = bin2hex(random_bytes(32));

    try {
        $stmt = $pdo->prepare("
            INSERT INTO " . TABLE_COLLABORATORI_EVENTI . " (idEvento, idUtente, " . COL_COLLABORATORI_EVENTI_INVITATO_DA . ", " . COL_COLLABORATORI_EVENTI_TOKEN . ")
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$eventoId, $invitedUserId, $invitedBy, $token]);

        // Invia email di invito
        $stmtEvento = $pdo->prepare("SELECT " . COL_EVENTI_NOME . " FROM " . TABLE_EVENTI . " WHERE " . COL_EVENTI_ID . " = ?");
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
        $stmt = $pdo->prepare("UPDATE " . TABLE_COLLABORATORI_EVENTI . " SET " . COL_COLLABORATORI_EVENTI_STATUS . " = '" . STATUS_ACCEPTED . "', " . COL_COLLABORATORI_EVENTI_UPDATED_AT . " = NOW() WHERE " . COL_COLLABORATORI_EVENTI_TOKEN . " = ? AND " . COL_COLLABORATORI_EVENTI_STATUS . " = '" . STATUS_PENDING . "'");
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
        $stmt = $pdo->prepare("UPDATE " . TABLE_COLLABORATORI_EVENTI . " SET " . COL_COLLABORATORI_EVENTI_STATUS . " = '" . STATUS_DECLINED . "', " . COL_COLLABORATORI_EVENTI_UPDATED_AT . " = NOW() WHERE " . COL_COLLABORATORI_EVENTI_TOKEN . " = ? AND " . COL_COLLABORATORI_EVENTI_STATUS . " = '" . STATUS_PENDING . "'");
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
        SELECT u." . COL_UTENTI_ID . ", u." . COL_UTENTI_NOME . ", u." . COL_UTENTI_COGNOME . ", u." . COL_UTENTI_EMAIL . ", ce." . COL_COLLABORATORI_EVENTI_STATUS . ", ce." . COL_COLLABORATORI_EVENTI_CREATED_AT . ",
               inviter." . COL_UTENTI_NOME . " as InvitatoDaNome, inviter." . COL_UTENTI_COGNOME . " as InvitatoDaCognome
        FROM " . TABLE_COLLABORATORI_EVENTI . " ce
        JOIN " . TABLE_UTENTI . " u ON ce.idUtente = u." . COL_UTENTI_ID . "
        JOIN " . TABLE_UTENTI . " inviter ON ce." . COL_COLLABORATORI_EVENTI_INVITATO_DA . " = inviter." . COL_UTENTI_ID . "
        WHERE ce.idEvento = ?
        ORDER BY ce." . COL_COLLABORATORI_EVENTI_CREATED_AT . " DESC
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
        SELECT u." . COL_UTENTI_ID . ", u." . COL_UTENTI_NOME . ", u." . COL_UTENTI_COGNOME . ", u." . COL_UTENTI_EMAIL . "
        FROM " . TABLE_CREATORI_EVENTI . " ce
        JOIN " . TABLE_UTENTI . " u ON ce.idUtente = u." . COL_UTENTI_ID . "
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
    if (!$creator || $creator[COL_UTENTI_ID] == $modifiedBy) {
        return; // Non notificare se è il creatore stesso a modificare
    }

    // Ottieni info evento
    $stmt = $pdo->prepare("SELECT " . COL_EVENTI_NOME . " FROM " . TABLE_EVENTI . " WHERE " . COL_EVENTI_ID . " = ?");
    $stmt->execute([$eventoId]);
    $nomeEvento = $stmt->fetchColumn();

    // Invia email
    $emailService = new EmailService($pdo, false);
    $emailService->sendEventModifiedNotification($creator[COL_UTENTI_ID], $modifiedBy, $eventoId, $nomeEvento, $modifiche);
}

/**
 * Ottieni tutti gli eventi creati da un utente
 */
function getEventiCreatiDaUtente(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT e.*, l." . COL_LOCATIONS_NOME . " as LocationNome, m." . COL_MANIFESTAZIONI_NOME . " as ManifestazioneName
        FROM " . TABLE_EVENTI . " e
        JOIN " . TABLE_CREATORI_EVENTI . " ce ON e." . COL_EVENTI_ID . " = ce.idEvento
        JOIN " . TABLE_LOCATIONS . " l ON e." . COL_EVENTI_ID_LOCATION . " = l." . COL_LOCATIONS_ID . "
        JOIN " . TABLE_MANIFESTAZIONI . " m ON e." . COL_EVENTI_ID_MANIFESTAZIONE . " = m." . COL_MANIFESTAZIONI_ID . "
        WHERE ce.idUtente = ?
        ORDER BY e." . COL_EVENTI_DATA . " DESC
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
        SELECT e.*, l." . COL_LOCATIONS_NOME . " as LocationNome, m." . COL_MANIFESTAZIONI_NOME . " as ManifestazioneName
        FROM " . TABLE_EVENTI . " e
        JOIN " . TABLE_COLLABORATORI_EVENTI . " ce ON e." . COL_EVENTI_ID . " = ce.idEvento
        JOIN " . TABLE_LOCATIONS . " l ON e." . COL_EVENTI_ID_LOCATION . " = l." . COL_LOCATIONS_ID . "
        JOIN " . TABLE_MANIFESTAZIONI . " m ON e." . COL_EVENTI_ID_MANIFESTAZIONE . " = m." . COL_MANIFESTAZIONI_ID . "
        WHERE ce.idUtente = ? AND ce." . COL_COLLABORATORI_EVENTI_STATUS . " = '" . STATUS_ACCEPTED . "'
        ORDER BY e." . COL_EVENTI_DATA . " DESC
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
        FROM " . TABLE_LOCATIONS . " l
        JOIN " . TABLE_CREATORI_LOCATIONS . " cl ON l." . COL_LOCATIONS_ID . " = cl.idLocation
        WHERE cl.idUtente = ?
        ORDER BY l." . COL_LOCATIONS_NOME . "
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
        FROM " . TABLE_MANIFESTAZIONI . " m
        JOIN " . TABLE_CREATORI_MANIFESTAZIONI . " cm ON m." . COL_MANIFESTAZIONI_ID . " = cm.idManifestazione
        WHERE cm.idUtente = ?
        ORDER BY m." . COL_MANIFESTAZIONI_NOME . "
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
