# EventsMaster

Sistema di gestione eventi e vendita biglietti online.

**Autore:** Bosco Mattia
**Classe:** 5C-IT
**Anno scolastico:** 2025/2026

---

## Indice

1. [Descrizione del progetto](#descrizione-del-progetto)
2. [Requisiti di sistema](#requisiti-di-sistema)
3. [Installazione](#installazione)
4. [Struttura del progetto](#struttura-del-progetto)
5. [Architettura](#architettura)
6. [Database](#database)
7. [Funzionalita](#funzionalita)
8. [Sistema di autenticazione](#sistema-di-autenticazione)
9. [Sistema di ruoli](#sistema-di-ruoli)
10. [API e routing](#api-e-routing)
11. [Sicurezza](#sicurezza)
12. [Configurazione](#configurazione)

---

## Descrizione del progetto

EventsMaster e una web application per la gestione completa di eventi e la vendita di biglietti online. Il sistema permette di:

- Consultare il catalogo eventi suddiviso per categorie (concerti, teatro, sport, eventi)
- Acquistare biglietti con diverse tipologie e settori
- Gestire il proprio profilo utente e storico ordini
- Amministrare eventi, utenti e contenuti (per utenti autorizzati)

L'interfaccia utente presenta un design moderno ispirato alle piattaforme di streaming, con supporto per tema chiaro, scuro e automatico.

---

## Requisiti di sistema

- PHP 8.0 o superiore
- MySQL 5.7 o superiore / MariaDB 10.3 o superiore
- Web server Apache con mod_rewrite abilitato
- XAMPP (consigliato per ambiente di sviluppo locale)

---

## Installazione

### 1. Clonare il repository

```bash
git clone https://github.com/[username]/eventsMaster.git
cd eventsMaster
```

### 2. Configurare il database

Importare il file SQL completo in phpMyAdmin o da riga di comando:

```bash
mysql -u root -p < db/install_complete.sql
```

Il file `install_complete.sql` crea il database, le tabelle e popola i dati di esempio.

### 3. Configurare le variabili d'ambiente

Creare un file `.env` nella root del progetto:

```env
APP_NAME=EventsMaster
APP_DEBUG=true

DB_HOST=localhost
DB_NAME=5cit_eventsMaster
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4

MAIL_SIMULATION=true
```

### 4. Avviare il server

Con XAMPP, posizionare il progetto in `htdocs` e accedere a:

```
http://localhost/eventsMaster/
```

### Credenziali di test

| Ruolo    | Email                     | Password |
|----------|---------------------------|----------|
| Admin    | admin@eventsmaster.it     | password |
| Moderatore | mod1@eventsmaster.it    | password |
| Promoter | promoter@eventsmaster.it  | password |
| Utente   | mario.rossi@example.com   | password |

---

## Struttura del progetto

```
eventsMaster/
├── config/                 # Configurazioni
│   ├── database.php        # Connessione PDO
│   ├── env.php             # Gestione variabili ambiente
│   ├── helpers.php         # Funzioni helper globali
│   ├── mail.php            # Configurazione email
│   └── session.php         # Configurazione sessione
├── controllers/            # Controller dell'applicazione
│   ├── AdminController.php
│   ├── AuthController.php
│   ├── BigliettoController.php
│   ├── EventoController.php
│   ├── OrdineController.php
│   ├── PageController.php
│   ├── RecensioneController.php
│   └── UserController.php
├── db/                     # File database
│   ├── 5cit_eventsMaster.sql   # Schema DDL
│   ├── dump.sql            # Dati base
│   ├── dump_extended.sql   # Dati estesi
│   ├── install_complete.sql    # Installazione completa
│   └── migrations/         # Migrazioni incrementali
├── models/                 # Model (accesso dati)
│   ├── Biglietto.php
│   ├── Evento.php
│   ├── Intrattenitore.php
│   ├── Location.php
│   ├── Manifestazione.php
│   ├── Ordine.php
│   ├── Recensione.php
│   └── Utente.php
├── public/                 # Asset pubblici
│   ├── css/                # Fogli di stile
│   │   ├── main.css        # Entry point CSS
│   │   ├── variables.css   # Variabili CSS e temi
│   │   ├── base.css        # Reset e stili base
│   │   ├── components.css  # Componenti UI
│   │   ├── forms.css       # Stili form
│   │   ├── header.css      # Header e navigazione
│   │   ├── footer.css      # Footer
│   │   └── admin.css       # Pannello admin
│   └── script.js           # JavaScript frontend
├── views/                  # Template delle pagine
│   ├── layouts/
│   │   └── main.php        # Layout principale
│   ├── admin/              # Pagine amministrazione
│   ├── home.php
│   ├── login.php
│   ├── register.php
│   └── ...
├── logs/                   # File di log (generato)
├── .env                    # Variabili ambiente (non versionato)
├── .gitignore
├── index.php               # Entry point / Router
└── README.md
```

---

## Architettura

Il progetto segue un pattern MVC semplificato:

### Model

I model (`models/`) contengono funzioni per l'accesso ai dati tramite PDO. Ogni model corrisponde a una tabella principale del database e fornisce operazioni CRUD.

### View

Le view (`views/`) sono template PHP che ricevono i dati dalla sessione e li renderizzano in HTML. Il layout principale (`layouts/main.php`) include header, footer e il contenuto dinamico.

### Controller

I controller (`controllers/`) gestiscono la logica applicativa, validano l'input, chiamano i model e preparano i dati per le view.

### Router

Il file `index.php` funge da router principale. Riceve tutte le richieste e le smista ai controller appropriati in base al parametro `action`.

---

## Database

### Schema ER

Il database modella le seguenti entita principali:

- **Manifestazioni**: contenitori logici di eventi (es. "Festival della Musica")
- **Eventi**: singoli appuntamenti con data, ora, location e prezzo base
- **Locations**: luoghi fisici con indirizzo strutturato
- **Settori**: suddivisioni delle location con posti e moltiplicatore prezzo
- **Intrattenitori**: artisti o gruppi che si esibiscono
- **Utenti**: utenti registrati con sistema di ruoli
- **Biglietti**: titoli di accesso con QR code
- **Ordini**: transazioni di acquisto
- **Recensioni**: valutazioni degli eventi

### Tabelle principali

| Tabella | Descrizione |
|---------|-------------|
| Manifestazioni | Contenitori di eventi correlati |
| Eventi | Eventi con data, ora, prezzo, categoria |
| Locations | Luoghi con indirizzo strutturato |
| Settori | Aree delle location con capienza |
| Utenti | Utenti con ruolo e credenziali |
| Biglietti | Biglietti con dati intestatario |
| Ordini | Ordini di acquisto |
| Tipo | Tipologie biglietto (Standard, VIP, Premium) |

### Relazioni

- Un evento appartiene a una manifestazione (N:1)
- Un evento si svolge in una location (N:1)
- Una location ha piu settori (1:N)
- Un utente puo avere piu ordini (1:N)
- Un ordine contiene piu biglietti (N:M)
- Un utente puo scrivere recensioni (1:N)

---

## Funzionalita

### Pubbliche

- Visualizzazione homepage con caroselli eventi
- Navigazione per categoria (concerti, teatro, sport, eventi)
- Ricerca eventi per nome, location o manifestazione
- Visualizzazione dettaglio evento con intrattenitori e recensioni
- Registrazione e login utente

### Utente autenticato

- Gestione carrello (localStorage)
- Acquisto biglietti
- Visualizzazione biglietti acquistati
- Storico ordini
- Gestione profilo (modifica dati, cambio password)
- Eliminazione account
- Scrittura recensioni
- Selezione tema (chiaro/scuro/automatico)

### Promoter

- Dashboard personale
- Gestione propri eventi

### Moderatore

- Dashboard moderazione
- Gestione contenuti

### Amministratore

- Dashboard con statistiche
- Gestione completa utenti (modifica ruoli, eliminazione)
- Gestione completa eventi (creazione, modifica, eliminazione)

---

## Sistema di autenticazione

### Registrazione

1. L'utente compila il form con nome, cognome, email e password
2. La password viene hashata con `password_hash()` (bcrypt)
3. Viene generato un token di verifica email
4. L'utente riceve un'email con il link di verifica

### Login

1. L'utente inserisce email e password
2. La password viene verificata con `password_verify()`
3. I dati utente vengono salvati in sessione

### Recupero password

1. L'utente richiede il reset inserendo l'email
2. Viene generato un token con scadenza di 1 ora
3. L'utente riceve un'email con il link di reset
4. Il link permette di impostare una nuova password

### Sessione

- Durata: 2 ore
- Protezione CSRF con token per tutti i form POST
- Rigenerazione session ID al login

---

## Sistema di ruoli

Il sistema implementa quattro livelli di permessi con gerarchia crescente:

| Ruolo | Livello | Permessi |
|-------|---------|----------|
| user | 1 | Acquisto biglietti, recensioni, gestione profilo |
| promoter | 2 | + Creazione e gestione propri eventi |
| mod | 3 | + Moderazione contenuti |
| admin | 4 | + Gestione completa utenti e sistema |

### Funzioni di verifica

```php
isLoggedIn()    // Verifica autenticazione
isAdmin()       // Verifica ruolo admin
isMod()         // Verifica ruolo mod o superiore
isPromoter()    // Verifica ruolo promoter o superiore
hasRole($role)  // Verifica ruolo minimo richiesto
```

---

## API e routing

Tutte le richieste passano per `index.php` con il parametro `action`.

### Autenticazione

| Action | Metodo | Descrizione |
|--------|--------|-------------|
| show_login | GET | Mostra form login |
| login | POST | Esegue login |
| show_register | GET | Mostra form registrazione |
| register | POST | Esegue registrazione |
| logout | POST | Esegue logout |

### Eventi

| Action | Metodo | Descrizione |
|--------|--------|-------------|
| list_eventi | GET | Lista tutti gli eventi |
| category | GET | Eventi per categoria (?cat=concerti) |
| view_evento | GET | Dettaglio evento (?id=1) |
| search_eventi | POST | Ricerca eventi |

### Profilo utente

| Action | Metodo | Descrizione |
|--------|--------|-------------|
| profilo | GET | Pagina profilo |
| update_profile | POST | Aggiorna profilo |
| miei_biglietti | GET | Lista biglietti |
| miei_ordini | GET | Storico ordini |
| cambia_password | GET | Form cambio password |
| update_password | POST | Aggiorna password |
| elimina_account | GET | Conferma eliminazione |
| delete_account | POST | Elimina account |

### Amministrazione

| Action | Metodo | Descrizione |
|--------|--------|-------------|
| admin_dashboard | GET | Dashboard admin |
| admin_users | GET | Gestione utenti |
| admin_update_role | POST | Modifica ruolo utente |
| admin_delete_user | POST | Elimina utente |
| admin_events | GET | Gestione eventi |
| admin_create_event | POST | Crea evento |
| admin_delete_event | POST | Elimina evento |

---

## Sicurezza

### Protezioni implementate

- **SQL Injection**: uso esclusivo di prepared statements PDO
- **XSS**: escape dell'output con `htmlspecialchars()` tramite helper `e()`
- **CSRF**: token univoco per sessione verificato su ogni form POST
- **Password**: hashing con bcrypt (`PASSWORD_DEFAULT`)
- **Session fixation**: rigenerazione session ID al login
- **Timing attacks**: confronto token con `hash_equals()`

### Best practices

- Validazione e sanitizzazione di tutti gli input utente
- Messaggi di errore generici per non rivelare informazioni sensibili
- File `.env` escluso dal version control
- Log degli errori su file separato

---

## Configurazione

### Variabili ambiente (.env)

| Variabile | Descrizione | Default |
|-----------|-------------|---------|
| APP_NAME | Nome applicazione | EventsMaster |
| APP_DEBUG | Abilita debug | false |
| DB_HOST | Host database | localhost |
| DB_NAME | Nome database | 5cit_eventsMaster |
| DB_USER | Utente database | root |
| DB_PASS | Password database | (vuota) |
| DB_CHARSET | Charset database | utf8mb4 |
| MAIL_SIMULATION | Simula invio email | true |

### Tema

Il sistema supporta tre modalita di tema:

- **light**: tema chiaro
- **dark**: tema scuro
- **auto**: segue le preferenze del sistema operativo

La preferenza viene salvata in localStorage e applicata tramite CSS custom properties.

---

## Licenza

Progetto didattico - Tutti i diritti riservati.
