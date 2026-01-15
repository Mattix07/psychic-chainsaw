<?php

/**
 * Controller per l'autenticazione
 */

require_once __DIR__ . '/../models/Utente.php';

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

function loginAction(PDO $pdo): void
{
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        redirect('index.php?action=show_login', null, 'Richiesta non valida');
    }

    $email = strtolower(trim(sanitize($_POST['email'] ?? '')));
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        redirect('index.php?action=show_login', null, 'Email e password sono obbligatori');
    }

    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        redirect('index.php?action=show_login', null, 'Email non valida');
    }

    // Cerca utente
    $utente = getUtenteByEmail($pdo, $email);

    if (!$utente) {
        logError("Login fallito: email non trovata - {$email}");
        redirect('index.php?action=show_login', null, 'Credenziali non valide');
    }

    // Nota: In una versione completa dovresti verificare la password con password_verify()
    // Per ora simuliamo il login

    // Login riuscito
    $_SESSION['user_id'] = $utente['id'];
    $_SESSION['user_nome'] = $utente['Nome'];
    $_SESSION['user_cognome'] = $utente['Cognome'];
    $_SESSION['user_email'] = $utente['Email'];
    $_SESSION['page'] = 'home';

    logError("Login riuscito: {$email}");
    redirect('index.php', 'Benvenuto, ' . $utente['Nome'] . '!');
}

function registerAction(PDO $pdo): void
{
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        redirect('index.php?action=show_register', null, 'Richiesta non valida');
    }

    $nome = sanitize($_POST['nome'] ?? '');
    $cognome = sanitize($_POST['cognome'] ?? '');
    $email = strtolower(trim(sanitize($_POST['email'] ?? '')));
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    // Validazioni
    if (empty($nome) || empty($cognome) || empty($email) || empty($password)) {
        redirect('index.php?action=show_register', null, 'Tutti i campi sono obbligatori');
    }

    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        redirect('index.php?action=show_register', null, 'Email non valida');
    }

    if (strlen($password) < 6) {
        redirect('index.php?action=show_register', null, 'La password deve essere di almeno 6 caratteri');
    }

    if ($password !== $passwordConfirm) {
        redirect('index.php?action=show_register', null, 'Le password non coincidono');
    }

    // Verifica email esistente
    if (getUtenteByEmail($pdo, $email)) {
        redirect('index.php?action=show_register', null, 'Email gia registrata');
    }

    try {
        $id = createUtente($pdo, [
            'Nome' => $nome,
            'Cognome' => $cognome,
            'Email' => $email
        ]);

        logError("Nuovo utente registrato: {$email} (ID: {$id})");
        redirect('index.php?action=show_login', 'Registrazione completata! Ora puoi accedere.');

    } catch (Exception $e) {
        logError("Errore registrazione: " . $e->getMessage());
        redirect('index.php?action=show_register', null, 'Errore durante la registrazione');
    }
}

function logoutAction(): void
{
    session_destroy();
    session_start();
    redirect('index.php', 'Logout effettuato con successo');
}
