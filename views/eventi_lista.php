<?php
/**
 * Lista completa degli eventi con card grid
 */
$eventi = $_SESSION['eventi'] ?? [];
$categoriaNome = $_SESSION['categoria_nome'] ?? 'Tutti gli Eventi';
?>

<div class="events-page">
    <div class="events-header">
        <h1><?= e($categoriaNome) ?></h1>
        <span class="events-count"><?= count($eventi) ?> eventi trovati</span>
    </div>

    <?php if (empty($eventi)): ?>
        <div class="no-data-container">
            <i class="fas fa-calendar-times"></i>
            <p>Nessun evento disponibile in questa categoria.</p>
            <a href="index.php" class="btn btn-primary">Torna alla Home</a>
        </div>
    <?php else: ?>
        <div class="events-grid">
            <?php foreach ($eventi as $evento): ?>
            <article class="event-card" onclick="window.location='index.php?action=view_evento&id=<?= $evento['id'] ?>'">
                <div class="event-card-poster">
                    <img src="img/events/<?= $evento['id'] ?>.jpg"
                         alt="<?= e($evento['Nome']) ?>"
                         onerror="this.src='https://picsum.photos/250/375?random=<?= $evento['id'] ?>'">
                    <span class="event-card-badge"><?= e($evento['ManifestazioneName'] ?? 'Evento') ?></span>
                    <div class="event-card-overlay">
                        <div class="event-card-actions">
                            <button class="card-action-btn primary" onclick="event.stopPropagation(); addToCart(<?= $evento['id'] ?>, 1, '<?= e($evento['Nome']) ?>', 'Standard', <?= $evento['PrezzoNoMod'] ?>, '<?= formatDate($evento['Data']) ?>', 'img/events/<?= $evento['id'] ?>.jpg')">
                                <i class="fas fa-cart-plus"></i>
                            </button>
                            <button class="card-action-btn" onclick="event.stopPropagation();">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="event-card-info">
                    <h3 class="event-card-title"><?= e($evento['Nome']) ?></h3>
                    <div class="event-card-meta">
                        <span class="event-card-date"><?= formatDate($evento['Data']) ?></span>
                        <span class="event-card-price">da <?= formatPrice($evento['PrezzoNoMod']) ?></span>
                    </div>
                    <div class="event-card-location">
                        <i class="fas fa-map-marker-alt"></i> <?= e($evento['LocationName']) ?>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
