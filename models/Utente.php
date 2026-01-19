<?php
/**
 * Model Utente
 * Gestisce operazioni CRUD, autenticazione e sistema di ruoli
 */

// Costanti per i ruoli utente - gerarchia crescente di permessi
define('ROLE_USER', 'user');
define('ROLE_PROMOTER', 'promoter');
define('ROLE_MOD', 'mod');
define('ROLE_ADMIN', 'admin');

/**
 * Recupera tutti gli utenti ordinati alfabeticamente
 * @return array Lista utenti
 */
function getAllUtenti(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM Utenti ORDER BY Cognome, Nome")->fetchAll();
}

/**
 * Recupera un utente tramite ID
 * @return array|null Dati utente o null se non trovato
 */
function getUtenteById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM Utenti WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Recupera un utente tramite email (per login)
 * @return array|null Dati utente o null se non trovato
 */
function getUtenteByEmail(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM Utenti WHERE Email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}

/**
 * Crea un nuovo utente
 * @return int ID del nuovo utente
 */
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

/**
 * Aggiorna i dati anagrafici di un utente
 * @return bool Esito operazione
 */
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

/**
 * Elimina un utente e i relativi dati (cascade nel DB)
 * @return bool Esito operazione
 */
function deleteUtente(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM Utenti WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Recupera lo storico ordini di un utente
 * @return array Lista ordini ordinati dal piu recente
 */
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

/**
 * Recupera le recensioni scritte da un utente
 * @return array Lista recensioni con nome evento
 */
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

/**
 * Aggiorna la password di un utente
 * La password viene automaticamente hashata con bcrypt
 * @return bool Esito operazione
 */
function updateUtentePassword(PDO $pdo, int $id, string $newPassword): bool
{
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE Utenti SET Password = ? WHERE id = ?");
    return $stmt->execute([$hashedPassword, $id]);
}

/**
 * Imposta il token per la verifica email
 * Il token scade dopo 24 ore
 * @return bool Esito operazione
 */
function setVerificationToken(PDO $pdo, int $id, string $token): bool
{
    $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $stmt = $pdo->prepare("UPDATE Utenti SET verification_token = ?, token_expiry = ? WHERE id = ?");
    return $stmt->execute([$token, $expiry, $id]);
}

/**
 * Verifica la validita di un token di verifica email
 * Controlla che il token esista, non sia scaduto e l'email non sia gia verificata
 * @return array|null Dati utente o null se token invalido
 */
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

/**
 * Marca l'email come verificata e rimuove il token
 * @return bool Esito operazione
 */
function markEmailVerified(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("
        UPDATE Utenti
        SET email_verified = 1, verification_token = NULL, token_expiry = NULL
        WHERE id = ?
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
        UPDATE Utenti
        SET reset_token = ?, reset_expiry = ?
        WHERE Email = ?
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
        SELECT * FROM Utenti
        WHERE reset_token = ?
          AND reset_expiry > NOW()
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
    $stmt = $pdo->prepare("UPDATE Utenti SET reset_token = NULL, reset_expiry = NULL WHERE id = ?");
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
        UPDATE Utenti
        SET Password = ?, reset_token = NULL, reset_expiry = NULL
        WHERE id = ?
    ");
    return $stmt->execute([$hashedPassword, $user['id']]);
}

/**
 * Recupera il ruolo di un utente dal database
 * @return string Ruolo utente (default: ROLE_USER)
 */
function getUserRole(PDO $pdo, int $id): string
{
    $stmt = $pdo->prepare("SELECT ruolo FROM Utenti WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    return $result['ruolo'] ?? ROLE_USER;
}

/**
 * Imposta il ruolo di un utente
 * Verifica che il ruolo sia valido prima dell'aggiornamento
 * @return bool Esito operazione
 */
function setUserRole(PDO $pdo, int $id, string $role): bool
{
    $validRoles = [ROLE_USER, ROLE_PROMOTER, ROLE_MOD, ROLE_ADMIN];
    if (!in_array($role, $validRoles)) {
        return false;
    }

    $stmt = $pdo->prepare("UPDATE Utenti SET ruolo = ? WHERE id = ?");
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
    return getUserRole($pdo, $id) === ROLE_ADMIN;
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
    return in_array($role, [ROLE_MOD, ROLE_ADMIN]);
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
    return in_array($role, [ROLE_PROMOTER, ROLE_MOD, ROLE_ADMIN]);
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
    $hierarchy = [ROLE_USER => 1, ROLE_PROMOTER => 2, ROLE_MOD => 3, ROLE_ADMIN => 4];

    return ($hierarchy[$role] ?? 0) >= ($hierarchy[$requiredRole] ?? 0);
}

/**
 * Recupera tutti gli utenti con un determinato ruolo
 * @return array Lista utenti filtrati per ruolo
 */
function getUtentiByRole(PDO $pdo, string $role): array
{
    $stmt = $pdo->prepare("SELECT * FROM Utenti WHERE ruolo = ? ORDER BY Cognome, Nome");
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
