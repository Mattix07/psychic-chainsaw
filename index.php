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
    // BIGLIETTI
    // ==========================================
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

    case 'admin_delete_event':
        require_once 'controllers/AdminController.php';
        adminDeleteEvent($pdo);
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
    // DEFAULT: HOMEPAGE
    // ==========================================
    default:
        require_once 'controllers/PageController.php';
        setPage('home');
        break;
}

// Renderizza il layout con la pagina richiesta
require_once 'views/layouts/main.php';
