<?php
/**
 * Migration 051: Viator catalog schema + guide tour placements.
 *
 * Scales the Viator integration beyond the eight curated products:
 * - Destination and tag taxonomy caches (synced from the Viator API)
 * - Municipality -> Viator destination mapping (seeded for known IDs,
 *   extended by scripts/sync-viator-catalog.php via name matching)
 * - Product-level attributed URL + matching metadata on viator_products
 * - viator_beach_products: auto-matched products per beach
 * - guide_tour_placements: curated Viator offers on guide pages
 *
 * Idempotent — safe to re-run.
 */

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

echo "Starting migration: Viator catalog + guide placements\n";

$db = getDb();

$db->exec('CREATE TABLE IF NOT EXISTS viator_destinations (
    destination_id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    destination_type TEXT,
    parent_destination_id INTEGER,
    lookup_id TEXT,
    latitude REAL,
    longitude REAL,
    raw_json TEXT,
    fetched_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_viator_destinations_name
    ON viator_destinations(name)');

$db->exec('CREATE TABLE IF NOT EXISTS viator_tags (
    tag_id INTEGER PRIMARY KEY,
    name_en TEXT,
    name_es TEXT,
    parent_tag_ids_json TEXT,
    fetched_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
)');

$db->exec('CREATE TABLE IF NOT EXISTS viator_municipality_destinations (
    municipality_slug TEXT PRIMARY KEY,
    destination_id INTEGER NOT NULL,
    source TEXT NOT NULL DEFAULT "seed",
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
)');

$db->exec('CREATE TABLE IF NOT EXISTS viator_beach_products (
    beach_id TEXT NOT NULL,
    product_code TEXT NOT NULL,
    score REAL NOT NULL DEFAULT 0,
    match_reasons TEXT,
    status TEXT NOT NULL DEFAULT "active",
    display_order INTEGER NOT NULL DEFAULT 0,
    matched_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (beach_id, product_code)
)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_viator_beach_products_beach
    ON viator_beach_products(beach_id, status, display_order)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_viator_beach_products_code
    ON viator_beach_products(product_code)');

$db->exec('CREATE TABLE IF NOT EXISTS guide_tour_placements (
    id TEXT PRIMARY KEY,
    guide_slug TEXT NOT NULL,
    campaign_id TEXT NOT NULL,
    display_order INTEGER NOT NULL DEFAULT 0,
    enabled INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (guide_slug, campaign_id),
    FOREIGN KEY (campaign_id) REFERENCES referral_campaigns(id) ON DELETE CASCADE
)');
$db->exec('CREATE INDEX IF NOT EXISTS idx_guide_tour_placements_guide
    ON guide_tour_placements(guide_slug, enabled, display_order)');

// Product-level attributed URL + matching metadata. The campaign-scoped links
// in viator_product_links stay authoritative for curated placements; these
// columns let auto-matched placements resolve an exact product URL too.
$productColumns = [];
foreach (query('PRAGMA table_info(viator_products)') as $column) {
    $productColumns[] = (string) $column['name'];
}
$newColumns = [
    'product_url' => 'TEXT',
    'campaign_value' => 'TEXT',
    'tags_json' => 'TEXT',
    'destination_ids_json' => 'TEXT',
    'source' => 'TEXT DEFAULT "curated_sync"',
];
foreach ($newColumns as $name => $type) {
    if (!in_array($name, $productColumns, true)) {
        $db->exec('ALTER TABLE viator_products ADD COLUMN ' . $name . ' ' . $type);
        echo "Added viator_products.{$name}\n";
    }
}

// Municipality -> destination seeds verified from live Viator product URLs
// (migration 047). The catalog sync extends this table by name matching.
$destinationSeeds = [
    // destination_id, name, type, parent
    [36, 'Puerto Rico', 'COUNTRY', null],
    [903, 'San Juan', 'CITY', 36],
    [23854, 'Fajardo', 'CITY', 36],
    [25616, 'Rincon', 'CITY', 36],
    [22812, 'Vieques', 'CITY', 36],
];
foreach ($destinationSeeds as [$destId, $name, $type, $parent]) {
    execute(
        'INSERT INTO viator_destinations (destination_id, name, destination_type, parent_destination_id)
         VALUES (:id, :name, :type, :parent)
         ON CONFLICT(destination_id) DO NOTHING',
        [':id' => $destId, ':name' => $name, ':type' => $type, ':parent' => $parent]
    );
}

$municipalitySeeds = [
    'san-juan' => 903,
    'carolina' => 903,
    'fajardo' => 23854,
    'rincon' => 25616,
    'vieques' => 22812,
];
foreach ($municipalitySeeds as $slug => $destId) {
    execute(
        'INSERT INTO viator_municipality_destinations (municipality_slug, destination_id, source)
         VALUES (:slug, :dest, "seed")
         ON CONFLICT(municipality_slug) DO UPDATE SET
            destination_id = excluded.destination_id,
            updated_at = CURRENT_TIMESTAMP',
        [':slug' => $slug, ':dest' => $destId]
    );
}

// Guide tour placements: curated product campaigns plus one regional browse
// fallback per guide. Slugs are the public/guides/*.php basenames.
$guidePlacements = [
    'bioluminescent-bays' => [
        'viator-product-vieques-biobay-kayak',
        'viator-product-la-parguera-biobay',
        'viator-tours-fajardo',
    ],
    'snorkeling-guide' => [
        'viator-product-escambron-snorkel',
        'viator-product-rincon-snorkel',
        'viator-product-culebra-snorkel',
    ],
    'culebra-vs-vieques' => [
        'viator-product-culebra-snorkel',
        'viator-product-vieques-biobay-kayak',
    ],
    'surfing-guide' => [
        'viator-product-rincon-surf-lesson',
        'viator-tours-rincon',
    ],
    'spring-break-beaches-puerto-rico' => [
        'viator-product-icacos-catamaran',
        'viator-product-culebra-snorkel',
    ],
    'kid-friendly-beaches' => [
        'viator-product-escambron-snorkel',
        'viator-tours-pr',
    ],
    'getting-to-puerto-rico-beaches' => [
        'viator-product-culebra-snorkel',
        'viator-tours-pr',
    ],
    'family-beach-vacation-planning' => [
        'viator-product-icacos-catamaran',
        'viator-tours-pr',
    ],
    'best-time-visit-puerto-rico-beaches' => [
        'viator-tours-pr',
    ],
];

$created = 0;
foreach ($guidePlacements as $guideSlug => $campaignSlugs) {
    foreach ($campaignSlugs as $order => $campaignSlug) {
        $campaign = queryOne('SELECT id FROM referral_campaigns WHERE slug = :slug', [':slug' => $campaignSlug]);
        if (!$campaign) {
            echo "WARNING: campaign {$campaignSlug} not found; guide placement skipped\n";
            continue;
        }

        $existing = queryOne(
            'SELECT id FROM guide_tour_placements WHERE guide_slug = :guide AND campaign_id = :campaign LIMIT 1',
            [':guide' => $guideSlug, ':campaign' => $campaign['id']]
        );
        if ($existing) {
            execute(
                'UPDATE guide_tour_placements
                 SET display_order = :order, enabled = 1, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id',
                [':order' => $order, ':id' => $existing['id']]
            );
            continue;
        }

        execute(
            'INSERT INTO guide_tour_placements (id, guide_slug, campaign_id, display_order, enabled)
             VALUES (:id, :guide, :campaign, :order, 1)',
            [':id' => uuid(), ':guide' => $guideSlug, ':campaign' => $campaign['id'], ':order' => $order]
        );
        $created++;
    }
}

echo "Migration 051 complete ({$created} new guide placements).\n";
