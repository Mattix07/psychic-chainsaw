<?php
/**
 * Admin - Form Creazione/Modifica Evento
 *
 * Form unificato per creare nuovi eventi o modificare quelli esistenti.
 * La modalità (crea/modifica) è determinata dalla presenza di $evento in sessione.
 *
 * Sezioni del form:
 * - Informazioni Base: nome, data, ora inizio/fine, programma/descrizione
 * - Location e Prezzi: location (select), prezzo base, manifestazione (opzionale)
 * - Immagine: URL immagine evento
 *
 * In modalità modifica, l'ID evento viene passato come hidden field.
 * Location e manifestazioni sono precaricate dal controller via sessione.
 */
$locations      = $_SESSION['admin_locations'] ?? [];
$manifestazioni = $_SESSION['admin_manifestazioni'] ?? [];
$evento         = $_SESSION['admin_evento'] ?? null;
$collaboratori  = $_SESSION['admin_evento_collaboratori'] ?? [];
$creatore       = $_SESSION['admin_evento_creatore'] ?? null;
$isEdit         = !empty($evento);
$isOwner        = $isEdit && $creatore && ($creatore['id'] === ($_SESSION['user_id'] ?? 0) || hasRole(ROLE_MOD));
unset($_SESSION['admin_evento_collaboratori'], $_SESSION['admin_evento_creatore']);

// Carica settori della location selezionata
require_once __DIR__ . '/../../models/Location.php';
$settoriDisponibili = [];
$settoriSelezionati = [];

if ($isEdit && !empty($evento['idLocation'])) {
    $settoriDisponibili = getSettoriByLocation($pdo, (int) $evento['idLocation']);
    require_once __DIR__ . '/../../models/EventoSettori.php';
    $settoriSelezionati = getEventoSettori($pdo, $evento['id']);
}
?>

