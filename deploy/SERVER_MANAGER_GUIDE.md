# EventsMaster - Server Manager Guide

## 🚀 Avvio Rapido

### Da SSH
Connettiti al server e lancia:
```bash
ssh root@192.168.1.50
server-manager
```

oppure usa l'alias breve:
```bash
sm
```

---

## 📋 Funzionalità Disponibili

### 1. 📁 Gestione Git

#### Sincronizza Repository
- Aggiorna il codice alla versione più recente
- Fa reset hard a `origin/main`
- Ripristina permessi corretti
- Riavvia Apache automaticamente

#### Configura Utente Git
- Imposta nome e email per i commit
- Configurazione locale al progetto

#### Configura Credenziali (GitLab/GitHub)
- Salva username e Personal Access Token
- Supporta sia GitLab che GitHub
- Permette push/pull senza inserire password ogni volta

#### Visualizza Stato Repository
- Branch corrente
- Remote URL
- Ultimo commit
- File modificati

---

### 2. 💾 Gestione Database

#### Backup Database
- Crea backup compresso in `/root/backups/eventmaster/`
- Formato: `db_backup_YYYYMMDD_HHMMSS.sql.gz`
- Mostra dimensione file

#### Ripristina Database
- Lista ultimi 10 backup disponibili
- Mostra dimensione di ogni backup
- Conferma prima di sovrascrivere
- Ripristino completo del database

#### Aggiorna Schema (Migrations)
- Applica migration dalla cartella `db/migrations/`
- Selezione interattiva del file
- Conferma prima dell'applicazione

#### Visualizza Statistiche
- Numero eventi
- Numero utenti
- Numero locations
- Numero biglietti
- Numero ordini
- Numero recensioni

---

### 3. 🌐 Gestione Siti Web

#### Abilita/Disabilita Sito
- Lista tutti i siti Apache disponibili
- Mostra stato (ATTIVO/DISABILITATO)
- Abilita o disabilita con un click
- Visualizza/Modifica configurazione
- Test configurazione Apache

#### Scegli Sito per Streaming Locale ⭐
**Funzione principale per il multi-sito!**

- Scansiona tutti i progetti in `/var/www/`
- Seleziona quale progetto streammare su `http://192.168.1.50`
- Disabilita automaticamente gli altri siti
- Crea Virtual Host temporaneo
- Rileva automaticamente DocumentRoot (root o public/)

**Esempio:**
1. Vai su "Gestione Siti Web"
2. Seleziona "Scegli sito per streaming locale"
3. Scegli tra: `eventsmaster`, `licam`, ecc.
4. Il sito selezionato sarà immediatamente accessibile su `http://192.168.1.50`

#### Lista Tutti i Siti
- Visualizza tutti i Virtual Host configurati
- Gestione rapida per ogni sito

---

### 4. 📊 Stato Sistema

Visualizza:
- Stato Apache (active/inactive)
- Stato MySQL (active/inactive)
- Utilizzo disco
- Utilizzo RAM
- Uptime server

---

### 5. 📝 Visualizza Log

Accesso rapido ai log principali:
- Apache Error Log (eventsmaster)
- Apache Access Log (eventsmaster)
- MySQL Error Log
- System Log (syslog)
- Apache Error Log (generale)

Mostra ultime 100 righe in visualizzazione scrollabile.

---

### 6. 🔄 Riavvia Servizi

Riavvio rapido di:
- Apache
- MySQL
- Entrambi insieme

---

## 🎯 Casi d'Uso Comuni

### Cambio Progetto in Streaming

**Scenario:** Hai sia `eventsmaster` che `licam` sul server e vuoi switchare tra i due.

```bash
ssh root@192.168.1.50
sm
# Seleziona: 3 (Gestione Siti Web)
# Seleziona: 2 (Scegli sito per streaming locale)
# Seleziona il progetto desiderato
# Apri browser su http://192.168.1.50
```

### Aggiornamento Codice da Git

**Scenario:** Hai pushato modifiche su GitLab e vuoi aggiornarle sul server.

```bash
sm
# Seleziona: 1 (Gestione Git)
# Seleziona: 1 (Sincronizza repository)
# Conferma
```

