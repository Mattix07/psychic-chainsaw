# Documentazione Completa — EventsMaster

> Questa documentazione spiega **come funziona il progetto dall'interno**: ogni file, ogni funzione, ogni meccanismo.
> È pensata per essere letta da chi non ha mai visto il codice (e magari PHP lo conosce poco) **e** da chi vuole capire le scelte tecniche nel dettaglio.

---

## Indice

1. [Introduzione al progetto](#1-introduzione-al-progetto)
2. [Architettura MVC](#2-architettura-mvc)
3. [Il Front Controller — index.php](#3-il-front-controller--indexphp)
4. [Configurazione — cartella config/](#4-configurazione--cartella-config)
5. [Le librerie — cartella lib/](#5-le-librerie--cartella-lib)
6. [I Controller](#6-i-controller)
7. [I Model](#7-i-model)
8. [Le View e il Layout](#8-le-view-e-il-layout)
9. [Il Database](#9-il-database)
10. [Sicurezza](#10-sicurezza)
11. [Flussi end-to-end](#11-flussi-end-to-end)
12. [Ruoli e permessi](#12-ruoli-e-permessi)
13. [Glossario](#13-glossario)

---

## 1. Introduzione al progetto

### Cos'è EventsMaster?

EventsMaster è una **piattaforma web per la vendita di biglietti per eventi**: concerti, teatro, sport, comedy, cinema e spettacoli per famiglie. Permette agli utenti di:

- Sfogliare eventi e filtrarli per categoria
- Aggiungere biglietti al carrello e acquistarli
- Ricevere un biglietto digitale con QR code
- Lasciare recensioni sugli eventi a cui hanno partecipato

Ci sono anche funzionalità per chi organizza gli eventi (promoter, moderatori, admin) per creare e gestire eventi, location e manifestazioni.

### Stack tecnologico

| Tecnologia | Ruolo |
|---|---|
| **PHP 8+** | Linguaggio lato server — genera le pagine HTML |
| **MySQL** | Database — salva tutti i dati |
| **PDO** | Libreria PHP per parlare con MySQL in modo sicuro |
| **XAMPP** | Ambiente di sviluppo locale (Apache + MySQL + PHP) |
| **HTML/CSS** | Struttura e stile delle pagine |
| **JavaScript** | Comportamento dinamico nel browser (carrello, ricerca) |
| **Font Awesome** | Icone |

### Struttura delle cartelle

```
eventsMaster/
├── index.php              ← Punto di ingresso: gestisce TUTTE le richieste
├── sitemap.php            ← Mappa del sito per i motori di ricerca
├── DOCUMENTAZIONE.md      ← Questo file
│
├── config/                ← Configurazione dell'applicazione
│   ├── session.php        ← Avvia la sessione con impostazioni sicure
│   ├── database.php       ← Connessione al database MySQL
│   ├── database_schema.php← Costanti per nomi di tabelle e colonne
│   ├── app_config.php     ← Costanti generali (dimensioni file, limiti, ecc.)
│   ├── helpers.php        ← Funzioni utili usate ovunque
│   ├── messages.php       ← Testi di errori e messaggi di sistema
│   ├── env.php            ← Legge le variabili d'ambiente dal file .env
│   └── mail.php           ← Configurazione per l'invio di email
│
├── controllers/           ← Logica di business (cosa deve fare l'app)
│   ├── AuthController.php       ← Login, registrazione, logout
│   ├── EventoController.php     ← Lista, ricerca, dettaglio eventi
│   ├── CartController.php       ← API JSON del carrello
│   ├── BigliettoController.php  ← Acquisto e validazione biglietti
│   ├── PageController.php       ← Navigazione e meta tag SEO
│   ├── AdminController.php      ← Pannello amministrazione
│   ├── UserController.php       ← Profilo, password, account
│   ├── LocationController.php   ← Gestione location
│   ├── ManifestazioneController.php ← Gestione manifestazioni
│   ├── RecensioneController.php ← Gestione recensioni
│   ├── OrdineController.php     ← Storico ordini
│   ├── CollaborazioneController.php ← Inviti collaboratori
│   ├── AvatarController.php     ← Upload foto profilo
│   └── NotificaController.php   ← Sistema notifiche
│
├── models/                ← Accesso ai dati (query SQL)
│   ├── Evento.php         ← Operazioni sulla tabella eventi
│   ├── Utente.php         ← Operazioni sulla tabella utenti
│   ├── Biglietto.php      ← Operazioni sulla tabella biglietti
│   ├── Ordine.php         ← Operazioni sulla tabella ordini
│   ├── Location.php       ← Operazioni sulla tabella locations
│   ├── Manifestazione.php ← Operazioni sulla tabella manifestazioni
│   └── Recensione.php     ← Operazioni sulla tabella recensioni
│
├── views/                 ← Pagine HTML (cosa vede l'utente)
│   ├── layouts/
│   │   └── main.php       ← Template principale: header, footer, ecc.
│   ├── home.php           ← Homepage
│   ├── evento_dettaglio.php← Pagina dettaglio evento
│   ├── eventi_lista.php   ← Lista eventi
│   ├── login.php          ← Pagina login
│   ├── register.php       ← Pagina registrazione
│   ├── checkout.php       ← Pagina acquisto
│   └── ...
│
├── lib/                   ← Librerie riutilizzabili
│   ├── QueryBuilder.php   ← Costruttore di query SQL
│   └── Validator.php      ← Validatore di dati in input
│
├── public/                ← File statici accessibili dal browser
│   ├── css/main.css       ← Stili principali
│   ├── css/mobile.css     ← Stili per mobile
│   └── script.js          ← JavaScript del frontend
│
└── .env                   ← Credenziali e configurazioni sensibili (non versionato)
```

---

## 2. Architettura MVC

### Cos'è il pattern MVC?

MVC sta per **Model — View — Controller**. È un modo di organizzare il codice dividendolo in tre ruoli ben separati. Pensa a un ristorante:

- Il **Controller** è il cameriere: riceve l'ordine del cliente e coordina tutto
- Il **Model** è la cucina: prepara il cibo (i dati) seguendo le ricette (le query SQL)
- La **View** è il piatto servito: la presentazione finale che vede il cliente

Questa separazione rende il codice più ordinato e facile da modificare: se vuoi cambiare come appare una pagina, tocchi solo la View. Se vuoi cambiare come vengono recuperati i dati, tocchi solo il Model.

### Come funziona in EventsMaster

```
Browser dell'utente
        |
        | HTTP Request (es. GET index.php?action=view_evento&id=5)
        v
+-------------------+
|    index.php      |  ← Front Controller: punto di ingresso unico
|  (router/switch)  |  ← Legge l'azione richiesta e carica il controller giusto
+-------------------+
        |
        | Carica e chiama il controller
        v
+-------------------+
|   Controller      |  ← Coordina la richiesta
|  (es. Evento-     |  ← Verifica permessi, valida input
|   Controller.php) |  ← Chiama i model per i dati
+-------------------+
        |
        | Chiede i dati
        v
+-------------------+
|     Model         |  ← Esegue le query SQL
|  (es. Evento.php) |  ← Ritorna array di dati PHP
+-------------------+
        |
        | Dati grezzi (array)
        v
+-------------------+
|   Controller      |  ← Salva i dati in sessione
|   (di ritorno)    |  ← Imposta la pagina da mostrare
+-------------------+
        |
        | require main.php
        v
+-------------------+
|   View (main.php) |  ← Legge i dati dalla sessione
| + pagina specifica|  ← Genera l'HTML finale
+-------------------+
        |
        | HTML
        v
Browser dell'utente ← Mostra la pagina all'utente
```

### Perché usare la sessione per passare i dati?

In questo progetto il controller salva i dati in `$_SESSION` prima che `main.php` venga incluso. Questo è un approccio semplice che evita variabili globali. Ad esempio:

```php
// Nel controller
$_SESSION['evento_corrente'] = getEventoById($pdo, $id);
setPage('evento_dettaglio');

// In views/evento_dettaglio.php
$evento = $_SESSION['evento_corrente'];
echo $evento['Nome'];
```

---

## 3. Il Front Controller — index.php

### Ruolo

`index.php` è **l'unico punto di ingresso** dell'applicazione. Ogni pagina, ogni azione, ogni richiesta HTTP passa da qui. Non esistono altri file accessibili direttamente dal browser (tranne `sitemap.php`).

### Come funziona

```php
// 1. Avvia la sessione e connette il database
require_once 'config/session.php';
require_once 'config/database.php';

// 2. Imposta header HTTP di sicurezza
header('X-Frame-Options: SAMEORIGIN');   // Impedisce embedding in iframe
header('X-Content-Type-Options: nosniff'); // Impedisce MIME sniffing

// 3. Recupera i messaggi flash (successo/errore) e li cancella dalla sessione
$msg   = $_SESSION['msg']   ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['msg'], $_SESSION['error']);

// 4. Determina l'azione richiesta
// Se è un POST, usa il valore del campo 'action' nel form
// Altrimenti usa il parametro 'action' nell'URL (GET)
$action = $_POST['action'] ?? $_GET['action'] ?? null;

// 5. Switch: in base all'azione, carica il controller giusto
switch ($action) {
    case 'login':
        require_once 'controllers/AuthController.php';
        handleAuth($pdo, $action);
        break;

    case 'view_evento':
        require_once 'controllers/EventoController.php';
        handleEvento($pdo, $action);
        break;

    // ... altri casi ...

    default:
        // Se nessuna azione è specificata, mostra la homepage
        setPage('home');
        break;
}

// 6. Renderizza il layout con la pagina impostata dal controller
require_once 'views/layouts/main.php';
```

### Il sistema dei messaggi flash

Un **messaggio flash** è un messaggio che appare una sola volta dopo un'azione (es. "Login effettuato con successo!"). Funziona così:

1. Il controller salva il messaggio in sessione: `$_SESSION['msg'] = 'Successo!'`
2. Fa un redirect: `header('Location: index.php'); exit;`
3. Nella nuova richiesta, `index.php` legge il messaggio dalla sessione e lo cancella subito
4. Il layout lo mostra all'utente

In questo modo il messaggio appare una volta sola e scompare al prossimo aggiornamento della pagina.

### Le azioni disponibili (elenco completo)

| Categoria | Azione | Descrizione |
|---|---|---|
| **Autenticazione** | `login`, `register`, `logout` | Operazioni di accesso |
| | `show_login`, `show_register` | Visualizza i form |
| **Navigazione** | `home`, `list_eventi`, `category`, `view_evento` | Pagine pubbliche |
| | `search_eventi` | Ricerca eventi |
| **Carrello (API)** | `cart_add`, `cart_get`, `cart_update`, `cart_remove` | Gestione carrello |
| | `cart_clear`, `cart_count`, `check_availability` | Altre operazioni carrello |
| **Biglietti** | `checkout`, `acquista`, `valida`, `view_biglietto` | Acquisto e gestione |
| **Profilo** | `profilo`, `update_profile`, `miei_biglietti`, `miei_ordini` | Area personale |
| **Password** | `cambia_password`, `update_password`, `recupera_password` | Gestione password |
| | `send_reset_email`, `reset_password`, `do_reset_password` | Reset via email |
| **Email** | `verify_email`, `resend_verification` | Verifica indirizzo |
| **Account** | `elimina_account`, `delete_account` | Eliminazione account |
| **Admin** | `admin_dashboard`, `admin_users`, `admin_events` | Pannello admin |
| | `admin_update_role`, `admin_delete_user`, `admin_create_event` | Gestione admin |
| **Location** | `list_locations`, `create_location`, `edit_location`, `save_location` | Gestione sedi |
| **Manifestazioni** | `list_manifestazioni`, `create_manifestazione`, `save_manifestazione` | Gestione manifestazioni |
| **Collaborazione** | `invite_collaborator`, `accept_collaboration`, `get_collaborators` | Collaboratori |
| **Avatar** | `upload_avatar`, `get_avatar`, `delete_avatar` | Foto profilo |

> **Nota sulle API JSON**: le azioni del carrello e alcune azioni admin non renderizzano HTML ma rispondono con JSON e terminano con `exit`. Questo le rende utilizzabili via JavaScript (AJAX).

---

## 4. Configurazione — cartella config/

### 4.1 session.php

**Ruolo**: Configura e avvia la sessione PHP in modo sicuro.

Una **sessione** PHP è un meccanismo per ricordare informazioni tra una richiesta e l'altra. Quando un utente fa login, i suoi dati vengono salvati nella sessione sul server. Ad ogni richiesta successiva, PHP riconosce l'utente grazie a un cookie con l'ID di sessione.

```php
ini_set('session.gc_maxlifetime', 7200);  // La sessione dura 2 ore (7200 secondi)
ini_set('session.cookie_lifetime', 7200); // Il cookie di sessione dura 2 ore
ini_set('session.cookie_httponly', 1);    // JavaScript NON può leggere il cookie (protezione XSS)
ini_set('session.cookie_samesite', 'Lax');// Il cookie non viene inviato a siti esterni (protezione CSRF)
ini_set('session.use_strict_mode', 1);   // Rifiuta ID di sessione inventati da attaccanti

session_start(); // Avvia la sessione
```

> La riga `session.cookie_secure` è commentata: andrebbe attivata in produzione dove si usa HTTPS, per fare in modo che il cookie venga inviato solo su connessioni cifrate.

### 4.2 database.php

**Ruolo**: Crea la connessione al database MySQL usando PDO.

**PDO** (PHP Data Objects) è una libreria PHP che permette di comunicare con vari database in modo uniforme e sicuro. Rispetto al vecchio `mysql_query()`, PDO supporta i **prepared statements** che proteggono dalle SQL injection.

```php
$pdo = new PDO(
    "mysql:host=localhost;dbname=5cit_eventsMaster;charset=utf8mb4",
    'root',     // utente DB (letto dal file .env)
    '',         // password DB (letta dal file .env)
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Lancia eccezioni sugli errori SQL
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Risultati come array ['colonna' => 'valore']
        PDO::ATTR_EMULATE_PREPARES   => false                    // Usa prepared statements veri (più sicuro)
    ]
);
```

La variabile `$pdo` viene resa disponibile globalmente e viene passata a tutti i controller e model che ne hanno bisogno.

**Cosa succede se la connessione fallisce?**

- In modalità debug: mostra il messaggio di errore completo
- In produzione: mostra un messaggio generico e scrive l'errore nel log

### 4.3 database_schema.php

**Ruolo**: Centralizza in costanti PHP tutti i nomi di tabelle e colonne del database.

**Perché usare costanti invece di stringhe?**

Immagina di dover rinominare la tabella `eventi` in `event`. Senza costanti dovresti cercare e modificare la stringa `'eventi'` in decine di file. Con le costanti basta cambiare `define('TABLE_EVENTI', 'eventi')` in un posto solo.

```php
// Nomi delle tabelle
define('TABLE_UTENTI',       'utenti');
define('TABLE_EVENTI',       'eventi');
define('TABLE_LOCATIONS',    'locations');
define('TABLE_BIGLIETTI',    'biglietti');
// ... ecc.

// Nomi delle colonne della tabella utenti
define('COL_UTENTI_ID',      'id');
define('COL_UTENTI_NOME',    'Nome');
define('COL_UTENTI_EMAIL',   'Email');
define('COL_UTENTI_RUOLO',   'ruolo');
// ... ecc.

// Valori enumerati (stati e categorie)
define('STATO_BIGLIETTO_CARRELLO',   'carrello');
define('STATO_BIGLIETTO_ACQUISTATO', 'acquistato');
define('STATO_BIGLIETTO_VALIDATO',   'validato');

define('RUOLO_USER',     'user');
define('RUOLO_PROMOTER', 'promoter');
define('RUOLO_MOD',      'mod');
define('RUOLO_ADMIN',    'admin');

define('CATEGORIA_CONCERTI', 'concerti');
define('CATEGORIA_TEATRO',   'teatro');
// ... ecc.
```

Il file contiene anche due funzioni helper per costruire parti di query SQL:

**`buildSelect(string $table, array $columns, string $alias = ''): string`**

- Costruisce la parte `SELECT colonna1, colonna2` di una query
- Parametri: nome tabella, array di colonne, alias opzionale
- Ritorna: stringa con le colonne separate da virgola
- Esempio: `buildSelect(TABLE_UTENTI, [COL_UTENTI_ID, COL_UTENTI_NOME], 'u')` → `"u.id, u.Nome"`

**`buildWhere(string $column, string $alias = ''): string`**

- Costruisce una condizione WHERE
- Ritorna: stringa `"colonna = ?"`
- Esempio: `buildWhere(COL_UTENTI_ID, 'u')` → `"u.id = ?"`

### 4.4 app_config.php

**Ruolo**: Definisce tutte le costanti di configurazione dell'applicazione.

```php
define('APP_NAME',    'EventsMaster');
define('BASE_URL',    'http://localhost/eventsMaster');

// Carrello
define('CART_EXPIRATION_HOURS', 24);   // Biglietti nel carrello scadono dopo 24h
define('MAX_TICKETS_PER_ORDER', 10);   // Massimo 10 biglietti per ordine

// Upload file
define('AVATAR_MAX_SIZE', 2 * 1024 * 1024); // 2MB per avatar
define('EVENT_IMAGE_MAX_SIZE', 5 * 1024 * 1024); // 5MB per immagini evento

// Sicurezza
define('PASSWORD_MIN_LENGTH', 6);      // Password minimo 6 caratteri
define('MAX_LOGIN_ATTEMPTS', 5);       // Blocca dopo 5 tentativi falliti
define('LOGIN_LOCKOUT_MINUTES', 15);   // Blocco dura 15 minuti

// Token
define('RESET_TOKEN_EXPIRY_HOURS', 1);           // Token reset password valido 1 ora
define('VERIFICATION_TOKEN_EXPIRY_HOURS', 24);   // Token verifica email valido 24 ore

// Paginazione
define('EVENTS_PER_PAGE', 12);         // 12 eventi per pagina

// Fuso orario
date_default_timezone_set('Europe/Rome');
```

Il file definisce anche queste funzioni helper:

**`formatDateTime(?string $datetime): string`** — Converte `2024-12-25 20:00:00` in `25/12/2024 20:00`

**`asset(string $path): string`** — Restituisce l'URL completo di un file nella cartella assets. Esempio: `asset('img/logo.png')` → `http://localhost/eventsMaster/assets/img/logo.png`

**`url(string $path = ''): string`** — Restituisce l'URL completo di una pagina. Esempio: `url('index.php?action=login')` → `http://localhost/eventsMaster/index.php?action=login`

**`isDebug(): bool`** — Ritorna `true` se l'app è in modalità debug (mostra errori dettagliati)

**`isProduction(): bool`** — Ritorna `true` se l'app è in produzione

### 4.5 helpers.php

**Ruolo**: Raccolta di funzioni utili usate in tutto il progetto. È il file più importante dopo `index.php`.

---

#### `e(?string $string): string`

**Cosa fa**: Rende sicura una stringa per l'output HTML. Converte i caratteri speciali in entità HTML.

**Perché è fondamentale**: Senza questa funzione, se un utente inserisce `<script>alert('hack')</script>` come nome, quel codice verrebbe eseguito nel browser di chi lo legge (attacco XSS). Con `e()`, viene mostrato come testo letterale.

```php
// Input dell'utente (potenzialmente pericoloso)
$nome = '<script>alert("xss")</script>';

// SBAGLIATO — esegue il codice JavaScript!
echo $nome;

// CORRETTO — mostra il testo innocuo &lt;script&gt;...
echo e($nome);
```

---

#### `redirect(string $location, ?string $msg = null, ?string $error = null): void`

**Cosa fa**: Reindirizza l'utente a un altro URL, con un messaggio opzionale.

**Come funziona**:

1. Salva il messaggio in sessione (se fornito)
2. Invia l'header HTTP `Location:` al browser
3. Chiama `exit` per fermare l'esecuzione del codice

```php
// Redirect semplice
redirect('index.php');

// Redirect con messaggio di successo
redirect('index.php?action=show_login', 'Registrazione completata!');

// Redirect con messaggio di errore
redirect('index.php?action=show_login', null, 'Email o password errati');
```

---

#### `generateCsrfToken(): string`

**Cosa fa**: Genera (o recupera dalla sessione) un token CSRF.

Un **token CSRF** è una stringa casuale segreta che viene inserita in ogni form. Quando il form viene inviato, il server verifica che il token sia corretto. Questo impedisce a siti terzi di far inviare richieste a nome dell'utente senza che lui lo sappia.

```php
// Genera un token di 64 caratteri esadecimali (32 byte = 64 hex)
$token = bin2hex(random_bytes(32));

// Il token viene salvato in sessione (generato una sola volta per sessione)
$_SESSION['csrf_token'] = $token;
```

---

#### `verifyCsrfToken(string $token): bool`

**Cosa fa**: Verifica che il token CSRF inviato dal form corrisponda a quello in sessione.

Usa `hash_equals()` invece del normale `==` per prevenire i **timing attack** (attacchi che misurano il tempo di risposta per indovinare il token carattere per carattere).

```php
// Verifica sicura (hash_equals confronta in tempo costante)
return hash_equals($_SESSION['csrf_token'], $token);
```

---

#### `verifyCsrf(): bool`

**Cosa fa**: Wrapper semplificato che legge il token direttamente da `$_POST`.

```php
// Equivale a: verifyCsrfToken($_POST['csrf_token'] ?? '')
if (!verifyCsrf()) {
    redirect('index.php', null, 'Richiesta non valida');
}
```

---

#### `csrfField(): string`

**Cosa fa**: Genera il codice HTML del campo hidden con il token CSRF. Va inserito in ogni `<form>` che usa il metodo POST.

```php
// Genera: <input type="hidden" name="csrf_token" value="abc123...">
echo csrfField();
```

---

#### `isLoggedIn(): bool`

**Cosa fa**: Verifica se c'è un utente autenticato controllando la sessione.

```php
// Ritorna true se $_SESSION['user_id'] esiste
return isset($_SESSION['user_id']);
```

---

#### `requireAuth(): void`

**Cosa fa**: Middleware che blocca l'accesso alle risorse protette. Se l'utente non è loggato, lo reindirizza al login.

```php
// Uso tipico all'inizio di una funzione protetta
function viewBiglietto(PDO $pdo): void {
    requireAuth(); // Se non loggato → redirect al login
    // ... resto del codice
}
```

---

#### `requireAdmin(): void`

**Cosa fa**: Come `requireAuth()` ma richiede il ruolo admin. Se l'utente è loggato ma non è admin, lo rimanda alla homepage.

---

#### `requireRole(string $role): void`

**Cosa fa**: Middleware generico che verifica il ruolo minimo richiesto.

```php
// Richiede almeno il ruolo "promoter"
requireRole(RUOLO_PROMOTER);
```

---

#### `sanitize(string $string): string`

**Cosa fa**: Pulisce una stringa rimuovendo tag HTML e spazi superflui.

```php
// Input: "  <b>Ciao</b>  Mondo  "
// Output: "Ciao Mondo"
return trim(strip_tags($string));
```

---

#### `formatPrice(float $price): string`

**Cosa fa**: Formatta un numero come prezzo in euro con notazione italiana.

```php
formatPrice(25.5)  // → "25,50 €"
formatPrice(100)   // → "100,00 €"
```

---

#### `formatDate(string $date): string`

**Cosa fa**: Converte una data dal formato MySQL al formato italiano.

```php
formatDate('2024-12-25')  // → "25/12/2024"
```

---

#### `formatTime(?string $time): string`

**Cosa fa**: Formatta un orario rimuovendo i secondi.

```php
formatTime('20:30:00')  // → "20:30"
```

---

#### `logError(string $message, array $context = []): void`

**Cosa fa**: Scrive un messaggio nel file di log `logs/error.log`, con timestamp e dati di contesto opzionali.

```php
logError("Login fallito: email non trovata", ['email' => $email]);
// Scrive: [2024-12-25 20:30:00] Login fallito: email non trovata {"email":"test@test.com"}
```

---

#### `calcolaPrezzoBiglietto(float $prezzoBase, float $modificatoreTipo, float $moltiplicatoreSettore): float`

**Cosa fa**: Calcola il prezzo finale di un biglietto applicando i modificatori.

**Formula**: `(prezzoBase + modificatoreTipo) * moltiplicatoreSettore`

```php
// Evento a 20€, biglietto VIP +10€, settore Palco ×1.5
calcolaPrezzoBiglietto(20.0, 10.0, 1.5)  // → 45.00€

// Evento a 20€, biglietto Standard +0€, settore Platea ×1.0
calcolaPrezzoBiglietto(20.0, 0.0, 1.0)   // → 20.00€
```

### 4.6 messages.php

**Ruolo**: Centralizza tutti i messaggi di testo (errori, successi) in costanti.

Questo evita testo hardcodato sparso nel codice e facilita la traduzione o la modifica dei messaggi.

```php
define('ERR_INVALID_CREDENTIALS', 'Email o password non corretti.');
define('ERR_INVALID_CSRF',        'Richiesta non valida. Riprova.');
define('ERR_EMAIL_ALREADY_EXISTS','Esiste già un account con questa email.');
define('MSG_SUCCESS_LOGIN',       'Benvenuto, %s!');  // %s = nome utente
define('MSG_SUCCESS_LOGOUT',      'Logout effettuato con successo.');
```

Il file include anche due funzioni:

**`setSuccessMessage(string $message): void`** — Salva un messaggio di successo in sessione

**`setErrorMessage(string $message): void`** — Salva un messaggio di errore in sessione

**`message(string $template, mixed ...$args): string`** — Formatta un messaggio con parametri (usa `sprintf` internamente)

### 4.7 env.php

**Ruolo**: Legge le variabili dal file `.env` e le rende disponibili tramite la funzione `env()`.

Il file `.env` contiene le credenziali sensibili (password del database, chiavi API) e **non viene versionato** con git, quindi non è visibile a chi scarica il codice sorgente.

```php
// Uso della funzione env()
$host = env('DB_HOST', 'localhost');  // Legge DB_HOST, usa 'localhost' come default

// Contenuto tipico del file .env
// DB_HOST=localhost
// DB_NAME=5cit_eventsMaster
// DB_USER=root
// DB_PASS=
// APP_DEBUG=true
```

### 4.8 mail.php

**Ruolo**: Configura il servizio di invio email e fornisce funzioni per inviare email di sistema.

Fornisce le seguenti funzioni:

**`sendVerificationEmail(string $email, string $nome, string $token): bool`** — Invia l'email di verifica account con un link univoco

**`sendPasswordResetEmail(string $email, string $nome, string $token): bool`** — Invia l'email per il reset della password

---

## 5. Le librerie — cartella lib/

### 5.1 QueryBuilder.php

**Ruolo**: Fornisce un'interfaccia fluente (a catena) per costruire query SQL senza scrivere SQL manualmente.

**Cos'è un Query Builder?** È una classe che permette di costruire query SQL usando metodi PHP invece di stringhe SQL. Il codice risulta più leggibile e meno soggetto a errori di sintassi.

**La classe `QueryBuilder`** viene istanziata tramite la funzione helper `table()`:

```php
// Crea un QueryBuilder per la tabella utenti
$qb = table($pdo, TABLE_UTENTI);
```

#### Metodi disponibili

**`select(array $columns): self`** — Specifica le colonne da selezionare

```php
table($pdo, TABLE_UTENTI)->select([COL_UTENTI_ID, COL_UTENTI_EMAIL])->get();
// SQL: SELECT id, Email FROM utenti
```

**`where(string $column, $value, string $operator = '='): self`** — Aggiunge una condizione WHERE

```php
table($pdo, TABLE_UTENTI)->where(COL_UTENTI_EMAIL, 'mario@esempio.it')->first();
// SQL: SELECT * FROM utenti WHERE Email = 'mario@esempio.it' LIMIT 1
```

**`whereIn(string $column, array $values): self`** — Condizione WHERE IN

```php
table($pdo, TABLE_BIGLIETTI)->whereIn(COL_BIGLIETTI_ID, [1, 2, 3])->get();
// SQL: SELECT * FROM biglietti WHERE id IN (1, 2, 3)
```

**`whereNull(string $column): self`** / **`whereNotNull(string $column): self`** — Controlla se un campo è NULL

**`join(...)` / `leftJoin(...)`** — Aggiunge JOIN tra tabelle

**`orderBy(string $column, string $direction = 'ASC'): self`** — Ordina i risultati

**`limit(int $limit): self`** / **`offset(int $offset): self`** — Paginazione

**`get(): array`** — Esegue la query e ritorna tutti i risultati come array

**`first(): ?array`** — Esegue la query e ritorna solo il primo risultato (o null)

**`count(): int`** — Conta il numero di risultati

**`exists(): bool`** — Verifica se esistono risultati (equivale a `count() > 0`)

**`insert(array $data): int`** — Inserisce un record e ritorna l'ID generato

```php
$id = table($pdo, TABLE_UTENTI)->insert([
    COL_UTENTI_NOME   => 'Mario',
    COL_UTENTI_EMAIL  => 'mario@esempio.it',
    COL_UTENTI_RUOLO  => RUOLO_USER
]);
// SQL: INSERT INTO utenti (Nome, Email, ruolo) VALUES ('Mario', 'mario@esempio.it', 'user')
```

**`update(array $data): int`** — Aggiorna i record che corrispondono al WHERE, ritorna il numero di righe modificate

```php
table($pdo, TABLE_UTENTI)
    ->where(COL_UTENTI_ID, 5)
    ->update([COL_UTENTI_RUOLO => RUOLO_ADMIN]);
// SQL: UPDATE utenti SET ruolo = 'admin' WHERE id = 5
```

**`delete(): int`** — Cancella i record che corrispondono al WHERE, ritorna il numero di righe cancellate

```php
table($pdo, TABLE_BIGLIETTI)
    ->where(COL_BIGLIETTI_ID, 42)
    ->where(COL_BIGLIETTI_ID_UTENTE, 5)
    ->delete();
// SQL: DELETE FROM biglietti WHERE id = 42 AND idUtente = 5
```

### 5.2 Validator.php

**Ruolo**: Valida i dati in input (form, API) in modo centralizzato e leggibile.

**Cos'è la validazione?** Prima di usare i dati inseriti dall'utente, bisogna controllare che siano nel formato atteso: che l'email sia una email vera, che la password abbia abbastanza caratteri, che la data sia una data valida, ecc.

La classe `Validator` si usa tramite la funzione helper `validate()`:

```php
$validator = validate($_POST)    // Inizia la validazione dei dati POST
    ->required('email')          // 'email' non deve essere vuoto
    ->email('email')             // 'email' deve essere un formato email valido
    ->required('password')       // 'password' non deve essere vuoto
    ->min('password', 6);        // 'password' deve avere almeno 6 caratteri

if ($validator->fails()) {
    // La validazione è fallita
    $primoErrore = $validator->firstError(); // es. "Il campo email è obbligatorio"
    redirect('index.php?action=show_login', null, $primoErrore);
}
```

#### Regole di validazione disponibili

**`required(string $field, string $message = null): self`** — Il campo non deve essere vuoto

**`email(string $field, string $message = null): self`** — Il campo deve essere un'email valida

**`min(string $field, int $min, string $message = null): self`** — Il campo deve avere almeno N caratteri

**`max(string $field, int $max, string $message = null): self`** — Il campo non deve superare N caratteri

**`matches(string $field, string $otherField, string $message = null): self`** — Due campi devono essere identici (es. password e conferma password)

**`numeric(string $field, string $message = null): self`** — Il campo deve essere un numero

**`in(string $field, array $values, string $message = null): self`** — Il campo deve essere uno dei valori nell'array

**`date(string $field, string $format = 'Y-m-d', string $message = null): self`** — Il campo deve essere una data valida nel formato specificato

**`future(string $field, string $message = null): self`** — Il campo deve essere una data futura

**`url(string $field, string $message = null): self`** — Il campo deve essere un URL valido

**`custom(string $field, callable $callback, string $message = null): self`** — Validazione personalizzata con una funzione arbitraria

#### Metodi di risultato

**`fails(): bool`** — `true` se almeno una validazione è fallita

**`passes(): bool`** — `true` se tutte le validazioni sono passate

**`firstError(): ?string`** — Ritorna il primo messaggio di errore

**`errors(): array`** — Ritorna tutti gli errori organizzati per campo

**`errorsAsString(): string`** — Ritorna tutti gli errori come stringa unica

---

## 6. I Controller

I controller contengono la logica di business: verificano permessi, validano input, chiamano i model per i dati e impostano la view da mostrare.

**Nota importante**: I controller in questo progetto sono file con funzioni globali, non classi. Vengono caricati con `require_once` solo quando servono, risparmiando memoria.

### 6.1 AuthController.php

**Ruolo**: Gestisce login, registrazione e logout.

---

#### `handleAuth(PDO $pdo, string $action): void`

Router interno: smista verso la funzione corretta in base all'azione.

---

#### `loginAction(PDO $pdo): void`

**Flusso completo del login**:

```
1. Verifica token CSRF → se non valido, redirect con errore
2. Valida email e password con Validator
3. Sanitizza email (minuscola, spazi rimossi)
4. Cerca l'utente per email nel database
   → Se non esiste: log errore + redirect con messaggio generico
5. Verifica la password con password_verify()
   → Se sbagliata: log errore + redirect con messaggio generico
6. Rigenera l'ID di sessione (protezione session fixation)
7. Salva in $_SESSION:
   - user_id, user_nome, user_cognome, user_email, user_ruolo
8. Se l'utente aveva biglietti nel carrello locale (localStorage),
   li aggiunge al carrello server-side
9. Redirect alla homepage con messaggio di benvenuto
```

> Il messaggio di errore è generico ("Email o password non corretti") e non specifica se è l'email o la password ad essere sbagliata, per non dare informazioni utili a chi tenta di indovinare le credenziali.

---

#### `registerAction(PDO $pdo): void`

**Flusso completo della registrazione**:

```
1. Verifica token CSRF
2. Valida: nome, cognome, email (formato), password (min 6 caratteri), corrispondenza password
3. Sanitizza i dati
4. Controlla che l'email non sia già registrata
5. Crea l'utente nel database con la password hashata con bcrypt
6. Genera un token univoco per la verifica email (scade in 24h)
7. Invia l'email di verifica
8. Redirect alla pagina di login con messaggio di successo
```

---

#### `logoutAction(): void`

```
1. Distrugge la sessione corrente (rimuove tutti i dati)
2. Crea una nuova sessione pulita
3. Redirect alla homepage con messaggio di conferma
```

### 6.2 EventoController.php

**Ruolo**: Gestisce la visualizzazione, la ricerca e le operazioni CRUD sugli eventi.

---

#### `handleEvento(PDO $pdo, string $action): void`

Router interno per le azioni sugli eventi. Le azioni di modifica (`create_evento`, `update_evento`, `delete_evento`) vengono precedute da `requireAdmin()`.

---

#### `viewEvento(PDO $pdo): void`

Mostra la pagina dettaglio di un evento.

```
1. Legge l'ID evento dall'URL ($_GET['id'])
2. Valida che l'ID sia un numero positivo
3. Chiama getEventoById() per ottenere i dati dell'evento
4. Carica i dati correlati:
   - Intrattenitori dell'evento
   - Recensioni degli utenti
   - Media dei voti
5. Salva tutto in $_SESSION per la view
6. Imposta i meta tag SEO con il nome dell'evento
7. Imposta la pagina da mostrare: 'evento_dettaglio'
```

---

#### `listEventi(PDO $pdo): void`

Carica tutti gli eventi e imposta la view lista.

---

#### `searchEventi(PDO $pdo): void`

```
1. Verifica CSRF (è un POST dal form di ricerca)
2. Legge il termine di ricerca da $_POST['query']
3. Sanitizza l'input
4. Chiama searchEventiByQuery() che cerca su nome evento, manifestazione, location
5. Salva i risultati in sessione
6. Imposta la pagina: 'eventi_ricerca'
```

---

#### `listByCategory(PDO $pdo, string $category): void`

Filtra gli eventi per categoria. Le categorie valide sono: `concerti`, `teatro`, `sport`, `comedy`, `cinema`, `famiglia`. Se viene passata una categoria non valida, mostra tutti gli eventi.

---

#### `createEventoAction`, `updateEventoAction`, `deleteEventoAction`

Operazioni CRUD che richiedono il ruolo admin. Tutte verificano il CSRF, sanitizzano i dati, chiamano il model corrispondente e reindirizzano con un messaggio di esito.

### 6.3 CartController.php

**Ruolo**: API JSON per la gestione del carrello. Tutte le funzioni rispondono con JSON invece di HTML.

**Come funziona il carrello**: Il carrello non è in localStorage ma nel database. I biglietti vengono creati nel DB con stato `'carrello'` quando l'utente li aggiunge. Al checkout lo stato cambia in `'acquistato'`. Questo garantisce:

- Il carrello persiste tra sessioni diverse
- La disponibilità viene verificata in tempo reale
- Non si possono "rubare" posti che qualcun altro ha già nel carrello

---

#### `handleCart(PDO $pdo, string $action): void`

Router che imposta l'header `Content-Type: application/json` e smista le azioni.

---

#### `addToCartApi(PDO $pdo): void`

```
POST: idEvento, idClasse, quantita, (idSettore)

1. Verifica login e CSRF
2. Valida i parametri con Validator
3. Controlla che la quantità non superi MAX_TICKETS_PER_ORDER (10)
4. Verifica che il tipo di biglietto esista nel database
5. Verifica che l'evento esista
6. Controlla la disponibilità di posti
7. Inizia una transazione DB
8. Crea N biglietti con stato 'carrello'
9. Commit della transazione
10. Ritorna JSON con i biglietti creati e il nuovo conteggio del carrello
```

---

#### `getCartApi(PDO $pdo): void`

Ritorna il carrello dell'utente in formato JSON. Se non è loggato, ritorna un carrello vuoto.

---

#### `removeFromCartApi(PDO $pdo): void`

```
POST: idBiglietto

1. Verifica login e CSRF
2. Verifica che il biglietto appartenga all'utente (IDOR protection)
3. Verifica che il biglietto sia nello stato 'carrello'
4. Elimina il biglietto dal database
5. Ritorna il carrello aggiornato
```

---

#### `checkAvailabilityApi(PDO $pdo): void`

Verifica quanti posti sono disponibili per un evento. Non richiede login.

---

#### `formatCartForJs(array $cart): array`

Trasforma l'array dei biglietti dal formato database al formato atteso dal JavaScript frontend. Converte le immagini in base64 per l'embedding.

---

#### `jsonResponse(array $data, int $statusCode = 200): void`

Imposta il codice HTTP, serializza l'array in JSON, lo manda al browser e termina l'esecuzione.

### 6.4 BigliettoController.php

**Ruolo**: Gestisce l'acquisto definitivo dei biglietti e la loro visualizzazione.

---

#### `acquistaBiglietto(PDO $pdo): void`

```
1. Richiede autenticazione (requireAuth)
2. Blocca l'acquisto per admin, mod e promoter
   (gli organizzatori non possono comprare biglietti)
3. Verifica CSRF
4. Valida il metodo di pagamento e i dati del carrello
5. Determina se il carrello viene dal server (DB) o dal localStorage
6. Chiama acquistaFromServerCart() o acquistaFromLocalCart()
```

---

#### `acquistaFromServerCart(PDO $pdo, array $cartData, string $metodo): void`

```
1. Verifica che ci siano biglietti da acquistare
2. Verifica che tutti abbiano nome e cognome compilati
3. Inizia una transazione DB
4. Per ogni biglietto:
   a. Verifica che appartenga all'utente e sia nel carrello
   b. Aggiorna i dati dell'intestatario
5. Chiama confirmPurchase() → cambia lo stato da 'carrello' a 'acquistato'
6. Chiama assegnaPostiAutomatici() → assegna un posto fisico nel settore
7. Crea l'ordine e associa i biglietti
8. Commit
9. Redirect alla pagina dell'ordine con messaggio di successo
```

---

#### `validaBigliettoAction(PDO $pdo): void`

Usato all'ingresso dell'evento (solo admin). Verifica il QR code e marca il biglietto come `'validato'`, impedendo il riuso.

---

#### `viewBiglietto(PDO $pdo): void`

Mostra il dettaglio di un biglietto. Verifica che il biglietto appartenga all'utente loggato (protezione IDOR).

### 6.5 PageController.php

**Ruolo**: Gestisce la navigazione tra pagine e i meta tag SEO.

---

#### `setPage(string $page): void`

Salva il nome della pagina da mostrare in `$_SESSION['page']`. Il nome corrisponde al file nella cartella `views/` senza estensione.

```php
setPage('home');            // Caricherà views/home.php
setPage('evento_dettaglio'); // Caricherà views/evento_dettaglio.php
```

---

#### `getCurrentPage(): string`

Ritorna la pagina corrente dalla sessione (default `'home'`).

---

#### `setSeoMeta(string $title, string $description = '', string $robots = 'index,follow', ?string $canonical = null): void`

Imposta i meta tag SEO salvandoli in sessione. Vengono letti da `main.php` per generare i tag `<title>`, `<meta name="description">`, ecc.

```php
// Pagina pubblica
setSeoMeta(
    'Concerto di Natale',                           // Titolo
    'Acquista i biglietti per il Concerto di Natale', // Descrizione
    'index,follow'                                  // I motori di ricerca indicizzano questa pagina
);

// Pagina privata (non indicizzare)
setSeoMeta('Checkout', '', 'noindex,nofollow');
```

---

## 7. I Model

I model gestiscono esclusivamente l'accesso ai dati. Non contengono logica di business, non verificano permessi, non fanno redirect. Ricevono i parametri, eseguono le query SQL e ritornano i risultati.

### 7.1 Evento.php

**Tabella principale**: `eventi`

---

#### `getAllEventi(PDO $pdo): array`

Recupera tutti gli eventi con JOIN a manifestazioni e locations.

```sql
SELECT e.*, m.Nome as ManifestazioneName, l.Nome as LocationName
FROM eventi e
JOIN manifestazioni m ON e.idManifestazione = m.id
JOIN locations l ON e.idLocation = l.id
ORDER BY e.Data, e.OraI
```

---

#### `getEventoById(PDO $pdo, int $id): ?array`

Recupera un singolo evento per ID. Usa `LEFT JOIN` per le manifestazioni (un evento potrebbe non averne una). Ritorna `null` se non trovato.

---

#### `getEventiByManifestazione(PDO $pdo, int $idManifestazione): array`

Tutti gli eventi appartenenti a una stessa manifestazione, ordinati per data.

---

#### `getEventiProssimi(PDO $pdo, int $limit = 10): array`

Solo gli eventi con data futura (`>= CURDATE()`), limitati a N risultati. Usato nella homepage.

---

#### `createEvento(PDO $pdo, array $data): int`

INSERT nella tabella eventi. Accetta un array con: `idManifestazione`, `idLocation`, `Nome`, `PrezzoNoMod`, `Data`, `OraI`, `OraF`, `Programma`, `Immagine`, `Categoria`, `idCreatore`. Ritorna l'ID del nuovo evento.

---

#### `updateEvento(PDO $pdo, int $id, array $data): bool`

UPDATE su tutti i campi modificabili di un evento. Ritorna `true` se l'operazione è riuscita.

---

#### `deleteEvento(PDO $pdo, int $id): bool`

DELETE dell'evento. I biglietti associati vengono eliminati automaticamente dal database grazie alle foreign key con `ON DELETE CASCADE`.

---

#### `getIntrattenitoriEvento(PDO $pdo, int $idEvento): array`

Recupera gli artisti/intrattenitori che si esibiscono nell'evento, tramite la tabella di join `evento_intrattenitori`.

---

#### `searchEventiByQuery(PDO $pdo, string $query): array`

Ricerca testuale su nome evento, nome manifestazione e nome location usando `LIKE`:

```sql
WHERE e.Nome LIKE '%query%'
   OR m.Nome LIKE '%query%'
   OR l.Nome LIKE '%query%'
```

---

#### `getEventiByTipo(PDO $pdo, string $tipo): array`

Filtra gli eventi per categoria (es. `'concerti'`, `'teatro'`).

### 7.2 Utente.php

**Tabella principale**: `utenti`

---

#### `getAllUtenti(PDO $pdo): array`

Tutti gli utenti in ordine alfabetico per cognome e nome, tramite QueryBuilder.

---

#### `getUtenteById(PDO $pdo, int $id): ?array`

Utente specifico per ID.

---

#### `getUtenteByEmail(PDO $pdo, string $email): ?array`

Utente per email. Usato principalmente durante il login.

---

#### `createUtente(PDO $pdo, array $data): int`

Crea un nuovo utente. La password deve essere già hashata prima di chiamare questa funzione.

---

#### `updateUtente`, `deleteUtente`

Aggiorna i dati anagrafici o elimina un utente.

---

#### `updateUtentePassword(PDO $pdo, int $id, string $newPassword): bool`

Aggiorna la password hashata con bcrypt.

---

#### `setVerificationToken(PDO $pdo, int $id, string $token): bool`

Imposta il token per la verifica dell'email. Il token scade dopo 24 ore.

---

#### `verifyEmailToken(PDO $pdo, string $token): ?array`

Verifica che il token sia valido: deve esistere, l'email non deve essere già verificata, e il token non deve essere scaduto.

```sql
SELECT * FROM utenti
WHERE email_verification_token = ?
  AND verificato = 0
  AND (email_verification_token_expiry IS NULL
       OR email_verification_token_expiry > NOW())
```

---

#### `markEmailVerified(PDO $pdo, int $id): bool`

Marca l'email come verificata e cancella il token (non più necessario).

---

#### `setResetToken`, `verifyResetToken`, `clearResetToken`

Sistema per il recupero password via email. Il token scade dopo **1 ora** (più breve rispetto alla verifica email, perché i link di reset sono più sensibili).

---

#### `resetPasswordWithToken(PDO $pdo, string $token, string $newPassword): bool`

Operazione atomica: verifica il token, aggiorna la password e invalida il token in un'unica transazione logica. Se il token non è valido, ritorna `false` senza modificare nulla.

---

#### `getUserRole(PDO $pdo, int $id): string`

Legge il ruolo direttamente dal database. Usato da `isAdmin()`, `isMod()`, `isPromoter()`.

---

#### `setUserRole(PDO $pdo, int $id, string $role): bool`

Assegna un ruolo a un utente. Valida che il ruolo sia uno di quelli ammessi prima di aggiornare.

---

#### `isAdmin(?int $userId = null): bool`

Verifica se l'utente è admin. Se `$userId` è `null`, usa l'utente della sessione corrente.

---

#### `isMod(?int $userId = null): bool`

Verifica se l'utente è moderatore **o superiore** (mod o admin).

---

#### `isPromoter(?int $userId = null): bool`

Verifica se l'utente è promoter **o superiore** (promoter, mod, o admin).

---

#### `hasRole(string $requiredRole, ?int $userId = null): bool`

Verifica la gerarchia dei ruoli usando un array di pesi numerici:

```php
$hierarchy = [
    RUOLO_USER     => 1,
    RUOLO_PROMOTER => 2,
    RUOLO_MOD      => 3,
    RUOLO_ADMIN    => 4
];

// L'utente ha il ruolo richiesto se il suo peso >= peso richiesto
return $hierarchy[$role] >= $hierarchy[$requiredRole];
```

---

#### `countUtentiByRole(PDO $pdo): array`

Ritorna un array associativo con il conteggio degli utenti per ruolo. Usato per le statistiche nella dashboard admin.

---

## 8. Le View e il Layout

### 8.1 main.php — Il template principale

`views/layouts/main.php` è il **template master** dell'applicazione. Viene incluso come ultimo passo in ogni richiesta (a fine `index.php`). Contiene la struttura HTML completa e include dinamicamente la pagina specifica.

**Struttura del file**:

```
main.php
│
├── <head>
│   ├── Meta SEO (title, description, robots, canonical)
│   ├── CSS (main.css, mobile.css) con cache-busting via filemtime()
│   └── Font Awesome (icone)
│
├── <header>
│   ├── Logo
│   ├── Navigazione principale (Home, Eventi, Concerti, Teatro, Sport)
│   ├── Barra di ricerca con form POST e CSRF
│   └── Azioni utente:
│       ├── Toggle tema (chiaro/scuro/auto)
│       ├── Carrello con badge conteggio
│       └── Se loggato: dropdown utente con profilo, biglietti, ordini
│           e link al pannello del proprio ruolo (admin/mod/promoter)
│           oppure: bottone "Accedi"
│
├── <main>
│   ├── Messaggi flash di successo/errore
│   └── require "views/{$_SESSION['page']}.php"  ← CONTENUTO DINAMICO
│
├── <section> Newsletter
│
├── <footer>
│   └── Link informativi e social
│
├── Cart Sidebar (pannello laterale carrello, gestito via JS)
│
├── Modal duplicati carrello (appare al login se ci sono biglietti sovrapposti)
│
└── <script>
    ├── window.EventsMaster = { isLoggedIn, userId, csrfToken, ... }
    └── <script src="public/script.js">
```

### Come vengono caricate le pagine dinamicamente

Il meccanismo è semplice ma efficace:

1. Il controller chiama `setPage('nome_pagina')` che scrive `$_SESSION['page'] = 'nome_pagina'`
2. `main.php` legge `$_SESSION['page']` e include il file corrispondente: `require "views/{$page}.php"`
3. La view legge i dati dalla sessione (messi lì dal controller) e genera l'HTML

```php
// In EventoController.php
$_SESSION['evento_corrente'] = getEventoById($pdo, $id);
setPage('evento_dettaglio');

// In main.php
$page = $_SESSION['page'] ?? 'home';
require __DIR__ . "/../{$page}.php";

// In views/evento_dettaglio.php
$evento = $_SESSION['evento_corrente'];
echo '<h1>' . e($evento['Nome']) . '</h1>';
```

### Cache-busting per CSS e JavaScript

Per evitare che il browser mostri versioni vecchie di CSS e JS dopo un aggiornamento, i file vengono inclusi con un numero di versione basato sulla data di modifica:

```php
<link rel="stylesheet" href="public/css/main.css?v=<?= filemtime(__DIR__ . '/../../public/css/main.css') ?>">
<script src="public/script.js?v=<?= filemtime(__DIR__ . '/../../public/script.js') ?>"></script>
```

`filemtime()` ritorna il timestamp Unix dell'ultima modifica del file. Se il file cambia, il timestamp cambia, e il browser scarica la nuova versione invece di usare quella in cache.

### Comunicazione PHP → JavaScript

In fondo a `main.php`, i dati PHP vengono passati al JavaScript tramite un oggetto globale:

```php
window.EventsMaster = {
    isLoggedIn: <?= isLoggedIn() ? 'true' : 'false' ?>,
    userId: <?= isLoggedIn() ? ($_SESSION['user_id'] ?? 'null') : 'null' ?>,
    csrfToken: '<?= generateCsrfToken() ?>',
    redirectAfterLogin: <?= json_encode($_GET['redirect'] ?? '') ?>
};
```

Il file `public/script.js` usa `window.EventsMaster.csrfToken` per includere il token CSRF nelle richieste AJAX al carrello.

### SEO dinamico

`setSeoMeta()` salva i valori in sessione. `main.php` li legge e genera i tag HTML:

```php
$seoTitle  = $_SESSION['seo_title']       ?? null;
$seoDesc   = $_SESSION['seo_description'] ?? '';
$seoRobots = $_SESSION['seo_robots']      ?? 'index,follow';

// Cancella subito dalla sessione (non devono persistere)
unset($_SESSION['seo_title'], $_SESSION['seo_description'], ...);

// Output HTML
echo "<title>{$seoTitle} | EventsMaster</title>";
echo "<meta name='description' content='{$seoDesc}'>";
echo "<meta name='robots' content='{$seoRobots}'>";
```

---

## 9. Il Database

### Schema ER semplificato

```
utenti
  id, Nome, Cognome, Email, Password (hash), ruolo
  verificato, email_verification_token, reset_token
  └─── ha ordini ────────────────────────┐
  └─── ha biglietti ─────────────────────┤
  └─── scrive recensioni ────────────────┤
  └─── collabora a eventi ───────────────┤
  └─── riceve notifiche ─────────────────┘

locations
  id, Nome, Indirizzo, Citta, CAP, Regione, Capienza, Lat, Lng, idCreatore
  └─── ha settori

settori
  id, Nome, NumFile, PostiPerFila, PostiTotali, MoltiplicatorePrezzo, idLocation
  └─── associati a eventisettori
  └─── associati a settore_biglietti

manifestazioni
  id, Nome, Descrizione, DataInizio, DataFine, idCreatore
  └─── raggruppa eventi

eventi
  id, Nome, Data, OraI, OraF, PrezzoNoMod, Programma, Immagine, Categoria
  idLocation, idManifestazione, idCreatore
  └─── ha biglietti
  └─── ha intrattenitori (via evento_intrattenitori)
  └─── ha settori (via eventisettori)
  └─── ha recensioni

tipo (tipo di biglietto)
  id, nome, ModificatorePrezzo
  └─── assegnato a biglietti

biglietti
  id, Nome, Cognome, Sesso, Stato, QRCode
  idEvento, idTipo, idUtente, DataCarrello, DataAcquisto
  └─── ha posto assegnato (settore_biglietti)
  └─── appartiene a ordine (ordine_biglietti)

ordini
  id, idUtente, MetodoPagamento, DataOrdine, Totale, stato

ordine_biglietti
  idOrdine, idBiglietto

settore_biglietti
  idBiglietto, idSettore, Fila, NumPosto

intrattenitori
  id, Nome, Categoria

evento_intrattenitori
  idEvento, idIntrattenitore

recensioni
  id, idEvento, idUtente, Voto (1-5), Commento, created_at

collaboratorieventi
  id, idEvento, idUtente, invitato_da, status, token, token_expiry

notifiche
  id, tipo, destinatario_id, mittente_id, oggetto, messaggio, letta, metadata
```

### Tabelle principali in dettaglio

**`utenti`** — Gli account degli utenti

| Colonna | Tipo | Descrizione |
|---|---|---|
| `id` | INT PK | Identificativo univoco |
| `Nome` | VARCHAR | Nome |
| `Cognome` | VARCHAR | Cognome |
| `Email` | VARCHAR UNIQUE | Email (usata per login) |
| `Password` | VARCHAR | Hash bcrypt della password |
| `ruolo` | ENUM | user / promoter / mod / admin |
| `verificato` | TINYINT | 0 = email non verificata, 1 = verificata |
| `email_verification_token` | VARCHAR | Token per verifica email |
| `reset_token` | VARCHAR | Token per reset password |
| `reset_token_expiry` | DATETIME | Scadenza token reset (1 ora) |
| `Avatar` | LONGBLOB | Immagine profilo (salvata nel DB) |

**`eventi`** — Gli eventi della piattaforma

| Colonna | Tipo | Descrizione |
|---|---|---|
| `id` | INT PK | Identificativo univoco |
| `Nome` | VARCHAR | Nome dell'evento |
| `Data` | DATE | Data dello spettacolo |
| `OraI` | TIME | Ora di inizio |
| `OraF` | TIME | Ora di fine |
| `PrezzoNoMod` | DECIMAL | Prezzo base (prima dei modificatori) |
| `Programma` | TEXT | Descrizione/programma |
| `Categoria` | VARCHAR | concerti / teatro / sport / ... |
| `idLocation` | INT FK | Location dove si svolge |
| `idManifestazione` | INT FK | Manifestazione di appartenenza |
| `idCreatore` | INT FK | Utente che ha creato l'evento |

**`biglietti`** — I biglietti acquistati o nel carrello

| Colonna | Tipo | Descrizione |
|---|---|---|
| `id` | INT PK | Identificativo univoco |
| `Nome`, `Cognome` | VARCHAR | Intestatario del biglietto |
| `Sesso` | ENUM | M / F / Altro |
| `Stato` | ENUM | carrello / acquistato / validato |
| `QRCode` | VARCHAR | Codice QR univoco per validazione |
| `idEvento` | INT FK | Evento del biglietto |
| `idTipo` | INT FK | Tipo biglietto (Standard, VIP, ecc.) |
| `idUtente` | INT FK | Proprietario del biglietto |
| `DataCarrello` | DATETIME | Quando è stato aggiunto al carrello |
| `DataAcquisto` | DATETIME | Quando è stato acquistato |

**`settori`** — Le zone di una location (platea, palco, tribuna...)

| Colonna | Tipo | Descrizione |
|---|---|---|
| `Nome` | VARCHAR | Nome del settore |
| `NumFile` | INT | Numero di file di posti |
| `PostiPerFila` | INT | Posti per ogni fila |
| `PostiTotali` | INT | Capienza totale |
| `MoltiplicatorePrezzo` | DECIMAL | Coefficiente moltiplicativo del prezzo (es. 1.5 per i posti migliori) |

---

## 10. Sicurezza

Questo capitolo spiega ogni meccanismo di sicurezza implementato nel progetto.

### 10.1 Protezione XSS (Cross-Site Scripting)

**Cos'è**: Un attacco XSS inietta codice JavaScript malevolo in una pagina che viene poi eseguito nel browser di altri utenti.

**Soluzione**: La funzione `e()` (definita in `helpers.php`) applica `htmlspecialchars()` a tutte le stringhe prima di mostrarle. Caratteri come `<`, `>`, `"`, `'` vengono convertiti in entità HTML sicure.

```php
// Regola d'oro: usa sempre e() per output di variabili
echo e($evento['Nome']);
echo e($_SESSION['user_email']);
```

Inoltre:

- I cookie di sessione hanno il flag `HttpOnly` (JS non può leggerli)
- Viene inviato l'header `X-Content-Type-Options: nosniff`

### 10.2 Protezione CSRF (Cross-Site Request Forgery)

**Cos'è**: Un attacco CSRF induce un utente autenticato a compiere azioni non volute (es. cambiare email, fare acquisti) tramite un link o pagina malevola.

**Soluzione**: Ogni form POST include un token segreto generato dal server:

```html
<form method="post">
    <!-- Il campo hidden con il token CSRF -->
    <input type="hidden" name="csrf_token" value="abc123xyz...">
    ...
</form>
```

Il server verifica che il token inviato corrisponda a quello in sessione. Un sito terzo non può conoscere questo token, quindi non può forgiare richieste valide.

```php
// In ogni controller che accetta POST:
if (!verifyCsrf()) {
    redirect('index.php', null, 'Richiesta non valida');
}
```

Le richieste AJAX del JavaScript usano `window.EventsMaster.csrfToken` incluso nell'header della richiesta.

### 10.3 Protezione SQL Injection

**Cos'è**: Un attacco SQL injection inserisce codice SQL nei dati inviati (es. nei form) per manipolare le query del database.

**Soluzione**: Tutti i valori inseriti dagli utenti vengono passati come parametri nei prepared statements, mai concatenati direttamente nelle stringhe SQL.

```php
// SBAGLIATO (vulnerabile a SQL injection):
$stmt = $pdo->query("SELECT * FROM utenti WHERE email = '{$email}'");

// CORRETTO (sicuro con prepared statement):
$stmt = $pdo->prepare("SELECT * FROM utenti WHERE Email = ?");
$stmt->execute([$email]);
```

Con i prepared statements, il database tratta i parametri come dati puri, non come parte del codice SQL, anche se contengono caratteri speciali come `'` o `"`.

### 10.4 Protezione Session Fixation

**Cos'è**: Un attaccante potrebbe "fissare" l'ID di sessione prima che l'utente faccia login, poi usare lo stesso ID per accedere all'account dopo l'autenticazione.

**Soluzione**: Dopo ogni login riuscito, viene rigenerato l'ID di sessione:

```php
session_regenerate_id(true);
```

Il vecchio ID viene invalidato e viene creato uno nuovo, rendendo inutile quello eventualmente fissato dall'attaccante.

### 10.5 Hashing delle password

Le password non vengono mai salvate in chiaro nel database. Viene usato **bcrypt** tramite le funzioni native di PHP:

```php
// Al momento della registrazione
$passwordHashata = password_hash($passwordInChiaro, PASSWORD_DEFAULT);

// Al momento del login
if (password_verify($passwordInChiaro, $hashDalDatabase)) {
    // Login riuscito
}
```

`PASSWORD_DEFAULT` usa bcrypt con un salt automatico e un work factor elevato. Anche se qualcuno rubasse il database, non potrebbe risalire alle password originali senza fare un attacco a forza bruta molto costoso.

### 10.6 Protezione IDOR (Insecure Direct Object Reference)

**Cos'è**: Un utente accede a risorse di altri utenti cambiando un ID nell'URL (es. `/biglietto?id=123` → `/biglietto?id=124`).

**Soluzione**: Quando si mostra un biglietto o si modifica un elemento del carrello, il controller verifica sempre che l'oggetto appartenga all'utente loggato:

```php
// viewBiglietto - verifica proprietà
if ((int)$biglietto['idUtente'] !== (int)$_SESSION['user_id']) {
    redirect('index.php', null, 'Accesso negato.');
}

// removeFromCartApi - verifica proprietà prima di eliminare
$bigliettoOwner = table($pdo, TABLE_BIGLIETTI)
    ->where(COL_BIGLIETTI_ID, $idBiglietto)
    ->where(COL_BIGLIETTI_ID_UTENTE, $idUtente) // <-- verifica che sia del NOSTRO utente
    ->where(COL_BIGLIETTI_STATO, STATO_BIGLIETTO_CARRELLO)
    ->first();
```

### 10.7 Security Headers HTTP

```php
// Impedisce di incorporare il sito in un iframe (clickjacking)
header('X-Frame-Options: SAMEORIGIN');

// Impedisce al browser di "indovinare" il tipo di contenuto
header('X-Content-Type-Options: nosniff');

// Controlla quali info sul referrer vengono inviate
header('Referrer-Policy: strict-origin-when-cross-origin');

// Disabilita cache del browser (per pagine con dati di sessione)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
```

### 10.8 Cookie di sessione sicuri

Configurati in `session.php`:

- `HttpOnly`: JavaScript non può leggere il cookie (protezione XSS)
- `SameSite=Lax`: Il cookie non viene inviato in richieste cross-site (protezione CSRF)
- `Strict Mode`: Rifiuta ID di sessione non generati dal server

### 10.9 .htaccess

Il file `.htaccess` configura Apache per proteggere file sensibili:

```apache
# Niente directory listing
Options -Indexes

# Blocca accesso a file sensibili
<FilesMatch "\.(env|sql|log|md)$">
    Deny from all
</FilesMatch>
```

---

## 11. Flussi end-to-end

Questa sezione racconta cosa succede "dietro le quinte" per le operazioni più importanti.

### 11.1 Login utente

```
Utente → compila form login (email + password)
         clicca "Accedi" → POST index.php?action=login

index.php
  └── switch 'login' → require AuthController.php → handleAuth() → loginAction()

loginAction():
  1. if (!verifyCsrf()) → redirect con errore "Richiesta non valida"
  2. validate email + password → se non validi → redirect con errore
  3. sanitize: email = strtolower(trim(email))
  4. table(utenti)->where(email)->first() → cerca nel DB
     → se non trovato → log + redirect "Email o password non corretti"
  5. password_verify(passwordForm, hashDB)
     → se false → log + redirect "Email o password non corretti"
  6. session_regenerate_id(true)
  7. $_SESSION['user_id'] = $utente['id']
     $_SESSION['user_nome'] = ...
     $_SESSION['user_ruolo'] = ...
  8. Merge carrello localStorage → DB (se c'erano biglietti)
  9. redirect('index.php', 'Benvenuto, Mario!')

index.php (nuova richiesta)
  └── $msg = $_SESSION['msg'] → "Benvenuto, Mario!"
  └── switch null → default → setPage('home')
  └── require main.php → mostra homepage con messaggio di benvenuto
```

### 11.2 Acquisto biglietto

```
Utente → va su un evento → clicca "Aggiungi al carrello"
         (richiesta AJAX) POST index.php?action=cart_add

CartController → addToCartApi():
  1. Verifica login e CSRF
  2. Valida idEvento, quantità
  3. Verifica che il tipo biglietto esista
  4. Verifica che l'evento esista
  5. checkDisponibilitaBiglietti() → conta posti liberi
  6. $pdo->beginTransaction()
  7. Per ogni biglietto: addBigliettoToCart() → INSERT biglietti (stato='carrello')
  8. $pdo->commit()
  9. Risponde JSON: { success: true, cartCount: N, cart: [...] }

JavaScript (script.js):
  └── Aggiorna il badge del carrello
  └── Mostra toast "Biglietto aggiunto"

Utente → va al checkout → index.php?action=checkout
  └── if (!isLoggedIn()) → redirect login
  └── setPage('checkout')
  └── main.php → views/checkout.php

Utente → compila dati intestatari → clicca "Acquista"
         POST index.php?action=acquista

BigliettoController → acquistaBiglietto():
  1. requireAuth()
  2. Blocca admin/mod/promoter
  3. verifyCsrf()
  4. Valida metodo pagamento e dati carrello
  5. acquistaFromServerCart():
     a. Verifica che ogni biglietto abbia nome e cognome
     b. $pdo->beginTransaction()
     c. Per ogni biglietto:
        - Verifica che sia nel carrello dell'utente
        - UPDATE nome/cognome/sesso
     d. confirmPurchase() → UPDATE stato='acquistato' + genera QRCode
     e. assegnaPostiAutomatici() → INSERT settore_biglietti (fila, posto)
     f. createOrdine() → INSERT ordini
     g. associaOrdineBiglietto() → INSERT ordine_biglietti
     h. $pdo->commit()
  6. redirect('view_ordine&id=X', 'Biglietti acquistati!')
```

### 11.3 Creazione evento (admin)

```
Admin → va alla dashboard → clicca "Crea evento"
        index.php?action=admin_create_event

AdminController → adminCreateEvent():
  └── requireAdmin()
  └── Carica form con locations e manifestazioni disponibili
  └── setPage('admin_create_event')

Admin → compila il form → clicca "Salva"
        POST index.php?action=create_evento

EventoController → handleEvento():
  └── case 'create_evento' → requireAdmin() → createEventoAction()

createEventoAction():
  1. verifyCsrf()
  2. Raccoglie e sanitizza: nome, prezzo, data, orari, programma
  3. createEvento($pdo, $data) → INSERT eventi → ritorna $id
  4. redirect('view_evento&id=' + $id, 'Evento creato con successo')
```

---

## 12. Ruoli e permessi

### La gerarchia

```
ADMIN (4)
  └── può tutto
  └── gestisce utenti: assegna ruoli, elimina account
  └── gestisce tutti gli eventi (CRUD)
  └── valida biglietti all'ingresso
  └── vede le statistiche nella dashboard

MOD (3)
  └── può tutto tranne gestire utenti admin
  └── gestisce contenuti: moderazione recensioni
  └── dashboard con overview eventi e location

PROMOTER (2)
  └── crea e gestisce i propri eventi
  └── può invitare collaboratori ai propri eventi
  └── crea e gestisce location proprie
  └── crea manifestazioni

USER (1) ← ruolo default alla registrazione
  └── sfoglia eventi
  └── acquista biglietti
  └── lascia recensioni sugli eventi a cui ha partecipato
  └── gestisce il proprio profilo
```

### Implementazione in codice

I permessi vengono verificati a livello di controller prima di eseguire qualsiasi operazione:

```php
// Middleware diretto (interrompe se non autorizzato)
requireAdmin();   // Solo admin
requireRole(RUOLO_PROMOTER); // Promoter o superiore

// Verifica condizionale (per mostrare/nascondere elementi)
if (isAdmin()) {
    // Mostra opzioni admin
}

if (isPromoter()) {
    // Mostra link dashboard promoter
}
```

La gerarchia è implementata nella funzione `hasRole()`:

```php
// La gerarchia
$hierarchy = ['user'=>1, 'promoter'=>2, 'mod'=>3, 'admin'=>4];

// hasRole(RUOLO_MOD) è true se il ruolo dell'utente è mod O admin
// perché 3 >= 3 (mod) e 4 >= 3 (admin)
return $hierarchy[$ruoloUtente] >= $hierarchy[$ruoloRichiesto];
```

### Cosa non possono fare i ruoli superiori

Per evitare abusi, admin, mod e promoter **non possono acquistare biglietti**:

```php
if (in_array($ruolo, [RUOLO_ADMIN, RUOLO_MOD, RUOLO_PROMOTER])) {
    setErrorMessage(ERR_ORGANIZERS_CANNOT_PURCHASE);
    redirect('index.php?action=checkout');
}
```

---

## 13. Glossario

**Array associativo** — In PHP, un array dove ogni elemento ha una "chiave" testuale invece di un indice numerico. Es: `['nome' => 'Mario', 'email' => 'mario@test.it']`. I risultati delle query SQL vengono restituiti così.

**bcrypt** — Algoritmo di hashing pensato per le password. È "lento" di proposito: ci vuole molto tempo per calcolare un hash, rendendo gli attacchi a forza bruta impraticabili.

**Cache-busting** — Tecnica per forzare il browser a scaricare la versione aggiornata di un file CSS o JS aggiungendo all'URL un parametro che cambia quando il file viene modificato (es. `?v=1703534400`).

**CSRF (Cross-Site Request Forgery)** — Tipo di attacco in cui un sito malevolo induce il browser dell'utente a fare richieste non volute a un altro sito dove l'utente è autenticato.

**DELETE CASCADE** — Opzione del database che elimina automaticamente i record correlati quando viene eliminato un record padre. Es: eliminare un evento elimina automaticamente tutti i suoi biglietti.

**Foreign Key** — Colonna di una tabella che fa riferimento alla chiave primaria di un'altra tabella, garantendo l'integrità dei dati.

**Flash message** — Messaggio che appare una sola volta dopo un'azione (es. dopo il login) e sparisce al prossimo aggiornamento della pagina. Viene salvato e poi cancellato dalla sessione.

**Front Controller** — Pattern architetturale in cui un unico file (`index.php`) gestisce tutte le richieste HTTP e le smista alle parti appropriate.

**Hash** — Trasformazione a senso unico di un dato (es. una password) in una stringa di lunghezza fissa. Non si può risalire al dato originale dall'hash.

**Header HTTP** — Metadati inviati insieme alla risposta HTTP dal server al browser. Possono specificare il tipo di contenuto, le istruzioni di cache, le politiche di sicurezza, ecc.

**IDOR (Insecure Direct Object Reference)** — Vulnerabilità in cui un utente può accedere a dati altrui semplicemente modificando un ID nell'URL.

**JSON (JavaScript Object Notation)** — Formato di testo per scambiare dati, usato nelle comunicazioni tra frontend JS e backend PHP (le API del carrello).

**Middleware** — Codice che viene eseguito prima della logica principale per verificare condizioni (autenticazione, permessi). Le funzioni `requireAuth()`, `requireAdmin()`, `requireRole()` sono middleware.

**MVC (Model-View-Controller)** — Pattern architetturale che separa i dati (Model), la presentazione (View) e la logica (Controller).

**PDO (PHP Data Objects)** — Interfaccia PHP per accedere ai database in modo sicuro e uniforme, con supporto per prepared statements.

**Prepared Statement** — Query SQL precompilata dove i valori vengono passati separatamente, impedendo SQL injection.

**QR Code** — Codice a barre bidimensionale. In EventsMaster viene generato per ogni biglietto e contiene un codice univoco per la validazione all'ingresso.

**Redirect** — Risposta HTTP che dice al browser di navigare a un altro URL.

**Salt** — Stringa casuale aggiunta alla password prima dell'hashing per rendere unico ogni hash, impedendo attacchi con tabelle precalcolate (rainbow tables).

**Sanitizzare** — Rimuovere o neutralizzare caratteri pericolosi dall'input utente.

**Sessione** — Meccanismo PHP per mantenere dati tra richieste HTTP successive (es. i dati dell'utente loggato).

**SQL Injection** — Attacco in cui codice SQL viene inserito nei dati utente per manipolare le query al database.

**Timing Attack** — Attacco che misura il tempo di esecuzione di operazioni crittografiche per dedurre informazioni segrete. La funzione `hash_equals()` previene questo tipo di attacco.

**Token** — Stringa casuale e univoca usata per operazioni sensibili come la verifica email o il reset password.

**Transazione DB** — Gruppo di operazioni SQL che vengono eseguite tutte insieme: o vanno tutte a buon fine (`COMMIT`) o nessuna (`ROLLBACK`). Garantisce la consistenza dei dati.

**Validazione** — Verifica che i dati in input rispettino le regole attese (formato email, lunghezza minima, ecc.).

**XSS (Cross-Site Scripting)** — Attacco in cui codice JavaScript malevolo viene iniettato in una pagina e eseguito nel browser di altri utenti.
