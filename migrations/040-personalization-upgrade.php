<?php
/**
 * Migration 040: Personalization upgrade
 *  - Adds users.welcome_seen so the post-onboarding welcome modal can be shown
 *    exactly once to EVERY user (including users who signed up before the modal
 *    existed, and users who skipped onboarding), not only right after onboarding.
 *
 * No data is rewritten. Existing users default to welcome_seen = 0, so each will
 * see the welcome once on their next visit, then it is marked seen permanently.
 *
 * The companion "For You" favorites fallback (public/index.php) needs no schema.
 *
 * Idempotent — safe to re-run.
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: personalization upgrade\n";

$db = getDb();

$cols = [];
$res = $db->query('PRAGMA table_info(users)');
while ($res && ($c = $res->fetchArray(SQLITE3_ASSOC))) {
    $cols[$c['name']] = true;
}

if (!isset($cols['welcome_seen'])) {
    $db->exec('ALTER TABLE users ADD COLUMN welcome_seen INTEGER DEFAULT 0');
    echo "  ✓ users.welcome_seen\n";
} else {
    echo "  - users.welcome_seen already exists\n";
}

echo "Personalization migration complete.\n";
