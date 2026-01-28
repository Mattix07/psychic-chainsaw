# ============================================================================
# EventsMaster - Script di Deploy da Windows
# ============================================================================

# Configurazioni
$SERVER_IP = "192.168.1.50"
$SERVER_USER = "root"
$DEPLOY_SCRIPT = Join-Path $PSScriptRoot "setup_server.sh"

# Funzioni colori
function Write-Success { Write-Host $args -ForegroundColor Green }
function Write-Info { Write-Host $args -ForegroundColor Cyan }
function Write-Warn { Write-Host $args -ForegroundColor Yellow }
function Write-Err { Write-Host $args -ForegroundColor Red }

# Banner
Clear-Host
Write-Success "=========================================="
Write-Success "EventsMaster - Deployment Automatico"
Write-Success "Windows -> Ubuntu Server 24"
Write-Success "=========================================="
Write-Host ""

# Verifica prerequisiti
Write-Info "Verifica prerequisiti..."

$sshInstalled = Get-Command ssh -ErrorAction SilentlyContinue
if (-not $sshInstalled) {
    Write-Err "ERRORE: OpenSSH Client non trovato!"
    Write-Host ""
    Write-Host "Per installare OpenSSH Client:"
    Write-Host "1. Impostazioni > App > Funzionalita facoltative"
    Write-Host "2. Aggiungi funzionalita"
    Write-Host "3. Cerca 'OpenSSH Client'"
    Write-Host "4. Installa"
    Write-Host ""
    Read-Host "Premi Enter per uscire"
    exit 1
}

Write-Success "OK - OpenSSH Client trovato"

if (-not (Test-Path $DEPLOY_SCRIPT)) {
    Write-Err "ERRORE: Script setup_server.sh non trovato"
    Write-Host "Path cercato: $DEPLOY_SCRIPT"
    Read-Host "Premi Enter per uscire"
    exit 1
}

Write-Success "OK - Script di deployment trovato"
Write-Host ""

# Menu
Write-Info "Seleziona operazione:"
Write-Host "1. Deploy completo (installa tutto da zero)"
Write-Host "2. Solo aggiornamento codice (git pull)"
Write-Host "3. Trasferisci solo script di setup"
Write-Host "4. Connessione SSH al server"
Write-Host "5. Backup database dal server"
Write-Host ""
$choice = Read-Host "Scelta (1-5)"

