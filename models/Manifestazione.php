<?php
/**
 * Model Manifestazione
 * Gestisce i contenitori logici che raggruppano eventi correlati
 *
 * Una manifestazione rappresenta un festival, una rassegna o una serie
 * di eventi collegati (es. "Festival della Musica 2025").
 * Ogni evento appartiene a una manifestazione.
 */

require_once __DIR__ . '/../config/database_schema.php';
require_once __DIR__ . '/../lib/QueryBuilder.php';

/**
 * Recupera tutte le manifestazioni ordinate alfabeticamente
 *
 * @return array Lista manifestazioni con tutti i campi
 */
function getAllManifestazioni(PDO $pdo): array
{
    return table($pdo, TABLE_MANIFESTAZIONI)
        ->orderBy(COL_MANIFESTAZIONI_NOME)
        ->get();
}

/**
 * Recupera una manifestazione tramite ID
 *
 * @return array|null Dati manifestazione o null se non trovata
 */
function getManifestazioneById(PDO $pdo, int $id): ?array
{
    return table($pdo, TABLE_MANIFESTAZIONI)
        ->select([COL_MANIFESTAZIONI_ID, COL_MANIFESTAZIONI_NOME])
        ->where(COL_MANIFESTAZIONI_ID, $id)
        ->first();
}

/**
 * Cerca una manifestazione per nome esatto
 * Utile per verificare duplicati prima della creazione
 *
 * @return array|null Dati manifestazione o null se non trovata
 */
function getManifestazioneByNome(PDO $pdo, string $nome): ?array
{
    return table($pdo, TABLE_MANIFESTAZIONI)
        ->select([COL_MANIFESTAZIONI_ID, COL_MANIFESTAZIONI_NOME])
        ->where(COL_MANIFESTAZIONI_NOME, $nome)
        ->first();
}

/**
 * Crea una nuova manifestazione
 *
 * @param string $nome Nome della manifestazione
 * @return int ID della nuova manifestazione
 */
function createManifestazione(PDO $pdo, $data): int
{
    // Accetta sia stringa che array per retro-compatibilità
    if (is_string($data)) {
        $nome = $data;
        $descrizione = null;
        $dataInizio = null;
        $dataFine = null;
    } else {
        $nome = $data['Nome'];
        $descrizione = $data['Descrizione'] ?? null;
        $dataInizio = $data['DataInizio'] ?? null;
        $dataFine = $data['DataFine'] ?? null;
    }

    $stmt = $pdo->prepare("
        INSERT INTO " . TABLE_MANIFESTAZIONI . " (" . COL_MANIFESTAZIONI_NOME . ", " . COL_MANIFESTAZIONI_DESCRIZIONE . ", " . COL_MANIFESTAZIONI_DATA_INIZIO . ", " . COL_MANIFESTAZIONI_DATA_FINE . ")
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$nome, $descrizione, $dataInizio, $dataFine]);
    return (int) $pdo->lastInsertId();
}

/**
 * Aggiorna il nome di una manifestazione
 *
 * @return bool Esito operazione
 */
function updateManifestazione(PDO $pdo, int $id, $data): bool
{
    // Accetta sia stringa che array per retro-compatibilità
    if (is_string($data)) {
        $nome = $data;
        $descrizione = null;
        $dataInizio = null;
        $dataFine = null;
    } else {
        $nome = $data['Nome'];
        $descrizione = $data['Descrizione'] ?? null;
        $dataInizio = $data['DataInizio'] ?? null;
        $dataFine = $data['DataFine'] ?? null;
    }

    $stmt = $pdo->prepare("
        UPDATE " . TABLE_MANIFESTAZIONI . " SET
            " . COL_MANIFESTAZIONI_NOME . " = ?,
            " . COL_MANIFESTAZIONI_DESCRIZIONE . " = ?,
            " . COL_MANIFESTAZIONI_DATA_INIZIO . " = ?,
            " . COL_MANIFESTAZIONI_DATA_FINE . " = ?
        WHERE " . COL_MANIFESTAZIONI_ID . " = ?
    ");
    return $stmt->execute([$nome, $descrizione, $dataInizio, $dataFine, $id]);
}

/**
 * Elimina una manifestazione
 * Gli eventi associati devono essere gestiti prima dell'eliminazione
 *
 * @return bool Esito operazione
 */
function deleteManifestazione(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM " . TABLE_MANIFESTAZIONI . " WHERE " . COL_MANIFESTAZIONI_ID . " = ?");
    return $stmt->execute([$id]);
}

/**
 * Alias per compatibilità con il controller
 */
function deleteManifestazioneById(PDO $pdo, int $id): bool
{
    return deleteManifestazione($pdo, $id);
}

/**
 * Recupera le manifestazioni create da un utente specifico
 * Solo per promoter
 *
 * @return array Lista manifestazioni create dall'utente
 */
function getManifestazioniByCreator(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT m.*
        FROM " . TABLE_MANIFESTAZIONI . " m
        INNER JOIN " . TABLE_CREATORI_MANIFESTAZIONI . " cm ON m." . COL_MANIFESTAZIONI_ID . " = cm.idManifestazione
        WHERE cm.idUtente = ?
        ORDER BY m." . COL_MANIFESTAZIONI_DATA_INIZIO . " DESC, m." . COL_MANIFESTAZIONI_NOME . "
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}
