#!/bin/bash

###############################################################################
# EventsMaster - Script di Deployment Automatico per Ubuntu Server 24
#
# Questo script installa e configura tutto il necessario per hostare il
# progetto EventsMaster su un server Ubuntu Server 24 pulito.
#
# Prerequisiti:
# - Ubuntu Server 24 con accesso root
# - Connessione internet attiva
# - Repository Git accessibile (GitLab o GitHub)
#
# Uso:
#   sudo bash setup_server.sh
###############################################################################

set -e  # Esci immediatamente se un comando fallisce

# Colori per output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configurazioni
PROJECT_NAME="eventsMaster"
PROJECT_DIR="/var/www/eventsMaster"
DB_NAME="5cit_eventsMaster"
DB_USER="eventsmaster_user"
DB_PASSWORD=$(openssl rand -base64 16)  # Password generata casualmente
GIT_REPO="https://gitlab.com/boscomattia6/eventsmaster.git"
DOMAIN_OR_IP="192.168.1.50"
PHP_VERSION="8.3"

# Log function
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERRORE]${NC} $1"
    exit 1
}

warning() {
    echo -e "${YELLOW}[ATTENZIONE]${NC} $1"
}

info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

# Verifica che lo script sia eseguito come root
if [ "$EUID" -ne 0 ]; then
    error "Questo script deve essere eseguito come root (usa sudo)"
fi

log "=========================================="
log "EventsMaster - Deployment Automatico"
log "Server: Ubuntu 24.04"
log "=========================================="

###############################################################################
# FASE 1: Aggiornamento sistema e installazione pacchetti base
###############################################################################
log "FASE 1: Aggiornamento sistema e installazione pacchetti base..."

apt-get update -qq
apt-get upgrade -y -qq

log "Installazione pacchetti essenziali..."
apt-get install -y -qq \
    curl \
    wget \
    git \
    unzip \
    software-properties-common \
    ca-certificates \
    apt-transport-https \
    gnupg2 \
    || error "Errore durante l'installazione dei pacchetti base"

###############################################################################
# FASE 2: Installazione Apache
###############################################################################
log "FASE 2: Installazione e configurazione Apache..."

apt-get install -y -qq apache2 || error "Errore durante l'installazione di Apache"

# Abilita moduli Apache necessari
a2enmod rewrite
a2enmod ssl
a2enmod headers

systemctl enable apache2
systemctl start apache2

log "Apache installato e avviato con successo"

###############################################################################
# FASE 3: Installazione PHP 8.3
###############################################################################
log "FASE 3: Installazione PHP ${PHP_VERSION} e estensioni..."

# Aggiungi repository PPA per PHP
add-apt-repository -y ppa:ondrej/php >/dev/null 2>&1
apt-get update -qq

# Installa PHP e le estensioni necessarie
apt-get install -y -qq \
    php${PHP_VERSION} \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-common \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-readline \
    libapache2-mod-php${PHP_VERSION} \
    || error "Errore durante l'installazione di PHP"

# Verifica installazione PHP
PHP_INSTALLED_VERSION=$(php -v | head -n 1 | cut -d " " -f 2)
log "PHP ${PHP_INSTALLED_VERSION} installato con successo"

# Configurazione PHP per produzione
info "Configurazione parametri PHP..."
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 10M/' /etc/php/${PHP_VERSION}/apache2/php.ini
sed -i 's/post_max_size = 8M/post_max_size = 10M/' /etc/php/${PHP_VERSION}/apache2/php.ini
sed -i 's/max_execution_time = 30/max_execution_time = 300/' /etc/php/${PHP_VERSION}/apache2/php.ini
sed -i 's/memory_limit = 128M/memory_limit = 256M/' /etc/php/${PHP_VERSION}/apache2/php.ini

###############################################################################
# FASE 4: Installazione MySQL
###############################################################################
log "FASE 4: Installazione e configurazione MySQL..."

