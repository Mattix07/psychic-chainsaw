<?php
/**
 * Model EventoSettori
 * Gestisce i settori disponibili per ogni evento
 */

/**
 * Associa settori a un evento
 * @param array $settoriIds Array di ID settori da associare
 */
function setEventoSettori(PDO $pdo, int $eventoId, array $settoriIds): bool
{
    try {
        $pdo->beginTransaction();

        // Rimuovi associazioni esistenti
        $stmt = $pdo->prepare("DELETE FROM EventiSettori WHERE idEvento = ?");
        $stmt->execute([$eventoId]);

        // Aggiungi nuove associazioni
        if (!empty($settoriIds)) {
            $stmt = $pdo->prepare("INSERT INTO EventiSettori (idEvento, idSettore) VALUES (?, ?)");
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
        SELECT s.id, s.Nome, s.PostiDisponibili, s.MoltiplicatorePrezzo
        FROM Settori s
        JOIN EventiSettori es ON s.id = es.idSettore
        WHERE es.idEvento = ?
        ORDER BY s.MoltiplicatorePrezzo
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
        SELECT SUM(s.PostiDisponibili) as totale
        FROM Settori s
        JOIN EventiSettori es ON s.id = es.idSettore
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
        SELECT COUNT(*) FROM EventiSettori
        WHERE idEvento = ? AND idSettore = ?
    ");
    $stmt->execute([$eventoId, $settoreId]);
    return $stmt->fetchColumn() > 0;
}
