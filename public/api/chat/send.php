<?php
/**
 * Chat API: Send a message.
 * POST /api/chat/send  body: channel_id, body, csrf_token
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
    jsonResponse(['success' => false, 'error' => 'Please sign in to send messages.'], 401);
}

$csrfToken = (string)($_POST['csrf_token'] ?? '');
if (!validateCsrf($csrfToken)) {
    jsonResponse(['success' => false, 'error' => 'Invalid security token. Please refresh and try again.'], 403);
}

$channelId = trim((string)($_POST['channel_id'] ?? ''));
$body = trim((string)($_POST['body'] ?? ''));

if ($channelId === '') {
    jsonResponse(['success' => false, 'error' => 'channel_id required'], 400);
}
if ($body === '') {
    jsonResponse(['success' => false, 'error' => 'Message cannot be empty.'], 400);
}

$user = currentUser();
$result = chatSendMessage($channelId, $user['id'], $body);

if (!$result['success']) {
    $code = 400;
    if (str_contains($result['error'] ?? '', 'rate')) $code = 429;
    if (str_contains($result['error'] ?? '', 'banned') || str_contains($result['error'] ?? '', 'muted')) $code = 403;
    jsonResponse(['success' => false, 'error' => $result['error']], $code);
}

if (isHtmx()) {
    header('Content-Type: text/html; charset=utf-8');
    // Return the rendered message for append
    $message = $result['message'];
    $currentUserId = $user['id'];
    $lang = getCurrentLanguage();
    include APP_ROOT . '/components/chat/message.php';
    exit;
}

$safeMsg = $result['message'];
unset($safeMsg['ip_hash'], $safeMsg['moderation_result'], $safeMsg['report_count']);
jsonResponse(['success' => true, 'message' => $safeMsg]);
