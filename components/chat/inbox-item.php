<?php
/**
 * Chat Component: Inbox Conversation Row
 *
 * Expects: $channel (with _display_name, _icon, last_message, unread_count)
 */

$displayName = $channel['_display_name'] ?? 'Chat';
$icon = $channel['_icon'] ?? '💬';
$lastMsg = $channel['last_message'] ?? null;
$unread = (int)($channel['unread_count'] ?? 0);
$lastTime = $lastMsg ? chatRelativeTime($lastMsg['created_at']) : '';
$preview = '';

if ($lastMsg) {
    $senderName = $lastMsg['user_name'] ?? 'User';
    $isMe = ($userId ?? null) && $lastMsg['user_id'] === ($userId ?? '');
    $prefix = $isMe ? (__('chat.you') . ': ') : ($senderName . ': ');
    $preview = $prefix . mb_substr($lastMsg['body'], 0, 60);
    if (mb_strlen($lastMsg['body']) > 60) $preview .= '...';
}

// Color gradient for beach channel icons
$gradients = [
    'bg-gradient-to-br from-cyan-700 to-cyan-900',
    'bg-gradient-to-br from-emerald-700 to-emerald-900',
    'bg-gradient-to-br from-purple-700 to-purple-900',
    'bg-gradient-to-br from-amber-700 to-amber-900',
    'bg-gradient-to-br from-rose-700 to-rose-900',
    'bg-gradient-to-br from-indigo-700 to-indigo-900',
];
$gradientIndex = abs(crc32($channel['id'])) % count($gradients);
$iconBg = $channel['type'] === 'general'
    ? 'bg-gradient-to-br from-sunset-400/80 to-amber-600'
    : $gradients[$gradientIndex];
?>
<button class="chat-convo-item flex items-center gap-2.5 p-2.5 rounded-xl"
        data-action="openChatThread"
        data-action-args='["<?= h($channel['id']) ?>"]'>
    <div class="w-9 h-9 rounded-lg <?= $iconBg ?> flex items-center justify-center text-sm shrink-0"><?= $icon ?></div>
    <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-white"><?= h($displayName) ?></span>
            <?php if ($lastTime): ?>
            <span class="text-[10px] text-warm-500"><?= h($lastTime) ?></span>
            <?php endif; ?>
        </div>
        <?php if ($preview): ?>
        <p class="text-[11px] text-white/40 truncate mt-0.5"><?= h($preview) ?></p>
        <?php else: ?>
        <p class="text-[11px] text-white/30 mt-0.5"><?= h(__('chat.no_messages_yet')) ?></p>
        <?php endif; ?>
    </div>
    <?php if ($unread > 0): ?>
    <div class="chat-unread-dot shrink-0"></div>
    <?php endif; ?>
</button>
