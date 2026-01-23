# Istruzioni Implementazione - EventsMaster

## File Creati

Questi file sono stati aggiunti al progetto e contengono le nuove funzionalità:

### Database & Models
- `db/migrations/001_add_collaboration_system.sql` - Migration per nuove tabelle
- `models/Permessi.php` - Gestione permessi e collaboratori
- `models/EventoSettori.php` - Gestione settori per evento

### Controllers
- `controllers/CollaborazioneController.php` - API inviti e collaborazioni
- `controllers/AvatarController.php` - Upload e gestione avatar utente
- `controllers/AdminController.php` - **ESTESO** con nuove funzionalità admin/mod

### Services
- `lib/EmailService.php` - Servizio notifiche email

### Cron Jobs
- `cron/auto_delete_old_events.php` - Eliminazione automatica eventi

## Modifiche ai File Esistenti

### models/Recensione.php
Aggiunta la limitazione temporale alle recensioni (2 settimane post evento):
- Nuova funzione `isEventoRecensibile()`
- Nuova funzione `getRecensioniVisibili()`
- Modificata `canRecensire()` per includere controllo temporale

### views/checkout.php
Già modificato in precedenza per:
- Gestione settori con prezzo moltiplicatore
- Bug fix prezzi nel carrello

## Step 1: Eseguire la Migration Database

Apri phpMyAdmin e esegui il file SQL:
```
db/migrations/001_add_collaboration_system.sql
```

Questo creerà le seguenti tabelle:
- `CreatoriEventi` - Traccia chi ha creato gli eventi
- `CreatoriLocations` - Traccia chi ha creato le location
- `CreatoriManifestazioni` - Traccia chi ha creato le manifestazioni
- `CollaboratoriEventi` - Gestione inviti e collaboratori
- `Notifiche` - Sistema notifiche email
- `EventiSettori` - Settori disponibili per evento

E aggiungerà questi campi:
- `Utenti.Avatar` - MEDIUMBLOB per avatar utente
- `Recensioni.created_at` - Timestamp creazione recensione
- `Biglietti.Stato` - Enum('carrello', 'acquistato')
- `Biglietti.idUtente` - FK per carrello persistente
- `Biglietti.DataCarrello` - Timestamp aggiunta al carrello

## Step 2: Aggiornare index.php

Aggiungi queste route al file `index.php`:

```php
// DOPO la sezione CARRELLO (API), AGGIUNGI:

    // ==========================================
    // COLLABORAZIONE (API)
    // ==========================================
    case 'invite_collaborator':
        require_once 'controllers/CollaborazioneController.php';
        inviteCollaboratorApi($pdo);
        break;

    case 'accept_collaboration':
        require_once 'controllers/CollaborazioneController.php';
        acceptCollaborationApi($pdo);
        break;

    case 'decline_collaboration':
        require_once 'controllers/CollaborazioneController.php';
        declineCollaborationApi($pdo);
        break;

    case 'get_collaborators':
        require_once 'controllers/CollaborazioneController.php';
        getCollaboratorsApi($pdo);
        break;

    // ==========================================
    // ADMIN API ESTESE
    // ==========================================
    case 'delete_biglietti_evento':
        require_once 'controllers/AdminController.php';
        deleteBigliettiEventoApi($pdo);
        break;

    case 'delete_location':
        require_once 'controllers/AdminController.php';
        deleteLocationApi($pdo);
        break;

    case 'delete_manifestazione':
        require_once 'controllers/AdminController.php';
        deleteManifestazioneApi($pdo);
        break;

    case 'delete_recensione':
        require_once 'controllers/AdminController.php';
        deleteRecensioneApi($pdo);
        break;

    case 'verify_account':
        require_once 'controllers/AdminController.php';
        verifyAccountApi($pdo);
        break;

    case 'get_unverified_accounts':
        require_once 'controllers/AdminController.php';
        getUnverifiedAccountsApi($pdo);
        break;

    // ==========================================
    // AVATAR (API)
    // ==========================================
    case 'upload_avatar':
        require_once 'controllers/AvatarController.php';
        uploadAvatarApi($pdo);
        break;

    case 'get_avatar':
        require_once 'controllers/AvatarController.php';
        getAvatarApi($pdo);
        break;

    case 'delete_avatar':
        require_once 'controllers/AvatarController.php';
        deleteAvatarApi($pdo);
        break;
```

