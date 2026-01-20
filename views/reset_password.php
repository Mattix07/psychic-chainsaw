<?php
/**
 * Pagina Reset Password - Form nuova password
 *
 * Seconda fase del processo di reset password.
 * L'utente arriva qui cliccando il link ricevuto via email.
 * Il token viene passato come parametro GET e validato dal controller.
 *
 * Se il token è valido e non scaduto, l'utente può inserire
 * la nuova password (minimo 6 caratteri, da confermare).
 * Dopo il reset, il token viene invalidato.
 */
$token = $_GET['token'] ?? '';
?>

<div class="auth-page">
    <div class="auth-form">
        <h1><i class="fas fa-key"></i> Nuova Password</h1>
        <p class="subtitle">Inserisci la tua nuova password.</p>

        <form method="post" action="index.php">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="do_reset_password">
            <input type="hidden" name="token" value="<?= e($token) ?>">

            <div class="form-group">
                <label for="password">Nuova Password</label>
                <input type="password" id="password" name="password" placeholder="Minimo 6 caratteri" required minlength="6">
            </div>

            <div class="form-group">
                <label for="password_confirm">Conferma Password</label>
                <input type="password" id="password_confirm" name="password_confirm" placeholder="Ripeti la password" required minlength="6">
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-save"></i> Salva Nuova Password
            </button>
        </form>

        <p class="auth-links">
            <a href="index.php?action=show_login"><i class="fas fa-arrow-left"></i> Torna al login</a>
        </p>
    </div>
</div>
