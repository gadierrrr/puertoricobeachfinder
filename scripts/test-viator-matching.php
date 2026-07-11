#!/usr/bin/env php
<?php
/**
 * Smoke tests for Viator catalog auto-matching, guide tour placements, and
 * product-level redirect resolution. Seeds fake catalog rows inside a
 * transaction and rolls back, so the database is left untouched.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/tours.php';

$checks = 0;
$failures = [];

$assert = static function (bool $condition, string $message) use (&$checks, &$failures): void {
    $checks++;
    if (!$condition) {
        $failures[] = $message;
    }
};

foreach (['viator_beach_products', 'guide_tour_placements', 'viator_municipality_destinations'] as $table) {
    if (!viatorTableExists($table)) {
        fwrite(STDERR, "Missing table {$table}. Run php scripts/migrate.php first.\n");
        exit(3);
    }
}

$db = getDb();
$db->exec('BEGIN');

try {
    // ---- Seed a small fake catalog -------------------------------------
    $seedProducts = [
        // Should match flamenco-beach: municipality in title + snorkeling tag.
        ['TEST100P1', 'Culebra Snorkeling Adventure by Boat', [36], 4.8, 200],
        // Should match Fajardo beaches tagged snorkeling: destination + tag.
        ['TEST200P2', 'Fajardo Catamaran Sail and Snorkel', [23854], 4.9, 500],
        // Should match nothing: destination-only score stays below threshold.
        ['TEST300P3', 'San Juan Food Walking Tour', [903], 5.0, 900],
        // Name-token match for flamenco-beach; outranks TEST100P1.
        ['TEST400P4', 'Flamenco Beach Day Trip with Lunch', [36], 4.7, 120],
        // Transport product: excluded even with perfect signals.
        ['TEST500P5', 'Flamenco Beach Airport Transfer from Culebra', [34162], 5.0, 400],
        // Snorkel product whose photo is missing: excluded (image stripped below).
        ['TEST600P6', 'Flamenco Beach Reef Snorkel Culebra', [34162], 4.9, 300],
    ];
    foreach ($seedProducts as [$code, $title, $destinations, $rating, $reviews]) {
        foreach (['en', 'es'] as $locale) {
            execute(
                'INSERT INTO viator_products
                    (product_code, locale, status, title, description, image_url, rating, review_count,
                     price_from, currency, fetched_at, product_url, campaign_value, tags_json,
                     destination_ids_json, source)
                 VALUES
                    (:code, :locale, "ACTIVE", :title, "Test description for matching.", "https://media.tacdn.com/test.jpg",
                     :rating, :reviews, 99, "USD", CURRENT_TIMESTAMP,
                     :url, "viator-tours-pr", "[]", :destinations, "catalog_search")',
                [
                    ':code' => $code,
                    ':locale' => $locale,
                    ':title' => $title,
                    ':rating' => $rating,
                    ':reviews' => $reviews,
                    ':url' => 'https://www.viator.com/tours/test/' . $code,
                    ':destinations' => json_encode($destinations),
                ]
            );
        }
    }

    execute('UPDATE viator_products SET image_url = "" WHERE product_code = "TEST600P6"');

    $flamenco = queryOne('SELECT * FROM beaches WHERE slug = "flamenco-beach"');
    $assert(is_array($flamenco), 'Expected flamenco-beach to exist');

    // An editor-blocked match must never be resurfaced as active.
    execute(
        'INSERT INTO viator_beach_products (beach_id, product_code, score, status)
         VALUES (:beach_id, "TEST100P1", 0, "blocked")',
        [':beach_id' => (string) $flamenco['id']]
    );

    // ---- Auto-matching ---------------------------------------------------
    $stats = viatorRebuildBeachMatches();
    $assert($stats['matches'] > 0, 'Rebuild should produce matches from the seeded catalog');

    $flamencoMatches = query(
        'SELECT product_code, score FROM viator_beach_products
         WHERE beach_id = :id AND status = "active" ORDER BY display_order ASC',
        [':id' => (string) $flamenco['id']]
    );
    $flamencoCodes = array_column($flamencoMatches ?: [], 'product_code');
    $assert(in_array('TEST400P4', $flamencoCodes, true), 'Name-token product should match flamenco-beach');
    $assert(($flamencoCodes[0] ?? '') === 'TEST400P4', 'Name-token match should rank first for flamenco-beach');
    $assert(!in_array('TEST100P1', $flamencoCodes, true), 'Blocked product must not resurface for flamenco-beach');

    $blockedRow = queryOne(
        'SELECT status FROM viator_beach_products WHERE beach_id = :id AND product_code = "TEST100P1"',
        [':id' => (string) $flamenco['id']]
    );
    $assert(((string) ($blockedRow['status'] ?? '')) === 'blocked', 'Blocked row should be preserved across rebuilds');

    // Score directly (membership in the top-2 depends on the live catalog).
    $icacos = queryOne('SELECT * FROM beaches WHERE slug = "icacos-beach"');
    $assert(is_array($icacos), 'Expected icacos-beach to exist');
    $fajardoProduct = queryOne(
        'SELECT product_code, title, rating, review_count, tags_json, destination_ids_json
         FROM viator_products WHERE product_code = "TEST200P2" AND locale = "en"'
    );
    $scored = viatorScoreProductForBeach($icacos, $fajardoProduct ?: [], []);
    $assert($scored['score'] >= 35.0, 'Fajardo destination + snorkeling tag product should clear the threshold for icacos-beach');
    $assert(
        (bool) array_filter($scored['reasons'], static fn(string $r): bool => str_starts_with($r, 'tag:')),
        'Fajardo product score should include an activity-tag reason'
    );
    $assert(
        in_array('destination:23854', $scored['reasons'], true),
        'Fajardo product score should include the destination reason'
    );

    $foodTourMatches = queryOne(
        'SELECT COUNT(*) AS n FROM viator_beach_products WHERE product_code = "TEST300P3" AND status = "active"'
    );
    $assert((int) ($foodTourMatches['n'] ?? 0) === 0, 'Destination-only food tour should stay below the match threshold');

    $transferMatches = queryOne(
        'SELECT COUNT(*) AS n FROM viator_beach_products WHERE product_code = "TEST500P5" AND status = "active"'
    );
    $assert((int) ($transferMatches['n'] ?? 0) === 0, 'Airport transfer products must never match beaches');

    $imagelessMatches = queryOne(
        'SELECT COUNT(*) AS n FROM viator_beach_products WHERE product_code = "TEST600P6" AND status = "active"'
    );
    $assert((int) ($imagelessMatches['n'] ?? 0) === 0, 'Products without an official photo must never match beaches');

    // Geographic-consistency guard: a Fajardo-destination Cayo Icacos charter
    // must not attach to Yabucoa's Playa Icacos by shared name alone, while
    // the real Icacos beach (10km from Fajardo) keeps its name signal.
    $icacosCharter = [
        'product_code' => 'GEOX', 'title' => 'Icacos Island Charter Adventure',
        'rating' => 4.9, 'review_count' => 300, 'tags_json' => '[]', 'destination_ids_json' => '[23854]',
    ];
    $yabucoaIcacos = queryOne('SELECT * FROM beaches WHERE slug = "playa-icacos"');
    if (is_array($yabucoaIcacos)) {
        $geoScore = viatorScoreProductForBeach($yabucoaIcacos, $icacosCharter, []);
        $assert(
            $geoScore['score'] < 35.0,
            'Distant name collision should stay below the threshold (got ' . $geoScore['score'] . ')'
        );
    }
    $geoScore = viatorScoreProductForBeach($icacos, $icacosCharter, []);
    $assert(
        (bool) array_filter($geoScore['reasons'], static fn(string $r): bool => str_starts_with($r, 'name_token:icacos')),
        'Nearby beach keeps its name signal under the geographic guard'
    );

    // Supplier promo noise is stripped from card descriptions.
    $cleaned = toursCleanDescription('Weekly update: Great conditions this week, July 6th-10th. Book now!! A guided reef snorkel with all equipment included and a certified local guide.');
    $assert(
        $cleaned === 'A guided reef snorkel with all equipment included and a certified local guide.',
        'Promo sentences should be stripped from descriptions'
    );
    $assert(
        toursCleanDescription('Book now!! Great deal!') === 'Book now!! Great deal!',
        'Cleaning falls back to the original when nothing meaningful remains'
    );

    // ---- Product-level URL + redirect fallback ---------------------------
    $assert(
        viatorProductLevelUrl('TEST200P2', 'en') === 'https://www.viator.com/tours/test/TEST200P2',
        'Product-level URL should resolve from the catalog cache'
    );
    $assert(viatorProductLevelUrl('NOPE404', 'en') === '', 'Unknown product code should resolve to empty');

    $resolved = referralResolveRedirect('viator-tours-fajardo', [
        'page_type' => 'beach',
        'page_slug' => 'icacos-beach',
        'placement' => 'tours_section',
        'locale' => 'en',
        'product_code' => 'TEST200P2',
        'match_type' => 'auto_product',
    ]);
    $assert(($resolved['ok'] ?? false) === true, 'Redirect for auto-matched product should resolve');
    $assert(
        ($resolved['target_url'] ?? '') === 'https://www.viator.com/tours/test/TEST200P2',
        'Redirect should use the exact product-level API URL'
    );
    $assert(($resolved['attribution_mode'] ?? '') === 'api_product_url', 'Redirect should report API attribution');

    // ---- Beach page rendering with an auto slot ---------------------------
    $en = renderToursSection($icacos, 'en', 'redesign');
    $assert(str_contains($en, 'match_type=curated_beach'), 'Icacos should keep its curated card');
    $assert(str_contains($en, 'match_type=auto_product'), 'Icacos should gain an auto-matched card');
    $assert(str_contains($en, 'Popular near this beach'), 'Auto card should use the auto kicker');
    $assert(str_contains($en, 'match_type=regional_browse'), 'Browse fallback card should remain');
    $assert(str_contains($en, 'Curated for this beach'), 'Curated card keeps its kicker');

    $es = renderToursSection($icacos, 'es', 'redesign');
    $assert(str_contains($es, 'Popular cerca de esta playa'), 'Spanish auto kicker should render');

    // ---- Guide tours section ----------------------------------------------
    $guideEn = renderGuideToursSection('snorkeling-guide', 'en');
    $assert(str_contains($guideEn, 'page_type=guide'), 'Guide cards should carry guide page_type context');
    $assert(str_contains($guideEn, 'page_slug=snorkeling-guide'), 'Guide cards should carry the guide slug');
    $assert(str_contains($guideEn, 'placement=guide_tours'), 'Guide cards should carry the guide placement key');
    $assert(str_contains($guideEn, 'match_type=curated_guide'), 'Guide product cards should use curated_guide match type');
    $assert(substr_count($guideEn, 'View on Viator') === 3, 'Snorkeling guide should render its three curated products');
    $assert(str_contains($guideEn, 'Book these experiences'), 'Guide section heading should render');

    $guideEs = renderGuideToursSection('snorkeling-guide', 'es');
    $assert(str_contains($guideEs, 'Reserva estas experiencias'), 'Spanish guide heading should render');
    $assert(substr_count($guideEs, 'Ver en Viator') === 3, 'Spanish guide should render its three curated products');

    $guideNone = renderGuideToursSection('beach-safety-tips', 'en');
    $assert($guideNone === '', 'Guides without placements should render nothing');

    $bioBays = renderGuideToursSection('bioluminescent-bays', 'en');
    $assert(substr_count($bioBays, 'View on Viator') === 2, 'Bio bays guide should render two curated products');
    $assert(str_contains($bioBays, 'match_type=regional_browse'), 'Bio bays guide should include its browse fallback');
} finally {
    $db->exec('ROLLBACK');
}

$leftover = queryOne('SELECT COUNT(*) AS n FROM viator_products WHERE product_code LIKE "TEST%"');
$assert((int) ($leftover['n'] ?? 0) === 0, 'Rollback should leave no seeded products behind');

if ($failures === []) {
    echo "Viator matching tests passed ({$checks} checks).\n";
    exit(0);
}

echo 'Viator matching tests failed (' . count($failures) . " / {$checks} failed):\n";
foreach ($failures as $index => $failure) {
    echo '  ' . ($index + 1) . '. ' . $failure . "\n";
}
exit(1);
