# Fix Case Sensitivity Database - EventsMaster

## 🚨 Problema Identificato

### Causa
Gli export SQL da **phpMyAdmin su Windows** creano tabelle con nomi **minuscoli** (`eventi`, `utenti`, ecc.), mentre il codice EventsMaster si aspetta nomi con **maiuscole** (`Eventi`, `Utenti`).

**MySQL su Linux è case-sensitive** per i nomi delle tabelle, quindi:
- ❌ `SELECT * FROM eventi` → **ERRORE** (tabella non trovata)
- ✅ `SELECT * FROM Eventi` → OK

### Sintomi
Quando importi un export da Windows sul server Linux:
```
PHP Fatal error: SQLSTATE[42S02]: Base table or view not found:
1146 Table '5cit_eventsMaster.Utenti' doesn't exist
```

Il sito non funziona, nessun evento viene mostrato.

---

## ✅ Soluzione Automatica

### Il Menu Ora Fixa Automaticamente!

La funzione **"Aggiorna con versione più recente (SQL)"** nel menu server manager ora:

1. ✅ Rileva automaticamente file SQL da Windows
2. ✅ Converte tutti i nomi tabelle al formato corretto
3. ✅ Importa il database senza errori
4. ✅ Tutto funziona perfettamente!

**Non devi fare nulla di speciale!** Il fix è completamente automatico.

---

## 📋 Tabelle Corrette Automaticamente

Il sistema converte automaticamente:

| ❌ Export Windows | ✅ Formato Corretto |
|------------------|---------------------|
| `biglietti` | `Biglietti` |
| `collaboratorieventi` | `CollaboratoriEventi` |
| `creatorieventi` | `CreatoriEventi` |
| `creatorilocations` | `CreatoriLocations` |
| `creatorimanifestazioni` | `CreatoriManifestazioni` |
| `eventi` | `Eventi` |
| `eventisettori` | `EventiSettori` |
| `evento_intrattenitore` | `Evento_Intrattenitore` |
| `intrattenitore` | `Intrattenitore` |
| `locations` | `Locations` |
| `manifestazioni` | `Manifestazioni` |
| `notifiche` | `Notifiche` |
| `ordine_biglietti` | `Ordine_Biglietti` |
| `ordini` | `Ordini` |
| `recensioni` | `Recensioni` |
| `settore_biglietti` | `Settore_Biglietti` |
| `settori` | `Settori` |
| `tipo` | `Tipo` |
| `utente_ordini` | `Utente_Ordini` |
| `utenti` | `Utenti` |

---

## 🚀 Workflow Aggiornato

### Dalla tua macchina Windows

1. **Esporta da phpMyAdmin locale**
   ```
   http://localhost/phpmyadmin
   → Seleziona: 5cit_eventsmaster
   → Export → SQL → Esporta
   ```

2. **Trasferisci al server**
   ```bash
   scp "C:\Users\bosco\Downloads\5cit_eventsmaster.sql" root@192.168.1.50:/var/www/eventsmaster/db/nuovo_export.sql
   ```

3. **Importa tramite menu** (con auto-fix!)
   ```bash
   ssh root@192.168.1.50
   sm
   → 2 (Database)
   → 3 (Aggiorna con versione più recente)
   → Seleziona: nuovo_export.sql
   → Backup? Sì
   → Conferma
   ```

4. **Il menu farà automaticamente:**
   - ✅ Fix case sensitivity (tabelle minuscole → maiuscole)
   - ✅ Drop database esistente
   - ✅ Crea nuovo database
   - ✅ Importa con nomi corretti
   - ✅ Mostra statistiche

**Il sito funziona immediatamente!** 🎉

---

## 🔧 Fix Manuale (se necessario)

Se vuoi fixare un file SQL manualmente:

```bash
ssh root@192.168.1.50

# Usa lo script di fix
/root/fix_sql_case.sh /path/to/export.sql

# Viene creato: export_fixed.sql
# Importa il file fixed
mysql --defaults-file=/etc/mysql/debian.cnf 5cit_eventsMaster < export_fixed.sql
```

---

## 📊 Verifica Post-Import

### 1. Verifica tabelle create correttamente
```bash
ssh root@192.168.1.50
mysql --defaults-file=/etc/mysql/debian.cnf -e "USE 5cit_eventsMaster; SHOW TABLES;"
```

Dovresti vedere tabelle con maiuscole:
```
Biglietti
CollaboratoriEventi
CreatoriEventi
Eventi
Locations
Utenti
...
```

