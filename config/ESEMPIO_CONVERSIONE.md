# Esempio di Conversione a database_schema.php

## Prima della conversione (Utente.php)

```php
<?php
/**
 * Model Utente - VERSIONE VECCHIA
 */

function getAllUtenti(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM Utenti ORDER BY Cognome, Nome")->fetchAll();
}

function getUtenteById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM Utenti WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getUtenteByEmail(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM Utenti WHERE Email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}

function updateUtente(PDO $pdo, int $id, array $data): bool
{
    $stmt = $pdo->prepare("
        UPDATE Utenti SET Nome = ?, Cognome = ?, Email = ?
        WHERE id = ?
    ");
    return $stmt->execute([
        $data['Nome'],
        $data['Cognome'],
        $data['Email'],
        $id
    ]);
}

function setUserRole(PDO $pdo, int $id, string $role): bool
{
    $validRoles = ['user', 'promoter', 'mod', 'admin'];
    if (!in_array($role, $validRoles)) {
        return false;
    }

    $stmt = $pdo->prepare("UPDATE Utenti SET ruolo = ? WHERE id = ?");
    return $stmt->execute([$role, $id]);
}
```

## Dopo la conversione (Utente.php)

```php
<?php
/**
 * Model Utente - VERSIONE NUOVA con database_schema.php
 */

require_once __DIR__ . '/../config/database_schema.php';

function getAllUtenti(PDO $pdo): array
{
    return $pdo->query("
        SELECT * FROM " . TABLE_UTENTI . "
        ORDER BY " . COL_UTENTI_COGNOME . ", " . COL_UTENTI_NOME
    )->fetchAll();
}

function getUtenteById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT * FROM " . TABLE_UTENTI . "
        WHERE " . COL_UTENTI_ID . " = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getUtenteByEmail(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare("
        SELECT * FROM " . TABLE_UTENTI . "
        WHERE " . COL_UTENTI_EMAIL . " = ?
    ");
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}

function updateUtente(PDO $pdo, int $id, array $data): bool
{
    $stmt = $pdo->prepare("
        UPDATE " . TABLE_UTENTI . " SET
            " . COL_UTENTI_NOME . " = ?,
            " . COL_UTENTI_COGNOME . " = ?,
            " . COL_UTENTI_EMAIL . " = ?
        WHERE " . COL_UTENTI_ID . " = ?
    ");
    return $stmt->execute([
        $data['Nome'],
        $data['Cognome'],
        $data['Email'],
        $id
    ]);
}

function setUserRole(PDO $pdo, int $id, string $role): bool
{
    // Usa le costanti invece dei valori hardcoded
    $validRoles = [RUOLO_USER, RUOLO_PROMOTER, RUOLO_MOD, RUOLO_ADMIN];
    if (!in_array($role, $validRoles)) {
        return false;
    }

    $stmt = $pdo->prepare("
        UPDATE " . TABLE_UTENTI . "
        SET " . COL_UTENTI_RUOLO . " = ?
        WHERE " . COL_UTENTI_ID . " = ?
    ");
    return $stmt->execute([$role, $id]);
}
```

## Esempio con Query Complessa (Eventi con JOIN)

### Prima

```php
function getEventoById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT e.*, m.Nome as ManifestazioneName, l.Nome as LocationName
        FROM Eventi e
        JOIN Manifestazioni m ON e.idManifestazione = m.id
        JOIN Locations l ON e.idLocation = l.id
        WHERE e.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}
```

### Dopo

```php
require_once __DIR__ . '/../config/database_schema.php';

function getEventoById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT
            e.*,
            m." . COL_MANIFESTAZIONI_NOME . " as ManifestazioneName,
            l." . COL_LOCATIONS_NOME . " as LocationName
        FROM " . TABLE_EVENTI . " e
        JOIN " . TABLE_MANIFESTAZIONI . " m
            ON e." . COL_EVENTI_ID_MANIFESTAZIONE . " = m." . COL_MANIFESTAZIONI_ID . "
        JOIN " . TABLE_LOCATIONS . " l
            ON e." . COL_EVENTI_ID_LOCATION . " = l." . COL_LOCATIONS_ID . "
        WHERE e." . COL_EVENTI_ID . " = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}
```

## Vantaggi Evidenti

### Scenario: Rinominare "idLocation" in "location_id"

#### Senza database_schema.php
Devi cercare e modificare in **TUTTI** questi file:
- `models/Evento.php` (5 query)
- `models/Location.php` (3 query)
- `models/Settore.php` (4 query)
- `controllers/EventoController.php` (2 query)
- `controllers/LocationController.php` (3 query)
- ... e altri 10+ file

**Tempo stimato: 2-3 ore + rischio di errori**

#### Con database_schema.php
Modifichi **1 SOLA RIGA**:
```php
// In config/database_schema.php
define('COL_EVENTI_ID_LOCATION', 'location_id'); // <-- FATTO!
```

**Tempo stimato: 30 secondi + zero errori**

## Checklist per la Conversione

- [ ] Include `database_schema.php` all'inizio del file
- [ ] Sostituisci i nomi delle tabelle con `TABLE_*`
- [ ] Sostituisci i nomi delle colonne con `COL_*`
- [ ] Sostituisci i valori ENUM con le costanti (RUOLO_*, STATO_*, ecc.)
- [ ] Testa che tutte le query funzionino
- [ ] Committa le modifiche

## Regex per Find & Replace (IDE)

Puoi usare questi pattern per velocizzare:

### Tabelle
```
Find:    'Utenti'
Replace: TABLE_UTENTI
```

### Colonne comuni
```
Find:    \.id\b
Replace: .COL_*_ID  (sostituisci * con il nome tabella)
```

### Valori ENUM
```
Find:    'user'
Replace: RUOLO_USER

Find:    'carrello'
Replace: STATO_BIGLIETTO_CARRELLO
```
