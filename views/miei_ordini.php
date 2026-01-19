<?php
/**
 * I Miei Ordini - Storico ordini utente
 */

$ordini = $_SESSION['ordini_utente'] ?? [];
?>

<div class="profile-page">
    <div class="page-header">
        <h1><i class="fas fa-receipt"></i> Storico Ordini</h1>
        <p class="subtitle">Tutti i tuoi acquisti su EventsMaster</p>
    </div>

    <?php if (empty($ordini)): ?>
        <div class="no-data-container">
            <i class="fas fa-shopping-bag"></i>
            <p>Non hai ancora effettuato ordini.</p>
            <a href="index.php?action=list_eventi" class="btn btn-primary">Scopri gli eventi</a>
        </div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($ordini as $ordine): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-id">
                            <span class="label">Ordine</span>
                            <strong>#<?= $ordine['id'] ?></strong>
                        </div>
                        <div class="order-method">
                            <i class="fas fa-<?= $ordine['Metodo'] === 'Carta' ? 'credit-card' : ($ordine['Metodo'] === 'PayPal' ? 'paypal' : 'university') ?>"></i>
                            <?= e($ordine['Metodo']) ?>
                        </div>
                    </div>
                    <div class="order-body">
                        <div class="order-stat">
                            <i class="fas fa-ticket-alt"></i>
                            <span><?= $ordine['num_biglietti'] ?> bigliett<?= $ordine['num_biglietti'] == 1 ? 'o' : 'i' ?></span>
                        </div>
                    </div>
                    <div class="order-footer">
                        <a href="index.php?action=view_ordine&id=<?= $ordine['id'] ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-eye"></i> Dettagli
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
