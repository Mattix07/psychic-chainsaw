<?php
/**
 * Lista Location - Gestione per Promoter/Admin/Mod
 */
requireRole(ROLE_PROMOTER);

$locations = $_SESSION['locations_list'] ?? [];
$ruolo = $_SESSION['user_ruolo'] ?? 'user';
$canDelete = in_array($ruolo, ['admin', 'mod']);
?>

<div class="admin-page">
    <div class="admin-header">
        <div>
            <a href="index.php?action=<?= $ruolo ?>_dashboard" class="back-link">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
            <h1><i class="fas fa-map-marker-alt"></i> Gestione Location</h1>
        </div>
        <a href="index.php?action=create_location" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuova Location
        </a>
    </div>

    <?php if (empty($locations)): ?>
        <div class="no-data-container">
            <i class="fas fa-map-marker-alt"></i>
            <p>Nessuna location trovata</p>
            <a href="index.php?action=create_location" class="btn btn-primary">
                <i class="fas fa-plus"></i> Crea la prima location
            </a>
        </div>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Città</th>
                        <th>Regione</th>
                        <th>Capienza</th>
                        <th>Indirizzo</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($locations as $location): ?>
                        <tr>
                            <td><strong><?= e($location['Nome']) ?></strong></td>
                            <td><?= e($location['Citta']) ?></td>
                            <td><?= e($location['Regione']) ?></td>
                            <td><?= number_format($location['Capienza']) ?> posti</td>
                            <td><?= e($location['Indirizzo']) ?>, <?= e($location['CAP']) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="index.php?action=edit_location&id=<?= $location['id'] ?>"
                                       class="btn btn-sm btn-secondary" title="Modifica">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($canDelete): ?>
                                        <form method="post" action="index.php?action=delete_location_form" style="display: inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= $location['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Eliminare questa location? Gli eventi associati perderanno il riferimento.')"
                                                    title="Elimina">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-stats">
            <div class="stat-card">
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <h3><?= count($locations) ?></h3>
                    <p>Location totali</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
