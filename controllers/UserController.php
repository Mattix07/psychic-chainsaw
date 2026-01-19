<?php
/**
 * Controller per la gestione del profilo utente
 */

require_once __DIR__ . '/../models/Utente.php';

function showProfilo(PDO $pdo): void
{
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Devi effettuare il login.';
        header('Location: index.php?action=show_login');
        exit;
    }

    // Carica dati utente
    $user = getUtenteById($pdo, $_SESSION['user_id']);
    $_SESSION['user_data'] = $user;
    setPage('profilo');
}

function updateProfile(PDO $pdo): void
{
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Devi effettuare il login.';
        header('Location: index.php?action=show_login');
        exit;
    }

    if (!verifyCsrf()) {
        $_SESSION['error'] = 'Token di sicurezza non valido.';
        header('Location: index.php?action=profilo');
        exit;
    }

    $nome = trim($_POST['nome'] ?? '');
    $cognome = trim($_POST['cognome'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($nome) || empty($cognome) || empty($email)) {
        $_SESSION['error'] = 'Tutti i campi sono obbligatori.';
        header('Location: index.php?action=profilo');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Email non valida.';
        header('Location: index.php?action=profilo');
        exit;
    }

    // Verifica che l'email non sia già usata da altri
    $existingUser = getUtenteByEmail($pdo, $email);
    if ($existingUser && $existingUser['id'] !== $_SESSION['user_id']) {
        $_SESSION['error'] = 'Questa email è già registrata.';
        header('Location: index.php?action=profilo');
        exit;
    }

    $success = updateUtente($pdo, $_SESSION['user_id'], [
        'Nome' => $nome,
        'Cognome' => $cognome,
        'Email' => $email
    ]);

    if ($success) {
        // Aggiorna la sessione
        $_SESSION['user_nome'] = $nome;
        $_SESSION['user_cognome'] = $cognome;
        $_SESSION['user_email'] = $email;
        $_SESSION['msg'] = 'Profilo aggiornato con successo.';
    } else {
        $_SESSION['error'] = 'Errore durante l\'aggiornamento del profilo.';
    }

    header('Location: index.php?action=profilo');
    exit;
}

function updatePassword(PDO $pdo): void
{
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Devi effettuare il login.';
        header('Location: index.php?action=show_login');
        exit;
    }

    if (!verifyCsrf()) {
        $_SESSION['error'] = 'Token di sicurezza non valido.';
        header('Location: index.php?action=cambia_password');
        exit;
    }

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $_SESSION['error'] = 'Tutti i campi sono obbligatori.';
        header('Location: index.php?action=cambia_password');
        exit;
    }

    if (strlen($newPassword) < 6) {
        $_SESSION['error'] = 'La nuova password deve essere di almeno 6 caratteri.';
        header('Location: index.php?action=cambia_password');
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        $_SESSION['error'] = 'Le password non coincidono.';
        header('Location: index.php?action=cambia_password');
        exit;
    }

    // Verifica la password attuale
    $user = getUtenteById($pdo, $_SESSION['user_id']);
    if (!$user || !password_verify($currentPassword, $user['Password'])) {
        $_SESSION['error'] = 'La password attuale non è corretta.';
        header('Location: index.php?action=cambia_password');
        exit;
    }

    // Aggiorna la password
    $success = updateUtentePassword($pdo, $_SESSION['user_id'], $newPassword);

    if ($success) {
        $_SESSION['msg'] = 'Password aggiornata con successo.';
        header('Location: index.php?action=profilo');
    } else {
        $_SESSION['error'] = 'Errore durante l\'aggiornamento della password.';
        header('Location: index.php?action=cambia_password');
    }
    exit;
}

function deleteAccount(PDO $pdo): void
{
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Devi effettuare il login.';
        header('Location: index.php?action=show_login');
        exit;
    }

    if (!verifyCsrf()) {
        $_SESSION['error'] = 'Token di sicurezza non valido.';
        header('Location: index.php?action=elimina_account');
        exit;
    }

    $password = $_POST['password'] ?? '';
    $confirmDelete = isset($_POST['confirm_delete']);

    if (!$confirmDelete) {
        $_SESSION['error'] = 'Devi confermare l\'eliminazione dell\'account.';
        header('Location: index.php?action=elimina_account');
        exit;
    }

    // Verifica la password
    $user = getUtenteById($pdo, $_SESSION['user_id']);
    if (!$user || !password_verify($password, $user['Password'])) {
        $_SESSION['error'] = 'Password non corretta.';
        header('Location: index.php?action=elimina_account');
        exit;
    }

    // Elimina l'account
    $success = deleteUtente($pdo, $_SESSION['user_id']);

    if ($success) {
        // Distruggi la sessione
        session_destroy();
        session_start();
        $_SESSION['msg'] = 'Account eliminato con successo.';
        header('Location: index.php');
    } else {
        $_SESSION['error'] = 'Errore durante l\'eliminazione dell\'account.';
        header('Location: index.php?action=elimina_account');
    }
    exit;
}

