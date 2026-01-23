<?php
/**
 * Model Settori
 * Gestisce i settori/tribune delle location
 */

/**
 * Recupera tutti i settori disponibili
 *
 * @param PDO $pdo Connessione database
 * @return array Lista di tutti i settori
 */
function getAllSettori(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT s.*, l.Nome as LocationNome
        FROM Settori s
        LEFT JOIN Locations l ON s.idLocation = l.id
        ORDER BY l.Nome, s.Nome
    ");
    return $stmt->fetchAll();
}

/**
 * Recupera un settore per ID
 *
 * @param PDO $pdo Connessione database
 * @param int $id ID del settore
 * @return array|null Dati del settore o null se non trovato
 */
function getSettoreById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT s.*, l.Nome as LocationNome
        FROM Settori s
        LEFT JOIN Locations l ON s.idLocation = l.id
        WHERE s.id = ?
    ");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Crea un nuovo settore
 *
 * @param PDO $pdo Connessione database
 * @param array $data Dati del settore
 * @return int ID del settore creato
 */
function createSettore(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare("
        INSERT INTO Settori (Nome, Fila, Posto, idLocation, MoltiplicatorePrezzo, PostiDisponibili)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['Nome'] ?? '',
        $data['Fila'] ?? '',
        $data['Posto'] ?? 0,
        $data['idLocation'] ?? 0,
        $data['MoltiplicatorePrezzo'] ?? 1.0,
        $data['PostiDisponibili'] ?? 0
    ]);
    return (int) $pdo->lastInsertId();
}

/**
 * Aggiorna un settore esistente
 *
 * @param PDO $pdo Connessione database
 * @param int $id ID del settore
 * @param array $data Nuovi dati
 * @return bool True se aggiornamento riuscito
 */
function updateSettore(PDO $pdo, int $id, array $data): bool
{
    $stmt = $pdo->prepare("
        UPDATE Settori
        SET Nome = ?,
            Fila = ?,
            Posto = ?,
            idLocation = ?,
            MoltiplicatorePrezzo = ?,
            PostiDisponibili = ?
        WHERE id = ?
    ");
    return $stmt->execute([
        $data['Nome'] ?? '',
        $data['Fila'] ?? '',
        $data['Posto'] ?? 0,
        $data['idLocation'] ?? 0,
        $data['MoltiplicatorePrezzo'] ?? 1.0,
        $data['PostiDisponibili'] ?? 0,
        $id
    ]);
}

/**
 * Elimina un settore
 *
 * @param PDO $pdo Connessione database
 * @param int $id ID del settore
 * @return bool True se eliminazione riuscita
 */
function deleteSettore(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM Settori WHERE id = ?");
    return $stmt->execute([$id]);
}
