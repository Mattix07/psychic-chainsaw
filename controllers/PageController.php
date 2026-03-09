<?php
/**
 * Controller Pagine
 * Gestisce la navigazione tra le pagine dell'applicazione
 *
 * Utilizza la sessione per memorizzare la pagina corrente,
 * che viene poi caricata dal layout principale.
 */

/**
 * Imposta la pagina da visualizzare
 * Il nome corrisponde al file in views/ senza estensione
 *
 * @param string $page Nome della pagina (es. 'home', 'evento_dettaglio')
 */
function setPage(string $page): void
{
    $_SESSION['page'] = $page;
}

/**
 * Recupera la pagina corrente dalla sessione
 *
 * @return string Nome pagina, default 'home'
 */
function getCurrentPage(): string
{
    return $_SESSION['page'] ?? 'home';
}

/**
 * Imposta i meta tag SEO per la pagina corrente.
 * Deve essere chiamato prima di require 'views/layouts/main.php'.
 *
 * @param string      $title       Titolo pagina (senza il suffisso del sito)
 * @param string      $description Meta description (max 160 caratteri)
 * @param string      $robots      Valore meta robots (es. 'noindex,nofollow')
 * @param string|null $canonical   URL canonico assoluto (null = nessun tag)
 */
function setSeoMeta(string $title, string $description = '', string $robots = 'index,follow', ?string $canonical = null): void
{
    $_SESSION['seo_title']       = $title;
    $_SESSION['seo_description'] = $description;
    $_SESSION['seo_robots']      = $robots;
    $_SESSION['seo_canonical']   = $canonical;
}
