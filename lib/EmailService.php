<?php
/**
 * Servizio Email
 * Gestisce l'invio di email con supporto per template
 *
 * TODO: Configurare PHPMailer per invio SMTP reale
 * Al momento usa mail() PHP, che richiede sendmail configurato
 */

class EmailService
{
    private PDO $pdo;
    private bool $enableRealSending;
    private string $fromEmail;
    private string $fromName;

    /**
     * @param PDO $pdo Database connection
     * @param bool $enableRealSending Se true, invia email reali. Se false, solo log nel DB
     */
    public function __construct(PDO $pdo, bool $enableRealSending = false)
    {
        $this->pdo = $pdo;
        $this->enableRealSending = $enableRealSending;
        $this->fromEmail = 'noreply@eventsmaster.it';
        $this->fromName = 'EventsMaster';
    }

    /**
     * Invia email di notifica per modifica evento
     */
    public function sendEventModifiedNotification(int $destinatarioId, int $mittenteId, int $eventoId, string $nomeEvento, array $modifiche): bool
    {
        $stmt = $this->pdo->prepare("SELECT Nome, Cognome, Email FROM Utenti WHERE id = ?");
        $stmt->execute([$destinatarioId]);
        $destinatario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$destinatario) return false;

        $stmt->execute([$mittenteId]);
        $mittente = $stmt->fetch(PDO::FETCH_ASSOC);

        $modificheHtml = '<ul>';
        foreach ($modifiche as $campo => $valore) {
            $modificheHtml .= "<li><strong>$campo:</strong> $valore</li>";
        }
        $modificheHtml .= '</ul>';

        $oggetto = "Evento \"$nomeEvento\" modificato";
        $messaggio = $this->getTemplate('modifica_evento', [
            'nome' => $destinatario['Nome'],
            'cognome' => $destinatario['Cognome'],
            'nomeEvento' => $nomeEvento,
            'modificatoDa' => $mittente['Nome'] . ' ' . $mittente['Cognome'],
            'ruoloMittente' => $this->getRuoloLabel($mittenteId),
            'modifiche' => $modificheHtml,
            'linkEvento' => $this->getEventoUrl($eventoId)
        ]);

