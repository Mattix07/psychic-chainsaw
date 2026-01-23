<?php
/**
 * CartController
 * Gestisce le API del carrello lato server
 *
 * Il carrello è salvato nel DB come biglietti con Stato='carrello'
 * Questo permette di:
 * - Mantenere il carrello anche dopo logout/scadenza sessione
 * - Verificare disponibilità biglietti in tempo reale
 * - Limitare il numero di biglietti per evento
 */

require_once __DIR__ . '/../models/Biglietto.php';
require_once __DIR__ . '/../models/Evento.php';
require_once __DIR__ . '/../config/helpers.php';

/**
 * Router per le azioni del carrello
 */
function handleCart(PDO $pdo, string $action): void
{
    // Imposta header JSON per le risposte API
    header('Content-Type: application/json');

    switch ($action) {
        case 'cart_add':
            addToCartApi($pdo);
            break;
        case 'cart_get':
            getCartApi($pdo);
            break;
        case 'cart_update':
            updateCartApi($pdo);
            break;
        case 'cart_remove':
            removeFromCartApi($pdo);
            break;
        case 'cart_clear':
            clearCartApi($pdo);
            break;
        case 'cart_count':
            getCartCountApi($pdo);
            break;
        case 'check_availability':
            checkAvailabilityApi($pdo);
            break;
        case 'get_settori':
            getSettoriApi($pdo);
            break;
        case 'cart_update_settore':
            updateSettoreApi($pdo);
            break;
        default:
            jsonResponse(['error' => 'Azione non valida'], 400);
    }
}

/**
 * Aggiunge un biglietto al carrello
 * POST: idEvento, idClasse, (quantita)
 */
function addToCartApi(PDO $pdo): void
{
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'Devi effettuare il login'], 401);
        return;
    }

    if (!verifyCsrf()) {
        jsonResponse(['error' => 'Token CSRF non valido'], 403);
        return;
    }

    $idEvento = (int) ($_POST['idEvento'] ?? 0);
    $idClasse = trim(sanitize($_POST['idClasse'] ?? 'Standard'));
    $quantita = (int) ($_POST['quantita'] ?? 1);
    $idSettore = (int) ($_POST['idSettore'] ?? 0);
    $idUtente = $_SESSION['user_id'];

    if ($idEvento <= 0) {
        jsonResponse(['error' => 'Evento non valido'], 400);
        return;
    }

    // Verifica che il tipo biglietto esista
    $tipo = getTipoByNome($pdo, $idClasse);
    if (!$tipo) {
        // Prova con Standard come fallback
        $idClasse = 'Standard';
        $tipo = getTipoByNome($pdo, $idClasse);
        if (!$tipo) {
            jsonResponse(['error' => 'Tipo biglietto non valido: ' . $idClasse], 400);
            return;
        }
    }

    // Verifica che l'evento esista
    $evento = getEventoById($pdo, $idEvento);
    if (!$evento) {
        jsonResponse(['error' => 'Evento non trovato'], 404);
        return;
    }

    // Verifica disponibilità
    if (!checkDisponibilitaBiglietti($pdo, $idEvento, $quantita)) {
        $disponibili = getBigliettiDisponibili($pdo, $idEvento);
        jsonResponse([
            'error' => 'Biglietti non disponibili',
            'disponibili' => $disponibili,
            'richiesti' => $quantita
        ], 400);
        return;
    }

    // Aggiungi i biglietti al carrello
    $bigliettiIds = [];
    try {
        $pdo->beginTransaction();

        for ($i = 0; $i < $quantita; $i++) {
            $id = addBigliettoToCart($pdo, $idEvento, $idClasse, $idUtente, $idSettore > 0 ? $idSettore : null);
            $bigliettiIds[] = $id;
        }

        $pdo->commit();

        // Recupera il carrello aggiornato
        $cart = getCartByUtente($pdo, $idUtente);
        $count = countCartItems($pdo, $idUtente);

        jsonResponse([
            'success' => true,
            'message' => $quantita . ' bigliett' . ($quantita > 1 ? 'i aggiunti' : 'o aggiunto') . ' al carrello',
            'bigliettiIds' => $bigliettiIds,
            'cartCount' => $count,
            'cart' => formatCartForJs($cart)
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['error' => 'Errore durante l\'aggiunta al carrello: ' . $e->getMessage()], 500);
    }
}

/**
 * Recupera il carrello dell'utente
 */
