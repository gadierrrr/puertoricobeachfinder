<?php
/**
 * API: Collection explorer data for list/cards/grid views.
 *
 * GET /api/collection-beaches.php
 * Params:
 * - collection (required)
 * - q
 * - tags[]
 * - municipality
 * - sort
 * - view
 * - page
 * - limit
 * - include_all
 * - format=html|json
 */

require_once __DIR__ . '/../inc/session.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/collection_query.php';

$isHtmxRequest = isset($_SERVER['HTTP_HX_REQUEST']);
$format = isset($_GET['format']) ? (string)$_GET['format'] : ($isHtmxRequest ? 'html' : 'json');

$collectionKey = isset($_GET['collection']) ? (string)$_GET['collection'] : '';
if (!isValidCollectionKey($collectionKey)) {
    jsonResponse([
        'success' => false,
        'error' => 'Invalid collection key.',
        'allowed' => getCollectionKeys(),
    ], 400);
}

$filters = collectionFiltersFromRequest($collectionKey, $_GET);
$collectionData = fetchCollectionBeaches($collectionKey, $filters);
$collectionContext = $collectionData['collection'];
$collectionState = $collectionData['effective_filters'];

$userFavorites = [];
if (isAuthenticated()) {
    $favorites = query(
        'SELECT beach_id FROM user_favorites WHERE user_id = :user_id',
        [':user_id' => $_SESSION['user_id']]
    ) ?: [];
    $userFavorites = array_column($favorites, 'beach_id');
}

if ($format === 'html') {
    header('Content-Type: text/html; charset=utf-8');
    include __DIR__ . '/../components/collection/results.php';
    exit;
}

jsonResponse([
    'success' => true,
    'collection' => $collectionContext,
    'data' => $collectionData['beaches'] ?? [],
    'meta' => [
        'total' => intval($collectionData['total'] ?? 0),
        'page' => intval($collectionData['page'] ?? 1),
        'limit' => intval($collectionData['limit'] ?? 0),
        'pages' => intval($collectionData['pages'] ?? 1),
        'context_fallback' => !empty($collectionData['context_fallback']),
        'filters' => $collectionState,
    ],
]);
