<?php
/**
 * Model Location
 * Gestisce i luoghi dove si svolgono gli eventi
 *
 * Ogni location ha un indirizzo strutturato e puo avere piu settori
 * con diverse capienze e fasce di prezzo.
 */

/**
 * Recupera tutte le location ordinate alfabeticamente
 *
 * @return array Lista completa location
 */
function getAllLocations(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM Locations ORDER BY Nome")->fetchAll();
}

/**
 * Recupera una location tramite ID
 *
 * @return array|null Dati location o null se non trovata
 */
function getLocationById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM Locations WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Recupera una location con tutti i suoi settori
 * Utile per la selezione posti durante l'acquisto
 *
 * @return array|null Location con array 'settori' annidato
 */
function getLocationWithSettori(PDO $pdo, int $id): ?array
{
    $location = getLocationById($pdo, $id);
    if ($location) {
        $location['settori'] = getSettoriByLocation($pdo, $id);
    }
    return $location;
}

/**
 * Recupera i settori di una location
 * Ordinati per moltiplicatore prezzo decrescente (settori premium prima)
 *
 * @return array Lista settori della location
 */
function getSettoriByLocation(PDO $pdo, int $idLocation): array
{
    $stmt = $pdo->prepare("
        SELECT * FROM Settori
        WHERE idLocation = ?
        ORDER BY MoltiplicatorePrezzo DESC
    ");
    $stmt->execute([$idLocation]);
    return $stmt->fetchAll();
}

/**
 * Crea una nuova location
 *
 * @param array $data Dati indirizzo: Nome, Stato, Regione, CAP, Citta, civico
 * @return int ID della nuova location
 */
function createLocation(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare("
        INSERT INTO Locations (Nome, Indirizzo, Citta, CAP, Regione, Capienza)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['Nome'],
        $data['Indirizzo'] ?? '',
        $data['Citta'] ?? '',
        $data['CAP'] ?? '',
        $data['Regione'] ?? '',
        $data['Capienza'] ?? 0
    ]);
    return (int) $pdo->lastInsertId();
}

/**
 * Aggiorna i dati di una location
 *
 * @return bool Esito operazione
 */
function updateLocation(PDO $pdo, int $id, array $data): bool
{
    $stmt = $pdo->prepare("
        UPDATE Locations SET
            Nome = ?,
            Indirizzo = ?,
            Citta = ?,
            CAP = ?,
            Regione = ?,
            Capienza = ?
        WHERE id = ?
    ");
    return $stmt->execute([
        $data['Nome'],
        $data['Indirizzo'] ?? '',
        $data['Citta'] ?? '',
        $data['CAP'] ?? '',
        $data['Regione'] ?? '',
        $data['Capienza'] ?? 0,
        $id
    ]);
}

/**
 * Elimina una location
 * Gli eventi associati devono essere gestiti prima dell'eliminazione
 *
 * @return bool Esito operazione
 */
function deleteLocation(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM Locations WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Alias per compatibilità con il controller
 */
function deleteLocationById(PDO $pdo, int $id): bool
{
    return deleteLocation($pdo, $id);
}

/**
 * Recupera le location create da un utente specifico
 * Solo per promoter
 *
 * @return array Lista location create dall'utente
 */
function getLocationsByCreator(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT l.*
        FROM Locations l
        INNER JOIN CreatoriLocations cl ON l.id = cl.idLocation
        WHERE cl.idUtente = ?
        ORDER BY l.Nome
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Calcola i posti ancora disponibili in un settore per un evento
 * Sottrae dal totale posti i biglietti gia venduti per quel settore
 *
 * @return int Numero posti disponibili
 */
function getPostiDisponibiliSettore(PDO $pdo, int $idSettore, int $idEvento): int
{
    $stmt = $pdo->prepare("
        SELECT s.PostiDisponibili - COUNT(sb.idBiglietto) as disponibili
        FROM Settori s
        LEFT JOIN Settore_Biglietti sb ON s.id = sb.idSettore
        LEFT JOIN Biglietti b ON sb.idBiglietto = b.id AND b.idEvento = ?
        WHERE s.id = ?
        GROUP BY s.id
    ");
    $stmt->execute([$idEvento, $idSettore]);
    $result = $stmt->fetch();
    return $result ? (int) $result['disponibili'] : 0;
}
