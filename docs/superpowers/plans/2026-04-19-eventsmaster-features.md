# EventsMaster Features Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement all features from newFeatures.txt in priority order, excluding F9 (app Blibber).

**Architecture:** PHP MVC with front controller (`index.php`), PDO+QueryBuilder for DB access, `$_SESSION['page']` for view routing. All cart/avatar endpoints return JSON. Flash messages via `$_SESSION['msg']`/`$_SESSION['error']`.

**Tech Stack:** PHP 8+, MySQL via PDO, XAMPP, GD (avatar resize), QRCodeJS (client), vanilla JS fetch API.

---

## Implementation Order
F6 → F7 → F2 → F3 → F14 → F13 → F10 → F5 → F4 → F8 → F15 → F11 → F12 → F1

---

## Task 1: F6 — Blocco acquisto eventi passati

**Files:**
- Modify: `controllers/CartController.php` (addToCartApi, mergeGuestCart if exists)
- Modify: `controllers/BigliettoController.php` (acquistaFromServerCart, acquistaFromLocalCart)
- Modify: `views/evento_dettaglio.php`

- [ ] **Step 1: Fix conflitto `jsonResponse()` duplicata**

Entrambi `CartController.php` e `AvatarController.php` definiscono `jsonResponse()`. Aggiungere guard in `AvatarController.php`:

In `controllers/AvatarController.php`, trovare la funzione `jsonResponse` (riga 210) e sostituirla con:
```php
if (!function_exists('jsonResponse')) {
    function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
```

- [ ] **Step 2: Blocco nel CartController — addToCartApi**

In `controllers/CartController.php`, dopo il blocco che verifica che l'evento esista (dopo riga ~126 `if (!$evento) { ... }`), aggiungere:

```php
    // Blocco acquisto eventi passati (F6)
    if ($evento[COL_EVENTI_DATA] < date('Y-m-d')) {
        jsonResponse(['error' => 'Evento già concluso, acquisto non disponibile'], 400);
        return;
    }
```

- [ ] **Step 3: Blocco in BigliettoController — acquistaFromServerCart**

In `controllers/BigliettoController.php`, dentro `acquistaFromServerCart`, dopo il blocco che verifica l'ownership del biglietto (dopo la `if (!$stmt->fetch()) throw new Exception...`), aggiungere questa verifica per ogni ticket dentro il loop `foreach ($tickets as $ticket)`:

```php
            // Verifica data evento (F6)
            $evStmt = $pdo->prepare("SELECT Data FROM " . TABLE_EVENTI . " WHERE " . COL_EVENTI_ID . " = (SELECT " . COL_BIGLIETTI_ID_EVENTO . " FROM " . TABLE_BIGLIETTI . " WHERE " . COL_BIGLIETTI_ID . " = ?)");
            $evStmt->execute([$idBiglietto]);
            $evRow = $evStmt->fetch(PDO::FETCH_ASSOC);
            if ($evRow && $evRow['Data'] < date('Y-m-d')) {
                throw new Exception('Evento già concluso per il biglietto ' . $idBiglietto);
            }
```

- [ ] **Step 4: Blocco in BigliettoController — acquistaFromLocalCart**

In `acquistaFromLocalCart`, dentro il `foreach ($cartData as $ticket)`, dopo `$idEvento = (int) ($ticket['eventoId'] ?? 0);` e il controllo `if ($idEvento <= 0) continue;`, aggiungere:

```php
            // Verifica data evento (F6)
            $evStmt = $pdo->prepare("SELECT Data FROM " . TABLE_EVENTI . " WHERE " . COL_EVENTI_ID . " = ?");
            $evStmt->execute([$idEvento]);
            $evRow = $evStmt->fetch(PDO::FETCH_ASSOC);
            if ($evRow && $evRow['Data'] < date('Y-m-d')) {
                throw new Exception('Evento già concluso, acquisto non disponibile');
            }
```

- [ ] **Step 5: Frontend — disabilitare bottone in evento_dettaglio.php**

In `views/evento_dettaglio.php`, trovare il div/section che contiene il form di aggiunta al carrello (cerca `addEventToCart` o `add-to-cart`). Aggiungere logica PHP per mostrare un messaggio "Evento concluso" se l'evento è passato.

Cerca la sezione con il bottone di acquisto. Prima del bottone, aggiungere:

```php
<?php $eventoPassato = ($evento['Data'] < date('Y-m-d')); ?>
```

E poi sostituire il bottone "Aggiungi al carrello" con:

```php
<?php if ($eventoPassato): ?>
    <button class="btn btn-disabled" disabled>
        <i class="fas fa-times-circle"></i> Evento concluso
    </button>
<?php else: ?>
    <!-- bottone originale -->
    <button class="btn btn-primary add-to-cart-btn" id="addToCartBtn" ...>
        <i class="fas fa-shopping-cart"></i> Aggiungi al carrello
    </button>
<?php endif; ?>
```

- [ ] **Step 6: Commit**

```bash
git add controllers/CartController.php controllers/BigliettoController.php controllers/AvatarController.php views/evento_dettaglio.php
git commit -m "feat(F6): blocco acquisto eventi passati + fix jsonResponse duplicata"
```

---

## Task 2: F7 — Archivio eventi passati nelle dashboard

**Files:**
- Modify: `models/Evento.php`
- Modify: `views/admin/promoter_dashboard.php` (o file dashboard promoter)
- Modify: `views/admin/mod_dashboard.php`
- Modify: `views/admin/dashboard.php`

- [ ] **Step 1: Trovare i file dashboard esistenti**

```bash
ls controllers/
ls views/admin/
```

- [ ] **Step 2: Aggiungere funzione getEventiPassati in Evento.php**

In `models/Evento.php`, aggiungere dopo le funzioni esistenti:

