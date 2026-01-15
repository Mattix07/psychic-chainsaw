<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(env('APP_NAME', 'EventsMaster')) ?> - Biglietti Eventi</title>
    <link rel="stylesheet" href="public/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

<!-- HEADER -->
<header class="header" id="header">
    <div class="container">
        <!-- Logo -->
        <a href="index.php" class="logo">
            <img src="img/logo.png" alt="EventsMaster" onerror="this.style.display='none'">
            <span>Events</span>
        </a>

        <!-- Navigation principale -->
        <nav class="nav-main">
            <a href="index.php" class="<?= ($_SESSION['page'] ?? '') === 'home' ? 'active' : '' ?>">Home</a>
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
                <a href="index.php?action=miei_ordini" title="I miei ordini">
                    <i class="fas fa-ticket"></i>
                </a>
                <div class="user-menu">
                    <div class="user-avatar" title="<?= e($_SESSION['user_nome'] ?? 'Utente') ?>">
                        <?= strtoupper(substr($_SESSION['user_nome'] ?? 'U', 0, 1)) ?>
                    </div>
                </div>
                <form method="post" action="index.php" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </form>
            <?php else: ?>
                <a href="index.php?action=show_login" class="btn-login">Accedi</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- MAIN CONTENT -->
<main class="main">
    <?php if ($msg ?? null): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= e($msg) ?>
        </div>
    <?php endif; ?>

    <?php if ($error ?? null): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <?php
    $page = $_SESSION['page'] ?? 'home';
    require __DIR__ . "/../{$page}.php";
    ?>
</main>

<!-- FOOTER -->
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

<!-- CART SIDEBAR -->
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

<!-- MODAL DUPLICATI CARRELLO -->
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
    // Pass PHP data to JavaScript
    window.EventsMaster = {
        isLoggedIn: <?= isLoggedIn() ? 'true' : 'false' ?>,
        userId: <?= isLoggedIn() ? ($_SESSION['user_id'] ?? 'null') : 'null' ?>,
        csrfToken: '<?= $_SESSION['csrf_token'] ?? '' ?>',
        redirectAfterLogin: '<?= $_GET['redirect'] ?? '' ?>'
    };
</script>
<script src="public/script.js"></script>
</body>
</html>
