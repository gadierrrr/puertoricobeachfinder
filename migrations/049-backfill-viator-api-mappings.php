<?php
/**
 * Migration 049: Backfill API mappings after curated campaigns exist.
 *
 * Some production databases receive migration 048 before the optional curated
 * offer seed. This idempotent follow-up makes the dependency explicit and
 * refuses to silently launch with an empty hydration set.
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: backfill Viator API mappings\n";

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

$mapped = 0;
foreach ($seed as $campaignSlug => $productCode) {
    $campaign = queryOne('SELECT id FROM referral_campaigns WHERE slug = :slug', [':slug' => $campaignSlug]);
    if (!$campaign) {
        echo "ERROR: campaign {$campaignSlug} not found; run migration 047 first.\n";
        exit(1);
    }

    execute(
        'INSERT INTO viator_campaign_products (campaign_id, product_code, created_at, updated_at)
         VALUES (:campaign_id, :product_code, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
         ON CONFLICT(campaign_id) DO UPDATE SET
            product_code = excluded.product_code,
            updated_at = CURRENT_TIMESTAMP',
        [':campaign_id' => $campaign['id'], ':product_code' => $productCode]
    );
    $mapped++;
}

echo "Migration 049 complete ({$mapped} mappings).\n";
