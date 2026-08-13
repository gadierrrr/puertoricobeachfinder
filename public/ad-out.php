<?php
/** Signed paid-placement click redirect. */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/advertising.php';

header('X-Robots-Tag: noindex, nofollow', true);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$payload = advertisingVerifyPayload(trim((string) ($_GET['t'] ?? '')));
if (!$payload || ($payload['purpose'] ?? '') !== 'event' || trim((string) ($payload['x'] ?? '')) === '') {
    http_response_code(404);
    echo 'Placement unavailable.';
    exit;
}

$result = advertisingRecordEvent($payload, 'click');
if (!($result['ok'] ?? false)) {
    http_response_code((int) ($result['status'] ?? 404));
    echo 'Placement unavailable.';
    exit;
}

$assignment = (array) ($result['assignment'] ?? []);
$action = (string) ($payload['x'] ?? '');
$target = advertisingActionTarget($assignment, $action);
if ($target === '') {
    http_response_code(404);
    echo 'Placement link unavailable.';
    exit;
}

$target = str_replace(["\r", "\n", "\0"], '', $target);
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Location: ' . $target, true, 302);
exit;

