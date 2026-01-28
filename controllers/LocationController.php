<?php
/**
 * Controller Location
 * Gestisce creazione e modifica delle location da parte di promoter/admin/mod
 */

require_once __DIR__ . '/../config/database_schema.php';
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/messages.php';
require_once __DIR__ . '/../lib/Validator.php';
require_once __DIR__ . '/../lib/QueryBuilder.php';
require_once __DIR__ . '/../models/Location.php';
require_once __DIR__ . '/../models/Permessi.php';

/**
 * Mostra form creazione location
 */
function showCreateLocation(PDO $pdo): void
{
    requireRole(ROLE_PROMOTER);
    setPage('admin/location_form');
}

/**
 * Mostra form modifica location
 */
function showEditLocation(PDO $pdo): void
{
    requireRole(ROLE_PROMOTER);

    $id = (int) ($_GET['id'] ?? 0);
    $location = getLocationById($pdo, $id);

    if (!$location) {
        redirect('index.php?action=list_locations', null, 'Location non trovata');
    }

    // Verifica permessi
    if (!canEditLocation($pdo, $_SESSION['user_id'], $id)) {
        redirect('index.php?action=list_locations', null, 'Non hai i permessi per modificare questa location');
    }

    $_SESSION['current_location'] = $location;
    setPage('admin/location_form');
}

/**
 * Salva location (crea o modifica)
 */
function saveLocation(PDO $pdo): void
{
    requireRole(ROLE_PROMOTER);

    if (!verifyCsrf()) {
        redirect('index.php?action=list_locations', null, 'Token non valido');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $data = [
        'Nome' => sanitize($_POST['nome'] ?? ''),
        'Indirizzo' => sanitize($_POST['indirizzo'] ?? ''),
        'Citta' => sanitize($_POST['citta'] ?? ''),
        'CAP' => sanitize($_POST['cap'] ?? ''),
        'Regione' => sanitize($_POST['regione'] ?? ''),
        'Capienza' => (int) ($_POST['capienza'] ?? 0)
    ];

    if (empty($data['Nome']) || empty($data['Citta'])) {
        redirect('index.php?action=create_location', null, 'Nome e città sono obbligatori');
    }

    try {
        if ($id > 0) {
            // Modifica
            if (!canEditLocation($pdo, $_SESSION['user_id'], $id)) {
                redirect('index.php?action=list_locations', null, 'Non hai i permessi');
            }

            updateLocation($pdo, $id, $data);
            redirect('index.php?action=list_locations', 'Location modificata con successo');
        } else {
            // Creazione
            $newId = createLocation($pdo, $data);

            // Registra come creatore
            registerLocationCreator($pdo, $newId, $_SESSION['user_id']);

            redirect('index.php?action=list_locations', 'Location creata con successo');
        }
    } catch (Exception $e) {
        logError("Errore salvataggio location: " . $e->getMessage());
        redirect('index.php?action=list_locations', null, 'Errore durante il salvataggio');
    }
}

/**
 * Lista tutte le location
 */
function listLocations(PDO $pdo): void
{
    requireRole(ROLE_PROMOTER);

    $ruolo = $_SESSION['user_ruolo'] ?? RUOLO_USER;

    if (in_array($ruolo, [RUOLO_ADMIN, RUOLO_MOD])) {
        // Admin e mod vedono tutto
        $locations = getAllLocations($pdo);
    } else {
        // Promoter vede solo le sue
        $locations = getLocationsByCreator($pdo, $_SESSION['user_id']);
    }

    $_SESSION['locations_list'] = $locations;
    setPage('admin/locations_list');
}

/**
 * Elimina location (solo admin/mod)
 */
function deleteLocationAction(PDO $pdo): void
{
    requireRole(ROLE_MOD);

    if (!verifyCsrf()) {
        redirect('index.php?action=list_locations', null, 'Token non valido');
    }

    $id = (int) ($_POST['id'] ?? 0);

    try {
        deleteLocationById($pdo, $id);
        redirect('index.php?action=list_locations', 'Location eliminata con successo');
    } catch (Exception $e) {
        logError("Errore eliminazione location: " . $e->getMessage());
        redirect('index.php?action=list_locations', null, 'Errore durante l\'eliminazione');
    }
}
