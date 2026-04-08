<?php
/**
 * Chat API: Get inbox channels.
 * GET /api/chat/inbox?beach_id=xxx
 * Works for guests (read-only, no unread counts).
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/session.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once APP_ROOT . '/inc/chat.php';

$beachId = isset($_GET['beach_id']) ? trim((string)$_GET['beach_id']) : null;
$user = currentUser();
$userId = $user['id'] ?? null;
$lang = getCurrentLanguage();

$inbox = chatGetInbox($userId, $beachId);

if (isHtmx()) {
    header('Content-Type: text/html; charset=utf-8');

    // General Chat
    if ($inbox['general']) {
        $channel = $inbox['general'];
        $channel['_display_name'] = __('chat.general_chat');
        $channel['_icon'] = '💬';
        $channel['_section'] = null;
        include APP_ROOT . '/components/chat/inbox-item.php';
    }

    // This Beach
    if ($inbox['current_beach']) {
        echo '<div class="border-t border-white/5 mx-3 my-1"></div>';
        echo '<div class="px-3 pt-2 pb-1"><p class="text-[10px] uppercase tracking-wider text-warm-500 font-semibold px-1 mb-2">' . h(__('chat.this_beach')) . '</p>';
        $channel = $inbox['current_beach'];
        $channel['_display_name'] = $channel['beach_name'] ?? 'Beach';
        $channel['_icon'] = '🏖️';
        include APP_ROOT . '/components/chat/inbox-item.php';
        echo '</div>';
    }

    // Other Beaches
    if (!empty($inbox['other_beaches'])) {
        echo '<div class="border-t border-white/5 mx-3 my-1"></div>';
        echo '<div class="px-3 pt-2 pb-1"><p class="text-[10px] uppercase tracking-wider text-warm-500 font-semibold px-1 mb-2">' . h(__('chat.other_beaches')) . '</p>';
        foreach ($inbox['other_beaches'] as $channel) {
            $channel['_display_name'] = $channel['beach_name'] ?? 'Beach';
            $channel['_icon'] = '🏖️';
            include APP_ROOT . '/components/chat/inbox-item.php';
        }
        echo '</div>';
    }

    // Empty state (no channels at all for guest)
    if (!$inbox['general'] && !$inbox['current_beach'] && empty($inbox['other_beaches'])) {
        echo '<div class="px-4 py-8 text-center"><p class="text-sm text-warm-500">' . h(__('chat.no_conversations')) . '</p></div>';
    }

    exit;
}

// JSON response
jsonResponse($inbox);
