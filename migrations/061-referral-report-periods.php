<?php
/** Viator campaign exports are period totals, with separate API/link rows. */
require_once __DIR__ . '/../inc/db.php';
$db = getDb();
if (!$db->exec('CREATE TABLE IF NOT EXISTS referral_campaign_reports (
    id TEXT PRIMARY KEY,
    provider_id TEXT NOT NULL REFERENCES referral_providers(id) ON DELETE CASCADE,
    campaign_id TEXT REFERENCES referral_campaigns(id) ON DELETE SET NULL,
    period_start TEXT NOT NULL,
    period_end TEXT NOT NULL,
    source_type TEXT NOT NULL DEFAULT "",
    campaign_value TEXT NOT NULL,
    product_code TEXT NOT NULL DEFAULT "",
    visitors INTEGER NOT NULL DEFAULT 0,
    bookings INTEGER NOT NULL DEFAULT 0,
    booking_value REAL NOT NULL DEFAULT 0,
    commission_value REAL NOT NULL DEFAULT 0,
    currency TEXT NOT NULL DEFAULT "USD",
    raw_json TEXT,
    imported_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (provider_id, period_start, period_end, source_type, campaign_value, product_code, currency)
)')) throw new RuntimeException($db->lastErrorMsg());
if (!$db->exec('INSERT OR IGNORE INTO referral_campaign_reports
    (id, provider_id, campaign_id, period_start, period_end, campaign_value, product_code,
     visitors, bookings, booking_value, commission_value, currency, raw_json, imported_at)
    SELECT id, provider_id, campaign_id, report_date, report_date, campaign_value, product_code,
           visitors, bookings, booking_value, commission_value, currency, raw_json, imported_at
    FROM referral_campaign_daily')) throw new RuntimeException($db->lastErrorMsg());
$db->exec('CREATE INDEX IF NOT EXISTS idx_referral_reports_period ON referral_campaign_reports(provider_id, period_start, period_end)');
echo "Referral reports now retain their reporting period and source type.\n";
