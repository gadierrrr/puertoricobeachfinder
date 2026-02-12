<?php
/**
 * API: Get Beach Data for Map View.
 *
 * Supports both generic filters and collection context handoff from
 * collection explorer pages.
 *
 * GET /api/beaches-map.php
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/collection_query.php';

header('Content-Type: application/json');
header('Cache-Control: public, max-age=86400, s-maxage=86400');

$collectionKey = isset($_GET['collection']) ? (string)$_GET['collection'] : '';
if ($collectionKey === '' && (($_GET['near'] ?? '') === 'san-juan')) {
    $collectionKey = 'beaches-near-san-juan';
}
$beaches = [];
$meta = [
    'collection' => null,
    'context_fallback' => false,
];

if ($collectionKey !== '' && isValidCollectionKey($collectionKey)) {
    $mapLimit = min(500, max(1, intval($_GET['limit'] ?? 500)));
    $filtersInput = $_GET;
    $filtersInput['page'] = 1;
    $filtersInput['limit'] = $mapLimit;
    $filters = collectionFiltersFromRequest($collectionKey, $filtersInput, 500);

    $collectionData = fetchCollectionBeaches($collectionKey, $filters, 500);
    $beaches = $collectionData['beaches'] ?? [];
    $meta['collection'] = $collectionKey;
    $meta['context_fallback'] = !empty($collectionData['context_fallback']);
    $meta['filters'] = $collectionData['effective_filters'] ?? [];
} else {
    $tags = isset($_GET['tags']) ? (array)$_GET['tags'] : [];
    if (isset($_GET['tags[]'])) {
        $tags = array_merge($tags, (array)$_GET['tags[]']);
    }
    $tags = array_values(array_filter($tags, 'isValidTag'));
    $hasLifeguard = isset($_GET['has_lifeguard']) && in_array((string)$_GET['has_lifeguard'], ['1', 'true'], true);
    $amenities = [];
    if (isset($_GET['amenities'])) {
        $amenities = array_merge($amenities, (array)$_GET['amenities']);
    }
    if (isset($_GET['amenities[]'])) {
        $amenities = array_merge($amenities, (array)$_GET['amenities[]']);
    }
    if (in_array('lifeguards', $amenities, true) || in_array('lifeguard', $amenities, true)) {
        $hasLifeguard = true;
    }

    $municipality = '';
    if (isset($_GET['municipality']) && is_string($_GET['municipality']) && isValidMunicipality($_GET['municipality'])) {
        $municipality = $_GET['municipality'];
    }

    $searchQuery = trim((string)($_GET['q'] ?? ''));
    $sort = isset($_GET['sort']) ? (string)$_GET['sort'] : 'name';
    if (!in_array($sort, ['name', 'rating', 'reviews'], true)) {
        $sort = 'name';
    }

    $params = [];
    $where = ['b.publish_status = "published"'];

    if (!empty($tags)) {
        $tagPlaceholders = [];
        foreach ($tags as $idx => $tag) {
            $placeholder = ':tag_' . $idx;
            $tagPlaceholders[] = $placeholder;
            $params[$placeholder] = $tag;
        }
        $where[] = 'EXISTS (
            SELECT 1 FROM beach_tags bt
            WHERE bt.beach_id = b.id
            AND bt.tag IN (' . implode(', ', $tagPlaceholders) . ')
        )';
    }

    if ($municipality !== '') {
        $params[':municipality'] = $municipality;
        $where[] = 'b.municipality = :municipality';
    }

    if ($hasLifeguard) {
        $where[] = 'b.has_lifeguard = 1';
    }

    if ($searchQuery !== '') {
        $search = '%' . $searchQuery . '%';
        $params[':search_name'] = $search;
        $params[':search_municipality'] = $search;
        $params[':search_description'] = $search;
        $where[] = '(b.name LIKE :search_name
            OR b.municipality LIKE :search_municipality
            OR b.description LIKE :search_description)';
    }

    $whereClause = ' WHERE ' . implode(' AND ', $where);
    $orderBy = 'b.name ASC';
    if ($sort === 'rating') {
        $orderBy = 'COALESCE(b.google_rating, 0) DESC, COALESCE(b.google_review_count, 0) DESC, b.name ASC';
    } elseif ($sort === 'reviews') {
        $orderBy = 'COALESCE(b.google_review_count, 0) DESC, COALESCE(b.google_rating, 0) DESC, b.name ASC';
    }

    $sql = 'SELECT b.id, b.slug, b.name, b.municipality, b.lat, b.lng,
                   b.cover_image, b.google_rating
            FROM beaches b' . $whereClause . '
            ORDER BY ' . $orderBy . '
            LIMIT 500';
    $beaches = query($sql, $params) ?: [];
    if (!empty($beaches)) {
        attachBeachMetadata($beaches);
    }

    $meta['filters'] = [
        'tags' => $tags,
        'municipality' => $municipality,
        'has_lifeguard' => $hasLifeguard,
        'q' => $searchQuery,
        'sort' => $sort,
    ];
}

echo json_encode([
    'beaches' => $beaches,
    'total' => count($beaches),
    'meta' => $meta,
]);
