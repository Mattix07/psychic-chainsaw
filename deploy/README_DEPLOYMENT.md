# Guida al Deployment di EventsMaster su Ubuntu Server 24

## Panoramica

Questa guida spiega come deployare il progetto EventsMaster su un server Ubuntu Server 24 pulito utilizzando lo script di deployment automatico.

## Requisiti

### Server
- **OS**: Ubuntu Server 24.04 LTS
- **RAM**: Minimo 2GB (consigliato 4GB)
- **Storage**: Minimo 20GB
- **Accesso**: root o utente con privilegi sudo
- **Connessione**: Internet attiva
- **IP**: 192.168.1.50 (nel tuo caso)

### Credenziali server
- **IP/Host**: 192.168.1.50
- **User**: root
- **Password**: 2001

### Repository Git
Il progetto deve essere accessibile da:
- GitLab: https://gitlab.com/boscomattia6/eventsmaster.git
- oppure GitHub: https://github.com/Mattix07/psychic-chainsaw.git

## Cosa installa lo script

Lo script `setup_server.sh` installa e configura automaticamente:

1. **Apache 2.4** - Web server con moduli rewrite, ssl, headers
2. **PHP 8.3** - Con tutte le estensioni necessarie (mysql, curl, gd, mbstring, xml, zip, ecc.)
3. **MySQL 8** - Database server con database e utente dedicato
4. **Git** - Per clonare il repository
5. **Composer** - Gestione dipendenze PHP (se presente composer.json)
6. **UFW Firewall** - Configurato per HTTP, HTTPS e SSH

## Istruzioni Passo-Passo

### Step 1: Connessione al server

Dalla tua macchina locale, connettiti al server via SSH:

```bash
ssh root@192.168.1.50
# Password: 2001
```

### Step 2: Download dello script

Puoi scaricare lo script in due modi:

#### Opzione A: Clone del repository completo (consigliato)

```bash
cd /tmp
git clone https://gitlab.com/boscomattia6/eventsmaster.git
cd eventsmaster/deploy
```

#### Opzione B: Download diretto dello script

```bash
cd /tmp
wget https://gitlab.com/boscomattia6/eventsmaster/-/raw/main/deploy/setup_server.sh
# oppure
curl -O https://gitlab.com/boscomattia6/eventsmaster/-/raw/main/deploy/setup_server.sh
```

### Step 3: Rendi eseguibile lo script

```bash
chmod +x setup_server.sh
```

### Step 4: Esegui lo script

```bash
sudo bash setup_server.sh
```

Lo script richiederà circa **10-15 minuti** per completare l'installazione (dipende dalla velocità della connessione).

### Step 5: Salva le credenziali

Al termine, lo script mostrerà informazioni importanti:

```
==========================================
DEPLOYMENT COMPLETATO CON SUCCESSO!
==========================================

URL Applicazione:    http://192.168.1.50
Directory Progetto:  /var/www/eventsMaster

Database MySQL:
Database:            5cit_eventsMaster
Utente:              eventsmaster_user
Password:            [password_generata_automaticamente]
Host:                localhost

MySQL Root:
Password Root:       [password_generata_automaticamente]
```

**IMPORTANTE**: Queste credenziali sono anche salvate in:
```
/root/eventsmaster_credentials.txt
```

### Step 6: Verifica installazione

Apri un browser e visita:
```
http://192.168.1.50
```

Dovresti vedere la homepage di EventsMaster.

## Configurazione Post-Installazione

### 1. Configurazione Email

Modifica il file `.env` per configurare l'invio email:

```bash
nano /var/www/eventsMaster/config/.env
```

Aggiorna le seguenti variabili:

```env
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tua_email@gmail.com
MAIL_PASSWORD=tua_app_password
MAIL_FROM=noreply@tuodominio.com
MAIL_FROM_NAME=EventsMaster
```

Per Gmail, genera una "App Password" da: https://myaccount.google.com/apppasswords

### 2. Crea utente amministratore

Accedi all'applicazione e registra il primo utente, che diventerà admin. Oppure inserisci manualmente un admin nel database:

