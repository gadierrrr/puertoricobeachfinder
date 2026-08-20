<?php
/**
 * Migration 054: monthly API-usage counters for the Google Places photo proxy.
 *
 * The photo endpoints enforce hard monthly caps so the Places bill can never
 * exceed the configured budget (see inc/google_photos.php). Counters are keyed
 * by calendar month (UTC) + kind ('details' | 'media') and only ever grow
 * within a month; old rows are left as a cheap audit trail.
 *
 * Idempotent: CREATE TABLE IF NOT EXISTS.
 */

require_once __DIR__ . '/../inc/db.php';

echo "Migration 054: creating api_usage_counters table...\n";

getDb()->exec('
    CREATE TABLE IF NOT EXISTS api_usage_counters (
        period TEXT NOT NULL,
        kind TEXT NOT NULL,
        count INTEGER NOT NULL DEFAULT 0,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (period, kind)
    )
');

echo "Done.\n";
