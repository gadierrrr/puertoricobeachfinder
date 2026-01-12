<?php
/**
 * API: Toggle Beach Favorite
 *
 * POST /api/toggle-favorite.php
 * Body: beach_id, csrf_token
 * Returns updated favorite button HTML (for HTMX)
 */

require_once __DIR__ . '/../inc/session.php';
session_start();
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

// Require authentication
if (!isAuthenticated()) {
    http_response_code(401);
    echo '<button class="favorite-btn" onclick="alert(\'Please sign in to save favorites\')">🤍</button>';
    exit;
}

// Validate CSRF token
$csrfToken = $_POST['csrf_token'] ?? '';
if (!validateCsrf($csrfToken)) {
    http_response_code(403);
    echo '<button class="favorite-btn">⚠️</button>';
    exit;
}

// Get beach ID
$beachId = $_POST['beach_id'] ?? '';
if (!$beachId) {
    http_response_code(400);
    echo '<button class="favorite-btn">⚠️</button>';
    exit;
}

// Verify beach exists
$beach = queryOne('SELECT id FROM beaches WHERE id = :id', [':id' => $beachId]);
if (!$beach) {
    http_response_code(404);
    echo '<button class="favorite-btn">⚠️</button>';
    exit;
}

$userId = $_SESSION['user_id'];

// Check if already favorited
$existing = queryOne(
    'SELECT id FROM user_favorites WHERE user_id = :user_id AND beach_id = :beach_id',
    [':user_id' => $userId, ':beach_id' => $beachId]
);

if ($existing) {
    // Remove from favorites
    execute('DELETE FROM user_favorites WHERE id = :id', [':id' => $existing['id']]);
    $isFavorite = false;
} else {
    // Add to favorites
    $favoriteId = uuid();
    execute(
        'INSERT INTO user_favorites (id, user_id, beach_id, created_at) VALUES (:id, :user_id, :beach_id, datetime("now"))',
        [':id' => $favoriteId, ':user_id' => $userId, ':beach_id' => $beachId]
    );
    $isFavorite = true;
}

// Return updated button
?>
<button class="favorite-btn absolute top-3 left-3 w-8 h-8 flex items-center justify-center rounded-full bg-white/90 shadow hover:bg-white transition-colors"
        hx-post="/api/toggle-favorite.php"
        hx-target="this"
        hx-swap="outerHTML"
        hx-vals='{"beach_id": "<?= h($beachId) ?>", "csrf_token": "<?= h(csrfToken()) ?>"}'
        title="<?= $isFavorite ? 'Remove from favorites' : 'Add to favorites' ?>">
    <span class="text-lg <?= $isFavorite ? 'text-red-500' : 'text-gray-400' ?>">
        <?= $isFavorite ? '❤️' : '🤍' ?>
    </span>
</button>
