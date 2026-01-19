<?php
/**
 * Pagina Profilo Utente
 */

$user = $_SESSION['user_data'] ?? null;

if (!$user) {
    echo '<div class="no-data-container"><p class="no-data">Devi effettuare il login per vedere il tuo profilo.</p></div>';
    return;
}
?>

<div class="profile-page">
    <div class="profile-header">
        <div class="profile-avatar-large">
            <?= strtoupper(substr($user['Nome'] ?? 'U', 0, 1) . substr($user['Cognome'] ?? '', 0, 1)) ?>
        </div>
        <div class="profile-info">
            <h1><?= e($user['Nome'] . ' ' . $user['Cognome']) ?></h1>
            <p class="profile-email"><i class="fas fa-envelope"></i> <?= e($user['Email']) ?></p>
            <p class="profile-since"><i class="fas fa-calendar"></i> Membro dal <?= date('d/m/Y', strtotime($user['created_at'] ?? 'now')) ?></p>
        </div>
    </div>

    <div class="profile-content">
        <div class="profile-section">
            <h2><i class="fas fa-user"></i> Informazioni Personali</h2>
            <form method="post" action="index.php" class="profile-form">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_profile">

                <div class="form-row">
                    <div class="form-group">
                        <label for="nome">Nome</label>
                        <input type="text" id="nome" name="nome" value="<?= e($user['Nome']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="cognome">Cognome</label>
                        <input type="text" id="cognome" name="cognome" value="<?= e($user['Cognome']) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= e($user['Email']) ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salva Modifiche
                </button>
            </form>
        </div>

        <div class="profile-section">
            <h2><i class="fas fa-shield-alt"></i> Sicurezza</h2>
            <div class="security-options">
                <a href="index.php?action=cambia_password" class="security-option">
                    <i class="fas fa-key"></i>
                    <div>
                        <h4>Cambia Password</h4>
                        <p>Aggiorna la tua password per mantenere sicuro il tuo account</p>
                    </div>
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>

        <div class="profile-section danger-zone">
            <h2><i class="fas fa-exclamation-triangle"></i> Zona Pericolosa</h2>
            <div class="security-options">
                <a href="index.php?action=elimina_account" class="security-option danger">
                    <i class="fas fa-trash"></i>
                    <div>
                        <h4>Elimina Account</h4>
                        <p>Elimina permanentemente il tuo account e tutti i dati associati</p>
                    </div>
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>
