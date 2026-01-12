<?php
/**
 * API: List Beach Reviews
 * GET /api/reviews/list.php?beach_id=xxx
 */

require_once __DIR__ . '/../../inc/db.php';
require_once __DIR__ . '/../../inc/helpers.php';

header('Content-Type: application/json');
header('Cache-Control: public, max-age=300');

$beachId = $_GET['beach_id'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = min(50, max(1, intval($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;
$sortBy = $_GET['sort'] ?? 'recent'; // recent, helpful, rating_high, rating_low

if (!$beachId) {
    jsonResponse(['success' => false, 'error' => 'Beach ID required'], 400);
}

// Build sort clause
$orderBy = match($sortBy) {
    'helpful' => 'r.helpful_count DESC, r.created_at DESC',
    'rating_high' => 'r.rating DESC, r.created_at DESC',
    'rating_low' => 'r.rating ASC, r.created_at DESC',
    default => 'r.created_at DESC'
};

// Get reviews with user names
$reviews = query("
    SELECT
        r.id, r.rating, r.title, r.review_text, r.visit_date, r.visit_type,
        r.helpful_count, r.created_at, r.would_recommend,
        u.name as user_name
    FROM beach_reviews r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.beach_id = :beach_id AND r.status = 'published'
    ORDER BY {$orderBy}
    LIMIT {$limit} OFFSET {$offset}
", [':beach_id' => $beachId]);

// Get total count
$total = queryOne("
    SELECT COUNT(*) as count
    FROM beach_reviews
    WHERE beach_id = :beach_id AND status = 'published'
", [':beach_id' => $beachId])['count'] ?? 0;

// Get rating distribution
$distribution = query("
    SELECT rating, COUNT(*) as count
    FROM beach_reviews
    WHERE beach_id = :beach_id AND status = 'published'
    GROUP BY rating
    ORDER BY rating DESC
", [':beach_id' => $beachId]);

$ratingDistribution = array_fill(1, 5, 0);
foreach ($distribution as $row) {
    $ratingDistribution[$row['rating']] = $row['count'];
}

jsonResponse([
    'success' => true,
    'data' => $reviews,
    'meta' => [
        'total' => (int)$total,
        'page' => $page,
        'limit' => $limit,
        'pages' => ceil($total / $limit),
        'rating_distribution' => $ratingDistribution
    ]
]);
