<?php
/**
 * Dettaglio Ordine
 *
 * Mostra tutti i dettagli di un ordine specifico.
 *
 * Informazioni visualizzate:
 * - Riepilogo: metodo pagamento, numero biglietti, totale ordine
 * - Lista biglietti con:
 *   - Nome evento e intestatario
 *   - Data e ora evento
 *   - Tipo biglietto
 *   - Stato validazione (Validato/Da utilizzare)
 *
 * L'accesso è protetto: il controller verifica che l'ordine
 * appartenga all'utente loggato prima di mostrarlo.
 */
require_once __DIR__ . '/../models/Ordine.php';

// Dati ordine passati dal controller via sessione
$ordine = $_SESSION['ordine_corrente'] ?? null;
$biglietti = $_SESSION['biglietti_ordine'] ?? [];

if (!$ordine) {
    echo '<div class="no-data-container"><p class="no-data">Ordine non trovato.</p></div>';
    return;
}

// Calcola totale sommando i prezzi finali di tutti i biglietti
$totale = calcolaTotaleOrdine($pdo, $ordine['id']);
?>

<div class="profile-page">
    <div class="page-header">
        <a href="index.php?action=miei_ordini" class="back-link"><i class="fas fa-arrow-left"></i> Torna agli ordini</a>
        <h1><i class="fas fa-receipt"></i> Ordine #<?= $ordine['id'] ?></h1>
    </div>

    <div class="order-detail-card">
        <div class="order-detail-header">
            <div class="order-detail-info">
                <h2>Riepilogo Ordine</h2>
                <p><strong>Metodo di pagamento:</strong> <?= e($ordine['Metodo']) ?></p>
                <p><strong>Numero biglietti:</strong> <?= count($biglietti) ?></p>
            </div>
            <div class="order-detail-total">
                <span class="label">Totale</span>
                <span class="price"><?= formatPrice($totale) ?></span>
            </div>
        </div>

        <div class="order-tickets">
            <h3><i class="fas fa-ticket-alt"></i> Biglietti</h3>
            <?php if (empty($biglietti)): ?>
                <p class="no-data">Nessun biglietto trovato.</p>
            <?php else: ?>
                <div class="tickets-list">
                    <?php foreach ($biglietti as $b): ?>
                        <div class="ticket-item">
                            <div class="ticket-item-info">
                                <h4><?= e($b['EventoNome']) ?></h4>
                                <p><i class="fas fa-user"></i> <?= e($b['Nome'] . ' ' . $b['Cognome']) ?></p>
                                <p><i class="fas fa-calendar"></i> <?= formatDate($b['Data']) ?> - <?= formatTime($b['OraI']) ?></p>
                                <p><i class="fas fa-tag"></i> <?= e($b['idClasse']) ?></p>
                            </div>
                            <div class="ticket-item-status">
                                <?php if ($b['Check']): ?>
                                    <span class="tag tag-success"><i class="fas fa-check"></i> Validato</span>
                                <?php else: ?>
                                    <span class="tag tag-info"><i class="fas fa-clock"></i> Da utilizzare</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
