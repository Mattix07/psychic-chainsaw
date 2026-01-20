<?php
/**
 * Form di Registrazione
 *
 * Permette la creazione di un nuovo account utente.
 * Campi richiesti: nome, cognome, email, password (min 6 caratteri).
 * La password deve essere confermata per evitare errori di digitazione.
 *
 * Dopo la registrazione, viene inviata un'email di verifica
 * (se il sistema email è configurato in modalità reale).
 * Protezione CSRF attiva su tutti i form.
 */
?>

<div class="auth-page">
    <div class="auth-form">
        <h1>Registrati</h1>
        <p class="subtitle">Crea un account per acquistare biglietti.</p>

        <form method="post" action="index.php">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="register">

            <div class="form-row">
                <div class="form-group">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" placeholder="Il tuo nome" required>
                </div>
                <div class="form-group">
                    <label for="cognome">Cognome</label>
                    <input type="text" id="cognome" name="cognome" placeholder="Il tuo cognome" required>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="La tua email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Minimo 6 caratteri" required minlength="6">
            </div>

            <div class="form-group">
                <label for="password_confirm">Conferma Password</label>
                <input type="password" id="password_confirm" name="password_confirm" placeholder="Ripeti la password" required minlength="6">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Registrati</button>
        </form>

        <p class="auth-links">
            Hai già un account? <a href="index.php?action=show_login">Accedi</a>
        </p>
    </div>
</div>
