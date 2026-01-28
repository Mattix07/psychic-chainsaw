<?php
/**
 * Model Ordine
 * Gestisce le transazioni di acquisto biglietti
 *
 * Un ordine raggruppa uno o piu biglietti acquistati in una singola transazione.
 * E collegato all'utente tramite la tabella ponte Utente_Ordini e ai biglietti
 * tramite Ordine_Biglietti.
 */

require_once __DIR__ . '/../config/database_schema.php';
require_once __DIR__ . '/../lib/QueryBuilder.php';

/**
 * Recupera tutti gli ordini ordinati dal piu recente
 *
 * @return array Lista completa ordini
 */
function getAllOrdini(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM " . TABLE_ORDINI . " ORDER BY " . COL_ORDINI_ID . " DESC")->fetchAll();
}

/**
 * Recupera un ordine tramite ID
 *
 * @return array|null Dati ordine o null se non trovato
 */
function getOrdineById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM " . TABLE_ORDINI . " WHERE " . COL_ORDINI_ID . " = ?");
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
        FROM " . TABLE_UTENTI . " u
        JOIN " . TABLE_UTENTE_ORDINI . " uo ON u." . COL_UTENTI_ID . " = uo.idUtente
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
    $stmt = $pdo->prepare("INSERT INTO " . TABLE_ORDINI . " (" . COL_ORDINI_METODO_PAGAMENTO . ") VALUES (?)");
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
        INSERT INTO " . TABLE_UTENTE_ORDINI . " (idUtente, idOrdine)
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
        INSERT INTO " . TABLE_ORDINE_BIGLIETTI . " (idOrdine, idBiglietto)
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
    $stmt = $pdo->prepare("DELETE FROM " . TABLE_ORDINI . " WHERE " . COL_ORDINI_ID . " = ?");
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
        SELECT SUM((e." . COL_EVENTI_PREZZO_NO_MOD . " + t." . COL_TIPO_MODIFICATORE_PREZZO . ") * COALESCE(s." . COL_SETTORI_MOLTIPLICATORE_PREZZO . ", 1)) as totale
        FROM " . TABLE_ORDINE_BIGLIETTI . " ob
        JOIN " . TABLE_BIGLIETTI . " b ON ob.idBiglietto = b." . COL_BIGLIETTI_ID . "
        JOIN " . TABLE_EVENTI . " e ON b." . COL_BIGLIETTI_ID_EVENTO . " = e." . COL_EVENTI_ID . "
        JOIN " . TABLE_TIPO . " t ON b." . COL_BIGLIETTI_ID_CLASSE . " = t." . COL_TIPO_NOME . "
        LEFT JOIN " . TABLE_SETTORE_BIGLIETTI . " sb ON b." . COL_BIGLIETTI_ID . " = sb." . COL_SETTORE_BIGLIETTI_ID_BIGLIETTO . "
        LEFT JOIN " . TABLE_SETTORI . " s ON sb." . COL_SETTORE_BIGLIETTI_ID_SETTORE . " = s." . COL_SETTORI_ID . "
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
        FROM " . TABLE_ORDINI . " o
        JOIN " . TABLE_UTENTE_ORDINI . " uo ON o." . COL_ORDINI_ID . " = uo.idOrdine
        LEFT JOIN " . TABLE_ORDINE_BIGLIETTI . " ob ON o." . COL_ORDINI_ID . " = ob.idOrdine
        WHERE uo.idUtente = ?
        GROUP BY o." . COL_ORDINI_ID . "
        ORDER BY o." . COL_ORDINI_ID . " DESC
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
        FROM " . TABLE_UTENTE_ORDINI . "
        WHERE idOrdine = ? AND idUtente = ?
    ");
    $stmt->execute([$idOrdine, $idUtente]);
    $result = $stmt->fetch();
    return $result && $result['count'] > 0;
}