### 2. Verifica dati
```bash
sm → 2 → 5 (Visualizza statistiche)
```

### 3. Test sito
Apri: http://192.168.1.50

Dovresti vedere:
- ✅ Eventi in homepage
- ✅ Nessun errore PHP
- ✅ Login funzionante

---

## 🐛 Troubleshooting

### Sito ancora non funziona dopo import

**Verifica 1: Controlla log errori**
```bash
sm → 5 (Log) → 1 (Apache Error Log)
```

Cerca errori tipo:
```
Table '5cit_eventsMaster.utenti' doesn't exist  ← tabella minuscola = problema
```

**Soluzione**: Reimporta usando il menu (fix automatico)

### Tabelle ancora minuscole

**Verifica:**
```bash
mysql --defaults-file=/etc/mysql/debian.cnf -e "SHOW TABLES FROM 5cit_eventsMaster;"
```

Se vedi tabelle minuscole (`eventi`, `utenti`):
```bash
# Reimporta con fix
sm → 2 → 3 → Seleziona file → Conferma
```

### File SQL già sul server non funziona

Se un file SQL sul server è già stato caricato in passato:

**Opzione A: Ricarica da Windows** (consigliato)
```bash
scp "nuovo_export.sql" root@192.168.1.50:/var/www/eventsmaster/db/
```

**Opzione B: Fix manuale sul server**
```bash
ssh root@192.168.1.50
/root/fix_sql_case.sh /var/www/eventsmaster/db/file_da_fixare.sql
mv /var/www/eventsmaster/db/file_da_fixare_fixed.sql /var/www/eventsmaster/db/file_da_fixare.sql
```

---

## 🎯 Best Practices

### 1. Usa sempre il menu per importare
Il menu ha il fix automatico integrato - è il modo più sicuro!

### 2. Backup prima di importare
Il menu chiede sempre se fare backup - **accetta sempre!**

### 3. Verifica dopo l'import
Dopo ogni import, apri http://192.168.1.50 e verifica che funzioni.

### 4. Mantieni file SQL organizzati
```
/var/www/eventsmaster/db/
├── install_complete.sql      ← Database completo con 80 eventi
├── latest_export.sql          ← Ultimo export (auto-fixed)
├── backup_YYYYMMDD.sql        ← Backup manuali
└── migrations/                ← Modifiche incrementali
```

---

## 📝 Note Tecniche

### Perché succede?

**Windows:**
- Filesystem: case-insensitive (C: non distingue FILE.txt da file.txt)
- MySQL: converte tabelle in minuscolo per coerenza
- Export: tabelle minuscole

**Linux:**
- Filesystem: case-sensitive (File.txt ≠ file.txt)
- MySQL: mantiene il case originale
- Import: crea tabelle minuscole (problemi!)

### Soluzione Permanente

Il codice EventsMaster usa nomi con maiuscole (`Eventi`, `Utenti`) quindi:
- ✅ Tutti gli import devono rispettare questo formato
- ✅ Il menu ora fa questo automaticamente
- ✅ Nessuna modifica al codice necessaria

### File Processati

Durante l'import, il menu:
1. Crea file temporaneo: `/tmp/db_import_fixed.sql`
2. Applica conversioni case
3. Importa il file fixed
4. Elimina file temporaneo

**Non lascia tracce**, tutto pulito!

---

## ✅ Checklist Rapida

Prima di importare un nuovo database:

- [ ] Ho fatto backup del database corrente?
- [ ] Il file SQL è sul server in `/var/www/eventsmaster/db/`?
- [ ] Uso il menu (opzione 3) per importare?
- [ ] Dopo import, ho verificato http://192.168.1.50?
- [ ] Eventi e utenti si vedono correttamente?

Se tutto ✅ → Sei a posto! 🎉

---

## 🚀 Quick Reference

**Import rapido da Windows:**
```bash
# 1. Trasferisci
scp export.sql root@192.168.1.50:/var/www/eventsmaster/db/

# 2. Importa
ssh root@192.168.1.50
sm → 2 → 3 → Seleziona export.sql

# 3. Verifica
Apri http://192.168.1.50
```

**Rollback se qualcosa va male:**
```bash
sm → 2 → 2 (Ripristina database) → Seleziona ultimo backup
```

---

**Problema risolto! Il menu ora gestisce tutto automaticamente.** ✨

**Versione**: 1.1
**Data**: 29/01/2026
**Fix Applicato**: Auto-conversione case sensitivity