<div class="admin-page">
    <div class="admin-header">
        <div>
            <a href="index.php?action=admin_events" class="back-link"><i class="fas fa-arrow-left"></i> Eventi</a>
            <h1><i class="fas fa-<?= $isEdit ? 'edit' : 'plus-circle' ?>"></i> <?= $isEdit ? 'Modifica' : 'Nuovo' ?> Evento</h1>
        </div>
    </div>

    <div class="admin-form-container">
        <form method="post" action="index.php?action=<?= $isEdit ? 'admin_edit_event' : 'admin_create_event' ?>" class="admin-form">
            <?= csrfField() ?>
            <?php if ($isEdit): ?>
                <input type="hidden" name="evento_id" value="<?= $evento['id'] ?>">
            <?php endif; ?>

            <div class="form-section">
                <h3>Informazioni Base</h3>

                <div class="form-group">
                    <label for="nome">Nome Evento *</label>
                    <input type="text" id="nome" name="nome" value="<?= e($evento['Nome'] ?? '') ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="data">Data *</label>
                        <input type="date" id="data" name="data" value="<?= $evento['Data'] ?? '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="ora_inizio">Ora Inizio</label>
                        <input type="time" id="ora_inizio" name="ora_inizio" value="<?= $evento['OraI'] ?? '20:00' ?>">
                    </div>
                    <div class="form-group">
                        <label for="ora_fine">Ora Fine</label>
                        <input type="time" id="ora_fine" name="ora_fine" value="<?= $evento['OraF'] ?? '23:00' ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="programma">Programma / Descrizione</label>
                    <textarea id="programma" name="programma" rows="4"><?= e($evento['Programma'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="form-section">
                <h3>Location e Prezzi</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="location">Location *</label>
                        <select id="location" name="location" required>
                            <option value="">Seleziona location...</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= $loc['id'] ?>" <?= ($evento['idLocation'] ?? '') == $loc['id'] ? 'selected' : '' ?>>
                                    <?= e($loc['Nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="prezzo">Prezzo Base (€) *</label>
                        <input type="number" id="prezzo" name="prezzo" step="0.01" min="0" value="<?= $evento['PrezzoNoMod'] ?? '25.00' ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="manifestazione">Manifestazione (opzionale)</label>
                    <select id="manifestazione" name="manifestazione">
                        <option value="">Nessuna manifestazione</option>
                        <?php foreach ($manifestazioni as $man): ?>
                            <option value="<?= $man['id'] ?>" <?= ($evento['idManifestazione'] ?? '') == $man['id'] ? 'selected' : '' ?>>
                                <?= e($man['Nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-section">
                <h3>Settori Disponibili</h3>
                <p class="form-hint">Seleziona i settori disponibili per questo evento. Deve essere attivo almeno un settore.</p>

                <div id="settori-grid" class="settori-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px;">
                    <?php if (!empty($settoriDisponibili)): ?>
                        <?php
                        $selectedIds = array_column($settoriSelezionati, 'id');
                        foreach ($settoriDisponibili as $settore):
                            $isSelected = in_array($settore['id'], $selectedIds);
                        ?>
                        <label class="checkbox-card" style="display: flex; align-items: center; padding: 12px; border: 1px solid #ddd; border-radius: 6px; cursor: pointer;">
                            <input type="checkbox" name="settori[]" value="<?= $settore['id'] ?>" <?= $isSelected ? 'checked' : '' ?> style="margin-right: 8px;">
                            <div>
                                <strong><?= e($settore['Nome']) ?></strong>
                                <small style="display: block; color: #666;">Posti: <?= $settore['PostiTotali'] ?> | ×<?= $settore['MoltiplicatorePrezzo'] ?></small>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-data" style="grid-column: 1/-1; color: #888;">Seleziona una location per visualizzare i settori disponibili.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-section">
                <h3>Immagine</h3>

                <div class="form-group">
                    <label for="immagine">URL Immagine</label>
                    <input type="url" id="immagine" name="immagine" value="<?= e($evento['Immagine'] ?? '') ?>" placeholder="https://esempio.com/immagine.jpg">
                    <small class="form-hint">Inserisci l'URL di un'immagine per l'evento</small>
                </div>
            </div>

            <div class="form-actions">
                <a href="index.php?action=admin_events" class="btn btn-secondary">Annulla</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?= $isEdit ? 'Salva Modifiche' : 'Crea Evento' ?>
                </button>
            </div>
        </form>

        <?php if ($isEdit): ?>
        <!-- SEZIONE COLLABORATORI -->
        <div class="form-section" style="margin-top:2rem;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
                <h3><i class="fas fa-users"></i> Collaboratori</h3>
                <?php if ($isOwner): ?>
                <button class="btn btn-primary btn-sm" onclick="openInviteModal()">
                    <i class="fas fa-user-plus"></i> Invita Promoter
                </button>
                <?php endif; ?>
            </div>

            <!-- Creatore -->
            <?php if ($creatore): ?>
            <div style="margin-bottom:1rem;">
                <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:0.5rem;">Proprietario</p>
                <div class="ticket-item" style="display:flex; align-items:center; gap:0.75rem; padding:0.75rem 1rem;">
                    <i class="fas fa-crown" style="color:var(--warning);"></i>
                    <div>
                        <strong><?= e($creatore['Nome'] . ' ' . $creatore['Cognome']) ?></strong>
                        <small style="display:block; color:var(--text-muted);"><?= e($creatore['Email']) ?></small>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Lista collaboratori -->
            <?php if (empty($collaboratori)): ?>
                <p class="no-data">Nessun collaboratore aggiunto.</p>
            <?php else: ?>
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Stato</th>
                                <th>Invitato da</th>
                                <?php if ($isOwner): ?><th>Azioni</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($collaboratori as $c): ?>
                            <tr>
                                <td><strong><?= e($c['Nome'] . ' ' . $c['Cognome']) ?></strong></td>
                                <td><?= e($c['Email']) ?></td>
                                <td>
                                    <?php
                                    $statusLabels = ['pending' => ['tag-warning','clock','In attesa'], 'accepted' => ['tag-success','check','Accettato'], 'declined' => ['tag-danger','times','Rifiutato'], 'revoked' => ['tag-secondary','ban','Revocato']];
                                    [$cls, $icon, $label] = $statusLabels[$c['status']] ?? ['tag-secondary','question',$c['status']];
                                    ?>
                                    <span class="tag <?= $cls ?>"><i class="fas fa-<?= $icon ?>"></i> <?= $label ?></span>
                                </td>
                                <td><?= e($c['InvitatoDaNome'] . ' ' . $c['InvitatoDaCognome']) ?></td>
                                <?php if ($isOwner): ?>
                                <td>
                                    <form method="post" action="index.php?action=remove_collaborator" style="display:inline;"
                                          onsubmit="return confirm('Rimuovere <?= e(addslashes($c['Nome'] . ' ' . $c['Cognome'])) ?> dai collaboratori?')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="evento_id" value="<?= $evento['id'] ?>">
                                        <input type="hidden" name="user_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-user-minus"></i></button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($isOwner): ?>
        <!-- Modal Invito -->
        <div class="modal" id="inviteModal">
            <div class="modal-content" style="max-width:460px;">
                <div class="modal-header">
                    <h3><i class="fas fa-user-plus"></i> Invita Collaboratore</h3>
                    <button class="modal-close" onclick="closeInviteModal()"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Cerca per nome o email</label>
                        <input type="text" id="searchPromoter" placeholder="Es: mario, mario@email.com" autocomplete="off">
                    </div>
                    <div id="searchResults" style="margin-top:0.5rem;"></div>
                    <form method="post" action="index.php?action=invite_collaborator" id="inviteForm">
                        <?= csrfField() ?>
                        <input type="hidden" name="evento_id" value="<?= $evento['id'] ?>">
                        <input type="hidden" name="user_id" id="inviteUserId" value="">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeInviteModal()">Annulla</button>
                    <button type="button" class="btn btn-primary" id="inviteSubmit" disabled onclick="document.getElementById('inviteForm').submit()">
                        <i class="fas fa-paper-plane"></i> Invia Invito
                    </button>
                </div>
            </div>
        </div>

        <script>
        function openInviteModal() {
            document.getElementById('searchPromoter').value = '';
            document.getElementById('searchResults').innerHTML = '';
            document.getElementById('inviteUserId').value = '';
            document.getElementById('inviteSubmit').disabled = true;
            document.getElementById('inviteModal').classList.add('active');
            document.body.style.overflow = 'hidden';
            setTimeout(() => document.getElementById('searchPromoter').focus(), 100);
        }
        function closeInviteModal() {
            document.getElementById('inviteModal').classList.remove('active');
            document.body.style.overflow = '';
        }
        document.getElementById('inviteModal').addEventListener('click', function(e) {
            if (e.target === this) closeInviteModal();
        });

        let searchTimer;
        document.getElementById('searchPromoter').addEventListener('input', function() {
            clearTimeout(searchTimer);
            const q = this.value.trim();
            const results = document.getElementById('searchResults');
            if (q.length < 2) { results.innerHTML = ''; return; }
            results.innerHTML = '<p style="color:var(--text-muted); font-size:0.85rem;">Ricerca...</p>';
            searchTimer = setTimeout(() => {
                fetch('index.php?action=search_promoters&q=' + encodeURIComponent(q))
                    .then(r => r.json())
                    .then(res => {
                        if (!res.data || res.data.length === 0) {
                            results.innerHTML = '<p style="color:var(--text-muted); font-size:0.85rem;">Nessun promoter trovato.</p>';
                            return;
                        }
                        results.innerHTML = res.data.map(u => `
                            <div class="search-result-item" onclick="selectPromoter(${u.id}, '${u.Nome} ${u.Cognome}')"
                                 style="display:flex; align-items:center; gap:0.75rem; padding:0.6rem 0.75rem; border-radius:6px; cursor:pointer; border:1px solid var(--border-color); margin-bottom:0.4rem; background:var(--bg-card);">
                                <i class="fas fa-user" style="color:var(--primary);"></i>
                                <div>
                                    <strong>${u.Nome} ${u.Cognome}</strong>
                                    <small style="display:block; color:var(--text-muted);">${u.Email}</small>
                                </div>
                            </div>
                        `).join('');
                    });
            }, 300);
        });

        function selectPromoter(id, name) {
            document.getElementById('inviteUserId').value = id;
            document.getElementById('inviteSubmit').disabled = false;
            document.getElementById('searchResults').innerHTML = `
                <div style="display:flex; align-items:center; gap:0.75rem; padding:0.6rem 0.75rem; border-radius:6px; border:2px solid var(--primary); background:var(--bg-secondary);">
                    <i class="fas fa-check-circle" style="color:var(--primary);"></i>
                    <strong>${name}</strong>
                </div>`;
            document.getElementById('searchPromoter').value = name;
        }
        </script>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('location').addEventListener('change', function() {
    const locationId = this.value;
    const grid = document.getElementById('settori-grid');

    if (!locationId) {
        grid.innerHTML = '<p class="no-data" style="grid-column: 1/-1; color: #888;">Seleziona una location per visualizzare i settori disponibili.</p>';
        return;
    }

    grid.innerHTML = '<p style="grid-column: 1/-1; color: #888;">Caricamento settori...</p>';

    fetch('index.php?action=get_settori_location&idLocation=' + locationId)
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.data || res.data.length === 0) {
                grid.innerHTML = '<p class="no-data" style="grid-column: 1/-1; color: #888;">Nessun settore disponibile per questa location.</p>';
                return;
            }
            grid.innerHTML = res.data.map(s => `
                <label class="checkbox-card" style="display: flex; align-items: center; padding: 12px; border: 1px solid #ddd; border-radius: 6px; cursor: pointer;">
                    <input type="checkbox" name="settori[]" value="${s.id}" checked style="margin-right: 8px;">
                    <div>
                        <strong>${s.Nome}</strong>
                        <small style="display: block; color: #666;">Posti: ${s.PostiDisponibili} | ×${s.MoltiplicatorePrezzo}</small>
                    </div>
                </label>
            `).join('');
        });
});
</script>
