<?php
/**
 * Risultati ricerca eventi per manifestazione
 */
$eventi = $_SESSION['eventi_ricerca'] ?? [];
$nomeRicerca = $_SESSION['ricerca_nome'] ?? '';
?>

<h1>Eventi per: <?= e($nomeRicerca) ?></h1>

<?php if (empty($eventi)): ?>
    <p class="no-data">Nessun evento trovato per questa manifestazione.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Nome Evento</th>
                <th>Data</th>
                <th>Inizio</th>
                <th>Fine</th>
                <th>Luogo</th>
                <th>Prezzo Base</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($eventi as $evento): ?>
                <tr>
                    <td><?= e($evento['eNome']) ?></td>
                    <td><?= formatDate($evento['Data']) ?></td>
                    <td><?= formatTime($evento['OraI']) ?></td>
                    <td><?= formatTime($evento['OraF']) ?></td>
                    <td><?= e($evento['LocationName']) ?></td>
                    <td><?= formatPrice($evento['PrezzoNoMod']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p>
    <a href="index.php" class="btn btn-secondary">Nuova Ricerca</a>
    <a href="index.php?action=list_eventi" class="btn btn-primary">Tutti gli Eventi</a>
</p>
