<?php
/**
 * Application Configuration
 *
 * Centralizza tutte le configurazioni dell'applicazione.
 * Simile a .env ma per costanti PHP.
 */

// Carica prima database_schema.php per avere accesso alle costanti RUOLO_*
require_once __DIR__ . '/database_schema.php';

// ============================================================
// CONFIGURAZIONE APPLICAZIONE
// ============================================================

define('APP_NAME', 'EventsMaster');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development'); // development, staging, production

// ============================================================
// URL E PATH
// ============================================================

define('BASE_URL', 'http://localhost/eventsMaster');
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOAD_PATH', __DIR__ . '/../uploads');
define('LOGS_PATH', __DIR__ . '/../logs');

// ============================================================
// SESSIONE
// ============================================================

define('SESSION_LIFETIME', 3600 * 2); // 2 ore
define('SESSION_NAME', 'eventsmaster_session');
define('CSRF_TOKEN_NAME', '_csrf_token');

// ============================================================
// CARRELLO
// ============================================================

define('CART_EXPIRATION_HOURS', 24); // Biglietti nel carrello scadono dopo 24h
define('MAX_TICKETS_PER_ORDER', 10); // Massimo biglietti per ordine
define('CART_SESSION_KEY', 'cart_items');

// ============================================================
// BIGLIETTI
// ============================================================

define('QRCODE_LENGTH', 32); // Lunghezza codice QR
define('TICKET_PDF_ENABLED', true); // Abilita generazione PDF
define('TICKET_VALIDATION_WINDOW_HOURS', 2); // Finestra validazione pre-evento

// ============================================================
// PAGAMENTI
// ============================================================

define('PAYMENT_METHODS', ['Carta di credito', 'PayPal', 'Bonifico']);
define('DEFAULT_PAYMENT_METHOD', 'Carta di credito');

// ============================================================
// RUOLI (Alias per compatibilità)
// ============================================================
// Le costanti principali sono in database_schema.php (RUOLO_*)
// Questi sono alias per il codice che usa ROLE_*

if (!defined('ROLE_USER')) define('ROLE_USER', RUOLO_USER);
if (!defined('ROLE_PROMOTER')) define('ROLE_PROMOTER', RUOLO_PROMOTER);
if (!defined('ROLE_MOD')) define('ROLE_MOD', RUOLO_MOD);
if (!defined('ROLE_ADMIN')) define('ROLE_ADMIN', RUOLO_ADMIN);

// ============================================================
// EMAIL
// ============================================================

define('EMAIL_FROM', 'noreply@eventsmaster.com');
define('EMAIL_FROM_NAME', APP_NAME);
define('EMAIL_ENABLED', true); // Disabilita in dev se necessario
define('EMAIL_QUEUE_ENABLED', false); // Per implementazione futura

// ============================================================
// UPLOAD E FILE
// ============================================================

define('AVATAR_MAX_SIZE', 2 * 1024 * 1024); // 2MB
define('AVATAR_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/jpg']);
define('AVATAR_MAX_DIMENSION', 1024); // Pixel
define('AVATAR_CACHE_DURATION', 86400); // 1 giorno in secondi
define('DEFAULT_AVATAR_PATH', 'public/img/default-avatar.png');
define('EVENT_IMAGE_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('EVENT_IMAGE_ALLOWED_TYPES', ['image/jpeg', 'image/png']);

// ============================================================
// PAGINAZIONE
// ============================================================

define('ITEMS_PER_PAGE', 20);
define('EVENTS_PER_PAGE', 12);
define('ORDERS_PER_PAGE', 10);

// ============================================================
// RICERCA
// ============================================================

define('SEARCH_MIN_LENGTH', 3); // Minimo caratteri per ricerca
define('SEARCH_MAX_RESULTS', 50); // Massimo risultati

// ============================================================
// RECENSIONI
// ============================================================

define('REVIEW_MIN_VOTE', 1);
define('REVIEW_MAX_VOTE', 5);
define('REVIEW_EDIT_WINDOW_HOURS', 24); // Tempo per modificare recensione
define('REVIEW_VISIBILITY_DAYS', 14); // Giorni visibilità dopo evento

// ============================================================
// SICUREZZA
// ============================================================

define('PASSWORD_MIN_LENGTH', 6);
define('PASSWORD_REQUIRE_UPPERCASE', false);
define('PASSWORD_REQUIRE_NUMBERS', false);
define('PASSWORD_REQUIRE_SPECIAL', false);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);

// ============================================================
// TOKEN
// ============================================================

define('RESET_TOKEN_LENGTH', 32);
define('RESET_TOKEN_EXPIRY_HOURS', 1);
define('VERIFICATION_TOKEN_LENGTH', 32);
define('VERIFICATION_TOKEN_EXPIRY_HOURS', 24);
define('COLLABORATION_TOKEN_LENGTH', 32);

// ============================================================
// CACHE
// ============================================================

define('CACHE_ENABLED', false); // Per implementazione futura
define('CACHE_TTL', 3600); // 1 ora

// ============================================================
// DEBUG E LOGGING
// ============================================================

define('DEBUG_MODE', APP_ENV === 'development');
define('LOG_ERRORS', true);
define('LOG_QUERIES', DEBUG_MODE); // Log query SQL in dev
define('SHOW_ERRORS', DEBUG_MODE);

// Configurazione error reporting
if (SHOW_ERRORS) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ============================================================
// FORMATI DATA/ORA
// ============================================================

define('DATE_FORMAT', 'd/m/Y');
define('TIME_FORMAT', 'H:i');
define('DATETIME_FORMAT', 'd/m/Y H:i');
define('DB_DATE_FORMAT', 'Y-m-d');
define('DB_TIME_FORMAT', 'H:i:s');
define('DB_DATETIME_FORMAT', 'Y-m-d H:i:s');

// ============================================================
// TIMEZONE
// ============================================================

date_default_timezone_set('Europe/Rome');

// ============================================================
// HELPER FUNCTIONS
// ============================================================
// Nota: formatDate() e formatTime() sono già definite in helpers.php
// Queste funzioni aggiuntive estendono le funzionalità base

/**
 * Formatta una data/ora dal formato DB a quello visualizzato
 */
if (!function_exists('formatDateTime')) {
    function formatDateTime(?string $datetime): string
    {
        if (!$datetime) return '';
        return date(DATETIME_FORMAT, strtotime($datetime));
    }
}

/**
 * Converte una data dal formato visualizzato al formato DB
 */
if (!function_exists('toDbDate')) {
    function toDbDate(?string $date): ?string
    {
        if (!$date) return null;
        return date(DB_DATE_FORMAT, strtotime($date));
    }
}

/**
 * Restituisce l'URL completo di una risorsa
 */
if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return ASSETS_URL . '/' . ltrim($path, '/');
    }
}

/**
 * Restituisce l'URL completo di una pagina
 */
if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return BASE_URL . '/' . ltrim($path, '/');
    }
}

/**
 * Verifica se siamo in modalità debug
 */
if (!function_exists('isDebug')) {
    function isDebug(): bool
    {
        return DEBUG_MODE;
    }
}

/**
 * Verifica se siamo in produzione
 */
if (!function_exists('isProduction')) {
    function isProduction(): bool
    {
        return APP_ENV === 'production';
    }
}
