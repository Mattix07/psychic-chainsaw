<?php

/**
 * Controller per la gestione degli Eventi
 */

require_once __DIR__ . '/../models/Evento.php';
require_once __DIR__ . '/../models/Manifestazione.php';
require_once __DIR__ . '/../models/Location.php';
require_once __DIR__ . '/../models/Recensione.php';

function handleEvento(PDO $pdo, string $action): void
{
    switch ($action) {
        case 'view_evento':
            viewEvento($pdo);
            break;

        case 'list_eventi':
            listEventi($pdo);
            break;

        case 'search_eventi':
            searchEventi($pdo);
            break;

        case 'create_evento':
            requireAdmin();
            createEventoAction($pdo);
            break;

        case 'update_evento':
            requireAdmin();
            updateEventoAction($pdo);
            break;

        case 'delete_evento':
            requireAdmin();
            deleteEventoAction($pdo);
            break;
    }
}

function viewEvento(PDO $pdo): void
{
    $id = (int) ($_GET['id'] ?? 0);

    if ($id <= 0) {
        redirect('index.php', null, 'Evento non valido');
    }

    $evento = getEventoById($pdo, $id);

    if (!$evento) {
        redirect('index.php', null, 'Evento non trovato');
    }

    $_SESSION['evento_corrente'] = $evento;
    $_SESSION['intrattenitori_evento'] = getIntrattenitoriEvento($pdo, $id);
    $_SESSION['recensioni_evento'] = getRecensioniByEvento($pdo, $id);
    $_SESSION['media_voti'] = getMediaVotiEvento($pdo, $id);
    setPage('evento_dettaglio');
}

function listEventi(PDO $pdo): void
{
    $_SESSION['eventi'] = getAllEventi($pdo);
    setPage('eventi_lista');
}

function searchEventi(PDO $pdo): void
{
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        redirect('index.php', null, 'Richiesta non valida');
    }

    $query = sanitize($_POST['query'] ?? $_POST['nome_manifestazione'] ?? '');

    if (empty($query)) {
        redirect('index.php', null, 'Inserisci un termine di ricerca');
    }

    $_SESSION['eventi_ricerca'] = searchEventiByQuery($pdo, $query);
    $_SESSION['ricerca_nome'] = $query;
    setPage('eventi_ricerca');
}

function listByCategory(PDO $pdo, string $category): void
{
    // Mappa alle categorie nel database (minuscolo)
    $validCategories = ['concerti', 'teatro', 'sport', 'eventi'];
    $categoria = strtolower($category);

    if (in_array($categoria, $validCategories)) {
        $_SESSION['eventi'] = getEventiByTipo($pdo, $categoria);
        $_SESSION['categoria_nome'] = ucfirst($categoria);
    } else {
        $_SESSION['eventi'] = getAllEventi($pdo);
        $_SESSION['categoria_nome'] = 'Tutti gli eventi';
    }

    setPage('eventi_lista');
}

function createEventoAction(PDO $pdo): void
{
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        redirect('index.php', null, 'Richiesta non valida');
    }

    $data = [
        'idManifestazione' => (int) $_POST['idManifestazione'],
        'idLocation' => (int) $_POST['idLocation'],
        'Nome' => sanitize($_POST['Nome']),
        'PrezzoNoMod' => (float) $_POST['PrezzoNoMod'],
        'Data' => $_POST['Data'],
        'OraI' => $_POST['OraI'],
        'OraF' => $_POST['OraF'],
        'Programma' => sanitize($_POST['Programma'] ?? '')
    ];

    try {
        $id = createEvento($pdo, $data);
        redirect('index.php?action=view_evento&id=' . $id, 'Evento creato con successo');
    } catch (Exception $e) {
        logError("Errore creazione evento: " . $e->getMessage());
        redirect('index.php', null, 'Errore durante la creazione dell\'evento');
    }
}

function updateEventoAction(PDO $pdo): void
{
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        redirect('index.php', null, 'Richiesta non valida');
    }

    $id = (int) $_POST['id'];
    $data = [
        'idManifestazione' => (int) $_POST['idManifestazione'],
        'idLocation' => (int) $_POST['idLocation'],
        'Nome' => sanitize($_POST['Nome']),
        'PrezzoNoMod' => (float) $_POST['PrezzoNoMod'],
        'Data' => $_POST['Data'],
        'OraI' => $_POST['OraI'],
        'OraF' => $_POST['OraF'],
        'Programma' => sanitize($_POST['Programma'] ?? '')
    ];

    try {
        updateEvento($pdo, $id, $data);
        redirect('index.php?action=view_evento&id=' . $id, 'Evento aggiornato con successo');
    } catch (Exception $e) {
        logError("Errore aggiornamento evento: " . $e->getMessage());
        redirect('index.php', null, 'Errore durante l\'aggiornamento dell\'evento');
    }
}

function deleteEventoAction(PDO $pdo): void
{
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        redirect('index.php', null, 'Richiesta non valida');
    }

    $id = (int) $_POST['id'];

    try {
        deleteEvento($pdo, $id);
        redirect('index.php?action=list_eventi', 'Evento eliminato con successo');
    } catch (Exception $e) {
        logError("Errore eliminazione evento: " . $e->getMessage());
        redirect('index.php', null, 'Errore durante l\'eliminazione dell\'evento');
    }
}
