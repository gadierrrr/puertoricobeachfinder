<?php
/**
 * Chat System — Core Functions
 *
 * Channel management, message CRUD, inbox queries.
 * Reuses: inc/db.php (query, queryOne, execute, uuid)
 *         inc/helpers.php (isAuthenticated, h)
 *         inc/rate_limiter.php (RateLimiter)
 */

if (defined('CHAT_INCLUDED')) return;
define('CHAT_INCLUDED', true);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/** Well-known UUID for the general chat channel */
define('CHAT_GENERAL_CHANNEL_ID', '00000000-0000-4000-8000-000000000001');

/**
 * Verify a user can access a channel.
 * General and beach channels are public. DM channels require membership.
 * Returns true if access is allowed.
 */
function chatCanAccessChannel(array $channel, ?string $userId): bool {
    if ($channel['type'] === 'general' || $channel['type'] === 'beach') {
        return true;
    }
    // DM channels require the user to be a participant
    if ($channel['type'] === 'dm') {
        if (!$userId) return false;
        return $channel['participant_a'] === $userId || $channel['participant_b'] === $userId;
    }
    return false;
}

/**
 * Get the general chat channel.
 */
function chatGetGeneralChannel(): ?array {
    return queryOne('SELECT * FROM chat_channels WHERE id = :id', [':id' => CHAT_GENERAL_CHANNEL_ID]);
}

/**
 * Get or create the beach-specific channel.
 * Uses BEGIN IMMEDIATE to prevent race conditions.
 */
