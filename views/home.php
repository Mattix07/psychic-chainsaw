<?php
/**
 * Homepage - Layout Netflix-style con carousel
 */
require_once __DIR__ . '/../models/Evento.php';
require_once __DIR__ . '/../models/Manifestazione.php';

$eventiProssimi = getEventiProssimi($pdo, 12);
$manifestazioni = getAllManifestazioni($pdo);
$tuttiEventi = getAllEventi($pdo);

// Evento in evidenza (il primo prossimo)
$eventoHero = !empty($eventiProssimi) ? $eventiProssimi[0] : null;

// Categorie per i carousel
$categorie = [
    'concerti' => 'Concerti',
    'teatro' => 'Teatro',
    'festival' => 'Festival',
    'sport' => 'Sport',
    'comedy' => 'Comedy'
];
?>

<!-- HERO BILLBOARD -->
<?php if ($eventoHero): ?>
<section class="hero-billboard">
    <div class="hero-bg" style="background-image: url('img/events/<?= $eventoHero['id'] ?>.jpg'), linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);"></div>
    <div class="hero-content">
        <span class="hero-tag"><?= e($eventoHero['ManifestazioneName']) ?></span>
        <h1 class="hero-title"><?= e($eventoHero['Nome']) ?></h1>
        <div class="hero-info">
            <span><i class="fas fa-calendar"></i> <?= formatDate($eventoHero['Data']) ?></span>
            <span><i class="fas fa-clock"></i> <?= formatTime($eventoHero['OraI']) ?></span>
            <span><i class="fas fa-map-marker-alt"></i> <?= e($eventoHero['LocationName']) ?></span>
        </div>
        <?php if (!empty($eventoHero['Programma'])): ?>
            <p class="hero-description"><?= e($eventoHero['Programma']) ?></p>
        <?php else: ?>
            <p class="hero-description">Scopri questo fantastico evento e acquista i tuoi biglietti prima che sia troppo tardi!</p>
        <?php endif; ?>
        <div class="hero-actions">
            <a href="index.php?action=view_evento&id=<?= $eventoHero['id'] ?>" class="btn-hero btn-hero-primary">
                <i class="fas fa-ticket"></i> Acquista Biglietti
            </a>
            <a href="index.php?action=view_evento&id=<?= $eventoHero['id'] ?>" class="btn-hero btn-hero-secondary">
                <i class="fas fa-info-circle"></i> Maggiori Info
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CATEGORY SHORTCUTS -->
<div class="category-shortcuts">
    <a href="index.php" class="category-btn active"><i class="fas fa-fire"></i> Tutti</a>
    <a href="index.php?action=category&cat=concerti" class="category-btn"><i class="fas fa-music"></i> Concerti</a>
    <a href="index.php?action=category&cat=teatro" class="category-btn"><i class="fas fa-theater-masks"></i> Teatro</a>
    <a href="index.php?action=category&cat=sport" class="category-btn"><i class="fas fa-futbol"></i> Sport</a>
    <a href="index.php?action=category&cat=comedy" class="category-btn"><i class="fas fa-laugh"></i> Comedy</a>
    <a href="index.php?action=category&cat=cinema" class="category-btn"><i class="fas fa-film"></i> Cinema</a>
    <a href="index.php?action=category&cat=famiglia" class="category-btn"><i class="fas fa-child"></i> Famiglia</a>
</div>

<!-- CAROUSEL: In Evidenza (Locandine Grandi) -->
<?php if (!empty($tuttiEventi)): ?>
<section class="row-section">
    <div class="row-header">
        <h2 class="row-title">
            <i class="fas fa-star"></i> In Evidenza
            <a href="index.php?action=list_eventi">Vedi tutti <i class="fas fa-chevron-right"></i></a>
        </h2>
    </div>
    <div class="carousel-container">
        <button class="carousel-nav prev" data-carousel="featured"><i class="fas fa-chevron-left"></i></button>
        <div class="carousel" id="featured">
            <?php foreach ($tuttiEventi as $evento): ?>
            <article class="event-card large" onclick="window.location='index.php?action=view_evento&id=<?= $evento['id'] ?>'">
                <div class="event-card-poster">
                    <img src="img/events/<?= $evento['id'] ?>.jpg"
                         alt="<?= e($evento['Nome']) ?>"
                         onerror="this.src='https://picsum.photos/400/600?random=<?= $evento['id'] ?>'">
                    <span class="event-card-badge">In vendita</span>
                    <div class="event-card-overlay">
                        <div class="event-card-actions">
                            <button class="card-action-btn primary" onclick="event.stopPropagation(); addToCart(<?= $evento['id'] ?>, 1, '<?= e($evento['Nome']) ?>', 'Standard', <?= $evento['PrezzoNoMod'] ?>, '<?= formatDate($evento['Data']) ?>', 'img/events/<?= $evento['id'] ?>.jpg')"><i class="fas fa-cart-plus"></i></button>
                            <button class="card-action-btn"><i class="fas fa-heart"></i></button>
                        </div>
                    </div>
                </div>
                <div class="event-card-info">
                    <h3 class="event-card-title"><?= e($evento['Nome']) ?></h3>
                    <div class="event-card-meta">
                        <span class="event-card-date"><?= formatDate($evento['Data']) ?></span>
                        <span><?= e($evento['LocationName']) ?></span>
                        <span class="event-card-price">da <?= formatPrice($evento['PrezzoNoMod']) ?></span>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <button class="carousel-nav next" data-carousel="featured"><i class="fas fa-chevron-right"></i></button>
    </div>
</section>
<?php endif; ?>

