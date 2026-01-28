<?php
/**
 * Model Evento
 * Gestisce operazioni CRUD e query per gli eventi
 */

require_once __DIR__ . '/../config/database_schema.php';
require_once __DIR__ . '/../lib/QueryBuilder.php';

/**
 * Recupera tutti gli eventi con dati di manifestazione e location
 * @return array Lista eventi ordinati per data e ora
 */
function getAllEventi(PDO $pdo): array
{
    return $pdo->query("
        SELECT e.*, m." . COL_MANIFESTAZIONI_NOME . " as ManifestazioneName, l." . COL_LOCATIONS_NOME . " as LocationName
        FROM " . TABLE_EVENTI . " e
        JOIN " . TABLE_MANIFESTAZIONI . " m ON e." . COL_EVENTI_ID_MANIFESTAZIONE . " = m." . COL_MANIFESTAZIONI_ID . "
        JOIN " . TABLE_LOCATIONS . " l ON e." . COL_EVENTI_ID_LOCATION . " = l." . COL_LOCATIONS_ID . "
        ORDER BY e." . COL_EVENTI_DATA . ", e." . COL_EVENTI_ORA_INIZIO . "
    ")->fetchAll();
}

/**
 * Recupera un evento specifico con tutti i dettagli
 * @return array|null Dati evento o null se non trovato
 */
function getEventoById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT e.*, COALESCE(m." . COL_MANIFESTAZIONI_NOME . ", 'indipendente') AS ManifestazioneName, l." . COL_LOCATIONS_NOME . " as LocationName
        FROM " . TABLE_EVENTI . " e
        LEFT JOIN " . TABLE_MANIFESTAZIONI . " m ON e." . COL_EVENTI_ID_MANIFESTAZIONE . " = m." . COL_MANIFESTAZIONI_ID . "
        JOIN " . TABLE_LOCATIONS . " l ON e." . COL_EVENTI_ID_LOCATION . " = l." . COL_LOCATIONS_ID . "
        WHERE e." . COL_EVENTI_ID . " = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Recupera tutti gli eventi di una manifestazione
 * Utile per mostrare eventi correlati nella stessa manifestazione
 * @return array Lista eventi della manifestazione
 */
function getEventiByManifestazione(PDO $pdo, int $idManifestazione): array
{
    $stmt = $pdo->prepare("
        SELECT e.*, l." . COL_LOCATIONS_NOME . " as LocationName
        FROM " . TABLE_EVENTI . " e
        JOIN " . TABLE_LOCATIONS . " l ON e." . COL_EVENTI_ID_LOCATION . " = l." . COL_LOCATIONS_ID . "
        WHERE e." . COL_EVENTI_ID_MANIFESTAZIONE . " = ?
        ORDER BY e." . COL_EVENTI_DATA . ", e." . COL_EVENTI_ORA_INIZIO . "
    ");
    $stmt->execute([$idManifestazione]);
    return $stmt->fetchAll();
}

/**
 * Recupera eventi di una manifestazione cercando per nome
 * @return array Lista eventi trovati
 */
function getEventiByManifestazioneNome(PDO $pdo, string $nome): array
{
    $stmt = $pdo->prepare("
        SELECT e." . COL_EVENTI_NOME . " as eNome, e." . COL_EVENTI_ORA_INIZIO . ", e." . COL_EVENTI_ORA_FINE . ", e." . COL_EVENTI_DATA . ", e." . COL_EVENTI_PREZZO_NO_MOD . ", l." . COL_LOCATIONS_NOME . " as LocationName
        FROM " . TABLE_EVENTI . " e
        JOIN " . TABLE_MANIFESTAZIONI . " m ON e." . COL_EVENTI_ID_MANIFESTAZIONE . " = m." . COL_MANIFESTAZIONI_ID . "
        JOIN " . TABLE_LOCATIONS . " l ON e." . COL_EVENTI_ID_LOCATION . " = l." . COL_LOCATIONS_ID . "
        WHERE m." . COL_MANIFESTAZIONI_NOME . " = ?
        ORDER BY e." . COL_EVENTI_DATA . ", e." . COL_EVENTI_ORA_INIZIO . " ASC
    ");
    $stmt->execute([$nome]);
    return $stmt->fetchAll();
}

/**
 * Recupera i prossimi eventi futuri
 * Filtra solo eventi con data >= oggi
 * @param int $limit Numero massimo di risultati
 * @return array Lista eventi futuri
 */
function getEventiProssimi(PDO $pdo, int $limit = 10): array
{
    $stmt = $pdo->prepare("
        SELECT e.*, m." . COL_MANIFESTAZIONI_NOME . " as ManifestazioneName, l." . COL_LOCATIONS_NOME . " as LocationName
        FROM " . TABLE_EVENTI . " e
        JOIN " . TABLE_MANIFESTAZIONI . " m ON e." . COL_EVENTI_ID_MANIFESTAZIONE . " = m." . COL_MANIFESTAZIONI_ID . "
        JOIN " . TABLE_LOCATIONS . " l ON e." . COL_EVENTI_ID_LOCATION . " = l." . COL_LOCATIONS_ID . "
        WHERE e." . COL_EVENTI_DATA . " >= CURDATE()
        ORDER BY e." . COL_EVENTI_DATA . ", e." . COL_EVENTI_ORA_INIZIO . "
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Crea un nuovo evento
 * @return int ID del nuovo evento
 */
function createEvento(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare("
        INSERT INTO " . TABLE_EVENTI . " (" . COL_EVENTI_ID_MANIFESTAZIONE . ", " . COL_EVENTI_ID_LOCATION . ", " . COL_EVENTI_NOME . ", " . COL_EVENTI_PREZZO_NO_MOD . ", " . COL_EVENTI_DATA . ", " . COL_EVENTI_ORA_INIZIO . ", " . COL_EVENTI_ORA_FINE . ", " . COL_EVENTI_PROGRAMMA . ", " . COL_EVENTI_IMMAGINE . ", " . COL_EVENTI_CATEGORIA . ")
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['idManifestazione'],
        $data['idLocation'],
        $data['Nome'],
        $data['PrezzoNoMod'],
        $data['Data'],
        $data['OraI'],
        $data['OraF'],
        $data['Programma'] ?? null,
        $data['Immagine'] ?? null,
        $data['Categoria'] ?? CATEGORIA_FAMIGLIA
    ]);
    return (int) $pdo->lastInsertId();
}

/**
 * Aggiorna i dati di un evento esistente
 * @return bool Esito operazione
 */
function updateEvento(PDO $pdo, int $id, array $data): bool
{
    $stmt = $pdo->prepare("
        UPDATE " . TABLE_EVENTI . " SET
            " . COL_EVENTI_ID_MANIFESTAZIONE . " = ?,
            " . COL_EVENTI_ID_LOCATION . " = ?,
            " . COL_EVENTI_NOME . " = ?,
            " . COL_EVENTI_PREZZO_NO_MOD . " = ?,
            " . COL_EVENTI_DATA . " = ?,
            " . COL_EVENTI_ORA_INIZIO . " = ?,
            " . COL_EVENTI_ORA_FINE . " = ?,
            " . COL_EVENTI_PROGRAMMA . " = ?,
            " . COL_EVENTI_CATEGORIA . " = ?
        WHERE " . COL_EVENTI_ID . " = ?
    ");
    return $stmt->execute([
        $data['idManifestazione'],
        $data['idLocation'],
        $data['Nome'],
        $data['PrezzoNoMod'],
        $data['Data'],
        $data['OraI'],
        $data['OraF'],
        $data['Programma'] ?? null,
        $data['Categoria'] ?? CATEGORIA_FAMIGLIA,
        $id
    ]);
}

/**
 * Elimina un evento (i biglietti associati vengono eliminati in cascade)
 * @return bool Esito operazione
 */
function deleteEvento(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM " . TABLE_EVENTI . " WHERE " . COL_EVENTI_ID . " = ?");
    return $stmt->execute([$id]);
}

/**
 * Recupera gli intrattenitori che si esibiscono in un evento
 * Include gli orari delle esibizioni
 * @return array Lista intrattenitori con orari
 */
function getIntrattenitoriEvento(PDO $pdo, int $idEvento): array
{
    $stmt = $pdo->prepare("
        SELECT i.*, e." . COL_EVENTI_ORA_INIZIO . ", e." . COL_EVENTI_ORA_FINE . "
        FROM " . TABLE_INTRATTENITORE . " i, " . TABLE_EVENTI . " e, " . TABLE_EVENTO_INTRATTENITORE . " es
        WHERE es.idEvento = ?
        ORDER BY e." . COL_EVENTI_ORA_INIZIO . "
    ");
    $stmt->execute([$idEvento]);
    return $stmt->fetchAll();
}

/**
 * Cerca eventi per testo libero
 * Ricerca su nome evento, manifestazione e location
 * @param string $query Termine di ricerca
 * @return array Eventi corrispondenti
 */
function searchEventiByQuery(PDO $pdo, string $query): array
{
    $search = "%{$query}%";
    $stmt = $pdo->prepare("
        SELECT e.*, e." . COL_EVENTI_NOME . " as eNome, e." . COL_EVENTI_ID . " as id, m." . COL_MANIFESTAZIONI_NOME . " as ManifestazioneName, e." . COL_EVENTI_CATEGORIA . ",
               l." . COL_LOCATIONS_NOME . " as LocationName
        FROM " . TABLE_EVENTI . " e
        JOIN " . TABLE_MANIFESTAZIONI . " m ON e." . COL_EVENTI_ID_MANIFESTAZIONE . " = m." . COL_MANIFESTAZIONI_ID . "
        JOIN " . TABLE_LOCATIONS . " l ON e." . COL_EVENTI_ID_LOCATION . " = l." . COL_LOCATIONS_ID . "
        WHERE e." . COL_EVENTI_NOME . " LIKE ?
           OR m." . COL_MANIFESTAZIONI_NOME . " LIKE ?
           OR l." . COL_LOCATIONS_NOME . " LIKE ?
        ORDER BY e." . COL_EVENTI_DATA . ", e." . COL_EVENTI_ORA_INIZIO . "
    ");
    $stmt->execute([$search, $search, $search]);
    return $stmt->fetchAll();
}

/**
 * Recupera eventi filtrati per categoria
 * Categorie: concerti, teatro, sport, eventi, famiglia
 * @param string $tipo Categoria da filtrare
 * @return array Eventi della categoria
 */
function getEventiByTipo(PDO $pdo, string $tipo): array
{
    $stmt = $pdo->prepare("
        SELECT e.*, e." . COL_EVENTI_ID . " AS id, COALESCE(m." . COL_MANIFESTAZIONI_NOME . ", 'indipendente') AS ManifestazioneName, e." . COL_EVENTI_CATEGORIA . ", l." . COL_LOCATIONS_NOME . " AS LocationName
        FROM " . TABLE_EVENTI . " e
        LEFT JOIN " . TABLE_MANIFESTAZIONI . " m ON e." . COL_EVENTI_ID_MANIFESTAZIONE . " = m." . COL_MANIFESTAZIONI_ID . "
        JOIN " . TABLE_LOCATIONS . " l ON e." . COL_EVENTI_ID_LOCATION . " = l." . COL_LOCATIONS_ID . "
        WHERE e." . COL_EVENTI_CATEGORIA . " = ? ORDER BY e." . COL_EVENTI_DATA . ", e." . COL_EVENTI_ORA_INIZIO . ";
    ");
    $stmt->execute([$tipo]);
    return $stmt->fetchAll();
}
