<?php
/**
 * Migration 053: Curate the three Viator products that actually booked.
 *
 * Viator Performance Trends (Jul 21 – Aug 19, 2026) showed every booking came
 * from San Juan water activities: the guided turtle snorkel (45107P2,
 * 18k+ reviews), the VIP turtle snorkel (486634P2) and the San Juan Bay jet
 * ski tour (395618P1). Auto-matched adventure products drove 82 visitors and
 * zero bookings in the same window.
 *
 * This migration promotes the proven products to curated placements across
 * the San Juan metro beach pages, the snorkeling/kid-friendly guides and the
 * San Juan cluster landing pages (which reuse guide_tour_placements).
 *
 * Product codes and URLs verified against the live Viator API on 2026-08-20.
 * Idempotent — safe to re-run.
 */

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

echo "Starting migration: Viator San Juan winners\n";

$viator = queryOne("SELECT id FROM referral_providers WHERE slug = 'viator'");
if (!$viator) {
    echo "ERROR: viator provider not found — run migration 041 first.\n";
    exit(1);
}
$viatorId = (string) $viator['id'];

$offers = [
    [
        'slug' => 'viator-product-san-juan-turtle-snorkel',
        'name' => 'San Juan Guided Turtle Snorkel Tour and Complimentary Videos',
        'scope' => 'san_juan',
        'url' => 'https://www.viator.com/tours/San-Juan/Guided-Snorkel-Tour/d903-45107P2',
        'product_code' => '45107P2',
    ],
    [
        'slug' => 'viator-product-san-juan-vip-snorkel',
        'name' => 'San Juan VIP-Snorkeling with Turtles and Complimentary Videos',
        'scope' => 'san_juan',
        'url' => 'https://www.viator.com/tours/San-Juan/Snorkeling-with-Turtles-Adventure/d903-486634P2',
        'product_code' => '486634P2',
    ],
    [
        'slug' => 'viator-product-san-juan-jet-ski',
        'name' => 'Jet Ski Tour through San Juan Bay',
        'scope' => 'san_juan',
        'url' => 'https://www.viator.com/tours/San-Juan/Jet-ski-tour-through-San-Juan-Bay/d903-395618P1',
        'product_code' => '395618P1',
    ],
];

$campaignIds = [];
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
    $campaignIds[$offer['slug']] = $campaignId;

    execute(
        'INSERT INTO viator_campaign_products (campaign_id, product_code, created_at, updated_at)
         VALUES (:campaign_id, :product_code, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
         ON CONFLICT(campaign_id) DO UPDATE SET
            product_code = excluded.product_code,
            updated_at = CURRENT_TIMESTAMP',
        [':campaign_id' => $campaignId, ':product_code' => $offer['product_code']]
    );
}

/**
 * Beach placements. Ordering favors the product whose meeting point is
 * closest to the beach: turtle/VIP snorkels meet at Escambrón, the jet ski
 * departs from Pier 10 on San Juan Bay.
 */
$beachPlacements = [
    // Escambrón cluster — snorkels meet right here.
    'balneario-el-escambron' => [['viator-product-san-juan-turtle-snorkel', 0], ['viator-product-san-juan-vip-snorkel', 1]],
    'la-ocho-escambron-east-reef' => [['viator-product-san-juan-turtle-snorkel', 0], ['viator-product-san-juan-vip-snorkel', 1]],
    'playa-del-capitolio' => [['viator-product-san-juan-turtle-snorkel', 0], ['viator-product-san-juan-vip-snorkel', 1]],
    'playa-puerta-de-tierra' => [['viator-product-san-juan-turtle-snorkel', 0], ['viator-product-san-juan-vip-snorkel', 1]],
    // Condado — jet ski departs across the lagoon/bay.
    'condado-beach' => [['viator-product-san-juan-jet-ski', 0], ['viator-product-san-juan-turtle-snorkel', 1]],
    'playita-del-condado' => [['viator-product-san-juan-jet-ski', 0], ['viator-product-san-juan-turtle-snorkel', 1]],
    'playita-de-la-laguna-del-condado' => [['viator-product-san-juan-jet-ski', 0], ['viator-product-san-juan-turtle-snorkel', 1]],
    'condado-playita-ochoa-pocket' => [['viator-product-san-juan-jet-ski', 0], ['viator-product-san-juan-turtle-snorkel', 1]],
    'playa-ashford' => [['viator-product-san-juan-jet-ski', 0], ['viator-product-san-juan-turtle-snorkel', 1]],
    // Old San Juan — Pier 10 is walkable.
    'playa-pena-old-san-juan' => [['viator-product-san-juan-jet-ski', 0], ['viator-product-san-juan-turtle-snorkel', 1]],
    'playita-puerta-de-san-juan' => [['viator-product-san-juan-jet-ski', 0], ['viator-product-san-juan-turtle-snorkel', 1]],
    // Ocean Park & east San Juan.
    'ocean-park-beach' => [['viator-product-san-juan-turtle-snorkel', 0], ['viator-product-san-juan-jet-ski', 1]],
    'ocean-park-barbosa-segment' => [['viator-product-san-juan-turtle-snorkel', 0], ['viator-product-san-juan-jet-ski', 1]],
    'punta-las-marias' => [['viator-product-san-juan-turtle-snorkel', 0], ['viator-product-san-juan-jet-ski', 1]],
    // Isla Verde / Carolina — 15 minutes from both meeting points.
    'isla-verde-beach' => [['viator-product-san-juan-turtle-snorkel', 0], ['viator-product-san-juan-jet-ski', 1]],
    'balneario-isla-verde' => [['viator-product-san-juan-turtle-snorkel', 0], ['viator-product-san-juan-jet-ski', 1]],
    'isla-verde-pine-grove' => [['viator-product-san-juan-turtle-snorkel', 0], ['viator-product-san-juan-jet-ski', 1]],
    'balneario-de-carolina' => [['viator-product-san-juan-turtle-snorkel', 0], ['viator-product-san-juan-jet-ski', 1]],
];