# Genera password temporanea per root MySQL
MYSQL_ROOT_PASSWORD=$(openssl rand -base64 16)

# Imposta password root prima dell'installazione
debconf-set-selections <<< "mysql-server mysql-server/root_password password ${MYSQL_ROOT_PASSWORD}"
debconf-set-selections <<< "mysql-server mysql-server/root_password_again password ${MYSQL_ROOT_PASSWORD}"

apt-get install -y -qq mysql-server || error "Errore durante l'installazione di MySQL"

systemctl enable mysql
systemctl start mysql

log "MySQL installato con successo"

# Crea database e utente
log "Configurazione database ${DB_NAME}..."

mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" <<MYSQL_SCRIPT
-- Crea database
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crea utente
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';

-- Assegna privilegi
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';

-- Applica modifiche
FLUSH PRIVILEGES;
MYSQL_SCRIPT

log "Database ${DB_NAME} creato con successo"

###############################################################################
# FASE 5: Clone del progetto da Git
###############################################################################
log "FASE 5: Clone del progetto da repository Git..."

# Rimuovi directory se esiste già
if [ -d "$PROJECT_DIR" ]; then
    warning "Directory ${PROJECT_DIR} già esistente. Rimozione..."
    rm -rf "$PROJECT_DIR"
fi

# Crea directory padre se non esiste
mkdir -p /var/www

# Clone del repository
log "Clone da ${GIT_REPO}..."
git clone "$GIT_REPO" "$PROJECT_DIR" || error "Errore durante il clone del repository"

cd "$PROJECT_DIR"
log "Progetto clonato con successo in ${PROJECT_DIR}"

###############################################################################
# FASE 6: Configurazione permessi
###############################################################################
log "FASE 6: Configurazione permessi filesystem..."

# Imposta proprietario corretto
chown -R www-data:www-data "$PROJECT_DIR"

# Crea directory necessarie se non esistono
mkdir -p "$PROJECT_DIR/uploads"
mkdir -p "$PROJECT_DIR/uploads/avatars"
mkdir -p "$PROJECT_DIR/uploads/eventi"
mkdir -p "$PROJECT_DIR/logs"
mkdir -p "$PROJECT_DIR/public/qrcodes"

# Imposta permessi corretti
chmod -R 755 "$PROJECT_DIR"
chmod -R 775 "$PROJECT_DIR/uploads"
chmod -R 775 "$PROJECT_DIR/logs"
chmod -R 775 "$PROJECT_DIR/public/qrcodes"

log "Permessi configurati correttamente"

###############################################################################
# FASE 7: Configurazione ambiente (.env)
###############################################################################
log "FASE 7: Creazione file di configurazione .env..."

cat > "$PROJECT_DIR/config/.env" <<ENV_FILE
# Configurazione Database
DB_HOST=localhost
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASSWORD}
DB_CHARSET=utf8mb4

# Configurazione Applicazione
APP_ENV=production
APP_DEBUG=false
APP_URL=http://${DOMAIN_OR_IP}

# Email (configurare con credenziali reali)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_email_password
MAIL_FROM=noreply@eventsmaster.com
MAIL_FROM_NAME=EventsMaster

# Sicurezza
SESSION_LIFETIME=7200
ENV_FILE

chmod 640 "$PROJECT_DIR/config/.env"
chown www-data:www-data "$PROJECT_DIR/config/.env"

log "File .env creato con successo"

###############################################################################
# FASE 8: Importazione schema database
###############################################################################
log "FASE 8: Importazione schema e dati database..."

# Cerca il file SQL più appropriato per l'installazione
if [ -f "$PROJECT_DIR/db/install_complete.sql" ]; then
    SQL_FILE="$PROJECT_DIR/db/install_complete.sql"
elif [ -f "$PROJECT_DIR/db/schema_completo.sql" ]; then
    SQL_FILE="$PROJECT_DIR/db/schema_completo.sql"
