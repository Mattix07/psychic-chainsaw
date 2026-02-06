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
$locations = $_SESSION['admin_locations'] ?? [];
$manifestazioni = $_SESSION['admin_manifestazioni'] ?? [];
$evento = $_SESSION['admin_evento'] ?? null;
$isEdit = !empty($evento);

// Carica settori disponibili
require_once __DIR__ . '/../../models/Settore.php';
$settoriDisponibili = getAllSettori($pdo);

// Se in modifica, carica settori già associati
$settoriSelezionati = [];
if ($isEdit) {
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
                <p class="form-hint">Seleziona i settori disponibili per questo evento. Se non selezioni nessun settore, saranno disponibili tutti i settori della location.</p>

                <div class="settori-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px;">
                    <?php foreach ($settoriDisponibili as $settore): ?>
                        <?php
                        $isSelected = in_array($settore['id'], array_column($settoriSelezionati, 'id'));
                        ?>
                        <label class="checkbox-card" style="display: flex; align-items: center; padding: 12px; border: 1px solid #ddd; border-radius: 6px; cursor: pointer;">
                            <input type="checkbox" name="settori[]" value="<?= $settore['id'] ?>" <?= $isSelected ? 'checked' : '' ?> style="margin-right: 8px;">
                            <div>
                                <strong><?= e($settore['Nome']) ?></strong>
                                <small style="display: block; color: #666;">Posti: <?= $settore['PostiDisponibili'] ?> | ×<?= $settore['MoltiplicatorePrezzo'] ?></small>
                            </div>
                        </label>
                    <?php endforeach; ?>
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
