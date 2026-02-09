<?php
/**
 * Controller Amministrazione
 * Gestisce dashboard e operazioni per admin, moderatori e promoter
 *
 * Implementa un sistema di accesso basato su ruoli con controlli
 * di autorizzazione per ogni funzione sensibile.
 */

require_once __DIR__ . '/../config/database_schema.php';
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/messages.php';
require_once __DIR__ . '/../lib/Validator.php';
require_once __DIR__ . '/../lib/QueryBuilder.php';
require_once __DIR__ . '/../models/Utente.php';
require_once __DIR__ . '/../models/Evento.php';
require_once __DIR__ . '/../models/Ordine.php';
require_once __DIR__ . '/../models/Permessi.php';
require_once __DIR__ . '/../lib/EmailService.php';

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
        setErrorMessage(ERR_INVALID_CSRF);
        header('Location: index.php?action=admin_users');
        exit;
    }

    // Validazione con Validator
    $validator = validate($_POST)
        ->required('user_id')
        ->numeric('user_id')
        ->required('role')
        ->in('role', [RUOLO_USER, RUOLO_PROMOTER, RUOLO_MOD, RUOLO_ADMIN]);

    if ($validator->fails()) {
        setErrorMessage($validator->firstError());
        header('Location: index.php?action=admin_users');
        exit;
    }

    $userId = (int) $_POST['user_id'];
    $newRole = $_POST['role'];

    // Protezione: non permettere modifica del proprio ruolo
    if ($userId === $_SESSION['user_id']) {
        setErrorMessage(ERR_CANNOT_MODIFY_SELF);
        header('Location: index.php?action=admin_users');
        exit;
    }

    if (setUserRole($pdo, $userId, $newRole)) {
        setSuccessMessage(MSG_SUCCESS_USER_ROLE_UPDATED);
    } else {
        setErrorMessage(ERR_GENERIC);
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
        setErrorMessage(ERR_INVALID_CSRF);
        header('Location: index.php?action=admin_users');
        exit;
    }

    // Validazione con Validator
    $validator = validate($_POST)
        ->required('user_id')
        ->numeric('user_id');

    if ($validator->fails()) {
        setErrorMessage($validator->firstError());
        header('Location: index.php?action=admin_users');
        exit;
    }

    $userId = (int) $_POST['user_id'];

    // Protezione: non permettere auto-eliminazione
    if ($userId === $_SESSION['user_id']) {
        setErrorMessage(ERR_CANNOT_DELETE_SELF);
        header('Location: index.php?action=admin_users');
        exit;
    }

    if (deleteUtente($pdo, $userId)) {
        setSuccessMessage(MSG_SUCCESS_USER_DELETED);
    } else {
        setErrorMessage(ERR_GENERIC);
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
            setErrorMessage(ERR_INVALID_CSRF);
            header('Location: index.php?action=admin_events');
            exit;
        }

        // Validazione con Validator
        $validator = validate($_POST)
            ->required('nome')
            ->required('data')
            ->date('data')
            ->future('data')
            ->required('location')
            ->numeric('location');

        if ($validator->fails()) {
            setErrorMessage($validator->firstError());
            header('Location: index.php?action=admin_create_event');
            exit;
        }

        // Settori: se nessuno selezionato, usa tutti quelli della location
        require_once __DIR__ . '/../models/Location.php';
        $settori = $_POST['settori'] ?? [];
        if (empty($settori)) {
            $locationSettori = getSettoriByLocation($pdo, (int) $_POST['location']);
            $settori = array_column($locationSettori, 'id');
        }

        if (empty($settori)) {
            setErrorMessage('La location selezionata non ha settori configurati.');
            header('Location: index.php?action=admin_create_event');
            exit;
        }

        $data = [
            'Nome' => sanitize($_POST['nome']),
            'Data' => $_POST['data'],
            'OraI' => $_POST['ora_inizio'] ?? '',
            'OraF' => $_POST['ora_fine'] ?? '',
            'Programma' => sanitize($_POST['programma'] ?? ''),
            'PrezzoNoMod' => (float) ($_POST['prezzo'] ?? 0),
            'idLocation' => (int) $_POST['location'],
            'idManifestazione' => (int) ($_POST['manifestazione'] ?? 0) ?: null,
            'Immagine' => sanitize($_POST['immagine'] ?? '')
        ];

        try {
            require_once __DIR__ . '/../models/EventoSettori.php';
            require_once __DIR__ . '/../models/Permessi.php';

            // Crea l'evento
            $eventoId = createEvento($pdo, $data);

            // Registra il creatore dell'evento
            registerEventoCreator($pdo, $eventoId, $_SESSION['user_id']);

            // Salva i settori selezionati
            setEventoSettori($pdo, $eventoId, array_map('intval', $settori));

            setSuccessMessage(MSG_SUCCESS_EVENT_CREATED);
            header('Location: index.php?action=admin_events');
        } catch (Exception $e) {
            setErrorMessage(ERR_GENERIC);
            header('Location: index.php?action=admin_create_event');
        }
        exit;
    }

    // GET: mostra form con dati per select
    require_once __DIR__ . '/../models/Location.php';
    require_once __DIR__ . '/../models/Manifestazione.php';

    unset($_SESSION['admin_evento']);
    $_SESSION['admin_locations'] = getAllLocations($pdo);
    $_SESSION['admin_manifestazioni'] = getAllManifestazioni($pdo);
    setPage('admin/evento_form');
}

