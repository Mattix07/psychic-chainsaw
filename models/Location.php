<?php
/**
 * Model Location
 * Gestisce i luoghi dove si svolgono gli eventi
 *
 * Ogni location ha un indirizzo strutturato e puo avere piu settori
 * con diverse capienze e fasce di prezzo.
 */

require_once __DIR__ . '/../config/database_schema.php';
require_once __DIR__ . '/../lib/QueryBuilder.php';

/**
 * Recupera tutte le location ordinate alfabeticamente
 *
 * @return array Lista completa location
 */
function getAllLocations(PDO $pdo): array
{
    return table($pdo, TABLE_LOCATIONS)
        ->orderBy(COL_LOCATIONS_NOME)
        ->get();
}

/**
 * Recupera una location tramite ID
 *
 * @return array|null Dati location o null se non trovata
 */
function getLocationById(PDO $pdo, int $id): ?array
{
    return table($pdo, TABLE_LOCATIONS)
        ->where(COL_LOCATIONS_ID, $id)
        ->first();
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
        SELECT * FROM " . TABLE_SETTORI . "
        WHERE " . COL_SETTORI_ID_LOCATION . " = ?
        ORDER BY " . COL_SETTORI_MOLTIPLICATORE_PREZZO . " DESC
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
    return table($pdo, TABLE_LOCATIONS)->insert([
        COL_LOCATIONS_NOME => $data['Nome'],
        COL_LOCATIONS_INDIRIZZO => $data['Indirizzo'] ?? '',
        COL_LOCATIONS_CITTA => $data['Citta'] ?? '',
        COL_LOCATIONS_CAP => $data['CAP'] ?? '',
        COL_LOCATIONS_REGIONE => $data['Regione'] ?? '',
        COL_LOCATIONS_CAPIENZA => $data['Capienza'] ?? 0
    ]);
}

/**
 * Aggiorna i dati di una location
 *
 * @return bool Esito operazione
 */
function updateLocation(PDO $pdo, int $id, array $data): bool
{
    $stmt = $pdo->prepare("
        UPDATE " . TABLE_LOCATIONS . " SET
            " . COL_LOCATIONS_NOME . " = ?,
            " . COL_LOCATIONS_INDIRIZZO . " = ?,
            " . COL_LOCATIONS_CITTA . " = ?,
            " . COL_LOCATIONS_CAP . " = ?,
            " . COL_LOCATIONS_REGIONE . " = ?,
            " . COL_LOCATIONS_CAPIENZA . " = ?
        WHERE " . COL_LOCATIONS_ID . " = ?
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
    $stmt = $pdo->prepare("DELETE FROM " . TABLE_LOCATIONS . " WHERE " . COL_LOCATIONS_ID . " = ?");
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
        FROM " . TABLE_LOCATIONS . " l
        INNER JOIN " . TABLE_CREATORI_LOCATIONS . " cl ON l." . COL_LOCATIONS_ID . " = cl.idLocation
        WHERE cl.idUtente = ?
        ORDER BY l." . COL_LOCATIONS_NOME . "
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
        SELECT s." . COL_SETTORI_POSTI_DISPONIBILI . " - COUNT(sb." . COL_SETTORE_BIGLIETTI_ID_BIGLIETTO . ") as disponibili
        FROM " . TABLE_SETTORI . " s
        LEFT JOIN " . TABLE_SETTORE_BIGLIETTI . " sb ON s." . COL_SETTORI_ID . " = sb." . COL_SETTORE_BIGLIETTI_ID_SETTORE . "
        LEFT JOIN " . TABLE_BIGLIETTI . " b ON sb." . COL_SETTORE_BIGLIETTI_ID_BIGLIETTO . " = b." . COL_BIGLIETTI_ID . " AND b." . COL_BIGLIETTI_ID_EVENTO . " = ?
        WHERE s." . COL_SETTORI_ID . " = ?
        GROUP BY s." . COL_SETTORI_ID . "
    ");
    $stmt->execute([$idEvento, $idSettore]);
    $result = $stmt->fetch();
    return $result ? (int) $result['disponibili'] : 0;
}
