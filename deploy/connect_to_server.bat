@echo off
REM ============================================================================
REM Script di connessione rapida al server Ubuntu
REM ============================================================================
REM
REM Questo script batch semplifica la connessione SSH al server.
REM Richiede OpenSSH Client installato su Windows 10/11 (di solito gia presente)
REM
REM Uso: Doppio click su questo file
REM ============================================================================

echo ========================================
echo EventsMaster - Connessione Server
echo ========================================
echo.
echo Server: 192.168.1.50
echo User:   root
echo.
echo Connessione in corso...
echo.

REM Connetti al server via SSH
ssh root@192.168.1.50

REM Se la connessione fallisce
if errorlevel 1 (
    echo.
    echo ========================================
    echo ERRORE: Connessione fallita
    echo ========================================
    echo.
    echo Possibili cause:
    echo - Server non raggiungibile
    echo - OpenSSH Client non installato su Windows
    echo - Password errata
    echo.
    echo Per installare OpenSSH Client su Windows:
    echo 1. Impostazioni ^> App ^> Funzionalita facoltative
    echo 2. Aggiungi funzionalita
    echo 3. Cerca "OpenSSH Client"
    echo 4. Installa
    echo.
    pause
)