// ==========================================
// PASSWORD RECOVERY
// ==========================================

function sendResetEmail(PDO $pdo): void
{
    require_once __DIR__ . '/../config/mail.php';

    if (!verifyCsrf()) {
        $_SESSION['error'] = 'Token di sicurezza non valido.';
        header('Location: index.php?action=recupera_password');
        exit;
    }

    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Inserisci un indirizzo email valido.';
        header('Location: index.php?action=recupera_password');
        exit;
    }

    // Verifica se l'utente esiste
    $user = getUtenteByEmail($pdo, $email);

    // Mostra sempre lo stesso messaggio per sicurezza
    $_SESSION['msg'] = 'Se l\'email è registrata, riceverai le istruzioni per reimpostare la password.';

    if ($user) {
        $token = generateToken();
        setResetToken($pdo, $email, $token);
        sendPasswordResetEmail($email, $user['Nome'], $token);
    }

    header('Location: index.php?action=show_login');
    exit;
}

function doResetPassword(PDO $pdo): void
{
    if (!verifyCsrf()) {
        $_SESSION['error'] = 'Token di sicurezza non valido.';
        header('Location: index.php?action=show_login');
        exit;
    }

    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (empty($token)) {
        $_SESSION['error'] = 'Token non valido o scaduto.';
        header('Location: index.php?action=recupera_password');
        exit;
    }

    if (strlen($password) < 6) {
        $_SESSION['error'] = 'La password deve essere di almeno 6 caratteri.';
        header('Location: index.php?action=reset_password&token=' . urlencode($token));
        exit;
    }

    if ($password !== $passwordConfirm) {
        $_SESSION['error'] = 'Le password non coincidono.';
        header('Location: index.php?action=reset_password&token=' . urlencode($token));
        exit;
    }

    $success = resetPasswordWithToken($pdo, $token, $password);

    if ($success) {
        $_SESSION['msg'] = 'Password reimpostata con successo. Puoi ora effettuare il login.';
        header('Location: index.php?action=show_login');
    } else {
        $_SESSION['error'] = 'Token non valido o scaduto. Richiedi un nuovo link.';
        header('Location: index.php?action=recupera_password');
    }
    exit;
}

// ==========================================
// EMAIL VERIFICATION
// ==========================================

function verifyEmail(PDO $pdo): void
{
    $token = $_GET['token'] ?? '';

    if (empty($token)) {
        $_SESSION['error'] = 'Token di verifica non valido.';
        header('Location: index.php');
        exit;
    }

    $user = verifyEmailToken($pdo, $token);

    if ($user) {
        markEmailVerified($pdo, $user['id']);
        $_SESSION['msg'] = 'Email verificata con successo! Puoi ora effettuare il login.';
    } else {
        $_SESSION['error'] = 'Token non valido o scaduto.';
    }

    header('Location: index.php?action=show_login');
    exit;
}

function resendVerification(PDO $pdo): void
{
    require_once __DIR__ . '/../config/mail.php';

    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Devi effettuare il login.';
        header('Location: index.php?action=show_login');
        exit;
    }

    $user = getUtenteById($pdo, $_SESSION['user_id']);

    if (!$user) {
        $_SESSION['error'] = 'Utente non trovato.';
        header('Location: index.php');
        exit;
    }

    if (!empty($user['email_verified'])) {
        $_SESSION['msg'] = 'La tua email è già verificata.';
        header('Location: index.php?action=profilo');
        exit;
    }

    $token = generateToken();
    setVerificationToken($pdo, $user['id'], $token);
    sendVerificationEmail($user['Email'], $user['Nome'], $token);

    $_SESSION['msg'] = 'Email di verifica inviata. Controlla la tua casella di posta.';
    header('Location: index.php?action=profilo');
    exit;
}