elif [ -f "$PROJECT_DIR/db/5cit_eventsMaster.sql" ]; then
    SQL_FILE="$PROJECT_DIR/db/5cit_eventsMaster.sql"
else
    error "Nessun file SQL di installazione trovato nella directory db/"
fi

info "Importazione schema da: ${SQL_FILE}"
mysql -u"${DB_USER}" -p"${DB_PASSWORD}" "${DB_NAME}" < "${SQL_FILE}" || error "Errore durante l'importazione dello schema"

log "Schema database importato con successo"

###############################################################################
# FASE 9: Configurazione Virtual Host Apache
###############################################################################
log "FASE 9: Configurazione Virtual Host Apache..."

cat > /etc/apache2/sites-available/${PROJECT_NAME}.conf <<VHOST
<VirtualHost *:80>
    ServerName ${DOMAIN_OR_IP}
    ServerAlias www.${DOMAIN_OR_IP}

    DocumentRoot ${PROJECT_DIR}/public
    DirectoryIndex index.php index.html

    <Directory ${PROJECT_DIR}/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted

        # Abilita URL rewriting
        RewriteEngine On

        # Blocca accesso a file sensibili
        <FilesMatch "^\.">
            Require all denied
        </FilesMatch>
    </Directory>

    # Blocca accesso alle directory di configurazione
    <Directory ${PROJECT_DIR}/config>
        Require all denied
    </Directory>

    <Directory ${PROJECT_DIR}/db>
        Require all denied
    </Directory>

    # Logs
    ErrorLog \${APACHE_LOG_DIR}/${PROJECT_NAME}_error.log
    CustomLog \${APACHE_LOG_DIR}/${PROJECT_NAME}_access.log combined

    # PHP Settings
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
    php_value max_execution_time 300
    php_value memory_limit 256M
</VirtualHost>
VHOST

# Disabilita default site e abilita il nostro
a2dissite 000-default.conf
a2ensite ${PROJECT_NAME}.conf

# Test configurazione Apache
apache2ctl configtest || warning "Possibili errori nella configurazione Apache"

# Riavvia Apache per applicare modifiche
systemctl restart apache2

log "Virtual Host configurato e Apache riavviato"

###############################################################################
# FASE 10: Installazione Composer (se necessario)
###############################################################################
log "FASE 10: Verifica installazione Composer..."

if [ -f "$PROJECT_DIR/composer.json" ]; then
    info "File composer.json trovato, installazione dipendenze..."

    # Installa Composer se non presente
    if ! command -v composer &> /dev/null; then
        log "Installazione Composer..."
        curl -sS https://getcomposer.org/installer | php
        mv composer.phar /usr/local/bin/composer
        chmod +x /usr/local/bin/composer
    fi

    # Installa dipendenze
    cd "$PROJECT_DIR"
    sudo -u www-data composer install --no-dev --optimize-autoloader || warning "Errore durante l'installazione delle dipendenze Composer"
else
    info "Nessun composer.json trovato, skip installazione dipendenze"
fi

###############################################################################
# FASE 11: Configurazione Firewall
###############################################################################
log "FASE 11: Configurazione firewall UFW..."

if command -v ufw &> /dev/null; then
    ufw allow 22/tcp    # SSH
    ufw allow 80/tcp    # HTTP
    ufw allow 443/tcp   # HTTPS

    # Abilita firewall solo se non è già attivo
    ufw status | grep -q "Status: active" || echo "y" | ufw enable

    log "Firewall configurato correttamente"
else
    warning "UFW non installato, configurazione firewall saltata"
fi

###############################################################################
# FASE 12: Ottimizzazioni finali
###############################################################################
log "FASE 12: Ottimizzazioni finali..."

