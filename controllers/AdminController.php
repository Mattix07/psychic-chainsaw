<?php
/**
 * Controller Amministrazione
 * Gestisce dashboard e operazioni per admin, moderatori e promoter
 *
 * Implementa un sistema di accesso basato su ruoli con controlli
 * di autorizzazione per ogni funzione sensibile.
 */

require_once __DIR__ . '/../models/Utente.php';
require_once __DIR__ . '/../models/Evento.php';
require_once __DIR__ . '/../models/Ordine.php';

/**
 * Middleware di controllo accesso basato su ruolo
 * Verifica autenticazione e ruolo minimo richiesto
 *
 * @param string $role Ruolo minimo richiesto (usa costanti ROLE_*)
 */
function requireRole(string $role): void
{
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Devi effettuare il login.';
        header('Location: index.php?action=show_login');
        exit;
    }

    if (!hasRole($role)) {
        $_SESSION['error'] = 'Non hai i permessi per accedere a questa area.';
        header('Location: index.php');
        exit;
    }
}

// ==========================================
// ADMIN DASHBOARD
// ==========================================

/**
 * Mostra la dashboard amministratore con statistiche sistema
 * Richiede ruolo ADMIN
 */
function showAdminDashboard(PDO $pdo): void
{
    requireRole(ROLE_ADMIN);

    $stats = [
        'utenti' => countUtentiByRole($pdo),
        'eventi_totali' => countEventi($pdo),
        'eventi_futuri' => countEventiFuturi($pdo),
        'ordini_totali' => countOrdini($pdo)
    ];

    $_SESSION['admin_stats'] = $stats;
    $_SESSION['admin_utenti'] = getAllUtenti($pdo);
    setPage('admin/dashboard');
}

/**
 * Gestione utenti con filtro opzionale per ruolo
 * Richiede ruolo ADMIN
 */
function adminManageUsers(PDO $pdo): void
{
    requireRole(ROLE_ADMIN);

    $filter = $_GET['role'] ?? 'all';
    if ($filter === 'all') {
        $utenti = getAllUtenti($pdo);
    } else {
        $utenti = getUtentiByRole($pdo, $filter);
    }

    $_SESSION['admin_utenti'] = $utenti;
    $_SESSION['admin_filter'] = $filter;
    setPage('admin/utenti');
}

/**
 * Aggiorna il ruolo di un utente
 * Impedisce la modifica del proprio ruolo per sicurezza
 */
function adminUpdateUserRole(PDO $pdo): void
{
    requireRole(ROLE_ADMIN);

    if (!verifyCsrf()) {
        $_SESSION['error'] = 'Token non valido.';
        header('Location: index.php?action=admin_users');
        exit;
    }

    $userId = (int) ($_POST['user_id'] ?? 0);
    $newRole = $_POST['role'] ?? '';

    // Protezione: non permettere modifica del proprio ruolo
    if ($userId === $_SESSION['user_id']) {
        $_SESSION['error'] = 'Non puoi modificare il tuo stesso ruolo.';
        header('Location: index.php?action=admin_users');
        exit;
    }

    if (setUserRole($pdo, $userId, $newRole)) {
        $_SESSION['msg'] = 'Ruolo aggiornato con successo.';
    } else {
        $_SESSION['error'] = 'Errore durante l\'aggiornamento del ruolo.';
    }

    header('Location: index.php?action=admin_users');
    exit;
}

/**
 * Elimina un utente dal sistema
 * Impedisce l'auto-eliminazione dell'admin
 */
function adminDeleteUser(PDO $pdo): void
{
    requireRole(ROLE_ADMIN);

    if (!verifyCsrf()) {
        $_SESSION['error'] = 'Token non valido.';
        header('Location: index.php?action=admin_users');
        exit;
    }

    $userId = (int) ($_POST['user_id'] ?? 0);

    // Protezione: non permettere auto-eliminazione
    if ($userId === $_SESSION['user_id']) {
        $_SESSION['error'] = 'Non puoi eliminare il tuo stesso account.';
        header('Location: index.php?action=admin_users');
        exit;
    }

    if (deleteUtente($pdo, $userId)) {
        $_SESSION['msg'] = 'Utente eliminato con successo.';
    } else {
        $_SESSION['error'] = 'Errore durante l\'eliminazione dell\'utente.';
    }

    header('Location: index.php?action=admin_users');
    exit;
}

/**
 * Lista eventi per amministrazione
 * Richiede almeno ruolo MOD
 */
function adminManageEvents(PDO $pdo): void
{
    requireRole(ROLE_MOD);

    $eventi = getAllEventiAdmin($pdo);
    $_SESSION['admin_eventi'] = $eventi;
    setPage('admin/eventi');
}

/**
 * Creazione nuovo evento
 * Gestisce sia GET (form) che POST (salvataggio)
 * Richiede almeno ruolo PROMOTER
 */
