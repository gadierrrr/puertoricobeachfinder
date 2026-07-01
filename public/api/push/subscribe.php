<?php
/**
 * Store / remove a browser web-push subscription for the current user.
 * POST JSON { action: 'subscribe'|'unsubscribe', csrf_token, subscription: {endpoint, keys:{p256dh,auth}} }
 * (Foundation endpoint — the opt-in UI + encrypted sender are the remaining Phase 2 work.)
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
session_start();
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/push.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$body = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($body)) {
    $body = $_POST;
    if (isset($body['subscription']) && is_string($body['subscription'])) {
        $body['subscription'] = json_decode($body['subscription'], true);
    }
}

if (!validateCsrf($body['csrf_token'] ?? '')) {
    jsonResponse(['error' => 'Invalid request'], 403);
}

$action = $body['action'] ?? 'subscribe';
$sub = $body['subscription'] ?? null;
$userId = $_SESSION['user_id'];

if ($action === 'unsubscribe') {
    $endpoint = is_array($sub) ? (string) ($sub['endpoint'] ?? '') : '';
    if ($endpoint !== '') {
        // Scope the delete to the current user so one user can't remove another's subscription.
        pushDeleteUserSubscription($userId, $endpoint);
    }
    jsonResponse(['success' => true]);
}

if (!is_array($sub)) {
    jsonResponse(['error' => 'Missing subscription'], 400);
}

$ok = pushStoreSubscription($userId, $sub);
jsonResponse($ok ? ['success' => true] : ['error' => 'Invalid subscription'], $ok ? 200 : 400);
