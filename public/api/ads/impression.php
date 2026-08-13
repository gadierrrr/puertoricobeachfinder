<?php
/** First-party viewable-impression endpoint. */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/advertising.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
$raw = file_get_contents('php://input') ?: '';
$data = str_contains($contentType, 'application/json') ? json_decode($raw, true) : $_POST;
if (!is_array($data)) {
    jsonResponse(['error' => 'Invalid payload'], 400);
}

$token = trim((string) ($data['token'] ?? ''));
$payload = advertisingVerifyPayload($token);
if (!$payload || ($payload['purpose'] ?? '') !== 'event' || (string) ($payload['x'] ?? '') !== '') {
    jsonResponse(['error' => 'Invalid token'], 400);
}

$result = advertisingRecordEvent($payload, 'impression');
if (!($result['ok'] ?? false)) {
    jsonResponse(['error' => 'Impression not accepted'], (int) ($result['status'] ?? 400));
}

header('Cache-Control: no-store');
jsonResponse(['ok' => true, 'duplicate' => (bool) ($result['duplicate'] ?? false)]);
