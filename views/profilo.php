<?php
/**
 * Pagina Profilo Utente
 *
 * Mostra e permette la modifica dei dati dell'utente autenticato.
 *
 * Sezioni:
 * - Header: avatar con iniziali, nome completo, email, data iscrizione
 * - Informazioni Personali: form per modificare nome, cognome, email
 * - Sicurezza: link a cambio password
 * - Zona Pericolosa: link a eliminazione account
 *
 * I dati utente sono recuperati da $_SESSION['user_data'] impostato dal controller.
 */

$user = $_SESSION['user_data'] ?? null;

// Protezione: la pagina richiede autenticazione
if (!$user) {
    echo '<div class="no-data-container"><p class="no-data">Devi effettuare il login per vedere il tuo profilo.</p></div>';
    return;
}
?>

<?php
$hasAvatar = !empty($user['Avatar']);
$userId = (int)($_SESSION['user_id'] ?? 0);
?>
<div class="profile-page">
    <div class="profile-header">
        <div class="profile-avatar-container" style="position:relative;display:inline-block;">
            <?php if ($hasAvatar): ?>
                <img src="index.php?action=get_avatar&id=<?= $userId ?>"
                     alt="Avatar"
                     class="profile-avatar-large profile-avatar-img"
                     id="profileAvatarImg"
                     style="object-fit:cover;border-radius:50%;">
            <?php else: ?>
                <div class="profile-avatar-large profile-avatar-initials" id="profileAvatarInitials">
                    <?= strtoupper(substr($user['Nome'] ?? 'U', 0, 1) . substr($user['Cognome'] ?? '', 0, 1)) ?>
                </div>
            <?php endif; ?>
            <label for="avatarInput"
                   title="Cambia foto profilo"
                   style="position:absolute;bottom:0;right:0;background:var(--primary,#6366f1);color:#fff;border-radius:50%;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:0.85rem;">
                <i class="fas fa-camera"></i>
            </label>
            <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/gif" style="display:none;">
        </div>

        <div class="profile-info">
            <h1><?= e($user['Nome'] . ' ' . $user['Cognome']) ?></h1>
            <p class="profile-email"><i class="fas fa-envelope"></i> <?= e($user['Email']) ?></p>
            <p class="profile-since"><i class="fas fa-calendar"></i> Membro dal <?= date('d/m/Y', strtotime($user['DataRegistrazione'] ?? $user['created_at'] ?? 'now')) ?></p>
            <div id="avatarFeedback" style="margin-top:0.25rem;font-size:0.85rem;"></div>
            <?php if ($hasAvatar): ?>
            <button type="button" class="btn btn-sm btn-danger" id="deleteAvatarBtn" style="margin-top:0.5rem;">
                <i class="fas fa-trash"></i> Rimuovi foto
            </button>
            <?php endif; ?>
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

<script>
(function() {
    const input    = document.getElementById('avatarInput');
    const feedback = document.getElementById('avatarFeedback');
    const csrfToken = window.EventsMaster?.csrfToken || '';

    function updateAllHeaderAvatars(url) {
        document.querySelectorAll('.user-avatar, .user-avatar-lg').forEach(el => {
            let img = el.querySelector('img');
            if (!img) {
                img = document.createElement('img');
                img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:50%;';
                el.innerHTML = '';
                el.appendChild(img);
            }
            img.src = url + '&t=' + Date.now();
        });
    }

    if (input) {
        input.addEventListener('change', async function() {
            const file = this.files[0];
            if (!file) return;

            // Anteprima immediata
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById('profileAvatarImg');
                const initials = document.getElementById('profileAvatarInitials');
                if (img) {
                    img.src = e.target.result;
                } else if (initials) {
                    const newImg = document.createElement('img');
                    newImg.src = e.target.result;
                    newImg.className = 'profile-avatar-large profile-avatar-img';
                    newImg.id = 'profileAvatarImg';
                    newImg.style.cssText = 'object-fit:cover;border-radius:50%;';
                    initials.replaceWith(newImg);
                }
            };
            reader.readAsDataURL(file);

            feedback.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Caricamento...';
            const fd = new FormData();
            fd.append('avatar', file);
            fd.append('csrf_token', csrfToken);
            fd.append('action', 'upload_avatar');

            try {
                const res  = await fetch('index.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success || data.data) {
                    feedback.innerHTML = '<span style="color:green"><i class="fas fa-check"></i> Foto aggiornata!</span>';
                    const avatarUrl = (data.data?.avatarUrl) || ('index.php?action=get_avatar&id=<?= $userId ?>');
                    updateAllHeaderAvatars(avatarUrl);
                    setTimeout(() => { feedback.innerHTML = ''; }, 3000);
                } else {
                    feedback.innerHTML = '<span style="color:red"><i class="fas fa-times"></i> ' + (data.error || 'Errore') + '</span>';
                }
            } catch(e) {
                feedback.innerHTML = '<span style="color:red">Errore di connessione</span>';
            }
        });
    }

    const deleteBtn = document.getElementById('deleteAvatarBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', async function() {
            if (!confirm('Rimuovere la foto profilo?')) return;
            const fd = new FormData();
            fd.append('csrf_token', csrfToken);
            fd.append('action', 'delete_avatar');
            try {
                const res  = await fetch('index.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success || data.data !== undefined) {
                    location.reload();
                }
            } catch(e) {}
        });
    }
})();
</script>
