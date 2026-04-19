<?php
$artista = $_SESSION['artista'] ?? [];
$eventi  = $_SESSION['artista_eventi'] ?? [];
unset($_SESSION['artista'], $_SESSION['artista_eventi']);

$nome    = e($artista[COL_INTRATTENITORI_NOME] ?? 'Artista');
$bio     = e($artista['bio'] ?? '');
$social  = json_decode($artista['social_links'] ?? '{}', true) ?: [];
$hasFoto = !empty($artista['foto']);
$idArtista = (int)($artista[COL_INTRATTENITORI_ID] ?? 0);

$eventiPassati = array_filter($eventi, fn($e) => ($e[COL_EVENTI_DATA] ?? '') < date('Y-m-d'));
$eventiFuturi  = array_filter($eventi, fn($e) => ($e[COL_EVENTI_DATA] ?? '') >= date('Y-m-d'));
?>

<div class="artista-profile-page">
    <div class="artista-hero">
        <div class="artista-avatar-wrap">
            <?php if ($hasFoto): ?>
                <img src="index.php?action=get_artista_foto&id=<?= $idArtista ?>" alt="<?= $nome ?>" class="artista-foto">
            <?php else: ?>
                <div class="artista-initials-large"><?= mb_strtoupper(mb_substr($artista[COL_INTRATTENITORI_NOME] ?? 'A', 0, 1)) ?></div>
            <?php endif; ?>
        </div>
        <div class="artista-hero-info">
            <h1><?= $nome ?></h1>
            <?php if ($bio): ?>
                <p class="artista-bio"><?= nl2br($bio) ?></p>
            <?php endif; ?>
            <div class="artista-social">
                <?php if (!empty($social['instagram'])): ?>
                    <a href="https://instagram.com/<?= e($social['instagram']) ?>" target="_blank" rel="noopener" class="social-link"><i class="fab fa-instagram"></i></a>
                <?php endif; ?>
                <?php if (!empty($social['spotify'])): ?>
                    <a href="https://open.spotify.com/artist/<?= e($social['spotify']) ?>" target="_blank" rel="noopener" class="social-link"><i class="fab fa-spotify"></i></a>
                <?php endif; ?>
                <?php if (!empty($social['youtube'])): ?>
                    <a href="https://youtube.com/<?= e($social['youtube']) ?>" target="_blank" rel="noopener" class="social-link"><i class="fab fa-youtube"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($eventiFuturi)): ?>
    <section class="artista-events-section">
        <h2><i class="fas fa-calendar-alt"></i> Prossimi eventi</h2>
        <div class="eventi-grid">
            <?php foreach ($eventiFuturi as $ev): ?>
                <a href="index.php?action=view_evento&id=<?= (int)$ev[COL_EVENTI_ID] ?>" class="event-card-link">
                    <div class="event-card">
                        <?php if (!empty($ev[COL_EVENTI_IMMAGINE])): ?>
                            <img src="index.php?action=view_evento_img&id=<?= (int)$ev[COL_EVENTI_ID] ?>" alt="<?= e($ev[COL_EVENTI_NOME]) ?>" class="event-card-img">
                        <?php endif; ?>
                        <div class="event-card-body">
                            <h3><?= e($ev[COL_EVENTI_NOME]) ?></h3>
                            <p><i class="fas fa-calendar"></i> <?= e($ev[COL_EVENTI_DATA]) ?></p>
                            <p class="event-ruolo"><i class="fas fa-music"></i> <?= e($ev['Ruolo'] ?? '') ?></p>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($eventiPassati)): ?>
    <section class="artista-events-section artista-past-events">
        <h2><i class="fas fa-history"></i> Storico eventi</h2>
        <div class="eventi-grid">
            <?php foreach ($eventiPassati as $ev): ?>
                <a href="index.php?action=view_evento&id=<?= (int)$ev[COL_EVENTI_ID] ?>" class="event-card-link">
                    <div class="event-card past">
                        <div class="event-card-body">
                            <h3><?= e($ev[COL_EVENTI_NOME]) ?></h3>
                            <p><i class="fas fa-calendar"></i> <?= e($ev[COL_EVENTI_DATA]) ?></p>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (empty($eventi)): ?>
        <p class="no-data-msg">Nessun evento trovato per questo artista.</p>
    <?php endif; ?>
</div>
