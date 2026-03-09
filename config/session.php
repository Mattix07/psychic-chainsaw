<?php
// Configurazione sessione
ini_set('session.gc_maxlifetime', 7200);
ini_set('session.cookie_lifetime', 7200);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', 1);
// ini_set('session.cookie_secure', 1); // Attivare in produzione con HTTPS

session_start();
