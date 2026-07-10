<?php
/**
 * Migration 048: Viator API product cache and campaign-level reporting.
 *
 * Keeps changing supplier facts and exact API-generated affiliate URLs out of
 * PHP source while preserving the existing editorial beach/product matches.
 * Also stores aggregated Viator Performance Trends exports separately from
 * row-level referral conversions.
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: Viator API hydration + campaign reporting\n";

$db = getDb();

$db->exec('CREATE TABLE IF NOT EXISTS viator_campaign_products (
    campaign_id TEXT PRIMARY KEY,
    product_code TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES referral_campaigns(id) ON DELETE CASCADE
)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_viator_campaign_products_code
    ON viator_campaign_products(product_code)');

$db->exec('CREATE TABLE IF NOT EXISTS viator_products (
    product_code TEXT NOT NULL,
    locale TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT "UNKNOWN",
    title TEXT,
    description TEXT,
    image_url TEXT,
    rating REAL,
    review_count INTEGER NOT NULL DEFAULT 0,
    duration_minutes_min INTEGER,
    duration_minutes_max INTEGER,
    departure_summary TEXT,
    free_cancellation INTEGER,
    price_from REAL,
    currency TEXT,
    viator_last_updated_at TEXT,
    fetched_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    raw_json TEXT,
    PRIMARY KEY (product_code, locale)
)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_viator_products_status
    ON viator_products(status, fetched_at)');

$db->exec('CREATE TABLE IF NOT EXISTS viator_product_links (
    campaign_id TEXT NOT NULL,
    product_code TEXT NOT NULL,
    locale TEXT NOT NULL,
    campaign_value TEXT NOT NULL,
    product_url TEXT NOT NULL,
    fetched_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (campaign_id, locale),
    FOREIGN KEY (campaign_id) REFERENCES referral_campaigns(id) ON DELETE CASCADE
)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_viator_product_links_code
    ON viator_product_links(product_code, locale)');

$db->exec('CREATE TABLE IF NOT EXISTS viator_sync_runs (
    id TEXT PRIMARY KEY,
    status TEXT NOT NULL DEFAULT "running",
    environment TEXT NOT NULL DEFAULT "production",
    products_attempted INTEGER NOT NULL DEFAULT 0,
    products_updated INTEGER NOT NULL DEFAULT 0,
    errors_count INTEGER NOT NULL DEFAULT 0,
    started_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at TEXT,
    summary_json TEXT,
    error_log TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_viator_sync_runs_started
    ON viator_sync_runs(started_at DESC)');

$db->exec('CREATE TABLE IF NOT EXISTS referral_impressions (
    id TEXT PRIMARY KEY,
    event_id TEXT NOT NULL UNIQUE,
    provider_id TEXT NOT NULL,
    campaign_id TEXT NOT NULL,
    page_type TEXT,
    page_slug TEXT,
    placement_key TEXT,
    locale TEXT NOT NULL DEFAULT "en",
    match_type TEXT,
    product_code TEXT,
    api_hydrated INTEGER NOT NULL DEFAULT 0,
    anon_id TEXT,
    ip_hash TEXT,
    ua_hash TEXT,
    viewed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES referral_providers(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES referral_campaigns(id) ON DELETE CASCADE
)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_referral_impressions_campaign
    ON referral_impressions(campaign_id, viewed_at)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_referral_impressions_page
    ON referral_impressions(page_type, page_slug, viewed_at)');

$db->exec('CREATE TABLE IF NOT EXISTS referral_campaign_daily (
    id TEXT PRIMARY KEY,
    provider_id TEXT NOT NULL,
    campaign_id TEXT,
    report_date TEXT NOT NULL,
    source TEXT NOT NULL DEFAULT "viator_performance_csv",
    campaign_value TEXT NOT NULL,
    product_code TEXT NOT NULL DEFAULT "",
    visitors INTEGER NOT NULL DEFAULT 0,
    bookings INTEGER NOT NULL DEFAULT 0,
    booking_value REAL NOT NULL DEFAULT 0,
    commission_value REAL NOT NULL DEFAULT 0,
    currency TEXT NOT NULL DEFAULT "USD",
    raw_json TEXT,
    imported_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES referral_providers(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES referral_campaigns(id) ON DELETE SET NULL,
    UNIQUE (provider_id, report_date, campaign_value, product_code, currency)
)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_referral_campaign_daily_campaign
    ON referral_campaign_daily(campaign_id, report_date)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_referral_campaign_daily_provider
    ON referral_campaign_daily(provider_id, report_date)');

$seed = [
    'viator-product-icacos-catamaran' => '14939P2',
    'viator-product-culebra-snorkel' => '41096P7',
    'viator-product-vieques-biobay-kayak' => '225672P1',
    'viator-product-rincon-snorkel' => '10972P1',
    'viator-product-rincon-surf-lesson' => '489026P2',
    'viator-product-escambron-snorkel' => '393101P7',
    'viator-product-cueva-del-indio' => '322734P3',
    'viator-product-la-parguera-biobay' => '5535976P5',
];

foreach ($seed as $campaignSlug => $productCode) {
    $campaign = queryOne('SELECT id FROM referral_campaigns WHERE slug = :slug', [':slug' => $campaignSlug]);
    if (!$campaign) {
        echo "WARNING: campaign {$campaignSlug} not found; API mapping skipped\n";
        continue;
    }

    execute(
        'INSERT INTO viator_campaign_products (campaign_id, product_code, created_at, updated_at)
         VALUES (:campaign_id, :product_code, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
         ON CONFLICT(campaign_id) DO UPDATE SET
            product_code = excluded.product_code,
            updated_at = CURRENT_TIMESTAMP',
        [':campaign_id' => $campaign['id'], ':product_code' => $productCode]
    );
}

echo "Migration 048 complete.\n";