function chatGetOrCreateBeachChannel(string $beachId): ?array {
    $channel = queryOne(
        "SELECT * FROM chat_channels WHERE type = 'beach' AND beach_id = :beach_id",
        [':beach_id' => $beachId]
    );
    if ($channel) return $channel;

    $db = getDB();
    $db->exec('BEGIN IMMEDIATE');
    try {
        // Double-check after acquiring lock
        $channel = queryOne(
            "SELECT * FROM chat_channels WHERE type = 'beach' AND beach_id = :beach_id",
            [':beach_id' => $beachId]
        );
        if ($channel) {
            $db->exec('COMMIT');
            return $channel;
        }

        $id = uuid();
        $stmt = $db->prepare("
            INSERT INTO chat_channels (id, type, beach_id, created_at)
            VALUES (:id, 'beach', :beach_id, datetime('now'))
        ");
        $stmt->bindValue(':id', $id, SQLITE3_TEXT);
        $stmt->bindValue(':beach_id', $beachId, SQLITE3_TEXT);
        $stmt->execute();
        $db->exec('COMMIT');

        return queryOne('SELECT * FROM chat_channels WHERE id = :id', [':id' => $id]);
    } catch (\Throwable $e) {
        $db->exec('ROLLBACK');
        error_log('chatGetOrCreateBeachChannel error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get inbox data for the chat panel.
 * Returns: ['general' => ..., 'current_beach' => ..., 'other_beaches' => [...]]
 */
function chatGetInbox(?string $userId, ?string $currentBeachId = null): array {
    $result = [
        'general' => null,
        'current_beach' => null,
        'other_beaches' => [],
    ];

    // General channel — always present
    $general = chatGetGeneralChannel();
    if ($general) {
        $general['last_message'] = chatGetLastMessage($general['id']);
        $general['unread_count'] = $userId ? chatChannelUnreadCount($general['id'], $userId) : 0;
        $result['general'] = $general;
    }

    // Current beach channel (if on a beach page)
    if ($currentBeachId) {
        $beachChannel = chatGetOrCreateBeachChannel($currentBeachId);
        if ($beachChannel) {
            $beachChannel['last_message'] = chatGetLastMessage($beachChannel['id']);
            $beachChannel['unread_count'] = $userId ? chatChannelUnreadCount($beachChannel['id'], $userId) : 0;
            // Get beach name
            $beach = queryOne('SELECT name FROM beaches WHERE id = :id', [':id' => $currentBeachId]);
            $beachChannel['beach_name'] = $beach['name'] ?? 'Beach';
            $result['current_beach'] = $beachChannel;
        }
    }

    // Other beach channels the user participates in
    if ($userId) {
        $otherBeaches = query("
            SELECT cc.*, b.name AS beach_name
            FROM chat_participants cp
            JOIN chat_channels cc ON cp.channel_id = cc.id
            LEFT JOIN beaches b ON cc.beach_id = b.id
            WHERE cp.user_id = :user_id
              AND cc.type = 'beach'
              AND (cc.beach_id <> :current_beach_id OR :current_beach_id IS NULL)
            ORDER BY cc.last_message_at DESC
            LIMIT 10
        ", [
            ':user_id' => $userId,
            ':current_beach_id' => $currentBeachId,
        ]);

        if ($otherBeaches) {
            foreach ($otherBeaches as &$ch) {
                $ch['last_message'] = chatGetLastMessage($ch['id']);
                $ch['unread_count'] = chatChannelUnreadCount($ch['id'], $userId);
            }
            $result['other_beaches'] = $otherBeaches;
        }
    }

    return $result;
}

/**
 * Get the last message for a channel (for inbox preview).
 */
function chatGetLastMessage(string $channelId): ?array {
    return queryOne("
        SELECT cm.*, u.name AS user_name
        FROM chat_messages cm
        JOIN users u ON cm.user_id = u.id
        WHERE cm.channel_id = :channel_id
          AND cm.status IN ('published', 'flagged')
        ORDER BY cm.created_at DESC
        LIMIT 1
    ", [':channel_id' => $channelId]);
}

/**
 * Get unread count for a specific channel for a user.
 */
function chatChannelUnreadCount(string $channelId, string $userId): int {
    $participant = queryOne(
        'SELECT last_read_at FROM chat_participants WHERE channel_id = :cid AND user_id = :uid',
        [':cid' => $channelId, ':uid' => $userId]
    );

    $lastRead = $participant['last_read_at'] ?? null;

    if ($lastRead) {
        $row = queryOne("
            SELECT COUNT(*) AS cnt FROM chat_messages
            WHERE channel_id = :cid AND status IN ('published', 'flagged')
              AND created_at > :last_read AND user_id <> :uid
        ", [':cid' => $channelId, ':last_read' => $lastRead, ':uid' => $userId]);
    } else {
        // Never read — count all messages not by this user
        $row = queryOne("
            SELECT COUNT(*) AS cnt FROM chat_messages
            WHERE channel_id = :cid AND status IN ('published', 'flagged') AND user_id <> :uid
        ", [':cid' => $channelId, ':uid' => $userId]);
    }

    return (int)($row['cnt'] ?? 0);
}

/**
 * Get messages for a channel, paginated.
 */
function chatGetMessages(string $channelId, int $limit = 50, ?string $before = null): array {
    if ($before) {
        $rows = query("
            SELECT cm.*, u.name AS user_name, u.avatar_url AS user_avatar
            FROM chat_messages cm
            JOIN users u ON cm.user_id = u.id
            WHERE cm.channel_id = :cid
              AND cm.status IN ('published', 'flagged')
              AND cm.created_at < (SELECT created_at FROM chat_messages WHERE id = :before)
            ORDER BY cm.created_at DESC
            LIMIT :limit
        ", [':cid' => $channelId, ':before' => $before, ':limit' => $limit]);
    } else {
        $rows = query("
            SELECT cm.*, u.name AS user_name, u.avatar_url AS user_avatar
            FROM chat_messages cm
            JOIN users u ON cm.user_id = u.id
            WHERE cm.channel_id = :cid
              AND cm.status IN ('published', 'flagged')
            ORDER BY cm.created_at DESC
            LIMIT :limit
        ", [':cid' => $channelId, ':limit' => $limit]);
    }

    // Reverse to chronological order (oldest first)
    return $rows ? array_reverse($rows) : [];
}

/**
 * Get new messages after a given message ID (for polling).
 */
function chatGetNewMessages(string $channelId, string $afterMessageId): array {
    $rows = query("
        SELECT cm.*, u.name AS user_name, u.avatar_url AS user_avatar
        FROM chat_messages cm
        JOIN users u ON cm.user_id = u.id
        WHERE cm.channel_id = :cid
          AND cm.status IN ('published', 'flagged')
          AND cm.created_at > (SELECT created_at FROM chat_messages WHERE id = :after)
        ORDER BY cm.created_at ASC
    ", [':cid' => $channelId, ':after' => $afterMessageId]);

    return $rows ?: [];
}

/**
 * Send a message. Orchestrates validation, moderation, and insert.
 * Returns ['success' => bool, 'message' => array|null, 'error' => string|null]
 */
function chatSendMessage(string $channelId, string $userId, string $body): array {
    require_once __DIR__ . '/chat_moderation.php';

    // Validate channel exists and user can access it
    $channel = queryOne('SELECT * FROM chat_channels WHERE id = :id', [':id' => $channelId]);
    if (!$channel) {
        return ['success' => false, 'message' => null, 'error' => 'Channel not found.'];
    }
    if (!chatCanAccessChannel($channel, $userId)) {
        return ['success' => false, 'message' => null, 'error' => 'Access denied.'];
    }

    // Check user status
    $user = queryOne('SELECT * FROM users WHERE id = :id', [':id' => $userId]);
    if (!$user) {
        return ['success' => false, 'message' => null, 'error' => 'User not found.'];
    }

    if (!empty($user['chat_banned'])) {
        return ['success' => false, 'message' => null, 'error' => __('chat.banned')];
    }

    if ($user['chat_muted_until'] && $user['chat_muted_until'] > gmdate('Y-m-d H:i:s')) {
        $muteEnd = $user['chat_muted_until'];
        return ['success' => false, 'message' => null, 'error' => str_replace(':time', $muteEnd, __('chat.muted'))];
    }

    // Rate limiting
    require_once __DIR__ . '/rate_limiter.php';
    $limiter = new RateLimiter(getDB());

    $minuteCheck = $limiter->check($userId, 'chat_send_minute', 10, 1);
    if (!$minuteCheck['allowed']) {
        return ['success' => false, 'message' => null, 'error' => __('chat.rate_limited')];
    }

    $hourCheck = $limiter->check($userId, 'chat_send_hour', 100, 60);
    if (!$hourCheck['allowed']) {
        return ['success' => false, 'message' => null, 'error' => __('chat.rate_limited')];
    }

    // Validate body
    $body = trim($body);
    if ($body === '') {
        return ['success' => false, 'message' => null, 'error' => 'Message cannot be empty.'];
    }
    if (mb_strlen($body) > 500) {
        return ['success' => false, 'message' => null, 'error' => 'Message must be 500 characters or less.'];
    }

    // Keyword blocklist check
    $blocklistResult = chatCheckBlocklist($body);
    if ($blocklistResult['blocked']) {
        return ['success' => false, 'message' => null, 'error' => __('chat.message_blocked')];
    }

    // Link restriction for new users
    $messageCount = (int)($user['chat_message_count'] ?? 0);
    if ($messageCount < 5 && chatCheckLinks($body)) {
        return ['success' => false, 'message' => null, 'error' => __('chat.no_links_yet')];
    }

    // AI moderation (synchronous, pre-publish)
    $status = $blocklistResult['flagged'] ? 'flagged' : 'published';
    $moderationResult = null;

    $channelContext = $channel['type'] === 'general' ? 'General Chat' : 'Beach Discussion';
    $aiResult = chatAIModerate($body, $channelContext);

    if ($aiResult) {
        $moderationResult = json_encode($aiResult);
        if ($aiResult['decision'] === 'block') {
            return ['success' => false, 'message' => null, 'error' => __('chat.message_blocked')];
        }
        if ($aiResult['decision'] === 'flag') {
            $status = 'flagged';
        }
    }

    // Insert message
    $messageId = uuid();
    $ipHash = hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

    $db = getDB();
    $db->exec('BEGIN IMMEDIATE');
    try {
        $stmt = $db->prepare("
            INSERT INTO chat_messages (id, channel_id, user_id, body, status, moderation_result, ip_hash, created_at)
            VALUES (:id, :channel_id, :user_id, :body, :status, :mod_result, :ip_hash, datetime('now'))
        ");
        $stmt->bindValue(':id', $messageId, SQLITE3_TEXT);
        $stmt->bindValue(':channel_id', $channelId, SQLITE3_TEXT);
        $stmt->bindValue(':user_id', $userId, SQLITE3_TEXT);
        $stmt->bindValue(':body', $body, SQLITE3_TEXT);
        $stmt->bindValue(':status', $status, SQLITE3_TEXT);
        $stmt->bindValue(':mod_result', $moderationResult, SQLITE3_TEXT);
        $stmt->bindValue(':ip_hash', $ipHash, SQLITE3_TEXT);
        $stmt->execute();

        // Update channel counters
        $stmtCh = $db->prepare("UPDATE chat_channels SET message_count = message_count + 1, last_message_at = datetime('now') WHERE id = :id");
        $stmtCh->bindValue(':id', $channelId, SQLITE3_TEXT);
        $stmtCh->execute();

        // Update user message count
        $stmtUc = $db->prepare("UPDATE users SET chat_message_count = chat_message_count + 1 WHERE id = :id");
        $stmtUc->bindValue(':id', $userId, SQLITE3_TEXT);
        $stmtUc->execute();

        // Upsert participant
        $stmt2 = $db->prepare("
            INSERT INTO chat_participants (channel_id, user_id, last_read_at, joined_at)
            VALUES (:cid, :uid, datetime('now'), datetime('now'))
            ON CONFLICT(channel_id, user_id) DO UPDATE SET last_read_at = datetime('now')
        ");
        $stmt2->bindValue(':cid', $channelId, SQLITE3_TEXT);
        $stmt2->bindValue(':uid', $userId, SQLITE3_TEXT);
        $stmt2->execute();

        $db->exec('COMMIT');
    } catch (\Throwable $e) {
        $db->exec('ROLLBACK');
        error_log('chatSendMessage error: ' . $e->getMessage());
        return ['success' => false, 'message' => null, 'error' => 'Failed to send message.'];
    }

    // Fetch the inserted message with user info
    $message = queryOne("
        SELECT cm.*, u.name AS user_name, u.avatar_url AS user_avatar
        FROM chat_messages cm
        JOIN users u ON cm.user_id = u.id
        WHERE cm.id = :id
    ", [':id' => $messageId]);

    return ['success' => true, 'message' => $message, 'error' => null];
}

/**
 * Mark a channel as read for a user.
 */
function chatMarkRead(string $channelId, string $userId): void {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO chat_participants (channel_id, user_id, last_read_at, joined_at)
        VALUES (:cid, :uid, datetime('now'), datetime('now'))
        ON CONFLICT(channel_id, user_id) DO UPDATE SET last_read_at = datetime('now')
    ");
    $stmt->bindValue(':cid', $channelId, SQLITE3_TEXT);
    $stmt->bindValue(':uid', $userId, SQLITE3_TEXT);
    $stmt->execute();
}

/**
 * Get total unread count across all channels for FAB badge.
 */
function chatGetUnreadCount(string $userId): int {
    $row = queryOne("
        SELECT COALESCE(SUM(unread), 0) AS total FROM (
            SELECT (
                SELECT COUNT(*) FROM chat_messages cm
                WHERE cm.channel_id = cp.channel_id
                  AND cm.status IN ('published', 'flagged')
                  AND cm.user_id <> :uid
                  AND (cp.last_read_at IS NULL OR cm.created_at > cp.last_read_at)
            ) AS unread
            FROM chat_participants cp
            WHERE cp.user_id = :uid2
        )
    ", [':uid' => $userId, ':uid2' => $userId]);

    // Also count unread in general channel even if user hasn't joined
    $generalUnread = chatChannelUnreadCount(CHAT_GENERAL_CHANNEL_ID, $userId);
    $participatesInGeneral = queryOne(
        'SELECT 1 FROM chat_participants WHERE channel_id = :cid AND user_id = :uid',
        [':cid' => CHAT_GENERAL_CHANNEL_ID, ':uid' => $userId]
    );

    $total = (int)($row['total'] ?? 0);
    if (!$participatesInGeneral) {
        $total += $generalUnread;
    }

    return $total;
}

/**
 * Get display info for a user in chat context.
 * Returns ['name', 'initials', 'color'] — no email exposed.
 */
function chatUserDisplayInfo(array $user): array {
    $name = $user['user_name'] ?? $user['name'] ?? '';
    if ($name === '' && isset($user['email'])) {
        $name = explode('@', $user['email'])[0];
    }
    if ($name === '') {
        $name = 'User';
    }

    // Generate initials
    $parts = preg_split('/[\s._-]+/', $name);
    if (count($parts) >= 2) {
        $initials = strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[count($parts) - 1], 0, 1));
    } else {
        $initials = strtoupper(mb_substr($name, 0, 2));
    }

    // Deterministic color from user ID
    $colors = [
        'bg-blue-600', 'bg-emerald-600', 'bg-purple-600', 'bg-amber-600',
        'bg-rose-600', 'bg-teal-600', 'bg-indigo-600', 'bg-orange-600',
        'bg-pink-600', 'bg-sky-600', 'bg-lime-600', 'bg-cyan-600',
    ];
    $colorIndex = crc32($user['user_id'] ?? $user['id'] ?? $name) % count($colors);
    $color = $colors[abs($colorIndex)];

    return [
        'name' => $name,
        'initials' => $initials,
        'color' => $color,
    ];
}

/**
 * Format a chat timestamp for display (relative time).
 */
function chatRelativeTime(string $datetime): string {
    $now = time();
    $ts = strtotime($datetime . ' UTC');
    $diff = $now - $ts;

    if ($diff < 60) return 'now';
    if ($diff < 3600) return floor($diff / 60) . 'm';
    if ($diff < 86400) return floor($diff / 3600) . 'h';
    if ($diff < 604800) return floor($diff / 86400) . 'd';
    return date('M j', $ts);
}
