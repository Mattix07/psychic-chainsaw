<?php
/**
 * Cron Job: Auto-eliminazione Eventi Passati
 *
 * Questo script deve essere eseguito periodicamente (es. giornalmente) tramite:
 * - Windows Task Scheduler
 * - Cron job Linux
 *
 * Elimina:
 * - Eventi con data >= 2 settimane fa
 * - I biglietti acquistati rimangono nello storico ordini
 * - I biglietti non acquistati vengono eliminati con l'evento
 *
 * Configurazione Windows Task Scheduler:
 * Program: C:\xampp\php\php.exe
 * Arguments: C:\xampp\htdocs\eventsMaster\cron\auto_delete_old_events.php
 * Schedule: Giornaliero alle 03:00
 *
 * Configurazione Cron Linux:
 * 0 3 * * * /usr/bin/php /path/to/eventsMaster/cron/auto_delete_old_events.php
 */

// Previeni esecuzione da web browser
if (php_sapi_name() !== 'cli') {
    die('Questo script deve essere eseguito da command line');
}

// Carica configurazione
require_once dirname(__DIR__) . '/config/db_config.php';

$logFile = dirname(__DIR__) . '/logs/cron_delete_events.log';

function logMessage(string $message): void
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

try {
    logMessage("=== Inizio pulizia eventi passati ===");

    // Connessione al database
    $pdo = getDbConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Calcola la data limite: 2 settimane fa
    $limitDate = date('Y-m-d', strtotime('-2 weeks'));
    logMessage("Data limite: $limitDate");

    // Step 1: Trova eventi da eliminare
    $stmt = $pdo->prepare("
        SELECT id, Nome, Data
        FROM Eventi
        WHERE Data < ?
    ");
    $stmt->execute([$limitDate]);
    $eventiDaEliminare = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $count = count($eventiDaEliminare);
    logMessage("Trovati $count eventi da eliminare");

    if ($count === 0) {
        logMessage("Nessun evento da eliminare");
        logMessage("=== Fine pulizia ===");
        exit(0);
    }

    $pdo->beginTransaction();

    foreach ($eventiDaEliminare as $evento) {
        $eventoId = $evento['id'];
        $nomeEvento = $evento['Nome'];
        $dataEvento = $evento['Data'];

        logMessage("Elaborazione evento ID $eventoId: '$nomeEvento' (Data: $dataEvento)");

        // Step 2: Elimina biglietti NON acquistati (in carrello)
        $stmtCarrello = $pdo->prepare("
            DELETE FROM Biglietti
            WHERE idEvento = ? AND Stato = 'carrello'
        ");
        $stmtCarrello->execute([$eventoId]);
        $bigliettiCarrello = $stmtCarrello->rowCount();
        logMessage("  - Eliminati $bigliettiCarrello biglietti in carrello");

        // Step 3: Conta biglietti acquistati (rimarranno nello storico)
        $stmtAcquistati = $pdo->prepare("
            SELECT COUNT(*) FROM Biglietti
            WHERE idEvento = ? AND Stato = 'acquistato'
        ");
        $stmtAcquistati->execute([$eventoId]);
        $bigliettiAcquistati = $stmtAcquistati->fetchColumn();
        logMessage("  - Biglietti acquistati (rimangono in storico): $bigliettiAcquistati");

        // Step 4: Elimina l'evento (CASCADE eliminerà le foreign key)
        // I biglietti acquistati verranno eliminati, ma rimangono tracciati negli ordini
        $stmtDelEvento = $pdo->prepare("DELETE FROM Eventi WHERE id = ?");
        $stmtDelEvento->execute([$eventoId]);

        logMessage("  - Evento eliminato con successo");
    }

    $pdo->commit();

    logMessage("=== Pulizia completata: $count eventi eliminati ===");
    exit(0);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    logMessage("ERRORE: " . $e->getMessage());
    logMessage("Stack trace: " . $e->getTraceAsString());
    logMessage("=== Pulizia fallita ===");
    exit(1);
}
