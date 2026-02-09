<?php
/**
 * Lista Manifestazioni - Gestione per Promoter/Admin/Mod
 */
requireRole(ROLE_PROMOTER);

$manifestazioni = $_SESSION['manifestazioni_list'] ?? [];
$canDelete = hasRole(ROLE_MOD);

// Determina la dashboard corretta in base al ruolo
$dashboardAction = 'promoter_dashboard';
if (hasRole(ROLE_ADMIN)) {
    $dashboardAction = 'admin_dashboard';
} elseif (hasRole(ROLE_MOD)) {
    $dashboardAction = 'mod_dashboard';
}
?>

<div class="admin-page">
    <div class="admin-header">
        <div>
            <a href="index.php?action=<?= $dashboardAction ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Dashboard
            </a>
            <h1><i class="fas fa-calendar-check"></i> Gestione Manifestazioni</h1>
        </div>
        <a href="index.php?action=create_manifestazione" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuova Manifestazione
        </a>
    </div>

    <?php if (empty($manifestazioni)): ?>
        <div class="no-data-container">
            <i class="fas fa-calendar-check"></i>
            <p>Nessuna manifestazione trovata</p>
            <a href="index.php?action=create_manifestazione" class="btn btn-primary">
                <i class="fas fa-plus"></i> Crea la prima manifestazione
            </a>
        </div>
    <?php else: ?>
        <div class="admin-table-container">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Descrizione</th>
                        <th>Data Inizio</th>
                        <th>Data Fine</th>
                        <th>Stato</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($manifestazioni as $manif): ?>
                        <?php
                        $oggi = date('Y-m-d');
                        $stato = 'In programma';
                        $statoClass = 'status-info';

                        if ($manif['DataFine'] && $manif['DataFine'] < $oggi) {
                            $stato = 'Conclusa';
                            $statoClass = 'status-inactive';
                        } elseif ($manif['DataInizio'] && $manif['DataInizio'] <= $oggi && $manif['DataFine'] >= $oggi) {
                            $stato = 'In corso';
                            $statoClass = 'status-active';
                        }
                        ?>
                        <tr>
                            <td><strong><?= e($manif['Nome']) ?></strong></td>
                            <td><?= e(substr($manif['Descrizione'] ?? '', 0, 100)) ?><?= strlen($manif['Descrizione'] ?? '') > 100 ? '...' : '' ?></td>
                            <td><?= $manif['DataInizio'] ? formatDate($manif['DataInizio']) : '-' ?></td>
                            <td><?= $manif['DataFine'] ? formatDate($manif['DataFine']) : '-' ?></td>
                            <td><span class="status-badge <?= $statoClass ?>"><?= $stato ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="index.php?action=edit_manifestazione&id=<?= $manif['id'] ?>"
                                       class="btn btn-sm btn-secondary" title="Modifica">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($canDelete): ?>
                                        <form method="post" action="index.php?action=delete_manifestazione_form" style="display: inline;">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= $manif['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Eliminare questa manifestazione? Gli eventi associati perderanno il riferimento.')"
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
                <i class="fas fa-calendar-check"></i>
                <div>
                    <h3><?= count($manifestazioni) ?></h3>
                    <p>Manifestazioni totali</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