<!-- CAROUSEL: Prossimi Eventi -->
<section class="row-section">
    <div class="row-header">
        <h2 class="row-title">
            <i class="fas fa-calendar-alt"></i> Prossimi Eventi
            <a href="index.php?action=list_eventi">Vedi tutti <i class="fas fa-chevron-right"></i></a>
        </h2>
    </div>
    <div class="carousel-container">
        <button class="carousel-nav prev" data-carousel="upcoming"><i class="fas fa-chevron-left"></i></button>
        <div class="carousel" id="upcoming">
            <?php foreach ($eventiProssimi as $evento): ?>
            <article class="event-card" onclick="window.location='index.php?action=view_evento&id=<?= $evento['id'] ?>'">
                <div class="event-card-poster">
                    <img src="img/events/<?= $evento['id'] ?>.jpg"
                         alt="<?= e($evento['Nome']) ?>"
                         onerror="this.src='https://picsum.photos/250/375?random=<?= $evento['id'] + 100 ?>'">
                    <div class="event-card-overlay">
                        <div class="event-card-actions">
                            <button class="card-action-btn primary"><i class="fas fa-ticket"></i></button>
                            <button class="card-action-btn"><i class="fas fa-heart"></i></button>
                        </div>
                    </div>
                </div>
                <div class="event-card-info">
                    <h3 class="event-card-title"><?= e($evento['Nome']) ?></h3>
                    <div class="event-card-meta">
                        <span class="event-card-date"><?= formatDate($evento['Data']) ?></span>
                        <span class="event-card-price"><?= formatPrice($evento['PrezzoNoMod']) ?></span>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <button class="carousel-nav next" data-carousel="upcoming"><i class="fas fa-chevron-right"></i></button>
    </div>
</section>

<!-- BANNER PROMO -->
<div class="banner-card">
    <div class="banner-card-bg" style="background-image: url('https://picsum.photos/1200/300?random=banner');"></div>
    <div class="banner-card-content">
        <h3 class="banner-card-title">Non perdere gli eventi del momento!</h3>
        <p class="banner-card-text">Iscriviti alla newsletter per ricevere offerte esclusive e anteprime sui biglietti.</p>
        <a href="#" class="btn btn-primary"><i class="fas fa-envelope"></i> Iscriviti Ora</a>
    </div>
</div>

<!-- CAROUSEL: Per Manifestazione -->
<?php foreach ($manifestazioni as $manifestazione): ?>
<?php
$eventiManifestazione = getEventiByManifestazione($pdo, $manifestazione['id']);
if (empty($eventiManifestazione)) continue;
?>
<section class="row-section">
    <div class="row-header">
        <h2 class="row-title">
            <?= e($manifestazione['Nome']) ?>
            <a href="index.php?action=search_eventi&nome=<?= urlencode($manifestazione['Nome']) ?>">Vedi tutti <i class="fas fa-chevron-right"></i></a>
        </h2>
    </div>
    <div class="carousel-container">
        <button class="carousel-nav prev" data-carousel="manif-<?= $manifestazione['id'] ?>"><i class="fas fa-chevron-left"></i></button>
        <div class="carousel" id="manif-<?= $manifestazione['id'] ?>">
            <?php foreach ($eventiManifestazione as $evento): ?>
            <article class="event-card" onclick="window.location='index.php?action=view_evento&id=<?= $evento['id'] ?>'">
                <div class="event-card-poster">
                    <img src="img/events/<?= $evento['id'] ?>.jpg"
                         alt="<?= e($evento['Nome']) ?>"
                         onerror="this.src='https://picsum.photos/250/375?random=<?= $evento['id'] + 200 ?>'">
                    <div class="event-card-overlay">
                        <div class="event-card-actions">
                            <button class="card-action-btn primary"><i class="fas fa-ticket"></i></button>
                            <button class="card-action-btn"><i class="fas fa-heart"></i></button>
                        </div>
                    </div>
                </div>
                <div class="event-card-info">
                    <h3 class="event-card-title"><?= e($evento['Nome']) ?></h3>
                    <div class="event-card-meta">
                        <span class="event-card-date"><?= formatDate($evento['Data']) ?></span>
                        <span class="event-card-price"><?= formatPrice($evento['PrezzoNoMod']) ?></span>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <button class="carousel-nav next" data-carousel="manif-<?= $manifestazione['id'] ?>"><i class="fas fa-chevron-right"></i></button>
    </div>
</section>
<?php endforeach; ?>

<!-- GRID: Potrebbe Interessarti -->
<section class="grid-section">
    <div class="row-header">
        <h2 class="row-title">
            <i class="fas fa-lightbulb"></i> Potrebbe Interessarti
        </h2>
    </div>
    <div class="events-grid">
        <?php
        $eventiRandom = array_slice($tuttiEventi, 0, 6);
        foreach ($eventiRandom as $evento):
        ?>
        <article class="event-card" onclick="window.location='index.php?action=view_evento&id=<?= $evento['id'] ?>'">
            <div class="event-card-poster">
                <img src="img/events/<?= $evento['id'] ?>.jpg"
                     alt="<?= e($evento['Nome']) ?>"
                     onerror="this.src='https://picsum.photos/200/300?random=<?= $evento['id'] + 300 ?>'">
                <div class="event-card-overlay">
                    <div class="event-card-actions">
                        <button class="card-action-btn primary"><i class="fas fa-ticket"></i></button>
                    </div>
                </div>
            </div>
            <div class="event-card-info">
                <h3 class="event-card-title"><?= e($evento['Nome']) ?></h3>
                <div class="event-card-meta">
                    <span class="event-card-date"><?= formatDate($evento['Data']) ?></span>
                    <span class="event-card-price"><?= formatPrice($evento['PrezzoNoMod']) ?></span>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</section>
