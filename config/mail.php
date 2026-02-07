<?php
/**
 * Configurazione e funzioni per l'invio email
 *
 * Il sistema supporta due modalita:
 * - Simulazione: le email vengono salvate su file di log (sviluppo)
 * - SMTP reale: invio tramite PHPMailer (produzione)
 *
 * Per attivare l'invio reale:
 * 1. Installa PHPMailer: composer require phpmailer/phpmailer
 * 2. Configura le credenziali SMTP nel file .env
 * 3. Imposta USE_REAL_MAIL = true
 */

// Modalita invio: controllato da MAIL_ENABLED in .env
define('USE_REAL_MAIL', filter_var(env('MAIL_ENABLED', false), FILTER_VALIDATE_BOOLEAN));
define('MAIL_LOG_PATH', __DIR__ . '/../logs/mail.log');

// Configurazione server SMTP (valori da .env in produzione)
define('SMTP_HOST', env('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', env('SMTP_PORT', 587));
define('SMTP_USER', env('SMTP_USER', ''));
define('SMTP_PASS', env('SMTP_PASS', ''));
define('SMTP_FROM_EMAIL', env('SMTP_FROM_EMAIL', 'noreply@eventsmaster.it'));
define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', 'EventsMaster'));

/**
 * Invia una email o la simula in base alla configurazione
 *
 * @param string $to Indirizzo destinatario
 * @param string $subject Oggetto email
 * @param string $body Contenuto HTML o testo
 * @param bool $isHtml True se il body e HTML
 * @return bool Esito invio
 */
function sendMail(string $to, string $subject, string $body, bool $isHtml = true): bool
{
    if (USE_REAL_MAIL) {
        return sendRealMail($to, $subject, $body, $isHtml);
    }

    return simulateMail($to, $subject, $body);
}

/**
 * Simula l'invio email scrivendo su file di log
 * Utile in sviluppo per verificare il contenuto senza inviare
 *
 * @return bool True se la scrittura su log e riuscita
 */
function simulateMail(string $to, string $subject, string $body): bool
{
    $logEntry = sprintf(
        "[%s] TO: %s | SUBJECT: %s\n%s\n%s\n\n",
        date('Y-m-d H:i:s'),
        $to,
        $subject,
        str_repeat('-', 50),
        strip_tags($body)
    );

    $result = file_put_contents(MAIL_LOG_PATH, $logEntry, FILE_APPEND | LOCK_EX);

    if ($result !== false) {
        logError("EMAIL SIMULATA inviata a: $to - Oggetto: $subject");
        return true;
    }

    return false;
}

/**
 * Invia email reale tramite SMTP usando PHPMailer
 * Fallback a simulazione se PHPMailer non e installato
 *
 * @return bool Esito invio
 */
function sendRealMail(string $to, string $subject, string $body, bool $isHtml): bool
{
    $phpmailerPath = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($phpmailerPath)) {
        logError("PHPMailer non installato. Usa: composer require phpmailer/phpmailer");
        return simulateMail($to, $subject, $body);
    }

    require_once $phpmailerPath;

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        // Configurazione connessione SMTP
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';

        // Imposta mittente e destinatario
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);

        // Configura contenuto
        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body = $body;
        if ($isHtml) {
            $mail->AltBody = strip_tags($body);
        }

        $mail->send();
        return true;

    } catch (Exception $e) {
        logError("Errore invio email: " . $e->getMessage());
        return false;
    }
}

/**
 * Genera un token crittograficamente sicuro
 * Utilizzato per verifica email e reset password
 *
 * @param int $length Lunghezza in byte (il risultato hex sara il doppio)
 * @return string Token esadecimale
 */
function generateToken(int $length = 32): string
{
    return bin2hex(random_bytes($length));
}

/**
 * Invia email di verifica account dopo la registrazione
 * Il link scade dopo 24 ore
 *
 * @return bool Esito invio
 */
