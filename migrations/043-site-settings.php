<?php
/**
 * Migration 043: site_settings key/value store.
 *
 * Generic settings table for site-wide configuration edited from the admin
 * panel. First consumer: the homepage design editor (/admin/homepage-design)
 * which stores its design JSON under the "homepage_design" key.
 *
 * Idempotent — safe to re-run.
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: site_settings table\n";

$db = getDb();

$db->exec("
    CREATE TABLE IF NOT EXISTS site_settings (
        key TEXT PRIMARY KEY,
        value TEXT NOT NULL,
        updated_at TEXT NOT NULL DEFAULT (datetime('now'))
    )
");

echo "Done: site_settings table ready\n";