function getCartApi(PDO $pdo): void
{
    if (!isLoggedIn()) {
        jsonResponse(['cart' => [], 'count' => 0]);
        return;
    }

    $idUtente = $_SESSION['user_id'];
    $cart = getCartByUtente($pdo, $idUtente);
    $count = countCartItems($pdo, $idUtente);

    jsonResponse([
        'cart' => formatCartForJs($cart),
        'count' => $count,
        'total' => array_sum(array_column($cart, 'PrezzoFinale'))
    ]);
}

/**
 * Aggiorna un biglietto nel carrello (nome, cognome, tipo)
 * POST: idBiglietto, (nome, cognome, sesso, idClasse)
 */
function updateCartApi(PDO $pdo): void
{
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'Devi effettuare il login'], 401);
        return;
    }

    if (!verifyCsrf()) {
        jsonResponse(['error' => 'Token CSRF non valido'], 403);
        return;
    }

    $idBiglietto = (int) ($_POST['idBiglietto'] ?? 0);
    $idUtente = $_SESSION['user_id'];

    if ($idBiglietto <= 0) {
        jsonResponse(['error' => 'Biglietto non valido'], 400);
        return;
    }

    // Aggiorna dati partecipante se forniti
    if (isset($_POST['nome']) || isset($_POST['cognome'])) {
        $nome = sanitize($_POST['nome'] ?? '');
        $cognome = sanitize($_POST['cognome'] ?? '');
        $sesso = sanitize($_POST['sesso'] ?? 'Altro');

        updateBigliettoCart($pdo, $idBiglietto, $nome, $cognome, $sesso);
    }

    // Aggiorna tipo biglietto se fornito
    if (isset($_POST['idClasse'])) {
        $idClasse = sanitize($_POST['idClasse']);
        updateBigliettoTipo($pdo, $idBiglietto, $idClasse);
    }

    // Recupera carrello aggiornato
    $cart = getCartByUtente($pdo, $idUtente);

    jsonResponse([
        'success' => true,
        'cart' => formatCartForJs($cart)
    ]);
}

/**
 * Rimuove un biglietto dal carrello
 * POST: idBiglietto
 */
function removeFromCartApi(PDO $pdo): void
{
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'Devi effettuare il login'], 401);
        return;
    }

    if (!verifyCsrf()) {
        jsonResponse(['error' => 'Token CSRF non valido'], 403);
        return;
    }

    $idBiglietto = (int) ($_POST['idBiglietto'] ?? 0);
    $idUtente = $_SESSION['user_id'];

    if ($idBiglietto <= 0) {
        jsonResponse(['error' => 'Biglietto non valido'], 400);
        return;
    }

    $result = removeFromCart($pdo, $idBiglietto, $idUtente);

    if ($result) {
        $cart = getCartByUtente($pdo, $idUtente);
        $count = countCartItems($pdo, $idUtente);

        jsonResponse([
            'success' => true,
            'message' => 'Biglietto rimosso dal carrello',
            'cart' => formatCartForJs($cart),
            'cartCount' => $count
        ]);
    } else {
        jsonResponse(['error' => 'Impossibile rimuovere il biglietto'], 400);
    }
}

/**
 * Svuota il carrello
 */
function clearCartApi(PDO $pdo): void
{
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'Devi effettuare il login'], 401);
        return;
    }

    if (!verifyCsrf()) {
        jsonResponse(['error' => 'Token CSRF non valido'], 403);
        return;
    }

    $idUtente = $_SESSION['user_id'];
    clearCart($pdo, $idUtente);

    jsonResponse([
        'success' => true,
        'message' => 'Carrello svuotato',
        'cart' => [],
        'cartCount' => 0
    ]);
}

/**
 * Conta gli elementi nel carrello
 */
function getCartCountApi(PDO $pdo): void
{
    if (!isLoggedIn()) {
        jsonResponse(['count' => 0]);
        return;
    }

    $idUtente = $_SESSION['user_id'];
    $count = countCartItems($pdo, $idUtente);

    jsonResponse(['count' => $count]);
}

/**
 * Verifica disponibilità biglietti per un evento
 * GET/POST: idEvento, (quantita)
 */
function checkAvailabilityApi(PDO $pdo): void
{
    $idEvento = (int) ($_REQUEST['idEvento'] ?? 0);
    $quantita = (int) ($_REQUEST['quantita'] ?? 1);

    if ($idEvento <= 0) {
        jsonResponse(['error' => 'Evento non valido'], 400);
        return;
    }

    $disponibili = getBigliettiDisponibili($pdo, $idEvento);
    $disponibile = checkDisponibilitaBiglietti($pdo, $idEvento, $quantita);

    jsonResponse([
        'disponibile' => $disponibile,
        'rimanenti' => $disponibili,
        'illimitati' => $disponibili === null,
        'richiesti' => $quantita
    ]);
}

