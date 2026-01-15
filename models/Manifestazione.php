<?php

/**
 * Model per la gestione delle Manifestazioni
 */

function getAllManifestazioni(PDO $pdo): array
{
    return $pdo->query("SELECT id, Nome FROM Manifestazioni ORDER BY Nome")->fetchAll();
}

function getManifestazioneById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT id, Nome FROM Manifestazioni WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getManifestazioneByNome(PDO $pdo, string $nome): ?array
{
    $stmt = $pdo->prepare("SELECT id, Nome FROM Manifestazioni WHERE Nome = ?");
    $stmt->execute([$nome]);
    return $stmt->fetch() ?: null;
}

function createManifestazione(PDO $pdo, string $nome): int
{
    $stmt = $pdo->prepare("INSERT INTO Manifestazioni (Nome) VALUES (?)");
    $stmt->execute([$nome]);
    return (int) $pdo->lastInsertId();
}

function updateManifestazione(PDO $pdo, int $id, string $nome): bool
{
    $stmt = $pdo->prepare("UPDATE Manifestazioni SET Nome = ? WHERE id = ?");
    return $stmt->execute([$nome, $id]);
}

function deleteManifestazione(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM Manifestazioni WHERE id = ?");
    return $stmt->execute([$id]);
}
