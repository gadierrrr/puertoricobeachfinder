<?php
/** Regression checks for the admin-managed page header image system. */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once APP_ROOT . '/inc/page_heroes.php';

$failures = [];
$expect = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$families = [
    '/' => 'home',
    '/es' => 'home',
    '/best-beaches' => 'listings',
    '/es/playas-cerca-de-ponce' => 'listings',
    '/guides/snorkeling-guide' => 'guides',
    '/es/guias/snorkeling-guide' => 'guides',
    '/quiz' => 'quiz',
    '/es/quiz-playa' => 'quiz',
    '/quiz-results' => 'quiz-results',
    '/es/resultados-quiz-playa' => 'quiz-results',
    '/compare' => 'compare',
    '/profile' => 'account',
    '/privacy' => 'legal',
    '/advertise' => 'general',
];
foreach ($families as $path => $expected) {
    $expect(pageHeroFamilyForPath($path) === $expected, "$path should resolve to $expected");
}

$expect(pageHeroFamilyForPath('/beach/flamenco-beach') === null, 'English beach profiles must be excluded');
$expect(pageHeroFamilyForPath('/es/playa/flamenco-beach') === null, 'Spanish beach profiles must be excluded');

$clean = sanitizePageHeroSettings([
    'families' => [
        'guides' => ['image' => '/uploads/admin/page-heroes/guide.webp', 'position' => 'center top', 'overlay' => 120],
        'unknown' => ['image' => '/uploads/admin/page-heroes/nope.webp'],
    ],
    'pages' => [
        '/guides/snorkeling-guide/' => ['image' => '/uploads/admin/page-heroes/snorkel.webp', 'position' => 'bogus', 'overlay' => -4],
        '/beach/flamenco-beach' => ['image' => '/uploads/admin/page-heroes/beach.webp'],
        '/bad?query=1' => ['image' => '/uploads/admin/page-heroes/bad.webp'],
    ],
]);
$expect(($clean['families']['guides']['overlay'] ?? null) === 80, 'Overlay should clamp to 80');
$expect(!isset($clean['families']['unknown']), 'Unknown families should be discarded');
$expect(($clean['pages']['/guides/snorkeling-guide']['position'] ?? null) === 'center center', 'Invalid focal points should reset');
$expect(($clean['pages']['/guides/snorkeling-guide']['overlay'] ?? null) === 0, 'Overlay should clamp to zero');
$expect(!isset($clean['pages']['/beach/flamenco-beach']), 'Beach profile page overrides should be discarded');

$managedRenderers = [
    'templates/redesign/home.php',
    'templates/redesign/listing.php',
    'templates/redesign/quiz-results.php',
    'public/quiz.php',
    'public/compare.php',
    'public/guides/index.php',
    'components/hero-guide.php',
    'components/collection/hero.php',
];
foreach ($managedRenderers as $relative) {
    $contents = file_get_contents(APP_ROOT . '/' . $relative);
    $expect(is_string($contents) && str_contains($contents, 'pageHero'), "$relative should consume managed hero settings");
}

$beachTemplate = file_get_contents(APP_ROOT . '/templates/redesign/beach.php');
$expect(is_string($beachTemplate) && !str_contains($beachTemplate, 'pageHeroAttributes'), 'Beach profile template must remain independent');

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, "FAIL: $failure\n");
    }
    exit(1);
}

echo 'Page hero regression checks passed (' . (count($families) + 13) . " assertions).\n";