switch ($choice) {
    "1" {
        # Deploy completo
        Write-Info "=========================================="
        Write-Info "DEPLOY COMPLETO"
        Write-Info "=========================================="
        Write-Host ""
        Write-Warn "Questo installera tutto da zero sul server."
        Write-Warn "Server: $SERVER_IP"
        Write-Host ""

        $confirm = Read-Host "Continuare? (s/n)"
        if ($confirm -ne "s") {
            Write-Host "Operazione annullata."
            exit 0
        }

        Write-Info "Step 1/3: Trasferimento script al server..."
        Write-Host ""

        # Trasferisci script
        & scp "$DEPLOY_SCRIPT" "${SERVER_USER}@${SERVER_IP}:/tmp/setup_server.sh"

        if ($LASTEXITCODE -eq 0) {
            Write-Success "OK - Script trasferito con successo"
        } else {
            Write-Err "ERRORE - Trasferimento fallito"
            Read-Host "Premi Enter per uscire"
            exit 1
        }

        Write-Host ""
        Write-Info "Step 2/3: Esecuzione script di setup sul server..."
        Write-Warn "Questo richiedera circa 10-15 minuti..."
        Write-Host ""

        # Esegui script sul server
        & ssh "${SERVER_USER}@${SERVER_IP}" "chmod +x /tmp/setup_server.sh; sudo bash /tmp/setup_server.sh"

        Write-Host ""
        Write-Info "Step 3/3: Recupero credenziali..."
        Write-Host ""

        # Scarica credenziali
        $credentialsFile = Join-Path $PSScriptRoot "server_credentials.txt"
        & scp "${SERVER_USER}@${SERVER_IP}:/root/eventsmaster_credentials.txt" $credentialsFile

        if (Test-Path $credentialsFile) {
            Write-Success "=========================================="
            Write-Success "DEPLOYMENT COMPLETATO!"
            Write-Success "=========================================="
            Write-Host ""
            Write-Host "Credenziali salvate in:"
            Write-Warn $credentialsFile
            Write-Host ""
            Get-Content $credentialsFile
            Write-Host ""
            Write-Success "Applicazione disponibile su: http://$SERVER_IP"
        }
    }

    "2" {
        # Aggiornamento codice
        Write-Info "=========================================="
        Write-Info "AGGIORNAMENTO CODICE"
        Write-Info "=========================================="
        Write-Host ""

        Write-Info "Esecuzione git pull sul server..."
        & ssh "${SERVER_USER}@${SERVER_IP}" "cd /var/www/eventsMaster; sudo -u www-data git pull origin main"

        if ($LASTEXITCODE -eq 0) {
            Write-Host ""
            Write-Success "OK - Codice aggiornato con successo"
            Write-Info "Riavvio Apache..."
            & ssh "${SERVER_USER}@${SERVER_IP}" "systemctl restart apache2"
            Write-Success "OK - Apache riavviato"
        }
    }

    "3" {
        # Trasferimento script
        Write-Info "=========================================="
        Write-Info "TRASFERIMENTO SCRIPT"
        Write-Info "=========================================="
        Write-Host ""

        Write-Info "Trasferimento setup_server.sh..."
        & scp "$DEPLOY_SCRIPT" "${SERVER_USER}@${SERVER_IP}:/tmp/setup_server.sh"

        if ($LASTEXITCODE -eq 0) {
            Write-Success "OK - Script trasferito in: /tmp/setup_server.sh"
            Write-Host ""
            Write-Info "Per eseguirlo, connettiti al server e lancia:"
            Write-Warn "sudo bash /tmp/setup_server.sh"
        }
    }

    "4" {
        # Connessione SSH
        Write-Info "Apertura connessione SSH..."
        Write-Host ""
        & ssh "${SERVER_USER}@${SERVER_IP}"
    }

    "5" {
        # Backup database
        Write-Info "=========================================="
        Write-Info "BACKUP DATABASE"
        Write-Info "=========================================="
        Write-Host ""

        $backupFile = "eventsMaster_backup_$(Get-Date -Format 'yyyyMMdd_HHmmss').sql"
        $localBackupPath = Join-Path $PSScriptRoot $backupFile

        Write-Info "Creazione backup sul server..."

        # Leggi credenziali DB
        $getCredsCmd = "grep 'DB_USER\|DB_PASS\|DB_NAME' /var/www/eventsMaster/config/.env"
        $creds = & ssh "${SERVER_USER}@${SERVER_IP}" $getCredsCmd

        # Estrai valori
        $dbUser = ($creds | Select-String "DB_USER").ToString().Split("=")[1].Trim()
        $dbPass = ($creds | Select-String "DB_PASS").ToString().Split("=")[1].Trim()
        $dbName = ($creds | Select-String "DB_NAME").ToString().Split("=")[1].Trim()

        # Crea backup
        $backupCmd = "mysqldump -u$dbUser -p'$dbPass' $dbName > /tmp/$backupFile"
        & ssh "${SERVER_USER}@${SERVER_IP}" $backupCmd

        Write-Info "Download backup..."
        & scp "${SERVER_USER}@${SERVER_IP}:/tmp/$backupFile" $localBackupPath

        # Cleanup remoto
        & ssh "${SERVER_USER}@${SERVER_IP}" "rm /tmp/$backupFile"

        if (Test-Path $localBackupPath) {
            Write-Success "OK - Backup completato!"
            Write-Host ""
            Write-Host "File salvato in:"
            Write-Warn $localBackupPath

            $fileSize = (Get-Item $localBackupPath).Length / 1MB
            Write-Host ("Dimensione: {0:N2} MB" -f $fileSize)
        }
    }

    default {
        Write-Err "Scelta non valida."
        exit 1
    }
}

Write-Host ""
Write-Host ""
Read-Host "Premi Enter per uscire"