$placed = 0;
foreach ($beachPlacements as $beachSlug => $placements) {
    $beach = queryOne('SELECT id FROM beaches WHERE slug = :slug', [':slug' => $beachSlug]);
    if (!$beach) {
        echo "WARNING: beach {$beachSlug} not found; placement skipped\n";
        continue;
    }

    foreach ($placements as [$campaignSlug, $order]) {
        $campaignId = $campaignIds[$campaignSlug];
        $existing = queryOne(
            'SELECT id FROM beach_referral_placements
             WHERE beach_id = :beach_id AND campaign_id = :campaign_id AND anchor_key = "tours_curated"
             LIMIT 1',
            [':beach_id' => $beach['id'], ':campaign_id' => $campaignId]
        );

        if ($existing) {
            execute(
                'UPDATE beach_referral_placements
                 SET locale = "all", enabled = 1, display_order = :order, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id',
                [':order' => $order, ':id' => $existing['id']]
            );
        } else {
            execute(
                'INSERT INTO beach_referral_placements
                    (id, beach_id, anchor_key, campaign_id, block_id, locale, enabled, display_order, created_at, updated_at)
                 VALUES
                    (:id, :beach_id, "tours_curated", :campaign_id, NULL, "all", 1, :order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
                [':id' => uuid(), ':beach_id' => $beach['id'], ':campaign_id' => $campaignId, ':order' => $order]
            );
        }
        $placed++;
    }
}

// The original Escambrón placement (393101P7: 10 visitors, 0 direct bookings)
// drops behind the two products visitors actually booked. It stays enabled as
// the fallback if either winner goes inactive.
$escambron = queryOne('SELECT id FROM beaches WHERE slug = :slug', [':slug' => 'balneario-el-escambron']);
$legacy = queryOne('SELECT id FROM referral_campaigns WHERE slug = :slug', [':slug' => 'viator-product-escambron-snorkel']);
if ($escambron && $legacy) {
    execute(
        'UPDATE beach_referral_placements
         SET display_order = 2, updated_at = CURRENT_TIMESTAMP
         WHERE beach_id = :beach_id AND campaign_id = :campaign_id AND anchor_key = "tours_curated"',
        [':beach_id' => $escambron['id'], ':campaign_id' => $legacy['id']]
    );
    echo "Demoted viator-product-escambron-snorkel on balneario-el-escambron\n";
}

/**
 * Guide + landing page placements. The San Juan cluster landing pages reuse
 * guide_tour_placements keyed by their public/ basenames.
 */
$guidePlacements = [
    'snorkeling-guide' => [
        'viator-product-san-juan-turtle-snorkel' => 0,
        'viator-product-escambron-snorkel' => 1,
        'viator-product-rincon-snorkel' => 2,
        'viator-product-culebra-snorkel' => 3,
    ],
    'kid-friendly-beaches' => [
        'viator-product-san-juan-turtle-snorkel' => 0,
        'viator-product-escambron-snorkel' => 1,
        'viator-tours-pr' => 2,
    ],
    'best-beaches-san-juan' => [
        'viator-product-san-juan-turtle-snorkel' => 0,
        'viator-product-san-juan-jet-ski' => 1,
        'viator-product-san-juan-vip-snorkel' => 2,
    ],
    'beaches-near-san-juan' => [
        'viator-product-san-juan-turtle-snorkel' => 0,
        'viator-product-san-juan-jet-ski' => 1,
        'viator-product-san-juan-vip-snorkel' => 2,
    ],
    'beaches-near-san-juan-airport' => [
        'viator-product-san-juan-turtle-snorkel' => 0,
        'viator-product-san-juan-jet-ski' => 1,
        'viator-product-san-juan-vip-snorkel' => 2,
    ],
];

$guidePlaced = 0;
foreach ($guidePlacements as $guideSlug => $campaignOrders) {
    foreach ($campaignOrders as $campaignSlug => $order) {
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
        } else {
            execute(
                'INSERT INTO guide_tour_placements (id, guide_slug, campaign_id, display_order, enabled)
                 VALUES (:id, :guide, :campaign, :order, 1)',
                [':id' => uuid(), ':guide' => $guideSlug, ':campaign' => $campaign['id'], ':order' => $order]
            );
        }
        $guidePlaced++;
    }
}

echo "Migration 053 complete ({$placed} beach placements, {$guidePlaced} guide placements).\n";
