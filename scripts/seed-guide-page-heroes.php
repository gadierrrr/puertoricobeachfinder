#!/usr/bin/env php
<?php
/**
 * Seed managed hero photos for guide/collection pages (page scope, EN + ES).
 *
 * Copies rights-documented gallery photos (already optimized 1200px WebP,
 * credits from beach_gallery) into /uploads/admin/page-heroes/ and publishes
 * page-scope entries via pageHeroSetEntry(). Idempotent: re-running refreshes
 * the same entries. Photos missing on this machine are skipped with a note
 * (the local checkout doesn't sync uploads/), so run on prod for real effect.
 *
 * Usage: php scripts/seed-guide-page-heroes.php [--dry-run]
 */

require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/page_heroes.php';

$dryRun = in_array('--dry-run', $argv, true);

$sourceDir = APP_ROOT . '/uploads/admin/beaches';
$targetDir = APP_ROOT . '/uploads/admin/page-heroes';

$heroes = [
    [
        'file' => 'balneario-la-monserrate-luquillo_f4ceb66f_1787248460_1200.webp',
        'credit' => 'Ricardo Manual · CC BY 2.0',
        'credit_url' => 'https://commons.wikimedia.org/wiki/File:Luquillo_Beach,_Luquillo,_Puerto_Rico.jpg',
        'overlay' => 42,
        'pages' => ['/best-family-beaches', '/es/mejores-playas-familiares'],
    ],
    [
        'file' => 'flamenco-beach_f3867b1f_1787248440_1200.webp',
        'credit' => 'Breezy Baldwin · CC BY 2.0',
        'credit_url' => 'https://commons.wikimedia.org/wiki/File:Flamenco_Beach,_Culebra_Island,_Puerto_Rico.jpg',
        'overlay' => 30,
        'pages' => ['/best-beaches', '/es/mejores-playas'],
    ],
    [
        'file' => 'cayo-icacos-la-cordillera_f3a8a09d_1787248442_1200.webp',
        'credit' => 'jeffgunn · CC BY 2.0',
        'credit_url' => 'https://commons.wikimedia.org/wiki/File:Isle_of_Icacos_II.jpg',
        'overlay' => 42,
        'pages' => [
            '/best-snorkeling-beaches', '/es/mejores-playas-para-snorkel',
            '/best-beaches-fajardo', '/es/mejores-playas-fajardo',
            '/guides/snorkeling-guide', '/es/guias/guia-snorkel',
        ],
    ],
    [
        'file' => 'jobos-beach_f4ade13f_1787248458_1200.webp',
        'credit' => 'Trish Hartmann · CC BY 2.0',
        'credit_url' => 'https://commons.wikimedia.org/wiki/File:Jobos_Beach_in_Isabela,_Puerto_Rico.jpg',
        'overlay' => 38,
        'pages' => [
            '/best-surfing-beaches', '/es/mejores-playas-para-surf',
            '/best-beaches-isabela', '/es/mejores-playas-isabela',
            '/guides/surfing-guide', '/es/guias/guia-surf',
        ],
    ],
    [
        'file' => 'crash-boat-beach_f3f1618a_1787248447_1200.webp',
        'credit' => 'Tom Vazquez · Public domain',
        'credit_url' => 'https://commons.wikimedia.org/wiki/File:Crash_Boat_01.jpg',
        'overlay' => 42,
        'pages' => ['/best-swimming-beaches', '/es/mejores-playas-para-nadar'],
    ],
    [
        'file' => 'balneario-de-boqueron_f4614505_1787248454_1200.webp',
        'credit' => 'Ligocsicnarf89 · CC BY 4.0',
        'credit_url' => 'https://commons.wikimedia.org/wiki/File:Boquer%C3%B3n_Beach_(2026)-2.jpg',
        'overlay' => 40,
        'pages' => ['/best-calm-water-beaches', '/es/mejores-playas-aguas-tranquilas'],
    ],
    [
        'file' => 'la-playuela-playa-sucia_f41cffbe_1787248449_1200.webp',
        'credit' => 'Edgar Torres · CC BY 3.0',
        'credit_url' => 'https://commons.wikimedia.org/wiki/File:Playa_Sucia_and_lighthouse,_Cabo_Rojo,_PR_-_panoramio.jpg',
        'overlay' => 40,
        'pages' => ['/best-scenic-beaches', '/es/mejores-playas-escenicas'],
    ],
    [
        'file' => 'la-playuela-playa-sucia_f40b8c2e_1787248448_1200.webp',
        'credit' => 'LeanneMarie1215 · CC BY 2.0',
        'credit_url' => 'https://commons.wikimedia.org/wiki/File:Playa_Sucia-_Cabo_Rojo,_Puerto_Rico.jpg',
        'overlay' => 40,
        'pages' => ['/best-beaches-cabo-rojo', '/es/mejores-playas-cabo-rojo'],
    ],
    [
        'file' => 'domes-beach_f4a2378d_1787248458_1200.webp',
        'credit' => 'Gordon Tarpley · CC BY 2.0',
        'credit_url' => 'https://commons.wikimedia.org/wiki/File:View_of_dome_on_Domes_Beach,_Rinc%C3%B3n,_Puerto_Rico.jpg',
        'overlay' => 42,
        'pages' => ['/best-beaches-rincon', '/es/mejores-playas-rincon'],
    ],
    [
        'file' => 'flamenco-beach_f3785440_1787248439_1200.webp',
        'credit' => 'Carolyn Sugg · CC BY-SA 2.0',
        'credit_url' => 'https://commons.wikimedia.org/wiki/File:US_military_tank_on_Flamenco_Beach,_Culebra,_Puerto_Rico.jpg',
        'overlay' => 36,
        'pages' => [
            '/best-beaches-culebra', '/es/mejores-playas-culebra',
            '/guides/culebra-vs-vieques', '/es/guias/culebra-vs-vieques',
        ],
    ],
    [
        'file' => 'sun-bay_f4d81114_1787248461_1200.webp',
        'credit' => 'Steven Isaacson · CC BY-SA 2.0',
        'credit_url' => 'https://commons.wikimedia.org/wiki/File:Sunset_at_Sun_Bay_Beach,_Vieques,_Puerto_Rico.jpg',
        'overlay' => 25,
        'pages' => ['/best-beaches-vieques', '/es/mejores-playas-vieques'],
    ],
    [
        'file' => 'balneario-la-monserrate-luquillo_f4c1b82a_1787248460_1200.webp',
        'credit' => 'Güldem Üstün · CC BY 2.0',
        'credit_url' => 'https://commons.wikimedia.org/wiki/File:Luquillo_Beach_in_Puerto_Rico.jpg',
        'overlay' => 44,
        'pages' => [
            '/best-beaches-luquillo', '/es/mejores-playas-luquillo',
            '/guides/kid-friendly-beaches',
        ],
    ],
];

