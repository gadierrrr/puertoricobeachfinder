<?php
/**
 * API: redirect to one Google Places photo (live fetch, never cached).
 *
 * GET /api/beach-photo-media.php?name=places/X/photos/Y&w=1200
 * 302s to the short-lived googleusercontent URI so image bytes never pass
 * through this server. Each successful request is one billable Places photo
 * event — guarded by same-origin + per-IP rate limit + the monthly media
 * budget cap, which bounds worst-case spend regardless of traffic.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/google_photos.php';
require_once APP_ROOT . '/inc/rate_limiter.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    exit;
}
if (!googlePhotosRequestIsSameOrigin()) {
    http_response_code(403);
    exit;
}

$name = trim((string) ($_GET['name'] ?? ''));
if (!preg_match('#^places/[A-Za-z0-9_-]+/photos/[A-Za-z0-9_.=-]+$#', $name)) {
    http_response_code(400);
    exit;
}
$width = (int) ($_GET['w'] ?? 1200);
$width = max(320, min(1600, $width));

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$limiter = new RateLimiter(getDb());
$limit = $limiter->check($ip, 'google_photos_media', 60, 10);
if (empty($limit['allowed'])) {
    http_response_code(429);
    exit;
}

if (googlePhotosApiKey() === '') {
    http_response_code(503);
    exit;
}
if (!googlePhotosBudgetConsume('media', googlePhotosMediaMonthlyCap())) {
    http_response_code(503);
    exit;
}

$uri = googlePhotosFetchMediaUri($name, $width);
if ($uri === null) {
    http_response_code(404);
    exit;
}

// Let the same browser reuse the resolved image for an hour without
// re-billing; nothing is stored server-side (ToS: no caching of photos).
header('Cache-Control: private, max-age=3600');
header('Location: ' . $uri, true, 302);