/**
 * Recupera i settori disponibili per un evento
 * GET: idEvento
 */
function getSettoriApi(PDO $pdo): void
{
    $idEvento = (int) ($_REQUEST['idEvento'] ?? 0);

    if ($idEvento <= 0) {
        jsonResponse(['error' => 'Evento non valido'], 400);
        return;
    }

    // Recupera la location dell'evento
    require_once __DIR__ . '/../models/Evento.php';
    require_once __DIR__ . '/../models/Location.php';

    $evento = getEventoById($pdo, $idEvento);
    if (!$evento) {
        jsonResponse(['error' => 'Evento non trovato'], 404);
        return;
    }

    $settori = getSettoriByLocation($pdo, $evento['idLocation']);

    jsonResponse([
        'settori' => array_map(function($s) {
            return [
                'id' => $s['id'],
                'nome' => $s['Nome'],
                'posti' => $s['PostiDisponibili'],
                'moltiplicatore' => (float) $s['MoltiplicatorePrezzo']
            ];
        }, $settori)
    ]);
}

/**
 * Aggiorna il settore di un biglietto nel carrello
 * POST: idBiglietto, idSettore
 */
function updateSettoreApi(PDO $pdo): void
{
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'Devi effettuare il login'], 401);
        return;
    }

    if (!verifyCsrf()) {
        jsonResponse(['error' => 'Token CSRF non valido'], 403);
        return;
    }

    $idBiglietto = (int) ($_POST['idBiglietto'] ?? 0);
    $idSettore = (int) ($_POST['idSettore'] ?? 0);
    $idUtente = $_SESSION['user_id'];

    if ($idBiglietto <= 0 || $idSettore <= 0) {
        jsonResponse(['error' => 'Parametri non validi'], 400);
        return;
    }

    // Verifica che il biglietto appartenga all'utente e sia nel carrello
    $stmt = $pdo->prepare("SELECT id, idEvento FROM Biglietti WHERE id = ? AND idUtente = ? AND Stato = 'carrello'");
    $stmt->execute([$idBiglietto, $idUtente]);
    $biglietto = $stmt->fetch();

    if (!$biglietto) {
        jsonResponse(['error' => 'Biglietto non trovato'], 404);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Rimuovi eventuale assegnazione precedente
        $stmt = $pdo->prepare("DELETE FROM Settore_Biglietti WHERE idBiglietto = ?");
        $stmt->execute([$idBiglietto]);

        // Assegna nuovo posto nel settore selezionato
        assegnaPostoInSettore($pdo, $idBiglietto, $biglietto['idEvento'], $idSettore);

        $pdo->commit();

        // Recupera carrello aggiornato
        $cart = getCartByUtente($pdo, $idUtente);

        jsonResponse([
            'success' => true,
            'message' => 'Settore aggiornato',
            'cart' => formatCartForJs($cart)
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['error' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()], 500);
    }
}

/**
 * Formatta il carrello per il frontend JavaScript
 * Include info settore e posto assegnato
 */
function formatCartForJs(array $cart): array
{
    return array_map(function($item) {
        return [
            'id' => $item['id'],
            'idEvento' => $item['idEvento'],
            'eventoNome' => $item['EventoNome'],
            'eventoData' => $item['Data'],
            'idClasse' => $item['idClasse'],
            'prezzo' => (float) $item['PrezzoFinale'],
            'prezzoBase' => (float) $item['PrezzoNoMod'],
            'modificatore' => (float) $item['ModificatorePrezzo'],
            'moltiplicatoreSettore' => isset($item['MoltiplicatorePrezzo']) ? (float) $item['MoltiplicatorePrezzo'] : 1,
            'idSettore' => $item['idSettore'] ?? null,
            'fila' => $item['Fila'] ?? null,
            'postoNumero' => $item['PostoNumero'] ?? null,
            'nome' => $item['Nome'],
            'cognome' => $item['Cognome'],
            'sesso' => $item['Sesso'],
            'dataCarrello' => $item['DataCarrello'],
            'image' => $item['Immagine'] ? 'data:image/jpeg;base64,' . base64_encode($item['Immagine']) : null
        ];
    }, $cart);
}

/**
 * Invia risposta JSON
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}
