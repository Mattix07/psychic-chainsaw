<?php

/**
 * Model per la gestione dei Biglietti
 */

function getAllTipiBiglietto(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM Tipo ORDER BY ModificatorePrezzo")->fetchAll();
}

function getTipoByNome(PDO $pdo, string $nome): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM Tipo WHERE nome = ?");
    $stmt->execute([$nome]);
    return $stmt->fetch() ?: null;
}

function getBigliettoById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT b.*, e.Nome as EventoNome, t.ModificatorePrezzo
        FROM Biglietti b
        JOIN Eventi e ON b.idEvento = e.id
        JOIN Tipo t ON b.idClasse = t.nome
        WHERE b.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getBigliettiByEvento(PDO $pdo, int $idEvento): array
{
    $stmt = $pdo->prepare("
        SELECT b.*, t.ModificatorePrezzo, sb.Fila, sb.Numero, s.id as idSettore
        FROM Biglietti b
        JOIN Tipo t ON b.idClasse = t.nome
        LEFT JOIN Settore_Biglietti sb ON b.id = sb.idBiglietto
        LEFT JOIN Settori s ON sb.idSettore = s.id
        WHERE b.idEvento = ?
        ORDER BY b.id
    ");
    $stmt->execute([$idEvento]);
    return $stmt->fetchAll();
}

function getBigliettiByOrdine(PDO $pdo, int $idOrdine): array
{
    $stmt = $pdo->prepare("
        SELECT b.*, e.Nome as EventoNome, e.Data, e.OraI, t.ModificatorePrezzo
        FROM Biglietti b
        JOIN Ordine_Biglietti ob ON b.id = ob.idBiglietto
        JOIN Eventi e ON b.idEvento = e.id
        JOIN Tipo t ON b.idClasse = t.nome
        WHERE ob.idOrdine = ?
    ");
    $stmt->execute([$idOrdine]);
    return $stmt->fetchAll();
}

function createBiglietto(PDO $pdo, array $data): int
{
    $qrcode = generateQRCode();

    $stmt = $pdo->prepare("
        INSERT INTO Biglietti (idEvento, idClasse, Nome, Cognome, Sesso, QRcode)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['idEvento'],
        $data['idClasse'],
        $data['Nome'],
        $data['Cognome'],
        $data['Sesso'],
        $qrcode
    ]);
    return (int) $pdo->lastInsertId();
}

function assegnaPosto(PDO $pdo, int $idBiglietto, int $idSettore, string $fila, int $numero): bool
{
    $stmt = $pdo->prepare("
        INSERT INTO Settore_Biglietti (idSettore, idBiglietto, Fila, Numero)
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([$idSettore, $idBiglietto, $fila, $numero]);
}

function validaBiglietto(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("UPDATE Biglietti SET `Check` = TRUE WHERE id = ?");
    return $stmt->execute([$id]);
}

function isBigliettoValidato(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("SELECT `Check` FROM Biglietti WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    return $result && $result['Check'];
}

function deleteBiglietto(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM Biglietti WHERE id = ?");
    return $stmt->execute([$id]);
}

function generateQRCode(): string
{
    return bin2hex(random_bytes(32));
}

function calcolaPrezzoFinale(PDO $pdo, int $idEvento, string $idClasse, int $idSettore): float
{
    $stmt = $pdo->prepare("
        SELECT e.PrezzoNoMod, t.ModificatorePrezzo, s.MoltiplicatorePrezzo
        FROM Eventi e
        CROSS JOIN Tipo t
        CROSS JOIN Settori s
        WHERE e.id = ? AND t.nome = ? AND s.id = ?
    ");
    $stmt->execute([$idEvento, $idClasse, $idSettore]);
    $result = $stmt->fetch();

    if (!$result) {
        return 0.0;
    }

    return ($result['PrezzoNoMod'] + $result['ModificatorePrezzo']) * $result['MoltiplicatorePrezzo'];
}
