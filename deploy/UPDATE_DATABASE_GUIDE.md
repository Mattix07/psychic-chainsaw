# Guida Aggiornamento Database

## 📦 File più recente caricato

**File**: `latest_export.sql`
**Data**: 29/01/2026 00:17
**Dimensione**: 29KB
**Posizione server**: `/var/www/eventsmaster/db/latest_export.sql`

---

## 🚀 Aggiornamento Rapido

### Metodo 1: Tramite Menu (CONSIGLIATO)

```bash
ssh root@192.168.1.50
sm
```

Poi:
1. Seleziona: **2** (Gestione Database)
2. Seleziona: **3** (Aggiorna con versione più recente)
3. Scegli: **latest_export.sql (29K)**
4. Conferma backup (consigliato: **Sì**)
5. Conferma aggiornamento
6. Attendi completamento (~10 secondi)
7. Visualizza statistiche nuovo database

---

### Metodo 2: Manuale da SSH

```bash
ssh root@192.168.1.50

# Backup opzionale
mysqldump --defaults-file=/etc/mysql/debian.cnf 5cit_eventsMaster > /root/backup_pre_update.sql

# Drop e ricrea database
mysql --defaults-file=/etc/mysql/debian.cnf -e "DROP DATABASE IF EXISTS 5cit_eventsMaster;"
mysql --defaults-file=/etc/mysql/debian.cnf -e "CREATE DATABASE 5cit_eventsMaster CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importa nuovo database
mysql --defaults-file=/etc/mysql/debian.cnf 5cit_eventsMaster < /var/www/eventsmaster/db/latest_export.sql

# Verifica
mysql --defaults-file=/etc/mysql/debian.cnf -e "USE 5cit_eventsMaster; SELECT COUNT(*) FROM Utenti; SELECT COUNT(*) FROM Eventi;"
```

---

## 📤 Aggiungere Nuovi Export

### Dal tuo PC Windows

```bash
# Trova ultimo export
cd C:\Users\bosco\Downloads

# Trasferisci al server
scp "nuovo_export.sql" root@192.168.1.50:/var/www/eventsmaster/db/
```

Oppure usa il file già presente in:
```
c:\xampp\htdocs\eventsMaster\db\
```

---

## 🔄 Workflow Tipico

### Scenario: Hai modificato il database in locale e vuoi aggiornare il server

1. **Esporta da phpMyAdmin locale**
   - Vai su http://localhost/phpmyadmin
   - Seleziona database `5cit_eventsmaster`
   - Export → SQL → Esporta
   - Salva in Downloads

2. **Trasferisci al server**
   ```bash
   cd c:\xampp\htdocs\eventsMaster\deploy
   scp "C:\Users\bosco\Downloads\5cit_eventsmaster.sql" root@192.168.1.50:/var/www/eventsmaster/db/latest_export.sql
   ```

3. **Aggiorna tramite menu**
   ```bash
   ssh root@192.168.1.50
   sm
   → 2 (Database)
   → 3 (Aggiorna con versione più recente)
   → latest_export.sql
   ```

4. **Verifica sul sito**
   - Apri http://192.168.1.50
   - Verifica che i dati siano aggiornati

---

## ⚠️ Note Importanti

### Backup Automatico
Il menu offre sempre un backup prima dell'aggiornamento. **Accetta sempre il backup!**

### Operazione Distruttiva
L'aggiornamento **CANCELLA** tutti i dati esistenti e li sostituisce con quelli del file SQL.

### File SQL Supportati
Il server riconosce automaticamente tutti i file `.sql` in:
- `/var/www/eventsmaster/db/`
- Esclusa la cartella `migrations/`

### Cosa Include latest_export.sql
- ✅ Schema completo (tutte le tabelle)
- ✅ Dati (eventi, utenti, locations, ecc.)
- ✅ Indici e chiavi esterne
- ✅ Vincoli di integrità

---

## 🎯 File SQL Disponibili

Dopo il trasferimento, sul server sono disponibili:

```
/var/www/eventsmaster/db/
├── latest_export.sql           ← Versione più recente (29/01/2026)
├── install_complete.sql        ← Installazione completa con dati demo
├── schema_completo.sql         ← Solo schema senza dati
├── dump_extended.sql           ← Dump esteso
├── 5cit_eventsMaster.sql       ← Versione base
└── migrations/                 ← Migration incrementali
    ├── 001_add_collaboration_system.sql
    └── add_categoria_to_eventi.sql
```

---

## 📊 Verifica Post-Aggiornamento

Dopo l'aggiornamento, verifica:

1. **Numero record**
   ```bash
   sm → 2 → 5 (Visualizza statistiche)
   ```

2. **Funzionamento sito**
   - Apri http://192.168.1.50
   - Verifica homepage carica
   - Verifica eventi mostrati
   - Test login (se hai utenti test)

3. **Log errori**
   ```bash
   sm → 5 (Visualizza Log) → 1 (Apache Error Log)
   ```

---

## 🔧 Troubleshooting

### Errore durante l'import

**Sintomo**: Messaggio di errore durante l'aggiornamento

**Soluzione**:
```bash
# Verifica file SQL
cat /var/www/eventsmaster/db/latest_export.sql | head -50

# Controlla log
cat /tmp/db_update_output

# Ripristina backup
sm → 2 → 2 (Ripristina database)
```

### Database vuoto dopo aggiornamento

**Sintomo**: Il sito non mostra eventi/utenti

**Causa**: File SQL potrebbe essere solo schema senza dati

**Soluzione**: Usa `install_complete.sql` invece di `latest_export.sql`

### Errore "Access denied"

**Sintomo**: MySQL rifiuta la connessione

**Soluzione**:
```bash
# Verifica credenziali
cat /var/www/eventsmaster/.env | grep DB_

# Verifica utente MySQL
mysql --defaults-file=/etc/mysql/debian.cnf -e "SELECT User, Host FROM mysql.user;"
```

---

## 📅 Cronologia Aggiornamenti

| Data | File | Descrizione |
|------|------|-------------|
| 29/01/2026 | latest_export.sql | Export più recente caricato |
| 28/01/2026 | install_complete.sql | Installazione iniziale server |

---

## 💡 Best Practices

1. **Sempre backup prima dell'aggiornamento**
   - Il menu lo offre automaticamente
   - I backup sono salvati in `/root/backups/eventsmaster/`

2. **Testa prima in locale**
   - Importa il file SQL nel tuo localhost
   - Verifica che funzioni tutto
   - Poi aggiorna il server

3. **Documenta le modifiche**
   - Annota cosa è cambiato nel database
   - Utile per debug futuro

4. **Usa migrations per piccole modifiche**
   - Per aggiungere una colonna: crea migration
   - Per import completo: usa "Aggiorna con versione più recente"

---

## 🚨 Comandi di Emergenza

### Rollback veloce
```bash
# Lista backup disponibili
ls -lht /root/backups/eventsmaster/

# Ripristina ultimo backup
sm → 2 → 2 (Ripristina database) → Seleziona ultimo backup
```

### Reset completo a dati demo
```bash
ssh root@192.168.1.50
mysql --defaults-file=/etc/mysql/debian.cnf 5cit_eventsMaster < /var/www/eventsmaster/db/install_complete.sql
```

---

**Ultima modifica**: 29/01/2026
**Versione**: 1.0
