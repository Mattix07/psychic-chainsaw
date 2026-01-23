# Script Reset Database

## 📋 Cosa fa questo script

Lo script `reset_to_production_state.sql` ripristina il database a uno stato "produzione pulita", ideale per:
- **Demo/Presentazioni**: Database popolato ma senza dati di test sporchi
- **Sviluppo**: Ricominciare da uno stato pulito ma non vuoto
- **Testing**: Ambiente controllato con dati realistici

## 🔥 ATTENZIONE

⚠️ **Questo script CANCELLA TUTTI I DATI esistenti nel database!**

Assicurati di:
1. **Fare un backup** se hai dati importanti
2. Essere sicuro di voler procedere
3. Essere nel database corretto

## 📦 Cosa contiene il database dopo il reset

### Utenti (4 account di test)
| Email | Password | Ruolo | Descrizione |
|-------|----------|-------|-------------|
| `admin@eventsmaster.it` | `password123` | Admin | Accesso completo al sistema |
| `mod@eventsmaster.it` | `password123` | Moderatore | Gestione contenuti e moderazione |
| `promoter@eventsmaster.it` | `password123` | Promoter | Creazione e gestione eventi |
| `user@eventsmaster.it` | `password123` | User | Utente normale |

### Locations (15 venue realistiche)
- **3 Stadi**: San Siro, Olimpico, Allianz Stadium
- **3 Palazzetti**: Mediolanum Forum, PalaAlpitour, Palazzo dello Sport
- **3 Teatri**: Scala, Opera, Regio
- **3 Club**: Alcatraz, Fabrique, Atlantico
- **3 Spazi Aperti**: Ippodromo, Auditorium Parco Musica, Arena Verona

### Settori (15 settori distribuiti tra le location)
Ogni location ha i propri settori con:
- Moltiplicatori di prezzo diversi (1.0x - 3.0x)
- Capacità realistica
- Nomi appropriati (Tribuna, Curva, Platea, Parterre, ecc.)

### Manifestazioni (5 eventi ricorrenti/festival)
- Rock in Italy Festival 2026
- Opera Estate 2026
- Milano Music Week
- Jazz & Wine Festival
- Teatro Contemporaneo

### Intrattenitori (16 artisti/band/compagnie)
Categorizzati per genere:
- Musica Rock/Pop: Måneskin, Jovanotti, Vasco Rossi, Ligabue, Negramaro, Subsonica
- Classica/Opera: Riccardo Muti, Orchestra Sinfonica, Ludovico Einaudi
- Jazz: Paolo Fresu, Stefano Bollani
- Teatro: Compagnie e attori
- Comedy: Maurizio Crozza, Luca & Paolo

### Eventi (20+ eventi futuri)
Distribuiti nei **prossimi 6 mesi** con:
- **Concerti**: Rock, pop, classica, jazz
- **Opera**: Traviata, Barbiere di Siviglia
- **Teatro**: Pirandello, Kafka
- **Comedy**: Show comici
- **Sport**: Derby, match Serie A
- **Eventi Speciali**: Notte Bianca, Cinema all'aperto
- **Famiglia**: Musical, Cirque du Soleil

### Tipi Biglietto (5 categorie)
- Standard (€0 modificatore)
- VIP (+€50)
- Premium (+€100)
- Ridotto (-€10)
- Under 18 (-€15)

### Cosa NON contiene
❌ Ordini completati
❌ Biglietti acquistati
❌ Recensioni
❌ Carrelli salvati
❌ Notifiche

## 🚀 Come usare lo script

### Opzione 1: PHPMyAdmin (Consigliata)