function sendVerificationEmail(string $email, string $nome, string $token): bool
{
    $verifyUrl = getBaseUrl() . "index.php?action=verify_email&token=" . urlencode($token);

    $subject = "Verifica il tuo account EventsMaster";
    $body = getEmailTemplate('verify', [
        'nome' => $nome,
        'verify_url' => $verifyUrl
    ]);

    return sendMail($email, $subject, $body);
}

/**
 * Invia email per reset password
 * Il link scade dopo 1 ora per sicurezza
 *
 * @return bool Esito invio
 */
function sendPasswordResetEmail(string $email, string $nome, string $token): bool
{
    $resetUrl = getBaseUrl() . "index.php?action=reset_password&token=" . urlencode($token);

    $subject = "Reimposta la tua password - EventsMaster";
    $body = getEmailTemplate('reset_password', [
        'nome' => $nome,
        'reset_url' => $resetUrl
    ]);

    return sendMail($email, $subject, $body);
}

/**
 * Costruisce il corpo HTML dell'email da template
 * I placeholder {{chiave}} vengono sostituiti con i valori
 *
 * @param string $template Nome del template (verify, reset_password)
 * @param array $data Dati da inserire nel template
 * @return string HTML dell'email
 */
function getEmailTemplate(string $template, array $data): string
{
    $templates = [
        'verify' => '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="background: linear-gradient(135deg, #10b981, #3b82f6); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                    <h1 style="color: white; margin: 0;">EventsMaster</h1>
                </div>
                <div style="background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px;">
                    <h2 style="color: #1f2937;">Ciao {{nome}}!</h2>
                    <p style="color: #4b5563; font-size: 16px;">Grazie per esserti registrato su EventsMaster. Per completare la registrazione, verifica il tuo indirizzo email cliccando sul pulsante qui sotto.</p>
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="{{verify_url}}" style="background: linear-gradient(135deg, #10b981, #3b82f6); color: white; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: bold;">Verifica Email</a>
                    </div>
                    <p style="color: #6b7280; font-size: 14px;">Se non hai creato un account, puoi ignorare questa email.</p>
                    <p style="color: #6b7280; font-size: 12px;">Il link scade tra 24 ore.</p>
                </div>
            </div>',

        'reset_password' => '
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="background: linear-gradient(135deg, #10b981, #3b82f6); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                    <h1 style="color: white; margin: 0;">EventsMaster</h1>
                </div>
                <div style="background: #f9fafb; padding: 30px; border-radius: 0 0 10px 10px;">
                    <h2 style="color: #1f2937;">Ciao {{nome}},</h2>
                    <p style="color: #4b5563; font-size: 16px;">Hai richiesto di reimpostare la password del tuo account EventsMaster. Clicca sul pulsante qui sotto per procedere.</p>
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="{{reset_url}}" style="background: linear-gradient(135deg, #10b981, #3b82f6); color: white; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: bold;">Reimposta Password</a>
                    </div>
                    <p style="color: #6b7280; font-size: 14px;">Se non hai richiesto il reset della password, puoi ignorare questa email.</p>
                    <p style="color: #6b7280; font-size: 12px;">Il link scade tra 1 ora.</p>
                </div>
            </div>'
    ];

    $html = $templates[$template] ?? '';

    // Sostituisce i placeholder con i valori (escape per sicurezza)
    foreach ($data as $key => $value) {
        $html = str_replace('{{' . $key . '}}', htmlspecialchars($value), $html);
    }

    return $html;
}

/**
 * Costruisce l'URL base dell'applicazione
 * Rileva automaticamente protocollo, host e path
 *
 * @return string URL completo con trailing slash
 */
function getBaseUrl(): string
{
    if (defined('BASE_URL')) {
        return rtrim(BASE_URL, '/') . '/';
    }
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = dirname($_SERVER['SCRIPT_NAME']);
    return $protocol . '://' . $host . $path . '/';
}
