<?php
/**
 * Chat API: Poll for new messages.
 * GET /api/chat/poll?channel_id=xxx&after=messageId
 * Returns 204 if no new messages. Works for guests.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/session.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once APP_ROOT . '/inc/chat.php';

$channelId = trim((string)($_GET['channel_id'] ?? ''));
$afterMessageId = trim((string)($_GET['after'] ?? ''));

if ($channelId === '' || $afterMessageId === '') {
    http_response_code(400);
    exit;
}

// Verify channel exists and user can access it
$channel = queryOne('SELECT * FROM chat_channels WHERE id = :id', [':id' => $channelId]);
if (!$channel) {
    http_response_code(404);
    exit;
}
$user = currentUser();
$pollUserId = $user['id'] ?? null;
if (!chatCanAccessChannel($channel, $pollUserId)) {
    http_response_code(403);
    exit;
}

$newMessages = chatGetNewMessages($channelId, $afterMessageId);

if (empty($newMessages)) {
    http_response_code(204);
    exit;
}

// Mark as read for authenticated users
if ($pollUserId) {
    $userId = $pollUserId;
} else {
    $userId = null;
}
if ($userId) {
    chatMarkRead($channelId, $userId);
}

if (isHtmx() || !isset($_GET['format']) || $_GET['format'] !== 'json') {
    header('Content-Type: text/html; charset=utf-8');
    $currentUserId = $userId;
    $lang = getCurrentLanguage();
    foreach ($newMessages as $message) {
        include APP_ROOT . '/components/chat/message.php';
    }
    exit;
}

$safeMessages = array_map(function ($m) {
    unset($m['ip_hash'], $m['moderation_result'], $m['report_count']);
    return $m;
}, $newMessages);
jsonResponse(['messages' => $safeMessages]);
