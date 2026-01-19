<?php
/**
 * Model Recensione
 * Gestisce le valutazioni degli eventi da parte degli utenti
 *
 * Gli utenti possono recensire solo eventi per cui hanno acquistato biglietti.
 * Ogni utente puo lasciare una sola recensione per evento.
 */

/**
 * Recupera tutte le recensioni di un evento con dati autore
 *
 * @return array Lista recensioni con nome e cognome utente
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

/**
 * Calcola la media voti di un evento
 * Arrotonda a una cifra decimale per visualizzazione
 *
 * @return float|null Media voti o null se nessuna recensione
 */
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

/**
 * Recupera la recensione di un utente per un evento specifico
 *
 * @return array|null Dati recensione o null se non esiste
 */
function getRecensione(PDO $pdo, int $idEvento, int $idUtente): ?array
{
    $stmt = $pdo->prepare("
        SELECT * FROM Recensioni
        WHERE idEvento = ? AND idUtente = ?
    ");
    $stmt->execute([$idEvento, $idUtente]);
    return $stmt->fetch() ?: null;
}

/**
 * Crea una nuova recensione
 *
 * @param int $voto Valutazione da 1 a 5
 * @param string|null $messaggio Commento testuale opzionale
 * @return bool Esito operazione
 */
function createRecensione(PDO $pdo, int $idEvento, int $idUtente, int $voto, ?string $messaggio = null): bool
{
    $stmt = $pdo->prepare("
        INSERT INTO Recensioni (idEvento, idUtente, Voto, Messaggio)
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([$idEvento, $idUtente, $voto, $messaggio]);
}

/**
 * Aggiorna una recensione esistente
 * Permette di modificare voto e messaggio
 *
 * @return bool Esito operazione
 */
function updateRecensione(PDO $pdo, int $idEvento, int $idUtente, int $voto, ?string $messaggio = null): bool
{
    $stmt = $pdo->prepare("
        UPDATE Recensioni
        SET Voto = ?, Messaggio = ?
        WHERE idEvento = ? AND idUtente = ?
    ");
    return $stmt->execute([$voto, $messaggio, $idEvento, $idUtente]);
}

/**
 * Elimina una recensione
 *
 * @return bool Esito operazione
 */
function deleteRecensione(PDO $pdo, int $idEvento, int $idUtente): bool
{
    $stmt = $pdo->prepare("
        DELETE FROM Recensioni
        WHERE idEvento = ? AND idUtente = ?
    ");
    return $stmt->execute([$idEvento, $idUtente]);
}

/**
 * Verifica se un utente ha gia recensito un evento
 *
 * @return bool True se la recensione esiste
 */
function hasRecensito(PDO $pdo, int $idEvento, int $idUtente): bool
{
    return getRecensione($pdo, $idEvento, $idUtente) !== null;
}

/**
 * Verifica se l'utente ha acquistato almeno un biglietto per l'evento
 * Controlla solo ordini in stato completato/confermato/pagato
 *
 * @return bool True se l'utente ha acquistato biglietti
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
 * Verifica se l'utente puo recensire un evento
 * Requisiti: aver acquistato un biglietto e non aver gia recensito
 *
 * @return bool True se l'utente puo scrivere una recensione
 */
function canRecensire(PDO $pdo, int $idEvento, int $idUtente): bool
{
    return hasAcquistatoBiglietto($pdo, $idEvento, $idUtente) && !hasRecensito($pdo, $idEvento, $idUtente);
}