## Step 3: Configurare il Cron Job

### Windows (Task Scheduler):
1. Apri Task Scheduler
2. Crea nuova attività programmata
3. Trigger: Giornalmente alle 03:00
4. Azione: Avvia programma
   - Programma: `C:\xampp\php\php.exe`
   - Argomenti: `C:\xampp\htdocs\eventsMaster\cron\auto_delete_old_events.php`

### Linux (Crontab):
```bash
crontab -e
# Aggiungi:
0 3 * * * /usr/bin/php /path/to/eventsMaster/cron/auto_delete_old_events.php
```

## Step 4: Creare Avatar Predefinito

Crea il file `public/img/default-avatar.png` con un'immagine avatar predefinita (150x150px).

## Step 5: Modificare la Home per Ruoli Diversi

Nel file `views/home.php` (o equivalente), aggiungi la logica per reindirizzare:

```php
<?php
if (isLoggedIn()) {
    $ruolo = $_SESSION['ruolo'] ?? 'user';

    if ($ruolo === 'admin') {
        header('Location: index.php?action=admin_dashboard');
        exit;
    } elseif ($ruolo === 'mod') {
        header('Location: index.php?action=mod_dashboard');
        exit;
    } elseif ($ruolo === 'promoter') {
        header('Location: index.php?action=promoter_dashboard');
        exit;
    }
}
// Altrimenti mostra la home normale
?>
```

## Step 6: Disabilitare Acquisto Biglietti per Promoter/Admin/Mod

In `controllers/BigliettoController.php` (o dove gestisci l'aggiunta al carrello):

```php
function addBigliettoToCart(...) {
    if (isLoggedIn()) {
        $ruolo = $_SESSION['ruolo'] ?? 'user';
        if (in_array($ruolo, ['promoter', 'mod', 'admin'])) {
            jsonResponse(['error' => 'Gli organizzatori non possono acquistare biglietti'], 403);
            return;
        }
    }
    // ... resto del codice
}
```

E nascondi i pulsanti "Acquista" nell'interfaccia per questi ruoli.

## Step 7: Pop-up Conferma Modifiche Checkout

Nel file `views/checkout.php`, modifica la funzione `confirmEdit` per aggiungere conferma:

```javascript
window.confirmEdit = async function() {
    // AGGIUNGI ALL'INIZIO:
    if (!confirm('Confermi le modifiche ai biglietti selezionati?')) {
        return;
    }

    // ... resto del codice esistente
}
```

E modifica `deleteSingleTicket` e `deleteGroupTickets`:

```javascript
window.deleteSingleTicket = async function(idx) {
    const ticket = tickets[idx];
    if (!confirm(`Confermi l'eliminazione del biglietto per "${ticket.eventName}"?`)) {
        return;
    }
    // ... resto del codice
};
```

## Step 8: Modificare Creazione Eventi

In `controllers/EventoController.php` (o dove gestisci la creazione eventi):

```php
function createEvento(...) {
    // Dopo aver creato l'evento
    $eventoId = ...; // ID evento appena creato
    $userId = $_SESSION['user_id'];

    // Registra il creatore
    require_once 'models/Permessi.php';
    registerEventoCreator($pdo, $eventoId, $userId);

    // Se ci sono settori selezionati, associali
    if (!empty($_POST['settori'])) {
        require_once 'models/EventoSettori.php';
        $settoriIds = array_map('intval', $_POST['settori']);
        setEventoSettori($pdo, $eventoId, $settoriIds);
    }
}
```

E modifica il form di creazione evento per includere selezione settori:

```html
<label>Settori Disponibili:</label>
<div id="settori-list">
    <!-- Popolato dinamicamente in base alla location selezionata -->
