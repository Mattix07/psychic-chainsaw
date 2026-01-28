<?php
/**
 * Controller Utente
 * Gestisce profilo, password, verifica email e eliminazione account
 *
 * Include funzionalita per:
 * - Visualizzazione e modifica profilo
 * - Cambio password con verifica password attuale
 * - Recupero password via email
 * - Verifica indirizzo email
 * - Eliminazione account con conferma
 */

require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/messages.php';
require_once __DIR__ . '/../lib/Validator.php';
require_once __DIR__ . '/../lib/QueryBuilder.php';
require_once __DIR__ . '/../models/Utente.php';

/**
 * Mostra la pagina profilo utente
 * Carica i dati aggiornati dal database
 */
function showProfilo(PDO $pdo): void
{
    if (!isLoggedIn()) {
        setErrorMessage(ERR_LOGIN_REQUIRED);
        header('Location: index.php?action=show_login');
        exit;
    }

    $user = getUtenteById($pdo, $_SESSION['user_id']);
    $_SESSION['user_data'] = $user;
    setPage('profilo');
}

/**
 * Aggiorna i dati anagrafici del profilo
 * Verifica unicita email se modificata
 */
function updateProfile(PDO $pdo): void
{
    if (!isLoggedIn()) {
        setErrorMessage(ERR_LOGIN_REQUIRED);
        header('Location: index.php?action=show_login');
        exit;
    }

    if (!verifyCsrf()) {
        setErrorMessage(ERR_INVALID_CSRF);
        header('Location: index.php?action=profilo');
        exit;
    }

    // Prepara i dati con trim
    $data = [
        'nome' => trim($_POST['nome'] ?? ''),
        'cognome' => trim($_POST['cognome'] ?? ''),
        'email' => trim($_POST['email'] ?? '')
    ];

    // Validazione con Validator
    $validator = validate($data)
        ->required('nome')
        ->required('cognome')
        ->required('email')
        ->email('email');

    if ($validator->fails()) {
        setErrorMessage($validator->firstError());
        header('Location: index.php?action=profilo');
        exit;
    }

    // Verifica che la nuova email non sia gia usata da altri utenti
    $existingUser = getUtenteByEmail($pdo, $data['email']);
    if ($existingUser && $existingUser['id'] !== $_SESSION['user_id']) {
        setErrorMessage(ERR_EMAIL_ALREADY_EXISTS);
        header('Location: index.php?action=profilo');
        exit;
    }

    $success = updateUtente($pdo, $_SESSION['user_id'], [
        'Nome' => $data['nome'],
        'Cognome' => $data['cognome'],
        'Email' => $data['email']
    ]);

    if ($success) {
        // Sincronizza i dati di sessione
        $_SESSION['user_nome'] = $data['nome'];
        $_SESSION['user_cognome'] = $data['cognome'];
        $_SESSION['user_email'] = $data['email'];
        setSuccessMessage(MSG_SUCCESS_PROFILE_UPDATE);
    } else {
        setErrorMessage(ERR_GENERIC);
    }

    header('Location: index.php?action=profilo');
    exit;
}

/**
 * Aggiorna la password utente
 * Richiede verifica della password attuale per sicurezza
 */
