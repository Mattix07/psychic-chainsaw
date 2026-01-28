#!/usr/bin/env php
<?php
/**
 * Cleanup Script - Eliminazione Eventi Vecchi
 *
 * Elimina automaticamente:
 * - Eventi finiti da più di 2 settimane
 * - Manifestazioni finite (se tutti gli eventi sono finiti)
 * - Biglietti associati agli eventi eliminati
 * - Ordini vuoti (senza biglietti)
 * - Recensioni degli eventi eliminati
 * - Relazioni in tabelle di join
 *
 * Esecuzione: cronjob giornaliero alle 03:00
 */

// Carica configurazione
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/database_schema.php';

// Log file
$logFile = __DIR__ . '/../logs/cleanup_events.log';

/**
 * Scrive nel log
 */
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";

    // Crea directory logs se non esiste
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage;
}

/**
 * Inizio script
 */
logMessage("=== INIZIO CLEANUP EVENTI VECCHI ===");

try {
    // Calcola data limite (2 settimane fa)
    $dataLimite = date('Y-m-d', strtotime('-2 weeks'));
    logMessage("Data limite: {$dataLimite} (eventi finiti prima di questa data verranno eliminati)");

    // FASE 1: Trova eventi da eliminare
    logMessage("FASE 1: Ricerca eventi da eliminare...");

    $stmt = $pdo->prepare("
        SELECT id, Nome, Data, OraF
        FROM " . TABLE_EVENTI . "
        WHERE Data < :data_limite
        ORDER BY Data ASC
    ");
    $stmt->execute(['data_limite' => $dataLimite]);
    $eventiDaEliminare = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totaleEventi = count($eventiDaEliminare);
    logMessage("Trovati {$totaleEventi} eventi da eliminare");

    if ($totaleEventi === 0) {
        logMessage("Nessun evento da eliminare. Fine.");
        logMessage("=== FINE CLEANUP ===\n");
        exit(0);
    }

    // Inizia transazione
    $pdo->beginTransaction();

    $eventiEliminati = 0;
    $bigliettiEliminati = 0;
    $recensioniEliminate = 0;

    foreach ($eventiDaEliminare as $evento) {
        $eventoId = $evento['id'];
        $eventoNome = $evento['Nome'];
        $eventoData = $evento['Data'];

        logMessage("  Elaborazione: #{$eventoId} - {$eventoNome} ({$eventoData})");

        // FASE 2: Elimina biglietti dell'evento
        // 2.1: Elimina da Settore_Biglietti
        $stmt = $pdo->prepare("
            DELETE sb FROM " . TABLE_SETTORE_BIGLIETTI . " sb
            INNER JOIN " . TABLE_BIGLIETTI . " b ON sb." . COL_SETTORE_BIGLIETTI_ID_BIGLIETTO . " = b." . COL_BIGLIETTI_ID . "
            WHERE b." . COL_BIGLIETTI_ID_EVENTO . " = :evento_id
        ");
        $stmt->execute(['evento_id' => $eventoId]);
        $deletedSettoreBiglietti = $stmt->rowCount();

        // 2.2: Elimina da Ordine_Biglietti
        $stmt = $pdo->prepare("
            DELETE ob FROM " . TABLE_ORDINE_BIGLIETTI . " ob
            INNER JOIN " . TABLE_BIGLIETTI . " b ON ob.idBiglietto = b." . COL_BIGLIETTI_ID . "
            WHERE b." . COL_BIGLIETTI_ID_EVENTO . " = :evento_id
        ");
        $stmt->execute(['evento_id' => $eventoId]);
        $deletedOrdineBiglietti = $stmt->rowCount();

        // 2.3: Elimina biglietti
        $stmt = $pdo->prepare("
            DELETE FROM " . TABLE_BIGLIETTI . "
            WHERE " . COL_BIGLIETTI_ID_EVENTO . " = :evento_id
        ");
        $stmt->execute(['evento_id' => $eventoId]);
        $deletedBiglietti = $stmt->rowCount();
        $bigliettiEliminati += $deletedBiglietti;

        logMessage("    - Eliminati {$deletedBiglietti} biglietti");

        // FASE 3: Elimina recensioni dell'evento
        $stmt = $pdo->prepare("
            DELETE FROM " . TABLE_RECENSIONI . "
            WHERE " . COL_RECENSIONI_ID_EVENTO . " = :evento_id
        ");
        $stmt->execute(['evento_id' => $eventoId]);
        $deletedRecensioni = $stmt->rowCount();
        $recensioniEliminate += $deletedRecensioni;

        if ($deletedRecensioni > 0) {
            logMessage("    - Eliminate {$deletedRecensioni} recensioni");
        }

        // FASE 4: Elimina relazioni evento
        // 4.1: EventiSettori
        $stmt = $pdo->prepare("
            DELETE FROM " . TABLE_EVENTI_SETTORI . "
            WHERE idEvento = :evento_id
        ");
        $stmt->execute(['evento_id' => $eventoId]);

        // 4.2: Evento_Intrattenitore
        $stmt = $pdo->prepare("
            DELETE FROM " . TABLE_EVENTO_INTRATTENITORE . "
            WHERE idEvento = :evento_id
        ");
        $stmt->execute(['evento_id' => $eventoId]);

        // 4.3: CollaboratoriEventi
        $stmt = $pdo->prepare("
            DELETE FROM " . TABLE_COLLABORATORI_EVENTI . "
            WHERE " . COL_COLLABORATORI_EVENTI_ID_EVENTO . " = :evento_id
        ");
        $stmt->execute(['evento_id' => $eventoId]);

        // 4.4: CreatoriEventi
        $stmt = $pdo->prepare("
            DELETE FROM " . TABLE_CREATORI_EVENTI . "
            WHERE idEvento = :evento_id
        ");
        $stmt->execute(['evento_id' => $eventoId]);

        // FASE 5: Elimina evento
        $stmt = $pdo->prepare("
            DELETE FROM " . TABLE_EVENTI . "
            WHERE " . COL_EVENTI_ID . " = :evento_id
        ");
        $stmt->execute(['evento_id' => $eventoId]);

        $eventiEliminati++;
        logMessage("    ✓ Evento eliminato");
    }

    // FASE 6: Elimina manifestazioni finite
    logMessage("FASE 6: Ricerca manifestazioni finite...");

    $stmt = $pdo->prepare("
        SELECT m." . COL_MANIFESTAZIONI_ID . ", m." . COL_MANIFESTAZIONI_NOME . ", m." . COL_MANIFESTAZIONI_DATA_FINE . "
        FROM " . TABLE_MANIFESTAZIONI . " m
        WHERE m." . COL_MANIFESTAZIONI_DATA_FINE . " < :data_limite
        AND NOT EXISTS (
            SELECT 1 FROM " . TABLE_EVENTI . " e
            WHERE e." . COL_EVENTI_ID_MANIFESTAZIONE . " = m." . COL_MANIFESTAZIONI_ID . "
        )
    ");
    $stmt->execute(['data_limite' => $dataLimite]);
    $manifestazioniDaEliminare = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $manifestazioniEliminate = 0;
    foreach ($manifestazioniDaEliminare as $manifestazione) {
        $manifId = $manifestazione[COL_MANIFESTAZIONI_ID];
        $manifNome = $manifestazione[COL_MANIFESTAZIONI_NOME];

        // Elimina relazioni
        $stmt = $pdo->prepare("
            DELETE FROM " . TABLE_CREATORI_MANIFESTAZIONI . "
            WHERE idManifestazione = :manif_id
        ");
        $stmt->execute(['manif_id' => $manifId]);

        // Elimina manifestazione
        $stmt = $pdo->prepare("
            DELETE FROM " . TABLE_MANIFESTAZIONI . "
            WHERE " . COL_MANIFESTAZIONI_ID . " = :manif_id
        ");
        $stmt->execute(['manif_id' => $manifId]);

        $manifestazioniEliminate++;
        logMessage("  ✓ Manifestazione eliminata: {$manifNome}");
    }

    // FASE 7: Pulisci ordini vuoti
    logMessage("FASE 7: Pulizia ordini vuoti...");

    $stmt = $pdo->prepare("
        DELETE o FROM " . TABLE_ORDINI . " o
        WHERE NOT EXISTS (
            SELECT 1 FROM " . TABLE_ORDINE_BIGLIETTI . " ob
            WHERE ob.idOrdine = o." . COL_ORDINI_ID . "
        )
    ");
    $stmt->execute();
    $ordiniVuoti = $stmt->rowCount();

    if ($ordiniVuoti > 0) {
        logMessage("  ✓ Eliminati {$ordiniVuoti} ordini vuoti");
    }

    // FASE 8: Pulisci relazioni Utente_Ordini orfane
    $stmt = $pdo->prepare("
        DELETE uo FROM " . TABLE_UTENTE_ORDINI . " uo
        WHERE NOT EXISTS (
            SELECT 1 FROM " . TABLE_ORDINI . " o
            WHERE o." . COL_ORDINI_ID . " = uo.idOrdine
        )
    ");
    $stmt->execute();

    // Commit transazione
    $pdo->commit();

    // RIEPILOGO
    logMessage("");
    logMessage("=== RIEPILOGO CLEANUP ===");
    logMessage("Eventi eliminati: {$eventiEliminati}");
    logMessage("Biglietti eliminati: {$bigliettiEliminati}");
    logMessage("Recensioni eliminate: {$recensioniEliminate}");
    logMessage("Manifestazioni eliminate: {$manifestazioniEliminate}");
    logMessage("Ordini vuoti eliminati: {$ordiniVuoti}");
    logMessage("=== CLEANUP COMPLETATO CON SUCCESSO ===\n");

    exit(0);

} catch (Exception $e) {
    // Rollback in caso di errore
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    logMessage("ERRORE: " . $e->getMessage());
    logMessage("Stack trace: " . $e->getTraceAsString());
    logMessage("=== CLEANUP FALLITO ===\n");

    exit(1);
}
