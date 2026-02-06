# EventsMaster - Mappa della Web App

## Architettura

**Entry point:** `index.php` - Router principale. Riceve il parametro `action` (POST o GET) e smista le richieste ai controller tramite uno switch. Al termine, renderizza `views/layouts/main.php` che carica dinamicamente la view corrente da `$_SESSION['page']`.

**Layout master:** `views/layouts/main.php` - Template condiviso da tutte le pagine. Contiene header con navigazione, barra di ricerca, dropdown utente, carrello sidebar, footer e newsletter.

---

## Pagine pubbliche (senza autenticazione)

### Home

- **View:** `views/home.php`
- **Route:** `action=home` (default)
- Layout a caroselli stile Netflix. Billboard con il prossimo evento in programma, scorciatoie per categoria (Concerti, Teatro, Sport, Comedy, Cinema, Famiglia), caroselli per eventi in evidenza, prossimi eventi e per ogni manifestazione. Griglia "Ti potrebbe interessare". Se l'utente loggato e admin/mod/promoter, redirect alla dashboard corrispondente.

### Login

- **View:** `views/login.php`
- **Route:** `action=show_login`
- **Controller:** `AuthController.php::handleAuth('login')`
- Form email/password con protezione CSRF. Link a registrazione e recupero password. Supporta redirect post-login.

### Registrazione

- **View:** `views/register.php`
- **Route:** `action=show_register`
- **Controller:** `AuthController.php::handleAuth('register')`
- Creazione account con nome, cognome, email, password (min 6 caratteri) e conferma password. Verifica email post-registrazione.

### Recupera password

- **View:** `views/recupera_password.php`
- **Route:** `action=recupera_password`
- **Controller:** `UserController.php::sendResetEmail()`
- Inserimento email per ricevere il link di reset. Token a scadenza (1 ora). Nessuna enumerazione utenti.

### Reset password

- **View:** `views/reset_password.php`
- **Route:** `action=reset_password` (+ parametro token)
- **Controller:** `UserController.php::doResetPassword()`
- Form nuova password accessibile dal link email. Validazione token e scadenza.

---

## Pagine utente autenticato

### Profilo

- **View:** `views/profilo.php`
- **Route:** `action=profilo`
- **Controller:** `UserController.php::showProfilo()`
- Visualizza e modifica informazioni personali (nome, cognome, email). Avatar con iniziali. Link a cambio password e eliminazione account.

### Cambio password

- **View:** `views/cambia_password.php`
- **Route:** `action=cambia_password`
- **Controller:** `UserController.php::updatePassword()`
- Verifica password corrente, inserimento nuova password con conferma (min 6 caratteri).

### Eliminazione account

- **View:** `views/elimina_account.php`
- **Route:** `action=elimina_account`
- **Controller:** `UserController.php::deleteAccount()`
- Eliminazione irreversibile. Richiede conferma password e checkbox esplicito. Spiega le conseguenze: cancellazione account, biglietti e anonimizzazione recensioni.

### I miei biglietti

- **View:** `views/miei_biglietti.php`
- **Route:** `action=miei_biglietti`
- Elenco biglietti futuri e passati. Ogni biglietto mostra nome evento, intestatario, data/ora, tipologia e QR code. Modal per dettagli biglietto.

### I miei ordini

- **View:** `views/miei_ordini.php`
- **Route:** `action=miei_ordini`
- **Controller:** `OrdineController.php::showOrdiniUtente()`
- Lista ordini con ID, metodo di pagamento, numero biglietti. Link al dettaglio ordine.

### Dettaglio ordine

- **View:** `views/ordine_dettaglio.php`
- **Route:** `action=view_ordine`
- **Controller:** `OrdineController.php::viewOrdine()`
- Riepilogo ordine: metodo di pagamento, numero biglietti, totale. Lista biglietti con stato di validazione (validato/da usare). Controllo accesso: solo l'utente proprietario puo visualizzare.

---

## Navigazione eventi

### Lista eventi

