<?php
/**
 * Form di Registrazione
 */
?>

<div class="auth-form">
    <h1>Registrati</h1>

    <form method="post" action="index.php">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="register">

        <div class="form-row">
            <div class="form-group">
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" required>
            </div>
            <div class="form-group">
                <label for="cognome">Cognome:</label>
                <input type="text" id="cognome" name="cognome" required>
            </div>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required minlength="6">
        </div>

        <div class="form-group">
            <label for="password_confirm">Conferma Password:</label>
            <input type="password" id="password_confirm" name="password_confirm" required minlength="6">
        </div>

        <button type="submit" class="btn btn-primary btn-block">Registrati</button>
    </form>

    <p class="auth-links">
        Hai gia un account? <a href="index.php?action=login">Accedi</a>
    </p>
</div>
