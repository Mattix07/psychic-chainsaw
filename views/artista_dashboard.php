<?php
$artista = $_SESSION['artista'] ?? [];
$eventi  = $_SESSION['artista_eventi'] ?? [];
unset($_SESSION['artista'], $_SESSION['artista_eventi']);

$social  = json_decode($artista['social_links'] ?? '{}', true) ?: [];
$hasFoto = !empty($artista['foto']);
$idArtista = (int)($artista[COL_INTRATTENITORI_ID] ?? 0);
$eventiPassati = array_filter($eventi, fn($e) => ($e[COL_EVENTI_DATA] ?? '') < date('Y-m-d'));
$eventiFuturi  = array_filter($eventi, fn($e) => ($e[COL_EVENTI_DATA] ?? '') >= date('Y-m-d'));
?>

<div class="artista-dashboard-page">
    <div class="page-header">
        <h1><i class="fas fa-music"></i> Dashboard Artista</h1>
        <a href="index.php?action=artista_profile&id=<?= $idArtista ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-eye"></i> Visualizza profilo pubblico
        </a>
    </div>

    <div class="dashboard-grid">
        <!-- Modifica profilo artista -->
        <div class="dashboard-card">
            <h2><i class="fas fa-edit"></i> Il tuo profilo artista</h2>

            <div class="artista-current-foto">
                <?php if ($hasFoto): ?>
                    <img src="index.php?action=get_artista_foto&id=<?= $idArtista ?>" alt="Foto profilo" class="artista-foto-sm">
                <?php else: ?>
                    <div class="artista-initials-sm"><?= mb_strtoupper(mb_substr($artista[COL_INTRATTENITORI_NOME] ?? 'A', 0, 1)) ?></div>
                <?php endif; ?>
            </div>

            <form method="post" action="index.php" enctype="multipart/form-data" class="artista-edit-form">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update_artista_profile">

                <div class="form-group">
                    <label>Foto profilo</label>
                    <input type="file" name="foto" accept="image/jpeg,image/png" class="form-control">
                    <small class="form-hint">Massimo 3MB, formato JPG o PNG</small>
                </div>

                <div class="form-group">
                    <label>Biografia</label>
                    <textarea name="bio" class="form-control" rows="5" placeholder="Racconta qualcosa di te..."><?= e($artista['bio'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label><i class="fab fa-instagram"></i> Instagram (username)</label>
                    <input type="text" name="instagram" class="form-control" value="<?= e($social['instagram'] ?? '') ?>" placeholder="tuo_username">
                </div>

                <div class="form-group">
                    <label><i class="fab fa-spotify"></i> Spotify (ID artista)</label>
                    <input type="text" name="spotify" class="form-control" value="<?= e($social['spotify'] ?? '') ?>" placeholder="ID artista Spotify">
                </div>

                <div class="form-group">
                    <label><i class="fab fa-youtube"></i> YouTube (canale/@handle)</label>
                    <input type="text" name="youtube" class="form-control" value="<?= e($social['youtube'] ?? '') ?>" placeholder="@canale">
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salva modifiche
                </button>
            </form>
        </div>

        <!-- Statistiche e eventi -->
        <div class="dashboard-right">
            <div class="stats-row">
                <div class="stat-card">
                    <i class="fas fa-calendar-check"></i>
                    <span class="stat-number"><?= count($eventiFuturi) ?></span>
                    <span class="stat-label">Prossimi eventi</span>
                </div>
                <div class="stat-card">
                    <i class="fas fa-history"></i>
                    <span class="stat-number"><?= count($eventiPassati) ?></span>
                    <span class="stat-label">Eventi passati</span>
                </div>
            </div>

            <?php if (!empty($eventiFuturi)): ?>
            <div class="dashboard-card">
                <h3><i class="fas fa-calendar-alt"></i> Prossimi eventi</h3>
                <div class="eventi-list-compact">
                    <?php foreach ($eventiFuturi as $ev): ?>
                        <a href="index.php?action=view_evento&id=<?= (int)$ev[COL_EVENTI_ID] ?>" class="evento-compact-item">
                            <span class="evento-compact-nome"><?= e($ev[COL_EVENTI_NOME]) ?></span>
                            <span class="evento-compact-data"><?= e($ev[COL_EVENTI_DATA]) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($eventiPassati)): ?>
            <div class="dashboard-card">
                <h3><i class="fas fa-history"></i> Ultimi eventi</h3>
                <div class="eventi-list-compact">
                    <?php foreach (array_slice(array_values($eventiPassati), 0, 5) as $ev): ?>
                        <a href="index.php?action=view_evento&id=<?= (int)$ev[COL_EVENTI_ID] ?>" class="evento-compact-item">
                            <span class="evento-compact-nome"><?= e($ev[COL_EVENTI_NOME]) ?></span>
                            <span class="evento-compact-data text-muted"><?= e($ev[COL_EVENTI_DATA]) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
