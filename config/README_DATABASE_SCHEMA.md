# Database Schema Configuration

## Scopo

Il file `database_schema.php` centralizza tutti i nomi di tabelle e colonne del database. Questo permette di:

- ✅ **Modificare lo schema in un solo punto** anziché cercare in tutto il progetto
- ✅ **Evitare errori di battitura** nei nomi di tabelle/colonne
- ✅ **Autocomplete nell'IDE** per i nomi delle colonne
- ✅ **Refactoring sicuro** quando lo schema cambia

## Come Usare

### 1. Include il file all'inizio dei tuoi models

```php
<?php
require_once __DIR__ . '/../config/database_schema.php';

function getUtenteById(PDO $pdo, int $id): ?array
{
    // Invece di scrivere direttamente i nomi:
    // $stmt = $pdo->prepare("SELECT * FROM Utenti WHERE id = ?");

    // Usa le costanti:
    $stmt = $pdo->prepare("
        SELECT * FROM " . TABLE_UTENTI . "
        WHERE " . COL_UTENTI_ID . " = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}
```

### 2. Esempio con JOIN

```php
<?php
require_once __DIR__ . '/../config/database_schema.php';

function getEventoById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT
            e.*,
            l." . COL_LOCATIONS_NOME . " as LocationName,
            m." . COL_MANIFESTAZIONI_NOME . " as ManifestazioneName
        FROM " . TABLE_EVENTI . " e
        JOIN " . TABLE_LOCATIONS . " l ON e." . COL_EVENTI_ID_LOCATION . " = l." . COL_LOCATIONS_ID . "
        LEFT JOIN " . TABLE_MANIFESTAZIONI . " m ON e." . COL_EVENTI_ID_MANIFESTAZIONE . " = m." . COL_MANIFESTAZIONI_ID . "
        WHERE e." . COL_EVENTI_ID . " = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}
```

### 3. Uso delle funzioni helper

```php
<?php
require_once __DIR__ . '/../config/database_schema.php';

// Costruisce automaticamente la SELECT
$columns = [COL_UTENTI_ID, COL_UTENTI_NOME, COL_UTENTI_EMAIL];
$select = buildSelect(TABLE_UTENTI, $columns, 'u');
// Risultato: "u.id, u.Nome, u.Email"

$stmt = $pdo->prepare("SELECT $select FROM " . TABLE_UTENTI . " u");
```

### 4. Esempio con INSERT

```php
<?php
require_once __DIR__ . '/../config/database_schema.php';

function createEvento(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare("
        INSERT INTO " . TABLE_EVENTI . " (
            " . COL_EVENTI_NOME . ",
            " . COL_EVENTI_DATA . ",
            " . COL_EVENTI_ID_LOCATION . ",
            " . COL_EVENTI_CATEGORIA . "
        ) VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        $data['nome'],
        $data['data'],
        $data['idLocation'],
        $data['categoria'] ?? CATEGORIA_FAMIGLIA
    ]);

    return (int) $pdo->lastInsertId();
}
```

### 5. Uso delle costanti ENUM

```php
<?php
require_once __DIR__ . '/../config/database_schema.php';

// Stati biglietto
if ($biglietto[COL_BIGLIETTI_STATO] === STATO_BIGLIETTO_CARRELLO) {
    // logica per biglietti nel carrello
}

// Categorie eventi
$validCategories = [
    CATEGORIA_CONCERTI,
    CATEGORIA_TEATRO,
    CATEGORIA_SPORT,
    CATEGORIA_COMEDY,
    CATEGORIA_CINEMA,
    CATEGORIA_FAMIGLIA
];

// Ruoli utente
if ($user[COL_UTENTI_RUOLO] === RUOLO_ADMIN) {
    // logica per admin
}
```

## Quando Modificare database_schema.php

Modifica questo file quando:

1. **Rinomini una tabella** nel database
   ```php
   // Prima
   define('TABLE_EVENTI', 'Eventi');

   // Dopo il rename
   define('TABLE_EVENTI', 'Events');
   ```

2. **Rinomini una colonna** nel database
   ```php
   // Prima
   define('COL_EVENTI_IMMAGINE', 'Immagine');

   // Dopo il rename
   define('COL_EVENTI_IMMAGINE', 'Image');
   ```

3. **Aggiungi nuove tabelle/colonne**
   ```php
   // Nuova tabella
   define('TABLE_PRENOTAZIONI', 'Prenotazioni');

   // Nuove colonne
   define('COL_EVENTI_DURATA', 'Durata');
   define('COL_EVENTI_VISIBILE', 'Visibile');
   ```

## Migrazione del Codice Esistente

Per migrare gradualmente il codice esistente:

1. Inizia con un model alla volta
2. Sostituisci i nomi hardcoded con le costanti
3. Testa che tutto funzioni correttamente
4. Procedi con il prossimo model

**Suggerimento**: Usa la funzione "Find & Replace" dell'IDE per velocizzare:
- Cerca: `'Utenti'`
- Sostituisci con: `TABLE_UTENTI`

## Vantaggi

### Prima (senza database_schema.php)
```php
// Se rinomini 'Utenti' in 'Users', devi cercare in TUTTI i file:
$stmt = $pdo->query("SELECT * FROM Utenti");
$stmt = $pdo->query("SELECT * FROM Utenti WHERE Email = ?");
$stmt = $pdo->query("INSERT INTO Utenti (Nome, Email) VALUES (?, ?)");
// ... e così via in decine di file
```

### Dopo (con database_schema.php)
```php
// Basta cambiare UNA RIGA in database_schema.php:
define('TABLE_UTENTI', 'Users'); // <-- Cambia solo qui!

// E tutte le query funzionano automaticamente:
$stmt = $pdo->query("SELECT * FROM " . TABLE_UTENTI);
$stmt = $pdo->query("SELECT * FROM " . TABLE_UTENTI . " WHERE " . COL_UTENTI_EMAIL . " = ?");
```

## Note

- Le costanti sono **case-sensitive**
- Usa sempre le costanti nei nuovi file
- Migra gradualmente il codice esistente
- Aggiorna questo file quando lo schema cambia
