# EventsMaster - Documentazione Completa per Sviluppatori

**Autore:** Bosco Mattia
**Versione:** 1.0
**Ultimo aggiornamento:** Gennaio 2026

---

## Indice

1. [Introduzione](#1-introduzione)
2. [Architettura del Progetto](#2-architettura-del-progetto)
3. [Struttura Directory](#3-struttura-directory)
4. [Database Schema](#4-database-schema)
5. [Models - Documentazione Dettagliata](#5-models---documentazione-dettagliata)
6. [Controllers - Documentazione Dettagliata](#6-controllers---documentazione-dettagliata)
7. [Sistema di Autenticazione](#7-sistema-di-autenticazione)
8. [Sistema Permessi e Collaborazioni](#8-sistema-permessi-e-collaborazioni)
9. [Gestione Carrello e Checkout](#9-gestione-carrello-e-checkout)
10. [Sistema Notifiche Email](#10-sistema-notifiche-email)
11. [Cron Jobs e Manutenzione](#11-cron-jobs-e-manutenzione)
12. [Sicurezza Implementata](#12-sicurezza-implementata)
13. [Best Practices Utilizzate](#13-best-practices-utilizzate)
14. [Esempi Pratici di Flussi](#14-esempi-pratici-di-flussi)
15. [Come Estendere il Progetto](#15-come-estendere-il-progetto)

---

## 1. Introduzione

### 1.1 Cos'è EventsMaster

**EventsMaster** è una piattaforma completa per la gestione e vendita di biglietti per eventi culturali, sportivi e di intrattenimento. Il progetto implementa un'architettura **MVC (Model-View-Controller)** in PHP puro, senza framework esterni, con particolare attenzione a:

- **Sicurezza**: CSRF protection, password hashing, prepared statements
- **Scalabilità**: Database relazionale ben normalizzato
- **Manutenibilità**: Codice documentato, funzioni riutilizzabili, separazione delle responsabilità
- **User Experience**: Interfaccia moderna, carrello persistente, ricerca avanzata

### 1.2 Caratteristiche Principali

#### Gestione Eventi Multi-categoria
- **Concerti**: Band, artisti solisti, festival musicali
- **Teatro**: Spettacoli, musical, opere
- **Sport**: Partite di calcio, eventi sportivi
- **Eventi**: Manifestazioni, fiere, eventi generici

#### Sistema Biglietteria Avanzato
- **Tipologie**: Standard, VIP, Premium con sovraprezzi configurabili
- **Settori**: Divisione location in aree con moltiplicatori di prezzo
- **Posti Numerati**: Assegnazione automatica fila e numero (A1, A2, B1...)
- **Limiti Disponibilità**: Controllo posti esauriti in tempo reale
- **QR Code**: Codice univoco per validazione all'ingresso

#### Carrello Persistente
- **Salvato nel Database**: Non in localStorage, sopravvive a logout/chiusura browser
- **Prenotazione Temporanea**: I biglietti in carrello sono riservati
- **Pulizia Automatica**: Cron job elimina carrelli abbandonati dopo 24h
- **Multi-evento**: Acquisto biglietti per più eventi in un ordine

#### Sistema Ruoli e Permessi
- **User** (livello 1): Acquista biglietti, scrive recensioni
- **Promoter** (livello 2): Crea eventi, locations, manifestazioni
- **Moderator** (livello 3): Modera recensioni, gestisce tutti gli eventi
- **Admin** (livello 4): Gestione utenti, ruoli, sistema completo

#### Collaborazioni
- I **Promoter** possono invitare altri promoter a co-gestire eventi
- Sistema di inviti via email con token univoci
- Stati: pending, accepted, declined

#### Recensioni e Rating
- **Vincolo**: Solo chi ha acquistato biglietto può recensire
- **Voto**: 1-5 stelle obbligatorio
- **Messaggio**: Testo libero opzionale
- **Media Voti**: Calcolata automaticamente per ogni evento

### 1.3 Tecnologie Utilizzate

- **Backend**: PHP 8.x con PDO (PHP Data Objects)
- **Database**: MySQL 5.7+ / MariaDB 10.x
- **Frontend**: HTML5, CSS3 (CSS Variables per temi), JavaScript Vanilla ES6+
- **Sicurezza**:
  - Password hashing con `password_hash()` (bcrypt)
  - CSRF tokens con `bin2hex(random_bytes(32))`
  - Prepared statements PDO contro SQL Injection
  - XSS prevention con `htmlspecialchars()`
- **Architettura**: MVC puro senza framework
- **Email**: Template HTML con fallback simulazione file

### 1.4 Requisiti di Sistema

**Server**:
- PHP >= 8.0 (raccomandato 8.2+)
- MySQL >= 5.7 o MariaDB >= 10.3
- Apache 2.4+ o Nginx con PHP-FPM
- Moduli PHP: PDO, pdo_mysql, session, mbstring

**Sviluppo**:
- XAMPP 8.x / WAMP / MAMP
- Git per versionamento
- Editor: VS Code / PHPStorm

**Produzione**:
- HTTPS obbligatorio (certificato SSL)
- PHP OPcache abilitato
- Cron jobs per manutenzione
- Backup automatici database

---

## 2. Architettura del Progetto

### 2.1 Pattern MVC

EventsMaster implementa rigorosamente il pattern **Model-View-Controller**:

```
┌─────────────────────────────────────────────────────────────┐
│                         index.php                            │
│                    (Front Controller)                        │
│  - Unico entry point dell'applicazione                      │
│  - Routing basato su parametro 'action'                     │
│  - Gestisce sessione e messaggi flash                       │
│  - Include controller appropriato                           │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ├──────> CONTROLLERS ──────> MODELS ──────> DATABASE
                 │         (Business            (Data           (MySQL)
                 │          Logic)              Access)
                 │
                 └──────> VIEWS (Presentation Layer)
                           │
                           └──> LAYOUT (Header + Content + Footer)
```

**Responsabilità di ogni Layer**:

| Layer | Responsabilità | File |
|-------|----------------|------|
| **Router** | Determina quale controller chiamare | `index.php` |
| **Controller** | Business logic, orchestrazione | `controllers/*.php` |
| **Model** | Query database, CRUD operations | `models/*.php` |
| **View** | Presentazione HTML, output | `views/*.php` |
| **Layout** | Template condiviso (header/footer) | `views/layouts/main.php` |
| **Config** | Configurazioni globali | `config/*.php` |

### 2.2 Flusso di una Richiesta HTTP

Vediamo nel dettaglio cosa succede quando un utente visita una pagina:

```
1. BROWSER
   ↓ GET index.php?action=view_evento&id=42

2. index.php (Front Controller)
   ↓ Carica config/session.php (avvia sessione)
   ↓ Carica config/database.php (connessione PDO)
   ↓ Recupera messaggi flash da $_SESSION
   ↓ Legge parametro 'action'

3. ROUTER (switch statement)
   ↓ case 'view_evento':

4. CONTROLLER
   ↓ require_once 'controllers/EventoController.php'
   ↓ handleEvento($pdo, 'view_evento')
   ↓   └> viewEvento($pdo)

5. MODEL
   ↓ $evento = getEventoById($pdo, 42)
   ↓ $recensioni = getRecensioniByEvento($pdo, 42)
   ↓ [Query database via PDO]

6. CONTROLLER (prepara dati per view)
   ↓ $_SESSION['evento_corrente'] = $evento
   ↓ $_SESSION['recensioni_evento'] = $recensioni
   ↓ setPage('evento_dettaglio')

7. LAYOUT
   ↓ require 'views/layouts/main.php'
   ↓   ├> include 'views/header.php'
   ↓   ├> include 'views/evento_dettaglio.php' (view)
   ↓   └> include 'views/footer.php'

8. BROWSER
   ↓ Riceve HTML renderizzato
```

### 2.3 Esempio Pratico Completo

**URL richiesta**:
```
http://localhost/eventsMaster/index.php?action=view_evento&id=42
```

**index.php (Router)**:
```php
<?php
// 1. Avvia sessione
require_once 'config/session.php';

// 2. Connessione database
require_once 'config/database.php'; // crea $pdo

// 3. Messaggi flash
$msg = $_SESSION['msg'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['msg'], $_SESSION['error']);

// 4. Determina azione
$action = $_GET['action'] ?? null; // 'view_evento'

// 5. Router
switch ($action) {
    case 'view_evento':
        require_once 'controllers/PageController.php';
        require_once 'controllers/EventoController.php';
        handleEvento($pdo, $action);
        break;

    // ... altri case
}

// 6. Renderizza layout
require_once 'views/layouts/main.php';
```

**controllers/EventoController.php**:
```php
<?php
function handleEvento(PDO $pdo, string $action): void
{
    switch ($action) {
        case 'view_evento':
            viewEvento($pdo);
            break;
    }
}

function viewEvento(PDO $pdo): void
{
    // 1. Validazione input
    $id = (int) ($_GET['id'] ?? 0); // 42

    if ($id <= 0) {
        redirect('index.php', null, 'ID evento non valido');
    }

    // 2. Chiama Model per dati
    $evento = getEventoById($pdo, $id);

    if (!$evento) {
        redirect('index.php', null, 'Evento non trovato');
    }

    // 3. Carica dati correlati
    $intrattenitori = getIntrattenitoriEvento($pdo, $id);
    $recensioni = getRecensioniByEvento($pdo, $id);
    $mediaVoti = getMediaVotiEvento($pdo, $id);

    // 4. Prepara dati per la View
    $_SESSION['evento_corrente'] = $evento;
    $_SESSION['intrattenitori_evento'] = $intrattenitori;
    $_SESSION['recensioni_evento'] = $recensioni;
    $_SESSION['media_voti'] = $mediaVoti;

    // 5. Imposta quale View renderizzare
    setPage('evento_dettaglio');
}
```

**models/Evento.php**:
```php
<?php
function getEventoById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT e.*,
               m.Nome as ManifestazioneName,
               l.Nome as LocationName,
               l.Citta, l.Regione
        FROM Eventi e
        JOIN Manifestazioni m ON e.idManifestazione = m.id
        JOIN Locations l ON e.idLocation = l.id
        WHERE e.id = ?
    ");

    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}
```

**views/evento_dettaglio.php**:
```php
<?php
$evento = $_SESSION['evento_corrente'] ?? null;
$recensioni = $_SESSION['recensioni_evento'] ?? [];
$mediaVoti = $_SESSION['media_voti'] ?? 0;

if (!$evento): ?>
    <p>Evento non disponibile.</p>
<?php return; endif; ?>

<div class="evento-dettaglio">
    <h1><?= e($evento['Nome']) ?></h1>

    <div class="evento-info">
        <p><strong>Data:</strong> <?= formatDate($evento['Data']) ?></p>
        <p><strong>Orario:</strong> <?= formatTime($evento['OraI']) ?> - <?= formatTime($evento['OraF']) ?></p>
        <p><strong>Location:</strong> <?= e($evento['LocationName']) ?> (<?= e($evento['Citta']) ?>)</p>
        <p><strong>Prezzo:</strong> da <?= formatPrice($evento['PrezzoNoMod']) ?></p>
    </div>

    <?php if ($mediaVoti > 0): ?>
        <div class="rating">
            <span class="stars"><?= str_repeat('★', round($mediaVoti)) ?></span>
            <span><?= number_format($mediaVoti, 1) ?>/5</span>
        </div>
    <?php endif; ?>

    <div class="azioni">
        <form method="POST" action="index.php?action=cart_add" class="add-to-cart-form">
            <?= csrfField() ?>
            <input type="hidden" name="idEvento" value="<?= $evento['id'] ?>">

            <select name="idClasse">
                <option value="Standard">Standard</option>
                <option value="VIP">VIP</option>
                <option value="Premium">Premium</option>
            </select>

            <input type="number" name="quantita" value="1" min="1">
            <button type="submit">Aggiungi al Carrello</button>
        </form>
    </div>

    <div class="recensioni">
        <h2>Recensioni</h2>
        <?php foreach ($recensioni as $rec): ?>
            <div class="recensione">
                <strong><?= e($rec['NomeUtente']) ?></strong>
                <span class="voto"><?= str_repeat('★', $rec['Voto']) ?></span>
                <p><?= e($rec['Messaggio']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>
```

### 2.4 Vantaggi di questa Architettura

#### Separazione delle Responsabilità
Ogni componente ha un ruolo ben definito:
- **Model**: Solo logica dati (query SQL)
- **Controller**: Solo business logic (decisioni, validazioni)
- **View**: Solo presentazione (HTML)

**Anti-pattern da evitare**:
```php
// ❌ SBAGLIATO: Query SQL nella View
<?php foreach ($pdo->query("SELECT * FROM Eventi") as $evento): ?>
    <div><?= $evento['Nome'] ?></div>
<?php endforeach; ?>

// ✅ CORRETTO: Model per dati, View per output
<?php
$eventi = getAllEventi($pdo); // nel Controller
foreach ($eventi as $evento): ?>
    <div><?= e($evento['Nome']) ?></div>
<?php endforeach; ?>
```

#### Riutilizzabilità
I Model possono essere usati da controller diversi:

```php
// Stesso model usato in 3 contesti
$evento = getEventoById($pdo, $id);

// 1. Pagina dettaglio evento (EventoController)
// 2. Dashboard admin (AdminController)
// 3. API JSON biglietti (BigliettoController)
```

#### Testabilità
Ogni layer può essere testato isolatamente:

```php
// Test Model
assert(getEventoById($pdo, 999) === null); // ID inesistente
assert(getEventoById($pdo, 1)['Nome'] === 'Concerto Test');

// Test Controller (con mock PDO)
$mockPdo = createMockPdo();
viewEvento($mockPdo); // Verifica che setPage() sia chiamato

// Test View (rendering HTML)
$_SESSION['evento_corrente'] = ['Nome' => 'Test'];
ob_start();
include 'views/evento_dettaglio.php';
$html = ob_get_clean();
assert(strpos($html, 'Test') !== false);
```

#### Manutenibilità
Modifiche isolate non impattano altri componenti:

**Esempio**: Cambiare come viene calcolato il prezzo finale
```php
// PRIMA (nel Model)
function calcolaPrezzoFinale($pdo, $idEvento, $idClasse, $idSettore) {
    // Query + calcolo
    return ($prezzoBase + $modificatore) * $moltiplicatore;
}

// DOPO (aggiunto sconto)
function calcolaPrezzoFinale($pdo, $idEvento, $idClasse, $idSettore, $scontoPercentuale = 0) {
    $prezzo = ($prezzoBase + $modificatore) * $moltiplicatore;
    return $prezzo * (1 - $scontoPercentuale / 100);
}

// Controller e View NON cambiano!
```

#### Scalabilità
Facile aggiungere nuove funzionalità:

**Esempio**: Aggiungere wishlist
```
1. Crea tabella Wishlist nel DB
2. Crea models/Wishlist.php con CRUD
3. Crea controllers/WishlistController.php
4. Aggiungi case 'add_to_wishlist' in index.php
5. Crea views/wishlist.php
```

Nessun file esistente viene modificato, solo aggiunti!

---

## 3. Struttura Directory

```
eventsMaster/
│
├── config/                          # Configurazioni globali
│   ├── database.php                # Connessione PDO MySQL
│   ├── env.php                     # Caricamento variabili .env
│   ├── session.php                 # Configurazione sessioni PHP
│   ├── mail.php                    # Sistema email (simulato/reale)
│   └── helpers.php                 # Funzioni utility globali
│
├── controllers/                     # Business Logic Layer
│   ├── AuthController.php          # Login, registrazione, logout
│   ├── EventoController.php        # CRUD eventi, ricerca, filtri
│   ├── CartController.php          # API carrello (JSON responses)
│   ├── BigliettoController.php     # Acquisto e validazione biglietti
│   ├── OrdineController.php        # Visualizzazione storico ordini
│   ├── UserController.php          # Profilo, password, avatar
│   ├── AdminController.php         # Dashboard admin/mod/promoter
│   ├── RecensioneController.php    # CRUD recensioni
│   ├── CollaborazioneController.php# Sistema inviti collaborazione
│   ├── AvatarController.php        # Upload avatar utente
│   └── PageController.php          # Setter $_SESSION['page']
│
├── models/                          # Data Access Layer
│   ├── Utente.php                  # Autenticazione, ruoli, CRUD utenti
│   ├── Evento.php                  # CRUD eventi, ricerche
│   ├── Biglietto.php               # Carrello, posti, validazione
│   ├── Ordine.php                  # Transazioni acquisto
│   ├── Recensione.php              # Voti e commenti
│   ├── Location.php                # Luoghi eventi
│   ├── Manifestazione.php          # Contenitori eventi (festival)
│   ├── Intrattenitore.php          # Artisti/performer
│   ├── Permessi.php                # Sistema collaborazioni
│   └── EventoSettori.php           # Associazione settori-eventi
│
├── views/                           # Presentation Layer
│   ├── layouts/
│   │   └── main.php                # Template principale (header+footer)
│   ├── home.php                    # Homepage con carousel
│   ├── login.php                   # Form login
│   ├── register.php                # Form registrazione
│   ├── profilo.php                 # Profilo utente
│   ├── eventi_lista.php            # Lista eventi
│   ├── eventi_ricerca.php          # Risultati ricerca
│   ├── evento_dettaglio.php        # Dettaglio evento
│   ├── miei_biglietti.php          # Biglietti utente
│   ├── miei_ordini.php             # Storico ordini
│   ├── ordine_dettaglio.php        # Dettaglio ordine
│   ├── checkout.php                # Pagina checkout
│   ├── cambia_password.php         # Form cambio password
│   ├── recupera_password.php       # Form richiesta reset
│   ├── reset_password.php          # Form nuova password (con token)
│   ├── elimina_account.php         # Conferma eliminazione account
│   └── admin/                      # Area amministrazione
│       ├── dashboard.php           # Dashboard admin con statistiche
│       ├── utenti.php              # Gestione utenti e ruoli
│       ├── eventi.php              # Gestione eventi (admin)
│       ├── evento_form.php         # Form creazione/modifica evento
│       ├── promoter_dashboard.php  # Dashboard promoter
│       └── mod_dashboard.php       # Dashboard moderatore
│
├── public/                          # Assets pubblici
│   ├── css/
│   │   ├── variables.css           # CSS Variables (colori, font)
│   │   ├── base.css                # Reset e stili base
│   │   ├── header.css              # Navbar
│   │   ├── footer.css              # Footer
│   │   ├── components.css          # Card, button, badge
│   │   ├── forms.css               # Form e input
│   │   ├── main.css                # Layout pagine
│   │   ├── cart.css                # Carrello
│   │   ├── admin.css               # Stili admin
│   │   └── responsive.css          # Media queries
│   ├── script.js                   # JavaScript principale
│   └── images/                     # Immagini statiche
│
├── lib/                             # Librerie custom
│   └── EmailService.php            # Servizio email con template
│
├── db/                              # SQL scripts
│   ├── install_complete.sql        # Setup completo (tabelle + dati)
│   ├── dump.sql                    # Dump struttura base
│   ├── dump_extended.sql           # Dump con più dati
│   └── migrations/
│       └── 001_add_collaboration_system.sql
│
├── cron/                            # Script schedulati
│   └── auto_delete_old_events.php  # Pulizia eventi vecchi
│
├── logs/                            # File di log (git-ignored)
│   ├── error.log                   # Errori applicazione
│   ├── mail.log                    # Email inviate (simulazione)
│   └── cron_delete_events.log      # Log cron
│
├── uploads/                         # File caricati (git-ignored)
│   └── .gitkeep
│
├── .env                             # Variabili ambiente (git-ignored)
├── .gitignore                      # File esclusi da git
├── index.php                       # Front Controller (entry point)
└── README.md                       # Questa documentazione
```

### 3.1 Descrizione Dettagliata

#### config/

**database.php** - Connessione PDO
```php
$pdo = new PDO("mysql:host=localhost;dbname=5cit_eventsMaster", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // Lancia eccezioni
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Array associativi
    PDO::ATTR_EMULATE_PREPARES => false           // Prepared stmt reali
]);
```

**env.php** - Carica `.env`
```
DB_HOST=localhost
DB_NAME=5cit_eventsMaster
DB_USER=root
DB_PASS=
APP_DEBUG=true
```

**helpers.php** - Funzioni globali
- `e($str)`: Escape HTML
- `redirect($url, $msg, $error)`: Redirect con flash messages
- `csrfField()`: Campo hidden con token CSRF
- `formatPrice($num)`: Formatta prezzi in euro
- `formatDate($date)`, `formatTime($time)`: Formatta date/ore

#### controllers/

Ogni controller gestisce un'area funzionale:

| Controller | Responsabilità |
|------------|----------------|
| **AuthController** | Login, registrazione, logout |
| **EventoController** | CRUD eventi, ricerca per nome/categoria |
| **CartController** | API JSON per carrello (add, remove, update) |
| **BigliettoController** | Checkout finale, validazione biglietti all'ingresso |
| **OrdineController** | Visualizzazione storico ordini utente |
| **UserController** | Gestione profilo, cambio password, eliminazione account |
| **AdminController** | Dashboard admin, gestione utenti/ruoli |
| **RecensioneController** | Aggiunta/modifica/eliminazione recensioni |

#### models/

Ogni model corrisponde a una (o più) tabelle del database:

| Model | Tabelle | Operazioni |
|-------|---------|------------|
| **Utente** | Utenti | CRUD, auth, ruoli, token verifica/reset |
| **Evento** | Eventi, Manifestazioni, Locations | CRUD, ricerca, filtri categoria |
| **Biglietto** | Biglietti, Settore_Biglietti, Tipo | Carrello, assegnazione posti, validazione |
| **Ordine** | Ordini, Ordine_Biglietti, Utente_Ordini | Creazione transazioni, totali |
| **Recensione** | Recensioni | CRUD recensioni, calcolo media voti |
| **Permessi** | CreatoriEventi, CollaboratoriEventi | Verifica permessi, inviti collaborazione |

#### views/

Template HTML con PHP per output dinamico:

**Naming Convention**:
- `nome_pagina.php`: View normale (es: `eventi_lista.php`)
- `nome_dettaglio.php`: Dettaglio singolo elemento (es: `evento_dettaglio.php`)
- `miei_*.php`: Area utente loggato (es: `miei_biglietti.php`)
- `admin/*.php`: Area amministrazione

**Utilizzo `$_SESSION` nelle View**:
```php
// Controller prepara dati
$_SESSION['eventi'] = getAllEventi($pdo);

// View li usa
<?php foreach ($_SESSION['eventi'] ?? [] as $evento): ?>
    <div><?= e($evento['Nome']) ?></div>
<?php endforeach; ?>
```

#### public/

Asset statici serviti direttamente dal webserver:

**CSS Modulare**:
1. `variables.css` - CSS Custom Properties
2. `base.css` - Reset e tipografia
3. `components.css` - Componenti riutilizzabili
4. `forms.css` - Stili form
5. `main.css` - Layout specifiche pagine
6. `responsive.css` - Media queries

**JavaScript**:
- Gestione tema (light/dark/auto)
- Carrello dinamico (fetch API)
- Carousel eventi
- Search bar animata

#### db/

**install_complete.sql**: Script unico per setup database
- Crea database `5cit_eventsMaster`
- Crea 19 tabelle con foreign key
- Inserisce dati di esempio (50+ eventi, 30 locations)

**Esecuzione**:
```bash
# Da phpMyAdmin: Importa install_complete.sql
# Oppure da CLI:
mysql -u root -p < db/install_complete.sql
```

#### cron/

Script da eseguire periodicamente:

**auto_delete_old_events.php**:
- Elimina eventi con data > 2 settimane fa
- Mantiene biglietti acquistati negli ordini
- Elimina biglietti in carrello

**Configurazione Windows Task Scheduler**:
```
Program: C:\xampp\php\php.exe
Arguments: C:\xampp\htdocs\eventsMaster\cron\auto_delete_old_events.php
Schedule: Daily 03:00 AM
```

**Configurazione Cron Linux**:
```cron
0 3 * * * /usr/bin/php /var/www/eventsMaster/cron/auto_delete_old_events.php
```

---

*Continua nella prossima risposta con Database Schema completo...*
