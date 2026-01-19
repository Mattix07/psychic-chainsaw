<?php
/**
 * Model Biglietto
 * Gestisce operazioni CRUD sui biglietti, validazione e calcolo prezzi
 *
 * I biglietti rappresentano i titoli di accesso agli eventi.
 * Ogni biglietto e associato a un evento, una tipologia (Standard/VIP/Premium)
 * e opzionalmente a un settore con posto assegnato.
 */

/**
 * Recupera tutte le tipologie di biglietto disponibili
 * Le tipologie definiscono il sovrapprezzo applicato al prezzo base
 *
 * @return array Lista tipologie ordinate per modificatore prezzo crescente
 */
function getAllTipiBiglietto(PDO $pdo): array
{
    return $pdo->query("SELECT * FROM Tipo ORDER BY ModificatorePrezzo")->fetchAll();
}

/**
 * Recupera una tipologia biglietto tramite nome
 *
 * @param string $nome Nome della tipologia (es. "VIP", "Standard")
 * @return array|null Dati tipologia o null se non trovata
 */
function getTipoByNome(PDO $pdo, string $nome): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM Tipo WHERE nome = ?");
    $stmt->execute([$nome]);
    return $stmt->fetch() ?: null;
}

/**
 * Recupera un biglietto con dati evento e tipologia
 *
 * @return array|null Dati biglietto arricchiti o null se non trovato
 */
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

/**
 * Recupera tutti i biglietti di un evento con informazioni sul posto
 * Include dati settore se il biglietto ha un posto assegnato
 *
 * @return array Lista biglietti con fila e numero posto
 */
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

/**
 * Recupera i biglietti associati a un ordine
 * Utilizzato per visualizzare il riepilogo ordine
 *
 * @return array Lista biglietti con dettagli evento e prezzo
 */
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

/**
 * Crea un nuovo biglietto con QR code univoco
 * Il QR code viene generato automaticamente per la validazione all'ingresso
 *
 * @param array $data Dati intestatario: idEvento, idClasse, Nome, Cognome, Sesso
 * @return int ID del biglietto creato
 */
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

/**
 * Assegna un posto specifico a un biglietto
 * Collega il biglietto a un settore con fila e numero posto
 *
 * @param string $fila Lettera della fila (es. "A", "B")
 * @param int $numero Numero del posto nella fila
 * @return bool Esito operazione
 */
