# 🚀 Quick Start - EventsMaster

Guida rapida per iniziare subito con EventsMaster.

## 📋 Prerequisiti

- ✅ **XAMPP** (o LAMP/MAMP) con PHP 8.0+ e MySQL 5.7+
- ✅ **Browser moderno** (Chrome, Firefox, Edge, Safari)
- ✅ **Git** (opzionale, per clonare il repository)

## ⚡ Setup Rapido (5 minuti)

### 1️⃣ Database

**Opzione A - Reset Automatico (Consigliata per sviluppo)**

Vai su: http://localhost/eventsMaster/db/reset_database.php

Clicca "SÌ, RESET DEL DATABASE" e attendi il completamento.

**Opzione B - Manuale (PHPMyAdmin)**

1. Apri PHPMyAdmin: http://localhost/phpmyadmin
2. Seleziona database `5cit_eventsMaster`
3. Tab "SQL"
4. Importa in ordine:
   - `db/migrations/001_add_collaboration_system.sql`
   - `db/reset_to_production_state.sql`
5. Clicca "Esegui"

### 2️⃣ Verifica Installazione

Vai su: http://localhost/eventsMaster/check_installation.php

Assicurati che tutti i check siano ✅ verdi.

### 3️⃣ Primo Accesso

Vai su: http://localhost/eventsMaster

**Utenti di test disponibili:**

| Email | Password | Ruolo | Cosa può fare |
|-------|----------|-------|---------------|
| `admin@eventsmaster.it` | `password123` | Admin | Tutto - gestione completa |
| `mod@eventsmaster.it` | `password123` | Moderatore | Moderazione contenuti |
| `promoter@eventsmaster.it` | `password123` | Promoter | Creare e gestire eventi |
| `user@eventsmaster.it` | `password123` | User | Acquistare biglietti |

## 🎯 Test Funzionalità Principali

### Come Admin

1. **Login**: `admin@eventsmaster.it` / `password123`
2. Verrai reindirizzato alla **Dashboard Admin**
3. Prova a:
   - ✅ Creare un nuovo evento
   - ✅ Selezionare settori disponibili
   - ✅ Gestire utenti
   - ✅ Verificare account non verificati
   - ✅ Eliminare recensioni/eventi

### Come Promoter

1. **Login**: `promoter@eventsmaster.it` / `password123`
2. Verrai reindirizzato alla **Dashboard Promoter**
3. Prova a:
   - ✅ Vedere i tuoi 7 eventi
   - ✅ Modificare un evento che hai creato
   - ✅ Invitare un collaboratore
   - ✅ Gestire settori per evento

### Come User

1. **Login**: `user@eventsmaster.it` / `password123`
2. Rimani sulla **Homepage**
3. Prova a:
   - ✅ Navigare tra gli eventi
   - ✅ Aggiungere biglietti al carrello
   - ✅ Selezionare settori diversi
   - ✅ Procedere al checkout
   - ✅ Completare un acquisto
   - ✅ Lasciare una recensione (solo per eventi recenti)
   - ✅ Caricare un avatar

## 📱 Test Mobile

1. Apri DevTools (F12)
2. Toggle device toolbar (Ctrl+Shift+M)
3. Seleziona un dispositivo mobile
4. Naviga il sito - tutto dovrebbe essere responsive!

## 🔍 Funzionalità Nuove da Testare

### ✨ Sistema Permessi

- **Admin/Mod** possono modificare tutti gli eventi
- **Promoter** può modificare solo eventi che ha creato o dove è collaboratore
- **User** non può modificare eventi

**Test**: Prova a modificare un evento con diversi ruoli

### 🤝 Collaborazioni

1. Login come **Promoter**
2. Vai a uno dei tuoi eventi
3. Invita un collaboratore (usa email di test: `test@example.com`)
4. Controlla la tabella `Notifiche` nel DB
5. Simula accettazione con link dell'invito

### 🎭 Settori Eventi

1. Crea un nuovo evento come **Admin/Promoter**
2. Nella sezione "Settori Disponibili" seleziona alcuni settori
3. Salva evento
4. Come **User**, aggiungi biglietto al carrello
5. Nel checkout, seleziona il settore desiderato
6. Verifica che il prezzo cambi in base al moltiplicatore

### 📧 Notifiche Email

Le email sono in modalità **LOG ONLY** (non inviate realmente).

Per vedere le "email":
```sql
SELECT * FROM Notifiche ORDER BY created_at DESC;
```

Per abilitare invio reale, modifica `lib/EmailService.php`:
```php
new EmailService($pdo, true); // true = invia email reali
```

