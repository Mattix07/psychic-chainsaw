<?php
/**
 * Controller Ordini
 * Gestisce visualizzazione storico ordini e dettaglio singolo ordine
 *
 * Gli ordini sono accessibili solo dall'utente proprietario.
 * Ogni ordine contiene uno o piu biglietti acquistati.
 */

require_once __DIR__ . '/../models/Ordine.php';
require_once __DIR__ . '/../models/Biglietto.php';

/**
 * Mostra lo storico ordini dell'utente corrente
 * Richiede autenticazione
 */
function showOrdiniUtente(PDO $pdo): void
{
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Devi effettuare il login.';
        header('Location: index.php?action=show_login');
        exit;
    }

    $ordini = getOrdiniByUtente($pdo, $_SESSION['user_id']);
    $_SESSION['ordini_utente'] = $ordini;
    setPage('miei_ordini');
}

/**
 * Mostra il dettaglio di un ordine con i biglietti associati
 * Verifica che l'ordine appartenga all'utente corrente
 */
function viewOrdine(PDO $pdo): void
{
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'Devi effettuare il login.';
        header('Location: index.php?action=show_login');
        exit;
    }

    $id = (int) ($_GET['id'] ?? 0);
    $ordine = getOrdineById($pdo, $id);

    if (!$ordine) {
        $_SESSION['error'] = 'Ordine non trovato.';
        header('Location: index.php?action=miei_ordini');
        exit;
    }

    // Controllo autorizzazione: l'ordine deve appartenere all'utente
    if (!isOrdineOfUtente($pdo, $id, $_SESSION['user_id'])) {
        $_SESSION['error'] = 'Non hai accesso a questo ordine.';
        header('Location: index.php?action=miei_ordini');
        exit;
    }

    $biglietti = getBigliettiByOrdine($pdo, $id);

    $_SESSION['ordine_corrente'] = $ordine;
    $_SESSION['biglietti_ordine'] = $biglietti;
    setPage('ordine_dettaglio');
}
