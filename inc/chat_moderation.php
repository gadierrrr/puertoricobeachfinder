<?php
/**
 * Chat System — Moderation Layer
 *
 * Keyword blocklist, AI moderation (Claude Haiku), report handling, mute/ban.
 * Reuses: inc/db.php, inc/audit_log.php
 */

if (defined('CHAT_MODERATION_INCLUDED')) return;
define('CHAT_MODERATION_INCLUDED', true);

require_once __DIR__ . '/db.php';

/** @var array|null Cached blocklist patterns (loaded once per request) */
$_chatBlocklistCache = null;

/**
 * Check message body against the keyword blocklist.
 * Returns ['blocked' => bool, 'flagged' => bool, 'matched_patterns' => array]
 */
function chatCheckBlocklist(string $body): array {
    global $_chatBlocklistCache;

    if ($_chatBlocklistCache === null) {
        $_chatBlocklistCache = query('SELECT pattern, is_regex, severity FROM chat_blocklist') ?: [];
    }

    $result = ['blocked' => false, 'flagged' => false, 'matched_patterns' => []];
    $bodyLower = mb_strtolower($body);

    foreach ($_chatBlocklistCache as $entry) {
        $matched = false;

        if ($entry['is_regex']) {
            $matched = (bool)preg_match('/' . $entry['pattern'] . '/iu', $bodyLower);
        } else {
            $matched = str_contains($bodyLower, mb_strtolower($entry['pattern']));
        }

        if ($matched) {
            $result['matched_patterns'][] = $entry['pattern'];
            if ($entry['severity'] === 'block') {
                $result['blocked'] = true;
            } else {
                $result['flagged'] = true;
            }
        }
    }

    return $result;
}

/**
 * Check if body contains URLs.
 */
function chatCheckLinks(string $body): bool {
    return (bool)preg_match('/https?:\/\/|www\./i', $body);
}

/**
 * AI moderation via Claude Haiku (synchronous, pre-publish).
 * Returns ['decision' => 'pass'|'flag'|'block', 'reason' => string] or null on failure.
 */
