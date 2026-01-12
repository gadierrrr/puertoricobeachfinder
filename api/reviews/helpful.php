<?php
/**
 * API: Mark Review as Helpful
 * POST /api/reviews/helpful.php
 */

require_once __DIR__ . '/../../inc/db.php';
require_once __DIR__ . '/../../inc/helpers.php';
require_once __DIR__ . '/../../inc/session.php';

session_start();

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Get review ID
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$reviewId = trim($input['review_id'] ?? '');

if (!$reviewId) {
    http_response_code(400);
    exit;
}

// Verify review exists
$review = queryOne('SELECT id, helpful_count FROM beach_reviews WHERE id = :id', [':id' => $reviewId]);
if (!$review) {
    http_response_code(404);
    exit;
}

$helpfulCount = $review['helpful_count'] ?? 0;
$voted = false;

// If user is logged in, track their vote
if (isAuthenticated()) {
    $userId = $_SESSION['user_id'];

    // Check if already voted
    $existingVote = queryOne(
        'SELECT id FROM review_helpful_votes WHERE review_id = :review_id AND user_id = :user_id',
        [':review_id' => $reviewId, ':user_id' => $userId]
    );

    if (!$existingVote) {
        // Add vote
        $db = getDb();
        $voteId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        $stmt = $db->prepare("
            INSERT INTO review_helpful_votes (id, review_id, user_id, created_at)
            VALUES (:id, :review_id, :user_id, datetime('now'))
        ");
        $stmt->bindValue(':id', $voteId, SQLITE3_TEXT);
        $stmt->bindValue(':review_id', $reviewId, SQLITE3_TEXT);
        $stmt->bindValue(':user_id', $userId, SQLITE3_TEXT);

        if ($stmt->execute()) {
            // Update count
            $db->exec("UPDATE beach_reviews SET helpful_count = helpful_count + 1 WHERE id = '$reviewId'");
            $helpfulCount++;
            $voted = true;
        }
    } else {
        $voted = true; // Already voted
    }
} else {
    // Anonymous - just increment (simple rate limiting would be good here)
    $db = getDb();
    $db->exec("UPDATE beach_reviews SET helpful_count = helpful_count + 1 WHERE id = '$reviewId'");
    $helpfulCount++;
    $voted = true;
}

// Return updated button
header('Content-Type: text/html; charset=utf-8');
?>
<button class="helpful-btn flex items-center gap-1.5 <?= $voted ? 'text-blue-600' : 'text-gray-500 hover:text-blue-600' ?> text-sm transition-colors"
        <?php if (!$voted): ?>
        hx-post="/api/reviews/helpful.php"
        hx-vals='{"review_id": "<?= h($reviewId) ?>"}'
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
