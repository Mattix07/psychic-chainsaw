<?php
/**
 * Model Utente
 * Gestisce operazioni CRUD, autenticazione e sistema di ruoli
 */

require_once __DIR__ . '/../config/database_schema.php';
require_once __DIR__ . '/../lib/QueryBuilder.php';

/**
 * Recupera tutti gli utenti ordinati alfabeticamente
 * @return array Lista utenti
 */
function getAllUtenti(PDO $pdo): array
{
    return table($pdo, TABLE_UTENTI)
        ->orderBy(COL_UTENTI_COGNOME)
        ->orderBy(COL_UTENTI_NOME)
        ->get();
}

/**
 * Recupera un utente tramite ID
 * @return array|null Dati utente o null se non trovato
 */
function getUtenteById(PDO $pdo, int $id): ?array
{
    return table($pdo, TABLE_UTENTI)
        ->where(COL_UTENTI_ID, $id)
        ->first();
}

/**
 * Recupera un utente tramite email (per login)
 * @return array|null Dati utente o null se non trovato
 */
function getUtenteByEmail(PDO $pdo, string $email): ?array
{
    return table($pdo, TABLE_UTENTI)
        ->where(COL_UTENTI_EMAIL, $email)
        ->first();
}

/**
 * Crea un nuovo utente
 * @return int ID del nuovo utente
 */
function createUtente(PDO $pdo, array $data): int
{
    return table($pdo, TABLE_UTENTI)->insert([
        COL_UTENTI_NOME => $data['Nome'],
        COL_UTENTI_COGNOME => $data['Cognome'],
        COL_UTENTI_EMAIL => $data['Email']
    ]);
}

/**
 * Aggiorna i dati anagrafici di un utente
 * @return bool Esito operazione
 */
function updateUtente(PDO $pdo, int $id, array $data): bool
{
    return table($pdo, TABLE_UTENTI)
        ->where(COL_UTENTI_ID, $id)
        ->update([
            COL_UTENTI_NOME => $data['Nome'],
            COL_UTENTI_COGNOME => $data['Cognome'],
            COL_UTENTI_EMAIL => $data['Email']
        ]) > 0;
}

/**
 * Elimina un utente e i relativi dati (cascade nel DB)
 * @return bool Esito operazione
 */
function deleteUtente(PDO $pdo, int $id): bool
{
    return table($pdo, TABLE_UTENTI)
        ->where(COL_UTENTI_ID, $id)
        ->delete() > 0;
}

/**
 * Recupera lo storico ordini di un utente
 * @return array Lista ordini ordinati dal piu recente
 */
