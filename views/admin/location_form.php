<?php
/**
 * Form Creazione/Modifica Location
 */
requireRole(ROLE_PROMOTER);

$location = $_SESSION['current_location'] ?? null;
$isEdit = !empty($location);

// Pulisci sessione
unset($_SESSION['current_location']);
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
    </div>
</div>
