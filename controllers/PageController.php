<?php

/**
 * Controller per la gestione delle pagine
 */

function setPage(string $page): void
{
    $_SESSION['page'] = $page;
}

function getCurrentPage(): string
{
    return $_SESSION['page'] ?? 'home';
}