# Crea cron job per pulizia carrelli abbandonati (se esistesse uno script)
if [ -f "$PROJECT_DIR/cron/cleanup_carts.php" ]; then
    info "Configurazione cron job per pulizia carrelli..."
    (crontab -u www-data -l 2>/dev/null; echo "0 2 * * * /usr/bin/php ${PROJECT_DIR}/cron/cleanup_carts.php") | crontab -u www-data -
fi

# Ottimizza MySQL
info "Ottimizzazione tabelle MySQL..."
mysqlcheck -u"${DB_USER}" -p"${DB_PASSWORD}" --optimize --all-databases >/dev/null 2>&1 || true

log "Ottimizzazioni completate"

###############################################################################
# FASE 13: Test finale
###############################################################################
log "FASE 13: Test installazione..."

# Verifica servizi attivi
systemctl is-active --quiet apache2 || error "Apache non è in esecuzione"
systemctl is-active --quiet mysql || error "MySQL non è in esecuzione"

# Verifica accesso web
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://${DOMAIN_OR_IP})
if [ "$HTTP_STATUS" -eq 200 ] || [ "$HTTP_STATUS" -eq 301 ] || [ "$HTTP_STATUS" -eq 302 ]; then
    log "Server web risponde correttamente (HTTP ${HTTP_STATUS})"
else
    warning "Server web ritorna HTTP ${HTTP_STATUS} - verificare configurazione"
fi

###############################################################################
# DEPLOYMENT COMPLETATO
###############################################################################

echo ""
echo -e "${GREEN}=========================================="
echo "DEPLOYMENT COMPLETATO CON SUCCESSO!"
echo -e "==========================================${NC}"
echo ""
echo -e "${BLUE}Informazioni di accesso:${NC}"
echo ""
echo "URL Applicazione:    http://${DOMAIN_OR_IP}"
echo "Directory Progetto:  ${PROJECT_DIR}"
echo ""
echo -e "${YELLOW}Database MySQL:${NC}"
echo "Database:            ${DB_NAME}"
echo "Utente:              ${DB_USER}"
echo "Password:            ${DB_PASSWORD}"
echo "Host:                localhost"
echo ""
echo -e "${YELLOW}MySQL Root:${NC}"
echo "Password Root:       ${MYSQL_ROOT_PASSWORD}"
echo ""
echo -e "${RED}IMPORTANTE: Salva queste credenziali in un posto sicuro!${NC}"
echo ""
echo -e "${BLUE}File di configurazione:${NC}"
echo "${PROJECT_DIR}/config/.env"
echo ""
echo -e "${BLUE}Log Apache:${NC}"
echo "Error log:  /var/log/apache2/${PROJECT_NAME}_error.log"
echo "Access log: /var/log/apache2/${PROJECT_NAME}_access.log"
echo ""
echo -e "${YELLOW}Prossimi passi:${NC}"
echo "1. Configura le credenziali email nel file .env"
echo "2. Crea un utente admin accedendo all'applicazione"
echo "3. Considera l'installazione di un certificato SSL (Let's Encrypt)"
echo "4. Configura backup automatici del database"
echo ""
echo -e "${GREEN}Buon lavoro con EventsMaster!${NC}"
echo ""

# Salva credenziali in un file
CREDENTIALS_FILE="/root/eventsmaster_credentials.txt"
cat > "$CREDENTIALS_FILE" <<CREDENTIALS
EventsMaster - Credenziali di Accesso
=====================================
Data installazione: $(date)

URL: http://${DOMAIN_OR_IP}
Directory: ${PROJECT_DIR}

Database MySQL
--------------
Database: ${DB_NAME}
Utente:   ${DB_USER}
Password: ${DB_PASSWORD}
Host:     localhost

MySQL Root
----------
Password: ${MYSQL_ROOT_PASSWORD}

File configurazione: ${PROJECT_DIR}/config/.env
CREDENTIALS

chmod 600 "$CREDENTIALS_FILE"
echo -e "${GREEN}Credenziali salvate in: ${CREDENTIALS_FILE}${NC}"
echo ""
