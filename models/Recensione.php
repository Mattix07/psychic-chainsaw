<?php

/**
 * Model per la gestione delle Recensioni
 */

function getRecensioniByEvento(PDO $pdo, int $idEvento): array
{
    $stmt = $pdo->prepare("
        SELECT r.*, u.Nome, u.Cognome
        FROM Recensioni r
        JOIN Utenti u ON r.idUtente = u.id
        WHERE r.idEvento = ?
        ORDER BY r.idEvento
    ");
    $stmt->execute([$idEvento]);
    return $stmt->fetchAll();
}

function getMediaVotiEvento(PDO $pdo, int $idEvento): ?float
{
    $stmt = $pdo->prepare("
        SELECT AVG(Voto) as media
        FROM Recensioni
        WHERE idEvento = ?
    ");
    $stmt->execute([$idEvento]);
    $result = $stmt->fetch();
    return $result && $result['media'] ? round((float) $result['media'], 1) : null;
}

function getRecensione(PDO $pdo, int $idEvento, int $idUtente): ?array
{
    $stmt = $pdo->prepare("
        SELECT * FROM Recensioni
        WHERE idEvento = ? AND idUtente = ?
    ");
    $stmt->execute([$idEvento, $idUtente]);
    return $stmt->fetch() ?: null;
}

function createRecensione(PDO $pdo, int $idEvento, int $idUtente, int $voto, ?string $messaggio = null): bool
{
    $stmt = $pdo->prepare("
        INSERT INTO Recensioni (idEvento, idUtente, Voto, Messaggio)
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([$idEvento, $idUtente, $voto, $messaggio]);
}

function updateRecensione(PDO $pdo, int $idEvento, int $idUtente, int $voto, ?string $messaggio = null): bool
{
    $stmt = $pdo->prepare("
        UPDATE Recensioni
        SET Voto = ?, Messaggio = ?
        WHERE idEvento = ? AND idUtente = ?
    ");
    return $stmt->execute([$voto, $messaggio, $idEvento, $idUtente]);
}

function deleteRecensione(PDO $pdo, int $idEvento, int $idUtente): bool
{
    $stmt = $pdo->prepare("
        DELETE FROM Recensioni
        WHERE idEvento = ? AND idUtente = ?
    ");
    return $stmt->execute([$idEvento, $idUtente]);
}

function hasRecensito(PDO $pdo, int $idEvento, int $idUtente): bool
{
    return getRecensione($pdo, $idEvento, $idUtente) !== null;
}

/**
 * Verifica se l'utente ha acquistato almeno un biglietto per l'evento
 */
function hasAcquistatoBiglietto(PDO $pdo, int $idEvento, int $idUtente): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM Ordini o
        JOIN OrdineDettagli od ON o.id = od.idOrdine
        JOIN Biglietti b ON od.idBiglietto = b.id
        WHERE o.idUtente = ?
        AND b.idEvento = ?
        AND o.Stato IN ('completato', 'confermato', 'pagato')
    ");
    $stmt->execute([$idUtente, $idEvento]);
    $result = $stmt->fetch();
    return $result && (int)$result['count'] > 0;
}

/**
 * Verifica se l'utente può recensire l'evento
 * L'utente deve aver acquistato un biglietto e non aver già recensito
 */
function canRecensire(PDO $pdo, int $idEvento, int $idUtente): bool
{
    return hasAcquistatoBiglietto($pdo, $idEvento, $idUtente) && !hasRecensito($pdo, $idEvento, $idUtente);
}
