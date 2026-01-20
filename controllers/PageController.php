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
