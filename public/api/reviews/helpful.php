<?php
/**
 * API: Mark Review as Helpful
 * POST /api/reviews/helpful.php
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
session_start();

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/rate_limiter.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

if (!isAuthenticated()) {
    jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$reviewId = trim((string)($input['review_id'] ?? ''));
$csrfToken = (string)($input['csrf_token'] ?? '');

if (!validateCsrf($csrfToken)) {
    jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
}

if ($reviewId === '') {
    jsonResponse(['success' => false, 'error' => 'review_id is required'], 400);
}

$userId = $_SESSION['user_id'];
$db = getDb();

$review = queryOne(
    'SELECT id, user_id, helpful_count FROM beach_reviews WHERE id = :id',
    [':id' => $reviewId]
);

if (!$review) {
    jsonResponse(['success' => false, 'error' => 'Review not found'], 404);
}

if (($review['user_id'] ?? '') === $userId) {
    jsonResponse(['success' => false, 'error' => 'You cannot vote on your own review'], 400);
}

$existingVote = queryOne(
    'SELECT id FROM review_helpful_votes WHERE review_id = :review_id AND user_id = :user_id',
    [':review_id' => $reviewId, ':user_id' => $userId]
);

if ($existingVote) {
    $helpfulCount = (int)($review['helpful_count'] ?? 0);
    if (isHtmx()) {
        renderHelpfulButton($reviewId, $helpfulCount, true);
        return;
    }

    jsonResponse([
        'success' => true,
        'helpful_count' => $helpfulCount,
        'voted' => true,
    ]);
}

$rateLimiter = new RateLimiter($db);
$limit = $rateLimiter->check($userId, 'review_helpful_vote', 30, 10);
if (!$limit['allowed']) {
    jsonResponse(['success' => false, 'error' => 'Too many votes. Please try again later.'], 429);
}

$voteId = uuid();
$voteStmt = $db->prepare(
    'INSERT INTO review_helpful_votes (id, review_id, user_id, created_at)
     VALUES (:id, :review_id, :user_id, datetime("now"))'
);
$voteStmt->bindValue(':id', $voteId, SQLITE3_TEXT);
$voteStmt->bindValue(':review_id', $reviewId, SQLITE3_TEXT);
$voteStmt->bindValue(':user_id', $userId, SQLITE3_TEXT);

if (!$voteStmt->execute()) {
    jsonResponse(['success' => false, 'error' => 'Failed to record vote'], 500);
}

$updateStmt = $db->prepare('UPDATE beach_reviews SET helpful_count = helpful_count + 1 WHERE id = :id');
$updateStmt->bindValue(':id', $reviewId, SQLITE3_TEXT);
if (!$updateStmt->execute()) {
    jsonResponse(['success' => false, 'error' => 'Failed to update review'], 500);
}

$updatedReview = queryOne(
    'SELECT helpful_count FROM beach_reviews WHERE id = :id',
    [':id' => $reviewId]
);
$helpfulCount = (int)($updatedReview['helpful_count'] ?? 0);

if (isHtmx()) {
    renderHelpfulButton($reviewId, $helpfulCount, true);
    return;
}

jsonResponse([
    'success' => true,
    'helpful_count' => $helpfulCount,
    'voted' => true,
]);

function renderHelpfulButton(string $reviewId, int $helpfulCount, bool $voted): void {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <button class="helpful-btn flex items-center gap-1.5 <?= $voted ? 'text-blue-600' : 'text-gray-500 hover:text-blue-600' ?> text-sm transition-colors"
            <?php if (!$voted): ?>
            hx-post="/api/reviews/helpful.php"
            hx-vals='{"review_id": "<?= h($reviewId) ?>", "csrf_token": "<?= h(csrfToken()) ?>"}'
            hx-target="this"
            hx-swap="outerHTML"
            <?php else: ?>
            disabled
            <?php endif; ?>>
        <span>👍</span>
        <span><?= $voted ? 'Thanks!' : 'Helpful' ?></span>
        <?php if ($helpfulCount > 0): ?>
        <span class="text-gray-400">(<?= $helpfulCount ?>)</span>
        <?php endif; ?>
    </button>
    <?php
}
