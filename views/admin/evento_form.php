<?php
/**
 * Admin - Form Creazione/Modifica Evento
 */
$locations = $_SESSION['admin_locations'] ?? [];
$manifestazioni = $_SESSION['admin_manifestazioni'] ?? [];
$evento = $_SESSION['admin_evento'] ?? null;
$isEdit = !empty($evento);
?>

<div class="admin-page">
    <div class="admin-header">
        <div>
            <a href="index.php?action=admin_events" class="back-link"><i class="fas fa-arrow-left"></i> Eventi</a>
            <h1><i class="fas fa-<?= $isEdit ? 'edit' : 'plus-circle' ?>"></i> <?= $isEdit ? 'Modifica' : 'Nuovo' ?> Evento</h1>
        </div>
    </div>

    <div class="admin-form-container">
        <form method="post" action="index.php?action=admin_create_event" class="admin-form">
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
    </div>
</div>