function getOrdiniUtente(PDO $pdo, int $idUtente): array
{
    $stmt = $pdo->prepare("
        SELECT o.*
        FROM " . TABLE_ORDINI . " o
        JOIN " . TABLE_UTENTE_ORDINI . " uo ON o." . COL_ORDINI_ID . " = uo.idOrdine
        WHERE uo.idUtente = ?
        ORDER BY o." . COL_ORDINI_ID . " DESC
    ");
    $stmt->execute([$idUtente]);
    return $stmt->fetchAll();
}

/**
 * Recupera le recensioni scritte da un utente
 * @return array Lista recensioni con nome evento
 */
function getRecensioniUtente(PDO $pdo, int $idUtente): array
{
    $stmt = $pdo->prepare("
        SELECT r.*, e." . COL_EVENTI_NOME . " as EventoNome
        FROM " . TABLE_RECENSIONI . " r
        JOIN " . TABLE_EVENTI . " e ON r." . COL_RECENSIONI_ID_EVENTO . " = e." . COL_EVENTI_ID . "
        WHERE r." . COL_RECENSIONI_ID_UTENTE . " = ?
        ORDER BY e." . COL_EVENTI_DATA . " DESC
    ");
    $stmt->execute([$idUtente]);
    return $stmt->fetchAll();
}

/**
 * Aggiorna la password di un utente
 * La password viene automaticamente hashata con bcrypt
 * @return bool Esito operazione
 */
function updateUtentePassword(PDO $pdo, int $id, string $newPassword): bool
{
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE " . TABLE_UTENTI . " SET " . COL_UTENTI_PASSWORD . " = ? WHERE " . COL_UTENTI_ID . " = ?");
    return $stmt->execute([$hashedPassword, $id]);
}

/**
 * Imposta il token per la verifica email
 * Il token scade dopo 24 ore
 * @return bool Esito operazione
 */
function setVerificationToken(PDO $pdo, int $id, string $token): bool
{
    $stmt = $pdo->prepare("UPDATE " . TABLE_UTENTI . " SET " . COL_UTENTI_EMAIL_VERIFICATION_TOKEN . " = ? WHERE " . COL_UTENTI_ID . " = ?");
    return $stmt->execute([$token, $id]);
}

/**
 * Verifica la validita di un token di verifica email
 * Controlla che il token esista, non sia scaduto e l'email non sia gia verificata
 * @return array|null Dati utente o null se token invalido
 */
function verifyEmailToken(PDO $pdo, string $token): ?array
{
    $stmt = $pdo->prepare("
        SELECT * FROM " . TABLE_UTENTI . "
        WHERE " . COL_UTENTI_EMAIL_VERIFICATION_TOKEN . " = ?
          AND " . COL_UTENTI_VERIFICATO . " = 0
    ");
    $stmt->execute([$token]);
    return $stmt->fetch() ?: null;
}

/**
 * Marca l'email come verificata e rimuove il token
 * @return bool Esito operazione
 */
function markEmailVerified(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("
        UPDATE " . TABLE_UTENTI . "
        SET " . COL_UTENTI_VERIFICATO . " = 1, " . COL_UTENTI_EMAIL_VERIFICATION_TOKEN . " = NULL
        WHERE " . COL_UTENTI_ID . " = ?
    ");
    return $stmt->execute([$id]);
}

/**
 * Imposta il token per il recupero password
 * Il token scade dopo 1 ora per sicurezza
 * @return bool Esito operazione
 */
function setResetToken(PDO $pdo, string $email, string $token): bool
{
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $stmt = $pdo->prepare("
        UPDATE " . TABLE_UTENTI . "
        SET " . COL_UTENTI_RESET_TOKEN . " = ?, " . COL_UTENTI_RESET_TOKEN_EXPIRY . " = ?
        WHERE " . COL_UTENTI_EMAIL . " = ?
    ");
    return $stmt->execute([$token, $expiry, $email]);
}

/**
 * Verifica la validita di un token di reset password
 * @return array|null Dati utente o null se token invalido/scaduto
 */
function verifyResetToken(PDO $pdo, string $token): ?array
{
    $stmt = $pdo->prepare("
        SELECT * FROM " . TABLE_UTENTI . "
        WHERE " . COL_UTENTI_RESET_TOKEN . " = ?
          AND " . COL_UTENTI_RESET_TOKEN_EXPIRY . " > NOW()
    ");
    $stmt->execute([$token]);
    return $stmt->fetch() ?: null;
}

/**
 * Rimuove il token di reset (dopo uso o per invalidazione)
 * @return bool Esito operazione
 */
function clearResetToken(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("UPDATE " . TABLE_UTENTI . " SET " . COL_UTENTI_RESET_TOKEN . " = NULL, " . COL_UTENTI_RESET_TOKEN_EXPIRY . " = NULL WHERE " . COL_UTENTI_ID . " = ?");
    return $stmt->execute([$id]);
}

/**
 * Reimposta la password usando il token di reset
 * Verifica il token, aggiorna la password e invalida il token in un'unica operazione
 * @return bool True se reset completato, false se token invalido
 */
function resetPasswordWithToken(PDO $pdo, string $token, string $newPassword): bool
{
    $user = verifyResetToken($pdo, $token);
    if (!$user) {
        return false;
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        UPDATE " . TABLE_UTENTI . "
        SET " . COL_UTENTI_PASSWORD . " = ?, " . COL_UTENTI_RESET_TOKEN . " = NULL, " . COL_UTENTI_RESET_TOKEN_EXPIRY . " = NULL
        WHERE " . COL_UTENTI_ID . " = ?
    ");
    return $stmt->execute([$hashedPassword, $user['id']]);
}

/**
 * Recupera il ruolo di un utente dal database
 * @return string Ruolo utente (default: ROLE_USER)
 */
function getUserRole(PDO $pdo, int $id): string
{
    $stmt = $pdo->prepare("SELECT " . COL_UTENTI_RUOLO . " FROM " . TABLE_UTENTI . " WHERE " . COL_UTENTI_ID . " = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    return $result[COL_UTENTI_RUOLO] ?? RUOLO_USER;
}

/**
 * Imposta il ruolo di un utente
 * Verifica che il ruolo sia valido prima dell'aggiornamento
 * @return bool Esito operazione
 */
function setUserRole(PDO $pdo, int $id, string $role): bool
{
    $validRoles = [RUOLO_USER, RUOLO_PROMOTER, RUOLO_MOD, RUOLO_ADMIN];
    if (!in_array($role, $validRoles)) {
        return false;
    }

    $stmt = $pdo->prepare("UPDATE " . TABLE_UTENTI . " SET " . COL_UTENTI_RUOLO . " = ? WHERE " . COL_UTENTI_ID . " = ?");
    return $stmt->execute([$role, $id]);
}

/**
 * Verifica se l'utente corrente o specificato e un amministratore
 * @param int|null $userId ID utente, se null usa la sessione corrente
 * @return bool True se admin
 */
function isAdmin(?int $userId = null): bool
{
    global $pdo;
    $id = $userId ?? ($_SESSION['user_id'] ?? null);
    if (!$id) return false;
    return getUserRole($pdo, $id) === RUOLO_ADMIN;
}

/**
 * Verifica se l'utente e un moderatore (o superiore)
 * I moderatori hanno accesso a funzioni di gestione contenuti
 * @return bool True se mod o admin
 */
function isMod(?int $userId = null): bool
{
    global $pdo;
    $id = $userId ?? ($_SESSION['user_id'] ?? null);
    if (!$id) return false;
    $role = getUserRole($pdo, $id);
    return in_array($role, [RUOLO_MOD, RUOLO_ADMIN]);
}

/**
 * Verifica se l'utente e un promoter (o superiore)
 * I promoter possono creare e gestire i propri eventi
 * @return bool True se promoter, mod o admin
 */
function isPromoter(?int $userId = null): bool
{
    global $pdo;
    $id = $userId ?? ($_SESSION['user_id'] ?? null);
    if (!$id) return false;
    $role = getUserRole($pdo, $id);
    return in_array($role, [RUOLO_PROMOTER, RUOLO_MOD, RUOLO_ADMIN]);
}

/**
 * Verifica se l'utente ha almeno il ruolo richiesto
 * Utilizza una gerarchia: user < promoter < mod < admin
 * @param string $requiredRole Ruolo minimo richiesto
 * @return bool True se il ruolo e sufficiente
 */
function hasRole(string $requiredRole, ?int $userId = null): bool
{
    global $pdo;
    $id = $userId ?? ($_SESSION['user_id'] ?? null);
    if (!$id) return false;

    $role = getUserRole($pdo, $id);
    $hierarchy = [RUOLO_USER => 1, RUOLO_PROMOTER => 2, RUOLO_MOD => 3, RUOLO_ADMIN => 4];

    return ($hierarchy[$role] ?? 0) >= ($hierarchy[$requiredRole] ?? 0);
}

/**
 * Recupera tutti gli utenti con un determinato ruolo
 * @return array Lista utenti filtrati per ruolo
 */
function getUtentiByRole(PDO $pdo, string $role): array
{
    $stmt = $pdo->prepare("SELECT * FROM " . TABLE_UTENTI . " WHERE " . COL_UTENTI_RUOLO . " = ? ORDER BY " . COL_UTENTI_COGNOME . ", " . COL_UTENTI_NOME);
    $stmt->execute([$role]);
    return $stmt->fetchAll();
}

/**
 * Conta gli utenti raggruppati per ruolo
 * Utile per statistiche nel pannello admin
 * @return array Array associativo [ruolo => conteggio]
 */
function countUtentiByRole(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT " . COL_UTENTI_RUOLO . ", COUNT(*) as count
        FROM " . TABLE_UTENTI . "
        GROUP BY " . COL_UTENTI_RUOLO . "
    ");
    $results = $stmt->fetchAll();
    $counts = [];
    foreach ($results as $row) {
        $counts[$row[COL_UTENTI_RUOLO]] = $row['count'];
    }
    return $counts;
}
