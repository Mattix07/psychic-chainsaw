<?php
/**
 * Controller Autenticazione
 * Gestisce login, registrazione e logout degli utenti
 *
 * Implementa validazione input, verifica credenziali e gestione sessione.
 * Tutte le operazioni POST sono protette da token CSRF.
 */

require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/messages.php';
require_once __DIR__ . '/../lib/Validator.php';
require_once __DIR__ . '/../lib/QueryBuilder.php';
require_once __DIR__ . '/../models/Utente.php';
require_once __DIR__ . '/../config/mail.php';

/**
 * Router interno per le azioni di autenticazione
 *
 * @param string $action Azione da eseguire (login, register, logout)
 */
function handleAuth(PDO $pdo, string $action): void
{
    switch ($action) {
        case 'login':
            loginAction($pdo);
            break;

        case 'register':
            registerAction($pdo);
            break;

        case 'logout':
            logoutAction();
            break;
    }
}

/**
 * Gestisce il login utente
 * Verifica CSRF, valida email/password e crea la sessione
 */
function loginAction(PDO $pdo): void
{
    if (!verifyCsrf()) {
        setErrorMessage(ERR_INVALID_CSRF);
        redirect('index.php?action=show_login');
    }

    // Validazione con Validator
    $validator = validate($_POST)
        ->required('email')
        ->email('email')
        ->required('password');

    if ($validator->fails()) {
        setErrorMessage($validator->firstError());
        redirect('index.php?action=show_login');
    }

    $email = strtolower(trim(sanitize($_POST['email'])));
    $password = $_POST['password'];

    // Ricerca utente con QueryBuilder
    $utente = table($pdo, TABLE_UTENTI)
        ->where(COL_UTENTI_EMAIL, $email)
        ->first();

    if (!$utente) {
        logError("Login fallito: email non trovata - {$email}");
        setErrorMessage(ERR_INVALID_CREDENTIALS);
        redirect('index.php?action=show_login');
    }

    if (!password_verify($password, $utente['Password'])) {
        logError("Login fallito: password errata - {$email}");
        setErrorMessage(ERR_INVALID_CREDENTIALS);
        redirect('index.php?action=show_login');
    }

    // Salva dati utente in sessione
    $_SESSION['user_id'] = $utente['id'];
    $_SESSION['user_nome'] = $utente['Nome'];
    $_SESSION['user_cognome'] = $utente['Cognome'];
    $_SESSION['user_email'] = $utente['Email'];
    $_SESSION['page'] = 'home';

    logError("Login riuscito: {$email}");
    setSuccessMessage(message(MSG_SUCCESS_LOGIN, $utente['Nome']));
    redirect('index.php');
}

/**
 * Gestisce la registrazione nuovo utente
 * Valida tutti i campi e verifica unicita email
 */
function registerAction(PDO $pdo): void
{
    if (!verifyCsrf()) {
        setErrorMessage(ERR_INVALID_CSRF);
        redirect('index.php?action=show_register');
    }

    // Validazione completa con Validator
    $validator = validate($_POST)
        ->required('nome')
        ->required('cognome')
        ->required('email')
        ->email('email')
        ->required('password')
        ->min('password', PASSWORD_MIN_LENGTH, message(ERR_PASSWORD_TOO_SHORT, PASSWORD_MIN_LENGTH))
        ->required('password_confirm')
        ->matches('password', 'password_confirm');

    if ($validator->fails()) {
        setErrorMessage($validator->firstError());
        redirect('index.php?action=show_register');
    }

    $nome = sanitize($_POST['nome']);
    $cognome = sanitize($_POST['cognome']);
    $email = strtolower(trim(sanitize($_POST['email'])));

    // Verifica email non gia registrata con QueryBuilder
    $existingUser = table($pdo, TABLE_UTENTI)
        ->where(COL_UTENTI_EMAIL, $email)
        ->exists();

    if ($existingUser) {
        setErrorMessage(ERR_EMAIL_ALREADY_EXISTS);
        redirect('index.php?action=show_register');
    }

    try {
        $id = createUtente($pdo, [
            'Nome' => $nome,
            'Cognome' => $cognome,
            'Email' => $email,
            'Password' => password_hash($_POST['password'], PASSWORD_DEFAULT)
        ]);

        // Genera token di verifica e invia email
        $token = generateToken();
        setVerificationToken($pdo, $id, $token);
        sendVerificationEmail($email, $nome, $token);

        logError("Nuovo utente registrato: {$email} (ID: {$id})");
        setSuccessMessage(MSG_SUCCESS_REGISTER);
        redirect('index.php?action=show_login');

    } catch (Exception $e) {
        logError("Errore registrazione: " . $e->getMessage());
        setErrorMessage(ERR_GENERIC);
        redirect('index.php?action=show_register');
    }
}

/**
 * Gestisce il logout utente
 * Distrugge la sessione e ne crea una nuova pulita
 */
function logoutAction(): void
{
    session_destroy();
    session_start();
    setSuccessMessage(MSG_SUCCESS_LOGOUT);
    redirect('index.php');
}
