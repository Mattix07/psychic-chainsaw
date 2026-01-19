<?php
/**
 * Model Intrattenitore
 * Gestisce artisti, gruppi e performer che si esibiscono agli eventi
 *
 * Gli intrattenitori sono collegati agli eventi tramite la tabella Esibizioni,
 * che permette di definire orari specifici per ogni performance.
 */

/**
 * Recupera tutti gli intrattenitori ordinati alfabeticamente
 *
 * @return array Lista completa intrattenitori
 */
function getAllIntrattenitori(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM Intrattenitori ORDER BY Nome")->fetchAll();
}

/**
 * Recupera un intrattenitore tramite ID
 *
 * @return array|null Dati intrattenitore o null se non trovato
 */
function getIntrattenitoreById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM Intrattenitori WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Recupera intrattenitori filtrati per mestiere
 * Utile per cercare artisti per categoria (cantante, band, attore, ecc.)
 *
 * @param string $mestiere Tipo di professione
 * @return array Lista intrattenitori filtrati
 */
function getIntrattenitoriByMestiere(PDO $pdo, string $mestiere): array
{
    $stmt = $pdo->prepare("SELECT * FROM Intrattenitori WHERE Mestiere = ? ORDER BY Nome");
    $stmt->execute([$mestiere]);
    return $stmt->fetchAll();
}

/**
 * Crea un nuovo intrattenitore
 *
 * @param string $nome Nome dell'artista o gruppo
 * @param string $mestiere Professione o categoria
 * @return int ID del nuovo intrattenitore
 */
function createIntrattenitore(PDO $pdo, string $nome, string $mestiere): int
{
    $stmt = $pdo->prepare("INSERT INTO Intrattenitori (Nome, Mestiere) VALUES (?, ?)");
    $stmt->execute([$nome, $mestiere]);
    return (int) $pdo->lastInsertId();
}

/**
 * Aggiorna i dati di un intrattenitore
 *
 * @return bool Esito operazione
 */
function updateIntrattenitore(PDO $pdo, int $id, string $nome, string $mestiere): bool
{
    $stmt = $pdo->prepare("UPDATE Intrattenitori SET Nome = ?, Mestiere = ? WHERE id = ?");
    return $stmt->execute([$nome, $mestiere, $id]);
}

/**
 * Elimina un intrattenitore
 * Le esibizioni associate vengono eliminate in cascade
 *
 * @return bool Esito operazione
 */
function deleteIntrattenitore(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM Intrattenitori WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Recupera tutti gli eventi in cui si esibisce un intrattenitore
 * Include orari specifici dell'esibizione per ogni evento
 *
 * @return array Lista eventi con dettagli esibizione
 */
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

/**
 * Aggiunge un'esibizione di un intrattenitore a un evento
 * Crea prima il record tempo se non esiste (INSERT IGNORE)
 *
 * @param string $oraI Ora inizio esibizione (formato HH:MM:SS)
 * @param string $oraF Ora fine esibizione (formato HH:MM:SS)
 * @return bool Esito operazione
 */
function addEsibizione(PDO $pdo, int $idEvento, int $idIntrattenitore, string $oraI, string $oraF): bool
{
    // Inserisce il tempo nella tabella Tempi se non esiste
    $stmt = $pdo->prepare("INSERT IGNORE INTO Tempi (OraI, OraF) VALUES (?, ?)");
    $stmt->execute([$oraI, $oraF]);

    // Crea l'associazione evento-intrattenitore con orari
    $stmt = $pdo->prepare("
        INSERT INTO Esibizioni (idEvento, idIntrattenitore, OraI, OraF)
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([$idEvento, $idIntrattenitore, $oraI, $oraF]);
}

/**
 * Rimuove un'esibizione specifica
 * Richiede tutti i parametri per identificare univocamente l'esibizione
 *
 * @return bool Esito operazione
 */
function removeEsibizione(PDO $pdo, int $idEvento, int $idIntrattenitore, string $oraI, string $oraF): bool
{
    $stmt = $pdo->prepare("
        DELETE FROM Esibizioni
        WHERE idEvento = ? AND idIntrattenitore = ? AND OraI = ? AND OraF = ?
    ");
    return $stmt->execute([$idEvento, $idIntrattenitore, $oraI, $oraF]);
}
