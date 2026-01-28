# Quick Start - Deploy EventsMaster

## Metodo 1: Deploy Automatico da Windows (CONSIGLIATO)

### Requisiti
- Windows 10/11
- OpenSSH Client installato
- PowerShell

### Passi
1. Apri PowerShell come Amministratore
2. Naviga nella cartella deploy:
   ```powershell
   cd c:\xampp\htdocs\eventsMaster\deploy
   ```
3. Esegui lo script:
   ```powershell
   .\deploy_from_windows.ps1
   ```
4. Seleziona opzione **1** (Deploy completo)
5. Attendi 10-15 minuti
6. Fatto! Vai su http://192.168.1.50

---

## Metodo 2: Deploy Manuale da Server

### Connessione al server
```bash
ssh root@192.168.1.50
# Password: 2001
```

### Opzione A: Clone e deploy
```bash
# Clone repository
cd /tmp
git clone https://gitlab.com/boscomattia6/eventsmaster.git
cd eventsmaster/deploy

# Esegui script
chmod +x setup_server.sh
sudo bash setup_server.sh
```

### Opzione B: Download script diretto
```bash
# Download script
cd /tmp
wget https://gitlab.com/boscomattia6/eventsmaster/-/raw/main/deploy/setup_server.sh

# Esegui
chmod +x setup_server.sh
sudo bash setup_server.sh
```

### Al termine
- Salva le credenziali mostrate a schermo
- Trova credenziali in: `/root/eventsmaster_credentials.txt`
- Visita: http://192.168.1.50

---

## Metodo 3: Connessione Rapida da Windows

### Doppio click su:
```
deploy\connect_to_server.bat
```

Password: `2001`

---

## Dopo l'installazione

### 1. Configura Email
```bash
nano /var/www/eventsMaster/config/.env
```

Modifica:
```env
MAIL_USERNAME=tua_email@gmail.com
MAIL_PASSWORD=tua_app_password
```

### 2. Crea Admin
Accedi a http://192.168.1.50 e registrati come primo utente.

### 3. Test
- Homepage: http://192.168.1.50
- Login: http://192.168.1.50/login
- Registrazione: http://192.168.1.50/register

---

## Troubleshooting

### Server non raggiungibile
```bash
ping 192.168.1.50
```

### Script fallisce
Controlla log:
```bash
tail -f /var/log/apache2/eventsMaster_error.log
```

### Errore database
Verifica connessione:
```bash
mysql -u eventsmaster_user -p 5cit_eventsMaster
```

### Permessi file
Reset permessi:
```bash
cd /var/www/eventsMaster
chown -R www-data:www-data .
chmod -R 755 .
chmod -R 775 uploads/ logs/
```

---

## Comandi Utili

### Status servizi
```bash
systemctl status apache2
systemctl status mysql
```

### Log in tempo reale
```bash
tail -f /var/log/apache2/eventsMaster_error.log
```

### Backup database
```bash
mysqldump -u eventsmaster_user -p 5cit_eventsMaster > backup.sql
```

### Aggiornamento codice
```bash
cd /var/www/eventsMaster
sudo -u www-data git pull origin main
systemctl restart apache2
```

---

## Link Utili

- **Guida Completa**: [README_DEPLOYMENT.md](README_DEPLOYMENT.md)
- **Repository**: https://gitlab.com/boscomattia6/eventsmaster
- **GitHub Mirror**: https://github.com/Mattix07/psychic-chainsaw

---

## Credenziali Default

### Server
- **IP**: 192.168.1.50
- **User**: root
- **Password**: 2001

### Database (generate automaticamente)
Vedi: `/root/eventsmaster_credentials.txt`

### Admin (da creare)
Primo utente registrato o inserito manualmente nel DB.

---

**Supporto**: Consulta README_DEPLOYMENT.md per dettagli completi
