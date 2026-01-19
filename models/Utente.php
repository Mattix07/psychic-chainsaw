<?php

/**
 * Model per la gestione degli Utenti
 */

function getAllUtenti(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM Utenti ORDER BY Cognome, Nome")->fetchAll();
}

function getUtenteById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM Utenti WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getUtenteByEmail(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM Utenti WHERE Email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}

function createUtente(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare("
        INSERT INTO Utenti (Nome, Cognome, Email)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([
        $data['Nome'],
        $data['Cognome'],
        $data['Email']
    ]);
    return (int) $pdo->lastInsertId();
}

function updateUtente(PDO $pdo, int $id, array $data): bool
{
    $stmt = $pdo->prepare("
        UPDATE Utenti SET Nome = ?, Cognome = ?, Email = ?
        WHERE id = ?
    ");
    return $stmt->execute([
        $data['Nome'],
        $data['Cognome'],
        $data['Email'],
        $id
    ]);
}

function deleteUtente(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM Utenti WHERE id = ?");
    return $stmt->execute([$id]);
}

function getOrdiniUtente(PDO $pdo, int $idUtente): array
{
    $stmt = $pdo->prepare("
        SELECT o.*
        FROM Ordini o
        JOIN Utente_Ordini uo ON o.id = uo.idOrdine
        WHERE uo.idUtente = ?
        ORDER BY o.id DESC
    ");
    $stmt->execute([$idUtente]);
    return $stmt->fetchAll();
}

function getRecensioniUtente(PDO $pdo, int $idUtente): array
{
    $stmt = $pdo->prepare("
        SELECT r.*, e.Nome as EventoNome
        FROM Recensioni r
        JOIN Eventi e ON r.idEvento = e.id
        WHERE r.idUtente = ?
        ORDER BY e.Data DESC
    ");
    $stmt->execute([$idUtente]);
    return $stmt->fetchAll();
}

function updateUtentePassword(PDO $pdo, int $id, string $newPassword): bool
{
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE Utenti SET Password = ? WHERE id = ?");
    return $stmt->execute([$hashedPassword, $id]);
}

function setVerificationToken(PDO $pdo, int $id, string $token): bool
{
    $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $stmt = $pdo->prepare("UPDATE Utenti SET verification_token = ?, token_expiry = ? WHERE id = ?");
    return $stmt->execute([$token, $expiry, $id]);
}

function verifyEmailToken(PDO $pdo, string $token): ?array
{
    $stmt = $pdo->prepare("
        SELECT * FROM Utenti
        WHERE verification_token = ?
          AND token_expiry > NOW()
          AND email_verified = 0
    ");
    $stmt->execute([$token]);
    return $stmt->fetch() ?: null;
}

function markEmailVerified(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("
        UPDATE Utenti
        SET email_verified = 1, verification_token = NULL, token_expiry = NULL
        WHERE id = ?
    ");
    return $stmt->execute([$id]);
}

function setResetToken(PDO $pdo, string $email, string $token): bool
{
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $stmt = $pdo->prepare("
        UPDATE Utenti
        SET reset_token = ?, reset_expiry = ?
        WHERE Email = ?
    ");
    return $stmt->execute([$token, $expiry, $email]);
}

function verifyResetToken(PDO $pdo, string $token): ?array
{
    $stmt = $pdo->prepare("
        SELECT * FROM Utenti
        WHERE reset_token = ?
          AND reset_expiry > NOW()
    ");
    $stmt->execute([$token]);
    return $stmt->fetch() ?: null;
}

function clearResetToken(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("UPDATE Utenti SET reset_token = NULL, reset_expiry = NULL WHERE id = ?");
    return $stmt->execute([$id]);
}

function resetPasswordWithToken(PDO $pdo, string $token, string $newPassword): bool
{
    $user = verifyResetToken($pdo, $token);
    if (!$user) {
        return false;
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        UPDATE Utenti
        SET Password = ?, reset_token = NULL, reset_expiry = NULL
        WHERE id = ?
    ");
    return $stmt->execute([$hashedPassword, $user['id']]);
}

// ==========================================
// GESTIONE RUOLI
// ==========================================

define('ROLE_USER', 'user');
define('ROLE_PROMOTER', 'promoter');
define('ROLE_MOD', 'mod');
define('ROLE_ADMIN', 'admin');

function getUserRole(PDO $pdo, int $id): string
{
    $stmt = $pdo->prepare("SELECT ruolo FROM Utenti WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    return $result['ruolo'] ?? ROLE_USER;
}

function setUserRole(PDO $pdo, int $id, string $role): bool
{
    $validRoles = [ROLE_USER, ROLE_PROMOTER, ROLE_MOD, ROLE_ADMIN];
    if (!in_array($role, $validRoles)) {
        return false;
    }

    $stmt = $pdo->prepare("UPDATE Utenti SET ruolo = ? WHERE id = ?");
    return $stmt->execute([$role, $id]);
}

function isAdmin(?int $userId = null): bool
{
    global $pdo;
    $id = $userId ?? ($_SESSION['user_id'] ?? null);
    if (!$id) return false;
    return getUserRole($pdo, $id) === ROLE_ADMIN;
}

function isMod(?int $userId = null): bool
{
    global $pdo;
    $id = $userId ?? ($_SESSION['user_id'] ?? null);
    if (!$id) return false;
    $role = getUserRole($pdo, $id);
    return in_array($role, [ROLE_MOD, ROLE_ADMIN]);
}

function isPromoter(?int $userId = null): bool
{
    global $pdo;
    $id = $userId ?? ($_SESSION['user_id'] ?? null);
    if (!$id) return false;
    $role = getUserRole($pdo, $id);
    return in_array($role, [ROLE_PROMOTER, ROLE_MOD, ROLE_ADMIN]);
}

function hasRole(string $requiredRole, ?int $userId = null): bool
{
    global $pdo;
    $id = $userId ?? ($_SESSION['user_id'] ?? null);
    if (!$id) return false;

    $role = getUserRole($pdo, $id);
    $hierarchy = [ROLE_USER => 1, ROLE_PROMOTER => 2, ROLE_MOD => 3, ROLE_ADMIN => 4];

    return ($hierarchy[$role] ?? 0) >= ($hierarchy[$requiredRole] ?? 0);
}

function getUtentiByRole(PDO $pdo, string $role): array
{
    $stmt = $pdo->prepare("SELECT * FROM Utenti WHERE ruolo = ? ORDER BY Cognome, Nome");
    $stmt->execute([$role]);
    return $stmt->fetchAll();
}

function countUtentiByRole(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT ruolo, COUNT(*) as count
        FROM Utenti
        GROUP BY ruolo
    ");
    $results = $stmt->fetchAll();
    $counts = [];
    foreach ($results as $row) {
        $counts[$row['ruolo']] = $row['count'];
    }
    return $counts;
}
