<?php
/** Public, secret-free Viator integration health summary. */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/viator.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$enabled = envBool('VIATOR_API_ENABLED', false);
$configured = trim((string) env('VIATOR_API_KEY', '')) !== '';
$ttlHours = max(1, (int) env('VIATOR_SYNC_TTL_HOURS', '24'));
$products = ['products' => 0, 'active_locales' => 0, 'last_fetched_at' => null];
$latest = null;

if (viatorTableExists('viator_products')) {
    $products = queryOne(
        'SELECT COUNT(DISTINCT product_code) AS products,
                SUM(CASE WHEN status = "ACTIVE" THEN 1 ELSE 0 END) AS active_locales,
                MAX(fetched_at) AS last_fetched_at
         FROM viator_products'
    ) ?: $products;
}
if (viatorTableExists('viator_sync_runs')) {
    $latest = queryOne(
        'SELECT status, environment, products_attempted, products_updated, errors_count, started_at, finished_at
         FROM viator_sync_runs ORDER BY started_at DESC LIMIT 1'
    );
}

$catalog = [
    'destinations' => 0,
    'tags' => 0,
    'search_products' => 0,
    'matched_beaches' => 0,
    'beach_product_matches' => 0,
    'guide_placements' => 0,
    'last_catalog_run' => null,
];
if (viatorTableExists('viator_destinations')) {
    $catalog['destinations'] = (int) (queryOne('SELECT COUNT(*) AS n FROM viator_destinations')['n'] ?? 0);
}
if (viatorTableExists('viator_tags')) {
    $catalog['tags'] = (int) (queryOne('SELECT COUNT(*) AS n FROM viator_tags')['n'] ?? 0);
}
if (viatorTableExists('viator_products')) {
    $catalog['search_products'] = (int) (queryOne(
        'SELECT COUNT(DISTINCT product_code) AS n FROM viator_products WHERE source = "catalog_search" AND status = "ACTIVE"'
    )['n'] ?? 0);
}
if (viatorTableExists('viator_beach_products')) {
    $matchStats = queryOne(
        'SELECT COUNT(DISTINCT beach_id) AS beaches, COUNT(*) AS matches
         FROM viator_beach_products WHERE status = "active"'
    );
    $catalog['matched_beaches'] = (int) ($matchStats['beaches'] ?? 0);
    $catalog['beach_product_matches'] = (int) ($matchStats['matches'] ?? 0);
}
if (viatorTableExists('guide_tour_placements')) {
    $catalog['guide_placements'] = (int) (queryOne(
        'SELECT COUNT(*) AS n FROM guide_tour_placements WHERE enabled = 1'
    )['n'] ?? 0);
}
if (viatorTableExists('viator_sync_runs')) {
    $catalog['last_catalog_run'] = queryOne(
        'SELECT status, started_at, finished_at, summary_json
         FROM viator_sync_runs
         WHERE summary_json LIKE \'%"kind":"catalog"%\'
         ORDER BY started_at DESC LIMIT 1'
    );
}

$lastFetch = trim((string) ($products['last_fetched_at'] ?? ''));
$fresh = false;
if ($lastFetch !== '') {
    try {
        $age = time() - (new DateTimeImmutable($lastFetch, new DateTimeZone('UTC')))->getTimestamp();
        $fresh = $age <= $ttlHours * 3600;
    } catch (Throwable $e) {
        $fresh = false;
    }
}

$healthy = !$enabled || (
    $configured
    && (int) ($products['products'] ?? 0) >= 8
    && $fresh
    && in_array((string) ($latest['status'] ?? ''), ['completed', 'completed_with_errors'], true)
);

http_response_code($healthy ? 200 : 503);
echo json_encode([
    'ok' => $healthy,
    'enabled' => $enabled,
    'configured' => $configured,
    'cache' => [
        'products' => (int) ($products['products'] ?? 0),
        'active_locales' => (int) ($products['active_locales'] ?? 0),
        'last_fetched_at' => $lastFetch !== '' ? $lastFetch : null,
        'fresh' => $fresh,
        'ttl_hours' => $ttlHours,
    ],
    'latest_sync' => $latest,
    'catalog' => $catalog,
], JSON_UNESCAPED_SLASHES);
