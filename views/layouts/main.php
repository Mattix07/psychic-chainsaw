<?php
/**
 * Layout principale dell'applicazione
 *
 * Questo file funge da template master per tutte le pagine del sito.
 * Include: header con navigazione, area contenuto dinamico, newsletter,
 * footer, carrello laterale e modali. La pagina specifica viene caricata
 * dinamicamente in base alla variabile di sessione $_SESSION['page'].
 *
 * Struttura:
 * - Header: logo, navigazione principale, barra di ricerca, azioni utente
 * - Main: messaggi flash (successo/errore) + contenuto pagina dinamico
 * - Newsletter: form iscrizione
 * - Footer: link informativi e social
 * - Cart Sidebar: carrello laterale con gestione JavaScript
 * - Modal: gestione duplicati carrello al login
 */
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(env('APP_NAME', 'EventsMaster')) ?> - Biglietti Eventi</title>
    <link rel="stylesheet" href="public/css/main.css?v=<?= time() ?>">
    <link rel="stylesheet" href="public/css/mobile.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<!--
    HEADER
    Contiene: logo, navigazione categorie, barra ricerca, toggle tema,
    carrello e menu utente (con dropdown per azioni account e ruoli admin/mod/promoter)
-->
<header class="header" id="header">
    <div class="container">
        <!-- Logo -->
        <a href="index.php" class="logo">
            <img src="img/logo.png" alt="EventsMaster" onerror="this.style.display='none'">
            <span>EventsMaster</span>
        </a>

        <!-- Navigation principale -->
        <nav class="nav-main">
            <a href="index.php" >Home</a>
            <a href="index.php?action=list_eventi">Eventi</a>
            <a href="index.php?action=category&cat=concerti">Concerti</a>
            <a href="index.php?action=category&cat=teatro">Teatro</a>
            <a href="index.php?action=category&cat=sport">Sport</a>
        </nav>

        <!-- Search Bar -->
        <div class="search-bar" id="searchBar">
            <form method="post" action="index.php">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="search_eventi">
                <input type="text" name="query" placeholder="Cerca eventi, artisti, location...">
            </form>
            <button type="button" class="search-btn" id="searchToggle">
                <i class="fas fa-search"></i>
            </button>
        </div>

        <!-- User Actions -->
        <div class="nav-actions">
            <!-- Theme Toggle -->
            <button class="theme-toggle" id="themeToggle" title="Cambia tema">
                <i class="fas fa-moon" id="themeIcon"></i>
            </button>

            <!-- Cart Button -->
            <button class="cart-toggle" id="cartToggle" title="Carrello">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-badge" id="cartBadge" style="display: none;">0</span>
            </button>

            <?php if (isLoggedIn()): ?>
                <div class="user-dropdown">
                    <button class="user-dropdown-toggle" id="userDropdownToggle">
                        <div class="user-avatar">
                            <?= strtoupper(substr($_SESSION['user_nome'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span class="user-name"><?= e($_SESSION['user_nome'] ?? 'Utente') ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="user-dropdown-menu" id="userDropdownMenu">
                        <div class="dropdown-header">
                            <div class="dropdown-user-info">
                                <div class="user-avatar-lg">
                                    <?= strtoupper(substr($_SESSION['user_nome'] ?? 'U', 0, 1) . substr($_SESSION['user_cognome'] ?? '', 0, 1)) ?>
                                </div>
                                <div>
                                    <strong><?= e(($_SESSION['user_nome'] ?? '') . ' ' . ($_SESSION['user_cognome'] ?? '')) ?></strong>
                                    <span><?= e($_SESSION['user_email'] ?? '') ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="index.php?action=profilo" class="dropdown-item">
                            <i class="fas fa-user"></i> Il mio profilo
                        </a>
                        <a href="index.php?action=miei_biglietti" class="dropdown-item">
                            <i class="fas fa-ticket-alt"></i> I miei biglietti
                        </a>
                        <a href="index.php?action=miei_ordini" class="dropdown-item">
                            <i class="fas fa-receipt"></i> Storico ordini
                        </a>
                        <?php if (function_exists('isAdmin') && isAdmin()): ?>
                            <div class="dropdown-divider"></div>
                            <a href="index.php?action=admin_dashboard" class="dropdown-item dropdown-item-admin">
                                <i class="fas fa-shield-alt"></i> Dashboard Admin
                            </a>
                        <?php elseif (function_exists('isMod') && isMod()): ?>
                            <div class="dropdown-divider"></div>
                            <a href="index.php?action=mod_dashboard" class="dropdown-item dropdown-item-mod">
                                <i class="fas fa-user-shield"></i> Moderazione
                            </a>
                        <?php elseif (function_exists('isPromoter') && isPromoter()): ?>
                            <div class="dropdown-divider"></div>
                            <a href="index.php?action=promoter_dashboard" class="dropdown-item dropdown-item-promoter">
                                <i class="fas fa-bullhorn"></i> I miei eventi
                            </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="index.php?action=cambia_password" class="dropdown-item">
                            <i class="fas fa-key"></i> Cambia password
                        </a>
                        <div class="dropdown-item theme-setting">
                            <i class="fas fa-palette"></i> Tema
                            <div class="theme-options">
                                <button class="theme-opt" data-theme="light" title="Chiaro"><i class="fas fa-sun"></i></button>
                                <button class="theme-opt" data-theme="dark" title="Scuro"><i class="fas fa-moon"></i></button>
                                <button class="theme-opt" data-theme="auto" title="Automatico"><i class="fas fa-circle-half-stroke"></i></button>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="index.php?action=elimina_account" class="dropdown-item dropdown-item-danger">
                            <i class="fas fa-trash"></i> Elimina account
                        </a>
                        <form method="post" action="index.php" class="dropdown-form">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <a href="index.php?action=show_login" class="btn-login">Accedi</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!--
    MAIN CONTENT
    Area principale dove viene iniettato il contenuto della pagina corrente.
    I messaggi flash (successo/errore) vengono mostrati prima del contenuto.
    La pagina da caricare è determinata da $_SESSION['page'] (default: 'home').
-->
<main class="main">
    <!-- Messaggi flash di successo -->
    <?php if ($msg ?? null): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= e($msg) ?>
        </div>
    <?php endif; ?>

    <!-- Messaggi flash di errore -->
    <?php if ($error ?? null): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <?php
    // Carica dinamicamente la view corrispondente alla pagina corrente
    // Il controller imposta $_SESSION['page'] prima di includere questo layout
    $page = $_SESSION['page'] ?? 'home';
    require __DIR__ . "/../{$page}.php";
    ?>
</main>

<!--
    NEWSLETTER
    Sezione per l'iscrizione alla newsletter con protezione CSRF.
    L'azione 'subscribe_newsletter' viene gestita dal controller.
-->
<section class="newsletter-section">
    <div class="newsletter-content">
        <div class="newsletter-text">
            <h3><i class="fas fa-envelope"></i> Iscriviti alla Newsletter</h3>
            <p>Ricevi anteprime esclusive, offerte speciali e aggiornamenti sui tuoi eventi preferiti.</p>
        </div>
        <form class="newsletter-form" method="post" action="index.php">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="subscribe_newsletter">
            <input type="email" name="newsletter_email" placeholder="La tua email..." required>
            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Iscriviti</button>
        </form>
    </div>
</section>

<!--
    FOOTER
    Link informativi (chi siamo, supporto, legale) e collegamenti social.
    L'anno del copyright viene generato dinamicamente.
-->
<footer class="footer">
    <div class="footer-content">
        <div class="footer-section">
            <h4>Chi Siamo</h4>
            <a href="#">La nostra storia</a>
            <a href="#">Team</a>
            <a href="#">Lavora con noi</a>
            <a href="#">Press</a>
        </div>
        <div class="footer-section">
            <h4>Supporto</h4>
            <a href="#">Centro assistenza</a>
            <a href="#">FAQ</a>
            <a href="#">Contattaci</a>
            <a href="#">Rimborsi</a>
        </div>
        <div class="footer-section">
            <h4>Legale</h4>
            <a href="#">Termini di servizio</a>
            <a href="#">Privacy Policy</a>
            <a href="#">Cookie Policy</a>
            <a href="#">Condizioni vendita</a>
        </div>
        <div class="footer-section">
            <h4>Seguici</h4>
            <a href="#"><i class="fab fa-facebook"></i> Facebook</a>
            <a href="#"><i class="fab fa-instagram"></i> Instagram</a>
            <a href="#"><i class="fab fa-twitter"></i> Twitter</a>
            <a href="#"><i class="fab fa-youtube"></i> YouTube</a>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="footer-logo">EventsMaster</div>
        <p class="footer-copy">&copy; <?= date('Y') ?> EventsMaster. Tutti i diritti riservati.</p>
    </div>
</footer>

<!--
    CART SIDEBAR
    Pannello laterale del carrello, gestito interamente via JavaScript.
    Mostra gli articoli nel carrello (localStorage) e permette il checkout.
    Il comportamento cambia in base allo stato di login dell'utente.
-->
<div class="cart-sidebar" id="cartSidebar">
    <div class="cart-header">
        <h3><i class="fas fa-shopping-cart"></i> Il tuo carrello</h3>
        <button class="cart-close" id="cartClose">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="cart-body" id="cartBody">
        <div class="cart-empty" id="cartEmpty">
            <i class="fas fa-shopping-basket"></i>
            <p>Il carrello è vuoto</p>
            <a href="index.php?action=list_eventi" class="btn btn-primary">Scopri gli eventi</a>
        </div>
        <div class="cart-items" id="cartItems"></div>
    </div>
    <div class="cart-footer" id="cartFooter" style="display: none;">
        <div class="cart-total">
            <span>Totale:</span>
            <strong id="cartTotal">€0,00</strong>
        </div>
        <?php if (isLoggedIn()): ?>
            <a href="index.php?action=checkout" class="btn btn-primary btn-block">Procedi al checkout</a>
        <?php else: ?>
            <a href="index.php?action=show_login&redirect=checkout" class="btn btn-primary btn-block">Accedi per acquistare</a>
        <?php endif; ?>
    </div>
</div>
<div class="cart-overlay" id="cartOverlay"></div>

<!--
    MODAL DUPLICATI CARRELLO
    Appare quando l'utente effettua il login e ha biglietti nel carrello locale
    che sono già presenti nel carrello salvato sul server. Offre la scelta
    tra mantenere il carrello locale o unire i due carrelli.
-->
<div class="modal" id="cartMergeModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Biglietti duplicati</h3>
            <button class="modal-close" id="modalClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p>Alcuni biglietti nel tuo carrello sono già presenti nel carrello salvato del tuo account.</p>
            <p>Come vuoi procedere?</p>
            <div id="duplicatesList"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="keepLocal">Mantieni carrello locale</button>
            <button class="btn btn-primary" id="mergeCart">Unisci i carrelli</button>
        </div>
    </div>
</div>

<script>
    /**
     * Configurazione globale JavaScript
     * Passa dati PHP al frontend per la gestione del carrello e dell'autenticazione.
     * - isLoggedIn: stato login per comportamenti condizionali
     * - userId: ID utente per sincronizzazione carrello
     * - csrfToken: token per richieste AJAX protette
     * - redirectAfterLogin: URL di redirect post-login (es. checkout)
     */
    window.EventsMaster = {
        isLoggedIn: <?= isLoggedIn() ? 'true' : 'false' ?>,
        userId: <?= isLoggedIn() ? ($_SESSION['user_id'] ?? 'null') : 'null' ?>,
        csrfToken: '<?= $_SESSION['csrf_token'] ?? '' ?>',
        redirectAfterLogin: '<?= $_GET['redirect'] ?? '' ?>'
    };
</script>
<script src="public/script.js?v=<?= time() ?>"></script>
</body>
</html>