/**
 * Modifica evento esistente
 * GET: carica i dati dell'evento nel form
 * POST: salva le modifiche
 * Richiede almeno ruolo PROMOTER + permessi sull'evento
 */
function adminEditEvent(PDO $pdo): void
{
    requireRole(ROLE_PROMOTER);

    $eventoId = (int) ($_GET['id'] ?? $_POST['evento_id'] ?? 0);
    if (!$eventoId) {
        setErrorMessage(ERR_GENERIC);
        header('Location: index.php?action=admin_events');
        exit;
    }

    require_once __DIR__ . '/../models/Evento.php';
    require_once __DIR__ . '/../models/Permessi.php';

    // Verifica permessi
    if (!canEditEvento($pdo, $_SESSION['user_id'], $eventoId)) {
        setErrorMessage(ERR_PERMISSION_DENIED);
        header('Location: index.php?action=admin_events');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCsrf()) {
            setErrorMessage(ERR_INVALID_CSRF);
            header('Location: index.php?action=admin_edit_event&id=' . $eventoId);
            exit;
        }

        $validator = validate($_POST)
            ->required('nome')
            ->required('data')
            ->date('data')
            ->required('location')
            ->numeric('location');

        if ($validator->fails()) {
            setErrorMessage($validator->firstError());
            header('Location: index.php?action=admin_edit_event&id=' . $eventoId);
            exit;
        }

        $data = [
            'Nome' => sanitize($_POST['nome']),
            'Data' => $_POST['data'],
            'OraI' => $_POST['ora_inizio'] ?? '',
            'OraF' => $_POST['ora_fine'] ?? '',
            'Programma' => sanitize($_POST['programma'] ?? ''),
            'PrezzoNoMod' => (float) ($_POST['prezzo'] ?? 0),
            'idLocation' => (int) $_POST['location'],
            'idManifestazione' => (int) ($_POST['manifestazione'] ?? 0) ?: null,
            'Immagine' => sanitize($_POST['immagine'] ?? '')
        ];

        try {
            require_once __DIR__ . '/../models/EventoSettori.php';

            updateEvento($pdo, $eventoId, $data);

            $settori = $_POST['settori'] ?? [];
            setEventoSettori($pdo, $eventoId, array_map('intval', $settori));

            setSuccessMessage(MSG_SUCCESS_EVENT_UPDATED);
            header('Location: index.php?action=admin_events');
        } catch (Exception $e) {
            setErrorMessage(ERR_GENERIC);
            header('Location: index.php?action=admin_edit_event&id=' . $eventoId);
        }
        exit;
    }

    // GET: carica dati evento e mostra form
    $evento = getEventoById($pdo, $eventoId);
    if (!$evento) {
        setErrorMessage(ERR_GENERIC);
        header('Location: index.php?action=admin_events');
        exit;
    }

    require_once __DIR__ . '/../models/Location.php';
    require_once __DIR__ . '/../models/Manifestazione.php';

    $_SESSION['admin_evento'] = $evento;
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
        setErrorMessage(ERR_INVALID_CSRF);
        header('Location: index.php?action=admin_events');
        exit;
    }

    // Validazione con Validator
    $validator = validate($_POST)
        ->required('evento_id')
        ->numeric('evento_id');

    if ($validator->fails()) {
        setErrorMessage($validator->firstError());
        header('Location: index.php?action=admin_events');
        exit;
    }

    $eventoId = (int) $_POST['evento_id'];

    if (deleteEvento($pdo, $eventoId)) {
        setSuccessMessage(MSG_SUCCESS_EVENT_DELETED);
    } else {
        setErrorMessage(ERR_GENERIC);
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
    return table($pdo, TABLE_EVENTI)->count();
}

