<?php
/**
 * Sitemap XML dinamica
 * Genera una sitemap aggiornata leggendo gli eventi dal database.
 * Usa BASE_URL da app_config per il dominio corretto.
 */

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app_config.php';
require_once __DIR__ . '/models/Evento.php';

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

$base = rtrim(BASE_URL, '/');
$now  = date('c');

$eventi = getAllEventi($pdo);

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Homepage
echo "<url><loc>{$base}/</loc><lastmod>{$now}</lastmod><priority>1.00</priority></url>\n";

// Lista eventi
echo "<url><loc>{$base}/index.php?action=list_eventi</loc><lastmod>{$now}</lastmod><priority>0.90</priority></url>\n";

// Categorie
foreach (['concerti', 'teatro', 'sport', 'comedy', 'cinema', 'famiglia'] as $cat) {
    $loc = htmlspecialchars("{$base}/index.php?action=category&cat={$cat}", ENT_XML1);
    echo "<url><loc>{$loc}</loc><lastmod>{$now}</lastmod><priority>0.70</priority></url>\n";
}

// Singoli eventi
foreach ($eventi as $evento) {
    $id      = (int) $evento['id'];
    $lastmod = !empty($evento['Data']) ? date('c', strtotime($evento['Data'])) : $now;
    $loc     = htmlspecialchars("{$base}/index.php?action=view_evento&id={$id}", ENT_XML1);
    echo "<url><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod><priority>0.80</priority></url>\n";
}

echo '</urlset>';
