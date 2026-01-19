<?php
/**
 * Model Ordine
 * Gestisce le transazioni di acquisto biglietti
 *
 * Un ordine raggruppa uno o piu biglietti acquistati in una singola transazione.
 * E collegato all'utente tramite la tabella ponte Utente_Ordini e ai biglietti
 * tramite Ordine_Biglietti.
 */

/**
 * Recupera tutti gli ordini ordinati dal piu recente
 *
 * @return array Lista completa ordini
 */
function getAllOrdini(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM Ordini ORDER BY id DESC")->fetchAll();
}

/**
 * Recupera un ordine tramite ID
 *
 * @return array|null Dati ordine o null se non trovato
 */
function getOrdineById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM Ordini WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Recupera un ordine con tutti i dettagli associati
 * Include lista biglietti e dati utente acquirente
 *
 * @return array|null Ordine arricchito o null se non trovato
 */
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

/**
 * Recupera l'utente associato a un ordine
 * Utilizza la tabella ponte Utente_Ordini
 *
 * @return array|null Dati utente o null
 */
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

/**
 * Crea un nuovo ordine
 * L'ordine viene creato vuoto, i biglietti vanno associati separatamente
 *
 * @param string $metodo Metodo di pagamento utilizzato
 * @return int ID del nuovo ordine
 */
function createOrdine(PDO $pdo, string $metodo): int
{
    $stmt = $pdo->prepare("INSERT INTO Ordini (Metodo) VALUES (?)");
    $stmt->execute([$metodo]);
    return (int) $pdo->lastInsertId();
}

/**
 * Associa un ordine a un utente
 * Crea il record nella tabella ponte Utente_Ordini
 *
 * @return bool Esito operazione
 */
function associaOrdineUtente(PDO $pdo, int $idOrdine, int $idUtente): bool
{
    $stmt = $pdo->prepare("
        INSERT INTO Utente_Ordini (idUtente, idOrdine)
        VALUES (?, ?)
    ");
    return $stmt->execute([$idUtente, $idOrdine]);
}

/**
 * Associa un biglietto a un ordine
 * Crea il record nella tabella ponte Ordine_Biglietti
 *
 * @return bool Esito operazione
 */
function associaOrdineBiglietto(PDO $pdo, int $idOrdine, int $idBiglietto): bool
{
    $stmt = $pdo->prepare("
        INSERT INTO Ordine_Biglietti (idOrdine, idBiglietto)
        VALUES (?, ?)
    ");
    return $stmt->execute([$idOrdine, $idBiglietto]);
}

/**
 * Elimina un ordine
 * I biglietti e le associazioni vengono gestiti dal cascade del DB
 *
 * @return bool Esito operazione
 */
function deleteOrdine(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM Ordini WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Calcola il totale di un ordine sommando i prezzi dei biglietti
 * Il prezzo di ogni biglietto dipende da evento, tipologia e settore
 *
 * @return float Totale ordine in euro
 */
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

/**
 * Recupera lo storico ordini di un utente
 * Include conteggio biglietti per ogni ordine
 *
 * @return array Lista ordini con numero biglietti
 */
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

/**
 * Verifica se un ordine appartiene a un utente specifico
 * Utilizzato per controlli di autorizzazione
 *
 * @return bool True se l'ordine appartiene all'utente
 */
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
