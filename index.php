<?php
/**
 * EventsMaster - Entry Point
 * Router principale dell'applicazione
 * Gestisce tutte le richieste HTTP e le indirizza ai controller appropriati
 */

require_once 'config/session.php';
require_once 'config/database.php';

// Disabilita cache browser per evitare problemi con dati di sessione
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Header di sicurezza HTTP
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Recupera e pulisce i messaggi flash dalla sessione
$msg = $_SESSION['msg'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['msg'], $_SESSION['error']);

// Determina l'azione richiesta (POST ha priorita su GET)
$action = $_POST['action'] ?? $_GET['action'] ?? null;

switch ($action) {

    // ==========================================
    // AUTENTICAZIONE
    // ==========================================
    case 'login':
    case 'register':
    case 'logout':
        require_once 'controllers/AuthController.php';
        handleAuth($pdo, $action);
        break;

    case 'show_login':
        require_once 'controllers/PageController.php';
        setSeoMeta('Accedi', '', 'noindex,nofollow');
        setPage('login');
        break;

    case 'show_register':
        require_once 'controllers/PageController.php';
        setSeoMeta('Registrati', '', 'noindex,nofollow');
        setPage('register');
        break;

    // ==========================================
    // NAVIGAZIONE EVENTI
    // ==========================================
    case 'home':
        require_once 'controllers/PageController.php';
        if (isLoggedIn()) {
            $ruolo = $_SESSION['user_ruolo'] ?? 'user';
            if ($ruolo === 'admin') { redirect('index.php?action=admin_dashboard'); }
            elseif ($ruolo === 'mod') { redirect('index.php?action=mod_dashboard'); }
            elseif ($ruolo === 'promoter') { redirect('index.php?action=promoter_dashboard'); }
        }
        setSeoMeta('Biglietti eventi online', 'Acquista biglietti per concerti, teatro, sport e molto altro. Trova i migliori eventi nella tua città su EventsMaster.');
        setPage('home');
        break;

    case 'list_eventi':
        require_once 'controllers/PageController.php';
        require_once 'controllers/EventoController.php';
        listEventi($pdo);
        break;

    case 'category':
        require_once 'controllers/PageController.php';
        require_once 'controllers/EventoController.php';
        listByCategory($pdo, $_GET['cat'] ?? '');
        break;

    case 'view_evento':
    case 'search_eventi':
    case 'create_evento':
    case 'update_evento':
    case 'delete_evento':
        require_once 'controllers/PageController.php';
        require_once 'controllers/EventoController.php';
        handleEvento($pdo, $action);
        break;

    // ==========================================
    // CARRELLO (API)
    // ==========================================
    case 'cart_add':
    case 'cart_get':
    case 'cart_update':
    case 'cart_remove':
    case 'cart_clear':
    case 'cart_count':
    case 'check_availability':
    case 'get_settori':
    case 'cart_update_settore':
        require_once 'controllers/CartController.php';
        handleCart($pdo, $action);
        exit; // Le API non renderizzano il layout

    // ==========================================
    // CHECKOUT E BIGLIETTI
    // ==========================================
    case 'checkout':
        require_once 'controllers/PageController.php';
        if (!isLoggedIn()) {
            redirect('index.php?action=show_login&redirect=checkout', null, 'Effettua il login per completare l\'acquisto.');
        }
        setSeoMeta('Checkout', '', 'noindex,nofollow');
        setPage('checkout');
        break;

    case 'acquista':
    case 'valida':
    case 'view_biglietto':
        require_once 'controllers/PageController.php';
        require_once 'controllers/BigliettoController.php';
        handleBiglietto($pdo, $action);
        break;

    case 'upload_documento_biglietto':
        require_once 'controllers/BigliettoController.php';
        uploadDocumentoBigliettoApi($pdo);
        exit;

    case 'get_documento_biglietto':
        require_once 'controllers/BigliettoController.php';
        getDocumentoBigliettoApi($pdo);
        exit;

    // ==========================================
    // RECENSIONI
    // ==========================================
    case 'add_recensione':
    case 'update_recensione':
    case 'delete_recensione':
    case 'hide_recensione':
    case 'restore_recensione':
    case 'flag_recensione':
    case 'get_recensioni_admin':
        require_once 'controllers/PageController.php';
        require_once 'controllers/RecensioneController.php';
        handleRecensione($pdo, $action);
        break;

    // ==========================================
    // PROFILO UTENTE
    // ==========================================
    case 'profilo':
        require_once 'controllers/PageController.php';
        require_once 'controllers/UserController.php';
        showProfilo($pdo);
        break;

    case 'update_profile':
        require_once 'controllers/UserController.php';
        updateProfile($pdo);
        break;

    case 'miei_biglietti':
        require_once 'controllers/PageController.php';
        if (!isLoggedIn()) {
            redirect('index.php?action=show_login', null, 'Devi effettuare il login.');
        }
        setSeoMeta('I miei biglietti', '', 'noindex,nofollow');
        setPage('miei_biglietti');
        break;

    case 'miei_ordini':
        require_once 'controllers/PageController.php';
        require_once 'controllers/OrdineController.php';
        showOrdiniUtente($pdo);
        break;

    case 'view_ordine':
        require_once 'controllers/PageController.php';
        require_once 'controllers/OrdineController.php';
        viewOrdine($pdo);
        break;

    // ==========================================
    // GESTIONE PASSWORD
    // ==========================================
    case 'cambia_password':
        require_once 'controllers/PageController.php';
        if (!isLoggedIn()) {
            redirect('index.php?action=show_login', null, 'Devi effettuare il login.');
        }
        setSeoMeta('Cambia password', '', 'noindex,nofollow');
        setPage('cambia_password');
        break;

    case 'update_password':
        require_once 'controllers/UserController.php';
        updatePassword($pdo);
        break;

    case 'recupera_password':
        require_once 'controllers/PageController.php';
        setSeoMeta('Recupera password', '', 'noindex,nofollow');
        setPage('recupera_password');
        break;

    case 'send_reset_email':
        require_once 'controllers/UserController.php';
        sendResetEmail($pdo);
        break;

    case 'reset_password':
        require_once 'controllers/PageController.php';
        setSeoMeta('Reimposta password', '', 'noindex,nofollow');
        setPage('reset_password');
        break;

    case 'do_reset_password':
        require_once 'controllers/UserController.php';
        doResetPassword($pdo);
        break;

    // ==========================================
    // VERIFICA EMAIL
    // ==========================================
    case 'verify_email':
        require_once 'controllers/UserController.php';
        verifyEmail($pdo);
        break;

    case 'resend_verification':
        require_once 'controllers/UserController.php';
        resendVerification($pdo);
        break;

    // ==========================================
    // ELIMINAZIONE ACCOUNT
    // ==========================================
    case 'elimina_account':
        require_once 'controllers/PageController.php';
        if (!isLoggedIn()) {
            redirect('index.php?action=show_login', null, 'Devi effettuare il login.');
        }
        setSeoMeta('Elimina account', '', 'noindex,nofollow');
        setPage('elimina_account');
        break;

    case 'delete_account':
        require_once 'controllers/UserController.php';
        deleteAccount($pdo);
        break;

    // ==========================================
    // PANNELLO AMMINISTRAZIONE
    // ==========================================
    case 'admin_dashboard':
        require_once 'controllers/PageController.php';
        require_once 'controllers/AdminController.php';
        showAdminDashboard($pdo);
        break;

    case 'admin_users':
        require_once 'controllers/PageController.php';
        require_once 'controllers/AdminController.php';
        adminManageUsers($pdo);
        break;

    case 'admin_update_role':
        require_once 'controllers/AdminController.php';
        adminUpdateUserRole($pdo);
        break;

    case 'admin_delete_user':
        require_once 'controllers/AdminController.php';
        adminDeleteUser($pdo);
        break;

    case 'admin_events':
        require_once 'controllers/PageController.php';
        require_once 'controllers/AdminController.php';
        adminManageEvents($pdo);
        break;

    case 'admin_create_event':
        require_once 'controllers/PageController.php';
        require_once 'controllers/AdminController.php';
        adminCreateEvent($pdo);
        break;

    case 'admin_edit_event':
        require_once 'controllers/PageController.php';
        require_once 'controllers/AdminController.php';
        adminEditEvent($pdo);
        break;

    case 'admin_delete_event':
        require_once 'controllers/AdminController.php';
        adminDeleteEvent($pdo);
        break;

    case 'invite_collaborator':
        require_once 'controllers/AdminController.php';
        inviteCollaboratorAction($pdo);
        break;

    case 'remove_collaborator':
        require_once 'controllers/AdminController.php';
        removeCollaboratorAction($pdo);
        break;

    case 'search_promoters':
        require_once 'controllers/AdminController.php';
        searchPromotersApi($pdo);
        exit;

    // ==========================================
    // NUOVE FUNZIONALITÀ ADMIN/MOD
    // ==========================================
    case 'get_settori_location':
        require_once 'controllers/AdminController.php';
        getSettoriByLocationApi($pdo);
        exit;

    case 'delete_biglietti_evento':
        require_once 'controllers/AdminController.php';
        deleteBigliettiEventoApi($pdo);
        exit;

    case 'delete_location':
        require_once 'controllers/AdminController.php';
        deleteLocationApi($pdo);
        exit;

    case 'delete_manifestazione':
        require_once 'controllers/AdminController.php';
        deleteManifestazioneApi($pdo);
        exit;

    case 'verify_account':
        require_once 'controllers/AdminController.php';
        verifyAccountApi($pdo);
        exit;

    case 'get_unverified_accounts':
        require_once 'controllers/AdminController.php';
        getUnverifiedAccountsApi($pdo);
        exit;

    // ==========================================
    // COLLABORAZIONE EVENTI
    // ==========================================
    case 'accept_collaboration':
        require_once 'controllers/CollaborazioneController.php';
        acceptCollaborationApi($pdo);
        break;

    case 'decline_collaboration':
        require_once 'controllers/CollaborazioneController.php';
        declineCollaborationApi($pdo);
        break;

    case 'get_collaborators':
        require_once 'controllers/CollaborazioneController.php';
        getCollaboratorsApi($pdo);
        exit;

    // ==========================================
    // AVATAR UTENTE
    // ==========================================
    case 'upload_avatar':
        require_once 'controllers/AvatarController.php';
        uploadAvatarApi($pdo);
        exit;

    case 'get_avatar':
        require_once 'controllers/AvatarController.php';
        getAvatarApi($pdo);
        exit;

    case 'delete_avatar':
        require_once 'controllers/AvatarController.php';
        deleteAvatarApi($pdo);
        exit;

    // ==========================================
    // ARTISTA
    // ==========================================
    case 'artista_profile':
        require_once 'controllers/ArtistaController.php';
        showArtistaProfile($pdo);
        break;

    case 'artista_dashboard':
        require_once 'controllers/ArtistaController.php';
        showArtistaDashboard($pdo);
        break;

    case 'update_artista_profile':
        require_once 'controllers/ArtistaController.php';
        updateArtistaProfile($pdo);
        break;

    case 'claim_artista':
        require_once 'controllers/ArtistaController.php';
        claimArtistaAction($pdo);
        break;

    case 'approve_claim':
        require_once 'controllers/ArtistaController.php';
        approveClaimAction($pdo);
        exit;

    case 'reject_claim':
        require_once 'controllers/ArtistaController.php';
        rejectClaimAction($pdo);
        exit;

    case 'get_claims_admin':
        require_once 'controllers/ArtistaController.php';
        getClaimsAdminApi($pdo);
        exit;

    case 'get_artista_foto':
        require_once 'controllers/ArtistaController.php';
        $id = (int)($_GET['id'] ?? 0);
        $row = table($pdo, TABLE_INTRATTENITORI)->where(COL_INTRATTENITORI_ID, $id)->first();
        if ($row && !empty($row['foto'])) {
            header('Content-Type: image/jpeg');
            header('Cache-Control: public, max-age=86400');
            echo $row['foto'];
        } else {
            http_response_code(404);
        }
        exit;

    // ==========================================
    // DASHBOARD PROMOTER E MODERATORE
    // ==========================================
    case 'promoter_dashboard':
        require_once 'controllers/PageController.php';
        require_once 'controllers/AdminController.php';
        showPromoterDashboard($pdo);
        break;

    case 'mod_dashboard':
        require_once 'controllers/PageController.php';
        require_once 'controllers/AdminController.php';
        showModDashboard($pdo);
        break;

    // ==========================================
    // GESTIONE LOCATION (Promoter/Admin/Mod)
    // ==========================================
    case 'list_locations':
        require_once 'controllers/PageController.php';
        require_once 'controllers/LocationController.php';
        listLocations($pdo);
        break;

    case 'create_location':
        require_once 'controllers/PageController.php';
        require_once 'controllers/LocationController.php';
        showCreateLocation($pdo);
        break;

    case 'edit_location':
        require_once 'controllers/PageController.php';
        require_once 'controllers/LocationController.php';
        showEditLocation($pdo);
        break;

    case 'save_location':
        require_once 'controllers/LocationController.php';
        saveLocation($pdo);
        break;

    case 'delete_location_form':
        require_once 'controllers/LocationController.php';
        deleteLocationAction($pdo);
        break;

    case 'save_settore':
        require_once 'controllers/LocationController.php';
        saveSettore($pdo);
        break;

    case 'delete_settore':
        require_once 'controllers/LocationController.php';
        deleteSettoreAction($pdo);
        break;

    // ==========================================
    // GESTIONE MANIFESTAZIONI (Promoter/Admin/Mod)
    // ==========================================
    case 'list_manifestazioni':
        require_once 'controllers/PageController.php';
        require_once 'controllers/ManifestazioneController.php';
        listManifestazioni($pdo);
        break;

    case 'create_manifestazione':
        require_once 'controllers/PageController.php';
        require_once 'controllers/ManifestazioneController.php';
        showCreateManifestazione($pdo);
        break;

    case 'edit_manifestazione':
        require_once 'controllers/PageController.php';
        require_once 'controllers/ManifestazioneController.php';
        showEditManifestazione($pdo);
        break;

    case 'save_manifestazione':
        require_once 'controllers/ManifestazioneController.php';
        saveManifestazione($pdo);
        break;

    case 'delete_manifestazione_form':
        require_once 'controllers/ManifestazioneController.php';
        deleteManifestazioneAction($pdo);
        break;

    // ==========================================
    // NEWSLETTER
    // ==========================================
    case 'subscribe_newsletter':
        verifyCsrf();
        $email = filter_input(INPUT_POST, 'newsletter_email', FILTER_VALIDATE_EMAIL);
        if ($email) {
            redirect('index.php', 'Grazie! Ti sei iscritto alla newsletter con successo.');
        } else {
            redirect('index.php', null, 'Indirizzo email non valido.');
        }
        break;

    // ==========================================
    // DEFAULT: HOMEPAGE
    // ==========================================
    default:
        require_once 'controllers/PageController.php';
        if (isLoggedIn()) {
            $ruolo = $_SESSION['user_ruolo'] ?? 'user';
            if ($ruolo === 'admin') { redirect('index.php?action=admin_dashboard'); }
            elseif ($ruolo === 'mod') { redirect('index.php?action=mod_dashboard'); }
            elseif ($ruolo === 'promoter') { redirect('index.php?action=promoter_dashboard'); }
        }
        setSeoMeta('Biglietti eventi online', 'Acquista biglietti per concerti, teatro, sport e molto altro. Trova i migliori eventi nella tua città su EventsMaster.');
        setPage('home');
        break;
}

// Renderizza il layout con la pagina richiesta
require_once 'views/layouts/main.php';
