<?php
/**
 * Form Creazione/Modifica Location
 */
requireRole(ROLE_PROMOTER);

$location = $_SESSION['current_location'] ?? null;
$settori  = $_SESSION['current_location_settori'] ?? [];
$isEdit = !empty($location);

// Pulisci sessione
unset($_SESSION['current_location'], $_SESSION['current_location_settori']);
?>

<div class="admin-page">
    <div class="admin-header">
        <div>
            <a href="index.php?action=list_locations" class="back-link">
                <i class="fas fa-arrow-left"></i> Location
            </a>
            <h1><i class="fas fa-<?= $isEdit ? 'edit' : 'plus-circle' ?>"></i> <?= $isEdit ? 'Modifica' : 'Nuova' ?> Location</h1>
        </div>
    </div>

    <div class="admin-form-container">
        <form method="post" action="index.php?action=save_location" class="admin-form">
            <?= csrfField() ?>
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= $location['id'] ?>">
            <?php endif; ?>

            <div class="form-section">
                <h3>Informazioni Location</h3>

                <div class="form-group">
                    <label for="nome">Nome Location *</label>
                    <input type="text" id="nome" name="nome" value="<?= e($location['Nome'] ?? '') ?>" required>
                    <small class="form-hint">Es: Stadio San Siro, Teatro alla Scala</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="citta">Città *</label>
                        <input type="text" id="citta" name="citta" value="<?= e($location['Citta'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="regione">Regione</label>
                        <input type="text" id="regione" name="regione" value="<?= e($location['Regione'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="cap">CAP</label>
                        <input type="text" id="cap" name="cap" value="<?= e($location['CAP'] ?? '') ?>" maxlength="5">
                    </div>
                </div>

                <div class="form-group">
                    <label for="indirizzo">Indirizzo</label>
                    <input type="text" id="indirizzo" name="indirizzo" value="<?= e($location['Indirizzo'] ?? '') ?>">
                    <small class="form-hint">Via, numero civico</small>
                </div>

                <div class="form-group">
                    <label for="capienza">Capienza Totale</label>
                    <input type="number" id="capienza" name="capienza" value="<?= $location['Capienza'] ?? 0 ?>" min="0">
                    <small class="form-hint">Numero massimo di posti disponibili</small>
                </div>
            </div>

            <div class="form-actions">
                <a href="index.php?action=list_locations" class="btn btn-secondary">Annulla</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?= $isEdit ? 'Salva Modifiche' : 'Crea Location' ?>
                </button>
            </div>
        </form>

        <?php if ($isEdit): ?>
        <!-- SEZIONE SETTORI -->
        <div class="form-section" style="margin-top: 2rem;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem;">
                <h3><i class="fas fa-th-large"></i> Settori</h3>
                <button class="btn btn-primary btn-sm" onclick="openSettoreModal()">
                    <i class="fas fa-plus"></i> Aggiungi Settore
                </button>
            </div>

            <?php if (empty($settori)): ?>
                <p class="no-data" style="padding:1rem 0;">Nessun settore configurato per questa location.</p>
            <?php else: ?>
                <div class="table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>File</th>
                                <th>Posti/Fila</th>
                                <th>Posti Totali</th>
                                <th>Moltiplicatore</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($settori as $s): ?>
                            <tr>
                                <td><strong><?= e($s['Nome']) ?></strong></td>
                                <td><?= $s['NumFile'] ?? '—' ?></td>
                                <td><?= $s['PostiPerFila'] ?? '—' ?></td>
                                <td><?= $s['PostiTotali'] ?></td>
                                <td>×<?= number_format($s['MoltiplicatorePrezzo'], 2) ?></td>
                                <td>
                                    <button class="btn btn-secondary btn-sm"
                                        onclick="openSettoreModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="post" action="index.php?action=delete_settore" style="display:inline;"
                                          onsubmit="return confirm('Eliminare il settore <?= e(addslashes($s['Nome'])) ?>?')">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="settore_id" value="<?= $s['id'] ?>">
                                        <input type="hidden" name="location_id" value="<?= $location['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Modal Settore -->
        <div class="modal" id="settoreModal">
            <div class="modal-content" style="max-width:480px; overflow:visible;">
                <div class="modal-header">
                    <h3 id="settoreModalTitle"><i class="fas fa-th-large"></i> Settore</h3>
                    <button class="modal-close" onclick="closeSettoreModal()"><i class="fas fa-times"></i></button>
                </div>
                <form method="post" action="index.php?action=save_settore" id="settoreForm">
                    <?= csrfField() ?>
                    <input type="hidden" name="settore_id" id="settoreId" value="0">
                    <input type="hidden" name="location_id" value="<?= $location['id'] ?>">

                    <div class="modal-body" style="overflow-y:auto; max-height:60vh;">
                        <div class="form-group">
                            <label for="settore_nome">Nome Settore *</label>
                            <input type="text" id="settore_nome" name="settore_nome" required placeholder="Es: Tribuna Nord, Pista, Platea">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="settore_num_file">Numero File</label>
                                <input type="number" id="settore_num_file" name="settore_num_file" min="0" placeholder="Es: 10">
                                <small class="form-hint">Lascia vuoto se non numerato</small>
                            </div>
                            <div class="form-group">
                                <label for="settore_posti_per_fila">Posti per Fila</label>
                                <input type="number" id="settore_posti_per_fila" name="settore_posti_per_fila" min="0" placeholder="Es: 20">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="settore_posti_totali">Posti Totali *</label>
                                <input type="number" id="settore_posti_totali" name="settore_posti_totali" min="0" required placeholder="Es: 200">
                                <small class="form-hint" id="posti_totali_hint"></small>
                            </div>
                            <div class="form-group">
                                <label for="settore_moltiplicatore">Moltiplicatore Prezzo</label>
                                <input type="number" id="settore_moltiplicatore" name="settore_moltiplicatore"
                                       step="0.01" min="0.01" value="1.00" placeholder="Es: 1.50">
                                <small class="form-hint">1.00 = prezzo base</small>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeSettoreModal()">Annulla</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salva Settore
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($isEdit): ?>
<script>
function openSettoreModal(settore) {
    const modal = document.getElementById('settoreModal');
    const title = document.getElementById('settoreModalTitle');

    if (settore) {
        title.innerHTML = '<i class="fas fa-edit"></i> Modifica Settore';
        document.getElementById('settoreId').value = settore.id;
        document.getElementById('settore_nome').value = settore.Nome || '';
        document.getElementById('settore_num_file').value = settore.NumFile || '';
        document.getElementById('settore_posti_per_fila').value = settore.PostiPerFila || '';
        document.getElementById('settore_posti_totali').value = settore.PostiTotali || '';
        document.getElementById('settore_moltiplicatore').value = parseFloat(settore.MoltiplicatorePrezzo || 1).toFixed(2);
    } else {
        title.innerHTML = '<i class="fas fa-plus"></i> Nuovo Settore';
        document.getElementById('settoreId').value = '0';
        document.getElementById('settoreForm').reset();
        document.getElementById('settore_moltiplicatore').value = '1.00';
    }

    aggiornaHintPostiTotali();
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeSettoreModal() {
    document.getElementById('settoreModal').classList.remove('active');
    document.body.style.overflow = '';
}

document.getElementById('settoreModal').addEventListener('click', function(e) {
    if (e.target === this) closeSettoreModal();
});

function aggiornaHintPostiTotali() {
    const file = parseInt(document.getElementById('settore_num_file').value) || 0;
    const postiPerFila = parseInt(document.getElementById('settore_posti_per_fila').value) || 0;
    const hint = document.getElementById('posti_totali_hint');
    const input = document.getElementById('settore_posti_totali');

    if (file > 0 && postiPerFila > 0) {
        const max = file * postiPerFila;
        const min = file; // almeno 1 posto per fila
        input.max = max;
        input.min = min;
        hint.textContent = 'Min: ' + min + ' — Max: ' + max + ' (' + file + ' file × ' + postiPerFila + ' posti)';
        // Correggi il valore se fuori range
        const val = parseInt(input.value) || 0;
        if (val > max) input.value = max;
        else if (val < min) input.value = min;
    } else {
        hint.textContent = '';
        input.removeAttribute('max');
        input.min = 0;
    }
}

document.getElementById('settore_num_file').addEventListener('input', aggiornaHintPostiTotali);
document.getElementById('settore_posti_per_fila').addEventListener('input', aggiornaHintPostiTotali);
</script>
<?php endif; ?>
