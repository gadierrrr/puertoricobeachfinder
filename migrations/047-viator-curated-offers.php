<?php
/**
 * Migration 047: Curated Viator product offers for high-intent beach pages.
 *
 * Regional destination campaigns remain the fallback. These product campaigns
 * are attached to exact beaches through beach_referral_placements so a visitor
 * sees a useful experience only when it genuinely matches the place they are
 * researching.
 *
 * Product pages and codes were verified live on Viator on 2026-07-09.
 * Idempotent — safe to re-run.
 */

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

echo "Starting migration: curated Viator offers\n";

$viator = queryOne("SELECT id FROM referral_providers WHERE slug = 'viator'");
if (!$viator) {
    echo "ERROR: viator provider not found — run migration 041 first.\n";
    exit(1);
}
$viatorId = (string) $viator['id'];

$offers = [
    [
        'slug' => 'viator-product-icacos-catamaran',
        'name' => 'Icacos Island Traveler Catamaran Snorkeling Tour',
        'scope' => 'fajardo',
        'url' => 'https://www.viator.com/tours/Fajardo/From-Fajardo-Icacos-Catamaran-Snorkeling-Tour/d23854-14939P2',
        'beaches' => ['icacos-beach', 'cayo-icacos-la-cordillera'],
    ],
    [
        'slug' => 'viator-product-culebra-snorkel',
        'name' => 'Culebra Snorkel and Beach Day with Lunch and Drinks',
        'scope' => 'culebra',
        'url' => 'https://www.viator.com/tours/Fajardo/Culebra-Beach-Tour/d23854-41096P7',
        'beaches' => ['flamenco-beach'],
    ],
    [
        'slug' => 'viator-product-vieques-biobay-kayak',
        'name' => 'Bioluminescent Bay Kayak Trip from Vieques',
        'scope' => 'vieques',
        'url' => 'https://www.viator.com/tours/Vieques/Bioluminescent-Bay-Kayak-Trip/d22812-225672P1',
        'beaches' => ['mosquito-bay-beach'],
    ],
    [
        'slug' => 'viator-product-rincon-snorkel',
        'name' => 'Rincon Snorkeling Adventure',
        'scope' => 'rincon',
        'url' => 'https://www.viator.com/tours/Rincon/Snorkeling-Adventure-Tour/d25616-10972P1',
        'beaches' => ['steps-beach-tres-palmas'],
    ],
    [
        'slug' => 'viator-product-rincon-surf-lesson',
        'name' => 'Surfing Lesson, Rincon PR',
        'scope' => 'rincon',
        'url' => 'https://www.viator.com/tours/Rincon/PRSurf-Rincon/d25616-489026P2',
        'beaches' => ['domes-beach', 'sandy-beach', 'sandy-beach-east'],
    ],
    [
        'slug' => 'viator-product-escambron-snorkel',
        'name' => 'San Juan Guided Snorkeling Experience',
        'scope' => 'san_juan',
        'url' => 'https://www.viator.com/tours/San-Juan/San-juan-Snorkeling-and-turtle-spotting/d903-393101P7',
        'beaches' => ['escambron-beach'],
    ],
    [
        'slug' => 'viator-product-cueva-del-indio',
        'name' => 'Taino Indian Cave, Arecibo Hike and Beach Tour',
        'scope' => 'arecibo',
        'url' => 'https://www.viator.com/tours/San-Juan/Cave-of-The-Taino-Indian-and-Beach-Tour-with-Transportation/d903-322734P3',
        'beaches' => ['cueva-del-indio-shore'],
    ],
    [
        'slug' => 'viator-product-la-parguera-biobay',
        'name' => 'Bioluminescent Bay Boat Tour in La Parguera',
        'scope' => 'lajas',
        'url' => 'https://www.viator.com/tours/Puerto-Rico/Bioluminescent-Bay-Night-Boat-Tour-in-La-Parguera-with-Captain/d36-5535976P5',
        'beaches' => ['la-parguera-bioluminescent-bay-entry', 'la-parguera-waterfront'],
    ],
];

foreach ($offers as $offer) {
    $campaign = queryOne('SELECT id FROM referral_campaigns WHERE slug = :slug', [':slug' => $offer['slug']]);
    if ($campaign) {
        $campaignId = (string) $campaign['id'];
        execute(
            'UPDATE referral_campaigns
             SET provider_id = :provider_id, name = :name, link_type = "tour_product",
                 destination_scope = :scope, target_url = :url, utm_json = "{}",
                 priority = 1, status = "active", updated_at = CURRENT_TIMESTAMP
             WHERE id = :id',
            [
                ':provider_id' => $viatorId,
                ':name' => $offer['name'],
                ':scope' => $offer['scope'],
                ':url' => $offer['url'],
                ':id' => $campaignId,
            ]
        );
        echo "Updated {$offer['slug']}\n";
    } else {
        $campaignId = uuid();
        execute(
            'INSERT INTO referral_campaigns
                (id, provider_id, slug, name, link_type, destination_scope, target_url, utm_json, priority, status)
             VALUES
                (:id, :provider_id, :slug, :name, "tour_product", :scope, :url, "{}", 1, "active")',
            [
                ':id' => $campaignId,
                ':provider_id' => $viatorId,
                ':slug' => $offer['slug'],
                ':name' => $offer['name'],
                ':scope' => $offer['scope'],
                ':url' => $offer['url'],
            ]
        );
        echo "Created {$offer['slug']}\n";
    }

    foreach ($offer['beaches'] as $beachSlug) {
        $beach = queryOne('SELECT id FROM beaches WHERE slug = :slug', [':slug' => $beachSlug]);
        if (!$beach) {
            echo "WARNING: beach {$beachSlug} not found; placement skipped\n";
            continue;
        }

        $existing = queryOne(
            'SELECT id FROM beach_referral_placements
             WHERE beach_id = :beach_id AND campaign_id = :campaign_id AND anchor_key = "tours_curated"
             LIMIT 1',
            [':beach_id' => $beach['id'], ':campaign_id' => $campaignId]
        );

        if ($existing) {
            execute(
                'UPDATE beach_referral_placements
                 SET locale = "all", enabled = 1, display_order = 0, updated_at = CURRENT_TIMESTAMP
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
            [':id' => uuid(), ':beach_id' => $beach['id'], ':campaign_id' => $campaignId]
        );
    }
}

echo "Migration 047 complete.\n";