1. Apri **PHPMyAdmin** (http://localhost/phpmyadmin)
2. Seleziona il database `5cit_eventsMaster`
3. Vai alla tab **"SQL"**
4. Copia tutto il contenuto di `reset_to_production_state.sql`
5. Incolla nella textarea
6. Clicca **"Esegui"**
7. Attendi il completamento (circa 5-10 secondi)
8. Verifica i risultati nel riepilogo finale

### Opzione 2: Riga di comando MySQL

```bash
# Windows (XAMPP)
cd C:\xampp\mysql\bin
mysql -u root -p 5cit_eventsMaster < C:\xampp\htdocs\eventsMaster\db\reset_to_production_state.sql

# Linux/Mac
mysql -u root -p 5cit_eventsMaster < /path/to/eventsMaster/db/reset_to_production_state.sql
```

### Opzione 3: MySQL Workbench

1. Apri MySQL Workbench
2. Connettiti al database locale
3. File → Open SQL Script
4. Seleziona `reset_to_production_state.sql`
5. Clicca sull'icona del fulmine (Execute)

## ✅ Verifica che tutto sia andato a buon fine

Alla fine dello script vedrai un riepilogo:

```
RIEPILOGO DATABASE RESET:
- Utenti: 4
- Locations: 15
- Settori: 15
- Manifestazioni: 5
- Intrattenitori: 16
- Eventi: 20+
- Tipi Biglietto: 5
- Associazioni Evento-Settore: ~50

Database resettato con successo!
```

## 🧪 Testing dopo il reset

1. **Accedi come Admin**:
   ```
   Email: admin@eventsmaster.it
   Password: password123
   ```

2. **Verifica la homepage**: Dovresti vedere tutti gli eventi nei carousel

3. **Testa ogni ruolo**:
   - Admin → Dashboard completa
   - Mod → Moderazione contenuti
   - Promoter → I suoi 7 eventi creati
   - User → Homepage pubblica con eventi

4. **Prova ad acquistare un biglietto** con l'utente normale

5. **Crea un nuovo evento** come Promoter e verifica i settori

## 🔧 Personalizzazione

Se vuoi modificare i dati di default:

### Cambiare le password
Genera un nuovo hash con PHP:
```php
echo password_hash('tuapassword', PASSWORD_BCRYPT);
```
Sostituisci l'hash nella sezione `FASE 2: UTENTI DI TEST`

### Aggiungere più eventi
Duplica le INSERT nella `FASE 8` cambiando:
- Nome evento
- Data (deve essere futura!)
- Location ID
- Prezzo

### Aggiungere location
Aggiungi INSERT nella `FASE 4` con dati realistici:
```sql
INSERT INTO Location (Nome, Indirizzo, Citta, CAP, Regione, Capienza) VALUES
('Tuo Venue', 'Via X', 'Città', 'CAP', 'Regione', 5000);
```

## 📝 Note importanti

1. **Date future**: Lo script usa date relative tipo `2026-06-15`. Quando siamo nel 2026, dovrai aggiornare l'anno!

2. **Caratteri speciali**: Lo script usa UTF-8, assicurati che PHPMyAdmin/MySQL siano configurati per UTF-8

3. **Foreign Keys**: Lo script disabilita temporaneamente le FK durante il TRUNCATE, poi le riabilita

4. **Performance**: Con questi dati il database è ~200KB, molto leggero

5. **Immagini eventi**: Lo script non include immagini. Le card eventi useranno i fallback placeholder automaticamente

## 🆘 Troubleshooting

### Errore: "Table doesn't exist"
**Soluzione**: Assicurati di aver prima eseguito le migration:
```bash
db/migrations/001_add_collaboration_system.sql
```

### Errore: "Duplicate entry for key 'PRIMARY'"
**Soluzione**: Hai già dati con gli stessi ID. Esegui prima la sezione TRUNCATE separatamente.

### Errore: "Unknown column 'Avatar'"
**Soluzione**: La migrazione non è stata eseguita. Esegui prima `001_add_collaboration_system.sql`

### Eventi non visibili in homepage
**Soluzione**: Controlla che le date siano future. Modifica l'anno negli INSERT se necessario.

## 📅 Manutenzione

**Ogni 6 mesi**: Aggiorna le date degli eventi per mantenerle future:
```sql
UPDATE Eventi SET Data = DATE_ADD(Data, INTERVAL 6 MONTH);
```

## 🎯 Script correlati

- `migrations/001_add_collaboration_system.sql` - Schema database completo
- `TESTING.md` - Checklist test dopo il reset
- `ISTRUZIONI_IMPLEMENTAZIONE.md` - Guida integrazione codice

---

**Ultima modifica**: 2026-01-23
**Versione database**: 1.0
**Compatibile con**: PHP 8.0+, MySQL 5.7+
