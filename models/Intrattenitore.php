<?php

/**
 * Model per la gestione degli Intrattenitori
 */

function getAllIntrattenitori(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM Intrattenitori ORDER BY Nome")->fetchAll();
}

function getIntrattenitoreById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM Intrattenitori WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getIntrattenitoriByMestiere(PDO $pdo, string $mestiere): array
{
    $stmt = $pdo->prepare("SELECT * FROM Intrattenitori WHERE Mestiere = ? ORDER BY Nome");
    $stmt->execute([$mestiere]);
    return $stmt->fetchAll();
}

function createIntrattenitore(PDO $pdo, string $nome, string $mestiere): int
{
    $stmt = $pdo->prepare("INSERT INTO Intrattenitori (Nome, Mestiere) VALUES (?, ?)");
    $stmt->execute([$nome, $mestiere]);
    return (int) $pdo->lastInsertId();
}

function updateIntrattenitore(PDO $pdo, int $id, string $nome, string $mestiere): bool
{
    $stmt = $pdo->prepare("UPDATE Intrattenitori SET Nome = ?, Mestiere = ? WHERE id = ?");
    return $stmt->execute([$nome, $mestiere, $id]);
}

function deleteIntrattenitore(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM Intrattenitori WHERE id = ?");
    return $stmt->execute([$id]);
}

function getEventiIntrattenitore(PDO $pdo, int $idIntrattenitore): array
{
    $stmt = $pdo->prepare("
        SELECT e.*, es.OraI as EsibOraI, es.OraF as EsibOraF, m.Nome as ManifestazioneName
        FROM Eventi e
        JOIN Esibizioni es ON e.id = es.idEvento
        JOIN Manifestazioni m ON e.idManifestazione = m.id
        WHERE es.idIntrattenitore = ?
        ORDER BY e.Data, es.OraI
    ");
    $stmt->execute([$idIntrattenitore]);
    return $stmt->fetchAll();
}

function addEsibizione(PDO $pdo, int $idEvento, int $idIntrattenitore, string $oraI, string $oraF): bool
{
    // Prima assicuriamoci che il tempo esista
    $stmt = $pdo->prepare("INSERT IGNORE INTO Tempi (OraI, OraF) VALUES (?, ?)");
    $stmt->execute([$oraI, $oraF]);

    // Poi creiamo l'esibizione
    $stmt = $pdo->prepare("
        INSERT INTO Esibizioni (idEvento, idIntrattenitore, OraI, OraF)
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([$idEvento, $idIntrattenitore, $oraI, $oraF]);
}

function removeEsibizione(PDO $pdo, int $idEvento, int $idIntrattenitore, string $oraI, string $oraF): bool
{
    $stmt = $pdo->prepare("
        DELETE FROM Esibizioni
        WHERE idEvento = ? AND idIntrattenitore = ? AND OraI = ? AND OraF = ?
    ");
    return $stmt->execute([$idEvento, $idIntrattenitore, $oraI, $oraF]);
}
