<?php
/** Add curated placements for canonical beach slugs used by production. */

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

echo "Starting migration: Viator production beach aliases\n";

$placements = [
    'viator-product-icacos-catamaran' => ['cayo-icacos-la-cordillera'],
    'viator-product-escambron-snorkel' => ['balneario-el-escambron'],
];

$created = 0;
foreach ($placements as $campaignSlug => $beachSlugs) {
    $campaign = queryOne('SELECT id FROM referral_campaigns WHERE slug = :slug', [':slug' => $campaignSlug]);
    if (!$campaign) {
        echo "ERROR: campaign {$campaignSlug} not found; run migration 047 first.\n";
        exit(1);
    }

    foreach ($beachSlugs as $beachSlug) {
        $beach = queryOne('SELECT id FROM beaches WHERE slug = :slug', [':slug' => $beachSlug]);
        if (!$beach) {
            echo "WARNING: beach {$beachSlug} not found; placement skipped\n";
            continue;
        }

        $existing = queryOne(
            'SELECT id FROM beach_referral_placements
             WHERE beach_id = :beach_id AND campaign_id = :campaign_id AND anchor_key = "tours_curated"
             LIMIT 1',
            [':beach_id' => $beach['id'], ':campaign_id' => $campaign['id']]
        );
        if ($existing) {
            execute(
                'UPDATE beach_referral_placements
                 SET enabled = 1, locale = "all", display_order = 0, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id',
                [':id' => $existing['id']]
            );
            continue;
        }

        execute(
            'INSERT INTO beach_referral_placements
                (id, beach_id, anchor_key, campaign_id, block_id, locale, enabled, display_order, created_at, updated_at)
             VALUES
                (:id, :beach_id, "tours_curated", :campaign_id, NULL, "all", 1, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
            [':id' => uuid(), ':beach_id' => $beach['id'], ':campaign_id' => $campaign['id']]
        );
        $created++;
    }
}

echo "Migration 050 complete ({$created} new placements).\n";
