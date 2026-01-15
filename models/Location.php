<?php

/**
 * Model per la gestione delle Locations
 */

function getAllLocations(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM Locations ORDER BY Nome")->fetchAll();
}

function getLocationById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM Locations WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getLocationWithSettori(PDO $pdo, int $id): ?array
{
    $location = getLocationById($pdo, $id);
    if ($location) {
        $location['settori'] = getSettoriByLocation($pdo, $id);
    }
    return $location;
}

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

function createLocation(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare("
        INSERT INTO Locations (Nome, Stato, Regione, CAP, Città, civico)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['Nome'],
        $data['Stato'],
        $data['Regione'],
        $data['CAP'],
        $data['Città'],
        $data['civico'] ?? null
    ]);
    return (int) $pdo->lastInsertId();
}

function updateLocation(PDO $pdo, int $id, array $data): bool
{
    $stmt = $pdo->prepare("
        UPDATE Locations SET
            Nome = ?,
            Stato = ?,
            Regione = ?,
            CAP = ?,
            Città = ?,
            civico = ?
        WHERE id = ?
    ");
    return $stmt->execute([
        $data['Nome'],
        $data['Stato'],
        $data['Regione'],
        $data['CAP'],
        $data['Città'],
        $data['civico'] ?? null,
        $id
    ]);
}

function deleteLocation(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM Locations WHERE id = ?");
    return $stmt->execute([$id]);
}

function getPostiDisponibiliSettore(PDO $pdo, int $idSettore, int $idEvento): int
{
    $stmt = $pdo->prepare("
        SELECT s.Posti - COUNT(sb.idBiglietto) as disponibili
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
