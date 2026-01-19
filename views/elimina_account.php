<?php
/**
 * Pagina Elimina Account
 */
?>

<div class="auth-page">
    <div class="auth-form danger-form">
        <h1><i class="fas fa-exclamation-triangle"></i> Elimina Account</h1>
        <p class="subtitle danger-text">Attenzione: questa azione è irreversibile!</p>

        <div class="warning-box">
            <h4>Cosa succederà:</h4>
            <ul>
                <li>Il tuo account verrà eliminato permanentemente</li>
                <li>Tutti i tuoi biglietti acquistati saranno cancellati</li>
                <li>Le tue recensioni rimarranno ma saranno anonime</li>
                <li>Non potrai recuperare nessun dato</li>
            </ul>
        </div>

        <form method="post" action="index.php">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="delete_account">

            <div class="form-group">
                <label for="password">Inserisci la tua password per confermare</label>
                <input type="password" id="password" name="password" placeholder="La tua password" required>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="confirm_delete" required>
                    <span>Confermo di voler eliminare definitivamente il mio account</span>
                </label>
            </div>

            <button type="submit" class="btn btn-danger btn-block">
                <i class="fas fa-trash"></i> Elimina Account Definitivamente
            </button>
        </form>

        <p class="auth-links">
            <a href="index.php?action=profilo"><i class="fas fa-arrow-left"></i> Annulla e torna al profilo</a>
        </p>
    </div>
</div>
