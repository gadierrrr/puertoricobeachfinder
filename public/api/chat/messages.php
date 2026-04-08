<?php
/**
 * Chat API: Get messages for a channel.
 * GET /api/chat/messages?channel_id=xxx&before=yyy&limit=50
 * Works for guests (read-only).
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/session.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once APP_ROOT . '/inc/chat.php';

$channelId = trim((string)($_GET['channel_id'] ?? ''));
$before = isset($_GET['before']) ? trim((string)$_GET['before']) : null;
$limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));

if ($channelId === '') {
    jsonResponse(['error' => 'channel_id required'], 400);
}

// Verify channel exists and user can access it
$channel = queryOne('SELECT * FROM chat_channels WHERE id = :id', [':id' => $channelId]);
if (!$channel) {
    jsonResponse(['error' => 'Channel not found'], 404);
}

$user = currentUser();
$userId = $user['id'] ?? null;

if (!chatCanAccessChannel($channel, $userId)) {
    jsonResponse(['error' => 'Access denied'], 403);
}
$lang = getCurrentLanguage();

$messages = chatGetMessages($channelId, $limit, $before);

// Mark as read for authenticated users
if ($userId) {
    chatMarkRead($channelId, $userId);
}

if (isHtmx()) {
    header('Content-Type: text/html; charset=utf-8');

    if (empty($messages)) {
        echo '<div class="px-3 py-8 text-center"><p class="text-xs text-warm-500">' . h(__('chat.no_messages_yet')) . '</p></div>';
        exit;
    }

    $currentUserId = $userId;
    foreach ($messages as $message) {
        include APP_ROOT . '/components/chat/message.php';
    }
    exit;
}

// Strip sensitive fields from JSON response
$safeMessages = array_map(function ($m) {
    unset($m['ip_hash'], $m['moderation_result'], $m['report_count']);
    return $m;
}, $messages);
jsonResponse(['messages' => $safeMessages]);
