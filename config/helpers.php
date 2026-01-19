<?php
/**
 * Funzioni helper globali per EventsMaster
 * Raccolta di utility riutilizzabili in tutto il progetto
 */

/**
 * Escape di stringhe per output HTML sicuro
 * Previene attacchi XSS convertendo caratteri speciali in entita HTML
 *
 * @param string|null $string Stringa da sanificare
 * @return string Stringa sicura per output HTML
 */
function e(?string $string): string
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Reindirizza l'utente con messaggi flash opzionali
 * I messaggi vengono salvati in sessione e mostrati dopo il redirect
 *
 * @param string $location URL di destinazione
 * @param string|null $msg Messaggio di successo
 * @param string|null $error Messaggio di errore
 */
function redirect(string $location, ?string $msg = null, ?string $error = null): void
{
    if ($msg) $_SESSION['msg'] = $msg;
    if ($error) $_SESSION['error'] = $error;
    header("Location: {$location}");
    exit;
}

/**
 * Genera o recupera il token CSRF dalla sessione
 * Il token viene generato una sola volta per sessione per coerenza
 *
 * @return string Token CSRF di 64 caratteri esadecimali
 */
function generateCsrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica la validita del token CSRF
 * Usa hash_equals per prevenire timing attacks
 *
 * @param string $token Token da verificare
 * @return bool True se il token e valido
 */
function verifyCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Genera un campo input hidden con il token CSRF
 * Da includere in tutti i form POST
 *
 * @return string HTML del campo hidden
 */
function csrfField(): string
{
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Verifica se esiste una sessione utente attiva
 *
 * @return bool True se l'utente e autenticato
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Middleware: richiede autenticazione per accedere alla risorsa
 * Reindirizza al login se l'utente non e autenticato
 */
function requireAuth(): void
{
    if (!isLoggedIn()) {
        redirect('index.php', null, 'Accesso negato. Effettua il login.');
    }
}

/**
 * Middleware: richiede privilegi di amministratore
 * Reindirizza alla home se l'utente non ha i permessi
 */
function requireAdmin(): void
{
    if (!isAdmin()) {
        redirect('index.php', null, 'Accesso negato. Privilegi insufficienti.');
    }
}

/**
 * Sanifica una stringa rimuovendo tag HTML e spazi superflui
 * Da usare per input utente che non deve contenere markup
 *
 * @param string $string Input da sanificare
 * @return string Stringa pulita
 */
function sanitize(string $string): string
{
    return trim(strip_tags($string));
}

/**
 * Formatta un importo numerico in euro con separatori italiani
 *
 * @param float $price Importo da formattare
 * @return string Prezzo formattato (es. "25,50 €")
 */
function formatPrice(float $price): string
{
    return number_format($price, 2, ',', '.') . ' &euro;';
}

/**
 * Converte una data dal formato MySQL al formato italiano
 *
 * @param string $date Data in formato Y-m-d
 * @return string Data in formato d/m/Y
 */
function formatDate(string $date): string
{
    return date('d/m/Y', strtotime($date));
}

/**
 * Formatta un orario rimuovendo i secondi
 *
 * @param string $time Orario in formato H:i:s
 * @return string Orario in formato H:i
 */
function formatTime(string $time): string
{
    return date('H:i', strtotime($time));
}

/**
 * Funzione di debug: stampa variabili e termina l'esecuzione
 * Attiva solo in ambiente di sviluppo (APP_DEBUG=true)
 *
 * @param mixed ...$vars Variabili da ispezionare
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
 * Registra un messaggio di errore nel file di log
 * Crea automaticamente la directory logs se non esiste
 *
 * @param string $message Messaggio di errore
 * @param array $context Dati aggiuntivi da loggare in formato JSON
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
 * Calcola il prezzo finale di un biglietto
 * Applica il modificatore del tipo (VIP, Premium, ecc.) e il moltiplicatore del settore
 *
 * @param float $prezzoBase Prezzo base dell'evento
 * @param float $modificatoreTipo Sovrapprezzo per tipologia biglietto
 * @param float $moltiplicatoreSettore Coefficiente del settore (es. 1.5 per posti migliori)
 * @return float Prezzo finale calcolato
 */
function calcolaPrezzoBiglietto(float $prezzoBase, float $modificatoreTipo, float $moltiplicatoreSettore): float
{
    return ($prezzoBase + $modificatoreTipo) * $moltiplicatoreSettore;
}