- **View:** `views/eventi_lista.php`
- **Route:** `action=list_eventi`
- **Controller:** `EventoController.php::listEventi()`
- Griglia di tutti gli eventi. Card con immagine, nome, data, prezzo, luogo. Bottone aggiungi al carrello e wishlist. Click sulla card per i dettagli.

### Eventi per categoria

- **View:** `views/eventi_lista.php` (stessa view della lista)
- **Route:** `action=category&cat={categoria}`
- **Controller:** `EventoController.php::listByCategory()`
- Categorie disponibili: concerti, teatro, sport, comedy, cinema, famiglia. Stessa griglia della lista eventi ma filtrata per categoria.

### Ricerca eventi

- **View:** `views/eventi_ricerca.php`
- **Route:** `action=search_eventi`
- **Controller:** `EventoController.php::handleEvento('search_eventi')`
- Ricerca per nome evento, nome artista o nome manifestazione. Risultati in griglia. Messaggio se nessun risultato.

### Dettaglio evento

- **View:** `views/evento_dettaglio.php`
- **Route:** `action=view_evento&id={evento_id}`
- **Controller:** `EventoController.php::handleEvento('view_evento')`
- Sezione hero con immagine, titolo, badge manifestazione, data, ora, luogo, media voti. Card info: prezzo base, location, data, ora. Programma/descrizione. Lista intrattenitori con ruolo e orario esibizione. Sezione recensioni con form per aggiungere recensione. Sidebar selettore biglietti: scelta settore, tipologia biglietto, quantita, calcolo prezzo dinamico (base + modificatore tipo + moltiplicatore settore), aggiungi al carrello. Eventi correlati della stessa manifestazione.

---

## Checkout e acquisto

### Carrello (sidebar)

- **Gestito in:** `views/layouts/main.php` (sidebar scorrevole)
- **API:** `CartController.php` (azioni: `cart_add`, `cart_get`, `cart_update`, `cart_remove`, `cart_clear`, `cart_count`, `check_availability`, `get_settori`, `cart_update_settore`)
- Sidebar laterale con lista biglietti da localStorage. Rimozione singola o per evento. Calcolo totale. Bottone checkout (richiede login). Merge carrello su login.

### Checkout

- **View:** `views/checkout.php`
- **Route:** `action=checkout`
- **Richiede:** Autenticazione
- Lista biglietti con campi intestatario (il primo auto-compilato con dati utente). Rimozione e modifica tipologia biglietto. Selezione metodo di pagamento: Carta, PayPal, Bonifico. Campo email conferma. Riepilogo ordine con conteggio e totale.

### Acquisto (elaborazione)

- **Route:** `action=acquista` (POST)
- **Controller:** `BigliettoController.php::handleBiglietto('acquista')`
- Crea record ordine, genera biglietti con QR code unico, salva intestatari. Redirect al dettaglio ordine.

---

## Pannelli di gestione

### Dashboard Admin

- **View:** `views/admin/dashboard.php`
- **Route:** `action=admin_dashboard`
- **Controller:** `AdminController.php::showAdminDashboard()`
- **Richiede:** Ruolo admin
- Statistiche: utenti totali, eventi totali, prossimi eventi, ordini totali. Conteggio utenti per ruolo. Azioni rapide: gestione utenti, gestione eventi, crea evento, gestione location, gestione manifestazioni.

### Dashboard Moderatore

- **View:** `views/admin/mod_dashboard.php`
- **Route:** `action=mod_dashboard`
- **Controller:** `AdminController.php::showModDashboard()`
- **Richiede:** Ruolo mod
- Statistiche: eventi totali, recensioni totali. Azioni rapide: gestione eventi, crea evento, gestione location, gestione manifestazioni. Info permessi: puo gestire tutti gli eventi, creare eventi, cancellare eventi inappropriati, ma non gestire utenti.

### Dashboard Promoter

- **View:** `views/admin/promoter_dashboard.php`
- **Route:** `action=promoter_dashboard`
- **Controller:** `AdminController.php::showPromoterDashboard()`
- **Richiede:** Ruolo promoter
- Azioni rapide: crea evento, gestione location, gestione manifestazioni. Griglia "I miei eventi" con data, stato (passato/prossimo), nome, luogo, orario, prezzo. Messaggio vuoto con invito a creare il primo evento.

