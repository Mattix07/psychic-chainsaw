<?php
/**
 * Model EventoSettori
 * Gestisce i settori disponibili per ogni evento
 */

require_once __DIR__ . '/../config/database_schema.php';
require_once __DIR__ . '/../lib/QueryBuilder.php';

/**
 * Associa settori a un evento
 * @param array $settoriIds Array di ID settori da associare
 */
function setEventoSettori(PDO $pdo, int $eventoId, array $settoriIds): bool
{
    try {
        $pdo->beginTransaction();

        // Rimuovi associazioni esistenti
        $stmt = $pdo->prepare("DELETE FROM " . TABLE_EVENTI_SETTORI . " WHERE idEvento = ?");
        $stmt->execute([$eventoId]);

        // Aggiungi nuove associazioni
        if (!empty($settoriIds)) {
            $stmt = $pdo->prepare("INSERT INTO " . TABLE_EVENTI_SETTORI . " (idEvento, idSettore) VALUES (?, ?)");
            foreach ($settoriIds as $settoreId) {
                $stmt->execute([$eventoId, $settoreId]);
            }
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Errore setEventoSettori: " . $e->getMessage());
        return false;
    }
}

/**
 * Ottieni i settori disponibili per un evento
 */
function getEventoSettori(PDO $pdo, int $eventoId): array
{
    $stmt = $pdo->prepare("
        SELECT s." . COL_SETTORI_ID . ", s." . COL_SETTORI_NOME . ", s." . COL_SETTORI_POSTI_DISPONIBILI . ", s." . COL_SETTORI_MOLTIPLICATORE_PREZZO . "
        FROM " . TABLE_SETTORI . " s
        JOIN " . TABLE_EVENTI_SETTORI . " es ON s." . COL_SETTORI_ID . " = es.idSettore
        WHERE es.idEvento = ?
        ORDER BY s." . COL_SETTORI_MOLTIPLICATORE_PREZZO . "
    ");
    $stmt->execute([$eventoId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Calcola il numero massimo di biglietti disponibili per un evento
 * in base ai settori selezionati
 */
function calcolaBigliettiDisponibili(PDO $pdo, int $eventoId): int
{
    $stmt = $pdo->prepare("
        SELECT SUM(s." . COL_SETTORI_POSTI_DISPONIBILI . ") as totale
        FROM " . TABLE_SETTORI . " s
        JOIN " . TABLE_EVENTI_SETTORI . " es ON s." . COL_SETTORI_ID . " = es.idSettore
        WHERE es.idEvento = ?
    ");
    $stmt->execute([$eventoId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int)($result['totale'] ?? 0);
}

/**
 * Verifica se un settore è disponibile per un evento
 */
function isSettoreDisponibilePerEvento(PDO $pdo, int $eventoId, int $settoreId): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM " . TABLE_EVENTI_SETTORI . "
        WHERE idEvento = ? AND idSettore = ?
    ");
    $stmt->execute([$eventoId, $settoreId]);
    return $stmt->fetchColumn() > 0;
}
