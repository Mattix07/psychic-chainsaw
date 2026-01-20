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

require_once __DIR__ . '/../models/Utente.php';

/**
 * Mostra la pagina profilo utente
 * Carica i dati aggiornati dal database
 */
function showProfilo(PDO $pdo): void
{
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Devi effettuare il login.';
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

    // Verifica che la nuova email non sia gia usata da altri utenti
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
        // Sincronizza i dati di sessione
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

/**
 * Aggiorna la password utente
 * Richiede verifica della password attuale per sicurezza
 */
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

    // Verifica password attuale prima di permettere il cambio
    $user = getUtenteById($pdo, $_SESSION['user_id']);
    if (!$user || !password_verify($currentPassword, $user['Password'])) {
        $_SESSION['error'] = 'La password attuale non è corretta.';
        header('Location: index.php?action=cambia_password');
        exit;
    }

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

/**
 * Elimina l'account utente
 * Richiede conferma esplicita e verifica password
 */
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

    // Richiede checkbox di conferma esplicita
    if (!$confirmDelete) {
        $_SESSION['error'] = 'Devi confermare l\'eliminazione dell\'account.';
        header('Location: index.php?action=elimina_account');
        exit;
    }

    // Verifica password per prevenire eliminazioni accidentali
    $user = getUtenteById($pdo, $_SESSION['user_id']);
    if (!$user || !password_verify($password, $user['Password'])) {
        $_SESSION['error'] = 'Password non corretta.';
        header('Location: index.php?action=elimina_account');
        exit;
    }

    $success = deleteUtente($pdo, $_SESSION['user_id']);

    if ($success) {
        // Termina la sessione dopo eliminazione
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

    $user = getUtenteByEmail($pdo, $email);

    // Messaggio generico per sicurezza (non rivela se email esiste)
    $_SESSION['msg'] = 'Se l\'email è registrata, riceverai le istruzioni per reimpostare la password.';

    if ($user) {
        $token = generateToken();
        setResetToken($pdo, $email, $token);
        sendPasswordResetEmail($email, $user['Nome'], $token);
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

/**
 * Reinvia l'email di verifica
 * Utile se l'utente non ha ricevuto la prima email
 */
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

    // Non reinviare se gia verificata
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
