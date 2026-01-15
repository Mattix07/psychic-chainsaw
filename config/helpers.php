<?php

/**
 * Helper functions globali per EventsMaster
 */

/**
 * Escaping output HTML
 */
function e(?string $string): string
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect helper
 */
function redirect(string $location, ?string $msg = null, ?string $error = null): void
{
    if ($msg) $_SESSION['msg'] = $msg;
    if ($error) $_SESSION['error'] = $error;
    header("Location: {$location}");
    exit;
}

/**
 * Genera CSRF token
 */
function generateCsrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica CSRF token
 */
function verifyCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Genera campo hidden CSRF
 */
function csrfField(): string
{
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Verifica se l'utente e' autenticato
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Verifica se l'utente e' admin
 */
function isAdmin(): bool
{
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Richiede autenticazione
 */
function requireAuth(): void
{
    if (!isLoggedIn()) {
        redirect('index.php', null, 'Accesso negato. Effettua il login.');
    }
}

/**
 * Richiede privilegi admin
 */
function requireAdmin(): void
{
    if (!isAdmin()) {
        redirect('index.php', null, 'Accesso negato. Privilegi insufficienti.');
    }
}

/**
 * Sanitizza stringa
 */
function sanitize(string $string): string
{
    return trim(strip_tags($string));
}

/**
 * Formatta prezzo in euro
 */
function formatPrice(float $price): string
{
    return number_format($price, 2, ',', '.') . ' &euro;';
}

/**
 * Formatta data in italiano
 */
function formatDate(string $date): string
{
    return date('d/m/Y', strtotime($date));
}

/**
 * Formatta ora
 */
function formatTime(string $time): string
{
    return date('H:i', strtotime($time));
}

/**
 * Debug helper (solo in sviluppo)
 */
function dd(...$vars): void
{
    if (env('APP_DEBUG', false)) {
        echo '<pre>';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
        die();
    }
}

/**
 * Log errori
 */
function logError(string $message, array $context = []): void
{
    $logFile = __DIR__ . '/../logs/error.log';
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context) : '';
    $logMessage = "[{$timestamp}] {$message} {$contextStr}" . PHP_EOL;

    error_log($logMessage, 3, $logFile);
}

/**
 * Calcola prezzo finale biglietto
 * Formula: PrezzoBase + ModificatoreTipo * MoltiplicatoreSettore
 */
function calcolaPrezzoBiglietto(float $prezzoBase, float $modificatoreTipo, float $moltiplicatoreSettore): float
{
    return ($prezzoBase + $modificatoreTipo) * $moltiplicatoreSettore;
}
