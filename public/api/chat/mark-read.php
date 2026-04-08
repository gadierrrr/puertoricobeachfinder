<?php
/**
 * Chat API: Mark a channel as read.
 * POST /api/chat/mark-read  body: channel_id, csrf_token
 * Requires authentication.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/session.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once APP_ROOT . '/inc/chat.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

if (!isAuthenticated()) {
    jsonResponse(['success' => false, 'error' => 'Authentication required.'], 401);
}

$csrfToken = (string)($_POST['csrf_token'] ?? '');
if (!validateCsrf($csrfToken)) {
    jsonResponse(['success' => false, 'error' => 'Invalid security token.'], 403);
}

$channelId = trim((string)($_POST['channel_id'] ?? ''));
if ($channelId === '') {
    jsonResponse(['success' => false, 'error' => 'channel_id required'], 400);
}

$user = currentUser();
chatMarkRead($channelId, $user['id']);

jsonResponse(['success' => true]);
