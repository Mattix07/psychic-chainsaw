<?php
/**
 * Script di Verifica Installazione EventsMaster
 *
 * Controlla che tutti i componenti siano installati correttamente
 * e che le nuove funzionalità siano operative.
 *
 * Uso: http://localhost/eventsMaster/check_installation.php
 */

// Solo localhost
if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    die('Accesso negato: script disponibile solo su localhost');
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifica Installazione - EventsMaster</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
            background: #f5f7fa;
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        .section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .section h2 {
            color: #34495e;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
            margin-top: 0;
        }
        .check-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            margin: 8px 0;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #ddd;
        }
        .check-item.success {
            border-left-color: #27ae60;
            background: #eafaf1;
        }
        .check-item.error {
            border-left-color: #e74c3c;
            background: #fadbd8;
        }
        .check-item.warning {
            border-left-color: #f39c12;
            background: #fef5e7;
        }
        .status {
            font-weight: bold;
            padding: 4px 12px;
            border-radius: 3px;
        }
        .status.ok {
            background: #27ae60;
            color: white;
        }
        .status.fail {
            background: #e74c3c;
            color: white;
        }
        .status.warn {
            background: #f39c12;
            color: white;
        }
        .summary {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .summary h2 {
            margin: 0;
            color: white;
            border: none;
        }
        code {
            background: #ecf0f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .details {
            font-size: 0.9em;
            color: #7f8c8d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <h1>🔍 Verifica Installazione EventsMaster</h1>

    <?php
    $checks = [];
    $errors = 0;
    $warnings = 0;
    $success = 0;

    // Helper function
    function addCheck($name, $status, $message = '', $details = '') {
        global $checks, $errors, $warnings, $success;
        $checks[] = [
            'name' => $name,
            'status' => $status,
            'message' => $message,
            'details' => $details
        ];
        if ($status === 'ok') $success++;
        elseif ($status === 'fail') $errors++;
        elseif ($status === 'warn') $warnings++;
    }

    // ============================================
    // VERIFICA PHP E ESTENSIONI
    // ============================================
    ?>
    <div class="section">
        <h2>🐘 PHP e Estensioni</h2>
        <?php
        // Versione PHP
        $phpVersion = phpversion();
        $phpOk = version_compare($phpVersion, '8.0.0', '>=');
        addCheck(
            'Versione PHP',
            $phpOk ? 'ok' : 'fail',
            "PHP $phpVersion",
            $phpOk ? 'Versione supportata' : 'Richiesto PHP 8.0 o superiore'
        );

        // PDO
        $pdoOk = extension_loaded('pdo') && extension_loaded('pdo_mysql');
        addCheck(
            'PDO MySQL',
            $pdoOk ? 'ok' : 'fail',
            '',
            $pdoOk ? 'Estensione caricata' : 'PDO MySQL non disponibile'
        );

        // GD (per immagini)
        $gdOk = extension_loaded('gd');
        addCheck(
            'GD Library',
            $gdOk ? 'ok' : 'warn',
            '',
            $gdOk ? 'Disponibile per resize avatar' : 'Consigliato per gestione immagini'
        );

        // mbstring
        $mbOk = extension_loaded('mbstring');
        addCheck(
            'Mbstring',
            $mbOk ? 'ok' : 'warn',
            '',
            $mbOk ? 'Disponibile' : 'Consigliato per gestione stringhe UTF-8'
        );
        ?>
    </div>

    <!-- VERIFICA FILES -->
    <div class="section">
        <h2>📁 File e Directory</h2>
        <?php
        $files = [
            // Config files
            'config/database.php' => 'File di configurazione database',
            'config/database_schema.php' => 'Schema database centralizzato',
            'config/app_config.php' => 'Configurazione applicazione',
            'config/messages.php' => 'Messaggi centralizzati',
            'config/helpers.php' => 'Funzioni helper globali',

            // Lib files
            'lib/QueryBuilder.php' => 'Query Builder fluent interface',
            'lib/Validator.php' => 'Sistema validazione',
            'lib/EmailService.php' => 'Servizio email',

            // Models
            'models/Permessi.php' => 'Model sistema permessi',
            'models/EventoSettori.php' => 'Model gestione settori',
            'models/Settore.php' => 'Model settori',

            // Controllers
            'controllers/CollaborazioneController.php' => 'Controller collaborazioni',
            'controllers/AvatarController.php' => 'Controller avatar',
            'controllers/CartController.php' => 'Controller carrello',

            // Migrations
            'db/migrations/001_add_collaboration_system.sql' => 'Migration database',
            'db/reset_to_production_state.sql' => 'Script reset database',

            // Documentation
            'ARCHITECTURE.md' => 'Documentazione architettura',
            'INTEGRATION_SUMMARY.md' => 'Riepilogo integrazione helpers',
            'FUNCTION_CONFLICT_PREVENTION.md' => 'Guida prevenzione conflitti',
        ];

        foreach ($files as $file => $desc) {
            $exists = file_exists(__DIR__ . '/' . $file);
            addCheck(
                $file,
                $exists ? 'ok' : 'fail',
                $desc,
                $exists ? 'File presente' : 'File mancante'
            );
        }

        // Directory scrivibili
        $writableDirs = ['logs', 'uploads'];
        foreach ($writableDirs as $dir) {
            $path = __DIR__ . '/' . $dir;
            $writable = is_dir($path) && is_writable($path);
            addCheck(
                "Directory $dir/",
                $writable ? 'ok' : 'warn',
                'Permessi scrittura',
                $writable ? 'Scrivibile' : 'Controllare permessi (chmod 777)'
            );
        }
        ?>
    </div>

    <!-- VERIFICA DATABASE -->
    <div class="section">
        <h2>🗄️ Connessione Database</h2>
        <?php
        try {
            require_once __DIR__ . '/config/database.php';

            addCheck(
                'Connessione PDO',
                'ok',
                'Connesso a ' . env('DB_NAME'),
                'Database raggiungibile'
            );

            // Verifica tabelle
            $requiredTables = [
                'Utenti', 'Eventi', 'Locations', 'Settori', 'Biglietti',
                'CreatoriEventi', 'CollaboratoriEventi', 'EventiSettori',
                'Notifiche', 'Manifestazioni', 'Intrattenitore'
            ];

            $stmt = $pdo->query("SHOW TABLES");
            $tablesResult = $stmt->fetchAll(PDO::FETCH_NUM);
            $existingTables = array_map(fn($row) => strtolower($row[0]), $tablesResult);

            foreach ($requiredTables as $table) {
                $exists = in_array(strtolower($table), $existingTables);
                addCheck(
                    "Tabella $table",
                    $exists ? 'ok' : 'fail',
                    '',
                    $exists ? 'Presente' : 'Tabella mancante - eseguire migration'
                );
            }

            // Verifica colonne nuove
            $newColumns = [
                'Utenti' => ['Avatar', 'verificato'],
                'Biglietti' => ['Stato', 'idUtente', 'DataCarrello'],
                'Recensioni' => ['created_at'],
            ];

            foreach ($newColumns as $table => $columns) {
                $stmt = $pdo->query("DESCRIBE $table");
                $existingCols = array_map('strtolower', $stmt->fetchAll(PDO::FETCH_COLUMN));

                foreach ($columns as $col) {
                    $exists = in_array(strtolower($col), $existingCols);
                    addCheck(
                        "Colonna $table.$col",
                        $exists ? 'ok' : 'fail',
                        '',
                        $exists ? 'Presente' : 'Colonna mancante - eseguire migration'
                    );
                }
            }

        } catch (Exception $e) {
            addCheck(
                'Connessione Database',
                'fail',
                'Errore: ' . $e->getMessage(),
                'Verificare config/database.php'
            );
        }
        ?>
    </div>

    <!-- VERIFICA HELPER SYSTEMS -->
    <div class="section">
        <h2>🛠️ Helper Systems (Nuovi)</h2>
        <?php
        // Test caricamento helper systems
        $helperSystemsLoaded = true;
        $loadErrors = [];

        try {
            // Tenta di caricare tutti i helper systems
            if (!defined('TABLE_UTENTI')) {
                require_once __DIR__ . '/config/database_schema.php';
            }
            if (!defined('APP_NAME')) {
                require_once __DIR__ . '/config/app_config.php';
            }
            if (!defined('MSG_SUCCESS_LOGIN')) {
                require_once __DIR__ . '/config/messages.php';
            }
            if (!function_exists('e')) {
                require_once __DIR__ . '/config/helpers.php';
            }
            if (!class_exists('QueryBuilder')) {
                require_once __DIR__ . '/lib/QueryBuilder.php';
            }
            if (!class_exists('Validator')) {
                require_once __DIR__ . '/lib/Validator.php';
            }

            addCheck(
                'Caricamento Helper Systems',
                'ok',
                'Tutti i file helper caricati',
                'Nessun conflitto di ridichiarazione'
            );
        } catch (Throwable $e) {
            $helperSystemsLoaded = false;
            addCheck(
                'Caricamento Helper Systems',
                'fail',
                'Errore: ' . $e->getMessage(),
                'Verificare i file di configurazione'
            );
        }

        if ($helperSystemsLoaded) {
            // Verifica costanti database_schema
            $schemaConstants = ['TABLE_UTENTI', 'TABLE_EVENTI', 'COL_UTENTI_ID', 'RUOLO_ADMIN'];
            foreach ($schemaConstants as $const) {
                $exists = defined($const);
                addCheck(
                    "Costante $const",
                    $exists ? 'ok' : 'fail',
                    'database_schema.php',
                    $exists ? 'Definita' : 'Mancante'
                );
            }

            // Verifica costanti app_config
            $appConstants = ['APP_NAME', 'PASSWORD_MIN_LENGTH', 'MAX_TICKETS_PER_ORDER'];
            foreach ($appConstants as $const) {
                $exists = defined($const);
                addCheck(
                    "Costante $const",
                    $exists ? 'ok' : 'fail',
                    'app_config.php',
                    $exists ? 'Definita' : 'Mancante'
                );
            }

            // Verifica costanti messages
            $msgConstants = ['MSG_SUCCESS_LOGIN', 'ERR_INVALID_CSRF', 'ERR_PERMISSION_DENIED'];
            foreach ($msgConstants as $const) {
                $exists = defined($const);
                addCheck(
                    "Costante $const",
                    $exists ? 'ok' : 'fail',
                    'messages.php',
                    $exists ? 'Definita' : 'Mancante'
                );
            }

            // Verifica funzioni helper
            $helperFunctions = [
                'formatDate' => 'helpers.php',
                'formatTime' => 'helpers.php',
                'sanitize' => 'helpers.php',
                'message' => 'messages.php',
                'setSuccessMessage' => 'messages.php',
                'apiSuccess' => 'messages.php',
                'table' => 'QueryBuilder.php',
                'validate' => 'Validator.php'
            ];

            foreach ($helperFunctions as $func => $file) {
                $exists = function_exists($func);
                addCheck(
                    "Funzione $func()",
                    $exists ? 'ok' : 'fail',
                    $file,
                    $exists ? 'Definita e protetta' : 'Mancante'
                );
            }

            // Verifica classi
            $classes = ['QueryBuilder', 'Validator'];
            foreach ($classes as $class) {
                $exists = class_exists($class);
                addCheck(
                    "Classe $class",
                    $exists ? 'ok' : 'fail',
                    '',
                    $exists ? 'Definita correttamente' : 'Classe mancante'
                );
            }
        }
        ?>
    </div>

    <!-- VERIFICA FUNZIONI MODELLI -->
    <div class="section">
        <h2>⚙️ Funzioni Modelli</h2>
        <?php
        // Carica i file necessari
        $functionFiles = [
            'models/Permessi.php' => ['canEditEvento', 'inviteCollaborator', 'registerEventoCreator'],
            'models/EventoSettori.php' => ['setEventoSettori', 'getEventoSettori'],
            'models/Settore.php' => ['getAllSettori', 'getSettoreById', 'createSettore'],
            'models/Utente.php' => ['getAllUtenti', 'getUtenteById', 'createUtente'],
            'models/Location.php' => ['getAllLocations', 'getLocationById', 'getSettoriByLocation'],
        ];

        foreach ($functionFiles as $file => $functions) {
            if (file_exists(__DIR__ . '/' . $file)) {
                try {
                    require_once __DIR__ . '/' . $file;

                    foreach ($functions as $func) {
                        $exists = function_exists($func);
                        addCheck(
                            "Funzione $func()",
                            $exists ? 'ok' : 'fail',
                            "in $file",
                            $exists ? 'Definita' : 'Funzione mancante'
                        );
                    }
                } catch (Throwable $e) {
                    addCheck(
                        "Caricamento $file",
                        'fail',
                        'Errore: ' . $e->getMessage(),
                        'Verificare il file'
                    );
                }
            }
        }

        // Verifica classe EmailService
        if (file_exists(__DIR__ . '/lib/EmailService.php')) {
            try {
                require_once __DIR__ . '/lib/EmailService.php';
                $classExists = class_exists('EmailService');
                addCheck(
                    'Classe EmailService',
                    $classExists ? 'ok' : 'fail',
                    '',
                    $classExists ? 'Definita correttamente' : 'Classe mancante'
                );
            } catch (Throwable $e) {
                addCheck(
                    'Classe EmailService',
                    'fail',
                    'Errore: ' . $e->getMessage(),
                    'Verificare lib/EmailService.php'
                );
            }
        }
        ?>
    </div>

    <!-- RIEPILOGO -->
    <div class="summary">
        <h2>📊 Riepilogo</h2>
        <p style="font-size: 1.2em; margin: 10px 0;">
            ✅ <strong><?= $success ?></strong> OK &nbsp;&nbsp;
            ⚠️ <strong><?= $warnings ?></strong> Warning &nbsp;&nbsp;
            ❌ <strong><?= $errors ?></strong> Errori
        </p>
        <?php if ($errors === 0 && $warnings === 0): ?>
            <p style="font-size: 1.1em;">🎉 Installazione completa e funzionante!</p>
        <?php elseif ($errors === 0): ?>
            <p>✅ Sistema funzionante con alcuni avvisi da controllare</p>
        <?php else: ?>
            <p>⚠️ Ci sono errori da correggere prima di procedere</p>
        <?php endif; ?>
    </div>

    <!-- DETTAGLI CHECKS -->
    <?php foreach (['ok' => 'Verifiche Riuscite', 'warn' => 'Avvisi', 'fail' => 'Errori'] as $type => $title): ?>
        <?php $filtered = array_filter($checks, fn($c) => $c['status'] === $type); ?>
        <?php if (!empty($filtered)): ?>
            <div class="section">
                <h2>
                    <?php if ($type === 'ok') echo '✅'; elseif ($type === 'warn') echo '⚠️'; else echo '❌'; ?>
                    <?= $title ?> (<?= count($filtered) ?>)
                </h2>
                <?php foreach ($filtered as $check): ?>
                    <div class="check-item <?= $type === 'ok' ? 'success' : ($type === 'warn' ? 'warning' : 'error') ?>">
                        <div>
                            <strong><?= htmlspecialchars($check['name']) ?></strong>
                            <?php if ($check['message']): ?>
                                <span style="color: #7f8c8d;"> - <?= htmlspecialchars($check['message']) ?></span>
                            <?php endif; ?>
                            <?php if ($check['details']): ?>
                                <div class="details"><?= htmlspecialchars($check['details']) ?></div>
                            <?php endif; ?>
                        </div>
                        <span class="status <?= $type === 'ok' ? 'ok' : ($type === 'warn' ? 'warn' : 'fail') ?>">
                            <?= strtoupper($type) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- TEST CONFLITTI FUNZIONI -->
    <div class="section">
        <h2>🔍 Test Conflitti Funzioni</h2>
        <?php
        // Esegui test_conflicts.php se esiste
        if (file_exists(__DIR__ . '/test_conflicts.php')) {
            addCheck(
                'Script test_conflicts.php',
                'ok',
                'File presente',
                'Eseguire manualmente: php test_conflicts.php'
            );
        } else {
            addCheck(
                'Script test_conflicts.php',
                'warn',
                'File non trovato',
                'Opzionale - Per testare conflitti di ridichiarazione'
            );
        }

        // Verifica documentazione
        $docs = [
            'ARCHITECTURE.md' => 'Documentazione architettura completa',
            'INTEGRATION_SUMMARY.md' => 'Riepilogo integrazione helper systems',
            'FUNCTION_CONFLICT_PREVENTION.md' => 'Guida prevenzione conflitti',
            'CONFLICT_FIX_SUMMARY.md' => 'Riepilogo correzioni conflitti'
        ];

        foreach ($docs as $doc => $desc) {
            $exists = file_exists(__DIR__ . '/' . $doc);
            addCheck(
                $doc,
                $exists ? 'ok' : 'warn',
                $desc,
                $exists ? 'Documentazione presente' : 'Documentazione mancante (opzionale)'
            );
        }
        ?>
    </div>

    <!-- AZIONI CONSIGLIATE -->
    <div class="section">
        <h2>💡 Prossimi Passi</h2>
        <?php if ($errors > 0): ?>
            <p><strong>⚠️ Ci sono errori da risolvere:</strong></p>
            <ol>
                <li>Se mancano tabelle/colonne: eseguire <code>db/migrations/001_add_collaboration_system.sql</code></li>
                <li>Se manca config/database.php: copiare da config/database.example.php e configurare</li>
                <li>Se mancano file PHP: verificare che tutti i file siano stati caricati</li>
                <li>Se ci sono errori di caricamento helper: verificare <code>FUNCTION_CONFLICT_PREVENTION.md</code></li>
                <li>Eseguire test conflitti: <code>php test_conflicts.php</code></li>
            </ol>
        <?php else: ?>
            <p><strong>✅ Sistema pronto! Puoi procedere con:</strong></p>
            <ol>
                <li>Testare i conflitti: <code>php test_conflicts.php</code></li>
                <li>Popolare il database: <a href="db/reset_database.php">db/reset_database.php</a></li>
                <li>Testare il sistema: <a href="index.php">Homepage</a></li>
                <li>Login come admin: <a href="index.php?action=show_login">Accedi</a> con <code>admin@eventsmaster.it</code> / <code>password123</code></li>
                <li>Consultare la documentazione:
                    <ul>
                        <li><code>ARCHITECTURE.md</code> - Architettura completa</li>
                        <li><code>INTEGRATION_SUMMARY.md</code> - Riepilogo helper systems</li>
                        <li><code>FUNCTION_CONFLICT_PREVENTION.md</code> - Best practices</li>
                    </ul>
                </li>
            </ol>
        <?php endif; ?>

        <div style="margin-top: 20px; padding: 15px; background: #e8f4f8; border-left: 4px solid #3498db; border-radius: 4px;">
            <strong>📚 Novità Helper Systems:</strong>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li><strong>database_schema.php</strong> - Costanti centralizzate per tabelle e colonne</li>
                <li><strong>app_config.php</strong> - Configurazione applicazione centralizzata</li>
                <li><strong>messages.php</strong> - Messaggi e risposte API standardizzate</li>
                <li><strong>QueryBuilder</strong> - Interfaccia fluent per query database</li>
                <li><strong>Validator</strong> - Sistema validazione riutilizzabile</li>
            </ul>
            <p style="margin: 10px 0 0 0; font-size: 0.9em;">
                Tutti i controller e models sono stati aggiornati per utilizzare questi sistemi.
                Consulta <code>INTEGRATION_SUMMARY.md</code> per i dettagli.
            </p>
        </div>
    </div>

    <div style="text-align: center; margin-top: 40px; color: #7f8c8d;">
        <p><strong>EventsMaster v2.0</strong> - Sistema di Biglietteria Eventi</p>
        <p style="font-size: 0.9em;">Con Helper Systems integrati: QueryBuilder, Validator, Centralized Config</p>
        <p style="font-size: 0.85em; margin-top: 10px;">Questo script è disponibile solo su localhost per sicurezza</p>
    </div>
</body>
</html>