### Gestione utenti

- **View:** `views/admin/utenti.php`
- **Route:** `action=admin_users`
- **Controller:** `AdminController.php::adminManageUsers()`
- **Richiede:** Ruolo admin
- Tabella utenti: ID, nome, email, ruolo, stato verifica, azioni. Filtri: tutti, admin, moderatori, promoter, utenti. Cambio ruolo tramite dropdown (auto-submit). Eliminazione utente con conferma. L'admin non puo modificare/eliminare se stesso.

### Gestione eventi (admin)

- **View:** `views/admin/eventi.php`
- **Route:** `action=admin_events`
- **Controller:** `AdminController.php::adminManageEvents()`
- **Richiede:** Ruolo admin
- Tabella eventi: ID, nome, data, location, prezzo base, azioni. Bottoni visualizza, modifica, elimina. Eventi passati con stile diverso.

### Form evento (crea/modifica)

- **View:** `views/admin/evento_form.php`
- **Route:** `action=admin_create_event`
- **Controller:** `AdminController.php::adminCreateEvent()`
- **Richiede:** Ruolo admin/mod/promoter
- Sezioni: info base (nome, data, ora inizio/fine, programma), location e prezzi (location, prezzo base, manifestazione opzionale), immagine, settori (multi-select checkbox). Modalita crea o modifica in base alla presenza di evento_id.

### Lista location

- **View:** `views/admin/locations_list.php`
- **Route:** `action=list_locations`
- **Controller:** `LocationController.php::listLocations()`
- **Richiede:** Ruolo promoter/mod/admin
- Tabella: nome, citta, regione, capienza, indirizzo, azioni. Bottone crea nuova. Modifica per tutti, eliminazione solo admin/mod.

### Form location (crea/modifica)

- **View:** `views/admin/location_form.php`
- **Route:** `action=create_location`, `action=edit_location`
- **Controller:** `LocationController.php::showCreateLocation()`, `showEditLocation()`, `saveLocation()`
- **Richiede:** Ruolo promoter/mod/admin
- Campi: nome, citta, regione, CAP, indirizzo, capienza. Modalita crea o modifica.

### Lista manifestazioni

- **View:** `views/admin/manifestazioni_list.php`
- **Route:** `action=list_manifestazioni`
- **Controller:** `ManifestazioneController.php::listManifestazioni()`
- **Richiede:** Ruolo promoter/mod/admin
- Tabella: nome, descrizione (troncata), data inizio, data fine, stato, azioni. Stato: "In programma", "In corso", "Conclusa" (con colori diversi). Modifica per tutti, eliminazione solo admin/mod.

### Form manifestazione (crea/modifica)

- **View:** `views/admin/manifestazione_form.php`
- **Route:** `action=create_manifestazione`, `action=edit_manifestazione`
- **Controller:** `ManifestazioneController.php::showCreateManifestazione()`, `showEditManifestazione()`, `saveManifestazione()`
- **Richiede:** Ruolo promoter/mod/admin
- Campi: nome, descrizione, data inizio, data fine. Modalita crea o modifica.

---

## API (senza rendering layout)

| Action | Controller | Descrizione |
|---|---|---|
| `cart_add`, `cart_get`, `cart_update`, `cart_remove`, `cart_clear`, `cart_count`, `check_availability`, `get_settori`, `cart_update_settore` | `CartController.php` | Operazioni carrello (localStorage + server) |
| `delete_biglietti_evento` | `AdminController.php` | Elimina biglietti di un evento |
| `delete_location` | `AdminController.php` | Elimina location (API JSON) |
| `delete_manifestazione` | `AdminController.php` | Elimina manifestazione (API JSON) |
| `delete_recensione` | `AdminController.php` | Elimina recensione (API JSON) |
| `verify_account` | `AdminController.php` | Verifica manuale account (API JSON) |
| `get_unverified_accounts` | `AdminController.php` | Lista account non verificati (API JSON) |
| `invite_collaborator`, `accept_collaboration`, `decline_collaboration`, `get_collaborators` | `CollaborazioneController.php` | Gestione collaboratori evento |
| `upload_avatar`, `get_avatar`, `delete_avatar` | `AvatarController.php` | Gestione avatar utente |
| `verify_email`, `resend_verification` | `UserController.php` | Verifica email |
| `add_recensione`, `update_recensione`, `delete_recensione` | `RecensioneController.php` | CRUD recensioni |