### 🗑️ Auto-Eliminazione Eventi

Script cron che elimina eventi >14 giorni:

```bash
# Test manuale
cd C:\xampp\htdocs\eventsMaster
php cron/auto_delete_old_events.php
```

Verifica i log in `logs/cron_delete_events.log`

### 🖼️ Avatar Utente

1. Login come qualsiasi utente
2. Vai al profilo
3. Carica un avatar (max 2MB)
4. L'immagine viene ridimensionata automaticamente
5. Verifica nella colonna `Utenti.Avatar` (MEDIUMBLOB)

### ⏰ Recensioni Temporali

Le recensioni sono consentite solo **entro 14 giorni** dalla data evento.

**Test**:
1. Trova un evento passato recente (< 14 giorni)
2. Acquista biglietto come User
3. Lascia recensione ✅
4. Prova con evento >14 giorni → Bloccato ❌

### 🚫 Blocco Acquisto Organizzatori

**Admin, Mod, Promoter NON possono acquistare biglietti.**

**Test**:
1. Login come Admin
2. Aggiungi evento al carrello
3. Vai al checkout
4. Prova ad acquistare → Errore: "Gli organizzatori non possono acquistare biglietti" ❌

## 📊 Database Overview

Dopo il reset avrai:

- **4 utenti** (1 per ruolo)
- **15 locations** (stadi, teatri, club, arene)
- **15 settori** distribuiti tra le location
- **5 manifestazioni** (festival, rassegne)
- **16 intrattenitori** (artisti, band, compagnie)
- **20+ eventi futuri** (prossimi 6 mesi)
- **5 tipi biglietto** (Standard, VIP, Premium, Ridotto, Under18)
- **0 ordini** - database pulito!

## 🛠️ Sviluppo

### Reset Database Veloce

Durante lo sviluppo, per ripulire tutto:

```
http://localhost/eventsMaster/db/reset_database.php?confirm=yes
```

### Hot Reload CSS

I file CSS hanno cache-busting automatico con `?v=<?= time() ?>` quindi basta refreshare la pagina.

### Debug

Abilita errori PHP in `config/database.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

Controlla i log:
- `logs/error.log` - errori applicazione
- `logs/cron_delete_events.log` - log cron job

## 📚 Documentazione Completa

- **README.md** - Panoramica generale
- **TESTING.md** - 685 test case dettagliati
- **ISTRUZIONI_IMPLEMENTAZIONE.md** - Guida integrazione codice
- **README_DOCUMENTAZIONE_COMPLETA.md** - Documentazione tecnica completa
- **MOBILE_CSS_INTEGRATION.md** - Guida CSS responsive
- **SOMMARIO_MODIFICHE.md** - Riepilogo tutte le modifiche
- **db/README_RESET.md** - Guida reset database

## 🆘 Problemi Comuni

### "Table doesn't exist"
**Soluzione**: Esegui le migration prima del reset
```sql
db/migrations/001_add_collaboration_system.sql
```

### "File not found" per avatar/settori
**Soluzione**: Esegui lo script di verifica
```
http://localhost/eventsMaster/check_installation.php
```

### Eventi non visibili
**Soluzione**: Verifica che le date siano future. Se siamo oltre il 2026, aggiorna le date:
```sql
UPDATE Eventi SET Data = DATE_ADD(Data, INTERVAL 1 YEAR);
```

### CSS non caricato su mobile
**Soluzione**: Verifica che `public/css/mobile.css` esista e sia linkato in `views/layouts/main.php`

### Redirect infinito su home
**Soluzione**: Cancella la sessione
```php
// Aggiungi temporaneamente in index.php
session_destroy();
```

## 🎉 Tutto Pronto!

Se hai seguito tutti i passaggi:
- ✅ Database popolato
- ✅ Sistema verificato
- ✅ Login funzionante
- ✅ Tutte le funzionalità operative

**Sei pronto per sviluppare e testare EventsMaster!**

---

## 🔗 Link Utili

- Homepage: http://localhost/eventsMaster
- Login: http://localhost/eventsMaster/index.php?action=show_login
- Admin Dashboard: http://localhost/eventsMaster/index.php?action=admin_dashboard
- Reset DB: http://localhost/eventsMaster/db/reset_database.php
- Verifica: http://localhost/eventsMaster/check_installation.php
- PHPMyAdmin: http://localhost/phpmyadmin

---

**Versione**: 1.0
**Ultimo aggiornamento**: 2026-01-23
**Supporto**: Consulta la documentazione completa in `README_DOCUMENTAZIONE_COMPLETA.md`
