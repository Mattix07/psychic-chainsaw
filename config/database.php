<?php
/**
 * Configurazione e connessione al database MySQL
 * Inizializza la connessione PDO con parametri sicuri
 */

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../models/Utente.php';

try {
    $pdo = new PDO(
        sprintf(
            "mysql:host=%s;dbname=%s;charset=%s",
            env('DB_HOST', 'localhost'),
            env('DB_NAME', '5cit_eventsMaster'),
            env('DB_CHARSET', 'utf8mb4')
        ),
        env('DB_USER', 'root'),
        env('DB_PASS', ''),
        [
            // Lancia eccezioni per errori SQL invece di fallire silenziosamente
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Restituisce risultati come array associativi
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Disabilita prepared statements emulati per maggiore sicurezza
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    logError("Database connection failed: " . $e->getMessage());

    // Mostra dettagli errore solo in ambiente di sviluppo
    if (env('APP_DEBUG', false)) {
        die("Errore DB: " . $e->getMessage());
    }

    die("Errore di connessione al database. Riprova più tardi.");
}
