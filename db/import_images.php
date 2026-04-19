<?php
/**
 * Script importazione immagini di test nel DB
 *
 * USO: eseguire da browser http://localhost/eventsMaster/db/import_images.php
 * oppure da CLI: php db/import_images.php
 *
 * Scarica immagini placeholder da picsum.photos e le salva come BLOB
 * nelle tabelle eventi, locations, manifestazioni e utenti (Avatar).
 *
 * SICUREZZA: aggiungere autenticazione o rimuovere dopo l'uso!
 */

// Solo in ambiente di sviluppo
if (!isset($_GET['confirm']) && php_sapi_name() !== 'cli') {
    echo '<h2>Import immagini DB</h2>';
    echo '<p>Questo script sovrascrive le immagini nel database con placeholder di test.</p>';
    echo '<a href="?confirm=1" style="background:#4f46e5;color:#fff;padding:10px 20px;text-decoration:none;border-radius:6px;">Conferma e avvia import</a>';
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/database_schema.php';

set_time_limit(300); // 5 minuti
ini_set('memory_limit', '256M');

$errors = [];
$success = 0;

function fetchImage(string $url): ?string
{
    $ctx = stream_context_create(['http' => ['timeout' => 15, 'user_agent' => 'EventsMaster/1.0']]);
    $data = @file_get_contents($url, false, $ctx);
    return $data !== false ? $data : null;
}

function importImages(PDO $pdo, string $table, string $idCol, string $imgCol, array $ids, int $width, int $height, array &$errors, int &$success): void
{
    foreach ($ids as $id => $seed) {
        $url = "https://picsum.photos/seed/{$seed}/{$width}/{$height}";
        $img = fetchImage($url);
        if ($img === null) {
            $errors[] = "Impossibile scaricare immagine per {$table} id={$id}";
            continue;
        }
        $stmt = $pdo->prepare("UPDATE {$table} SET {$imgCol} = ? WHERE {$idCol} = ?");
        $stmt->execute([$img, $id]);
        $success++;
        echo "✓ {$table} id={$id} ({$width}×{$height})<br>";
        flush();
        usleep(200000); // 200ms tra ogni richiesta per non sovraccaricare picsum
    }
}

echo '<pre style="font-family:monospace;">';

// ============================================================
// EVENTI (22 eventi, seed variati per categoria)
// ============================================================
$eventiSeeds = [
    1  => 'concert1',    2  => 'concert2',    3  => 'theater1',
    4  => 'theater2',    5  => 'sport1',       6  => 'sport2',
    7  => 'comedy1',     8  => 'comedy2',      9  => 'cinema1',
    10 => 'cinema2',     11 => 'family1',      12 => 'family2',
    13 => 'concert3',    14 => 'sport3',       15 => 'theater3',
    16 => 'concert4',    17 => 'festival1',    18 => 'festival2',
    19 => 'opera1',      20 => 'dance1',       21 => 'comedy3',
    22 => 'sport4',
];

echo "=== EVENTI ===\n";
importImages($pdo, TABLE_EVENTI, COL_EVENTI_ID, COL_EVENTI_IMMAGINE, $eventiSeeds, 800, 450, $errors, $success);

// ============================================================
// LOCATIONS (15 locations)
// ============================================================
$locationSeeds = array_combine(range(1, 15), ['venue1','venue2','venue3','venue4','venue5','venue6','venue7','venue8','venue9','venue10','venue11','venue12','venue13','venue14','venue15']);

echo "\n=== LOCATIONS ===\n";
importImages($pdo, TABLE_LOCATIONS, COL_LOCATIONS_ID, COL_LOCATIONS_IMMAGINE, $locationSeeds, 600, 400, $errors, $success);

// ============================================================
// MANIFESTAZIONI (5 manifestazioni)
// ============================================================
$manifSeeds = array_combine(range(1, 5), ['manif1','manif2','manif3','manif4','manif5']);

echo "\n=== MANIFESTAZIONI ===\n";
importImages($pdo, TABLE_MANIFESTAZIONI, COL_MANIFESTAZIONI_ID, COL_MANIFESTAZIONI_IMMAGINE, $manifSeeds, 600, 400, $errors, $success);

// ============================================================
// AVATAR UTENTI (4 utenti di test)
// ============================================================
$avatarSeeds = [
    1 => 'admin-avatar',
    2 => 'mod-avatar',
    3 => 'promoter-avatar',
    4 => 'user-avatar',
];

echo "\n=== AVATAR UTENTI ===\n";
foreach ($avatarSeeds as $id => $seed) {
    $url = "https://i.pravatar.cc/200?u={$seed}";
    $img = fetchImage($url);
    if ($img === null) {
        $errors[] = "Impossibile scaricare avatar per utente id={$id}";
        continue;
    }
    $stmt = $pdo->prepare("UPDATE " . TABLE_UTENTI . " SET " . COL_UTENTI_AVATAR . " = ? WHERE " . COL_UTENTI_ID . " = ?");
    $stmt->execute([$img, $id]);
    $success++;
    echo "✓ utente id={$id}\n";
    flush();
    usleep(200000);
}

// ============================================================
// RIEPILOGO
// ============================================================
echo "\n=== COMPLETATO ===\n";
echo "Immagini importate con successo: {$success}\n";
if (!empty($errors)) {
    echo "Errori (" . count($errors) . "):\n";
    foreach ($errors as $e) {
        echo "  ✗ {$e}\n";
    }
}
echo '</pre>';

echo '<p style="margin-top:1rem;"><strong>Passo successivo:</strong> esegui <code>mysqldump --hex-blob --complete-insert 5cit_eventsmaster &gt; db/dump_con_immagini.sql</code></p>';