function updatePassword(PDO $pdo): void
{
    if (!isLoggedIn()) {
        setErrorMessage(ERR_LOGIN_REQUIRED);
        header('Location: index.php?action=show_login');
        exit;
    }

    if (!verifyCsrf()) {
        setErrorMessage(ERR_INVALID_CSRF);
        header('Location: index.php?action=cambia_password');
        exit;
    }

    $data = [
        'current_password' => $_POST['current_password'] ?? '',
        'new_password' => $_POST['new_password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? ''
    ];

    // Validazione con Validator
    $validator = validate($data)
        ->required('current_password')
        ->required('new_password')
        ->min('new_password', PASSWORD_MIN_LENGTH, message(ERR_PASSWORD_TOO_SHORT, PASSWORD_MIN_LENGTH))
        ->required('confirm_password')
        ->matches('new_password', 'confirm_password');

    if ($validator->fails()) {
        setErrorMessage($validator->firstError());
        header('Location: index.php?action=cambia_password');
        exit;
    }

    // Verifica password attuale prima di permettere il cambio
    $user = getUtenteById($pdo, $_SESSION['user_id']);
    if (!$user || !password_verify($data['current_password'], $user['Password'])) {
        setErrorMessage('La password attuale non è corretta.');
        header('Location: index.php?action=cambia_password');
        exit;
    }

    $success = updateUtentePassword($pdo, $_SESSION['user_id'], $data['new_password']);

    if ($success) {
        setSuccessMessage(MSG_SUCCESS_PASSWORD_UPDATE);
        header('Location: index.php?action=profilo');
    } else {
        setErrorMessage(ERR_GENERIC);
        header('Location: index.php?action=cambia_password');
    }
    exit;
}

/**
 * Elimina l'account utente
 * Richiede conferma esplicita e verifica password
 */
function deleteAccount(PDO $pdo): void
{
    if (!isLoggedIn()) {
        setErrorMessage(ERR_LOGIN_REQUIRED);
        header('Location: index.php?action=show_login');
        exit;
    }

    if (!verifyCsrf()) {
        setErrorMessage(ERR_INVALID_CSRF);
        header('Location: index.php?action=elimina_account');
        exit;
    }

    $password = $_POST['password'] ?? '';
    $confirmDelete = isset($_POST['confirm_delete']);

    // Richiede checkbox di conferma esplicita
    if (!$confirmDelete) {
        setErrorMessage('Devi confermare l\'eliminazione dell\'account.');
        header('Location: index.php?action=elimina_account');
        exit;
    }

    // Verifica password per prevenire eliminazioni accidentali
    $user = getUtenteById($pdo, $_SESSION['user_id']);
    if (!$user || !password_verify($password, $user['Password'])) {
        setErrorMessage('Password non corretta.');
        header('Location: index.php?action=elimina_account');
        exit;
    }

    $success = deleteUtente($pdo, $_SESSION['user_id']);

    if ($success) {
        // Termina la sessione dopo eliminazione
        session_destroy();
        session_start();
        setSuccessMessage('Account eliminato con successo.');
        header('Location: index.php');
    } else {
        setErrorMessage(ERR_GENERIC);
        header('Location: index.php?action=elimina_account');
    }
    exit;
}

// ==========================================
// RECUPERO PASSWORD
// ==========================================

/**
 * Invia email per reset password
 * Genera un token con scadenza e invia il link via email
 * Messaggio generico per non rivelare se l'email esiste
 */
function sendResetEmail(PDO $pdo): void
{
    require_once __DIR__ . '/../config/mail.php';

    if (!verifyCsrf()) {
        setErrorMessage(ERR_INVALID_CSRF);
        header('Location: index.php?action=recupera_password');
        exit;
    }

    $data = [
        'email' => trim($_POST['email'] ?? '')
    ];

    // Validazione con Validator
    $validator = validate($data)
        ->required('email')
        ->email('email');

    if ($validator->fails()) {
        setErrorMessage($validator->firstError());
        header('Location: index.php?action=recupera_password');
        exit;
    }

    $user = getUtenteByEmail($pdo, $data['email']);

    // Messaggio generico per sicurezza (non rivela se email esiste)
    setSuccessMessage(MSG_SUCCESS_RESET_EMAIL_SENT);

    if ($user) {
        $token = generateToken();
        setResetToken($pdo, $data['email'], $token);
        sendPasswordResetEmail($data['email'], $user['Nome'], $token);
    }

    header('Location: index.php?action=show_login');
    exit;
}

/**
 * Esegue il reset della password usando il token
 * Verifica validita token e aggiorna la password
 */
function doResetPassword(PDO $pdo): void
{
    if (!verifyCsrf()) {
        setErrorMessage(ERR_INVALID_CSRF);
        header('Location: index.php?action=show_login');
        exit;
    }

    $token = $_POST['token'] ?? '';

    if (empty($token)) {
        setErrorMessage(ERR_INVALID_TOKEN);
        header('Location: index.php?action=recupera_password');
        exit;
    }

    $data = [
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? ''
    ];

    // Validazione con Validator
    $validator = validate($data)
        ->required('password')
        ->min('password', PASSWORD_MIN_LENGTH, message(ERR_PASSWORD_TOO_SHORT, PASSWORD_MIN_LENGTH))
        ->required('password_confirm')
        ->matches('password', 'password_confirm');

    if ($validator->fails()) {
        setErrorMessage($validator->firstError());
        header('Location: index.php?action=reset_password&token=' . urlencode($token));
        exit;
    }

    $success = resetPasswordWithToken($pdo, $token, $data['password']);

    if ($success) {
        setSuccessMessage(MSG_SUCCESS_PASSWORD_RESET);
        header('Location: index.php?action=show_login');
    } else {
        setErrorMessage(ERR_INVALID_TOKEN);
        header('Location: index.php?action=recupera_password');
    }
    exit;
}

// ==========================================
// VERIFICA EMAIL
// ==========================================

/**
 * Verifica l'indirizzo email tramite token
 * Il token viene inviato via email durante la registrazione
 */
function verifyEmail(PDO $pdo): void
{
    $token = $_GET['token'] ?? '';

    if (empty($token)) {
        setErrorMessage(ERR_INVALID_TOKEN);
        header('Location: index.php');
        exit;
    }

    $user = verifyEmailToken($pdo, $token);

    if ($user) {
        markEmailVerified($pdo, $user['id']);
        setSuccessMessage(MSG_SUCCESS_EMAIL_VERIFIED);
    } else {
        setErrorMessage(ERR_INVALID_TOKEN);
    }

    header('Location: index.php?action=show_login');
    exit;
}

/**
 * Reinvia l'email di verifica
 * Utile se l'utente non ha ricevuto la prima email
 */
function resendVerification(PDO $pdo): void
{
    require_once __DIR__ . '/../config/mail.php';

    if (!isLoggedIn()) {
        setErrorMessage(ERR_LOGIN_REQUIRED);
        header('Location: index.php?action=show_login');
        exit;
    }

    $user = getUtenteById($pdo, $_SESSION['user_id']);

    if (!$user) {
        setErrorMessage('Utente non trovato.');
        header('Location: index.php');
        exit;
    }

    // Non reinviare se gia verificata
    if (!empty($user['verificato'])) {
        setSuccessMessage('La tua email è già verificata.');
        header('Location: index.php?action=profilo');
        exit;
    }

    $token = generateToken();
    setVerificationToken($pdo, $user['id'], $token);
    sendVerificationEmail($user['Email'], $user['Nome'], $token);

    setSuccessMessage('Email di verifica inviata. Controlla la tua casella di posta.');
    header('Location: index.php?action=profilo');
    exit;
}
