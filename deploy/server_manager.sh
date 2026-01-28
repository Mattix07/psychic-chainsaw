#!/bin/bash
###############################################################################
# EventsMaster - Server Manager
# Menu interattivo per la gestione completa del server
###############################################################################

set -e

# Colori
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configurazioni
PROJECT_DIR="/var/www/eventsmaster"
DB_NAME="5cit_eventsMaster"
DEBIAN_CNF="/etc/mysql/debian.cnf"
APACHE_SITES_DIR="/etc/apache2/sites-available"
APACHE_ENABLED_DIR="/etc/apache2/sites-enabled"

# Funzioni di utilità
log() { echo -e "${GREEN}[✓]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; }
warning() { echo -e "${YELLOW}[!]${NC} $1"; }
info() { echo -e "${BLUE}[i]${NC} $1"; }

# Verifica whiptail installato
if ! command -v whiptail &> /dev/null; then
    echo "Installazione whiptail..."
    apt-get update -qq && apt-get install -y whiptail
fi

###############################################################################
# FUNZIONI GIT
###############################################################################

configure_git() {
    CURRENT_USER=$(cd "$PROJECT_DIR" && git config user.name 2>/dev/null || echo "Non configurato")
    CURRENT_EMAIL=$(cd "$PROJECT_DIR" && git config user.email 2>/dev/null || echo "Non configurato")

    USER_NAME=$(whiptail --inputbox "Nome utente Git:\n(Attuale: $CURRENT_USER)" 10 60 "$CURRENT_USER" --title "Configurazione Git" 3>&1 1>&2 2>&3)

    if [ -n "$USER_NAME" ]; then
        USER_EMAIL=$(whiptail --inputbox "Email Git:\n(Attuale: $CURRENT_EMAIL)" 10 60 "$CURRENT_EMAIL" --title "Configurazione Git" 3>&1 1>&2 2>&3)

        if [ -n "$USER_EMAIL" ]; then
            cd "$PROJECT_DIR"
            git config user.name "$USER_NAME"
            git config user.email "$USER_EMAIL"
            git config --global --add safe.directory "$PROJECT_DIR"

            whiptail --msgbox "Configurazione Git aggiornata:\n\nNome: $USER_NAME\nEmail: $USER_EMAIL" 10 60 --title "Successo"
        fi
    fi
}

configure_git_credentials() {
    REPO_TYPE=$(whiptail --menu "Seleziona repository:" 15 60 2 \
        "1" "GitLab" \
        "2" "GitHub" \
        --title "Configura Credenziali Git" 3>&1 1>&2 2>&3)

    if [ -n "$REPO_TYPE" ]; then
        USERNAME=$(whiptail --inputbox "Username:" 10 60 --title "Credenziali Git" 3>&1 1>&2 2>&3)

        if [ -n "$USERNAME" ]; then
            TOKEN=$(whiptail --passwordbox "Personal Access Token / Password:" 10 60 --title "Credenziali Git" 3>&1 1>&2 2>&3)

            if [ -n "$TOKEN" ]; then
                cd "$PROJECT_DIR"
                CURRENT_REMOTE=$(git remote get-url origin)

                if [ "$REPO_TYPE" = "1" ]; then
                    # GitLab
                    NEW_REMOTE="https://${USERNAME}:${TOKEN}@gitlab.com/boscomattia6/eventsmaster.git"
                else
                    # GitHub
                    NEW_REMOTE="https://${USERNAME}:${TOKEN}@github.com/Mattix07/psychic-chainsaw.git"
                fi

                git remote set-url origin "$NEW_REMOTE"
                whiptail --msgbox "Credenziali configurate con successo!" 8 60 --title "Successo"
            fi
        fi
    fi
}

sync_repository() {
    cd "$PROJECT_DIR"

    if whiptail --yesno "Questo aggiornerà il codice alla versione più recente dal repository.\n\nContinuare?" 10 60 --title "Conferma Sincronizzazione"; then
        {
            echo "10" ; echo "XXX" ; echo "Fetch da repository remoto..." ; echo "XXX"
            git fetch origin 2>&1 | tee /tmp/git_output

            echo "50" ; echo "XXX" ; echo "Reset alla versione main..." ; echo "XXX"
            git reset --hard origin/main 2>&1 | tee -a /tmp/git_output

            echo "80" ; echo "XXX" ; echo "Aggiornamento permessi..." ; echo "XXX"
            chown -R www-data:www-data .

            echo "100" ; echo "XXX" ; echo "Completato!" ; echo "XXX"
            sleep 1
        } | whiptail --gauge "Sincronizzazione repository in corso..." 8 70 0

        LAST_COMMIT=$(git log -1 --pretty=format:'%h - %s (%cr)')
        whiptail --msgbox "Repository aggiornato con successo!\n\nUltimo commit:\n$LAST_COMMIT" 12 70 --title "Successo"

        systemctl restart apache2
    fi
}

show_git_status() {
    cd "$PROJECT_DIR"
    STATUS=$(git status --short)
    BRANCH=$(git branch --show-current)
    LAST_COMMIT=$(git log -1 --pretty=format:'%h - %s (%cr by %an)')
    REMOTE=$(git remote get-url origin | sed 's/:[^:]*@/@/g')  # Nasconde password

    whiptail --msgbox "Repository: $PROJECT_DIR\n\nBranch: $BRANCH\nRemote: $REMOTE\n\nUltimo commit:\n$LAST_COMMIT\n\nFile modificati:\n${STATUS:-Nessuna modifica}" 20 80 --title "Git Status"
}

###############################################################################
# FUNZIONI DATABASE
###############################################################################

backup_database() {
    BACKUP_DIR="/root/backups/eventmaster"
    mkdir -p "$BACKUP_DIR"

    TIMESTAMP=$(date +%Y%m%d_%H%M%S)
    BACKUP_FILE="$BACKUP_DIR/db_backup_$TIMESTAMP.sql"

    if whiptail --yesno "Creare backup del database?\n\nVerrà salvato in:\n$BACKUP_FILE" 12 70 --title "Backup Database"; then
        {
            echo "50" ; echo "XXX" ; echo "Creazione backup in corso..." ; echo "XXX"
            mysqldump --defaults-file="$DEBIAN_CNF" "$DB_NAME" > "$BACKUP_FILE"
            gzip "$BACKUP_FILE"
            echo "100" ; echo "XXX" ; echo "Backup completato!" ; echo "XXX"
            sleep 1
        } | whiptail --gauge "Backup database..." 8 70 0

        SIZE=$(du -h "${BACKUP_FILE}.gz" | cut -f1)
        whiptail --msgbox "Backup creato con successo!\n\nFile: ${BACKUP_FILE}.gz\nDimensione: $SIZE" 10 70 --title "Successo"
    fi
}

restore_database() {
    BACKUP_DIR="/root/backups/eventmaster"

    if [ ! -d "$BACKUP_DIR" ] || [ -z "$(ls -A $BACKUP_DIR)" ]; then
        whiptail --msgbox "Nessun backup trovato in $BACKUP_DIR" 8 60 --title "Errore"
        return
    fi

    # Lista backup disponibili
    BACKUPS=$(ls -1t "$BACKUP_DIR"/*.sql.gz 2>/dev/null | head -10)
    MENU_ITEMS=()
    COUNT=1

    while IFS= read -r backup; do
        FILENAME=$(basename "$backup")
        SIZE=$(du -h "$backup" | cut -f1)
        MENU_ITEMS+=("$COUNT" "$FILENAME ($SIZE)")
        ((COUNT++))
    done <<< "$BACKUPS"

    if [ ${#MENU_ITEMS[@]} -eq 0 ]; then
        whiptail --msgbox "Nessun backup trovato" 8 60 --title "Errore"
        return
    fi

    SELECTION=$(whiptail --menu "Seleziona backup da ripristinare:" 20 80 10 "${MENU_ITEMS[@]}" --title "Restore Database" 3>&1 1>&2 2>&3)

    if [ -n "$SELECTION" ]; then
        BACKUP_FILE=$(echo "$BACKUPS" | sed -n "${SELECTION}p")

        if whiptail --yesno "ATTENZIONE: Questo sovrascriverà il database corrente!\n\nFile: $(basename $BACKUP_FILE)\n\nContinuare?" 12 70 --title "Conferma Restore" --defaultno; then
            {
                echo "30" ; echo "XXX" ; echo "Decompressione backup..." ; echo "XXX"
                gunzip -c "$BACKUP_FILE" > /tmp/restore.sql

                echo "60" ; echo "XXX" ; echo "Ripristino database..." ; echo "XXX"
                mysql --defaults-file="$DEBIAN_CNF" "$DB_NAME" < /tmp/restore.sql

                echo "90" ; echo "XXX" ; echo "Pulizia..." ; echo "XXX"
                rm /tmp/restore.sql

                echo "100" ; echo "XXX" ; echo "Completato!" ; echo "XXX"
                sleep 1
            } | whiptail --gauge "Ripristino database..." 8 70 0

            whiptail --msgbox "Database ripristinato con successo!" 8 60 --title "Successo"
        fi
    fi
}

update_database_from_latest() {
    DB_DIR="$PROJECT_DIR/db"

    # Lista file SQL disponibili
    SQL_FILES=$(ls -1 "$DB_DIR"/*.sql 2>/dev/null | grep -v migrations)

    if [ -z "$SQL_FILES" ]; then
        whiptail --msgbox "Nessun file SQL trovato in $DB_DIR" 10 70 --title "Errore"
        return
    fi

    MENU_ITEMS=()
    COUNT=1
    while IFS= read -r sqlfile; do
        FILENAME=$(basename "$sqlfile")
        SIZE=$(du -h "$sqlfile" | cut -f1)
        MENU_ITEMS+=("$COUNT" "$FILENAME ($SIZE)")
        ((COUNT++))
    done <<< "$SQL_FILES"

    SELECTION=$(whiptail --menu "Seleziona file SQL per aggiornare il database:\n(Sovrascriverà tutti i dati!)" 22 80 12 "${MENU_ITEMS[@]}" --title "Aggiorna Database" 3>&1 1>&2 2>&3)

    if [ -n "$SELECTION" ]; then
        SQL_FILE=$(echo "$SQL_FILES" | sed -n "${SELECTION}p")
        FILENAME=$(basename "$SQL_FILE")

        if whiptail --yesno "ATTENZIONE: Questo sovrascriverà TUTTO il database!\n\nFile: $FILENAME\n\nVuoi creare un backup prima?" 14 70 --title "Backup Prima?" --defaultno; then
            backup_database
        fi

        if whiptail --yesno "Procedere con l'aggiornamento del database?\n\nFile: $FILENAME\n\nQuesta operazione NON può essere annullata!" 14 70 --title "Conferma Finale" --defaultno; then
            {
                echo "10" ; echo "XXX" ; echo "Fix case sensitivity tabelle..." ; echo "XXX"
                # Fix automatico case sensitivity per export da Windows/phpMyAdmin
                FIXED_SQL="/tmp/db_import_fixed.sql"
                sed -e 's/`biglietti`/`Biglietti`/g' \
                    -e 's/`collaboratorieventi`/`CollaboratoriEventi`/g' \
                    -e 's/`creatorieventi`/`CreatoriEventi`/g' \
                    -e 's/`creatorilocations`/`CreatoriLocations`/g' \
                    -e 's/`creatorimanifestazioni`/`CreatoriManifestazioni`/g' \
                    -e 's/`eventi`/`Eventi`/g' \
                    -e 's/`eventisettori`/`EventiSettori`/g' \
                    -e 's/`evento_intrattenitore`/`Evento_Intrattenitore`/g' \
                    -e 's/`intrattenitore`/`Intrattenitore`/g' \
                    -e 's/`locations`/`Locations`/g' \
                    -e 's/`manifestazioni`/`Manifestazioni`/g' \
                    -e 's/`notifiche`/`Notifiche`/g' \
                    -e 's/`ordine_biglietti`/`Ordine_Biglietti`/g' \
                    -e 's/`ordini`/`Ordini`/g' \
                    -e 's/`recensioni`/`Recensioni`/g' \
                    -e 's/`settore_biglietti`/`Settore_Biglietti`/g' \
                    -e 's/`settori`/`Settori`/g' \
                    -e 's/`tipo`/`Tipo`/g' \
                    -e 's/`utente_ordini`/`Utente_Ordini`/g' \
                    -e 's/`utenti`/`Utenti`/g' \
                    "$SQL_FILE" > "$FIXED_SQL"

                echo "25" ; echo "XXX" ; echo "Drop database esistente..." ; echo "XXX"
                mysql --defaults-file="$DEBIAN_CNF" -e "DROP DATABASE IF EXISTS $DB_NAME;" 2>&1

                echo "45" ; echo "XXX" ; echo "Creazione nuovo database..." ; echo "XXX"
                mysql --defaults-file="$DEBIAN_CNF" -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1

                echo "65" ; echo "XXX" ; echo "Importazione schema e dati..." ; echo "XXX"
                mysql --defaults-file="$DEBIAN_CNF" "$DB_NAME" < "$FIXED_SQL" 2>&1 | tee /tmp/db_update_output

                echo "95" ; echo "XXX" ; echo "Pulizia file temporanei..." ; echo "XXX"
                rm -f "$FIXED_SQL"

                echo "100" ; echo "XXX" ; echo "Completato!" ; echo "XXX"
                sleep 1
            } | whiptail --gauge "Aggiornamento database in corso..." 8 70 0

            if [ $? -eq 0 ]; then
                # Mostra statistiche nuovo database
                STATS=$(mysql --defaults-file="$DEBIAN_CNF" -e "
                    USE $DB_NAME;
                    SELECT 'Eventi' as Tabella, COUNT(*) as Totale FROM Eventi
                    UNION ALL SELECT 'Utenti', COUNT(*) FROM Utenti
                    UNION ALL SELECT 'Locations', COUNT(*) FROM Locations;
                " 2>/dev/null)

                whiptail --msgbox "Database aggiornato con successo!\n\nStatistiche:\n$STATS" 16 60 --title "Successo"
            else
                whiptail --msgbox "Errore durante l'aggiornamento.\n\nVedi: /tmp/db_update_output" 10 70 --title "Errore"
            fi
        fi
    fi
}

update_database_schema() {
    MIGRATIONS_DIR="$PROJECT_DIR/db/migrations"

    if [ ! -d "$MIGRATIONS_DIR" ]; then
        whiptail --msgbox "Directory migrations non trovata:\n$MIGRATIONS_DIR" 10 70 --title "Errore"
        return
    fi

    MIGRATIONS=$(ls -1 "$MIGRATIONS_DIR"/*.sql 2>/dev/null)

    if [ -z "$MIGRATIONS" ]; then
        whiptail --msgbox "Nessuna migration trovata" 8 60 --title "Info"
        return
    fi

    MENU_ITEMS=()
    COUNT=1
    while IFS= read -r migration; do
        FILENAME=$(basename "$migration")
        MENU_ITEMS+=("$COUNT" "$FILENAME")
        ((COUNT++))
    done <<< "$MIGRATIONS"

    SELECTION=$(whiptail --menu "Seleziona migration da applicare:" 20 80 10 "${MENU_ITEMS[@]}" --title "Update Database Schema" --cancel-button "Indietro" 3>&1 1>&2 2>&3)

    if [ -n "$SELECTION" ]; then
        MIGRATION_FILE=$(echo "$MIGRATIONS" | sed -n "${SELECTION}p")

        if whiptail --yesno "Applicare migration:\n$(basename $MIGRATION_FILE)\n\nContinuare?" 12 70 --title "Conferma"; then
            mysql --defaults-file="$DEBIAN_CNF" "$DB_NAME" < "$MIGRATION_FILE" 2>&1 | tee /tmp/migration_output

            if [ $? -eq 0 ]; then
                whiptail --msgbox "Migration applicata con successo!" 8 60 --title "Successo"
            else
                whiptail --msgbox "Errore durante l'applicazione della migration.\n\nVedi: /tmp/migration_output" 10 70 --title "Errore"
            fi
        fi
    fi
}

show_database_stats() {
    STATS=$(mysql --defaults-file="$DEBIAN_CNF" -e "
        USE $DB_NAME;
        SELECT 'Eventi' as Tabella, COUNT(*) as Totale FROM Eventi
        UNION ALL
        SELECT 'Utenti', COUNT(*) FROM Utenti
        UNION ALL
        SELECT 'Locations', COUNT(*) FROM Locations
        UNION ALL
        SELECT 'Biglietti', COUNT(*) FROM Biglietti
        UNION ALL
        SELECT 'Ordini', COUNT(*) FROM Ordini
        UNION ALL
        SELECT 'Recensioni', COUNT(*) FROM Recensioni;
    " 2>/dev/null)

    whiptail --msgbox "Statistiche Database: $DB_NAME\n\n$STATS" 18 60 --title "Database Stats"
}

###############################################################################
# FUNZIONI GESTIONE SITI
###############################################################################

list_sites() {
    # Lista tutti i siti disponibili
    SITES=()
    for site in "$APACHE_SITES_DIR"/*.conf; do
        SITENAME=$(basename "$site" .conf)
        if [ -L "$APACHE_ENABLED_DIR/$SITENAME.conf" ]; then
            STATUS="✓ ATTIVO"
        else
            STATUS="✗ DISABILITATO"
        fi
        SITES+=("$SITENAME" "$STATUS")
    done

    SELECTED=$(whiptail --menu "Siti Apache disponibili:" 20 80 10 "${SITES[@]}" --title "Gestione Siti" 3>&1 1>&2 2>&3)

    if [ -n "$SELECTED" ]; then
        manage_site "$SELECTED"
    fi
}

manage_site() {
    SITE=$1

    if [ -L "$APACHE_ENABLED_DIR/$SITE.conf" ]; then
        CURRENT_STATUS="ATTIVO"
        ACTION="Disabilita"
    else
        CURRENT_STATUS="DISABILITATO"
        ACTION="Abilita"
    fi

    CHOICE=$(whiptail --menu "Sito: $SITE\nStato: $CURRENT_STATUS\n\nCosa vuoi fare?" 18 70 4 \
        "1" "$ACTION sito" \
        "2" "Visualizza configurazione" \
        "3" "Modifica configurazione" \
        "4" "Riavvia Apache" \
        --title "Gestione Sito: $SITE" 3>&1 1>&2 2>&3)

    case $CHOICE in
        1)
            if [ "$CURRENT_STATUS" = "ATTIVO" ]; then
                a2dissite "$SITE"
                systemctl reload apache2
                whiptail --msgbox "Sito $SITE disabilitato" 8 60 --title "Successo"
            else
                a2ensite "$SITE"
                systemctl reload apache2
                whiptail --msgbox "Sito $SITE abilitato" 8 60 --title "Successo"
            fi
            ;;
        2)
            whiptail --textbox "$APACHE_SITES_DIR/$SITE.conf" 30 100 --title "Configurazione: $SITE"
            ;;
        3)
            nano "$APACHE_SITES_DIR/$SITE.conf"
            if whiptail --yesno "Riavviare Apache per applicare le modifiche?" 8 60 --title "Conferma"; then
                apache2ctl configtest && systemctl restart apache2
                whiptail --msgbox "Apache riavviato" 8 60 --title "Successo"
            fi
            ;;
        4)
            systemctl restart apache2
            whiptail --msgbox "Apache riavviato" 8 60 --title "Successo"
            ;;
    esac
}

enable_streaming_site() {
    # Lista progetti disponibili in /var/www
    PROJECTS=()
    for dir in /var/www/*/; do
        PROJECT=$(basename "$dir")
        if [ "$PROJECT" != "html" ]; then
            # Verifica se ha index.php o public/index.php
            if [ -f "/var/www/$PROJECT/index.php" ] || [ -f "/var/www/$PROJECT/public/index.php" ]; then
                PROJECTS+=("$PROJECT" "")
            fi
        fi
    done

    if [ ${#PROJECTS[@]} -eq 0 ]; then
        whiptail --msgbox "Nessun progetto trovato in /var/www" 8 60 --title "Info"
        return
    fi

    SELECTED=$(whiptail --menu "Seleziona progetto da streammare sulla rete locale:" 20 80 10 "${PROJECTS[@]}" --title "Streaming Locale" 3>&1 1>&2 2>&3)

    if [ -n "$SELECTED" ]; then
        # Determina DocumentRoot
        if [ -f "/var/www/$SELECTED/public/index.php" ]; then
            DOC_ROOT="/var/www/$SELECTED/public"
        else
            DOC_ROOT="/var/www/$SELECTED"
        fi

        # Crea virtual host temporaneo
        cat > "$APACHE_SITES_DIR/${SELECTED}-local.conf" <<EOF
<VirtualHost *:80>
    ServerName 192.168.1.50
    DocumentRoot $DOC_ROOT
    DirectoryIndex index.php index.html

    <Directory $DOC_ROOT>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/${SELECTED}_error.log
    CustomLog \${APACHE_LOG_DIR}/${SELECTED}_access.log combined
</VirtualHost>
EOF

        # Disabilita tutti gli altri siti
        for site in "$APACHE_ENABLED_DIR"/*.conf; do
            SITENAME=$(basename "$site" .conf)
            a2dissite "$SITENAME" 2>/dev/null
        done

        # Abilita il nuovo sito
        a2ensite "${SELECTED}-local"
        systemctl reload apache2

        whiptail --msgbox "Sito $SELECTED ora in streaming su:\nhttp://192.168.1.50" 10 60 --title "Successo"
    fi
}

###############################################################################
# FUNZIONI SISTEMA
###############################################################################

show_system_status() {
    APACHE_STATUS=$(systemctl is-active apache2)
    MYSQL_STATUS=$(systemctl is-active mysql)
    DISK_USAGE=$(df -h / | tail -1 | awk '{print $5}')
    MEMORY=$(free -h | grep Mem | awk '{print $3 "/" $2}')
    UPTIME=$(uptime -p)

    whiptail --msgbox "Stato Sistema\n\nApache: $APACHE_STATUS\nMySQL: $MYSQL_STATUS\n\nDisco: $DISK_USAGE utilizzato\nRAM: $MEMORY\nUptime: $UPTIME" 14 60 --title "System Status"
}

view_logs() {
    LOG_CHOICE=$(whiptail --menu "Seleziona log da visualizzare:" 15 70 5 \
        "1" "Apache Error Log (eventsmaster)" \
        "2" "Apache Access Log (eventsmaster)" \
        "3" "MySQL Error Log" \
        "4" "System Log (syslog)" \
        "5" "Apache Error Log (generale)" \
        --title "Visualizza Log" 3>&1 1>&2 2>&3)

    case $LOG_CHOICE in
        1) tail -100 /var/log/apache2/eventsmaster_error.log | whiptail --textbox /dev/stdin 30 100 --title "Apache Error Log" ;;
        2) tail -100 /var/log/apache2/eventsmaster_access.log | whiptail --textbox /dev/stdin 30 100 --title "Apache Access Log" ;;
        3) tail -100 /var/log/mysql/error.log | whiptail --textbox /dev/stdin 30 100 --title "MySQL Error Log" ;;
        4) tail -100 /var/log/syslog | whiptail --textbox /dev/stdin 30 100 --title "System Log" ;;
        5) tail -100 /var/log/apache2/error.log | whiptail --textbox /dev/stdin 30 100 --title "Apache Error Log" ;;
    esac
}

###############################################################################
# MENU PRINCIPALE
###############################################################################

main_menu() {
    while true; do
        CHOICE=$(whiptail --menu "EventsMaster - Server Manager\n\nSeleziona operazione:" 25 80 15 \
            "1" "📁 Gestione Git" \
            "2" "💾 Gestione Database" \
            "3" "🌐 Gestione Siti Web" \
            "4" "📊 Stato Sistema" \
            "5" "📝 Visualizza Log" \
            "6" "🔄 Riavvia Servizi" \
            "0" "Esci" \
            --title "Menu Principale" 3>&1 1>&2 2>&3)

        case $CHOICE in
            1) git_menu ;;
            2) database_menu ;;
            3) sites_menu ;;
            4) show_system_status ;;
            5) view_logs ;;
            6) services_menu ;;
            0) exit 0 ;;
            *) exit 0 ;;
        esac
    done
}

git_menu() {
    while true; do
        CHOICE=$(whiptail --menu "Gestione Git" 18 70 6 \
            "1" "Sincronizza repository" \
            "2" "Configura utente Git" \
            "3" "Configura credenziali (GitLab/GitHub)" \
            "4" "Visualizza stato repository" \
            "5" "Torna al menu principale" \
            --title "Git Menu" --cancel-button "Indietro" 3>&1 1>&2 2>&3)

        case $CHOICE in
            1) sync_repository ;;
            2) configure_git ;;
            3) configure_git_credentials ;;
            4) show_git_status ;;
            5|"") return ;;
        esac
    done
}

database_menu() {
    while true; do
        CHOICE=$(whiptail --menu "Gestione Database" 20 70 7 \
            "1" "Backup database" \
            "2" "Ripristina database" \
            "3" "Aggiorna con versione più recente (SQL)" \
            "4" "Aggiorna schema (migrations)" \
            "5" "Visualizza statistiche" \
            "6" "Torna al menu principale" \
            --title "Database Menu" --cancel-button "Indietro" 3>&1 1>&2 2>&3)

        case $CHOICE in
            1) backup_database ;;
            2) restore_database ;;
            3) update_database_from_latest ;;
            4) update_database_schema ;;
            5) show_database_stats ;;
            6|"") return ;;
        esac
    done
}

sites_menu() {
    while true; do
        CHOICE=$(whiptail --menu "Gestione Siti Web" 18 70 5 \
            "1" "Abilita/Disabilita sito" \
            "2" "Scegli sito per streaming locale" \
            "3" "Lista tutti i siti" \
            "4" "Torna al menu principale" \
            --title "Sites Menu" --cancel-button "Indietro" 3>&1 1>&2 2>&3)

        case $CHOICE in
            1) list_sites ;;
            2) enable_streaming_site ;;
            3) list_sites ;;
            4|"") return ;;
        esac
    done
}

services_menu() {
    while true; do
        CHOICE=$(whiptail --menu "Riavvia Servizi" 15 70 4 \
            "1" "Riavvia Apache" \
            "2" "Riavvia MySQL" \
            "3" "Riavvia tutto" \
            "4" "Torna al menu principale" \
            --title "Services Menu" --cancel-button "Indietro" 3>&1 1>&2 2>&3)

        case $CHOICE in
            1)
                systemctl restart apache2
                whiptail --msgbox "Apache riavviato" 8 60 --title "Successo"
                ;;
            2)
                systemctl restart mysql
                whiptail --msgbox "MySQL riavviato" 8 60 --title "Successo"
                ;;
            3)
                systemctl restart apache2
                systemctl restart mysql
                whiptail --msgbox "Tutti i servizi riavviati" 8 60 --title "Successo"
                ;;
            4|"") return ;;
        esac
    done
}

# Avvio menu principale
main_menu
