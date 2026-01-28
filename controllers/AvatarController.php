<?php
/**
 * Controller Avatar
 * Gestisce upload e modifica avatar utente
 */

require_once __DIR__ . '/../config/database_schema.php';
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/messages.php';
require_once __DIR__ . '/../lib/Validator.php';
require_once __DIR__ . '/../lib/QueryBuilder.php';

/**
 * Upload avatar utente
 * POST: avatar (file)
 * Limiti:
 * - Max 2MB
 * - Solo immagini: jpg, jpeg, png, gif
 * - Risoluzione massima: 1024x1024
 */
function uploadAvatarApi(PDO $pdo): void
{
    if (!isLoggedIn()) {
        jsonResponse(apiError(ERR_LOGIN_REQUIRED, 401));
        return;
    }

    if (!verifyCsrf()) {
        jsonResponse(apiError(ERR_INVALID_CSRF, 403));
        return;
    }

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(apiError(ERR_UPLOAD_FAILED, 400));
        return;
    }

    $file = $_FILES['avatar'];

    // Validazione dimensione usando costante da app_config
    if ($file['size'] > AVATAR_MAX_SIZE) {
        jsonResponse(apiError(message(ERR_FILE_TOO_LARGE, AVATAR_MAX_SIZE / 1024 / 1024), 400));
        return;
    }

    // Validazione tipo MIME usando costanti
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, AVATAR_ALLOWED_TYPES)) {
        jsonResponse(apiError(ERR_INVALID_FILE_TYPE, 400));
        return;
    }

    // Validazione dimensioni immagine
    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        jsonResponse(apiError(ERR_INVALID_IMAGE, 400));
        return;
    }

    list($width, $height) = $imageInfo;
    $maxDimension = AVATAR_MAX_DIMENSION;

    // Ridimensiona se necessario
    if ($width > $maxDimension || $height > $maxDimension) {
        $image = resizeImage($file['tmp_name'], $mimeType, $maxDimension);
        if (!$image) {
            jsonResponse(['error' => 'Errore durante il ridimensionamento'], 500);
            return;
        }
    } else {
        $image = file_get_contents($file['tmp_name']);
    }

    // Salva nel database usando QueryBuilder
    try {
        table($pdo, TABLE_UTENTI)
            ->where(COL_UTENTI_ID, $_SESSION['user_id'])
            ->update([COL_UTENTI_AVATAR => $image]);

        jsonResponse(apiSuccess([
            'avatarUrl' => 'index.php?action=get_avatar&id=' . $_SESSION['user_id']
        ], MSG_SUCCESS_AVATAR_UPDATED, 200));
    } catch (Exception $e) {
        jsonResponse(apiError(ERR_GENERIC, 500));
    }
}

/**
 * Ottieni avatar utente
 * GET: id
 */
function getAvatarApi(PDO $pdo): void
{
    // Validazione con Validator
    $validator = validate($_GET)->required('id')->numeric('id');
    if ($validator->fails()) {
        http_response_code(400);
        exit;
    }

    $userId = (int) $_GET['id'];

    // Query usando QueryBuilder
    $user = table($pdo, TABLE_UTENTI)
        ->select([COL_UTENTI_AVATAR])
        ->where(COL_UTENTI_ID, $userId)
        ->first();

    if ($user && $user[COL_UTENTI_AVATAR]) {
        header('Content-Type: image/jpeg');
        header('Cache-Control: max-age=' . AVATAR_CACHE_DURATION);
        echo $user[COL_UTENTI_AVATAR];
    } else {
        // Avatar predefinito
        header('Location: ' . DEFAULT_AVATAR_PATH);
    }
    exit;
}

/**
 * Elimina avatar utente
 * POST
 */
function deleteAvatarApi(PDO $pdo): void
{
    if (!isLoggedIn()) {
        jsonResponse(apiError(ERR_LOGIN_REQUIRED, 401));
        return;
    }

    if (!verifyCsrf()) {
        jsonResponse(apiError(ERR_INVALID_CSRF, 403));
        return;
    }

    try {
        table($pdo, TABLE_UTENTI)
            ->where(COL_UTENTI_ID, $_SESSION['user_id'])
            ->update([COL_UTENTI_AVATAR => null]);

        jsonResponse(apiSuccess(null, MSG_SUCCESS_AVATAR_DELETED, 200));
    } catch (Exception $e) {
        jsonResponse(apiError(ERR_GENERIC, 500));
    }
}

/**
 * Ridimensiona immagine mantenendo aspect ratio
 */
function resizeImage(string $filePath, string $mimeType, int $maxDimension): ?string
{
    // Carica immagine in base al tipo
    switch ($mimeType) {
        case 'image/jpeg':
        case 'image/jpg':
            $source = imagecreatefromjpeg($filePath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($filePath);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($filePath);
            break;
        default:
            return null;
    }

    if (!$source) return null;

    $width = imagesx($source);
    $height = imagesy($source);

    // Calcola nuove dimensioni
    if ($width > $height) {
        $newWidth = $maxDimension;
        $newHeight = (int)(($height / $width) * $maxDimension);
    } else {
        $newHeight = $maxDimension;
        $newWidth = (int)(($width / $height) * $maxDimension);
    }

    // Crea nuova immagine
    $dest = imagecreatetruecolor($newWidth, $newHeight);

    // Mantieni trasparenza per PNG e GIF
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 255, 255, 255, 127);
        imagefilledrectangle($dest, 0, 0, $newWidth, $newHeight, $transparent);
    }

    // Ridimensiona
    imagecopyresampled($dest, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Converti in BLOB
    ob_start();
    imagejpeg($dest, null, 90);
    $imageData = ob_get_clean();

    imagedestroy($source);
    imagedestroy($dest);

    return $imageData;
}

function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
