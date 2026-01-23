<?php
/**
 * Dashboard Promoter
 *
 * Pannello di controllo per utenti con ruolo 'promoter'.
 * I promoter possono creare e visualizzare i propri eventi.
 *
 * Funzionalità:
 * - Pulsante per creare nuovo evento
 * - Griglia dei propri eventi con:
 *   - Data e stato (Passato/In arrivo)
 *   - Nome, location, orario
 *   - Prezzo e link al dettaglio
 *
 * Gli eventi passati sono visualmente differenziati con classe 'past'.
 * A differenza degli admin, i promoter vedono solo i propri eventi.
 */
$eventi = $_SESSION['promoter_eventi'] ?? [];
?>

<div class="admin-page">
    <div class="admin-header">
        <h1><i class="fas fa-bullhorn"></i> Dashboard Promoter</h1>
        <p class="subtitle">Gestisci i tuoi eventi</p>
    </div>

    <!-- Quick Actions -->
    <div class="admin-section">
        <div class="quick-actions">
            <a href="index.php?action=admin_create_event" class="action-card action-card-primary">
                <i class="fas fa-plus-circle"></i>
                <span>Crea Nuovo Evento</span>
            </a>
            <a href="index.php?action=list_locations" class="action-card">
                <i class="fas fa-map-marker-alt"></i>
                <span>Gestisci Location</span>
            </a>
            <a href="index.php?action=list_manifestazioni" class="action-card">
                <i class="fas fa-calendar-check"></i>
                <span>Gestisci Manifestazioni</span>
            </a>
        </div>
    </div>

    <!-- My Events -->
    <div class="admin-section">
        <h2><i class="fas fa-calendar-alt"></i> I Miei Eventi</h2>

        <?php if (empty($eventi)): ?>
            <div class="no-data-container">
                <i class="fas fa-calendar-plus"></i>
                <p>Non hai ancora creato eventi.</p>
                <a href="index.php?action=admin_create_event" class="btn btn-primary">Crea il tuo primo evento</a>
            </div>
        <?php else: ?>
            <div class="events-grid-admin">
                <?php foreach ($eventi as $e): ?>
                    <div class="event-card-admin <?= strtotime($e['Data']) < time() ? 'past' : '' ?>">
                        <div class="event-card-header">
                            <span class="event-date"><?= formatDate($e['Data']) ?></span>
                            <?php if (strtotime($e['Data']) < time()): ?>
                                <span class="event-status past">Passato</span>
                            <?php else: ?>
                                <span class="event-status upcoming">In arrivo</span>
                            <?php endif; ?>
                        </div>
                        <div class="event-card-body">
                            <h3><?= e($e['Nome']) ?></h3>
                            <p><i class="fas fa-map-marker-alt"></i> <?= e($e['LocationName']) ?></p>
                            <p><i class="fas fa-clock"></i> <?= formatTime($e['OraI']) ?> - <?= formatTime($e['OraF']) ?></p>
                        </div>
                        <div class="event-card-footer">
                            <span class="event-price"><?= formatPrice($e['PrezzoNoMod']) ?></span>
                            <a href="index.php?action=view_evento&id=<?= $e['id'] ?>" class="btn btn-secondary btn-sm">
                                <i class="fas fa-eye"></i> Vedi
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
