<?php
/**
 * Gestione variabili d'ambiente
 * Carica configurazioni dal file .env per separare i dati sensibili dal codice
 */

/**
 * Carica le variabili d'ambiente dal file .env
 * Ogni riga deve essere nel formato KEY=VALUE
 *
 * @param string $path Percorso assoluto del file .env
 */
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        die("File .env non trovato in: {$path}");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);

        if (strpos($line, '#') === 0) {
            continue;
        }

        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\' ');

            // Non sovrascrive variabili gia definite nel sistema
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}

/**
 * Recupera una variabile d'ambiente
 * Cerca prima in $_ENV, poi nelle variabili di sistema
 *
 * @param string $key Nome della variabile
 * @param mixed $default Valore di fallback se la variabile non esiste
 * @return mixed Valore della variabile o default
 */
function env(string $key, $default = null)
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

loadEnv(__DIR__ . '/../.env');