if (!$dryRun && !is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
    fwrite(STDERR, "Cannot create $targetDir\n");
    exit(1);
}

$published = 0;
$skipped = 0;
foreach ($heroes as $hero) {
    $source = $sourceDir . '/' . $hero['file'];
    $target = $targetDir . '/' . $hero['file'];
    if (!is_file($source) && !is_file($target)) {
        echo "SKIP (no source file): {$hero['file']}\n";
        $skipped += count($hero['pages']);
        continue;
    }
    if ($dryRun) {
        echo "DRY: {$hero['file']} -> " . implode(', ', $hero['pages']) . "\n";
        continue;
    }
    if (!is_file($target) && !copy($source, $target)) {
        fwrite(STDERR, "Failed to copy {$hero['file']}\n");
        continue;
    }
    foreach ($hero['pages'] as $path) {
        pageHeroSetEntry('page', $path, [
            'image' => '/uploads/admin/page-heroes/' . $hero['file'],
            'position' => 'center center',
            'overlay' => $hero['overlay'],
            'credit' => $hero['credit'],
            'credit_url' => $hero['credit_url'],
        ]);
        $published++;
        echo "SET $path <- {$hero['file']} (overlay {$hero['overlay']})\n";
    }
}
echo "Published $published page-hero entries" . ($skipped ? ", skipped $skipped (missing files)" : '') . ".\n";
