<?php
/**
 * Controller Artista
 * Gestisce profilo pubblico artista, modifica profilo e claim artista
 */

require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../models/Utente.php';
require_once __DIR__ . '/../lib/QueryBuilder.php';

if (!function_exists('jsonResponse')) {
    function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

/**
 * Pagina pubblica profilo artista
 */
function showArtistaProfile(PDO $pdo): void
{
    require_once __DIR__ . '/../controllers/PageController.php';

    $id = (int)($_GET['id'] ?? 0);
    $artista = table($pdo, TABLE_INTRATTENITORI)->where(COL_INTRATTENITORI_ID, $id)->first();

    if (!$artista) {
        redirect('index.php', null, 'Artista non trovato');
        return;
    }

    // Carica eventi associati
    $stmt = $pdo->prepare("
        SELECT e.*, ei.Ruolo
        FROM " . TABLE_EVENTI . " e
        JOIN " . TABLE_EVENTO_INTRATTENITORI . " ei ON ei.idEvento = e." . COL_EVENTI_ID . "
        WHERE ei.idIntrattenitore = ?
        ORDER BY e." . COL_EVENTI_DATA . " DESC
    ");
    $stmt->execute([$id]);
    $eventi = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $_SESSION['artista'] = $artista;
    $_SESSION['artista_eventi'] = $eventi;

    setSeoMeta(
        $artista[COL_INTRATTENITORI_NOME] ?? 'Artista',
        $artista['bio'] ?? ''
    );
    setPage('artista_profilo');
}

/**
 * Salva le modifiche al profilo artista (bio, foto, social)
 */
function updateArtistaProfile(PDO $pdo): void
{
    requireAuth();

    if (!verifyCsrf()) {
        redirect('index.php', null, 'Richiesta non valida');
    }

    $intrattenitore = getIntrattenitoreByUtente($pdo, $_SESSION['user_id']);
    if (!$intrattenitore) {
        redirect('index.php', null, 'Profilo artista non trovato');
        return;
    }

    $idArtista = (int)$intrattenitore[COL_INTRATTENITORI_ID];
    $bio = sanitize($_POST['bio'] ?? '');
    $social = [
        'instagram' => sanitize($_POST['instagram'] ?? ''),
        'spotify'   => sanitize($_POST['spotify'] ?? ''),
        'youtube'   => sanitize($_POST['youtube'] ?? ''),
    ];

    $updateData = [
        'bio'          => $bio ?: null,
        'social_links' => json_encode($social),
    ];

    // Gestione foto
    if (!empty($_FILES['foto']['tmp_name'])) {
        $file = $_FILES['foto'];
        if (!in_array($file['type'], ['image/jpeg', 'image/png', 'image/jpg'])) {
            redirect('index.php?action=artista_dashboard', null, 'Tipo file non supportato');
            return;
        }
        if ($file['size'] > 3 * 1024 * 1024) {
            redirect('index.php?action=artista_dashboard', null, 'Immagine troppo grande (max 3MB)');
            return;
        }
        $updateData['foto'] = file_get_contents($file['tmp_name']);
    }

    table($pdo, TABLE_INTRATTENITORI)->where(COL_INTRATTENITORI_ID, $idArtista)->update($updateData);

    redirect('index.php?action=artista_dashboard', 'Profilo aggiornato con successo');
}

/**
 * Richiesta di claim artista: utente vuole collegarsi a un intrattenitore
 */
function claimArtistaAction(PDO $pdo): void
{
    requireAuth();

    if (!verifyCsrf()) {
        redirect('index.php', null, 'Richiesta non valida');
    }

    $idIntrattenitore = (int)($_POST['idIntrattenitore'] ?? 0);
    $messaggio = sanitize($_POST['messaggio'] ?? '');
    $idUtente = $_SESSION['user_id'];

    // Verifica che l'intrattenitore esista
    $artista = table($pdo, TABLE_INTRATTENITORI)->where(COL_INTRATTENITORI_ID, $idIntrattenitore)->first();
    if (!$artista) {
        redirect('index.php', null, 'Artista non trovato');
        return;
    }

    // Verifica che non sia già collegato a qualcuno
    if (!empty($artista[COL_INTRATTENITORI_ID_UTENTE])) {
        redirect('index.php', null, 'Questo profilo artista è già collegato a un account');
        return;
    }

    // Verifica che l'utente non abbia già una richiesta pending
    $existing = table($pdo, TABLE_ARTISTA_CLAIMS)
        ->where(COL_CLAIMS_ID_UTENTE, $idUtente)
        ->where(COL_CLAIMS_STATO, 'pending')
        ->first();

    if ($existing) {
        redirect('index.php', null, 'Hai già una richiesta di collegamento in attesa');
        return;
    }

    table($pdo, TABLE_ARTISTA_CLAIMS)->insert([
        COL_CLAIMS_ID_UTENTE          => $idUtente,
        COL_CLAIMS_ID_INTRATTENITORE  => $idIntrattenitore,
        COL_CLAIMS_STATO              => 'pending',
        COL_CLAIMS_MESSAGGIO          => $messaggio ?: null,
        COL_CLAIMS_CREATED_AT         => date('Y-m-d H:i:s'),
    ]);

    redirect('index.php', 'Richiesta inviata! Attendi l\'approvazione di un amministratore');
}

/**
 * Admin: approva un claim artista
 */
function approveClaimAction(PDO $pdo): void
{
    requireRole(RUOLO_ADMIN);
    header('Content-Type: application/json');

    if (!verifyCsrf()) { jsonResponse(['error' => 'CSRF non valido'], 403); return; }

    $idClaim = (int)($_POST['idClaim'] ?? 0);
    $claim = table($pdo, TABLE_ARTISTA_CLAIMS)->where(COL_CLAIMS_ID, $idClaim)->first();

    if (!$claim || $claim[COL_CLAIMS_STATO] !== 'pending') {
        jsonResponse(['error' => 'Richiesta non trovata o già gestita'], 404);
        return;
    }

    // Collega utente a intrattenitore
    table($pdo, TABLE_INTRATTENITORI)
        ->where(COL_INTRATTENITORI_ID, $claim[COL_CLAIMS_ID_INTRATTENITORE])
        ->update([COL_INTRATTENITORI_ID_UTENTE => $claim[COL_CLAIMS_ID_UTENTE]]);

    // Promuovi utente ad artista
    table($pdo, TABLE_UTENTI)
        ->where(COL_UTENTI_ID, $claim[COL_CLAIMS_ID_UTENTE])
        ->update([COL_UTENTI_RUOLO => RUOLO_ARTISTA]);

    // Aggiorna claim
    table($pdo, TABLE_ARTISTA_CLAIMS)->where(COL_CLAIMS_ID, $idClaim)->update([
        COL_CLAIMS_STATO      => 'approvata',
        COL_CLAIMS_GESTITA_DA => $_SESSION['user_id'],
        COL_CLAIMS_GESTITA_AT => date('Y-m-d H:i:s'),
    ]);

    // Notifica all'utente
    table($pdo, TABLE_NOTIFICHE)->insert([
        COL_NOTIFICHE_DESTINATARIO_ID => $claim[COL_CLAIMS_ID_UTENTE],
        COL_NOTIFICHE_TIPO            => 'sistema',
        COL_NOTIFICHE_MESSAGGIO       => 'Il tuo account è stato collegato al profilo artista. Benvenuto!',
        COL_NOTIFICHE_LETTA           => 0,
        COL_NOTIFICHE_CREATED_AT      => date('Y-m-d H:i:s'),
    ]);

    jsonResponse(['success' => true]);
}

/**
 * Admin: rifiuta un claim artista
 */
function rejectClaimAction(PDO $pdo): void
{
    requireRole(RUOLO_ADMIN);

    if (!verifyCsrf()) { jsonResponse(['error' => 'CSRF non valido'], 403); return; }

    $idClaim = (int)($_POST['idClaim'] ?? 0);
    $claim = table($pdo, TABLE_ARTISTA_CLAIMS)->where(COL_CLAIMS_ID, $idClaim)->first();

    if (!$claim || $claim[COL_CLAIMS_STATO] !== 'pending') {
        jsonResponse(['error' => 'Richiesta non trovata o già gestita'], 404);
        return;
    }

    table($pdo, TABLE_ARTISTA_CLAIMS)->where(COL_CLAIMS_ID, $idClaim)->update([
        COL_CLAIMS_STATO      => 'rifiutata',
        COL_CLAIMS_GESTITA_DA => $_SESSION['user_id'],
        COL_CLAIMS_GESTITA_AT => date('Y-m-d H:i:s'),
    ]);

    jsonResponse(['success' => true]);
}

/**
 * API: lista claims pending per admin
 */
function getClaimsAdminApi(PDO $pdo): void
{
    requireRole(RUOLO_ADMIN);

    $stmt = $pdo->query("
        SELECT c.*, u." . COL_UTENTI_NOME . ", u." . COL_UTENTI_COGNOME . ", u." . COL_UTENTI_EMAIL . ",
               i." . COL_INTRATTENITORI_NOME . " AS nome_artista
        FROM " . TABLE_ARTISTA_CLAIMS . " c
        JOIN " . TABLE_UTENTI . " u ON c." . COL_CLAIMS_ID_UTENTE . " = u." . COL_UTENTI_ID . "
        JOIN " . TABLE_INTRATTENITORI . " i ON c." . COL_CLAIMS_ID_INTRATTENITORE . " = i." . COL_INTRATTENITORI_ID . "
        WHERE c." . COL_CLAIMS_STATO . " = 'pending'
        ORDER BY c." . COL_CLAIMS_CREATED_AT . " DESC
    ");
    jsonResponse(['claims' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

/**
 * Dashboard artista
 */
function showArtistaDashboard(PDO $pdo): void
{
    require_once __DIR__ . '/../controllers/PageController.php';

    requireRole(RUOLO_ARTISTA);

    $intrattenitore = getIntrattenitoreByUtente($pdo, $_SESSION['user_id']);
    if (!$intrattenitore) {
        redirect('index.php', null, 'Profilo artista non trovato');
        return;
    }

    $idArtista = (int)$intrattenitore[COL_INTRATTENITORI_ID];

    $stmt = $pdo->prepare("
        SELECT e.*, ei.Ruolo
        FROM " . TABLE_EVENTI . " e
        JOIN " . TABLE_EVENTO_INTRATTENITORI . " ei ON ei.idEvento = e." . COL_EVENTI_ID . "
        WHERE ei.idIntrattenitore = ?
        ORDER BY e." . COL_EVENTI_DATA . " DESC
    ");
    $stmt->execute([$idArtista]);
    $eventi = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $_SESSION['artista'] = $intrattenitore;
    $_SESSION['artista_eventi'] = $eventi;

    setSeoMeta('Dashboard Artista', '', 'noindex,nofollow');
    setPage('artista_dashboard');
}
