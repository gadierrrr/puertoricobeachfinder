<?php
/**
 * Chat Component: Single Message
 *
 * Expects: $message (row with user_name, user_avatar), $currentUserId, $lang
 */

$displayInfo = chatUserDisplayInfo($message);
$isMine = ($currentUserId && $message['user_id'] === $currentUserId);
$relTime = chatRelativeTime($message['created_at']);
?>
<div class="chat-message-row chat-fade-in flex gap-2 mb-3 <?= $isMine ? 'justify-end' : '' ?>" data-message-id="<?= h($message['id']) ?>">
<?php if ($isMine): ?>
    <div class="flex-1 min-w-0 flex flex-col items-end">
        <div class="flex items-baseline gap-1.5 mb-0.5">
            <span class="text-[10px] text-warm-500"><?= h($relTime) ?></span>
        </div>
        <div class="chat-bubble-mine px-3 py-2 inline-block max-w-[88%]">
            <p class="text-xs text-white leading-relaxed"><?= h($message['body']) ?></p>
        </div>
    </div>
<?php else: ?>
    <div class="chat-avatar <?= h($displayInfo['color']) ?> mt-4"><?= h($displayInfo['initials']) ?></div>
    <div class="flex-1 min-w-0">
        <div class="flex items-baseline gap-1.5 mb-0.5">
            <span class="text-[11px] font-semibold text-white"><?= h($displayInfo['name']) ?></span>
            <span class="text-[10px] text-warm-500"><?= h($relTime) ?></span>
            <?php if ($currentUserId): ?>
            <button class="chat-report-btn text-[10px] text-white/20 hover:text-red-400 transition-colors ml-auto"
                    data-action="chatReportMessage"
                    data-action-args='["<?= h($message['id']) ?>"]'
                    title="<?= h(__('chat.report')) ?>">
                <svg class="w-3 h-3 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
            </button>
            <?php endif; ?>
        </div>
        <div class="chat-bubble-other px-3 py-2 inline-block max-w-[95%]">
            <p class="text-xs text-white/90 leading-relaxed"><?= h($message['body']) ?></p>
        </div>
    </div>
<?php endif; ?>
</div>
