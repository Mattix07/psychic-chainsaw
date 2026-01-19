<?php
/**
 * Pagina Cambia Password
 */
?>

<div class="auth-page">
    <div class="auth-form">
        <h1><i class="fas fa-key"></i> Cambia Password</h1>
        <p class="subtitle">Aggiorna la tua password per mantenere sicuro il tuo account.</p>

        <form method="post" action="index.php">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update_password">

            <div class="form-group">
                <label for="current_password">Password Attuale</label>
                <input type="password" id="current_password" name="current_password" placeholder="La tua password attuale" required>
            </div>

            <div class="form-group">
                <label for="new_password">Nuova Password</label>
                <input type="password" id="new_password" name="new_password" placeholder="Minimo 6 caratteri" required minlength="6">
            </div>

            <div class="form-group">
                <label for="confirm_password">Conferma Nuova Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Ripeti la nuova password" required minlength="6">
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-save"></i> Aggiorna Password
            </button>
        </form>

        <p class="auth-links">
            <a href="index.php?action=profilo"><i class="fas fa-arrow-left"></i> Torna al profilo</a>
        </p>
    </div>
</div>
