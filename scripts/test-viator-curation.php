#!/usr/bin/env php
<?php
/**
 * Read-only smoke tests for curated Viator beach offers.
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

$curatedCases = [
    [['icacos-beach', 'cayo-icacos-la-cordillera'], 'viator-product-icacos-catamaran'],
    [['flamenco-beach'], 'viator-product-culebra-snorkel'],
    [['mosquito-bay-beach'], 'viator-product-vieques-biobay-kayak'],
    [['steps-beach-tres-palmas'], 'viator-product-rincon-snorkel'],
    [['domes-beach'], 'viator-product-rincon-surf-lesson'],
    [['escambron-beach', 'balneario-el-escambron'], 'viator-product-escambron-snorkel'],
    [['cueva-del-indio-shore'], 'viator-product-cueva-del-indio'],
    [['la-parguera-bioluminescent-bay-entry'], 'viator-product-la-parguera-biobay'],
];

foreach ($curatedCases as [$beachSlugs, $campaignSlug]) {
    $beach = null;
    foreach ($beachSlugs as $candidateSlug) {
        $beach = queryOne('SELECT * FROM beaches WHERE slug = :slug', [':slug' => $candidateSlug]);
        if ($beach) {
            break;
        }
    }
    $beachSlug = (string) ($beach['slug'] ?? implode(' or ', $beachSlugs));
    $assert(is_array($beach), 'Expected beach ' . implode(' or ', $beachSlugs));
    if (!$beach) {
        continue;
    }

    $offers = toursCampaignsForBeach($beach, 2);
    $assert(($offers[0]['slug'] ?? '') === $campaignSlug, "Expected {$campaignSlug} first for {$beachSlug}");
    $assert(($offers[0]['link_type'] ?? '') === 'tour_product', "Expected product offer for {$beachSlug}");
    $assert(($offers[1]['link_type'] ?? '') === 'tour', "Expected regional browse fallback for {$beachSlug}");

    $meta = toursCuratedOfferMeta($campaignSlug, 'en');
    $imageUrl = (string) ($meta['image_url'] ?? '');
    $imageHost = strtolower((string) parse_url($imageUrl, PHP_URL_HOST));
    $assert(
        str_starts_with($imageUrl, 'https://')
            && ($imageHost === 'media.tacdn.com' || str_ends_with($imageHost, '.tripadvisor.com')),
        "Expected official Viator/TripAdvisor CDN image for {$campaignSlug}"
    );
    $assert((string) ($meta['product_code'] ?? '') !== '', "Expected product code for {$campaignSlug}");
}

$genericBeach = queryOne('SELECT * FROM beaches WHERE slug = "seven-seas-beach"');
$genericOffers = $genericBeach ? toursCampaignsForBeach($genericBeach, 2) : [];
$assert(count($genericOffers) === 1, 'Generic regional beach should show one browse offer');
$assert(($genericOffers[0]['link_type'] ?? '') === 'tour', 'Generic regional beach should not fabricate a product match');

$icacos = queryOne('SELECT * FROM beaches WHERE slug IN ("icacos-beach", "cayo-icacos-la-cordillera") ORDER BY CASE slug WHEN "icacos-beach" THEN 0 ELSE 1 END LIMIT 1');
$icacosOffers = $icacos ? toursCampaignsForBeach($icacos, 2) : [];
$featured = $icacosOffers[0] ?? [];
$params = referralProviderTrackingParams($featured);
$target = referralBuildTargetUrl($featured, $params);
parse_str((string) parse_url($target, PHP_URL_QUERY), $query);
$configuredPid = trim((string) env('VIATOR_PID', ''));

$assert($configuredPid !== '', 'VIATOR_PID must be configured');
$assert(($query['pid'] ?? '') === $configuredPid, 'Outbound product link must use the configured Viator PID');
$assert(($query['mcid'] ?? '') === '42383', 'Outbound product link must use Viator text-link mcid');
$assert(($query['medium'] ?? '') === 'link', 'Outbound product link must identify link medium');
$assert(($query['campaign'] ?? '') === 'viator-product-icacos-catamaran', 'Outbound product link must preserve product campaign attribution');

$en = $icacos ? renderToursSection($icacos, 'en', 'redesign') : '';
$es = $icacos ? renderToursSection($icacos, 'es', 'redesign') : '';
$assert(str_contains($en, 'Curated for this beach'), 'English curated treatment should render');
$assert(str_contains($en, 'product_code=14939P2'), 'Product code should be included in click context');
$assert(str_contains($en, 'match_type=curated_beach'), 'Match type should be included in click context');
$assert(
    str_contains($en, 'media.tacdn.com/') || str_contains($en, '.tripadvisor.com/'),
    'Official Viator/TripAdvisor CDN image should render for the curated product'
);
$assert(str_contains($en, 'Viator photo'), 'Official image source should be identified in English');
$assert(str_contains($en, 'loading="lazy"'), 'Official product image should lazy load');
$assert(str_contains($es, 'Elegido para esta playa'), 'Spanish curated treatment should render');
$assert(str_contains($es, 'Foto de Viator'), 'Official image source should be identified in Spanish');
$assert(!str_contains($en, 'free cancellation on most tours'), 'Section should not make a blanket cancellation claim');

if ($failures === []) {
    echo "Viator curation tests passed ({$checks} checks).\n";
    exit(0);
}

echo 'Viator curation tests failed (' . count($failures) . " / {$checks} failed):\n";
foreach ($failures as $index => $failure) {
    echo '  ' . ($index + 1) . '. ' . $failure . "\n";
}
exit(1);
