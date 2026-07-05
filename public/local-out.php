<?php
/**
 * Local listing outbound redirect — logs the click, then redirects.
 *
 * /local-out?l=<listing_id>&b=<beach_id>&a=<website|instagram|call|whatsapp>
 *
 * Targets are never taken from the query string — they're rebuilt server-side
 * from the stored listing row, so this cannot be used as an open redirect.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/listings.php';
require_once APP_ROOT . '/inc/i18n.php';

$listingId = trim((string) ($_GET['l'] ?? ''));
$beachId = trim((string) ($_GET['b'] ?? ''));
$action = trim((string) ($_GET['a'] ?? 'website'));

$allowedActions = ['website', 'instagram', 'call', 'whatsapp'];
if (!in_array($action, $allowedActions, true)) {
    $action = 'website';
}

$listing = $listingId !== ''
    ? queryOne('SELECT * FROM local_listings WHERE id = :id', [':id' => $listingId])
    : null;

if (!$listing || !listingIsCurrentlyActive($listing)) {
    http_response_code(404);
    echo 'Listing not found.';
    exit;
}

// Build the target from stored fields only.
$target = '';
switch ($action) {
    case 'website':
        $url = trim((string) ($listing['website_url'] ?? ''));
        if (preg_match('~^https?://~i', $url)) {
            $target = $url;
        }
        break;
    case 'instagram':
        $handle = ltrim(trim((string) ($listing['instagram'] ?? '')), '@');
        if ($handle !== '' && preg_match('/^[A-Za-z0-9._]{1,30}$/', $handle)) {
            $target = 'https://www.instagram.com/' . $handle . '/';
        }
        break;
    case 'call':
        $phone = preg_replace('/[^0-9+]/', '', (string) ($listing['phone'] ?? ''));
        if ($phone !== '') {
            $target = 'tel:' . $phone;
        }
        break;
    case 'whatsapp':
        $wa = preg_replace('/[^0-9]/', '', (string) ($listing['whatsapp'] ?? ''));
        if ($wa !== '') {
            $target = 'https://wa.me/' . $wa;
        }
        break;
}

if ($target === '') {
    http_response_code(404);
    echo 'Listing link unavailable.';
    exit;
}

$ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
$ua = trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));

execute(
    'INSERT INTO local_listing_clicks (id, listing_id, beach_id, action, locale, ip_hash, ua_hash, referrer)
     VALUES (:id, :listing_id, :beach_id, :action, :locale, :ip_hash, :ua_hash, :referrer)',
    [
        ':id' => uuid(),
        ':listing_id' => $listingId,
        ':beach_id' => $beachId !== '' ? $beachId : null,
        ':action' => $action,
        ':locale' => getCurrentLanguage() === 'es' ? 'es' : 'en',
        ':ip_hash' => $ip !== '' ? hash('sha256', $ip) : null,
        ':ua_hash' => $ua !== '' ? hash('sha256', $ua) : null,
        ':referrer' => mb_substr(trim((string) ($_SERVER['HTTP_REFERER'] ?? '')), 0, 500),
    ]
);

$target = str_replace(["\r", "\n", "\0"], '', $target);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Location: ' . $target, true, 302);
exit;
