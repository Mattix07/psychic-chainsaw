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

    // Default: homepage
    default:
        require_once 'controllers/PageController.php';
        setPage('home');
        break;
}

// Carica il layout principale
require_once 'views/layouts/main.php';