/**
 * Conta gli eventi con data futura
 */
function countEventiFuturi(PDO $pdo): int
{
    return table($pdo, TABLE_EVENTI)
        ->whereRaw(COL_EVENTI_DATA . ' >= CURDATE()')
        ->count();
}

/**
 * Conta il numero totale di ordini
 */
function countOrdini(PDO $pdo): int
{
    return table($pdo, TABLE_ORDINI)->count();
}

/**
 * Conta il numero totale di recensioni
 */
function countRecensioni(PDO $pdo): int
{
    return table($pdo, TABLE_RECENSIONI)->count();
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
        SELECT e.*, l." . COL_LOCATIONS_NOME . " as LocationName, m." . COL_MANIFESTAZIONI_NOME . " as ManifestazioneName
        FROM " . TABLE_EVENTI . " e
        LEFT JOIN " . TABLE_LOCATIONS . " l ON e." . COL_EVENTI_ID_LOCATION . " = l." . COL_LOCATIONS_ID . "
        LEFT JOIN " . TABLE_MANIFESTAZIONI . " m ON e." . COL_EVENTI_ID_MANIFESTAZIONE . " = m." . COL_MANIFESTAZIONI_ID . "
        ORDER BY e." . COL_EVENTI_DATA . " DESC
    ")->fetchAll();
}

// ==========================================
// NUOVE FUNZIONALITÀ ADMIN
// ==========================================

/**
 * ADMIN: Elimina biglietti di un evento specifico
 * POST: idEvento
 */
function deleteBigliettiEventoApi(PDO $pdo): void
{
    if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
        jsonResponse(apiError(ERR_PERMISSION_DENIED, 403));
        return;
    }

    if (!verifyCsrf()) {
        jsonResponse(apiError(ERR_INVALID_CSRF, 403));
        return;
    }

    $validator = validate($_POST)->required('idEvento')->numeric('idEvento');
    if ($validator->fails()) {
        jsonResponse(apiError($validator->firstError(), 400));
        return;
    }

    $idEvento = (int) $_POST['idEvento'];

    try {
        $count = table($pdo, TABLE_BIGLIETTI)
            ->where(COL_BIGLIETTI_ID_EVENTO, $idEvento)
            ->delete();

        jsonResponse(apiSuccess(null, "$count biglietti eliminati", 200));
    } catch (Exception $e) {
        jsonResponse(apiError(ERR_GENERIC, 500));
    }
}

/**
 * ADMIN: Elimina location
 * POST: idLocation
 */
function deleteLocationApi(PDO $pdo): void
{
    if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
        jsonResponse(apiError(ERR_PERMISSION_DENIED, 403));
        return;
    }

    if (!verifyCsrf()) {
        jsonResponse(apiError(ERR_INVALID_CSRF, 403));
        return;
    }

    $validator = validate($_POST)->required('idLocation')->numeric('idLocation');
    if ($validator->fails()) {
        jsonResponse(apiError($validator->firstError(), 400));
        return;
    }

    $idLocation = (int) $_POST['idLocation'];

    try {
        table($pdo, TABLE_LOCATIONS)
            ->where(COL_LOCATIONS_ID, $idLocation)
            ->delete();
        jsonResponse(apiSuccess(null, MSG_SUCCESS_LOCATION_DELETED, 200));
    } catch (Exception $e) {
        jsonResponse(apiError(ERR_LOCATION_HAS_EVENTS, 400));
    }
}

/**
 * ADMIN: Elimina manifestazione
 * POST: idManifestazione
 */
function deleteManifestazioneApi(PDO $pdo): void
{
    if (!isLoggedIn() || !hasRole(ROLE_ADMIN)) {
        jsonResponse(apiError(ERR_PERMISSION_DENIED, 403));
        return;
    }

    if (!verifyCsrf()) {
        jsonResponse(apiError(ERR_INVALID_CSRF, 403));
        return;
    }

    $validator = validate($_POST)->required('idManifestazione')->numeric('idManifestazione');
    if ($validator->fails()) {
        jsonResponse(apiError($validator->firstError(), 400));
        return;
    }

    $idManifestazione = (int) $_POST['idManifestazione'];

    try {
        table($pdo, TABLE_MANIFESTAZIONI)
            ->where(COL_MANIFESTAZIONI_ID, $idManifestazione)
            ->delete();
        jsonResponse(apiSuccess(null, MSG_SUCCESS_MANIFESTATION_DELETED, 200));
    } catch (Exception $e) {
        jsonResponse(apiError(ERR_MANIFESTATION_HAS_EVENTS, 400));
    }
}