function chatAIModerate(string $body, string $channelContext): ?array {
    $apiKey = $_ENV['ANTHROPIC_API_KEY'] ?? getenv('ANTHROPIC_API_KEY');
    if (!$apiKey) {
        error_log('chatAIModerate: ANTHROPIC_API_KEY not set, skipping AI moderation');
        return null;
    }

    $systemPrompt = "You are a content moderator for a family-friendly beach community chat in Puerto Rico.
Classify the user message as: PASS, FLAG, or BLOCK.

PASS: Appropriate for a family-friendly beach community.
FLAG: Borderline — mild profanity, off-topic, or ambiguous. Allow but queue for review.
BLOCK: Hate speech, threats, harassment, explicit content, spam/scam, personal info (phone numbers, emails, addresses).

Context: \"{$channelContext}\" channel.
Respond ONLY with JSON: {\"decision\":\"PASS|FLAG|BLOCK\",\"reason\":\"brief explanation\"}";

    $payload = json_encode([
        'model' => 'claude-haiku-4-5-20251001',
        'max_tokens' => 100,
        'system' => $systemPrompt,
        'messages' => [
            ['role' => 'user', 'content' => $body],
        ],
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_TIMEOUT => 3,
        CURLOPT_CONNECTTIMEOUT => 2,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Network error or timeout
    if ($response === false || $curlError) {
        error_log("chatAIModerate: curl error: $curlError");
        return ['decision' => 'flag', 'reason' => 'AI moderation unavailable - network error'];
    }

    // API error
    if ($httpCode !== 200) {
        $err = json_decode($response, true);
        $msg = $err['error']['message'] ?? 'Unknown error';
        error_log("chatAIModerate: API $httpCode: $msg");
        return ['decision' => 'flag', 'reason' => "AI moderation unavailable - HTTP $httpCode"];
    }

    // Parse response
    $data = json_decode($response, true);
    $text = $data['content'][0]['text'] ?? null;
    if (!$text) {
        error_log('chatAIModerate: empty response content');
        return ['decision' => 'flag', 'reason' => 'AI moderation unavailable - empty response'];
    }

    // Extract JSON from response (may be wrapped in markdown or extra text)
    if (preg_match('/\{[\s\S]*?\}/', $text, $matches)) {
        $parsed = json_decode($matches[0], true);
        if ($parsed && isset($parsed['decision'])) {
            $decision = strtolower($parsed['decision']);
            if (in_array($decision, ['pass', 'flag', 'block'], true)) {
                return [
                    'decision' => $decision,
                    'reason' => $parsed['reason'] ?? '',
                ];
            }
        }
    }

    // Malformed response — fail open with flag
    error_log("chatAIModerate: could not parse response: $text");
    return ['decision' => 'flag', 'reason' => 'AI moderation unavailable - parse error'];
}

/**
 * Handle a user report on a message.
 * Returns ['success' => bool, 'error' => string|null]
 */
function chatHandleReport(string $messageId, string $reporterUserId, string $reason, ?string $details = null): array {
    $validReasons = ['spam', 'harassment', 'inappropriate', 'misinformation', 'other'];
    if (!in_array($reason, $validReasons, true)) {
        return ['success' => false, 'error' => 'Invalid report reason.'];
    }

    // Check message exists
    $message = queryOne('SELECT * FROM chat_messages WHERE id = :id', [':id' => $messageId]);
    if (!$message) {
        return ['success' => false, 'error' => 'Message not found.'];
    }

    // Can't report own message
    if ($message['user_id'] === $reporterUserId) {
        return ['success' => false, 'error' => 'You cannot report your own message.'];
    }

    // Check for duplicate report
    $existing = queryOne(
        'SELECT id FROM chat_reports WHERE message_id = :mid AND reporter_user_id = :uid',
        [':mid' => $messageId, ':uid' => $reporterUserId]
    );
    if ($existing) {
        return ['success' => false, 'error' => 'You have already reported this message.'];
    }

    $reportId = uuid();
    $db = getDB();
    $db->exec('BEGIN IMMEDIATE');
    try {
        $stmt = $db->prepare("
            INSERT INTO chat_reports (id, message_id, reporter_user_id, reason, details, created_at)
            VALUES (:id, :mid, :uid, :reason, :details, datetime('now'))
        ");
        $stmt->bindValue(':id', $reportId, SQLITE3_TEXT);
        $stmt->bindValue(':mid', $messageId, SQLITE3_TEXT);
        $stmt->bindValue(':uid', $reporterUserId, SQLITE3_TEXT);
        $stmt->bindValue(':reason', $reason, SQLITE3_TEXT);
        $stmt->bindValue(':details', $details, SQLITE3_TEXT);
        $stmt->execute();

        // Increment report count
        $stmtInc = $db->prepare("UPDATE chat_messages SET report_count = report_count + 1 WHERE id = :id");
        $stmtInc->bindValue(':id', $messageId, SQLITE3_TEXT);
        $stmtInc->execute();

        // Auto-hide at 3 reports
        $stmtCnt = $db->prepare("SELECT report_count FROM chat_messages WHERE id = :id");
        $stmtCnt->bindValue(':id', $messageId, SQLITE3_TEXT);
        $newCount = $stmtCnt->execute()->fetchArray(SQLITE3_ASSOC)['report_count'] ?? 0;
        if ($newCount >= 3) {
            $stmtHide = $db->prepare("UPDATE chat_messages SET status = 'hidden', updated_at = datetime('now') WHERE id = :id AND status <> 'hidden'");
            $stmtHide->bindValue(':id', $messageId, SQLITE3_TEXT);
            $stmtHide->execute();
        }

        $db->exec('COMMIT');
    } catch (\Throwable $e) {
        $db->exec('ROLLBACK');
        error_log('chatHandleReport error: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to submit report.'];
    }

    return ['success' => true, 'error' => null];
}

/**
 * Admin action on a message: approve, hide, delete.
 */
function chatAdminAction(string $messageId, string $action, string $adminId): bool {
    $statusMap = [
        'approve' => 'published',
        'hide' => 'hidden',
        'delete' => 'deleted',
    ];

    if (!isset($statusMap[$action])) return false;

    $ok = execute(
        "UPDATE chat_messages SET status = :status, updated_at = datetime('now') WHERE id = :id",
        [':status' => $statusMap[$action], ':id' => $messageId]
    );

    if ($ok && function_exists('auditLogRecord')) {
        require_once __DIR__ . '/audit_log.php';
        auditLogRecord('chat.message.' . $action, [
            'target_type' => 'chat_message',
            'target_id' => $messageId,
            'metadata' => ['action' => $action],
        ]);
    }

    // If approving, also resolve any pending reports on this message
    if ($action === 'approve') {
        execute(
            "UPDATE chat_reports SET resolved_at = datetime('now'), resolved_by = :admin, resolution = 'dismissed' WHERE message_id = :mid AND resolved_at IS NULL",
            [':admin' => $adminId, ':mid' => $messageId]
        );
    }

    return (bool)$ok;
}

/**
 * Mute a user's chat for a given number of hours.
 */
function chatMuteUser(string $userId, int $hours, string $adminId): bool {
    $until = gmdate('Y-m-d H:i:s', time() + ($hours * 3600));
    $ok = execute(
        "UPDATE users SET chat_muted_until = :until WHERE id = :id",
        [':until' => $until, ':id' => $userId]
    );

    if ($ok && function_exists('auditLogRecord')) {
        require_once __DIR__ . '/audit_log.php';
        auditLogRecord('chat.user.mute', [
            'target_type' => 'user',
            'target_id' => $userId,
            'metadata' => ['hours' => $hours, 'until' => $until],
        ]);
    }

    return (bool)$ok;
}

/**
 * Ban a user from chat permanently.
 */
function chatBanUser(string $userId, string $adminId): bool {
    $ok = execute('UPDATE users SET chat_banned = 1 WHERE id = :id', [':id' => $userId]);

    if ($ok && function_exists('auditLogRecord')) {
        require_once __DIR__ . '/audit_log.php';
        auditLogRecord('chat.user.ban', [
            'target_type' => 'user',
            'target_id' => $userId,
        ]);
    }

    return (bool)$ok;
}

/**
 * Unban a user from chat.
 */
function chatUnbanUser(string $userId, string $adminId): bool {
    $ok = execute(
        'UPDATE users SET chat_banned = 0, chat_muted_until = NULL WHERE id = :id',
        [':id' => $userId]
    );

    if ($ok && function_exists('auditLogRecord')) {
        require_once __DIR__ . '/audit_log.php';
        auditLogRecord('chat.user.unban', [
            'target_type' => 'user',
            'target_id' => $userId,
        ]);
    }

    return (bool)$ok;
}

/**
 * Get moderation stats for admin dashboard badge.
 */
function chatModerationStats(): array {
    $flagged = queryOne("SELECT COUNT(*) AS cnt FROM chat_messages WHERE status = 'flagged'");
    $reports = queryOne("SELECT COUNT(*) AS cnt FROM chat_reports WHERE resolved_at IS NULL");

    return [
        'flagged' => (int)($flagged['cnt'] ?? 0),
        'pending_reports' => (int)($reports['cnt'] ?? 0),
    ];
}
