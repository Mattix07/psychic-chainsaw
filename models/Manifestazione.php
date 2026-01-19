<?php
/**
 * Model Manifestazione
 * Gestisce i contenitori logici che raggruppano eventi correlati
 *
 * Una manifestazione rappresenta un festival, una rassegna o una serie
 * di eventi collegati (es. "Festival della Musica 2025").
 * Ogni evento appartiene a una manifestazione.
 */

/**
 * Recupera tutte le manifestazioni ordinate alfabeticamente
 *
 * @return array Lista manifestazioni con id e nome
 */
function getAllManifestazioni(PDO $pdo): array
{
    return $pdo->query("SELECT id, Nome FROM Manifestazioni ORDER BY Nome")->fetchAll();
}

/**
 * Recupera una manifestazione tramite ID
 *
 * @return array|null Dati manifestazione o null se non trovata
 */
function getManifestazioneById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT id, Nome FROM Manifestazioni WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Cerca una manifestazione per nome esatto
 * Utile per verificare duplicati prima della creazione
 *
 * @return array|null Dati manifestazione o null se non trovata
 */
function getManifestazioneByNome(PDO $pdo, string $nome): ?array
{
    $stmt = $pdo->prepare("SELECT id, Nome FROM Manifestazioni WHERE Nome = ?");
    $stmt->execute([$nome]);
    return $stmt->fetch() ?: null;
}

/**
 * Crea una nuova manifestazione
 *
 * @param string $nome Nome della manifestazione
 * @return int ID della nuova manifestazione
 */
function createManifestazione(PDO $pdo, string $nome): int
{
    $stmt = $pdo->prepare("INSERT INTO Manifestazioni (Nome) VALUES (?)");
    $stmt->execute([$nome]);
    return (int) $pdo->lastInsertId();
}

/**
 * Aggiorna il nome di una manifestazione
 *
 * @return bool Esito operazione
 */
function updateManifestazione(PDO $pdo, int $id, string $nome): bool
{
    $stmt = $pdo->prepare("UPDATE Manifestazioni SET Nome = ? WHERE id = ?");
    return $stmt->execute([$nome, $id]);
}

/**
 * Elimina una manifestazione
 * Gli eventi associati devono essere gestiti prima dell'eliminazione
 *
 * @return bool Esito operazione
 */
function deleteManifestazione(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM Manifestazioni WHERE id = ?");
    return $stmt->execute([$id]);
}
