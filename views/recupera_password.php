<?php
/**
 * Pagina Recupero Password - Richiesta
 *
 * Prima fase del processo di reset password.
 * L'utente inserisce la propria email e riceve un link
 * con token univoco per reimpostare la password.
 *
 * Il token ha una validità limitata nel tempo (configurabile).
 * Per sicurezza, il messaggio di conferma è sempre lo stesso
 * indipendentemente dall'esistenza dell'email nel sistema.
 */
?>

<div class="auth-page">
    <div class="auth-form">
        <h1><i class="fas fa-unlock-alt"></i> Recupera Password</h1>
        <p class="subtitle">Inserisci la tua email per ricevere le istruzioni di reset.</p>

        <form method="post" action="index.php">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="send_reset_email">

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="La tua email registrata" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-paper-plane"></i> Invia Link di Reset
            </button>
        </form>

        <p class="auth-links">
            <a href="index.php?action=show_login"><i class="fas fa-arrow-left"></i> Torna al login</a>
        </p>
    </div>
</div>
