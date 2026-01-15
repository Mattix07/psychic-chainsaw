<?php

/**
 * Model per la gestione degli Utenti
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

function createUtente(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare("
        INSERT INTO Utenti (Nome, Cognome, Email)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([
        $data['Nome'],
        $data['Cognome'],
        $data['Email']
    ]);
    return (int) $pdo->lastInsertId();
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

function deleteUtente(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM Utenti WHERE id = ?");
    return $stmt->execute([$id]);
}

function getOrdiniUtente(PDO $pdo, int $idUtente): array
{
    $stmt = $pdo->prepare("
        SELECT o.*
        FROM Ordini o
        JOIN Utente_Ordini uo ON o.id = uo.idOrdine
        WHERE uo.idUtente = ?
        ORDER BY o.id DESC
    ");
    $stmt->execute([$idUtente]);
    return $stmt->fetchAll();
}

function getRecensioniUtente(PDO $pdo, int $idUtente): array
{
    $stmt = $pdo->prepare("
        SELECT r.*, e.Nome as EventoNome
        FROM Recensioni r
        JOIN Eventi e ON r.idEvento = e.id
        WHERE r.idUtente = ?
        ORDER BY e.Data DESC
    ");
    $stmt->execute([$idUtente]);
    return $stmt->fetchAll();
}
