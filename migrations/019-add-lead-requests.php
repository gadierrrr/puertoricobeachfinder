<?php
/**
 * Migration: Add lead_requests table for list/result sends
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: lead_requests\n";

$db = getDb();

$db->exec("
    CREATE TABLE IF NOT EXISTS lead_requests (
        id TEXT PRIMARY KEY,
        email TEXT NOT NULL,
        context_type TEXT NOT NULL, -- municipality|collection|quiz
        context_key TEXT NOT NULL,
        filters_query TEXT,
        page_path TEXT,
        ip_hash TEXT,
        requested_at TEXT DEFAULT CURRENT_TIMESTAMP
    )
");

$db->exec("CREATE INDEX IF NOT EXISTS idx_lead_requests_email ON lead_requests(email)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_lead_requests_context ON lead_requests(context_type, context_key)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_lead_requests_requested ON lead_requests(requested_at DESC)");

echo "✅ Migration completed: lead_requests\n";

