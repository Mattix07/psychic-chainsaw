<?php
/**
 * Form di Login
 */
?>

<div class="auth-page">
    <div class="auth-form">
        <h1>Accedi</h1>
        <p class="subtitle">Bentornato! Inserisci le tue credenziali.</p>

        <form method="post" action="index.php">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="login">

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="La tua email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="La tua password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Accedi</button>
        </form>

        <p class="auth-links">
            <a href="index.php?action=recupera_password">Password dimenticata?</a>
        </p>
        <p class="auth-links">
            Non hai un account? <a href="index.php?action=show_register">Registrati</a>
        </p>
    </div>
</div>