</div>
```

## Step 9: Ottimizzazione Mobile

Aggiungi al file `public/css/main.css` (o crea `mobile.css`):

```css
/* Mobile Optimization */
@media (max-width: 768px) {
    /* Riduci dimensioni font */
    body {
        font-size: 14px;
    }

    h1 { font-size: 24px; }
    h2 { font-size: 20px; }
    h3 { font-size: 18px; }

    /* Card eventi più compatte */
    .evento-card {
        padding: 12px;
        margin: 8px 0;
    }

    .evento-card img {
        max-height: 150px;
    }

    /* Pulsanti più accessibili */
    .btn {
        min-height: 44px;
        font-size: 16px;
    }

    /* Checkout cards */
    .checkout-card {
        padding: 10px;
        font-size: 13px;
    }

    /* Form inputs */
    input, select, textarea {
        font-size: 16px; /* Previene zoom automatico su iOS */
    }
}

@media (max-width: 480px) {
    /* Ancor più piccolo per smartphone */
    .container {
        padding: 8px;
    }

    /* Stack elementi */
    .flex-row {
        flex-direction: column;
    }
}

/* Landscape phone */
@media (max-height: 500px) and (orientation: landscape) {
    /* Header più compatto */
    header {
        padding: 8px 0;
    }

    .nav-menu {
        font-size: 14px;
    }
}
```

## Funzionalità Implementate

### ✅ Completate
1. Sistema permessi promoter/admin/mod
2. Notifiche email per modifiche eventi
3. Inviti collaboratori con token email
4. Gestione locations e manifestazioni con permessi
5. Selezione settori per evento
6. Eliminazione per admin (biglietti, account, location, manifestazioni, eventi)
7. Eliminazione recensioni per mod
8. Auto-eliminazione eventi dopo 2 settimane (cron job)
9. Upload avatar utente con validazione
10. Verifica account da parte di admin/mod
11. Limitazione recensioni a 2 settimane post evento

### 📝 Da Implementare Manualmente
1. **Modificare home per ruoli** - Aggiungere redirect in base al ruolo
2. **Disabilitare acquisto per promoter/mod/admin** - Modificare BigliettoController
3. **Pop-up conferma modifiche** - Aggiungere `confirm()` in checkout.js
4. **Selezione settori in creazione evento** - Modificare form evento
5. **Ottimizzazione CSS mobile** - Aggiungere media queries
6. **Pannelli controllo dedicati** - Creare views per admin/mod/promoter dashboard
7. **Aggiornare routes** - Completare index.php con tutte le route

## Note Importanti

### Email
Il sistema usa `EmailService` che al momento **NON invia email reali**.
Le notifiche vengono salvate nella tabella `Notifiche`.

Per abilitare l'invio reale:
```php
// In qualsiasi controller che usa EmailService:
$emailService = new EmailService($pdo, true); // true = invio reale
```

E assicurati che il server abbia configurato sendmail o SMTP.

### Sicurezza
- Tutti i file sensibili hanno controlli CSRF
- Le API verificano autenticazione e ruolo
- Upload avatar validato per tipo e dimensione
- SQL prepared statements ovunque

### Performance
- Avatar cacheati 1 giorno lato browser
- Cron job esegue alle 3am per non impattare utenti
- Recensioni limitate temporalmente riducono query

## Prossimi Passi

1. Eseguire la migration SQL
2. Aggiornare index.php con le nuove route
3. Creare le views per i pannelli admin/mod/promoter
4. Implementare le modifiche manuali elencate sopra
5. Testare tutte le funzionalità
6. Configurare il cron job
7. Ottimizzare CSS per mobile

## Testing

Vedi il file `TESTING.md` per la checklist completa dei test.
