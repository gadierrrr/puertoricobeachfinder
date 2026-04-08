<?php
/**
 * Admin - Chat Moderation
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/chat.php';
require_once APP_ROOT . '/inc/chat_moderation.php';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once APP_ROOT . '/inc/session.php';
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    require_once APP_ROOT . '/inc/admin.php';
    requireAdmin();

    $messageId = (string)($_POST['message_id'] ?? '');
    $action = (string)($_POST['action'] ?? '');
    $targetUserId = (string)($_POST['target_user_id'] ?? '');
    $adminId = $_SESSION['user_id'] ?? '';

    // Message actions
    if ($messageId && in_array($action, ['approve', 'hide', 'delete'], true)) {
        chatAdminAction($messageId, $action, $adminId);
    }

    // User actions
    if ($targetUserId && $action === 'mute') {
        $hours = max(1, (int)($_POST['mute_hours'] ?? 24));
        chatMuteUser($targetUserId, $hours, $adminId);
    }
    if ($targetUserId && $action === 'ban') {
        chatBanUser($targetUserId, $adminId);
    }
    if ($targetUserId && $action === 'unban') {
        chatUnbanUser($targetUserId, $adminId);
    }

    // Blocklist actions
    if ($action === 'add_pattern') {
        $pattern = trim((string)($_POST['pattern'] ?? ''));
        $isRegex = !empty($_POST['is_regex']) ? 1 : 0;
        $severity = in_array($_POST['severity'] ?? '', ['block', 'flag'], true) ? $_POST['severity'] : 'block';
        if ($pattern !== '') {
            $id = uuid();
            execute(
                "INSERT OR IGNORE INTO chat_blocklist (id, pattern, is_regex, severity, created_by, created_at) VALUES (:id, :pattern, :is_regex, :severity, :by, datetime('now'))",
                [':id' => $id, ':pattern' => $pattern, ':is_regex' => $isRegex, ':severity' => $severity, ':by' => $adminId]
            );
        }
    }
    if ($action === 'delete_pattern') {
        $patternId = (string)($_POST['pattern_id'] ?? '');
        if ($patternId) {
            execute('DELETE FROM chat_blocklist WHERE id = :id', [':id' => $patternId]);
        }
    }

    // HTMX: return empty
    if (isset($_SERVER['HTTP_HX_REQUEST'])) {
        exit;
    }

    header('Location: /admin/chat-moderation?' . http_build_query(array_filter(['tab' => $_GET['tab'] ?? ''])));
    exit;
}

$pageTitle = 'Chat Moderation';
$pageSubtitle = 'Review flagged messages, reports, and manage chat users';

include __DIR__ . '/components/header.php';

$tab = (string)($_GET['tab'] ?? 'flagged');

// Stats
$stats = chatModerationStats();
$flaggedCount = $stats['flagged'];
$pendingReports = $stats['pending_reports'];
$hiddenCount = (int)(queryOne("SELECT COUNT(*) AS cnt FROM chat_messages WHERE status = 'hidden'")['cnt'] ?? 0);
$mutedCount = (int)(queryOne("SELECT COUNT(*) AS cnt FROM users WHERE chat_muted_until > datetime('now') OR chat_banned = 1")['cnt'] ?? 0);
$blocklistCount = (int)(queryOne("SELECT COUNT(*) AS cnt FROM chat_blocklist")['cnt'] ?? 0);
?>

<!-- Tabs -->
<div class="bg-white rounded-xl shadow-sm mb-6">
    <div class="flex border-b border-gray-200 overflow-x-auto">
        <a href="/admin/chat-moderation?tab=flagged"
           class="px-5 py-4 font-medium text-sm whitespace-nowrap <?= $tab === 'flagged' ? 'text-yellow-600 border-b-2 border-yellow-600' : 'text-gray-500 hover:text-gray-700' ?>">
            Flagged (<?= $flaggedCount ?>)
        </a>
        <a href="/admin/chat-moderation?tab=reports"
           class="px-5 py-4 font-medium text-sm whitespace-nowrap <?= $tab === 'reports' ? 'text-red-600 border-b-2 border-red-600' : 'text-gray-500 hover:text-gray-700' ?>">
            Reports (<?= $pendingReports ?>)
        </a>
        <a href="/admin/chat-moderation?tab=hidden"
           class="px-5 py-4 font-medium text-sm whitespace-nowrap <?= $tab === 'hidden' ? 'text-gray-600 border-b-2 border-gray-600' : 'text-gray-500 hover:text-gray-700' ?>">
            Hidden (<?= $hiddenCount ?>)
        </a>
        <a href="/admin/chat-moderation?tab=blocklist"
           class="px-5 py-4 font-medium text-sm whitespace-nowrap <?= $tab === 'blocklist' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' ?>">
            Blocklist (<?= $blocklistCount ?>)
        </a>
        <a href="/admin/chat-moderation?tab=users"
           class="px-5 py-4 font-medium text-sm whitespace-nowrap <?= $tab === 'users' ? 'text-purple-600 border-b-2 border-purple-600' : 'text-gray-500 hover:text-gray-700' ?>">
            Muted/Banned (<?= $mutedCount ?>)
        </a>
    </div>
</div>

<?php if ($tab === 'flagged' || $tab === 'hidden'): ?>
<?php
    $status = $tab === 'flagged' ? 'flagged' : 'hidden';
    $messages = query("
        SELECT cm.*, u.name AS user_name, u.email AS user_email,
               cc.type AS channel_type, cc.beach_id, b.name AS beach_name
        FROM chat_messages cm
        JOIN users u ON cm.user_id = u.id
        JOIN chat_channels cc ON cm.channel_id = cc.id
        LEFT JOIN beaches b ON cc.beach_id = b.id
        WHERE cm.status = :status
        ORDER BY cm.created_at DESC
        LIMIT 50
    ", [':status' => $status]);
?>
<div class="space-y-3">
    <?php if (empty($messages)): ?>
    <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">
        No <?= $status ?> messages.
    </div>
    <?php endif; ?>

    <?php foreach ($messages as $msg): ?>
    <div class="bg-white rounded-xl shadow-sm p-4" id="msg-<?= h($msg['id']) ?>">
        <div class="flex items-start gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span class="font-semibold text-sm text-gray-900"><?= h($msg['user_name'] ?? 'Unknown') ?></span>
                    <span class="text-xs text-gray-400">in</span>
                    <span class="text-xs font-medium text-blue-600">
                        <?= $msg['channel_type'] === 'general' ? 'General Chat' : h($msg['beach_name'] ?? 'Beach') ?>
                    </span>
                    <span class="text-xs text-gray-400">&middot; <?= h($msg['created_at']) ?></span>
                </div>
                <p class="text-sm text-gray-800 bg-gray-50 rounded-lg p-3 mb-2"><?= h($msg['body']) ?></p>
                <?php if ($msg['moderation_result']): ?>
                <div class="text-xs text-gray-500 mb-2">
                    <?php $modResult = json_decode($msg['moderation_result'], true); ?>
                    AI: <span class="font-medium <?= ($modResult['decision'] ?? '') === 'block' ? 'text-red-600' : 'text-yellow-600' ?>"><?= h(strtoupper($modResult['decision'] ?? 'unknown')) ?></span>
                    — <?= h($modResult['reason'] ?? '') ?>
                </div>
                <?php endif; ?>
                <?php if ($msg['report_count'] > 0): ?>
                <div class="text-xs text-red-500"><?= (int)$msg['report_count'] ?> report(s)</div>
                <?php endif; ?>
            </div>
            <div class="flex flex-col gap-1 shrink-0">
                <?php if ($status === 'flagged'): ?>
                <form method="POST" class="inline">
                    <input type="hidden" name="message_id" value="<?= h($msg['id']) ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="px-3 py-1 text-xs font-medium bg-green-100 text-green-700 rounded-lg hover:bg-green-200">Approve</button>
                </form>
                <?php endif; ?>
                <form method="POST" class="inline">
                    <input type="hidden" name="message_id" value="<?= h($msg['id']) ?>">
                    <input type="hidden" name="action" value="<?= $status === 'flagged' ? 'hide' : 'delete' ?>">
                    <button type="submit" class="px-3 py-1 text-xs font-medium bg-red-100 text-red-700 rounded-lg hover:bg-red-200"><?= $status === 'flagged' ? 'Hide' : 'Delete' ?></button>
                </form>
                <form method="POST" class="inline">
                    <input type="hidden" name="target_user_id" value="<?= h($msg['user_id']) ?>">
                    <input type="hidden" name="action" value="mute">
                    <input type="hidden" name="mute_hours" value="24">
                    <button type="submit" class="px-3 py-1 text-xs font-medium bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200">Mute 24h</button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php elseif ($tab === 'reports'): ?>
<?php
    $reports = query("
        SELECT cr.*, cm.body AS message_body, cm.status AS message_status,
               u_reporter.name AS reporter_name,
               u_author.name AS author_name, u_author.id AS author_id,
               cc.type AS channel_type, b.name AS beach_name
        FROM chat_reports cr
        JOIN chat_messages cm ON cr.message_id = cm.id
        JOIN users u_reporter ON cr.reporter_user_id = u_reporter.id
        JOIN users u_author ON cm.user_id = u_author.id
        JOIN chat_channels cc ON cm.channel_id = cc.id
        LEFT JOIN beaches b ON cc.beach_id = b.id
        WHERE cr.resolved_at IS NULL
        ORDER BY cr.created_at DESC
        LIMIT 50
    ");
?>
<div class="space-y-3">
    <?php if (empty($reports)): ?>
    <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">No pending reports.</div>
    <?php endif; ?>

    <?php foreach ($reports as $rpt): ?>
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-start gap-4">
            <div class="flex-1">
                <div class="text-xs text-gray-500 mb-1">
                    <span class="font-medium text-red-600"><?= h($rpt['reporter_name']) ?></span> reported
                    <span class="font-medium text-gray-800"><?= h($rpt['author_name']) ?></span>
                    for <span class="font-semibold"><?= h($rpt['reason']) ?></span>
                </div>
                <p class="text-sm text-gray-800 bg-gray-50 rounded-lg p-3 mb-1"><?= h($rpt['message_body']) ?></p>
                <?php if ($rpt['details']): ?>
                <p class="text-xs text-gray-500 italic">"<?= h($rpt['details']) ?>"</p>
                <?php endif; ?>
                <p class="text-xs text-gray-400 mt-1">Message status: <?= h($rpt['message_status']) ?> &middot; <?= h($rpt['created_at']) ?></p>
            </div>
            <div class="flex flex-col gap-1 shrink-0">
                <form method="POST" class="inline">
                    <input type="hidden" name="message_id" value="<?= h($rpt['message_id']) ?>">
                    <input type="hidden" name="action" value="hide">
                    <button type="submit" class="px-3 py-1 text-xs font-medium bg-red-100 text-red-700 rounded-lg hover:bg-red-200">Hide Message</button>
                </form>
                <form method="POST" class="inline">
                    <input type="hidden" name="message_id" value="<?= h($rpt['message_id']) ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="px-3 py-1 text-xs font-medium bg-green-100 text-green-700 rounded-lg hover:bg-green-200">Dismiss</button>
                </form>
                <form method="POST" class="inline">
                    <input type="hidden" name="target_user_id" value="<?= h($rpt['author_id']) ?>">
                    <input type="hidden" name="action" value="ban">
                    <button type="submit" class="px-3 py-1 text-xs font-medium bg-red-100 text-red-700 rounded-lg hover:bg-red-200">Ban User</button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php elseif ($tab === 'blocklist'): ?>
<!-- Add Pattern Form -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-4">
    <h3 class="font-semibold text-sm text-gray-900 mb-3">Add Pattern</h3>
    <form method="POST" class="flex gap-3 items-end flex-wrap">
        <input type="hidden" name="action" value="add_pattern">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Pattern</label>
            <input type="text" name="pattern" required class="border rounded-lg px-3 py-2 text-sm w-64" placeholder="word or regex...">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Severity</label>
            <select name="severity" class="border rounded-lg px-3 py-2 text-sm">
                <option value="block">Block</option>
                <option value="flag">Flag</option>
            </select>
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_regex" value="1" id="is_regex">
            <label for="is_regex" class="text-sm text-gray-600">Regex</label>
        </div>
        <button type="submit" class="px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700">Add</button>
    </form>
</div>

<?php $patterns = query('SELECT * FROM chat_blocklist ORDER BY created_at DESC'); ?>
<div class="bg-white rounded-xl shadow-sm">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-200">
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Pattern</th>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Type</th>
                <th class="text-left px-4 py-3 text-gray-500 font-medium">Severity</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($patterns as $p): ?>
            <tr class="border-b border-gray-100">
                <td class="px-4 py-3 font-mono text-xs"><?= h($p['pattern']) ?></td>
                <td class="px-4 py-3 text-xs"><?= $p['is_regex'] ? 'Regex' : 'Exact' ?></td>
                <td class="px-4 py-3"><span class="px-2 py-0.5 rounded text-xs font-medium <?= $p['severity'] === 'block' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' ?>"><?= h($p['severity']) ?></span></td>
                <td class="px-4 py-3 text-right">
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="delete_pattern">
                        <input type="hidden" name="pattern_id" value="<?= h($p['id']) ?>">
                        <button type="submit" class="text-red-500 hover:text-red-700 text-xs">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php elseif ($tab === 'users'): ?>
<?php
    $restrictedUsers = query("
        SELECT * FROM users
        WHERE chat_muted_until > datetime('now') OR chat_banned = 1
        ORDER BY CASE WHEN chat_banned = 1 THEN 0 ELSE 1 END, chat_muted_until DESC
    ");
?>
<div class="space-y-3">
    <?php if (empty($restrictedUsers)): ?>
    <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">No muted or banned users.</div>
    <?php endif; ?>

    <?php foreach ($restrictedUsers as $u): ?>
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex items-center justify-between">
            <div>
                <span class="font-semibold text-sm text-gray-900"><?= h($u['name'] ?? $u['email'] ?? 'Unknown') ?></span>
                <?php if (!empty($u['chat_banned'])): ?>
                <span class="ml-2 px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Banned</span>
                <?php else: ?>
                <span class="ml-2 px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700">Muted until <?= h($u['chat_muted_until']) ?></span>
                <?php endif; ?>
                <p class="text-xs text-gray-500 mt-0.5"><?= (int)$u['chat_message_count'] ?> total messages</p>
            </div>
            <form method="POST">
                <input type="hidden" name="target_user_id" value="<?= h($u['id']) ?>">
                <input type="hidden" name="action" value="unban">
                <button type="submit" class="px-3 py-1.5 text-xs font-medium bg-green-100 text-green-700 rounded-lg hover:bg-green-200">Unmute/Unban</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/components/footer.php'; ?>
