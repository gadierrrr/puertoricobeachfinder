<?php
/**
 * API: list Google Places photos for a beach (live fetch, never cached).
 *
 * GET /api/beach-photos.php?beach=<slug>
 * Returns {photos: [{src, width, height, author, author_url, alt}]}.
 *
 * Fetched only when a user explicitly opens the photo viewer. Guarded by a
 * same-origin check, a per-IP rate limit, and the monthly details budget cap
 * (inc/google_photos.php) so traffic spikes cannot run up the Places bill.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/google_photos.php';
require_once APP_ROOT . '/inc/rate_limiter.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

function beachPhotosFail(int $status, string $error): void
{
    http_response_code($status);
    echo json_encode(['photos' => [], 'error' => $error]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    beachPhotosFail(405, 'method');
}
if (!googlePhotosRequestIsSameOrigin()) {
    beachPhotosFail(403, 'origin');
}

$slug = trim((string) ($_GET['beach'] ?? ''));
if ($slug === '' || !preg_match('/^[a-z0-9-]{1,120}$/', $slug)) {
    beachPhotosFail(400, 'bad_request');
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$limiter = new RateLimiter(getDb());
$limit = $limiter->check($ip, 'google_photos_list', 20, 10);
if (empty($limit['allowed'])) {
    beachPhotosFail(429, 'rate_limited');
}

$beach = queryOne(
    "SELECT slug, name, place_id FROM beaches WHERE slug = :slug AND publish_status = 'published'",
    [':slug' => $slug]
);
if (!$beach) {
    beachPhotosFail(404, 'not_found');
}
$placeId = trim((string) ($beach['place_id'] ?? ''));
if ($placeId === '') {
    echo json_encode(['photos' => []]);
    exit;
}

if (googlePhotosApiKey() === '') {
    beachPhotosFail(503, 'not_configured');
}
if (!googlePhotosBudgetConsume('details', googlePhotosDetailsMonthlyCap())) {
    beachPhotosFail(503, 'budget');
}

$photos = googlePhotosFetchList($placeId);
if ($photos === null) {
    beachPhotosFail(502, 'unavailable');
}

$out = [];
foreach ($photos as $i => $photo) {
    $out[] = [
        'src' => '/api/beach-photo-media.php?name=' . rawurlencode($photo['name']) . '&w=1200',
        'width' => $photo['width'],
        'height' => $photo['height'],
        'author' => $photo['author'],
        'author_url' => $photo['author_url'],
        'alt' => $beach['name'] . ' — photo ' . ($i + 1) . ' from Google Maps',
    ];
}

echo json_encode(['photos' => $out]);
