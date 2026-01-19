<?php

/**
 * Model per la gestione degli Eventi
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

function createEvento(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare("
        INSERT INTO Eventi (idManifestazione, idLocation, Nome, PrezzoNoMod, Data, OraI, OraF, Programma, Locandina)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
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
        $data['Locandina'] ?? null
    ]);
    return (int) $pdo->lastInsertId();
}

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
            Programma = ?
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
        $id
    ]);
}

function deleteEvento(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM Eventi WHERE id = ?");
    return $stmt->execute([$id]);
}

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

function searchEventiByQuery(PDO $pdo, string $query): array
{
    $search = "%{$query}%";
    $stmt = $pdo->prepare("
        SELECT e.*, e.id as id, m.Nome as ManifestazioneName, e.Categoria,
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
