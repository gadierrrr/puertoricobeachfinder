#!/usr/bin/env php
<?php
/** Offline parser and attribution tests for the Viator API integration. */

require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/viator.php';

$checks = 0;
$failures = [];
$assert = static function (bool $condition, string $message) use (&$checks, &$failures): void {
    $checks++;
    if (!$condition) {
        $failures[] = $message;
    }
};

$product = [
    'status' => 'ACTIVE',
    'productCode' => '14939P2',
    'title' => 'Icacos Catamaran',
    'description' => 'A day on the water.',
    'lastUpdatedAt' => '2026-07-01T12:00:00Z',
    'duration' => ['fixedDurationInMinutes' => 330],
    'images' => [[
        'isCover' => true,
        'variants' => [
            ['url' => 'https://media.tacdn.com/small.jpg', 'width' => 240, 'height' => 160],
            ['url' => 'https://media.tacdn.com/large.jpg', 'width' => 1080, 'height' => 720],
        ],
    ]],
    'reviews' => ['combinedAverageRating' => 4.8, 'totalReviews' => 321],
    'flags' => ['FREE_CANCELLATION'],
    'logistics' => ['start' => [['location' => ['name' => 'Fajardo Marina']]]],
    'productUrl' => 'https://www.viator.com/tours/x?mcid=42383&pid=P00000000&medium=api&campaign=viator-product-icacos-catamaran',
];
$schedule = [
    'currency' => 'USD',
    'bookableItems' => [[
        'seasons' => [[
            'pricingRecords' => [['recommendedRetailPrice' => 149.50]],
        ]],
    ]],
];

$assert(viatorExtractImageUrl($product) === 'https://media.tacdn.com/large.jpg', 'Largest cover image should win');
[$min, $max] = viatorExtractDuration($product);
$assert($min === 330 && $max === 330, 'Fixed duration should parse');
$price = viatorExtractPrice($schedule);
$assert(abs((float) $price['price'] - 149.50) < 0.001, 'Retail schedule price should parse');
$assert($price['currency'] === 'USD', 'Schedule currency should parse');
$assert(viatorExtractFreeCancellation($product) === 1, 'Explicit free-cancellation flag should parse');
$assert(viatorExtractDepartureSummary($product) === 'Fajardo Marina', 'Departure location should parse');
$assert(viatorProductUrlIsValid($product['productUrl']), 'Official HTTPS product URL should validate');
$assert(!viatorProductUrlIsValid('https://example.com/tour'), 'Non-Viator product URL should fail');
$assert(viatorAcceptLanguage('es') === 'es', 'Spanish locale header should parse');
$assert(viatorAcceptLanguage('en') === 'en-US', 'English locale header should parse');

if ($failures === []) {
    echo "Viator API tests passed ({$checks} checks).\n";
    exit(0);
}

echo 'Viator API tests failed (' . count($failures) . " / {$checks}):\n";
foreach ($failures as $failure) {
    echo '  - ' . $failure . "\n";
}
exit(1);
