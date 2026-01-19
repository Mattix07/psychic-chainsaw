<?php
/**
 * EventsMaster - Entry Point (Router)
 * Sistema di gestione eventi e vendita biglietti
 */

require_once 'config/session.php';
require_once 'config/database.php';

// Previeni cache browser
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Messaggi flash
$msg = $_SESSION['msg'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['msg'], $_SESSION['error']);

// Router: determina l'azione
$action = $_POST['action'] ?? $_GET['action'] ?? null;

// Routing delle azioni
switch ($action) {
    // Autenticazione
    case 'login':
    case 'register':
    case 'logout':
        require_once 'controllers/AuthController.php';
        handleAuth($pdo, $action);
        break;

    // Navigazione pagine
    case 'list_eventi':
        require_once 'controllers/PageController.php';
        require_once 'controllers/EventoController.php';
        listEventi($pdo);
        break;

    case 'home':
        require_once 'controllers/PageController.php';
        setPage('home');
        break;

    case 'category':
        require_once 'controllers/PageController.php';
        require_once 'controllers/EventoController.php';
        listByCategory($pdo, $_GET['cat'] ?? '');
        break;

    // Eventi
    case 'view_evento':
    case 'search_eventi':
    case 'create_evento':
    case 'update_evento':
    case 'delete_evento':
        require_once 'controllers/PageController.php';
        require_once 'controllers/EventoController.php';
        handleEvento($pdo, $action);
        break;

    // Biglietti
    case 'acquista':
    case 'valida':
    case 'view_biglietto':
        require_once 'controllers/PageController.php';
        require_once 'controllers/BigliettoController.php';
        handleBiglietto($pdo, $action);
        break;

    // Recensioni
    case 'add_recensione':
    case 'update_recensione':
    case 'delete_recensione':
        require_once 'controllers/PageController.php';
        require_once 'controllers/RecensioneController.php';
        handleRecensione($pdo, $action);
        break;

    // Pagine statiche di autenticazione
    case 'show_login':
        require_once 'controllers/PageController.php';
        setPage('login');
        break;

    case 'show_register':
        require_once 'controllers/PageController.php';
        setPage('register');
        break;

    // Profilo utente
    case 'profilo':
        require_once 'controllers/PageController.php';
        require_once 'controllers/UserController.php';
        showProfilo($pdo);
        break;

    case 'miei_biglietti':
        require_once 'controllers/PageController.php';
        if (!isLoggedIn()) {
            $_SESSION['error'] = 'Devi effettuare il login.';
            header('Location: index.php?action=show_login');
            exit;
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

    case 'cambia_password':
        require_once 'controllers/PageController.php';
        if (!isLoggedIn()) {
            $_SESSION['error'] = 'Devi effettuare il login.';
            header('Location: index.php?action=show_login');
            exit;
        }
        setPage('cambia_password');
        break;

    case 'update_password':
        require_once 'controllers/UserController.php';
        updatePassword($pdo);
        break;

    case 'update_profile':
        require_once 'controllers/UserController.php';
        updateProfile($pdo);
        break;

    case 'elimina_account':
        require_once 'controllers/PageController.php';
        if (!isLoggedIn()) {
            $_SESSION['error'] = 'Devi effettuare il login.';
            header('Location: index.php?action=show_login');
            exit;
        }
        setPage('elimina_account');
        break;

    case 'delete_account':
        require_once 'controllers/UserController.php';
        deleteAccount($pdo);
        break;

    // Password Recovery
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

    // Email Verification
    case 'verify_email':
        require_once 'controllers/UserController.php';
        verifyEmail($pdo);
        break;

    case 'resend_verification':
        require_once 'controllers/UserController.php';
        resendVerification($pdo);
        break;

    // ==========================================
    // ADMIN ROUTES
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

    // Promoter Dashboard
    case 'promoter_dashboard':
        require_once 'controllers/PageController.php';
        require_once 'controllers/AdminController.php';
        showPromoterDashboard($pdo);
        break;

    // Mod Dashboard
    case 'mod_dashboard':
        require_once 'controllers/PageController.php';
        require_once 'controllers/AdminController.php';
        showModDashboard($pdo);
        break;

    // Default: homepage
    default:
        require_once 'controllers/PageController.php';
        setPage('home');
        break;
}

// Carica il layout principale
require_once 'views/layouts/main.php';
