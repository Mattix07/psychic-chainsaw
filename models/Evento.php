<?php
/**
 * Model Evento
 * Gestisce operazioni CRUD e query per gli eventi
 */

/**
 * Recupera tutti gli eventi con dati di manifestazione e location
 * @return array Lista eventi ordinati per data e ora
 */
function getAllEventi(PDO $pdo): array
{
    return $pdo->query("
        SELECT e.*, m.Nome as ManifestazioneName, l.Nome as LocationName
        FROM Eventi e
        JOIN Manifestazioni m ON e.idManifestazione = m.id
        JOIN Locations l ON e.idLocation = l.id
        ORDER BY e.Data, e.OraI
    ")->fetchAll();
}

/**
 * Recupera un evento specifico con tutti i dettagli
 * @return array|null Dati evento o null se non trovato
 */
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

/**
 * Recupera tutti gli eventi di una manifestazione
 * Utile per mostrare eventi correlati nella stessa manifestazione
 * @return array Lista eventi della manifestazione
 */
function getEventiByManifestazione(PDO $pdo, int $idManifestazione): array
{
    $stmt = $pdo->prepare("
        SELECT e.*, l.Nome as LocationName
        FROM Eventi e
        JOIN Locations l ON e.idLocation = l.id
        WHERE e.idManifestazione = ?
        ORDER BY e.Data, e.OraI
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
        SELECT e.Nome as eNome, e.OraI, e.OraF, e.Data, e.PrezzoNoMod, l.Nome as LocationName
        FROM Eventi e
        JOIN Manifestazioni m ON e.idManifestazione = m.id
        JOIN Locations l ON e.idLocation = l.id
        WHERE m.Nome = ?
        ORDER BY e.Data, e.OraI ASC
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
        SELECT e.*, m.Nome as ManifestazioneName, l.Nome as LocationName
        FROM Eventi e
        JOIN Manifestazioni m ON e.idManifestazione = m.id
        JOIN Locations l ON e.idLocation = l.id
        WHERE e.Data >= CURDATE()
        ORDER BY e.Data, e.OraI
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
        INSERT INTO Eventi (idManifestazione, idLocation, Nome, PrezzoNoMod, Data, OraI, OraF, Programma, Immagine, Categoria)
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
        $data['Categoria'] ?? 'famiglia'
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
        UPDATE Eventi SET
            idManifestazione = ?,
            idLocation = ?,
            Nome = ?,
            PrezzoNoMod = ?,
            Data = ?,
            OraI = ?,
            OraF = ?,
            Programma = ?,
            Categoria = ?
        WHERE id = ?
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
        $data['Categoria'] ?? 'famiglia',
        $id
    ]);
}

/**
 * Elimina un evento (i biglietti associati vengono eliminati in cascade)
 * @return bool Esito operazione
 */
function deleteEvento(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM Eventi WHERE id = ?");
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
        SELECT i.*, es.OraI, es.OraF
        FROM Intrattenitori i
        JOIN Esibizioni es ON i.id = es.idIntrattenitore
        WHERE es.idEvento = ?
        ORDER BY es.OraI
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
        SELECT e.*, e.Nome as eNome, e.id as id, m.Nome as ManifestazioneName, e.Categoria,
               l.Nome as LocationName
        FROM Eventi e
        JOIN Manifestazioni m ON e.idManifestazione = m.id
        JOIN Locations l ON e.idLocation = l.id
        WHERE e.Nome LIKE ?
           OR m.Nome LIKE ?
           OR l.Nome LIKE ?
        ORDER BY e.Data, e.OraI
    ");
    $stmt->execute([$search, $search, $search]);
    return $stmt->fetchAll();
}

/**
 * Recupera eventi filtrati per categoria
 * Categorie: concerti, teatro, sport, eventi
 * @param string $tipo Categoria da filtrare
 * @return array Eventi della categoria
 */
function getEventiByTipo(PDO $pdo, string $tipo): array
{
    $stmt = $pdo->prepare("
        SELECT e.*, e.id as id, m.Nome as ManifestazioneName, e.Categoria,
               l.Nome as LocationName
        FROM Eventi e
        JOIN Manifestazioni m ON e.idManifestazione = m.id
        JOIN Locations l ON e.idLocation = l.id
        WHERE e.Categoria = ?
        ORDER BY e.Data, e.OraI
    ");
    $stmt->execute([$tipo]);
    return $stmt->fetchAll();
}
