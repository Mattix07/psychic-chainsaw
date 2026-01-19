<?php
/**
 * Admin - Gestione Eventi
 */
$eventi = $_SESSION['admin_eventi'] ?? [];
?>

<div class="admin-page">
    <div class="admin-header">
        <div>
            <a href="index.php?action=admin_dashboard" class="back-link"><i class="fas fa-arrow-left"></i> Dashboard</a>
            <h1><i class="fas fa-calendar-alt"></i> Gestione Eventi</h1>
        </div>
        <a href="index.php?action=admin_create_event" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuovo Evento
        </a>
    </div>

    <!-- Events Table -->
    <div class="admin-table-container">
        <?php if (empty($eventi)): ?>
            <div class="no-data-container">
                <i class="fas fa-calendar-times"></i>
                <p>Nessun evento trovato.</p>
            </div>
        <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Data</th>
                    <th>Location</th>
                    <th>Prezzo Base</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($eventi as $e): ?>
                <tr class="<?= strtotime($e['Data']) < time() ? 'row-past' : '' ?>">
                    <td>#<?= $e['id'] ?></td>
                    <td>
                        <strong><?= e($e['Nome']) ?></strong>
                        <?php if ($e['ManifestazioneName']): ?>
                            <br><small class="text-muted"><?= e($e['ManifestazioneName']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= formatDate($e['Data']) ?>
                        <br><small class="text-muted"><?= formatTime($e['OraI']) ?> - <?= formatTime($e['OraF']) ?></small>
                    </td>
                    <td><?= e($e['LocationName']) ?></td>
                    <td><?= formatPrice($e['PrezzoNoMod']) ?></td>
                    <td>
                        <div class="action-buttons">
                            <a href="index.php?action=view_evento&id=<?= $e['id'] ?>" class="btn-icon" title="Visualizza">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="index.php?action=admin_edit_event&id=<?= $e['id'] ?>" class="btn-icon" title="Modifica">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="post" action="index.php" class="inline-form" onsubmit="return confirm('Sei sicuro di voler eliminare questo evento?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="admin_delete_event">
                                <input type="hidden" name="evento_id" value="<?= $e['id'] ?>">
                                <button type="submit" class="btn-icon btn-danger" title="Elimina">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