        return $this->send(
            $destinatario['Email'],
            $oggetto,
            $messaggio,
            'modifica_evento',
            $destinatarioId,
            $mittenteId,
            json_encode(['idEvento' => $eventoId, 'modifiche' => $modifiche])
        );
    }

    /**
     * Invia invito a collaborare su un evento
     */
    public function sendCollaborationInvite(int $destinatarioId, int $mittenteId, int $eventoId, string $nomeEvento, string $token): bool
    {
        $stmt = $this->pdo->prepare("SELECT Nome, Cognome, Email FROM Utenti WHERE id = ?");
        $stmt->execute([$destinatarioId]);
        $destinatario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$destinatario) return false;

        $stmt->execute([$mittenteId]);
        $mittente = $stmt->fetch(PDO::FETCH_ASSOC);

        $oggetto = "Sei stato invitato a collaborare su \"$nomeEvento\"";
        $messaggio = $this->getTemplate('invito_collaborazione', [
            'nome' => $destinatario['Nome'],
            'cognome' => $destinatario['Cognome'],
            'nomeEvento' => $nomeEvento,
            'invitatoDa' => $mittente['Nome'] . ' ' . $mittente['Cognome'],
            'linkAccetta' => $this->getAcceptInviteUrl($token),
            'linkRifiuta' => $this->getDeclineInviteUrl($token)
        ]);

        return $this->send(
            $destinatario['Email'],
            $oggetto,
            $messaggio,
            'invito_collaborazione',
            $destinatarioId,
            $mittenteId,
            json_encode(['idEvento' => $eventoId, 'token' => $token])
        );
    }

    /**
     * Invia notifica di avvenuta verifica account
     */
    public function sendAccountVerifiedNotification(int $destinatarioId, int $mittenteId): bool
    {
        $stmt = $this->pdo->prepare("SELECT Nome, Cognome, Email FROM Utenti WHERE id = ?");
        $stmt->execute([$destinatarioId]);
        $destinatario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$destinatario) return false;

        $oggetto = "Account verificato con successo!";
        $messaggio = $this->getTemplate('account_verificato', [
            'nome' => $destinatario['Nome'],
            'cognome' => $destinatario['Cognome']
        ]);

        return $this->send(
            $destinatario['Email'],
            $oggetto,
            $messaggio,
            'verifica_account',
            $destinatarioId,
            $mittenteId
        );
    }

    /**
     * Metodo principale di invio
     */
    private function send(string $to, string $subject, string $htmlMessage, string $tipo, int $destinatarioId, ?int $mittenteId = null, ?string $metadata = null): bool
    {
        // Salva notifica nel database
        $stmt = $this->pdo->prepare("
            INSERT INTO Notifiche (tipo, destinatario_id, mittente_id, oggetto, messaggio, email_inviata, metadata)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$tipo, $destinatarioId, $mittenteId, $subject, $htmlMessage, 0, $metadata]);
        $notificaId = $this->pdo->lastInsertId();

        // Se l'invio reale è disabilitato, fermiamoci qui
        if (!$this->enableRealSending) {
            error_log("EMAIL SIMULATA - To: $to, Subject: $subject");
            return true;
        }

        // Invio reale con mail() PHP
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        $success = mail($to, $subject, $htmlMessage, $headers);

        // Aggiorna lo stato della notifica
        if ($success) {
            $stmt = $this->pdo->prepare("UPDATE Notifiche SET email_inviata = 1 WHERE id = ?");
            $stmt->execute([$notificaId]);
        }

        return $success;
    }

    /**
     * Template email
     */
    private function getTemplate(string $tipo, array $data): string
    {
        $baseStyle = "
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; }
                .button { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
                .button.secondary { background: #ccc; color: #333; }
                .footer { text-align: center; padding: 20px; color: #999; font-size: 12px; }
            </style>
        ";

        switch ($tipo) {
            case 'modifica_evento':
                return "
                    <!DOCTYPE html>
                    <html>
                    <head>$baseStyle</head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1>EventsMaster</h1>
                            </div>
                            <div class='content'>
                                <h2>Ciao {$data['nome']} {$data['cognome']},</h2>
                                <p>L'evento <strong>{$data['nomeEvento']}</strong> è stato modificato da <strong>{$data['modificatoDa']}</strong> ({$data['ruoloMittente']}).</p>
                                <p><strong>Modifiche apportate:</strong></p>
                                {$data['modifiche']}
                                <p><a href='{$data['linkEvento']}' class='button'>Visualizza Evento</a></p>
                            </div>
                            <div class='footer'>
                                <p>Questa è una notifica automatica da EventsMaster</p>
                            </div>
                        </div>
                    </body>
                    </html>
                ";

            case 'invito_collaborazione':
                return "
                    <!DOCTYPE html>
                    <html>
                    <head>$baseStyle</head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1>EventsMaster</h1>
                            </div>
                            <div class='content'>
                                <h2>Ciao {$data['nome']} {$data['cognome']},</h2>
                                <p><strong>{$data['invitatoDa']}</strong> ti ha invitato a collaborare sull'evento <strong>{$data['nomeEvento']}</strong>.</p>
                                <p>Come collaboratore, potrai modificare i dettagli dell'evento e gestirne l'organizzazione.</p>
                                <p>
                                    <a href='{$data['linkAccetta']}' class='button'>Accetta Invito</a>
                                    <a href='{$data['linkRifiuta']}' class='button secondary'>Rifiuta</a>
                                </p>
                            </div>
                            <div class='footer'>
                                <p>Questa è una notifica automatica da EventsMaster</p>
                            </div>
                        </div>
                    </body>
                    </html>
                ";

            case 'account_verificato':
                return "
                    <!DOCTYPE html>
                    <html>
                    <head>$baseStyle</head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1>EventsMaster</h1>
                            </div>
                            <div class='content'>
                                <h2>Ciao {$data['nome']} {$data['cognome']},</h2>
                                <p>Il tuo account è stato verificato con successo!</p>
                                <p>Ora puoi accedere a tutte le funzionalità di EventsMaster.</p>
                            </div>
                            <div class='footer'>
                                <p>Questa è una notifica automatica da EventsMaster</p>
                            </div>
                        </div>
                    </body>
                    </html>
                ";

            default:
                return "";
        }
    }

    private function getRuoloLabel(int $userId): string
    {
        $stmt = $this->pdo->prepare("SELECT ruolo FROM Utenti WHERE id = ?");
        $stmt->execute([$userId]);
        $ruolo = $stmt->fetchColumn();

        $labels = [
            'admin' => 'Amministratore',
            'mod' => 'Moderatore',
            'promoter' => 'Promoter',
            'user' => 'Utente'
        ];

        return $labels[$ruolo] ?? 'Utente';
    }

    private function getEventoUrl(int $eventoId): string
    {
        return "http://{$_SERVER['HTTP_HOST']}/index.php?action=evento_dettaglio&id=$eventoId";
    }

    private function getAcceptInviteUrl(string $token): string
    {
        return "http://{$_SERVER['HTTP_HOST']}/index.php?action=accept_collaboration&token=$token";
    }

    private function getDeclineInviteUrl(string $token): string
    {
        return "http://{$_SERVER['HTTP_HOST']}/index.php?action=decline_collaboration&token=$token";
    }
}
