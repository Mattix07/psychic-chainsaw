<?php
/**
 * Model Settori
 * Gestisce i settori/tribune delle location
 */

require_once __DIR__ . '/../config/database_schema.php';
require_once __DIR__ . '/../lib/QueryBuilder.php';

/**
 * Recupera tutti i settori disponibili
 *
 * @param PDO $pdo Connessione database
 * @return array Lista di tutti i settori
 */
function getAllSettori(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT s.*, l." . COL_LOCATIONS_NOME . " as LocationNome
        FROM " . TABLE_SETTORI . " s
        LEFT JOIN " . TABLE_LOCATIONS . " l ON s." . COL_SETTORI_ID_LOCATION . " = l." . COL_LOCATIONS_ID . "
        ORDER BY l." . COL_LOCATIONS_NOME . ", s." . COL_SETTORI_NOME . "
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
        SELECT s.*, l." . COL_LOCATIONS_NOME . " as LocationNome
        FROM " . TABLE_SETTORI . " s
        LEFT JOIN " . TABLE_LOCATIONS . " l ON s." . COL_SETTORI_ID_LOCATION . " = l." . COL_LOCATIONS_ID . "
        WHERE s." . COL_SETTORI_ID . " = ?
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
        INSERT INTO " . TABLE_SETTORI . " (" . COL_SETTORI_NOME . ", " . COL_SETTORI_FILA . ", " . COL_SETTORI_POSTO . ", " . COL_SETTORI_ID_LOCATION . ", " . COL_SETTORI_MOLTIPLICATORE_PREZZO . ", " . COL_SETTORI_POSTI_DISPONIBILI . ")
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
        UPDATE " . TABLE_SETTORI . "
        SET " . COL_SETTORI_NOME . " = ?,
            " . COL_SETTORI_FILA . " = ?,
            " . COL_SETTORI_POSTO . " = ?,
            " . COL_SETTORI_ID_LOCATION . " = ?,
            " . COL_SETTORI_MOLTIPLICATORE_PREZZO . " = ?,
            " . COL_SETTORI_POSTI_DISPONIBILI . " = ?
        WHERE " . COL_SETTORI_ID . " = ?
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
    $stmt = $pdo->prepare("DELETE FROM " . TABLE_SETTORI . " WHERE " . COL_SETTORI_ID . " = ?");
    return $stmt->execute([$id]);
}
