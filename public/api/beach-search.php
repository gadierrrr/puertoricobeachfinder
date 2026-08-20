<?php
/**
 * API: lightweight beach-name typeahead for the site nav.
 *
 * GET /api/beach-search.php?q=<term>&lang=en|es
 * Returns {results: [{n, m, u}]} — name, municipality, localized URL.
 * Public data, tiny LIKE query, capped at 8 rows.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/locale_routes.php';

header('Content-Type: application/json');
header('Cache-Control: public, max-age=300');

$q = trim((string) ($_GET['q'] ?? ''));
$lang = ($_GET['lang'] ?? 'en') === 'es' ? 'es' : 'en';
if (mb_strlen($q) < 2 || mb_strlen($q) > 60) {
    echo json_encode(['results' => []]);
    exit;
}

$like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
$rows = query(
    "SELECT name, municipality, slug FROM beaches
     WHERE publish_status = 'published' AND (name LIKE :q ESCAPE '\\' OR municipality LIKE :q ESCAPE '\\')
     ORDER BY (name LIKE :qstart ESCAPE '\\') DESC, google_review_count DESC
     LIMIT 8",
    [':q' => $like, ':qstart' => str_replace('%', '', $like) . '%']
) ?: [];

echo json_encode(['results' => array_map(fn($r) => [
    'n' => $r['name'],
    'm' => $r['municipality'],
    'u' => routeUrl('beach_detail', $lang, ['slug' => $r['slug']]),
], $rows)]);