```php
function getEventiPassati(PDO $pdo, ?int $idCreatore = null): array
{
    $sql = "SELECT e.*, m.Nome AS ManifestazioneName, l.Nome AS LocationName
            FROM " . TABLE_EVENTI . " e
            LEFT JOIN " . TABLE_MANIFESTAZIONI . " m ON e.idManifestazione = m.id
            LEFT JOIN " . TABLE_LOCATIONS . " l ON e.idLocation = l.id
            WHERE e.Data < CURDATE()";
    $params = [];
    if ($idCreatore !== null) {
        $sql .= " AND e.idCreatore = ?";
        $params[] = $idCreatore;
    }
    $sql .= " ORDER BY e.Data DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

- [ ] **Step 3: Aggiungere filtro "solo futuri" alla funzione principale**

In `models/Evento.php`, trovare `getAllEventi` e aggiungere parametro opzionale:

```php
function getAllEventi(PDO $pdo, bool $soloFuturi = false): array
{
    $sql = "SELECT e.*, m.Nome AS ManifestazioneName, l.Nome AS LocationName
            FROM " . TABLE_EVENTI . " e
            LEFT JOIN " . TABLE_MANIFESTAZIONI . " m ON e.idManifestazione = m.id
            LEFT JOIN " . TABLE_LOCATIONS . " l ON e.idLocation = l.id";
    if ($soloFuturi) {
        $sql .= " WHERE e.Data >= CURDATE()";
    }
    $sql .= " ORDER BY e.Data ASC, e.OraI ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

- [ ] **Step 4: Aggiornare le dashboard per separare eventi futuri/passati**

Nelle dashboard (promoter_dashboard.php, mod_dashboard.php, admin/dashboard.php), dove vengono mostrati gli eventi, aggiungere una sezione "Archivio" separata:

```php
<!-- Sezione eventi futuri già esistente -->
<section class="dashboard-section">
    <h2>Eventi attivi</h2>
    <!-- tabella eventi futuri -->
</section>

<!-- Nuova sezione archivio -->
<section class="dashboard-section archived-section">
    <h2><i class="fas fa-archive"></i> Archivio eventi passati
        <button class="btn btn-sm btn-secondary" onclick="toggleArchive()">Mostra/Nascondi</button>
    </h2>
    <div id="archivioEventi" style="display:none;">
        <?php $eventiPassati = getEventiPassati($pdo, $idCreatoreFilter); ?>
        <?php if (empty($eventiPassati)): ?>
            <p class="no-data">Nessun evento passato.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>Nome</th><th>Data</th><th>Location</th><th>Stato</th></tr></thead>
                <tbody>
                <?php foreach ($eventiPassati as $ev): ?>
                    <tr>
                        <td><?= e($ev['Nome']) ?></td>
                        <td><?= e($ev['Data']) ?></td>
                        <td><?= e($ev['LocationName']) ?></td>
                        <td><span class="badge badge-gray">Concluso</span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>

<script>
function toggleArchive() {
    const el = document.getElementById('archivioEventi');
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
```

- [ ] **Step 5: Commit**

```bash
git add models/Evento.php views/admin/
git commit -m "feat(F7): archivio eventi passati nelle dashboard"
```

---

## Task 3: F2 — Admin/mod assegna promoter quando crea evento

**Files:**
- Modify: `controllers/EventoController.php`
- Modify: `controllers/AdminController.php` (se gestisce creazione evento)
- Modify: `views/admin/evento_form.php` (form creazione/modifica evento)
- Modify: `models/Utente.php`

- [ ] **Step 1: Verificare dove avviene la creazione evento per admin/mod**

```bash
grep -r "create_evento\|createEvento\|idCreatore" controllers/ --include="*.php" -l
grep -r "create_evento" index.php
```

- [ ] **Step 2: Aggiungere funzione getPromoters in Utente.php**

In `models/Utente.php`, aggiungere:

```php
function getPromoters(PDO $pdo): array
{
    return table($pdo, TABLE_UTENTI)
        ->select([COL_UTENTI_ID, COL_UTENTI_NOME, COL_UTENTI_COGNOME, COL_UTENTI_EMAIL])
        ->where(COL_UTENTI_RUOLO, RUOLO_PROMOTER)
        ->orderBy(COL_UTENTI_COGNOME)
        ->get();
}
```

- [ ] **Step 3: Aggiornare il controller creazione evento**

In `EventoController.php` (o `AdminController.php`), nel metodo/funzione che gestisce `create_evento` (POST), aggiungere logica:

```php
// Se l'utente è admin o mod, usa il promoter scelto dal form
if (in_array($_SESSION['user_ruolo'], [RUOLO_ADMIN, RUOLO_MOD])) {
    $idCreatore = (int) ($_POST['idCreatore'] ?? 0);
    if ($idCreatore <= 0) {
        setErrorMessage('Devi assegnare un promoter all\'evento');
        redirect('index.php?action=create_evento');
    }
    // Verifica che l'utente scelto sia promoter
    $promoter = table($pdo, TABLE_UTENTI)
        ->where(COL_UTENTI_ID, $idCreatore)
        ->where(COL_UTENTI_RUOLO, RUOLO_PROMOTER)
        ->first();
    if (!$promoter) {
        setErrorMessage('Utente selezionato non è un promoter');
        redirect('index.php?action=create_evento');
    }
} else {
    // Promoter: assegna se stesso
    $idCreatore = $_SESSION['user_id'];
}
```

- [ ] **Step 4: Aggiornare il form evento_form.php**

In `views/admin/evento_form.php`, aggiungere il campo select promoter visibile solo ad admin/mod:

```php
<?php
require_once __DIR__ . '/../../models/Utente.php';
$promoters = getPromoters($pdo);
?>

<?php if (isAdmin() || isMod()): ?>
<div class="form-group">
    <label for="idCreatore">Assegna a Promoter <span class="required">*</span></label>
    <select name="idCreatore" id="idCreatore" required>
        <option value="">— Seleziona promoter —</option>
        <?php foreach ($promoters as $p): ?>
            <option value="<?= (int)$p['id'] ?>"
                <?= isset($evento) && (int)$evento['idCreatore'] === (int)$p['id'] ? 'selected' : '' ?>>
                <?= e($p['Cognome'] . ' ' . $p['Nome']) ?> (<?= e($p['Email']) ?>)
            </option>
        <?php endforeach; ?>
    </select>
</div>
<?php else: ?>
    <input type="hidden" name="idCreatore" value="<?= (int)$_SESSION['user_id'] ?>">
<?php endif; ?>
```

- [ ] **Step 5: Commit**

```bash
git add controllers/ views/admin/evento_form.php models/Utente.php
git commit -m "feat(F2): admin/mod assegna promoter alla creazione evento"
```

---

## Task 4: F3 — Colonna is_owner in collaboratorieventi

**Files:**
- Modify: `db/ddl_migliorato.sql`
- Modify: `controllers/CollaborazioneController.php`
- Modify: `models/Evento.php` (createEvento)
- Modify: `config/database_schema.php`

- [ ] **Step 1: Aggiungere costante in database_schema.php**

In `config/database_schema.php`, trovare le costanti per `collaboratorieventi` e aggiungere:

```php
define('COL_COLLABORATORI_IS_OWNER', 'is_owner');
```

- [ ] **Step 2: ALTER TABLE nel database**

Eseguire in phpMyAdmin o MySQL CLI:

```sql
ALTER TABLE collaboratorieventi 
ADD COLUMN is_owner TINYINT(1) NOT NULL DEFAULT 0 AFTER status;
```

Aggiornare anche `db/ddl_migliorato.sql` aggiungendo la colonna nella definizione della tabella `collaboratorieventi`.

- [ ] **Step 3: Impostare is_owner=1 alla creazione evento**

In `models/Evento.php`, nella funzione `createEvento` (o dove avviene l'insert iniziale in `collaboratorieventi`), assicurarsi che il record del promoter creatore abbia `is_owner = 1`:

```php
// Dopo createEvento(), aggiungere il promoter come collaboratore proprietario
function addProprietarioEvento(PDO $pdo, int $idEvento, int $idUtente): void
{
    // Rimuovi eventuale record esistente per questo utente/evento
    table($pdo, TABLE_COLLABORATORI_EVENTI)
        ->where('idEvento', $idEvento)
        ->where('idUtente', $idUtente)
        ->delete();

    table($pdo, TABLE_COLLABORATORI_EVENTI)->insert([
        'idEvento'  => $idEvento,
        'idUtente'  => $idUtente,
        'status'    => 'accepted',
        COL_COLLABORATORI_IS_OWNER => 1
    ]);
}
```

Chiamare `addProprietarioEvento($pdo, $idEvento, $idCreatore)` subito dopo `createEvento()` nei controller.

- [ ] **Step 4: Blocco rimozione proprietario in CollaborazioneController**

In `controllers/CollaborazioneController.php`, trovare la funzione che gestisce `remove_collaborator` e aggiungere:

```php
// Verifica che il collaboratore da rimuovere non sia il proprietario
$collab = table($pdo, TABLE_COLLABORATORI_EVENTI)
    ->where('idEvento', $idEvento)
    ->where('idUtente', $idCollaboratore)
    ->first();

if ($collab && (int)$collab[COL_COLLABORATORI_IS_OWNER] === 1) {
    jsonResponse(['error' => 'Il proprietario dell\'evento non può essere rimosso dai collaboratori'], 403);
    return;
}
```

- [ ] **Step 5: Esporre is_owner nell'API get_collaborators**

In `CollaborazioneController.php`, nella risposta di `get_collaborators`, aggiungere il campo `is_owner` nel map:

```php
'is_owner' => (bool)($collab[COL_COLLABORATORI_IS_OWNER] ?? false)
```

- [ ] **Step 6: Badge "Proprietario" nella view collaboratori**

Nella view dove si mostrano i collaboratori (cerca in `views/admin/` o `views/evento_form.php`), aggiungere badge condizionale:

```php
<?php if ($collab['is_owner']): ?>
    <span class="badge badge-primary">Proprietario</span>
<?php endif; ?>
```

- [ ] **Step 7: Commit**

```bash
git add config/database_schema.php controllers/CollaborazioneController.php models/Evento.php db/ddl_migliorato.sql
git commit -m "feat(F3): colonna is_owner in collaboratorieventi, protezione proprietario"
```

---

## Task 5: F14 — Avatar utente funzionante

**Files:**
- Modify: `views/profilo.php`
- Modify: `views/layouts/main.php`
- Modify: `controllers/AvatarController.php` (solo guard jsonResponse — già fatto nel Task 1)

- [ ] **Step 1: Aggiungere sezione avatar in profilo.php**

In `views/profilo.php`, sostituire il blocco `<div class="profile-avatar-large">` con:

```php
<div class="profile-avatar-container">
    <?php
    $hasAvatar = !empty($user['Avatar']);
    $userId = (int)($_SESSION['user_id'] ?? 0);
    ?>

    <?php if ($hasAvatar): ?>
        <img src="index.php?action=get_avatar&id=<?= $userId ?>&t=<?= time() ?>"
             alt="Avatar"
             class="profile-avatar-large profile-avatar-img"
             id="profileAvatarImg">
    <?php else: ?>
        <div class="profile-avatar-large profile-avatar-initials" id="profileAvatarInitials">
            <?= strtoupper(substr($user['Nome'] ?? 'U', 0, 1) . substr($user['Cognome'] ?? '', 0, 1)) ?>
        </div>
    <?php endif; ?>

    <div class="avatar-actions">
        <label for="avatarInput" class="btn btn-secondary btn-sm" style="cursor:pointer;">
            <i class="fas fa-camera"></i> Cambia foto
        </label>
        <input type="file" id="avatarInput" accept="image/jpeg,image/png,image/gif" style="display:none;">
        <?php if ($hasAvatar): ?>
        <button type="button" class="btn btn-danger btn-sm" id="deleteAvatarBtn">
            <i class="fas fa-trash"></i> Rimuovi
        </button>
        <?php endif; ?>
    </div>
    <div id="avatarFeedback" style="margin-top:0.5rem;"></div>
</div>
```

- [ ] **Step 2: Aggiungere script JS per upload avatar in profilo.php**

In fondo a `views/profilo.php`, aggiungere:

```php
<script>
(function() {
    const input = document.getElementById('avatarInput');
    const feedback = document.getElementById('avatarFeedback');
    const csrfToken = window.EventsMaster?.csrfToken || '';

    if (input) {
        input.addEventListener('change', async function() {
            const file = this.files[0];
            if (!file) return;

            // Anteprima immediata
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.getElementById('profileAvatarImg');
                const initials = document.getElementById('profileAvatarInitials');
                if (img) {
                    img.src = e.target.result;
                } else {
                    // Crea img al posto delle iniziali
                    const newImg = document.createElement('img');
                    newImg.src = e.target.result;
                    newImg.className = 'profile-avatar-large profile-avatar-img';
                    newImg.id = 'profileAvatarImg';
                    if (initials) initials.replaceWith(newImg);
                }
            };
            reader.readAsDataURL(file);

            // Upload via AJAX
            feedback.innerHTML = '<span style="color:#6b7280"><i class="fas fa-spinner fa-spin"></i> Caricamento...</span>';
            const formData = new FormData();
            formData.append('avatar', file);
            formData.append('csrf_token', csrfToken);
            formData.append('action', 'upload_avatar');

            try {
                const res = await fetch('index.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    feedback.innerHTML = '<span style="color:green"><i class="fas fa-check"></i> Foto aggiornata!</span>';
                    // Aggiorna header avatar
                    const headerAvatars = document.querySelectorAll('.user-avatar, .user-avatar-lg');
                    headerAvatars.forEach(el => {
                        if (el.tagName !== 'IMG') {
                            const img = document.createElement('img');
                            img.src = data.avatarUrl + '&t=' + Date.now();
                            img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:50%;';
                            el.innerHTML = '';
                            el.appendChild(img);
                        }
                    });
                    setTimeout(() => { feedback.innerHTML = ''; }, 3000);
                } else {
                    feedback.innerHTML = '<span style="color:red"><i class="fas fa-times"></i> ' + (data.error || 'Errore') + '</span>';
                }
            } catch (e) {
                feedback.innerHTML = '<span style="color:red">Errore di connessione</span>';
            }
        });
    }

    const deleteBtn = document.getElementById('deleteAvatarBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', async function() {
            if (!confirm('Rimuovere la foto profilo?')) return;
            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('action', 'delete_avatar');
            try {
                const res = await fetch('index.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    location.reload();
                }
            } catch (e) {}
        });
    }
})();
</script>
```

- [ ] **Step 3: Aggiornare main.php — header avatar**

In `views/layouts/main.php`, trovare i due blocchi con le iniziali utente (`.user-avatar` e `.user-avatar-lg`) e aggiungere la foto se presente.

Sostituire riga ~98:
```php
<div class="user-avatar">
    <?= strtoupper(substr($_SESSION['user_nome'] ?? 'U', 0, 1)) ?>
</div>
```

Con:
```php
<div class="user-avatar" id="headerAvatar">
    <?php if (!empty($_SESSION['user_has_avatar'])): ?>
        <img src="index.php?action=get_avatar&id=<?= (int)$_SESSION['user_id'] ?>"
             alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
    <?php else: ?>
        <?= strtoupper(substr($_SESSION['user_nome'] ?? 'U', 0, 1)) ?>
    <?php endif; ?>
</div>
```

E riga ~107 (dropdown header):
```php
<div class="user-avatar-lg">
    <?= strtoupper(substr($_SESSION['user_nome'] ?? 'U', 0, 1) . substr($_SESSION['user_cognome'] ?? '', 0, 1)) ?>
</div>
```

Con:
```php
<div class="user-avatar-lg">
    <?php if (!empty($_SESSION['user_has_avatar'])): ?>
        <img src="index.php?action=get_avatar&id=<?= (int)$_SESSION['user_id'] ?>"
             alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
    <?php else: ?>
        <?= strtoupper(substr($_SESSION['user_nome'] ?? 'U', 0, 1) . substr($_SESSION['user_cognome'] ?? '', 0, 1)) ?>
    <?php endif; ?>
</div>
```

- [ ] **Step 4: Impostare `$_SESSION['user_has_avatar']` al login**

In `controllers/AuthController.php`, nella funzione `loginAction`, dopo il login avvenuto con successo (dove si imposta `$_SESSION['user_nome']`, ecc.), aggiungere:

```php
$_SESSION['user_has_avatar'] = !empty($user[COL_UTENTI_AVATAR]);
```

Fare lo stesso nella funzione `profiloAction` (o dove si carica il profilo), in modo che dopo un upload la sessione venga aggiornata.

- [ ] **Step 5: Aggiornare user_has_avatar dopo upload**

In `controllers/AvatarController.php`, nella funzione `uploadAvatarApi`, dopo il successo dell'aggiornamento DB, aggiungere prima di `jsonResponse(apiSuccess(...))`:

```php
$_SESSION['user_has_avatar'] = true;
```

E in `deleteAvatarApi`, dopo il successo:

```php
$_SESSION['user_has_avatar'] = false;
```

- [ ] **Step 6: Assicurarsi che la tabella utenti includa Avatar nel SELECT del profilo**

In `controllers/AuthController.php` o nel controller che gestisce `profilo`, verificare che la query che carica i dati utente includa il campo `Avatar`. Cercare dove viene impostato `$_SESSION['user_data']` e assicurarsi che la query non escluda `Avatar`.

Se usa QueryBuilder con `select([...])`, aggiungere `COL_UTENTI_AVATAR`. Se usa `select(['*'])`, è già incluso.

- [ ] **Step 7: Commit**

```bash
git add views/profilo.php views/layouts/main.php controllers/AvatarController.php controllers/AuthController.php
git commit -m "feat(F14): avatar upload funzionante in profilo + header"
```

---

## Task 6: F13 — Dump database con immagini

**Files:**
- Create: `db/import_images.php`
- Create: `db/assets/` (cartella immagini, file non in git)
- Modify: `db/ddl_migliorato.sql` (o separare in schema.sql + seed_data.sql)

> **Nota**: Questo task richiede immagini reali. Usa immagini royalty-free da unsplash.com o placeholder da picsum.photos.

- [ ] **Step 1: Creare la cartella assets**

```bash
mkdir -p db/assets/eventi db/assets/locations db/assets/manifestazioni db/assets/utenti
```

- [ ] **Step 2: Scaricare immagini di test**

Scaricare almeno un'immagine per categoria (concerti, teatro, sport, comedy, cinema, famiglia) e salvarle in `db/assets/eventi/`. Formati accettati: jpg o png.

Esempio con curl:
```bash
curl -L "https://picsum.photos/800/450" -o db/assets/eventi/concerti.jpg
curl -L "https://picsum.photos/800/450" -o db/assets/eventi/teatro.jpg
curl -L "https://picsum.photos/800/450" -o db/assets/eventi/sport.jpg
curl -L "https://picsum.photos/400/400" -o db/assets/locations/location1.jpg
curl -L "https://picsum.photos/400/400" -o db/assets/manifestazioni/manifest1.jpg
curl -L "https://picsum.photos/400/400" -o db/assets/utenti/admin.jpg
```

- [ ] **Step 3: Creare script import_images.php**

Creare `db/import_images.php`:

```php
<?php
/**
 * Script di import immagini nel database
 * Uso: php db/import_images.php
 */
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/database_schema.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function loadImageBlob(string $path): ?string {
    if (!file_exists($path)) {
        echo "WARN: file non trovato: $path\n";
        return null;
    }
    return file_get_contents($path);
}

function resizeToBlob(string $path, int $maxW = 800, int $maxH = 450): ?string {
    $info = getimagesize($path);
    if (!$info) return null;
    [$w, $h, $type] = [$info[0], $info[1], $info[2]];

    $src = match($type) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($path),
        IMAGETYPE_PNG  => imagecreatefrompng($path),
        IMAGETYPE_GIF  => imagecreatefromgif($path),
        default        => null
    };
    if (!$src) return null;

    $ratio = min($maxW / $w, $maxH / $h, 1.0);
    $nw = (int)($w * $ratio);
    $nh = (int)($h * $ratio);
    $dst = imagecreatetruecolor($nw, $nh);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    ob_start();
    imagejpeg($dst, null, 85);
    $blob = ob_get_clean();
    imagedestroy($src);
    imagedestroy($dst);
    return $blob;
}

$assetsDir = __DIR__ . '/assets';
echo "=== Import immagini EventsMaster ===\n";

// Immagini eventi per categoria
$categorieEventi = [
    'concerti'   => 'eventi/concerti.jpg',
    'teatro'     => 'eventi/teatro.jpg',
    'sport'      => 'eventi/sport.jpg',
    'comedy'     => 'eventi/comedy.jpg',
    'cinema'     => 'eventi/cinema.jpg',
    'famiglia'   => 'eventi/famiglia.jpg',
];

foreach ($categorieEventi as $cat => $imgPath) {
    $fullPath = "$assetsDir/$imgPath";
    $blob = resizeToBlob($fullPath);
    if ($blob) {
        $stmt = $pdo->prepare("UPDATE " . TABLE_EVENTI . " SET Immagine = ? WHERE Categoria = ? AND Immagine IS NULL");
        $stmt->execute([$blob, $cat]);
        echo "Aggiornati " . $stmt->rowCount() . " eventi ($cat)\n";
    }
}

// Immagine location generica
$locBlob = resizeToBlob("$assetsDir/locations/location1.jpg", 400, 400);
if ($locBlob) {
    $stmt = $pdo->prepare("UPDATE " . TABLE_LOCATIONS . " SET Immagine = ? WHERE Immagine IS NULL");
    $stmt->execute([$locBlob]);
    echo "Aggiornate " . $stmt->rowCount() . " locations\n";
}

// Immagine manifestazioni
$manifBlob = resizeToBlob("$assetsDir/manifestazioni/manifest1.jpg", 400, 400);
if ($manifBlob) {
    $stmt = $pdo->prepare("UPDATE " . TABLE_MANIFESTAZIONI . " SET Immagine = ? WHERE Immagine IS NULL");
    $stmt->execute([$manifBlob]);
    echo "Aggiornate " . $stmt->rowCount() . " manifestazioni\n";
}

// Avatar utenti di test
$userAvatars = [
    1 => 'utenti/admin.jpg',
    2 => 'utenti/mod.jpg',
    3 => 'utenti/promoter.jpg',
    4 => 'utenti/user.jpg',
];
foreach ($userAvatars as $id => $path) {
    $blob = resizeToBlob("$assetsDir/$path", 300, 300);
    if ($blob) {
        $stmt = $pdo->prepare("UPDATE " . TABLE_UTENTI . " SET Avatar = ? WHERE id = ?");
        $stmt->execute([$blob, $id]);
        echo "Avatar aggiornato per utente $id\n";
    }
}

echo "=== Import completato ===\n";
```

- [ ] **Step 4: Eseguire lo script**

```bash
php db/import_images.php
```

- [ ] **Step 5: Esportare dump con immagini**

```bash
mysqldump --hex-blob --complete-insert --default-character-set=utf8mb4 -u root -p1234 5cit_eventsmaster > db/dump_con_immagini.sql
```

- [ ] **Step 6: Separare schema e seed**

Creare `db/schema.sql` con solo le `CREATE TABLE` (copiare da `ddl_migliorato.sql` togliendo le INSERT). Creare `db/seed_data.sql` con solo le INSERT senza BLOB (dati testuali). Il file `dump_con_immagini.sql` contiene tutto inclusi i BLOB.

- [ ] **Step 7: Aggiungere .gitignore per assets e dump pesanti**

In `.gitignore` del progetto (o creare se non esiste):
```
db/assets/
db/dump_con_immagini.sql
db/seed_images.sql
```

- [ ] **Step 8: Commit**

```bash
git add db/import_images.php db/schema.sql db/seed_data.sql .gitignore
git commit -m "feat(F13): script import immagini + separazione schema/seed"
```

---

## Task 7: F10 — Stato "Non usufruito" biglietti eventi passati

**Files:**
- Modify: `views/miei_biglietti.php`

- [ ] **Step 1: Trovare dove viene mostrato lo stato biglietto nel modal**

Leggere `views/miei_biglietti.php` e cercare la sezione JavaScript che mostra `ticket.Stato === 'validato'`.

- [ ] **Step 2: Aggiungere CSS per badge "non usufruito"**

In `views/miei_biglietti.php`, nel blocco `<style>`, aggiungere dopo `.ticket-status.used { ... }`:

```css
.ticket-status.expired {
    background: #f3f4f6;
    color: #6b7280;
}
```

- [ ] **Step 3: Aggiornare la logica JS per lo stato**

Nel JavaScript che popola il modal del biglietto (cerca la funzione che imposta il contenuto), trovare la riga che controlla `ticket.Stato` e sostituire:

```javascript
// Prima (solo due stati):
const statusHtml = ticket.Stato === 'validato'
    ? '<span class="ticket-status used"><i class="fas fa-times-circle"></i> Utilizzato</span>'
    : '<span class="ticket-status valid"><i class="fas fa-check-circle"></i> Valido</span>';
```

Con:
```javascript
// Dopo (tre stati):
function getTicketStatusHtml(ticket) {
    const today = new Date().toISOString().split('T')[0];
    if (ticket.Stato === 'validato') {
        return '<span class="ticket-status used"><i class="fas fa-times-circle"></i> Utilizzato</span>';
    } else if (ticket.eventoData && ticket.eventoData < today) {
        return '<span class="ticket-status expired"><i class="fas fa-clock"></i> Non usufruito</span>';
    } else {
        return '<span class="ticket-status valid"><i class="fas fa-check-circle"></i> Da utilizzare</span>';
    }
}
const statusHtml = getTicketStatusHtml(ticket);
```

- [ ] **Step 4: Verificare che il campo `eventoData` sia nel payload PHP**

Nel PHP di `miei_biglietti.php`, verificare che i dati passati alle card/modal includano la data dell'evento. Se le card sono generate con PHP e i biglietti hanno `Data` dal JOIN, aggiungere `data-event-date="<?= e($b['Data']) ?>"` agli elementi card.

- [ ] **Step 5: Aggiungere logica PHP per la lista**

Nella lista biglietti passati (sezione "Biglietti passati"), aggiungere una colonna o badge che distingue "Utilizzato" da "Non usufruito":

```php
<?php
$statoEffettivo = $b['Stato'] === 'validato' ? 'Utilizzato' : 'Non usufruito';
$statoClass = $b['Stato'] === 'validato' ? 'badge-green' : 'badge-gray';
?>
<span class="badge <?= $statoClass ?>"><?= $statoEffettivo ?></span>
```

- [ ] **Step 6: Commit**

```bash
git add views/miei_biglietti.php
git commit -m "feat(F10): stato 'non usufruito' per biglietti eventi passati"
```

---

## Task 8: F5 — Nascondere moltiplicatore, mostrare prezzo finale settore

**Files:**
- Modify: `controllers/CartController.php` (getSettoriApi)
- Modify: `views/evento_dettaglio.php` (JS updatePrice e select settori)

- [ ] **Step 1: Aggiornare getSettoriApi in CartController.php**

In `controllers/CartController.php`, trovare `getSettoriApi`. L'evento è già caricato. Aggiungere recupero del prezzo base dell'evento e calcolo del prezzo finale per settore.

Sostituire la risposta `array_map` (riga ~415):

```php
    $prezzoBase = (float)($evento[COL_EVENTI_PREZZO] ?? $evento['Prezzo'] ?? 0);

    jsonResponse([
        'settori' => array_map(function($s) use ($prezzoBase) {
            $mult = (float) $s['MoltiplicatorePrezzo'];
            // prezzoFinale = prezzoBase * moltiplicatore (senza modificatore tipo qui)
            $prezzoFinale = round($prezzoBase * $mult, 2);
            $surplus = round($prezzoFinale - $prezzoBase, 2);
            return [
                'id'           => $s['id'],
                'nome'         => $s['Nome'],
                'posti'        => $s['PostiTotali'],
                'prezzoFinale' => $prezzoFinale,
                'surplus'      => $surplus,
            ];
        }, $settori)
    ]);
```

> **Nota**: verificare il nome della colonna prezzo nell'evento — potrebbe essere `Prezzo` o `PrezzoBase`. Cercare con `grep -r "PrezzoBase\|Prezzo" models/Evento.php`.

- [ ] **Step 2: Aggiornare il selettore settori in evento_dettaglio.php**

In `views/evento_dettaglio.php`, trovare la funzione JS che popola il `<select>` dei settori dopo la chiamata AJAX a `get_settori`. Il codice probabilmente fa qualcosa come:

```javascript
// Prima
option.textContent = `${s.nome} - x${s.moltiplicatore}`;
option.dataset.mult = s.moltiplicatore;
```

Cambiare in:
```javascript
// Dopo
const surplusText = s.surplus > 0
    ? ` (+${s.surplus.toFixed(2).replace('.', ',')} €)`
    : s.surplus < 0
        ? ` (-${Math.abs(s.surplus).toFixed(2).replace('.', ',')} €)`
        : '';
option.textContent = `${s.nome} — ${s.prezzoFinale.toFixed(2).replace('.', ',')} €${surplusText}`;
option.dataset.prezzoFinale = s.prezzoFinale;
option.dataset.surplus = s.surplus;
// Rimuovere: option.dataset.mult = s.moltiplicatore;
```

- [ ] **Step 3: Aggiornare la funzione updatePrice in evento_dettaglio.php**

Trovare `updatePrice()` e verificare che usi `prezzoFinale` invece del moltiplicatore:

```javascript
function updatePrice() {
    const tipoSelect = document.getElementById('tipoSelect');
    const settoreSelect = document.getElementById('settoreSelect');
    const quantita = parseInt(document.getElementById('quantita')?.value || 1);

    const prezzoBase = parseFloat(document.getElementById('prezzoBase')?.dataset?.price || 0);
    const modPrezzo = tipoSelect
        ? parseFloat(tipoSelect.selectedOptions[0]?.dataset?.mod || 0)
        : 0;

    // Usa prezzoFinale dal settore invece del moltiplicatore grezzo
    const prezzoSettore = settoreSelect && settoreSelect.value
        ? parseFloat(settoreSelect.selectedOptions[0]?.dataset?.prezzoFinale || prezzoBase + modPrezzo)
        : prezzoBase + modPrezzo;

    const totale = prezzoSettore * quantita;
    const priceDisplay = document.getElementById('priceDisplay');
    if (priceDisplay) {
        priceDisplay.textContent = totale.toFixed(2).replace('.', ',') + ' €';
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add controllers/CartController.php views/evento_dettaglio.php
git commit -m "feat(F5): nasconde moltiplicatore, mostra prezzo finale settore"
```

---

## Task 9: F4 — Raggruppamento biglietti nel carrello per evento/tipo

**Files:**
- Modify: `public/script.js` (funzione di rendering carrello)
- Modify: `views/checkout.php` (struttura biglietti)

- [ ] **Step 1: Trovare la funzione di rendering carrello in script.js**

```bash
grep -n "renderCart\|cartItems\|eventoNome" public/script.js | head -30
```

- [ ] **Step 2: Riscrivere il rendering per raggruppare per evento**

In `public/script.js`, trovare la funzione che genera l'HTML per i biglietti del carrello (es. `renderCartItems` o `updateCartDisplay`) e sostituire con:

```javascript
function renderCartItems(items) {
    // Raggruppa per evento
    const byEvento = {};
    items.forEach(item => {
        const key = item.idEvento;
        if (!byEvento[key]) {
            byEvento[key] = {
                nome: item.eventoNome,
                data: item.eventoData,
                tickets: []
            };
        }
        byEvento[key].tickets.push(item);
    });

    let html = '';
    Object.values(byEvento).forEach(ev => {
        // Raggruppa per tipo dentro l'evento
        const byTipo = {};
        ev.tickets.forEach(t => {
            if (!byTipo[t.idClasse]) byTipo[t.idClasse] = [];
            byTipo[t.idClasse].push(t);
        });

        const tipiHtml = Object.entries(byTipo).map(([tipo, ts]) => {
            const subtotale = ts.reduce((s, t) => s + t.prezzo, 0);
            return `<div class="cart-ticket-tipo">
                <span>${ts.length}&times; ${tipo} — ${subtotale.toFixed(2).replace('.', ',')} €</span>
            </div>`;
        }).join('');

        const eventoTotale = ev.tickets.reduce((s, t) => s + t.prezzo, 0);

        html += `<div class="cart-evento-card">
            <div class="cart-evento-header">
                <strong>${ev.nome}</strong>
                <span class="cart-evento-data">${ev.data || ''}</span>
            </div>
            <div class="cart-ticket-list">${tipiHtml}</div>
            <div class="cart-evento-totale">Totale: ${eventoTotale.toFixed(2).replace('.', ',')} €</div>
        </div>`;
    });

    return html || '<p class="cart-empty">Il carrello è vuoto</p>';
}
```

- [ ] **Step 3: Aggiungere CSS per le card evento nel carrello**

In `public/css/main.css` (o inline in main.php), aggiungere:

```css
.cart-evento-card {
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 8px;
    padding: 0.75rem;
    margin-bottom: 0.75rem;
    background: var(--bg-card, #fff);
}
.cart-evento-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}
.cart-evento-data {
    font-size: 0.8rem;
    color: #6b7280;
}
.cart-ticket-tipo {
    font-size: 0.9rem;
    color: #374151;
    padding: 0.25rem 0;
}
.cart-evento-totale {
    font-weight: 600;
    font-size: 0.9rem;
    text-align: right;
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid var(--border, #e5e7eb);
}
```

- [ ] **Step 4: Commit**

```bash
git add public/script.js public/css/main.css views/checkout.php
git commit -m "feat(F4): biglietti raggruppati per evento/tipo nel carrello"
```

---

## Task 10: F8 — Moderazione recensioni

**Files:**
- Modify: `db/ddl_migliorato.sql`
- Modify: `config/database_schema.php`
- Modify: `controllers/RecensioneController.php`
- Modify: `views/admin/mod_dashboard.php`
- Modify: `views/evento_dettaglio.php`
- Modify: `models/Recensione.php` (se esiste)

- [ ] **Step 1: ALTER TABLE recensioni**

Eseguire in MySQL:

```sql
ALTER TABLE recensioni
ADD COLUMN stato ENUM('visibile','nascosta','segnalata') NOT NULL DEFAULT 'visibile' AFTER testo,
ADD COLUMN moderata_da INT NULL,
ADD COLUMN moderata_at DATETIME NULL,
ADD CONSTRAINT fk_recensioni_moderata_da FOREIGN KEY (moderata_da) REFERENCES utenti(id) ON DELETE SET NULL;
```

Aggiornare `db/ddl_migliorato.sql` con le stesse modifiche.

- [ ] **Step 2: Aggiungere costanti in database_schema.php**

```php
define('COL_RECENSIONI_STATO',        'stato');
define('COL_RECENSIONI_MODERATA_DA',  'moderata_da');
define('COL_RECENSIONI_MODERATA_AT',  'moderata_at');

define('STATO_RECENSIONE_VISIBILE',   'visibile');
define('STATO_RECENSIONE_NASCOSTA',   'nascosta');
define('STATO_RECENSIONE_SEGNALATA',  'segnalata');
```

- [ ] **Step 3: Aggiungere casi in index.php per le nuove API**

In `index.php`, nel switch delle azioni, aggiungere:

```php
case 'hide_recensione':
case 'restore_recensione':
case 'flag_recensione':
case 'get_recensioni_admin':
    require_once __DIR__ . '/controllers/RecensioneController.php';
    handleRecensione($pdo, $action);
    break;
```

- [ ] **Step 4: Aggiungere funzioni in RecensioneController.php**

In `controllers/RecensioneController.php` (o creare se non esiste), aggiungere:

```php
function hideRecensioneAction(PDO $pdo): void
{
    requireRole(RUOLO_MOD);
    if (!verifyCsrf()) { jsonResponse(['error' => ERR_INVALID_CSRF], 403); return; }

    $id = (int)($_POST['id'] ?? 0);
    table($pdo, TABLE_RECENSIONI)
        ->where(COL_RECENSIONI_ID, $id)
        ->update([
            COL_RECENSIONI_STATO       => STATO_RECENSIONE_NASCOSTA,
            COL_RECENSIONI_MODERATA_DA => $_SESSION['user_id'],
            COL_RECENSIONI_MODERATA_AT => date('Y-m-d H:i:s')
        ]);
    jsonResponse(['success' => true]);
}

function restoreRecensioneAction(PDO $pdo): void
{
    requireRole(RUOLO_MOD);
    if (!verifyCsrf()) { jsonResponse(['error' => ERR_INVALID_CSRF], 403); return; }

    $id = (int)($_POST['id'] ?? 0);
    table($pdo, TABLE_RECENSIONI)
        ->where(COL_RECENSIONI_ID, $id)
        ->update([
            COL_RECENSIONI_STATO       => STATO_RECENSIONE_VISIBILE,
            COL_RECENSIONI_MODERATA_DA => $_SESSION['user_id'],
            COL_RECENSIONI_MODERATA_AT => date('Y-m-d H:i:s')
        ]);
    jsonResponse(['success' => true]);
}

function flagRecensioneAction(PDO $pdo): void
{
    if (!isLoggedIn()) { jsonResponse(['error' => ERR_LOGIN_REQUIRED], 401); return; }
    if (!verifyCsrf()) { jsonResponse(['error' => ERR_INVALID_CSRF], 403); return; }

    $id = (int)($_POST['id'] ?? 0);
    // Segna solo se non già nascosta
    $rec = table($pdo, TABLE_RECENSIONI)->where(COL_RECENSIONI_ID, $id)->first();
    if ($rec && $rec[COL_RECENSIONI_STATO] === STATO_RECENSIONE_VISIBILE) {
        table($pdo, TABLE_RECENSIONI)
            ->where(COL_RECENSIONI_ID, $id)
            ->update([COL_RECENSIONI_STATO => STATO_RECENSIONE_SEGNALATA]);

        // Notifica mod/admin
        $mods = table($pdo, TABLE_UTENTI)
            ->select([COL_UTENTI_ID])
            ->whereIn(COL_UTENTI_RUOLO, [RUOLO_MOD, RUOLO_ADMIN])
            ->get();
        foreach ($mods as $mod) {
            table($pdo, TABLE_NOTIFICHE)->insert([
                'idUtente'  => $mod[COL_UTENTI_ID],
                'messaggio' => 'Recensione #' . $id . ' segnalata come inappropriata',
                'letto'     => 0,
                'created_at'=> date('Y-m-d H:i:s')
            ]);
        }
    }
    jsonResponse(['success' => true]);
}

function getRecensioniAdminAction(PDO $pdo): void
{
    requireRole(RUOLO_MOD);
    $stato = $_GET['stato'] ?? null;
    $q = table($pdo, TABLE_RECENSIONI)
        ->select(['recensioni.*', 'u.Nome', 'u.Cognome', 'e.Nome AS EventoNome'])
        ->join(TABLE_UTENTI . ' u', 'recensioni.idUtente', '=', 'u.id')
        ->join(TABLE_EVENTI . ' e', 'recensioni.idEvento', '=', 'e.id')
        ->orderBy(COL_RECENSIONI_STATO . " = 'segnalata'", 'DESC')
        ->orderBy('recensioni.DataRecensione', 'DESC');
    if ($stato) $q = $q->where(COL_RECENSIONI_STATO, $stato);
    jsonResponse(['recensioni' => $q->get()]);
}
```

Aggiungere routing in `handleRecensione()`:
```php
case 'hide_recensione':    hideRecensioneAction($pdo); break;
case 'restore_recensione': restoreRecensioneAction($pdo); break;
case 'flag_recensione':    flagRecensioneAction($pdo); break;
case 'get_recensioni_admin': getRecensioniAdminAction($pdo); break;
```

- [ ] **Step 5: Filtrare recensioni nascoste nelle query pubbliche**

In `models/Recensione.php` (o dove si recuperano le recensioni per evento), aggiungere `WHERE stato = 'visibile'` alle query pubbliche:

```php
// Nella funzione getRecensioniByEvento, aggiungere filtro:
->where(COL_RECENSIONI_STATO, STATO_RECENSIONE_VISIBILE)
```

- [ ] **Step 6: Aggiungere sezione moderazione in mod_dashboard.php**

In `views/admin/mod_dashboard.php`, aggiungere nuova sezione:

```php
<section class="dashboard-section">
    <h2><i class="fas fa-flag"></i> Moderazione Recensioni</h2>

    <div class="filter-tabs" style="margin-bottom:1rem;">
        <button class="btn btn-sm btn-secondary active" onclick="loadRecensioni('segnalata')">Segnalate</button>
        <button class="btn btn-sm btn-secondary" onclick="loadRecensioni('nascosta')">Nascoste</button>
        <button class="btn btn-sm btn-secondary" onclick="loadRecensioni('')">Tutte</button>
    </div>

    <div id="recensioniModPanel">
        <p class="loading">Caricamento...</p>
    </div>
</section>

<script>
async function loadRecensioni(stato) {
    const panel = document.getElementById('recensioniModPanel');
    panel.innerHTML = '<p class="loading">Caricamento...</p>';
    const res = await fetch('index.php?action=get_recensioni_admin' + (stato ? '&stato=' + stato : ''));
    const data = await res.json();
    if (!data.recensioni || data.recensioni.length === 0) {
        panel.innerHTML = '<p class="no-data">Nessuna recensione trovata.</p>';
        return;
    }
    let html = '<table class="admin-table"><thead><tr><th>Autore</th><th>Evento</th><th>Voto</th><th>Commento</th><th>Stato</th><th>Azioni</th></tr></thead><tbody>';
    data.recensioni.forEach(r => {
        const statoClass = r.stato === 'segnalata' ? 'badge-orange' : r.stato === 'nascosta' ? 'badge-red' : 'badge-green';
        const btnHide = r.stato !== 'nascosta'
            ? `<button class="btn btn-xs btn-danger" onclick="moderaRecensione(${r.id}, 'hide_recensione')">Nascondi</button>`
            : `<button class="btn btn-xs btn-success" onclick="moderaRecensione(${r.id}, 'restore_recensione')">Ripristina</button>`;
        html += `<tr>
            <td>${r.Nome} ${r.Cognome}</td>
            <td>${r.EventoNome}</td>
            <td>${r.Valutazione}/5</td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;">${r.Testo || ''}</td>
            <td><span class="badge ${statoClass}">${r.stato}</span></td>
            <td>${btnHide}</td>
        </tr>`;
    });
    html += '</tbody></table>';
    panel.innerHTML = html;
}

async function moderaRecensione(id, action) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('csrf_token', window.EventsMaster?.csrfToken || '');
    formData.append('action', action);
    await fetch('index.php', { method: 'POST', body: formData });
    loadRecensioni('');
}

loadRecensioni('segnalata');
</script>
```

- [ ] **Step 7: Bottone "Segnala" in evento_dettaglio.php**

In `views/evento_dettaglio.php`, trovare il loop che renderizza le recensioni. Aggiungere dopo ogni recensione (solo per utenti loggati):

```php
<?php if (isLoggedIn() && $_SESSION['user_id'] != $rec['idUtente']): ?>
<button class="btn btn-xs btn-secondary flag-rec-btn"
        data-id="<?= (int)$rec['id'] ?>">
    <i class="fas fa-flag"></i> Segnala
</button>
<?php endif; ?>
```

E JS per gestire il click:
```javascript
document.querySelectorAll('.flag-rec-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
        const id = this.dataset.id;
        const fd = new FormData();
        fd.append('id', id);
        fd.append('csrf_token', window.EventsMaster?.csrfToken || '');
        fd.append('action', 'flag_recensione');
        const res = await fetch('index.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            this.textContent = 'Segnalata';
            this.disabled = true;
        }
    });
});
```

- [ ] **Step 8: Commit**

```bash
git add config/database_schema.php controllers/RecensioneController.php views/admin/mod_dashboard.php views/evento_dettaglio.php db/ddl_migliorato.sql index.php
git commit -m "feat(F8): moderazione recensioni (nascondi/segnala/ripristina)"
```

---

## Task 11: F15 — Ruolo artista

**Files:**
- Modify: `config/database_schema.php`
- Modify: `models/Utente.php`
- Create: `controllers/ArtistaController.php`
- Create: `views/artista_profilo.php`
- Create: `views/artista_dashboard.php`
- Modify: `views/layouts/main.php`
- Modify: `views/evento_dettaglio.php`
- Modify: `index.php`
- Modify: `db/ddl_migliorato.sql`

- [ ] **Step 1: ALTER TABLE — aggiungere ruolo artista e colonne intrattenitori**

```sql
ALTER TABLE utenti 
MODIFY ruolo ENUM('admin','mod','promoter','artista','user') NOT NULL DEFAULT 'user';

ALTER TABLE intrattenitori
ADD COLUMN idUtente INT NULL AFTER id,
ADD COLUMN bio TEXT NULL,
ADD COLUMN foto MEDIUMBLOB NULL,
ADD COLUMN social_links JSON NULL,
ADD UNIQUE KEY uq_intrattenitori_utente (idUtente),
ADD CONSTRAINT fk_intrattenitori_utente FOREIGN KEY (idUtente) REFERENCES utenti(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS artista_claims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idUtente INT NOT NULL,
    idIntrattenitore INT NOT NULL,
    stato ENUM('pending','approvata','rifiutata') NOT NULL DEFAULT 'pending',
    messaggio TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    gestita_da INT NULL,
    gestita_at DATETIME NULL,
    CONSTRAINT fk_claims_utente FOREIGN KEY (idUtente) REFERENCES utenti(id) ON DELETE CASCADE,
    CONSTRAINT fk_claims_intrattenitore FOREIGN KEY (idIntrattenitore) REFERENCES intrattenitori(id) ON DELETE CASCADE,
    CONSTRAINT fk_claims_gestita_da FOREIGN KEY (gestita_da) REFERENCES utenti(id) ON DELETE SET NULL
);
```

- [ ] **Step 2: Aggiungere costanti in database_schema.php**

```php
define('RUOLO_ARTISTA',                  'artista');
define('TABLE_ARTISTA_CLAIMS',           'artista_claims');
define('COL_INTRATTENITORI_ID_UTENTE',   'idUtente');
define('COL_INTRATTENITORI_BIO',         'bio');
define('COL_INTRATTENITORI_FOTO',        'foto');
define('COL_INTRATTENITORI_SOCIAL',      'social_links');
define('COL_CLAIMS_ID',                  'id');
define('COL_CLAIMS_ID_UTENTE',           'idUtente');
define('COL_CLAIMS_ID_INTRATTENITORE',   'idIntrattenitore');
define('COL_CLAIMS_STATO',               'stato');
define('COL_CLAIMS_MESSAGGIO',           'messaggio');
```

- [ ] **Step 3: Aggiornare models/Utente.php**

Aggiungere/modificare in `models/Utente.php`:

```php
// Aggiornare la gerarchia in hasRole():
$hierarchy = [
    RUOLO_USER     => 1,
    RUOLO_ARTISTA  => 2,
    RUOLO_PROMOTER => 3,
    RUOLO_MOD      => 4,
    RUOLO_ADMIN    => 5,
];

// Aggiungere funzione isArtista:
function isArtista(?int $userId = null): bool
{
    global $pdo;
    $uid = $userId ?? ($_SESSION['user_id'] ?? null);
    if (!$uid) return false;
    return getUserRole($pdo, $uid) === RUOLO_ARTISTA;
}

// Aggiungere funzione getIntratteniitoreByUtente:
function getIntrattenitoreByUtente(PDO $pdo, int $idUtente): ?array
{
    return table($pdo, TABLE_INTRATTENITORI)
        ->where(COL_INTRATTENITORI_ID_UTENTE, $idUtente)
        ->first();
}
```

- [ ] **Step 4: Creare ArtistaController.php**

Creare `controllers/ArtistaController.php`:

```php
<?php
require_once __DIR__ . '/../config/database_schema.php';
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../lib/QueryBuilder.php';
require_once __DIR__ . '/../lib/Validator.php';
require_once __DIR__ . '/../models/Utente.php';
require_once __DIR__ . '/../controllers/PageController.php';

function showArtistaProfile(PDO $pdo): void
{
    $id = (int)($_GET['id'] ?? 0);
    $artista = table($pdo, TABLE_INTRATTENITORI)
        ->where(COL_INTRATTENITORI_ID, $id)
        ->first();

    if (!$artista) {
        redirect('index.php', null, 'Artista non trovato');
    }

    // Carica eventi passati e futuri a cui partecipa
    $stmt = $pdo->prepare(
        "SELECT e.*, l.Nome AS LocationName
         FROM " . TABLE_EVENTI . " e
         LEFT JOIN " . TABLE_LOCATIONS . " l ON e.idLocation = l.id
         JOIN " . TABLE_EVENTO_INTRATTENITORI . " ei ON ei.idEvento = e.id
         WHERE ei.idIntrattenitore = ?
         ORDER BY e.Data DESC"
    );
    $stmt->execute([$id]);
    $eventi = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $_SESSION['artista_corrente'] = $artista;
    $_SESSION['artista_eventi'] = $eventi;

    setSeoMeta(
        'Artista: ' . ($artista['Nome'] ?? ''),
        'Scopri gli eventi di ' . ($artista['Nome'] ?? '')
    );
    setPage('artista_profilo');
}

function updateArtistaProfile(PDO $pdo): void
{
    if (!isLoggedIn()) { redirect('index.php', null, ERR_LOGIN_REQUIRED); }
    if (!verifyCsrf()) { redirect('index.php', null, ERR_INVALID_CSRF); }

    $intrattenitore = getIntrattenitoreByUtente($pdo, $_SESSION['user_id']);
    if (!$intrattenitore) {
        redirect('index.php', null, 'Profilo artista non trovato');
    }

    $bio = sanitize($_POST['bio'] ?? '');
    $socialLinks = json_encode([
        'instagram' => sanitize($_POST['instagram'] ?? ''),
        'spotify'   => sanitize($_POST['spotify'] ?? ''),
        'youtube'   => sanitize($_POST['youtube'] ?? ''),
    ]);

    $updates = [
        COL_INTRATTENITORI_BIO    => $bio,
        COL_INTRATTENITORI_SOCIAL => $socialLinks,
    ];

    // Gestione foto
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto'];
        if ($file['size'] <= AVATAR_MAX_SIZE) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            if (in_array($mime, AVATAR_ALLOWED_TYPES)) {
                $updates[COL_INTRATTENITORI_FOTO] = file_get_contents($file['tmp_name']);
            }
        }
    }

    table($pdo, TABLE_INTRATTENITORI)
        ->where(COL_INTRATTENITORI_ID, $intrattenitore['id'])
        ->update($updates);

    redirect('index.php?action=artista_dashboard', 'Profilo aggiornato con successo');
}

function claimArtistaAction(PDO $pdo): void
{
    if (!isLoggedIn()) { redirect('index.php', null, ERR_LOGIN_REQUIRED); }
    if (!verifyCsrf()) { redirect('index.php', null, ERR_INVALID_CSRF); }

    $idIntrattenitore = (int)($_POST['idIntrattenitore'] ?? 0);
    $messaggio = sanitize($_POST['messaggio'] ?? '');

    if ($idIntrattenitore <= 0) {
        redirect('index.php', null, 'Seleziona un artista valido');
    }

    // Verifica che l'artista non sia già reclamato
    $artista = table($pdo, TABLE_INTRATTENITORI)
        ->where(COL_INTRATTENITORI_ID, $idIntrattenitore)
        ->first();
    if ($artista && !empty($artista[COL_INTRATTENITORI_ID_UTENTE])) {
        redirect('index.php', null, 'Questo artista è già collegato a un account');
    }

    // Verifica che non esista già una richiesta pending
    $existingClaim = table($pdo, TABLE_ARTISTA_CLAIMS)
        ->where(COL_CLAIMS_ID_UTENTE, $_SESSION['user_id'])
        ->where(COL_CLAIMS_STATO, 'pending')
        ->first();
    if ($existingClaim) {
        redirect('index.php', null, 'Hai già una richiesta in attesa di approvazione');
    }

    table($pdo, TABLE_ARTISTA_CLAIMS)->insert([
        COL_CLAIMS_ID_UTENTE          => $_SESSION['user_id'],
        COL_CLAIMS_ID_INTRATTENITORE  => $idIntrattenitore,
        COL_CLAIMS_STATO              => 'pending',
        COL_CLAIMS_MESSAGGIO          => $messaggio,
    ]);

    redirect('index.php', 'Richiesta inviata. Attendi l\'approvazione dell\'amministratore.');
}

function showArtistaDashboard(PDO $pdo): void
{
    if (!isLoggedIn()) { redirect('index.php?action=show_login'); }
    if (!isArtista()) { redirect('index.php', null, 'Accesso negato'); }

    $intrattenitore = getIntrattenitoreByUtente($pdo, $_SESSION['user_id']);
    if (!$intrattenitore) {
        redirect('index.php', null, 'Profilo artista non trovato');
    }

    $stmt = $pdo->prepare(
        "SELECT e.*, l.Nome AS LocationName
         FROM " . TABLE_EVENTI . " e
         LEFT JOIN " . TABLE_LOCATIONS . " l ON e.idLocation = l.id
         JOIN " . TABLE_EVENTO_INTRATTENITORI . " ei ON ei.idEvento = e.id
         WHERE ei.idIntrattenitore = ?
         ORDER BY e.Data DESC"
    );
    $stmt->execute([$intrattenitore['id']]);
    $eventi = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $_SESSION['artista_corrente'] = $intrattenitore;
    $_SESSION['artista_eventi'] = $eventi;
    setPage('artista_dashboard');
}

function approvaClaimAction(PDO $pdo): void
{
    requireAdmin();
    if (!verifyCsrf()) { redirect('index.php'); }

    $idClaim = (int)($_POST['idClaim'] ?? 0);
    $claim = table($pdo, TABLE_ARTISTA_CLAIMS)->where(COL_CLAIMS_ID, $idClaim)->first();
    if (!$claim) { redirect('index.php?action=admin_dashboard', null, 'Richiesta non trovata'); }

    try {
        $pdo->beginTransaction();

        // Collega l'utente all'intrattenitore
        table($pdo, TABLE_INTRATTENITORI)
            ->where(COL_INTRATTENITORI_ID, $claim[COL_CLAIMS_ID_INTRATTENITORE])
            ->update([COL_INTRATTENITORI_ID_UTENTE => $claim[COL_CLAIMS_ID_UTENTE]]);

        // Aggiorna ruolo utente
        table($pdo, TABLE_UTENTI)
            ->where(COL_UTENTI_ID, $claim[COL_CLAIMS_ID_UTENTE])
            ->update([COL_UTENTI_RUOLO => RUOLO_ARTISTA]);

        // Segna la claim come approvata
        table($pdo, TABLE_ARTISTA_CLAIMS)
            ->where(COL_CLAIMS_ID, $idClaim)
            ->update([
                COL_CLAIMS_STATO    => 'approvata',
                'gestita_da'        => $_SESSION['user_id'],
                'gestita_at'        => date('Y-m-d H:i:s')
            ]);

        $pdo->commit();
        redirect('index.php?action=admin_dashboard', 'Richiesta approvata. L\'utente è ora artista.');
    } catch (Exception $e) {
        $pdo->rollBack();
        redirect('index.php?action=admin_dashboard', null, 'Errore: ' . $e->getMessage());
    }
}

function rifiutaClaimAction(PDO $pdo): void
{
    requireAdmin();
    if (!verifyCsrf()) { redirect('index.php'); }

    $idClaim = (int)($_POST['idClaim'] ?? 0);
    table($pdo, TABLE_ARTISTA_CLAIMS)
        ->where(COL_CLAIMS_ID, $idClaim)
        ->update([
            COL_CLAIMS_STATO => 'rifiutata',
            'gestita_da'     => $_SESSION['user_id'],
            'gestita_at'     => date('Y-m-d H:i:s')
        ]);
    redirect('index.php?action=admin_dashboard', 'Richiesta rifiutata.');
}
```

- [ ] **Step 5: Creare views/artista_profilo.php**

Creare `views/artista_profilo.php`:

```php
<?php
$artista = $_SESSION['artista_corrente'] ?? null;
$eventi  = $_SESSION['artista_eventi']   ?? [];
if (!$artista) { echo '<p>Artista non trovato.</p>'; return; }

$social = json_decode($artista['social_links'] ?? '{}', true) ?: [];
$evFuturi  = array_filter($eventi, fn($e) => $e['Data'] >= date('Y-m-d'));
$evPassati = array_filter($eventi, fn($e) => $e['Data'] < date('Y-m-d'));
?>
<div class="artista-profile-page">
    <div class="artista-header">
        <?php if (!empty($artista['foto'])): ?>
            <img src="index.php?action=get_artista_foto&id=<?= (int)$artista['id'] ?>"
                 alt="<?= e($artista['Nome']) ?>" class="artista-foto">
        <?php else: ?>
            <div class="artista-foto-placeholder">
                <i class="fas fa-music fa-3x"></i>
            </div>
        <?php endif; ?>
        <div class="artista-info">
            <h1><?= e($artista['Nome']) ?></h1>
            <p class="artista-categoria"><i class="fas fa-tag"></i> <?= e($artista['Categoria'] ?? '') ?></p>
            <?php if (!empty($social['instagram'])): ?>
                <a href="<?= e($social['instagram']) ?>" target="_blank" rel="noopener">
                    <i class="fab fa-instagram"></i> Instagram
                </a>
            <?php endif; ?>
            <?php if (!empty($social['spotify'])): ?>
                <a href="<?= e($social['spotify']) ?>" target="_blank" rel="noopener">
                    <i class="fab fa-spotify"></i> Spotify
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($artista['bio'])): ?>
    <section class="artista-bio">
        <h2>Bio</h2>
        <p><?= nl2br(e($artista['bio'])) ?></p>
    </section>
    <?php endif; ?>

    <?php if (!empty($evFuturi)): ?>
    <section class="artista-eventi">
        <h2>Prossimi eventi</h2>
        <div class="eventi-grid">
        <?php foreach ($evFuturi as $ev): ?>
            <a href="index.php?action=view_evento&id=<?= (int)$ev['id'] ?>" class="event-card">
                <div class="event-card-info">
                    <h3><?= e($ev['Nome']) ?></h3>
                    <p><?= e($ev['Data']) ?> — <?= e($ev['LocationName']) ?></p>
                </div>
            </a>
        <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($evPassati)): ?>
    <section class="artista-eventi-passati">
        <h2>Storico eventi</h2>
        <div class="eventi-grid">
        <?php foreach ($evPassati as $ev): ?>
            <a href="index.php?action=view_evento&id=<?= (int)$ev['id'] ?>" class="event-card event-card-past">
                <div class="event-card-info">
                    <h3><?= e($ev['Nome']) ?></h3>
                    <p><?= e($ev['Data']) ?> — <?= e($ev['LocationName']) ?></p>
                </div>
            </a>
        <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>
```

- [ ] **Step 6: Creare views/artista_dashboard.php**

Creare `views/artista_dashboard.php`:

```php
<?php
$artista = $_SESSION['artista_corrente'] ?? null;
$eventi  = $_SESSION['artista_eventi']   ?? [];
if (!$artista) { echo '<p>Profilo artista non trovato.</p>'; return; }
?>
<div class="dashboard-page">
    <h1><i class="fas fa-music"></i> Dashboard Artista</h1>

    <section class="dashboard-section">
        <h2>Il tuo profilo pubblico</h2>
        <p><a href="index.php?action=artista_profile&id=<?= (int)$artista['id'] ?>">
            Visualizza profilo pubblico
        </a></p>
    </section>

    <section class="dashboard-section">
        <h2>Modifica profilo</h2>
        <form method="post" action="index.php" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="update_artista_profile">
            <div class="form-group">
                <label>Bio</label>
                <textarea name="bio" rows="5"><?= e($artista['bio'] ?? '') ?></textarea>
            </div>
            <?php $social = json_decode($artista['social_links'] ?? '{}', true) ?: []; ?>
            <div class="form-group">
                <label>Instagram</label>
                <input type="url" name="instagram" value="<?= e($social['instagram'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Spotify</label>
                <input type="url" name="spotify" value="<?= e($social['spotify'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>YouTube</label>
                <input type="url" name="youtube" value="<?= e($social['youtube'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Foto profilo</label>
                <input type="file" name="foto" accept="image/jpeg,image/png">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Salva profilo
            </button>
        </form>
    </section>

    <section class="dashboard-section">
        <h2>I miei eventi (<?= count($eventi) ?>)</h2>
        <?php if (empty($eventi)): ?>
            <p class="no-data">Nessun evento trovato.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead><tr><th>Nome</th><th>Data</th><th>Location</th></tr></thead>
                <tbody>
                <?php foreach ($eventi as $ev): ?>
                    <tr>
                        <td><a href="index.php?action=view_evento&id=<?= (int)$ev['id'] ?>"><?= e($ev['Nome']) ?></a></td>
                        <td><?= e($ev['Data']) ?></td>
                        <td><?= e($ev['LocationName']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>
```

- [ ] **Step 7: Aggiungere endpoint in index.php**

In `index.php`, aggiungere nel switch:

```php
case 'artista_profile':
    require_once __DIR__ . '/controllers/ArtistaController.php';
    showArtistaProfile($pdo);
    break;

case 'artista_dashboard':
    require_once __DIR__ . '/controllers/ArtistaController.php';
    showArtistaDashboard($pdo);
    break;

case 'update_artista_profile':
    require_once __DIR__ . '/controllers/ArtistaController.php';
    updateArtistaProfile($pdo);
    break;

case 'claim_artista':
    require_once __DIR__ . '/controllers/ArtistaController.php';
    claimArtistaAction($pdo);
    break;

case 'approva_claim':
    require_once __DIR__ . '/controllers/ArtistaController.php';
    approvaClaimAction($pdo);
    break;

case 'rifiuta_claim':
    require_once __DIR__ . '/controllers/ArtistaController.php';
    rifiutaClaimAction($pdo);
    break;

case 'get_artista_foto':
    require_once __DIR__ . '/controllers/ArtistaController.php';
    getArtistaFotoApi($pdo);
    break;
```

Aggiungere funzione `getArtistaFotoApi` in `ArtistaController.php`:

```php
function getArtistaFotoApi(PDO $pdo): void
{
    $id = (int)($_GET['id'] ?? 0);
    $artista = table($pdo, TABLE_INTRATTENITORI)
        ->select([COL_INTRATTENITORI_FOTO])
        ->where(COL_INTRATTENITORI_ID, $id)
        ->first();

    if ($artista && !empty($artista[COL_INTRATTENITORI_FOTO])) {
        header('Content-Type: image/jpeg');
        header('Cache-Control: max-age=3600');
        echo $artista[COL_INTRATTENITORI_FOTO];
    } else {
        header('Location: ' . DEFAULT_AVATAR_PATH);
    }
    exit;
}
```

- [ ] **Step 8: Aggiornare main.php — link dashboard artista nel dropdown**

In `views/layouts/main.php`, nel blocco del dropdown utente, aggiungere dopo il blocco `isPromoter()`:

```php
<?php elseif (function_exists('isArtista') && isArtista()): ?>
    <div class="dropdown-divider"></div>
    <a href="index.php?action=artista_dashboard" class="dropdown-item">
        <i class="fas fa-music"></i> Dashboard Artista
    </a>
```

- [ ] **Step 9: Rendere cliccabili gli intrattenitori in evento_dettaglio.php**

In `views/evento_dettaglio.php`, trovare la griglia degli intrattenitori. Sostituire ogni item con:

```php
<?php
$linkArtista = !empty($intr['idUtente'])
    ? 'index.php?action=artista_profile&id=' . (int)$intr['id']
    : null;
?>
<?php if ($linkArtista): ?>
    <a href="<?= e($linkArtista) ?>" class="intrattenitore-card">
<?php else: ?>
    <div class="intrattenitore-card">
<?php endif; ?>
    <div class="intrattenitore-name"><?= e($intr['Nome']) ?></div>
    <div class="intrattenitore-cat"><?= e($intr['Categoria']) ?></div>
    <?php if ($linkArtista): ?>
        <span class="badge badge-primary" style="font-size:0.7rem;">Profilo artista</span>
    <?php endif; ?>
<?php if ($linkArtista): ?>
    </a>
<?php else: ?>
    </div>
<?php endif; ?>
```

- [ ] **Step 10: Aggiungere sezione richieste artista in admin_dashboard**

In `views/admin/dashboard.php`, aggiungere una sezione per le claim pending:

```php
<?php
require_once __DIR__ . '/../../controllers/ArtistaController.php';
$claimsPending = table($pdo, TABLE_ARTISTA_CLAIMS)
    ->select(['artista_claims.*', 'u.Nome AS UtenteNome', 'u.Cognome AS UtenteCognome',
              'i.Nome AS ArtistaName'])
    ->join(TABLE_UTENTI . ' u', 'artista_claims.idUtente', '=', 'u.id')
    ->join(TABLE_INTRATTENITORI . ' i', 'artista_claims.idIntrattenitore', '=', 'i.id')
    ->where(COL_CLAIMS_STATO, 'pending')
    ->get();
?>
<?php if (!empty($claimsPending)): ?>
<section class="dashboard-section">
    <h2><i class="fas fa-user-music"></i> Richieste ruolo artista (<?= count($claimsPending) ?>)</h2>
    <table class="admin-table">
        <thead><tr><th>Utente</th><th>Artista richiesto</th><th>Messaggio</th><th>Data</th><th>Azioni</th></tr></thead>
        <tbody>
        <?php foreach ($claimsPending as $claim): ?>
            <tr>
                <td><?= e($claim['UtenteCognome'] . ' ' . $claim['UtenteNome']) ?></td>
                <td><?= e($claim['ArtistaName']) ?></td>
                <td><?= e(substr($claim['messaggio'] ?? '', 0, 100)) ?></td>
                <td><?= e($claim['created_at']) ?></td>
                <td>
                    <form method="post" action="index.php" style="display:inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="approva_claim">
                        <input type="hidden" name="idClaim" value="<?= (int)$claim['id'] ?>">
                        <button class="btn btn-sm btn-success">Approva</button>
                    </form>
                    <form method="post" action="index.php" style="display:inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="rifiuta_claim">
                        <input type="hidden" name="idClaim" value="<?= (int)$claim['id'] ?>">
                        <button class="btn btn-sm btn-danger">Rifiuta</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php endif; ?>
```

- [ ] **Step 11: Commit**

```bash
git add config/database_schema.php models/Utente.php controllers/ArtistaController.php views/artista_profilo.php views/artista_dashboard.php views/layouts/main.php views/evento_dettaglio.php views/admin/dashboard.php index.php db/ddl_migliorato.sql
git commit -m "feat(F15): ruolo artista con profilo pubblico, dashboard, claim e approvazione admin"
```

---

## Task 12: F11 — Documento identità allegato al biglietto

**Files:**
- Modify: `db/ddl_migliorato.sql`
- Modify: `config/database_schema.php`
- Modify: `controllers/BigliettoController.php`
- Modify: `views/miei_biglietti.php`
- Modify: `index.php`

- [ ] **Step 1: ALTER TABLE biglietti**

```sql
ALTER TABLE biglietti
ADD COLUMN documento_foto MEDIUMBLOB NULL,
ADD COLUMN documento_tipo ENUM('ci','passaporto','patente') NULL,
ADD COLUMN documento_verificato TINYINT(1) NOT NULL DEFAULT 0;
```

- [ ] **Step 2: Aggiungere costanti in database_schema.php**

```php
define('COL_BIGLIETTI_DOCUMENTO_FOTO',       'documento_foto');
define('COL_BIGLIETTI_DOCUMENTO_TIPO',       'documento_tipo');
define('COL_BIGLIETTI_DOCUMENTO_VERIFICATO', 'documento_verificato');
```

- [ ] **Step 3: Aggiungere endpoint upload_documento in index.php**

```php
case 'upload_documento_biglietto':
    require_once __DIR__ . '/controllers/BigliettoController.php';
    uploadDocumentoBigliettoAction($pdo);
    break;

case 'get_documento_biglietto':
    require_once __DIR__ . '/controllers/BigliettoController.php';
    getDocumentoBigliettoAction($pdo);
    break;
```

- [ ] **Step 4: Implementare uploadDocumentoBigliettoAction in BigliettoController.php**

```php
function uploadDocumentoBigliettoAction(PDO $pdo): void
{
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => ERR_LOGIN_REQUIRED]);
        exit;
    }

    if (!verifyCsrf()) {
        http_response_code(403);
        echo json_encode(['error' => ERR_INVALID_CSRF]);
        exit;
    }

    $idBiglietto = (int)($_POST['idBiglietto'] ?? 0);
    $tipoDoc     = sanitize($_POST['tipo'] ?? '');

    if (!in_array($tipoDoc, ['ci', 'passaporto', 'patente'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Tipo documento non valido']);
        exit;
    }

    // Verifica ownership e stato acquistato
    $biglietto = table($pdo, TABLE_BIGLIETTI)
        ->where(COL_BIGLIETTI_ID, $idBiglietto)
        ->where(COL_BIGLIETTI_ID_UTENTE, $_SESSION['user_id'])
        ->where(COL_BIGLIETTI_STATO, STATO_BIGLIETTO_ACQUISTATO)
        ->first();

    if (!$biglietto) {
        http_response_code(404);
        echo json_encode(['error' => 'Biglietto non trovato o non acquistato']);
        exit;
    }

    if (!isset($_FILES['documento']) || $_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'File documento non valido']);
        exit;
    }

    $file = $_FILES['documento'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['error' => 'File troppo grande (max 5MB)']);
        exit;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Solo immagini JPG/PNG accettate']);
        exit;
    }

    $blob = file_get_contents($file['tmp_name']);
    table($pdo, TABLE_BIGLIETTI)
        ->where(COL_BIGLIETTI_ID, $idBiglietto)
        ->update([
            COL_BIGLIETTI_DOCUMENTO_FOTO => $blob,
            COL_BIGLIETTI_DOCUMENTO_TIPO => $tipoDoc,
        ]);

    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Documento caricato con successo']);
    exit;
}

function getDocumentoBigliettoAction(PDO $pdo): void
{
    if (!isLoggedIn()) { http_response_code(401); exit; }

    $id = (int)($_GET['id'] ?? 0);
    $biglietto = table($pdo, TABLE_BIGLIETTI)
        ->select([COL_BIGLIETTI_DOCUMENTO_FOTO, COL_BIGLIETTI_ID_UTENTE])
        ->where(COL_BIGLIETTI_ID, $id)
        ->first();

    if (!$biglietto || (int)$biglietto[COL_BIGLIETTI_ID_UTENTE] !== (int)$_SESSION['user_id']) {
        http_response_code(403);
        exit;
    }

    if (empty($biglietto[COL_BIGLIETTI_DOCUMENTO_FOTO])) {
        http_response_code(404);
        exit;
    }

    header('Content-Type: image/jpeg');
    header('Cache-Control: no-store');
    echo $biglietto[COL_BIGLIETTI_DOCUMENTO_FOTO];
    exit;
}
```

- [ ] **Step 5: Aggiungere form upload documento in miei_biglietti.php**

Nel modal biglietto (dove si vede il QR code), aggiungere la sezione documento. Trovare la funzione JS che popola il modal e aggiungere il form di upload:

```javascript
// Aggiungere nel modal HTML, dopo la sezione QR:
const documentoHtml = `
<div class="documento-section" style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid #e5e7eb;">
    <h4 style="margin-bottom:0.75rem;"><i class="fas fa-id-card"></i> Documento d'identità</h4>
    ${ticket.documento_tipo
        ? `<p style="color:#065f46;"><i class="fas fa-check-circle"></i> Documento caricato: ${ticket.documento_tipo.toUpperCase()}</p>
           <img src="index.php?action=get_documento_biglietto&id=${ticket.id}" style="max-width:100%;border-radius:8px;margin-top:0.5rem;">`
        : `<form id="docForm${ticket.id}" style="display:flex;flex-direction:column;gap:0.5rem;">
            <select name="tipo" required style="padding:0.5rem;border-radius:6px;border:1px solid #d1d5db;">
                <option value="">— Tipo documento —</option>
                <option value="ci">Carta d'identità</option>
                <option value="passaporto">Passaporto</option>
                <option value="patente">Patente</option>
            </select>
            <input type="file" name="documento" accept="image/jpeg,image/png" required>
            <button type="button" class="btn btn-primary btn-sm"
                    onclick="uploadDocumento(${ticket.id}, this.closest('form'))">
                <i class="fas fa-upload"></i> Carica documento
            </button>
           </form>`
    }
</div>`;
```

Aggiungere funzione JS:
```javascript
async function uploadDocumento(idBiglietto, form) {
    const fd = new FormData(form);
    fd.append('idBiglietto', idBiglietto);
    fd.append('csrf_token', window.EventsMaster?.csrfToken || '');
    fd.append('action', 'upload_documento_biglietto');
    const res = await fetch('index.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        alert('Documento caricato con successo!');
        location.reload();
    } else {
        alert(data.error || 'Errore durante il caricamento');
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add config/database_schema.php controllers/BigliettoController.php views/miei_biglietti.php index.php db/ddl_migliorato.sql
git commit -m "feat(F11): upload documento d'identità per biglietti acquistati"
```

---

## Task 13: F12 — Verifica documento (LIVELLO BASE, solo OCR manuale)

> **Nota**: Per semplicità questo task implementa solo la distinzione visiva "documento caricato / non caricato", senza OCR reale. L'OCR richiederebbe Tesseract installato sul server che XAMPP non ha.

**Files:**
- Modify: `db/ddl_migliorato.sql`
- Modify: `config/database_schema.php`
- Modify: `views/miei_biglietti.php`

- [ ] **Step 1: Aggiungere colonna verifica in biglietti**

```sql
ALTER TABLE biglietti
ADD COLUMN documento_verifica_stato ENUM('nessuno','caricato','verificato','rifiutato') NOT NULL DEFAULT 'nessuno';
```

- [ ] **Step 2: Aggiungere costante**

```php
define('COL_BIGLIETTI_DOC_VERIFICA_STATO', 'documento_verifica_stato');
```

- [ ] **Step 3: Aggiornare uploadDocumentoBigliettoAction**

In `BigliettoController.php`, nell'`update()` dopo l'upload del documento, aggiungere:

```php
COL_BIGLIETTI_DOC_VERIFICA_STATO => 'caricato',
```

- [ ] **Step 4: Mostrare badge stato verifica nel modal biglietto**

In `views/miei_biglietti.php`, aggiornare il JS del modal per mostrare:

```javascript
const verificaStati = {
    'nessuno':    '<span style="color:#6b7280"><i class="fas fa-times"></i> Nessun documento</span>',
    'caricato':   '<span style="color:#d97706"><i class="fas fa-clock"></i> Documento in attesa di verifica</span>',
    'verificato': '<span style="color:#065f46"><i class="fas fa-check-circle"></i> Documento verificato</span>',
    'rifiutato':  '<span style="color:#991b1b"><i class="fas fa-times-circle"></i> Documento rifiutato — ricarica</span>',
};
```

- [ ] **Step 5: Commit**

```bash
git add config/database_schema.php controllers/BigliettoController.php views/miei_biglietti.php db/ddl_migliorato.sql
git commit -m "feat(F12): stato verifica documento biglietto (livello base)"
```

---

## Task 14: F1 — Mappa SVG selezione posto (stub con fallback)

> **Nota**: F1 è complessa e richiede un editor SVG. Questo task implementa solo l'infrastruttura DB e il fallback (dropdown esistente), rimandando l'editor SVG a sviluppo futuro.

**Files:**
- Modify: `db/ddl_migliorato.sql`
- Modify: `config/database_schema.php`

- [ ] **Step 1: ALTER TABLE**

```sql
ALTER TABLE locations
ADD COLUMN svg_path TEXT NULL COMMENT 'SVG piantina location';

ALTER TABLE settore_biglietti
ADD COLUMN svg_seat_id VARCHAR(20) NULL COMMENT 'ID elemento SVG (es. A1, B3)';
```

- [ ] **Step 2: Aggiungere costanti**

```php
define('COL_LOCATIONS_SVG_PATH',          'svg_path');
define('COL_SETTORE_BIGLIETTI_SVG_SEAT',  'svg_seat_id');
```

- [ ] **Step 3: Aggiungere logica fallback in evento_dettaglio.php**

In `views/evento_dettaglio.php`, aggiungere commento nel selettore settori:

```php
<?php
// Se la location ha un SVG, in futuro mostrare mappa interattiva.
// Per ora mostra sempre il dropdown.
$hasSvg = !empty($location['svg_path']);
// TODO (F1): se $hasSvg, renderizzare la mappa SVG invece del dropdown
?>
```

- [ ] **Step 4: Commit**

```bash
git add config/database_schema.php db/ddl_migliorato.sql views/evento_dettaglio.php
git commit -m "feat(F1): infrastruttura DB per mappa SVG (fallback dropdown mantenuto)"
```

---

## Note finali

- F9 (app Blibber) è esclusa come da istruzioni utente.
- F12 livello avanzato (OCR/API esterna) richiede Tesseract o un servizio a pagamento — non implementato.
- F1 editor SVG completo richiede una sessione dedicata per l'UI dell'editor.
- Tutti i task usano le costanti `TABLE_*` e `COL_*` da `database_schema.php`.
- Ogni modifica DB va rispecchiata in `db/ddl_migliorato.sql`.
