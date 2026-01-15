<?php
/**
 * Form di Login
 */
?>

<div class="auth-form">
    <h1>Accedi</h1>

    <form method="post" action="index.php">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="login">

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Accedi</button>
    </form>

    <p class="auth-links">
        Non hai un account? <a href="index.php?action=register">Registrati</a>
    </p>
</div>
