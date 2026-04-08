<?php
/**
 * Chat API: Get total unread count.
 * GET /api/chat/unread
 * Returns badge HTML for HTMX swap. Auth required (guests get nothing).
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/session.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once APP_ROOT . '/inc/chat.php';

if (!isAuthenticated()) {
    echo '';
    exit;
}

$user = currentUser();
$count = chatGetUnreadCount($user['id']);

header('Content-Type: text/html; charset=utf-8');

if ($count > 0) {
    $display = $count > 9 ? '9+' : (string)$count;
    echo '<span class="chat-fab-badge">' . $display . '</span>';
} else {
    echo '';
}
