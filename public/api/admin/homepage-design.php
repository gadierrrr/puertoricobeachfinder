<?php
/**
 * Admin Homepage Design API
 *
 * GET                     - Current design + available background photos
 * POST action=save        - Persist design JSON (sanitized) to site_settings
 * POST action=upload-bg   - Upload a hero background photo (converted to webp)
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
session_start();
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/admin.php';
require_once APP_ROOT . '/inc/settings.php';
require_once APP_ROOT . '/inc/homepage_fonts.php';
require_once APP_ROOT . '/inc/homepage_bg_photos.php';
require_once APP_ROOT . '/inc/image-optimizer.php';

if (!isAdmin()) {
    jsonResponse(['error' => 'Unauthorized'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    jsonResponse([
        'design' => getHomepageDesign(),
        'photos' => listHomepageBgPhotos(),
    ]);
}

if ($method !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!validateCsrf($csrfToken)) {
    jsonResponse(['error' => 'Invalid CSRF token'], 403);
}

$action = $_POST['action'] ?? '';

if ($action === 'save') {
    $design = json_decode((string) ($_POST['design'] ?? ''), true);
    if (!is_array($design)) {
        jsonResponse(['error' => 'Invalid design payload'], 400);
    }
    $design = sanitizeHomepageDesign(array_merge(homepageDesignDefaults(), $design));
    setSetting('homepage_design', json_encode($design, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    jsonResponse(['ok' => true, 'design' => $design]);
}

if ($action === 'upload-bg') {
    uploadBgPhoto();
}

jsonResponse(['error' => 'Invalid action'], 400);

function uploadBgPhoto(): void {
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['error' => 'No file uploaded or upload error'], 400);
    }
    $file = $_FILES['photo'];
    if ($file['size'] > 15 * 1024 * 1024) {
        jsonResponse(['error' => 'File too large (max 15 MB)'], 400);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        jsonResponse(['error' => 'Unsupported image type (use JPEG, PNG or WebP)'], 400);
    }

    $image = loadImage($file['tmp_name'], $mimeType);
    if (!$image) {
        jsonResponse(['error' => 'Could not read image'], 400);
    }
    $image = autoRotateImage($image, $file['tmp_name'], $mimeType);

    // hero backgrounds render full-bleed — one wide webp is enough
    $w = imagesx($image);
    $h = imagesy($image);
    $maxW = 2000;
    if ($w > $maxW) {
        $nh = (int) round($h * $maxW / $w);
        $resized = imagecreatetruecolor($maxW, $nh);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $maxW, $nh, $w, $h);
        imagedestroy($image);
        $image = $resized;
    }

    $dir = homepageBgPhotoDir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        jsonResponse(['error' => 'Could not create upload directory'], 500);
    }
    $filename = 'bg-' . date('Ymd') . '-' . substr(bin2hex(random_bytes(6)), 0, 10) . '.webp';
    $path = $dir . '/' . $filename;
    if (!imagewebp($image, $path, 82)) {
        imagedestroy($image);
        jsonResponse(['error' => 'Could not save image'], 500);
    }
    imagedestroy($image);

    jsonResponse(['ok' => true, 'url' => '/uploads/admin/homepage/' . $filename]);
}
