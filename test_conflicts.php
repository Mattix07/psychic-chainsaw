<?php
/**
 * Script di Test per Conflitti di Funzioni
 * 
 * Testa che tutti i file si carichino correttamente senza errori di ridichiarazione
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Test Caricamento File Helper ===\n\n";

// Simula sessione
if (!isset($_SESSION)) {
    session_start();
}

$files = [
    'config/database_schema.php',
    'config/app_config.php',
    'config/messages.php',
    'config/helpers.php',
    'lib/QueryBuilder.php',
    'lib/Validator.php'
];

$errors = [];
$success = [];

foreach ($files as $file) {
    echo "Caricamento: $file ... ";
    
    try {
        require_once __DIR__ . '/' . $file;
        echo "✅ OK\n";
        $success[] = $file;
    } catch (Throwable $e) {
        echo "❌ ERRORE\n";
        $errors[] = [
            'file' => $file,
            'error' => $e->getMessage(),
            'line' => $e->getLine()
        ];
    }
}

echo "\n=== Risultati ===\n\n";
echo "File caricati correttamente: " . count($success) . "/" . count($files) . "\n";

if (!empty($errors)) {
    echo "\n❌ ERRORI TROVATI:\n\n";
    foreach ($errors as $error) {
        echo "File: {$error['file']}\n";
        echo "Errore: {$error['error']}\n";
        echo "Riga: {$error['line']}\n\n";
    }
    exit(1);
} else {
    echo "\n✅ Tutti i file caricati senza conflitti!\n";
    
    // Test funzioni principali
    echo "\n=== Test Funzioni ===\n\n";
    
    $functions = [
        'formatDate', 'formatTime', 'formatDateTime',
        'message', 'setSuccessMessage', 'setErrorMessage',
        'apiSuccess', 'apiError',
        'table', 'validate',
        'buildSelect', 'buildWhere'
    ];
    
    foreach ($functions as $func) {
        if (function_exists($func)) {
            echo "✅ $func() definita\n";
        } else {
            echo "❌ $func() NON definita\n";
        }
    }
    
    exit(0);
}
