<?php
/**
 * Weekly digest: "good beach days near your favorites".
 *
 * SAFE BY DEFAULT: does NOT send unless you pass --send. Without it, prints who
 * would be emailed (dry run). Honors user_preferences.weekly_digest (opt-out) and
 * email_contacts suppression, only emails users who have >=1 favorite currently
 * scoring "good", and includes a working unsubscribe link.
 *
 * Usage:
 *   php scripts/send-weekly-digest.php                     # dry run (default)
 *   php scripts/send-weekly-digest.php --test-to=me@x.com  # send only to this address' own digest
 *   php scripts/send-weekly-digest.php --send              # LIVE: send to all eligible users
 *   php scripts/send-weekly-digest.php --send --limit=5    # LIVE: first 5 eligible
 */

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/env.php';
loadEnvFile();
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/weather.php';
require_once __DIR__ . '/../inc/email.php';

$send   = in_array('--send', $argv, true);
$dryRun = !$send;
$limit  = null;
$testTo = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(1, (int) substr($arg, 8));
    }
    if (str_starts_with($arg, '--test-to=')) {
        $testTo = trim(substr($arg, 10));
    }
}
$SCORE_THRESHOLD = 60;

echo "=== Weekly Digest ===\n";
echo $dryRun ? "[DRY RUN — no emails sent] (add --send to send for real)\n\n" : "[LIVE SEND]\n\n";

$sql = "SELECT u.id, u.email, u.name FROM users u
        LEFT JOIN user_preferences p ON p.user_id = u.id
        WHERE u.email IS NOT NULL AND u.email <> '' AND COALESCE(p.weekly_digest, 1) = 1
        ORDER BY u.created_at ASC";
$users = query($sql) ?: [];

if ($testTo !== null) {
    $users = array_values(array_filter($users, static fn($u) => strcasecmp($u['email'], $testTo) === 0));
    if (!$users) {
        echo "No user found with email {$testTo}. (--test-to only sends a real user their own digest.)\n";
        exit(0);
    }
}
if ($limit) {
    $users = array_slice($users, 0, $limit);
}

$sent = 0;
$skippedNoGood = 0;
$skippedSuppressed = 0;
$failed = 0;

foreach ($users as $user) {
    // Suppression check.
    $contact = queryOne('SELECT unsubscribed FROM email_contacts WHERE email_hash = :h', [':h' => emailHash($user['email'])]);
    if ($contact && $contact['unsubscribed']) {
        $skippedSuppressed++;
        continue;
    }

    $favs = query(
        "SELECT b.* FROM beaches b
         INNER JOIN user_favorites uf ON b.id = uf.beach_id
         WHERE uf.user_id = :uid AND b.publish_status = 'published'",
        [':uid' => $user['id']]
    ) ?: [];
    if (empty($favs)) {
        $skippedNoGood++;
        continue;
    }

    $weatherMap = getBatchWeatherForBeaches($favs, max(1, count($favs)));

    $good = [];
    foreach ($favs as $b) {
        $w = $weatherMap[$b['id']] ?? null;
        if (!$w || empty($w['current']) || !is_array($w['current'])) {
            continue;
        }
        // Use the pre-computed beach_score (parseWeatherResponse set it on the RAW API
        // payload; recomputing on the parsed 'current' block reads keys that don't exist
        // there and silently inflates every score).
        $score = (int) ($w['current']['beach_score'] ?? 0);
        if ($score >= $SCORE_THRESHOLD) {
            $good[] = ['beach' => $b, 'score' => $score, 'weather' => $w];
        }
    }
    if (empty($good)) {
        $skippedNoGood++;
        continue;
    }

    usort($good, static fn($a, $b) => $b['score'] - $a['score']);
    $good = array_slice($good, 0, 5);

    $itemsHtml = '';
    foreach ($good as $g) {
        $bname = h($g['beach']['name'] ?? 'Beach');
        $muni  = h($g['beach']['municipality'] ?? '');
        $detailUrl = absoluteUrl('/beach/' . ($g['beach']['slug'] ?? ''));
        $rec = getBeachRecommendation($g['weather']);
        $verdict = h($rec['verdict'] ?? 'Good');
        $temp = isset($g['weather']['current']['temperature']) ? (' · ' . round((float) $g['weather']['current']['temperature']) . '°') : '';
        $itemsHtml .= "<li style=\"margin:0 0 12px;\"><strong style=\"color:#e2e8f0;\">{$bname}</strong> "
            . "<span style=\"color:#94a3b8;\">({$muni})</span> — "
            . "<span style=\"color:#4ade80;\">{$verdict} · {$g['score']}/100{$temp}</span><br>"
            . "<a href=\"{$detailUrl}\" style=\"color:#fb923c;text-decoration:none;\">View beach</a></li>";
    }

    $name = $user['name'] ?: explode('@', $user['email'])[0];
    $firstName = trim(explode(' ', (string) $name)[0]);
    $n = count($good);
    $intro = $n === 1
        ? '1 of your favorite beaches is looking great — check it out.'
        : "{$n} of your favorite beaches are looking great — check them out.";
    $recipient = $testTo ?: $user['email'];

    if ($dryRun) {
        echo "  [would send] {$recipient} — {$n} good beach(es): " . implode(', ', array_map(static fn($g) => $g['beach']['name'], $good)) . "\n";
        continue;
    }

    $ok = sendTemplateEmail('weekly-digest', $recipient, [
        'name' => $firstName,
        'intro' => $intro,
        'items_html' => $itemsHtml,
        'unsubscribe_url' => emailUnsubscribeUrl($recipient),
    ]);
    if ($ok) {
        $sent++;
        echo "  [sent] {$recipient} ({$n} beaches)\n";
    } else {
        $failed++;
        echo "  [FAILED] {$recipient}\n";
    }
    usleep(150000); // throttle ~150ms
}

echo "\n=== Done ===\n";
echo "Eligible recipients scanned: " . count($users) . "\n";
echo "Skipped (no good favorites):  {$skippedNoGood}\n";
echo "Skipped (unsubscribed):       {$skippedSuppressed}\n";
if (!$dryRun) {
    echo "Sent:   {$sent}\n";
    echo "Failed: {$failed}\n";
}
