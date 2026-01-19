<?php

/**
 * Model per la gestione degli Ordini
 */

function getAllOrdini(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM Ordini ORDER BY id DESC")->fetchAll();
}

function getOrdineById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM Ordini WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getOrdineCompleto(PDO $pdo, int $id): ?array
{
    $ordine = getOrdineById($pdo, $id);
    if ($ordine) {
        require_once __DIR__ . '/Biglietto.php';
        $ordine['biglietti'] = getBigliettiByOrdine($pdo, $id);
        $ordine['utente'] = getUtenteByOrdine($pdo, $id);
    }
    return $ordine;
}

function getUtenteByOrdine(PDO $pdo, int $idOrdine): ?array
{
    $stmt = $pdo->prepare("
        SELECT u.*
        FROM Utenti u
        JOIN Utente_Ordini uo ON u.id = uo.idUtente
        WHERE uo.idOrdine = ?
    ");
    $stmt->execute([$idOrdine]);
    return $stmt->fetch() ?: null;
}

function createOrdine(PDO $pdo, string $metodo): int
{
    $stmt = $pdo->prepare("INSERT INTO Ordini (Metodo) VALUES (?)");
    $stmt->execute([$metodo]);
    return (int) $pdo->lastInsertId();
}

function associaOrdineUtente(PDO $pdo, int $idOrdine, int $idUtente): bool
{
    $stmt = $pdo->prepare("
        INSERT INTO Utente_Ordini (idUtente, idOrdine)
        VALUES (?, ?)
    ");
    return $stmt->execute([$idUtente, $idOrdine]);
}

function associaOrdineBiglietto(PDO $pdo, int $idOrdine, int $idBiglietto): bool
{
    $stmt = $pdo->prepare("
        INSERT INTO Ordine_Biglietti (idOrdine, idBiglietto)
        VALUES (?, ?)
    ");
    return $stmt->execute([$idOrdine, $idBiglietto]);
}

function deleteOrdine(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM Ordini WHERE id = ?");
    return $stmt->execute([$id]);
}

function calcolaTotaleOrdine(PDO $pdo, int $idOrdine): float
{
    $stmt = $pdo->prepare("
        SELECT SUM((e.PrezzoNoMod + t.ModificatorePrezzo) * COALESCE(s.MoltiplicatorePrezzo, 1)) as totale
        FROM Ordine_Biglietti ob
        JOIN Biglietti b ON ob.idBiglietto = b.id
        JOIN Eventi e ON b.idEvento = e.id
        JOIN Tipo t ON b.idClasse = t.nome
        LEFT JOIN Settore_Biglietti sb ON b.id = sb.idBiglietto
        LEFT JOIN Settori s ON sb.idSettore = s.id
        WHERE ob.idOrdine = ?
    ");
    $stmt->execute([$idOrdine]);
    $result = $stmt->fetch();
    return $result ? (float) $result['totale'] : 0.0;
}

function getOrdiniByUtente(PDO $pdo, int $idUtente): array
{
    $stmt = $pdo->prepare("
        SELECT o.*, COUNT(ob.idBiglietto) as num_biglietti
        FROM Ordini o
        JOIN Utente_Ordini uo ON o.id = uo.idOrdine
        LEFT JOIN Ordine_Biglietti ob ON o.id = ob.idOrdine
        WHERE uo.idUtente = ?
        GROUP BY o.id
        ORDER BY o.id DESC
    ");
    $stmt->execute([$idUtente]);
    return $stmt->fetchAll();
}

function isOrdineOfUtente(PDO $pdo, int $idOrdine, int $idUtente): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM Utente_Ordini
        WHERE idOrdine = ? AND idUtente = ?
    ");
    $stmt->execute([$idOrdine, $idUtente]);
    $result = $stmt->fetch();
    return $result && $result['count'] > 0;
}
