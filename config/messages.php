<?php
/**
 * Messaggi dell'Applicazione
 *
 * Centralizza tutti i messaggi di errore e successo.
 * Utile per multi-lingua e consistenza.
 */

// ============================================================
// MESSAGGI DI SUCCESSO
// ============================================================

define('MSG_SUCCESS_LOGIN', 'Benvenuto, %s!');
define('MSG_SUCCESS_LOGOUT', 'Logout effettuato con successo');
define('MSG_SUCCESS_REGISTER', 'Registrazione completata! Ora puoi accedere.');
define('MSG_SUCCESS_PROFILE_UPDATE', 'Profilo aggiornato con successo.');
define('MSG_SUCCESS_PASSWORD_UPDATE', 'Password aggiornata con successo.');
define('MSG_SUCCESS_EMAIL_VERIFIED', 'Email verificata con successo!');
define('MSG_SUCCESS_RESET_EMAIL_SENT', 'Se l\'email è registrata, riceverai le istruzioni per reimpostare la password.');
define('MSG_SUCCESS_PASSWORD_RESET', 'Password reimpostata con successo. Puoi ora effettuare il login.');

// Eventi
define('MSG_SUCCESS_EVENT_CREATED', 'Evento creato con successo.');
define('MSG_SUCCESS_EVENT_UPDATED', 'Evento modificato con successo.');
define('MSG_SUCCESS_EVENT_DELETED', 'Evento eliminato con successo.');

// Biglietti
define('MSG_SUCCESS_TICKET_ADDED', '%d bigliett%s aggiunt%s al carrello');
define('MSG_SUCCESS_TICKET_REMOVED', 'Biglietto rimosso dal carrello');
define('MSG_SUCCESS_CART_CLEARED', 'Carrello svuotato');
define('MSG_SUCCESS_PURCHASE', 'Acquisto completato con successo!');
define('MSG_SUCCESS_TICKET_VALIDATED', 'Biglietto validato con successo');

// Location
define('MSG_SUCCESS_LOCATION_CREATED', 'Location creata con successo.');
define('MSG_SUCCESS_LOCATION_UPDATED', 'Location modificata con successo.');
define('MSG_SUCCESS_LOCATION_DELETED', 'Location eliminata con successo.');

// Recensioni
define('MSG_SUCCESS_REVIEW_CREATED', 'Recensione pubblicata con successo.');
define('MSG_SUCCESS_REVIEW_UPDATED', 'Recensione modificata con successo.');
define('MSG_SUCCESS_REVIEW_DELETED', 'Recensione eliminata con successo.');

// Admin
define('MSG_SUCCESS_USER_ROLE_UPDATED', 'Ruolo aggiornato con successo.');
define('MSG_SUCCESS_USER_DELETED', 'Utente eliminato con successo.');
define('MSG_SUCCESS_USER_VERIFIED', 'Account verificato');

// Manifestazioni
define('MSG_SUCCESS_MANIFESTATION_CREATED', 'Manifestazione creata con successo.');
define('MSG_SUCCESS_MANIFESTATION_UPDATED', 'Manifestazione modificata con successo.');
define('MSG_SUCCESS_MANIFESTATION_DELETED', 'Manifestazione eliminata con successo.');

// Avatar
define('MSG_SUCCESS_AVATAR_UPDATED', 'Avatar aggiornato con successo');
define('MSG_SUCCESS_AVATAR_DELETED', 'Avatar eliminato');

// ============================================================
// MESSAGGI DI ERRORE
// ============================================================

// Autenticazione
define('ERR_INVALID_CREDENTIALS', 'Credenziali non valide');
define('ERR_LOGIN_REQUIRED', 'Devi effettuare il login.');
define('ERR_PERMISSION_DENIED', 'Non hai i permessi per accedere a questa area.');
define('ERR_INVALID_TOKEN', 'Token non valido o scaduto.');
define('ERR_INVALID_CSRF', 'Token di sicurezza non valido.');

// Validazione
define('ERR_REQUIRED_FIELDS', 'Tutti i campi sono obbligatori');
define('ERR_INVALID_EMAIL', 'Email non valida');
define('ERR_EMAIL_ALREADY_EXISTS', 'Email già registrata');
define('ERR_PASSWORD_TOO_SHORT', 'La password deve essere di almeno %d caratteri');
define('ERR_PASSWORD_MISMATCH', 'Le password non coincidono');
define('ERR_INVALID_DATE', 'Data non valida');
define('ERR_INVALID_AMOUNT', 'Importo non valido');

// Biglietti
define('ERR_TICKETS_NOT_AVAILABLE', 'Biglietti non disponibili');
define('ERR_TICKET_NOT_FOUND', 'Biglietto non trovato');
define('ERR_MAX_TICKETS_EXCEEDED', 'Puoi acquistare massimo %d biglietti per ordine');
define('ERR_TICKET_ALREADY_VALIDATED', 'Biglietto già validato');
define('ERR_EMPTY_CART', 'Il carrello è vuoto');