function adminCreateEvent(PDO $pdo): void
{
    requireRole(ROLE_PROMOTER);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrf()) {
            $_SESSION['error'] = 'Token non valido.';
            header('Location: index.php?action=admin_events');
            exit;
        }

        $data = [
            'Nome' => sanitize($_POST['nome'] ?? ''),
            'Data' => $_POST['data'] ?? '',
            'OraI' => $_POST['ora_inizio'] ?? '',
            'OraF' => $_POST['ora_fine'] ?? '',
            'Programma' => sanitize($_POST['programma'] ?? ''),
            'PrezzoNoMod' => (float) ($_POST['prezzo'] ?? 0),
            'idLocation' => (int) ($_POST['location'] ?? 0),
            'idManifestazione' => (int) ($_POST['manifestazione'] ?? 0) ?: null,
            'Immagine' => sanitize($_POST['immagine'] ?? '')
        ];

        if (empty($data['Nome']) || empty($data['Data'])) {
            $_SESSION['error'] = 'Nome e data sono obbligatori.';
            header('Location: index.php?action=admin_create_event');
            exit;
        }

        try {
            createEvento($pdo, $data);
            $_SESSION['msg'] = 'Evento creato con successo.';
            header('Location: index.php?action=admin_events');
        } catch (Exception $e) {
            $_SESSION['error'] = 'Errore durante la creazione dell\'evento.';
            header('Location: index.php?action=admin_create_event');
        }
        exit;
    }

    // GET: mostra form con dati per select
    require_once __DIR__ . '/../models/Location.php';
    require_once __DIR__ . '/../models/Manifestazione.php';

    $_SESSION['admin_locations'] = getAllLocations($pdo);
    $_SESSION['admin_manifestazioni'] = getAllManifestazioni($pdo);
    setPage('admin/evento_form');
}

/**
 * Elimina un evento
 * Richiede almeno ruolo MOD
 */
function adminDeleteEvent(PDO $pdo): void
{
    requireRole(ROLE_MOD);

    if (!verifyCsrf()) {
        $_SESSION['error'] = 'Token non valido.';
        header('Location: index.php?action=admin_events');
        exit;
    }

    $eventoId = (int) ($_POST['evento_id'] ?? 0);

    if (deleteEvento($pdo, $eventoId)) {
        $_SESSION['msg'] = 'Evento eliminato con successo.';
    } else {
        $_SESSION['error'] = 'Errore durante l\'eliminazione dell\'evento.';
    }

    header('Location: index.php?action=admin_events');
    exit;
}

// ==========================================
// PROMOTER DASHBOARD
// ==========================================

/**
 * Dashboard per promoter
 * Mostra gli eventi gestiti dal promoter
 */
function showPromoterDashboard(PDO $pdo): void
{
    requireRole(ROLE_PROMOTER);

    // Admin e mod vedono tutti gli eventi
    if (hasRole(ROLE_MOD)) {
        $eventi = getAllEventiAdmin($pdo);
    } else {
        // TODO: filtrare per eventi creati dal promoter specifico
        $eventi = getAllEventiAdmin($pdo);
    }

    $_SESSION['promoter_eventi'] = $eventi;
    setPage('admin/promoter_dashboard');
}

// ==========================================
// MODERATOR DASHBOARD
// ==========================================

/**
 * Dashboard per moderatori
 * Mostra statistiche di moderazione
 */
function showModDashboard(PDO $pdo): void
{
    requireRole(ROLE_MOD);

    $stats = [
        'eventi_totali' => countEventi($pdo),
        'recensioni_totali' => countRecensioni($pdo)
    ];

    $_SESSION['mod_stats'] = $stats;
    setPage('admin/mod_dashboard');
}

// ==========================================
// FUNZIONI HELPER PER STATISTICHE
// ==========================================

/**
 * Conta il numero totale di eventi
 */
function countEventi(PDO $pdo): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM Eventi")->fetchColumn();
}

/**
 * Conta gli eventi con data futura
 */
function countEventiFuturi(PDO $pdo): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM Eventi WHERE Data >= CURDATE()")->fetchColumn();
}

/**
 * Conta il numero totale di ordini
 */
function countOrdini(PDO $pdo): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM Ordini")->fetchColumn();
}

/**
 * Conta il numero totale di recensioni
 */
function countRecensioni(PDO $pdo): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM Recensioni")->fetchColumn();
}

/**
 * Recupera tutti gli eventi per pannello admin
 * Include nome location e manifestazione
 *
 * @return array Lista eventi ordinati per data decrescente
 */
function getAllEventiAdmin(PDO $pdo): array
{
    return $pdo->query("
        SELECT e.*, l.Nome as LocationName, m.Nome as ManifestazioneName
        FROM Eventi e
        LEFT JOIN Locations l ON e.idLocation = l.id
        LEFT JOIN Manifestazioni m ON e.idManifestazione = m.id
        ORDER BY e.Data DESC
    ")->fetchAll();
}
