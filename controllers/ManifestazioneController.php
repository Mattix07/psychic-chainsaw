<?php
/**
 * Controller Manifestazioni
 * Gestisce creazione e modifica delle manifestazioni da parte di promoter/admin/mod
 */

require_once __DIR__ . '/../config/database_schema.php';
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/messages.php';
require_once __DIR__ . '/../lib/Validator.php';
require_once __DIR__ . '/../lib/QueryBuilder.php';
require_once __DIR__ . '/../models/Manifestazione.php';
require_once __DIR__ . '/../models/Permessi.php';

/**
 * Mostra form creazione manifestazione
 */
function showCreateManifestazione(PDO $pdo): void
{
    requireRole(ROLE_PROMOTER);
    setPage('admin/manifestazione_form');
}

/**
 * Mostra form modifica manifestazione
 */
function showEditManifestazione(PDO $pdo): void
{
    requireRole(ROLE_PROMOTER);

    $id = (int) ($_GET['id'] ?? 0);
    $manifestazione = getManifestazioneById($pdo, $id);

    if (!$manifestazione) {
        redirect('index.php?action=list_manifestazioni', null, 'Manifestazione non trovata');
    }

    // Verifica permessi
    if (!canEditManifestazione($pdo, $_SESSION['user_id'], $id)) {
        redirect('index.php?action=list_manifestazioni', null, 'Non hai i permessi per modificare questa manifestazione');
    }

    $_SESSION['current_manifestazione'] = $manifestazione;
    setPage('admin/manifestazione_form');
}

/**
 * Salva manifestazione (crea o modifica)
 */
function saveManifestazione(PDO $pdo): void
{
    requireRole(ROLE_PROMOTER);

    if (!verifyCsrf()) {
        redirect('index.php?action=list_manifestazioni', null, 'Token non valido');
    }

    $id = (int) ($_POST['id'] ?? 0);
    $data = [
        'Nome' => sanitize($_POST['nome'] ?? ''),
        'Descrizione' => sanitize($_POST['descrizione'] ?? ''),
        'DataInizio' => $_POST['data_inizio'] ?? null,
        'DataFine' => $_POST['data_fine'] ?? null
    ];

    if (empty($data['Nome'])) {
        redirect('index.php?action=create_manifestazione', null, 'Il nome è obbligatorio');
    }

    try {
        if ($id > 0) {
            // Modifica
            if (!canEditManifestazione($pdo, $_SESSION['user_id'], $id)) {
                redirect('index.php?action=list_manifestazioni', null, 'Non hai i permessi');
            }

            updateManifestazione($pdo, $id, $data);
            redirect('index.php?action=list_manifestazioni', 'Manifestazione modificata con successo');
        } else {
            // Creazione
            $newId = createManifestazione($pdo, $data);

            // Registra come creatore
            registerManifestazioneCreator($pdo, $newId, $_SESSION['user_id']);

            redirect('index.php?action=list_manifestazioni', 'Manifestazione creata con successo');
        }
    } catch (Exception $e) {
        logError("Errore salvataggio manifestazione: " . $e->getMessage());
        redirect('index.php?action=list_manifestazioni', null, 'Errore durante il salvataggio');
    }
}

/**
 * Lista tutte le manifestazioni
 */
function listManifestazioni(PDO $pdo): void
{
    requireRole(ROLE_PROMOTER);

    $ruolo = $_SESSION['user_ruolo'] ?? RUOLO_USER;

    if (in_array($ruolo, [RUOLO_ADMIN, RUOLO_MOD])) {
        // Admin e mod vedono tutto
        $manifestazioni = getAllManifestazioni($pdo);
    } else {
        // Promoter vede solo le sue
        $manifestazioni = getManifestazioniByCreator($pdo, $_SESSION['user_id']);
    }

    $_SESSION['manifestazioni_list'] = $manifestazioni;
    setPage('admin/manifestazioni_list');
}

/**
 * Elimina manifestazione (solo admin/mod)
 */
function deleteManifestazioneAction(PDO $pdo): void
{
    requireRole(ROLE_MOD);

    if (!verifyCsrf()) {
        redirect('index.php?action=list_manifestazioni', null, 'Token non valido');
    }

    $id = (int) ($_POST['id'] ?? 0);

    try {
        deleteManifestazioneById($pdo, $id);
        redirect('index.php?action=list_manifestazioni', 'Manifestazione eliminata con successo');
    } catch (Exception $e) {
        logError("Errore eliminazione manifestazione: " . $e->getMessage());
        redirect('index.php?action=list_manifestazioni', null, 'Errore durante l\'eliminazione');
    }
}
