<?php
/**
 * Migration 045: Daily beach page view aggregates.
 *
 * Stores one row per beach per Puerto Rico calendar date so homepage
 * "Popular now" can show yesterday's most visited beach pages without
 * depending on a third-party analytics API at render time.
 *
 * Idempotent — safe to re-run.
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: beach_page_views_daily table\n";

$db = getDb();

$db->exec("
    CREATE TABLE IF NOT EXISTS beach_page_views_daily (
        view_date TEXT NOT NULL,
        beach_id TEXT NOT NULL,
        views INTEGER NOT NULL DEFAULT 0,
        updated_at TEXT NOT NULL DEFAULT (datetime('now')),
        PRIMARY KEY (view_date, beach_id),
        FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE
    )
");

$db->exec('CREATE INDEX IF NOT EXISTS idx_beach_page_views_daily_date_views ON beach_page_views_daily(view_date, views DESC)');

echo "Done: beach_page_views_daily table ready\n";