/**
 * MOD: Elimina recensione
 * POST: idEvento, idUtente
 */
function deleteRecensioneApi(PDO $pdo): void
{
    if (!isLoggedIn() || !hasRole(ROLE_MOD)) {
        jsonResponse(apiError(ERR_PERMISSION_DENIED, 403));
        return;
    }

    if (!verifyCsrf()) {
        jsonResponse(apiError(ERR_INVALID_CSRF, 403));
        return;
    }

    $validator = validate($_POST)
        ->required('idEvento')
        ->numeric('idEvento')
        ->required('idUtente')
        ->numeric('idUtente');

    if ($validator->fails()) {
        jsonResponse(apiError($validator->firstError(), 400));
        return;
    }

    $idEvento = (int) $_POST['idEvento'];
    $idUtente = (int) $_POST['idUtente'];

    try {
        $count = table($pdo, TABLE_RECENSIONI)
            ->where(COL_RECENSIONI_ID_EVENTO, $idEvento)
            ->where(COL_RECENSIONI_ID_UTENTE, $idUtente)
            ->delete();

        if ($count > 0) {
            jsonResponse(apiSuccess(null, MSG_SUCCESS_REVIEW_DELETED, 200));
        } else {
            jsonResponse(apiError(ERR_REVIEW_NOT_FOUND, 404));
        }
    } catch (Exception $e) {
        jsonResponse(apiError(ERR_GENERIC, 500));
    }
}

/**
 * ADMIN/MOD: Verifica account utente
 * POST: idUtente
 */
function verifyAccountApi(PDO $pdo): void
{
    if (!isLoggedIn() || !hasRole(ROLE_MOD)) {
        jsonResponse(apiError(ERR_PERMISSION_DENIED, 403));
        return;
    }

    if (!verifyCsrf()) {
        jsonResponse(apiError(ERR_INVALID_CSRF, 403));
        return;
    }

    $validator = validate($_POST)->required('idUtente')->numeric('idUtente');
    if ($validator->fails()) {
        jsonResponse(apiError($validator->firstError(), 400));
        return;
    }

    $idUtente = (int) $_POST['idUtente'];

    try {
        table($pdo, TABLE_UTENTI)
            ->where(COL_UTENTI_ID, $idUtente)
            ->update([
                COL_UTENTI_VERIFICATO => 1,
                COL_UTENTI_EMAIL_VERIFICATION_TOKEN => null
            ]);

        $emailService = new EmailService($pdo);
        $emailService->sendAccountVerifiedNotification($idUtente, $_SESSION['user_id']);

        jsonResponse(apiSuccess(null, MSG_SUCCESS_USER_VERIFIED, 200));
    } catch (Exception $e) {
        jsonResponse(apiError(ERR_GENERIC, 500));
    }
}

/**
 * ADMIN/MOD: Ottieni lista account non verificati
 */
function getUnverifiedAccountsApi(PDO $pdo): void
{
    if (!isLoggedIn() || !hasRole(ROLE_MOD)) {
        jsonResponse(apiError(ERR_PERMISSION_DENIED, 403));
        return;
    }

    try {
        $accounts = table($pdo, TABLE_UTENTI)
            ->select([
                COL_UTENTI_ID,
                COL_UTENTI_NOME,
                COL_UTENTI_COGNOME,
                COL_UTENTI_EMAIL,
                COL_UTENTI_RUOLO,
                COL_UTENTI_DATA_REGISTRAZIONE
            ])
            ->where(COL_UTENTI_VERIFICATO, 0)
            ->orderBy(COL_UTENTI_DATA_REGISTRAZIONE, 'DESC')
            ->get();

        jsonResponse(apiSuccess(['accounts' => $accounts], null, 200));
    } catch (Exception $e) {
        jsonResponse(apiError(ERR_GENERIC, 500));
    }
}

/**
 * API: Restituisce i settori di una location
 * GET: idLocation
 */
function getSettoriByLocationApi(PDO $pdo): void
{
    if (!isLoggedIn() || !hasRole(ROLE_PROMOTER)) {
        jsonResponse(apiError(ERR_PERMISSION_DENIED, 403));
        return;
    }

    $idLocation = (int) ($_GET['idLocation'] ?? 0);
    if (!$idLocation) {
        jsonResponse(apiError('Location non valida', 400));
        return;
    }

    require_once __DIR__ . '/../models/Location.php';
    $settori = getSettoriByLocation($pdo, $idLocation);
    jsonResponse(apiSuccess($settori));
}

function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