```bash
mysql -u eventsmaster_user -p 5cit_eventsMaster
```

```sql
-- Inserisci admin (password: admin123)
INSERT INTO Utenti (Nome, Cognome, Email, Password, ruolo, verificato)
VALUES (
    'Admin',
    'Sistema',
    'admin@eventsmaster.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    1
);
```

### 3. Configura backup automatici

Crea uno script per backup giornaliero del database:

```bash
nano /root/backup_eventmaster.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/root/backups/eventmaster"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# Carica credenziali
DB_USER=$(grep DB_USER /var/www/eventsMaster/config/.env | cut -d '=' -f2)
DB_PASS=$(grep DB_PASS /var/www/eventsMaster/config/.env | cut -d '=' -f2)
DB_NAME=$(grep DB_NAME /var/www/eventsMaster/config/.env | cut -d '=' -f2)

# Backup database
mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"

# Mantieni solo ultimi 7 giorni
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +7 -delete

echo "Backup completato: db_$DATE.sql.gz"
```

Rendi eseguibile e aggiungi al crontab:

```bash
chmod +x /root/backup_eventmaster.sh

# Aggiungi a crontab (esegui ogni giorno alle 2:00)
crontab -e
```

Aggiungi questa riga:
```
0 2 * * * /root/backup_eventmaster.sh >> /var/log/eventmaster_backup.log 2>&1
```

### 4. Installa certificato SSL (HTTPS)

Per abilitare HTTPS con Let's Encrypt:

```bash
# Installa Certbot
apt-get install -y certbot python3-certbot-apache

# NOTA: Funziona solo con un dominio pubblico, non con IP locale
# Se hai un dominio:
certbot --apache -d tuodominio.com -d www.tuodominio.com
```

Per IP locale (192.168.1.50), puoi creare un certificato self-signed:

```bash
# Genera certificato self-signed
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/ssl/private/eventsmaster.key \
  -out /etc/ssl/certs/eventsmaster.crt \
  -subj "/C=IT/ST=Italy/L=City/O=EventsMaster/CN=192.168.1.50"

# Configura Apache per SSL
nano /etc/apache2/sites-available/eventsMaster-ssl.conf
```

```apache
<VirtualHost *:443>
    ServerName 192.168.1.50

    DocumentRoot /var/www/eventsMaster/public

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/eventsmaster.crt
    SSLCertificateKeyFile /etc/ssl/private/eventsmaster.key

    <Directory /var/www/eventsMaster/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

```bash
a2ensite eventsMaster-ssl
systemctl restart apache2
```

## Struttura Directory

Dopo l'installazione:

```
/var/www/eventsMaster/
├── config/
│   ├── .env                    # Configurazione ambiente (SENSIBILE)
│   ├── database.php
│   ├── database_schema.php
│   └── app_config.php
├── controllers/
├── models/
├── views/
├── public/                     # Document root Apache
│   ├── index.php
│   ├── css/
│   ├── js/
│   └── qrcodes/               # QR code biglietti
├── uploads/
│   ├── avatars/               # Avatar utenti
│   └── eventi/                # Immagini eventi
├── logs/                      # Log applicazione
├── db/                        # Script SQL
└── deploy/                    # Script deployment
```

## Comandi Utili

### Monitoraggio

```bash
# Status servizi
systemctl status apache2
systemctl status mysql

# Log Apache in tempo reale
tail -f /var/log/apache2/eventsMaster_error.log
tail -f /var/log/apache2/eventsMaster_access.log

# Log applicazione
tail -f /var/www/eventsMaster/logs/app.log

# Utilizzo risorse
htop
df -h
free -h
```

### Manutenzione Database

```bash
# Connetti a MySQL
mysql -u eventsmaster_user -p

# Backup manuale
mysqldump -u eventsmaster_user -p 5cit_eventsMaster > backup_$(date +%Y%m%d).sql

# Restore da backup
mysql -u eventsmaster_user -p 5cit_eventsMaster < backup_20260128.sql

