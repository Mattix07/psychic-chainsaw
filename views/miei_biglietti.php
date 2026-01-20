<?php
/**
 * I Miei Biglietti - Biglietti acquistati per eventi futuri
 *
 * Mostra solo i biglietti per eventi non ancora avvenuti.
 * I biglietti passati sono visibili nello storico ordini.
 *
 * Per ogni biglietto mostra:
 * - Tipo (Standard, VIP, ecc.) e settore
 * - Nome evento e intestatario biglietto
 * - Data, ora e location dell'evento
 * - Prezzo finale e ID biglietto
 *
 * Il filtro su eventi futuri evita di mostrare biglietti inutilizzabili.
 */
require_once __DIR__ . '/../models/Biglietto.php';

// Recupera solo biglietti per eventi con data >= oggi
$biglietti = getBigliettiUtenteFuturi($pdo, $_SESSION['user_id']);
?>

<div class="profile-page">
    <div class="page-header">
        <h1><i class="fas fa-ticket-alt"></i> I Miei Biglietti</h1>
        <p class="subtitle">Biglietti per eventi ancora da venire</p>
    </div>

    <?php if (empty($biglietti)): ?>
        <div class="no-data-container">
            <i class="fas fa-ticket-alt"></i>
            <p>Non hai biglietti per eventi futuri.</p>
            <a href="index.php?action=list_eventi" class="btn btn-primary">Esplora gli eventi</a>
        </div>
    <?php else: ?>
        <div class="tickets-grid">
            <?php foreach ($biglietti as $b): ?>
                <div class="ticket-card">
                    <div class="ticket-header">
                        <span class="ticket-type"><?= e($b['Classe']) ?></span>
                        <span class="ticket-sector">Settore <?= $b['idSettore'] ?></span>
                    </div>
                    <div class="ticket-body">
                        <h3><?= e($b['EventoNome']) ?></h3>
                        <div class="ticket-details">
                            <p><i class="fas fa-user"></i> <?= e($b['Nome'] . ' ' . $b['Cognome']) ?></p>
                            <p><i class="fas fa-calendar"></i> <?= formatDate($b['Data']) ?></p>
                            <p><i class="fas fa-clock"></i> <?= formatTime($b['OraI']) ?></p>
                            <p><i class="fas fa-map-marker-alt"></i> <?= e($b['LocationName']) ?></p>
                        </div>
                    </div>
                    <div class="ticket-footer">
                        <span class="ticket-price"><?= formatPrice($b['PrezzoFinale']) ?></span>
                        <span class="ticket-id">ID: <?= $b['id'] ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
