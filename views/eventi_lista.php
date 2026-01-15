<?php
/**
 * Lista completa degli eventi
 */
$eventi = $_SESSION['eventi'] ?? [];
?>

<h1>Tutti gli Eventi</h1>

<?php if (empty($eventi)): ?>
    <p class="no-data">Nessun evento disponibile.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Manifestazione</th>
                <th>Data</th>
                <th>Orario</th>
                <th>Luogo</th>
                <th>Prezzo Base</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($eventi as $evento): ?>
                <tr>
                    <td><?= e($evento['Nome']) ?></td>
                    <td><?= e($evento['ManifestazioneName']) ?></td>
                    <td><?= formatDate($evento['Data']) ?></td>
                    <td><?= formatTime($evento['OraI']) ?> - <?= formatTime($evento['OraF']) ?></td>
                    <td><?= e($evento['LocationName']) ?></td>
                    <td><?= formatPrice($evento['PrezzoNoMod']) ?></td>
                    <td>
                        <a href="index.php?action=view_evento&id=<?= $evento['id'] ?>" class="btn btn-small">
                            Dettagli
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p>
    <a href="index.php" class="btn btn-secondary">Torna alla Home</a>
</p>