# Ottimizza tabelle
mysqlcheck -u eventsmaster_user -p --optimize 5cit_eventsMaster
```

### Aggiornamento Applicazione

```bash
cd /var/www/eventsMaster

# Backup prima di aggiornare
mysqldump -u eventsmaster_user -p 5cit_eventsMaster > /root/backups/pre_update_$(date +%Y%m%d).sql

# Pull ultimi cambiamenti
sudo -u www-data git pull origin main

# Installa nuove dipendenze (se presenti)
sudo -u www-data composer install --no-dev

# Applica eventuali migration
mysql -u eventsmaster_user -p 5cit_eventsMaster < db/migrations/nuova_migration.sql

# Riavvia Apache
systemctl restart apache2
```

### Permessi

Se hai problemi con i permessi:

```bash
cd /var/www/eventsMaster

# Reset permessi completo
chown -R www-data:www-data .
chmod -R 755 .
chmod -R 775 uploads/ logs/ public/qrcodes/
chmod 640 config/.env
```

## Risoluzione Problemi

### Errore "500 Internal Server Error"

```bash
# Controlla log errori
tail -n 50 /var/log/apache2/eventsMaster_error.log

# Verifica permessi
ls -la /var/www/eventsMaster/

# Verifica configurazione PHP
php -v
php -m  # Mostra moduli caricati
```

### Errore connessione database

```bash
# Verifica MySQL attivo
systemctl status mysql

# Testa connessione
mysql -u eventsmaster_user -p 5cit_eventsMaster

# Controlla credenziali in .env
cat /var/www/eventsMaster/config/.env | grep DB_
```

### Upload file non funziona

```bash
# Verifica permessi directory upload
ls -la /var/www/eventsMaster/uploads/

# Ricrea permessi
chmod -R 775 /var/www/eventsMaster/uploads/
chown -R www-data:www-data /var/www/eventsMaster/uploads/

# Verifica limiti PHP
grep upload_max_filesize /etc/php/8.3/apache2/php.ini
grep post_max_size /etc/php/8.3/apache2/php.ini
```

### Apache non si avvia

```bash
# Test configurazione
apache2ctl configtest

# Verifica porta 80 libera
netstat -tulpn | grep :80

# Controlla log
journalctl -u apache2 -n 50
```

## Sicurezza

### Checklist Sicurezza Produzione

- [ ] Cambia `APP_ENV=production` in `.env`
- [ ] Imposta `APP_DEBUG=false` in `.env`
- [ ] Configura firewall UFW
- [ ] Cambia password root MySQL
- [ ] Abilita HTTPS con certificato SSL
- [ ] Configura backup automatici
- [ ] Imposta permessi file corretti (640 per .env)
- [ ] Disabilita directory listing Apache
- [ ] Monitora log regolarmente
- [ ] Aggiorna sistema operativo: `apt-get update && apt-get upgrade`

### Hardening MySQL

```bash
mysql_secure_installation
```

Rispondi:
- Remove anonymous users? **Yes**
- Disallow root login remotely? **Yes**
- Remove test database? **Yes**
- Reload privilege tables? **Yes**

### Protezione file .env

```bash
chmod 640 /var/www/eventsMaster/config/.env
chown www-data:www-data /var/www/eventsMaster/config/.env
```

## Performance

### Abilita cache PHP OPcache

Modifica `/etc/php/8.3/apache2/php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

Riavvia Apache:
```bash
systemctl restart apache2
```

### Ottimizza MySQL

Modifica `/etc/mysql/mysql.conf.d/mysqld.cnf`:

```ini
[mysqld]
max_connections = 100
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
query_cache_size = 16M
```

Riavvia MySQL:
```bash
systemctl restart mysql
```

## Contatti e Supporto

- **Progetto**: EventsMaster
- **Repository**: https://gitlab.com/boscomattia6/eventsmaster
- **Versione**: 1.0.0

## Note Finali

Questa installazione è configurata per ambiente di produzione. Per maggiori personalizzazioni, consulta la documentazione del progetto o modifica lo script `setup_server.sh` secondo le tue esigenze.

---

**Ultima modifica**: 2026-01-28
