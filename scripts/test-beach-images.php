#!/usr/bin/env php
<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/bootstrap.php';
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';

$beaches = query(
    "SELECT id, slug, name, cover_image FROM beaches WHERE publish_status = 'published' ORDER BY name"
);
if (!is_array($beaches)) {
    fwrite(STDERR, "Could not read published beaches.\n");
    exit(1);
}

$failures = [];
$placeholderCount = 0;
$recoveredCount = 0;

foreach ($beaches as $beach) {
    $raw = (string) ($beach['cover_image'] ?? '');
    $resolved = (string) getBeachImageUrl($beach, 'medium');
    if ($resolved === '/images/beaches/placeholder-beach.webp') {
        $placeholderCount++;
    } elseif ($raw !== '' && $resolved !== $raw) {
        $recoveredCount++;
    }

    foreach (array_merge([$resolved], srcsetUrls(getBeachImageSrcset($beach))) as $url) {
        if (!isLocalAssetUrl($url)) {
            continue;
        }
        $path = localAssetPath($url);
        if ($path === null || !is_file($path)) {
            $failures[] = sprintf('%s (%s): missing %s', $beach['name'], $beach['slug'], $url);
        }
    }
}

echo sprintf(
    "Beach image audit: %d published, %d recovered, %d placeholder fallback(s), %d failure(s).\n",
    count($beaches),
    $recoveredCount,
    $placeholderCount,
    count($failures)
);
foreach ($failures as $failure) {
    echo " - {$failure}\n";
}

exit($failures === [] ? 0 : 1);

function srcsetUrls(string $srcset): array
{
    if ($srcset === '') {
        return [];
    }
    $urls = [];
    foreach (explode(',', $srcset) as $candidate) {
        $parts = preg_split('/\s+/', trim($candidate));
        if (!empty($parts[0])) {
            $urls[] = $parts[0];
        }
    }
    return $urls;
}

function isLocalAssetUrl(string $url): bool
{
    return str_starts_with($url, '/uploads/')
        || str_starts_with($url, '/images/')
        || str_starts_with($url, '/assets/');
}

function localAssetPath(string $url): ?string
{
    $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
    if (str_starts_with($path, '/uploads/')) {
        return APP_ROOT . $path;
    }
    if (str_starts_with($path, '/images/') || str_starts_with($path, '/assets/')) {
        return PUBLIC_ROOT . $path;
    }
    return null;
}