function assegnaPosto(PDO $pdo, int $idBiglietto, int $idSettore, string $fila, int $numero): bool
{
    $stmt = $pdo->prepare("
        INSERT INTO Settore_Biglietti (idSettore, idBiglietto, Fila, Numero)
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([$idSettore, $idBiglietto, $fila, $numero]);
}

/**
 * Marca il biglietto come validato all'ingresso
 * Imposta il flag Check a TRUE per indicare che il biglietto e stato utilizzato
 *
 * @return bool Esito operazione
 */
function validaBiglietto(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("UPDATE Biglietti SET `Check` = TRUE WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Verifica se un biglietto e gia stato validato
 * Utile per prevenire riutilizzo fraudolento
 *
 * @return bool True se il biglietto e stato gia usato
 */
function isBigliettoValidato(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("SELECT `Check` FROM Biglietti WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    return $result && $result['Check'];
}

/**
 * Elimina un biglietto dal database
 * Le relazioni vengono eliminate in cascade dal DB
 *
 * @return bool Esito operazione
 */
function deleteBiglietto(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM Biglietti WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Genera un codice QR univoco per il biglietto
 * Utilizza random_bytes per garantire unicita crittografica
 *
 * @return string Stringa esadecimale di 64 caratteri
 */
function generateQRCode(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Calcola il prezzo finale di un biglietto
 * Formula: (PrezzoBase + ModificatoreTipo) * MoltiplicatoreSettore
 *
 * @param string $idClasse Nome della tipologia biglietto
 * @return float Prezzo finale calcolato, 0 se dati non trovati
 */
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

/**
 * Recupera i biglietti futuri di un utente
 * Mostra solo eventi non ancora svolti, ordinati per data
 * Include calcolo prezzo finale per ogni biglietto
 *
 * @return array Lista biglietti per eventi futuri
 */
function getBigliettiUtenteFuturi(PDO $pdo, int $idUtente): array
{
    $stmt = $pdo->prepare("
        SELECT b.*, e.Nome as EventoNome, e.Data, e.OraI, e.OraF,
               l.Nome as LocationName,
               sb.idSettore,
               (e.PrezzoNoMod + t.ModificatorePrezzo) * s.MoltiplicatorePrezzo as PrezzoFinale
        FROM Biglietti b
        JOIN Ordine_Biglietti ob ON b.id = ob.idBiglietto
        JOIN Ordini o ON ob.idOrdine = o.id
        JOIN Eventi e ON b.idEvento = e.id
        JOIN Locations l ON e.idLocation = l.id
        JOIN Tipo t ON b.idClasse = t.nome
        LEFT JOIN Settore_Biglietti sb ON b.id = sb.idBiglietto
        LEFT JOIN Settori s ON sb.idSettore = s.id
        WHERE o.idUtente = ? AND e.Data >= CURDATE()
        ORDER BY e.Data, e.OraI
    ");
    $stmt->execute([$idUtente]);
    return $stmt->fetchAll();
}

/**
 * Recupera i biglietti passati di un utente
 * Mostra eventi gia svolti, ordinati dal piu recente
 * Utile per lo storico acquisti
 *
 * @return array Lista biglietti per eventi passati
 */
function getBigliettiUtentePassati(PDO $pdo, int $idUtente): array
{
    $stmt = $pdo->prepare("
        SELECT b.*, e.Nome as EventoNome, e.Data, e.OraI,
               l.Nome as LocationName,
               (e.PrezzoNoMod + t.ModificatorePrezzo) * COALESCE(s.MoltiplicatorePrezzo, 1) as PrezzoFinale
        FROM Biglietti b
        JOIN Ordine_Biglietti ob ON b.id = ob.idBiglietto
        JOIN Ordini o ON ob.idOrdine = o.id
        JOIN Eventi e ON b.idEvento = e.id
        JOIN Locations l ON e.idLocation = l.id
        JOIN Tipo t ON b.idClasse = t.nome
        LEFT JOIN Settore_Biglietti sb ON b.id = sb.idBiglietto
        LEFT JOIN Settori s ON sb.idSettore = s.id
        WHERE o.idUtente = ? AND e.Data < CURDATE()
        ORDER BY e.Data DESC, e.OraI DESC
    ");
    $stmt->execute([$idUtente]);
    return $stmt->fetchAll();
}

/**
 * Verifica se un utente possiede almeno un biglietto per un evento
 * Utilizzato per abilitare la scrittura di recensioni
 *
 * @return bool True se l'utente ha biglietti per l'evento
 */
function hasBigliettoPerEvento(PDO $pdo, int $idUtente, int $idEvento): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM Biglietti b
        JOIN Ordine_Biglietti ob ON b.id = ob.idBiglietto
        JOIN Ordini o ON ob.idOrdine = o.id
        WHERE o.idUtente = ? AND b.idEvento = ?
    ");
    $stmt->execute([$idUtente, $idEvento]);
    $result = $stmt->fetch();
    return $result && $result['count'] > 0;
}

/**
 * Verifica se esiste gia un biglietto con stesso intestatario per l'evento
 * Previene acquisti duplicati per la stessa persona
 * Il confronto e case-insensitive e ignora spazi
 *
 * @return bool True se esiste un duplicato
 */
function esisteBigliettoDuplicato(PDO $pdo, int $idEvento, string $nome, string $cognome): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM Biglietti
        WHERE idEvento = ?
          AND LOWER(TRIM(Nome)) = LOWER(TRIM(?))
          AND LOWER(TRIM(Cognome)) = LOWER(TRIM(?))
    ");
    $stmt->execute([$idEvento, $nome, $cognome]);
    $result = $stmt->fetch();
    return $result && $result['count'] > 0;
}
