<?php

/**
 * Carica variabili d'ambiente dal file .env
 */
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        die("File .env non trovato in: {$path}");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);

        // Salta commenti
        if (strpos($line, '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Rimuovi virgolette se presenti
            $value = trim($value, '"\'');

            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}

/**
 * Ottiene una variabile d'ambiente
 */
function env(string $key, $default = null)
{
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// Carica il file .env
loadEnv(__DIR__ . '/../.env');
