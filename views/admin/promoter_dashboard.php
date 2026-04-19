<?php
/**
 * Dashboard Promoter
 */
$eventi               = $_SESSION['promoter_eventi'] ?? [];
$eventiCollab         = $_SESSION['promoter_eventi_collaborazione'] ?? [];
$isModOrAdmin         = hasRole(ROLE_MOD);
$userId               = $_SESSION['user_id'] ?? 0;
unset($_SESSION['promoter_eventi_collaborazione']);

/**
 * Renderizza una card evento con i pulsanti appropriati
 * $canEdit: true se l'utente può modificare/eliminare l'evento
 */
function renderEventCardPromoter(array $e, bool $canEdit): void { ?>
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
            <div style="display:flex; gap:0.4rem;">
                <a href="index.php?action=view_evento&id=<?= $e['id'] ?>" class="btn btn-secondary btn-sm">
                    <i class="fas fa-eye"></i>
                </a>
                <?php if ($canEdit): ?>
                <a href="index.php?action=admin_edit_event&id=<?= $e['id'] ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-edit"></i>
                </a>
                <form method="post" action="index.php?action=admin_delete_event" style="display:inline;"
                      onsubmit="return confirm('Eliminare l\'evento <?= e(addslashes($e['Nome'])) ?>?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $e['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php }
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

    <!-- I Miei Eventi -->
    <?php
    $eventiAttivi  = array_filter($eventi, fn($e) => $e['Data'] >= date('Y-m-d'));
    $eventiPassati = array_filter($eventi, fn($e) => $e['Data'] < date('Y-m-d'));
    ?>
    <div class="admin-section">
        <h2><i class="fas fa-calendar-alt"></i> I Miei Eventi</h2>

        <?php if (empty($eventiAttivi)): ?>
            <div class="no-data-container">
                <i class="fas fa-calendar-plus"></i>
                <p>Non hai eventi attivi al momento.</p>
                <a href="index.php?action=admin_create_event" class="btn btn-primary">Crea il tuo primo evento</a>
            </div>
        <?php else: ?>
            <div class="events-grid-admin">
                <?php foreach ($eventiAttivi as $e): ?>
                    <?php renderEventCardPromoter($e, true) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Archivio eventi passati -->
    <?php if (!empty($eventiPassati)): ?>
    <div class="admin-section">
        <h2>
            <i class="fas fa-archive"></i> Archivio eventi passati (<?= count($eventiPassati) ?>)
            <button type="button" class="btn btn-sm btn-secondary"
                    style="margin-left:1rem;font-size:0.8rem;"
                    onclick="var a=document.getElementById('archivioEventiPromoter');a.style.display=a.style.display==='none'?'block':'none'">
                Mostra/Nascondi
            </button>
        </h2>
        <div id="archivioEventiPromoter" style="display:none;">
            <div class="events-grid-admin">
                <?php foreach ($eventiPassati as $e): ?>
                    <?php renderEventCardPromoter($e, true) ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Eventi in Collaborazione -->
    <?php if (!$isModOrAdmin): ?>
    <div class="admin-section">
        <h2><i class="fas fa-handshake"></i> Eventi in Collaborazione</h2>

        <?php if (empty($eventiCollab)): ?>
            <p class="no-data">Non collabori a nessun evento al momento.</p>
        <?php else: ?>
            <div class="events-grid-admin">
                <?php foreach ($eventiCollab as $e): ?>
                    <?php renderEventCardPromoter($e, false) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
