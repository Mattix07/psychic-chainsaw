#!/bin/bash
###############################################################################
# Installazione Cronjob Cleanup Eventi Vecchi
#
# Questo script installa un cronjob che esegue automaticamente
# la pulizia degli eventi vecchi ogni giorno alle 03:00
###############################################################################

set -e

# Colori
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${GREEN}==================================================${NC}"
echo -e "${GREEN}Installazione Cronjob Cleanup Eventi${NC}"
echo -e "${GREEN}==================================================${NC}"
echo ""

# Verifica che lo script sia eseguito come root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Questo script deve essere eseguito come root${NC}"
    exit 1
fi

PROJECT_DIR="/var/www/eventsmaster"
CRON_SCRIPT="${PROJECT_DIR}/cron/cleanup_old_events.php"
LOG_DIR="${PROJECT_DIR}/logs"

# Verifica che lo script esista
if [ ! -f "$CRON_SCRIPT" ]; then
    echo -e "${RED}Errore: Script cleanup non trovato in ${CRON_SCRIPT}${NC}"
    exit 1
fi

# Rendi eseguibile lo script
chmod +x "$CRON_SCRIPT"
echo -e "${GREEN}✓${NC} Script reso eseguibile"

# Crea directory log se non esiste
mkdir -p "$LOG_DIR"
chown -R www-data:www-data "$LOG_DIR"
chmod -R 755 "$LOG_DIR"
echo -e "${GREEN}✓${NC} Directory log creata/verificata"

# Verifica se il cronjob esiste già
CRON_EXISTS=$(crontab -u www-data -l 2>/dev/null | grep -c "cleanup_old_events.php" || true)

if [ "$CRON_EXISTS" -gt 0 ]; then
    echo -e "${YELLOW}⚠${NC}  Cronjob già esistente, aggiornamento..."
    # Rimuovi vecchio cronjob
    crontab -u www-data -l 2>/dev/null | grep -v "cleanup_old_events.php" | crontab -u www-data -
fi

# Aggiungi nuovo cronjob
(crontab -u www-data -l 2>/dev/null; echo "# Cleanup eventi vecchi (esegui ogni giorno alle 03:00)") | crontab -u www-data -
(crontab -u www-data -l 2>/dev/null; echo "0 3 * * * /usr/bin/php ${CRON_SCRIPT} >> ${LOG_DIR}/cleanup_events.log 2>&1") | crontab -u www-data -

echo -e "${GREEN}✓${NC} Cronjob installato con successo"
echo ""

# Mostra cronjob installati
echo -e "${GREEN}Cronjob attivi per www-data:${NC}"
crontab -u www-data -l

echo ""
echo -e "${GREEN}==================================================${NC}"
echo -e "${GREEN}Installazione Completata!${NC}"
echo -e "${GREEN}==================================================${NC}"
echo ""
echo -e "Lo script verrà eseguito automaticamente:"
echo -e "  ${YELLOW}⏰ Orario:${NC} Ogni giorno alle 03:00"
echo -e "  ${YELLOW}📝 Log:${NC}    ${LOG_DIR}/cleanup_events.log"
echo ""
echo -e "Cosa viene eliminato automaticamente:"
echo -e "  • Eventi finiti da più di 2 settimane"
echo -e "  • Biglietti associati agli eventi eliminati"
echo -e "  • Recensioni degli eventi eliminati"
echo -e "  • Manifestazioni finite (senza eventi attivi)"
echo -e "  • Ordini vuoti (senza biglietti)"
echo ""
echo -e "Per testare manualmente lo script:"
echo -e "  ${YELLOW}sudo -u www-data php ${CRON_SCRIPT}${NC}"
echo ""
echo -e "Per visualizzare il log:"
echo -e "  ${YELLOW}tail -f ${LOG_DIR}/cleanup_events.log${NC}"
echo ""
