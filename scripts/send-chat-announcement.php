<?php
/**
 * Send Chat Feature Announcement to all eligible users.
 *
 * Usage:
 *   php scripts/send-chat-announcement.php              # Send to all
 *   php scripts/send-chat-announcement.php --dry-run     # Preview count only
 *   php scripts/send-chat-announcement.php --limit=10    # Send to first N users
 */

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/env.php';
loadEnvFile();
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/email.php';

$dryRun = in_array('--dry-run', $argv);
$limit = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(1, (int)substr($arg, 8));
    }
}

echo "=== Chat Feature Announcement Email ===\n\n";

// Get all users with email addresses
$sql = "SELECT id, email, name FROM users WHERE email IS NOT NULL AND email <> '' ORDER BY created_at ASC";
if ($limit) {
    $sql .= " LIMIT $limit";
}
$users = query($sql);

if (!$users) {
    echo "No users found.\n";
    exit(0);
}

// Filter out suppressed contacts
$eligible = [];
foreach ($users as $u) {
    $emailHash = hash('sha256', strtolower(trim($u['email'])));
    $contact = queryOne(
        'SELECT unsubscribed FROM email_contacts WHERE email_hash = :hash',
        [':hash' => $emailHash]
    );
    if ($contact && $contact['unsubscribed']) {
        continue; // skip unsubscribed
    }
    $eligible[] = $u;
}

$total = count($users);
$eligibleCount = count($eligible);
$suppressed = $total - $eligibleCount;

echo "Total users:      $total\n";
echo "Eligible to send: $eligibleCount\n";
echo "Suppressed/unsub: $suppressed\n";

if ($limit) {
    echo "Limit:            $limit\n";
}
echo "\n";

if ($dryRun) {
    echo "[DRY RUN] No emails sent.\n";
    exit(0);
}

if ($eligibleCount === 0) {
    echo "No eligible users. Exiting.\n";
    exit(0);
}

// Send emails
$sent = 0;
$failed = 0;

foreach ($eligible as $i => $user) {
    $name = $user['name'] ?: explode('@', $user['email'])[0];
    $num = $i + 1;

    $ok = sendTemplateEmail('chat-announcement', $user['email'], [
        'name' => $name,
    ]);

    if ($ok) {
        $sent++;
        echo "  [$num/$eligibleCount] Sent to {$user['email']}\n";
    } else {
        $failed++;
        echo "  [$num/$eligibleCount] FAILED: {$user['email']}\n";
    }

    // 100ms delay between sends to respect rate limits
    usleep(100000);
}

echo "\n=== Done ===\n";
echo "Sent:   $sent\n";
echo "Failed: $failed\n";
