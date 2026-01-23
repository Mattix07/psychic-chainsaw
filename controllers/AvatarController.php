<?php
/**
 * Controller Avatar
 * Gestisce upload e modifica avatar utente
 */

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
        jsonResponse(['error' => 'Devi effettuare il login'], 401);
        return;
    }

    if (!verifyCsrf()) {
        jsonResponse(['error' => 'Token CSRF non valido'], 403);
        return;
    }

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['error' => 'Errore durante l\'upload del file'], 400);
        return;
    }

    $file = $_FILES['avatar'];

    // Validazione dimensione (max 2MB)
    $maxSize = 2 * 1024 * 1024; // 2MB
    if ($file['size'] > $maxSize) {
        jsonResponse(['error' => 'Il file è troppo grande (max 2MB)'], 400);
        return;
    }

    // Validazione tipo MIME
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        jsonResponse(['error' => 'Formato file non valido (solo JPG, PNG, GIF)'], 400);
        return;
    }

    // Validazione dimensioni immagine
    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo) {
        jsonResponse(['error' => 'File non valido'], 400);
        return;
    }

    list($width, $height) = $imageInfo;
    $maxDimension = 1024;

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

    // Salva nel database
    try {
        $stmt = $pdo->prepare("UPDATE Utenti SET Avatar = ? WHERE id = ?");
        $stmt->execute([$image, $_SESSION['user_id']]);

        jsonResponse([
            'success' => true,
            'message' => 'Avatar aggiornato con successo',
            'avatarUrl' => 'index.php?action=get_avatar&id=' . $_SESSION['user_id']
        ]);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Errore durante il salvataggio: ' . $e->getMessage()], 500);
    }
}

/**
 * Ottieni avatar utente
 * GET: id
 */
function getAvatarApi(PDO $pdo): void
{
    $userId = (int)($_GET['id'] ?? 0);

    if (!$userId) {
        http_response_code(400);
        exit;
    }

    $stmt = $pdo->prepare("SELECT Avatar FROM Utenti WHERE id = ?");
    $stmt->execute([$userId]);
    $avatar = $stmt->fetchColumn();

    if ($avatar) {
        header('Content-Type: image/jpeg');
        header('Cache-Control: max-age=86400'); // Cache 1 giorno
        echo $avatar;
    } else {
        // Avatar predefinito
        header('Location: public/img/default-avatar.png');
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
        jsonResponse(['error' => 'Devi effettuare il login'], 401);
        return;
    }

    if (!verifyCsrf()) {
        jsonResponse(['error' => 'Token CSRF non valido'], 403);
        return;
    }

    try {
        $stmt = $pdo->prepare("UPDATE Utenti SET Avatar = NULL WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);

        jsonResponse([
            'success' => true,
            'message' => 'Avatar eliminato'
        ]);
    } catch (Exception $e) {
        jsonResponse(['error' => 'Errore: ' . $e->getMessage()], 500);
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
