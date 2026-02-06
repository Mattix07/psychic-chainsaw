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
        setPage('login');
        break;

    case 'show_register':
        require_once 'controllers/PageController.php';
        setPage('register');
        break;

    // ==========================================
    // NAVIGAZIONE EVENTI
    // ==========================================
    case 'home':
        require_once 'controllers/PageController.php';
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
        setPage('checkout');
        break;

    case 'acquista':
    case 'valida':
    case 'view_biglietto':
        require_once 'controllers/PageController.php';
        require_once 'controllers/BigliettoController.php';
        handleBiglietto($pdo, $action);
        break;

    // ==========================================
    // RECENSIONI
    // ==========================================
    case 'add_recensione':
    case 'update_recensione':
    case 'delete_recensione':
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
        setPage('cambia_password');
        break;

    case 'update_password':
        require_once 'controllers/UserController.php';
        updatePassword($pdo);
        break;

    case 'recupera_password':
        require_once 'controllers/PageController.php';
        setPage('recupera_password');
        break;

    case 'send_reset_email':
        require_once 'controllers/UserController.php';
        sendResetEmail($pdo);
        break;

    case 'reset_password':
        require_once 'controllers/PageController.php';
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

    // ==========================================
    // NUOVE FUNZIONALITÀ ADMIN/MOD
    // ==========================================
    case 'delete_biglietti_evento':
        require_once 'controllers/AdminController.php';
        deleteBigliettiEventoApi($pdo);
        exit; // API JSON
        break;

    case 'delete_location':
        require_once 'controllers/AdminController.php';
        deleteLocationApi($pdo);
        exit; // API JSON
        break;

    case 'delete_manifestazione':
        require_once 'controllers/AdminController.php';
        deleteManifestazioneApi($pdo);
        exit; // API JSON
        break;

    case 'delete_recensione':
        require_once 'controllers/AdminController.php';
        deleteRecensioneApi($pdo);
        exit; // API JSON
        break;

    case 'verify_account':
        require_once 'controllers/AdminController.php';
        verifyAccountApi($pdo);
        exit; // API JSON
        break;

    case 'get_unverified_accounts':
        require_once 'controllers/AdminController.php';
        getUnverifiedAccountsApi($pdo);
        exit; // API JSON
        break;

    // ==========================================
    // COLLABORAZIONE EVENTI
    // ==========================================
    case 'invite_collaborator':
        require_once 'controllers/CollaborazioneController.php';
        inviteCollaboratorApi($pdo);
        exit; // API JSON
        break;

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
        exit; // API JSON
        break;

    // ==========================================
    // AVATAR UTENTE
    // ==========================================
    case 'upload_avatar':
        require_once 'controllers/AvatarController.php';
        uploadAvatarApi($pdo);
        exit; // API JSON
        break;

    case 'get_avatar':
        require_once 'controllers/AvatarController.php';
        getAvatarApi($pdo);
        exit; // Image output
        break;

    case 'delete_avatar':
        require_once 'controllers/AvatarController.php';
        deleteAvatarApi($pdo);
        exit; // API JSON
        break;

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
        deleteLocation($pdo);
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
        deleteManifestazione($pdo);
        break;

    // ==========================================
    // DEFAULT: HOMEPAGE
    // ==========================================
    default:
        require_once 'controllers/PageController.php';
        setPage('home');
        break;
}

// Renderizza il layout con la pagina richiesta
require_once 'views/layouts/main.php';
