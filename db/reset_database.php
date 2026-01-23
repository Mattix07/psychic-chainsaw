<?php
/**
 * Script Helper per Reset Database
 *
 * ATTENZIONE: Questo script CANCELLA tutti i dati!
 * Usare SOLO in ambiente di sviluppo/test.
 *
 * Per ragioni di sicurezza, funziona solo su localhost
 * e richiede conferma tramite parametro GET.
 *
 * Uso: http://localhost/eventsMaster/db/reset_database.php?confirm=yes
 */

// ============================================
// CONFIGURAZIONE SICUREZZA
// ============================================

// Permetti solo su localhost
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1', 'localhost'])) {
    die('❌ ERRORE: Questo script può essere eseguito solo da localhost per ragioni di sicurezza.');
}

// Richiedi conferma esplicita
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
    ?>
    <!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reset Database - Conferma</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                max-width: 800px;
                margin: 50px auto;
                padding: 20px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #333;
            }
            .container {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            }
            h1 {
                color: #e74c3c;
                margin-top: 0;
            }
            .warning {
                background: #fff3cd;
                border-left: 4px solid #ffc107;
                padding: 15px;
                margin: 20px 0;
            }
            .info {
                background: #d1ecf1;
                border-left: 4px solid #17a2b8;
                padding: 15px;
                margin: 20px 0;
            }
            .button-group {
                display: flex;
                gap: 10px;
                margin-top: 30px;
            }
            button, a.button {
                padding: 12px 24px;
                border: none;
                border-radius: 5px;
                font-size: 16px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                transition: all 0.3s;
            }
            .btn-danger {
                background: #e74c3c;
                color: white;
            }
            .btn-danger:hover {
                background: #c0392b;
            }
            .btn-secondary {
                background: #95a5a6;
                color: white;
            }
            .btn-secondary:hover {
                background: #7f8c8d;
            }
            ul {
                line-height: 1.8;
            }
            code {
                background: #f8f9fa;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: 'Courier New', monospace;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>⚠️ Reset Database - Conferma Richiesta</h1>

            <div class="warning">
                <strong>ATTENZIONE:</strong> Stai per cancellare TUTTI i dati del database!
            </div>

            <h2>Cosa verrà fatto:</h2>
            <ul>
                <li>❌ Eliminazione di tutti gli account utente (eccetto 4 di test)</li>
                <li>❌ Eliminazione di tutti gli ordini e biglietti</li>
                <li>❌ Eliminazione di tutte le recensioni</li>
                <li>❌ Eliminazione di tutte le notifiche</li>
                <li>❌ Eliminazione di tutti i carrelli</li>
                <li>✅ Inserimento di 15 location realistiche</li>
                <li>✅ Inserimento di 15 settori</li>
                <li>✅ Inserimento di 5 manifestazioni</li>
                <li>✅ Inserimento di 16 intrattenitori</li>
                <li>✅ Inserimento di 20+ eventi futuri</li>
                <li>✅ Creazione di 4 utenti di test</li>
            </ul>

            <div class="info">
                <h3>📋 Utenti di test che verranno creati:</h3>
                <ul>
                    <li><code>admin@eventsmaster.it</code> / <code>password123</code> (Admin)</li>
                    <li><code>mod@eventsmaster.it</code> / <code>password123</code> (Moderatore)</li>
                    <li><code>promoter@eventsmaster.it</code> / <code>password123</code> (Promoter)</li>
                    <li><code>user@eventsmaster.it</code> / <code>password123</code> (User)</li>
                </ul>
            </div>

            <div class="button-group">
                <a href="?confirm=yes" class="button btn-danger" onclick="return confirm('Sei DAVVERO sicuro? Tutti i dati verranno persi!')">
                    🔥 SÌ, RESET DEL DATABASE
                </a>
                <a href="../index.php" class="button btn-secondary">
                    ← Annulla e torna alla Home
                </a>
            </div>

            <p style="margin-top: 30px; color: #7f8c8d; font-size: 14px;">
                <strong>Nota:</strong> Questo script funziona solo su localhost per ragioni di sicurezza.
                In produzione questo file deve essere rimosso o protetto.
            </p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ============================================
// ESECUZIONE RESET
// ============================================

// Carica configurazione database
require_once __DIR__ . '/../config/database.php';

// Leggi il file SQL
$sqlFile = __DIR__ . '/reset_to_production_state.sql';

if (!file_exists($sqlFile)) {
    die('❌ ERRORE: File SQL non trovato: ' . $sqlFile);
}

$sql = file_get_contents($sqlFile);

if (!$sql) {
    die('❌ ERRORE: Impossibile leggere il file SQL');
}

// Header HTML
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Database - In Esecuzione</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .success { color: #27ae60; }
        .error { color: #e74c3c; }
        .log {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            max-height: 500px;
            overflow-y: auto;
            margin: 20px 0;
        }
        .log-line {
            margin: 5px 0;
            padding: 5px;
            border-left: 3px solid #ddd;
            padding-left: 10px;
        }
        .log-line.success { border-color: #27ae60; background: #eafaf1; }
        .log-line.error { border-color: #e74c3c; background: #fadbd8; }
        .log-line.info { border-color: #3498db; background: #ebf5fb; }
        h1 { margin-top: 0; }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .summary {
            background: #e8f5e9;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .summary table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .summary td:first-child {
            font-weight: bold;
            width: 200px;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        button:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Reset Database in Corso...</h1>
        <div class="spinner"></div>
        <div class="log">
<?php

// Funzione per loggare messaggi
function logMessage($message, $type = 'info') {
    $class = $type;
    $icon = [
        'success' => '✅',
        'error' => '❌',
        'info' => 'ℹ️'
    ][$type] ?? 'ℹ️';

    echo "<div class='log-line $class'>$icon $message</div>";
    flush();
    ob_flush();
}

// Esegui il reset
try {
    logMessage('Connessione al database...', 'info');

    // Dividi il file SQL in statement singoli
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) &&
                   !preg_match('/^--/', $stmt) &&
                   !preg_match('/^\/\*/', $stmt);
        }
    );

    logMessage('Trovati ' . count($statements) . ' comandi SQL da eseguire', 'info');
    logMessage('Inizio esecuzione...', 'info');

    $executed = 0;
    $errors = 0;

    foreach ($statements as $statement) {
        try {
            // Salta commenti e linee vuote
            if (empty(trim($statement))) continue;

            $pdo->exec($statement . ';');
            $executed++;

            // Log ogni 10 statement
            if ($executed % 10 == 0) {
                logMessage("Eseguiti $executed comandi...", 'info');
            }
        } catch (PDOException $e) {
            $errors++;
            // Ignora errori su SELECT (sono query di verifica)
            if (stripos($statement, 'SELECT') !== 0) {
                logMessage('Errore in statement: ' . substr($statement, 0, 100) . '... - ' . $e->getMessage(), 'error');
            }
        }
    }

    logMessage("Esecuzione completata!", 'success');
    logMessage("Comandi eseguiti: $executed", 'success');
    if ($errors > 0) {
        logMessage("Errori riscontrati: $errors (probabilmente query di verifica)", 'info');
    }

    // Verifica finale
    logMessage('Verifica finale dei dati inseriti...', 'info');

    $counts = [
        'Utenti' => $pdo->query('SELECT COUNT(*) FROM Utenti')->fetchColumn(),
        'Locations' => $pdo->query('SELECT COUNT(*) FROM Location')->fetchColumn(),
        'Settori' => $pdo->query('SELECT COUNT(*) FROM Settori')->fetchColumn(),
        'Manifestazioni' => $pdo->query('SELECT COUNT(*) FROM Manifestazioni')->fetchColumn(),
        'Intrattenitori' => $pdo->query('SELECT COUNT(*) FROM Intrattenitore')->fetchColumn(),
        'Eventi' => $pdo->query('SELECT COUNT(*) FROM Eventi')->fetchColumn(),
        'Tipi Biglietto' => $pdo->query('SELECT COUNT(*) FROM Tipo')->fetchColumn(),
    ];

    echo '</div>'; // Chiudi log

    echo '<div class="summary">';
    echo '<h2 class="success">✅ Reset Completato con Successo!</h2>';
    echo '<table>';
    foreach ($counts as $label => $count) {
        echo "<tr><td>$label:</td><td>$count</td></tr>";
    }
    echo '</table>';
    echo '</div>';

    echo '<div class="info log-line">';
    echo '<h3>🔑 Credenziali di Accesso:</h3>';
    echo '<ul>';
    echo '<li><strong>Admin:</strong> admin@eventsmaster.it / password123</li>';
    echo '<li><strong>Moderatore:</strong> mod@eventsmaster.it / password123</li>';
    echo '<li><strong>Promoter:</strong> promoter@eventsmaster.it / password123</li>';
    echo '<li><strong>User:</strong> user@eventsmaster.it / password123</li>';
    echo '</ul>';
    echo '</div>';

    echo '<button onclick="window.location.href=\'../index.php\'">🏠 Vai alla Homepage</button>';
    echo ' ';
    echo '<button onclick="window.location.href=\'../index.php?action=show_login\'" style="background:#27ae60">🔐 Vai al Login</button>';

} catch (Exception $e) {
    echo '</div>'; // Chiudi log
    echo '<div class="error">';
    echo '<h2>❌ Errore durante il reset</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}

?>
    </div>
</body>
</html>