### Backup Prima di Modifiche Importanti

**Scenario:** Prima di applicare una migration o modifiche importanti.

```bash
sm
# Seleziona: 2 (Gestione Database)
# Seleziona: 1 (Backup database)
# Il backup viene salvato automaticamente
```

### Configurare Credenziali Git per Push

**Scenario:** Vuoi poter fare push direttamente dal server.

```bash
sm
# Seleziona: 1 (Gestione Git)
# Seleziona: 3 (Configura credenziali GitLab/GitHub)
# Inserisci username
# Inserisci Personal Access Token
```

**Come ottenere Personal Access Token:**

**GitLab:**
1. GitLab → Settings → Access Tokens
2. Nome: "Server EventsMaster"
3. Scopes: `read_repository`, `write_repository`
4. Crea token e copialo

**GitHub:**
1. GitHub → Settings → Developer Settings → Personal Access Tokens
2. Generate new token (classic)
3. Scopes: `repo`
4. Crea e copia token

---

## 🛠️ Installazione

Il menu è già installato durante il deployment! Se serve reinstallarlo:

```bash
# Dal tuo PC Windows
cd c:\xampp\htdocs\eventsMaster\deploy
scp server_manager.sh root@192.168.1.50:/usr/local/bin/server-manager
ssh root@192.168.1.50 "chmod +x /usr/local/bin/server-manager"
```

---

## 📁 Percorsi Importanti

- **Menu script**: `/usr/local/bin/server-manager`
- **Progetto**: `/var/www/eventsmaster`
- **Backup**: `/root/backups/eventmaster/`
- **Credenziali**: `/root/eventsmaster_credentials.txt`
- **Apache sites**: `/etc/apache2/sites-available/`
- **Apache enabled**: `/etc/apache2/sites-enabled/`
- **Log Apache**: `/var/log/apache2/`
- **Config .env**: `/var/www/eventsmaster/.env`

---

## 🎨 Interfaccia

Il menu usa **whiptail** per un'interfaccia testuale interattiva con:
- ✅ Dialog box grafici
- 📋 Menu di selezione
- 📊 Progress bar per operazioni lunghe
- ⚠️ Conferme per operazioni critiche
- 📝 Visualizzazione testo scrollabile
- ✏️ Input box per inserimento dati

---

## 🔧 Troubleshooting

### Menu non si avvia
```bash
# Verifica installazione
which server-manager

# Reinstalla
scp server_manager.sh root@192.168.1.50:/usr/local/bin/server-manager
ssh root@192.168.1.50 "chmod +x /usr/local/bin/server-manager"
```

### Whiptail non installato
```bash
apt-get update && apt-get install -y whiptail
```

### Errore sincronizzazione Git
- Configura prima le credenziali nel menu Git
- Oppure fai pull manualmente con SSH

---

## 💡 Tips

1. **Alias rapido**: Usa `sm` invece di `server-manager`
2. **ESC per uscire**: Premi ESC per tornare indietro o uscire
3. **Backup automatici**: Fai sempre backup prima di restore o migration
4. **Multi-sito**: Puoi avere più progetti ma solo uno attivo alla volta su IP locale
5. **Log in tempo reale**: Per vedere log live usa: `tail -f /var/log/apache2/eventsmaster_error.log`

---

## 🚨 Operazioni Critiche

Queste operazioni richiedono conferma esplicita:
- ❌ Restore database (sovrascrive dati)
- ❌ Sincronizzazione repository (sovrascrive codice locale)
- ❌ Applicazione migration (modifica schema)

---

## 📞 Comandi Rapidi

```bash
# Avvia menu
sm

# Connetti SSH
ssh root@192.168.1.50

# Backup rapido (senza menu)
mysqldump --defaults-file=/etc/mysql/debian.cnf 5cit_eventsMaster | gzip > /root/backup_$(date +%Y%m%d).sql.gz

# Restart Apache rapido
systemctl restart apache2

# Status servizi
systemctl status apache2
systemctl status mysql
```

---

**Versione**: 1.0
**Data**: 2026-01-28
**Autore**: EventsMaster Deploy Team