// Eventi
define('ERR_EVENT_NOT_FOUND', 'Evento non trovato');
define('ERR_EVENT_EXPIRED', 'L\'evento è già passato');
define('ERR_EVENT_FULL', 'L\'evento è al completo');

// Recensioni
define('ERR_ALREADY_REVIEWED', 'Hai già recensito questo evento');
define('ERR_REVIEW_NOT_ALLOWED', 'Non puoi recensire questo evento');
define('ERR_REVIEW_NOT_FOUND', 'Recensione non trovata');
define('ERR_MUST_ATTEND', 'Puoi recensire solo eventi a cui hai partecipato');
define('ERR_INVALID_VOTE', 'Il voto deve essere tra %d e %d');

// Location & Manifestazioni
define('ERR_LOCATION_HAS_EVENTS', 'Impossibile eliminare: ci sono eventi associati');
define('ERR_MANIFESTATION_HAS_EVENTS', 'Impossibile eliminare: ci sono eventi associati');

// Upload
define('ERR_UPLOAD_FAILED', 'Errore durante l\'upload del file');
define('ERR_INVALID_FILE_TYPE', 'Formato file non valido');
define('ERR_INVALID_IMAGE', 'File non valido');

// Admin
define('ERR_CANNOT_MODIFY_SELF', 'Non puoi modificare il tuo stesso ruolo');
define('ERR_CANNOT_DELETE_SELF', 'Non puoi eliminare il tuo stesso account');

// Biglietti
define('ERR_ORGANIZERS_CANNOT_PURCHASE', 'Gli organizzatori non possono acquistare biglietti');

// Sistema
define('ERR_GENERIC', 'Si è verificato un errore. Riprova più tardi.');
define('ERR_DATABASE', 'Errore di connessione al database');
define('ERR_FILE_UPLOAD', 'Errore durante il caricamento del file');
define('ERR_FILE_TOO_LARGE', 'Il file è troppo grande (max %sMB)');
define('ERR_FILE_INVALID_TYPE', 'Tipo di file non valido');

// ============================================================
// FUNZIONI HELPER PER MESSAGGI
// ============================================================

/**
 * Formatta un messaggio con parametri
 */
if (!function_exists('message')) {
    function message(string $template, ...$args): string
    {
        return sprintf($template, ...$args);
    }
}

/**
 * Imposta un messaggio di successo nella sessione
 */
if (!function_exists('setSuccessMessage')) {
    function setSuccessMessage(string $message): void
    {
        $_SESSION['msg'] = $message;
    }
}

/**
 * Imposta un messaggio di errore nella sessione
 */
if (!function_exists('setErrorMessage')) {
    function setErrorMessage(string $message): void
    {
        $_SESSION['error'] = $message;
    }
}

/**
 * Recupera e rimuove il messaggio di successo dalla sessione
 */
if (!function_exists('getSuccessMessage')) {
    function getSuccessMessage(): ?string
    {
        $msg = $_SESSION['msg'] ?? null;
        unset($_SESSION['msg']);
        return $msg;
    }
}

/**
 * Recupera e rimuove il messaggio di errore dalla sessione
 */
if (!function_exists('getErrorMessage')) {
    function getErrorMessage(): ?string
    {
        $error = $_SESSION['error'] ?? null;
        unset($_SESSION['error']);
        return $error;
    }
}

/**
 * Verifica se c'è un messaggio di successo
 */
if (!function_exists('hasSuccessMessage')) {
    function hasSuccessMessage(): bool
    {
        return isset($_SESSION['msg']);
    }
}

/**
 * Verifica se c'è un messaggio di errore
 */
if (!function_exists('hasErrorMessage')) {
    function hasErrorMessage(): bool
    {
        return isset($_SESSION['error']);
    }
}

// ============================================================
// RESPONSE TEMPLATES PER API
// ============================================================

/**
 * Risposta JSON di successo standardizzata
 */
if (!function_exists('apiSuccess')) {
    function apiSuccess($data = null, string $message = null, int $code = 200): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
    }
}

/**
 * Risposta JSON di errore standardizzata
 */
if (!function_exists('apiError')) {
    function apiError(string $message, int $code = 400, $errors = null): array
    {
        return [
            'success' => false,
            'error' => $message,
            'errors' => $errors
        ];
    }
}

/**
 * Risposta di validazione fallita
 */
if (!function_exists('apiValidationError')) {
    function apiValidationError(array $errors, string $message = 'Errori di validazione'): array
    {
        return apiError($message, 422, $errors);
    }
}

/**
 * Risposta di autenticazione fallita
 */
if (!function_exists('apiUnauthorized')) {
    function apiUnauthorized(string $message = null): array
    {
        return apiError($message ?? ERR_LOGIN_REQUIRED, 401);
    }
}

/**
 * Risposta di permessi insufficienti
 */
if (!function_exists('apiForbidden')) {
    function apiForbidden(string $message = null): array
    {
        return apiError($message ?? ERR_PERMISSION_DENIED, 403);
    }
}

/**
 * Risposta di risorsa non trovata
 */
if (!function_exists('apiNotFound')) {
    function apiNotFound(string $message = 'Risorsa non trovata'): array
    {
        return apiError($message, 404);
    }
}