---

## Controller

| File | Responsabilita |
|---|---|
| `controllers/AuthController.php` | Login, registrazione, logout |
| `controllers/UserController.php` | Profilo, password, eliminazione account, verifica email |
| `controllers/EventoController.php` | Navigazione eventi, dettaglio, ricerca, categorie |
| `controllers/CartController.php` | API carrello |
| `controllers/BigliettoController.php` | Acquisto biglietti, validazione |
| `controllers/AdminController.php` | Dashboard admin/mod/promoter, gestione utenti, gestione eventi, API admin |
| `controllers/LocationController.php` | CRUD location |
| `controllers/ManifestazioneController.php` | CRUD manifestazioni |
| `controllers/OrdineController.php` | Storico ordini, dettaglio ordine |
| `controllers/RecensioneController.php` | CRUD recensioni |
| `controllers/AvatarController.php` | Upload/get/delete avatar |
| `controllers/CollaborazioneController.php` | Inviti e gestione collaboratori evento |
| `controllers/PageController.php` | Helper per impostare la pagina corrente in sessione |

---

## Model

| File | Entita |
|---|---|
| `models/Utente.php` | Utenti |
| `models/Evento.php` | Eventi |
| `models/Location.php` | Location/Luoghi |
| `models/Manifestazione.php` | Manifestazioni (festival, tour) |
| `models/Ordine.php` | Ordini |
| `models/Biglietto.php` | Biglietti |
| `models/Recensione.php` | Recensioni |
| `models/Settore.php` | Settori (aree della venue) |
| `models/EventoSettori.php` | Associazione evento-settori |
| `models/Intrattenitore.php` | Intrattenitori/Artisti |
| `models/Permessi.php` | Permessi e ruoli |

---

## Flusso di navigazione

```
HOME (default)
|
|-- [Non autenticato]
|   |-- Login --> Redirect per ruolo
|   |-- Registrazione --> Verifica email
|   |-- Recupera password --> Reset password
|   |-- Naviga eventi
|   |   |-- Lista tutti gli eventi
|   |   |-- Filtro per categoria
|   |   |-- Ricerca
|   |   |-- Dettaglio evento --> Aggiungi al carrello
|   |       |-- Carrello --> Checkout (richiede login)
|
|-- [Utente autenticato]
|   |-- Profilo
|   |   |-- Modifica info
|   |   |-- Cambio password
|   |   |-- Elimina account
|   |-- I miei biglietti (futuri e passati)
|   |-- I miei ordini --> Dettaglio ordine
|   |-- Naviga eventi (come sopra)
|   |-- Checkout --> Acquisto --> Dettaglio ordine
|   |-- Recensioni (su eventi passati)
|
|-- [Admin]
|   |-- Dashboard Admin
|   |   |-- Gestione utenti (ruoli, eliminazione)
|   |   |-- Gestione eventi (CRUD)
|   |   |-- Gestione location (CRUD)
|   |   |-- Gestione manifestazioni (CRUD)
|
|-- [Moderatore]
|   |-- Dashboard Moderatore
|   |   |-- Gestione eventi (CRUD)
|   |   |-- Gestione location (CRUD)
|   |   |-- Gestione manifestazioni (CRUD)
|   |   |-- (NO gestione utenti)
|
|-- [Promoter]
|   |-- Dashboard Promoter
|   |   |-- I miei eventi
|   |   |-- Crea evento
|   |   |-- Gestione location (CRUD)
|   |   |-- Gestione manifestazioni (CRUD)
```
