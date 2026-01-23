<?php
/**
 * Form Creazione/Modifica Manifestazione
 */
requireRole(ROLE_PROMOTER);

$manifestazione = $_SESSION['current_manifestazione'] ?? null;
$isEdit = !empty($manifestazione);

// Pulisci sessione
unset($_SESSION['current_manifestazione']);
?>

<div class="admin-page">
    <div class="admin-header">
        <div>
            <a href="index.php?action=list_manifestazioni" class="back-link">
                <i class="fas fa-arrow-left"></i> Manifestazioni
            </a>
            <h1><i class="fas fa-<?= $isEdit ? 'edit' : 'plus-circle' ?>"></i> <?= $isEdit ? 'Modifica' : 'Nuova' ?> Manifestazione</h1>
        </div>
    </div>

    <div class="admin-form-container">
        <form method="post" action="index.php?action=save_manifestazione" class="admin-form">
            <?= csrfField() ?>
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= $manifestazione['id'] ?>">
            <?php endif; ?>

            <div class="form-section">
                <h3>Informazioni Manifestazione</h3>

                <div class="form-group">
                    <label for="nome">Nome Manifestazione *</label>
                    <input type="text" id="nome" name="nome" value="<?= e($manifestazione['Nome'] ?? '') ?>" required>
                    <small class="form-hint">Es: Rock in Italy Festival, Opera Estate 2026</small>
                </div>

                <div class="form-group">
                    <label for="descrizione">Descrizione</label>
                    <textarea id="descrizione" name="descrizione" rows="4"><?= e($manifestazione['Descrizione'] ?? '') ?></textarea>
                    <small class="form-hint">Breve descrizione della manifestazione</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="data_inizio">Data Inizio</label>
                        <input type="date" id="data_inizio" name="data_inizio" value="<?= $manifestazione['DataInizio'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label for="data_fine">Data Fine</label>
                        <input type="date" id="data_fine" name="data_fine" value="<?= $manifestazione['DataFine'] ?? '' ?>">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="index.php?action=list_manifestazioni" class="btn btn-secondary">Annulla</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?= $isEdit ? 'Salva Modifiche' : 'Crea Manifestazione' ?>
                </button>
            </div>
        </form>
    </div>
</div>
