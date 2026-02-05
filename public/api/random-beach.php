<?php
/**
 * Random Beach API
 * Returns a random beach or redirects to it
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';

// Get a random published beach
$beach = queryOne('
    SELECT slug, name
    FROM beaches
    WHERE publish_status = "published"
    ORDER BY RANDOM()
    LIMIT 1
');

if (!$beach) {
    if (isHtmx()) {
        http_response_code(404);
        echo '<p>No beaches found</p>';
        exit;
    }
    jsonResponse(['error' => 'No beaches found'], 404);
}

// Check if this is an AJAX request or redirect request
$format = $_GET['format'] ?? 'redirect';

if ($format === 'json') {
    jsonResponse([
        'slug' => $beach['slug'],
        'name' => $beach['name'],
        'url' => '/beach/' . $beach['slug']
    ]);
} else {
    // Redirect to the beach page
    header('Location: /beach/' . $beach['slug']);
    exit;
}
