<?php
/**
 * API: Submit Beach Review
 * POST /api/reviews/submit.php
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/session.php';

session_start();

header('Content-Type: application/json');

// Require authentication
if (!isAuthenticated()) {
    jsonResponse(['success' => false, 'error' => 'Please sign in to leave a review'], 401);
}

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

// Get input
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$beachId = trim($input['beach_id'] ?? '');
$rating = intval($input['rating'] ?? 0);
$title = trim($input['title'] ?? '');
$reviewText = trim($input['review_text'] ?? '');
$visitDate = trim($input['visit_date'] ?? '');
$visitType = trim($input['visit_type'] ?? '');
$csrfToken = $input['csrf_token'] ?? '';

// CSRF validation
if (!validateCsrf($csrfToken)) {
    jsonResponse(['success' => false, 'error' => 'Invalid request'], 403);
}

// Validate beach exists
$beach = queryOne('SELECT id, name FROM beaches WHERE id = :id', [':id' => $beachId]);
if (!$beach) {
    jsonResponse(['success' => false, 'error' => 'Beach not found'], 404);
}

// Validate rating
if ($rating < 1 || $rating > 5) {
    jsonResponse(['success' => false, 'error' => 'Rating must be between 1 and 5'], 400);
}

// Validate visit type
$validVisitTypes = ['solo', 'couple', 'family', 'friends', 'group', ''];
if (!in_array($visitType, $validVisitTypes)) {
    $visitType = '';
}

// Validate visit date (optional, must be in past)
if ($visitDate) {
    $visitTimestamp = strtotime($visitDate);
    if (!$visitTimestamp || $visitTimestamp > time()) {
        $visitDate = '';
    } else {
        $visitDate = date('Y-m-d', $visitTimestamp);
    }
}

// Sanitize text
$title = substr(strip_tags($title), 0, 200);
$reviewText = substr(strip_tags($reviewText), 0, 2000);

// Check if user already reviewed this beach
$existingReview = queryOne(
    'SELECT id FROM beach_reviews WHERE beach_id = :beach_id AND user_id = :user_id',
    [':beach_id' => $beachId, ':user_id' => $_SESSION['user_id']]
);

if ($existingReview) {
    jsonResponse(['success' => false, 'error' => 'You have already reviewed this beach'], 400);
}

// Generate review ID
$reviewId = generateUuid();

// Insert review
$db = getDb();
$stmt = $db->prepare("
    INSERT INTO beach_reviews (id, beach_id, user_id, rating, title, review_text, visit_date, visit_type, created_at)
    VALUES (:id, :beach_id, :user_id, :rating, :title, :review_text, :visit_date, :visit_type, datetime('now'))
");

$stmt->bindValue(':id', $reviewId, SQLITE3_TEXT);
$stmt->bindValue(':beach_id', $beachId, SQLITE3_TEXT);
$stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_TEXT);
$stmt->bindValue(':rating', $rating, SQLITE3_INTEGER);
$stmt->bindValue(':title', $title ?: null, SQLITE3_TEXT);
$stmt->bindValue(':review_text', $reviewText ?: null, SQLITE3_TEXT);
$stmt->bindValue(':visit_date', $visitDate ?: null, SQLITE3_TEXT);
$stmt->bindValue(':visit_type', $visitType ?: null, SQLITE3_TEXT);

if (!$stmt->execute()) {
    jsonResponse(['success' => false, 'error' => 'Failed to save review'], 500);
}

// Update beach average rating
updateBeachRating($beachId);

jsonResponse([
    'success' => true,
    'message' => 'Review submitted successfully',
    'review_id' => $reviewId
]);

/**
 * Update beach average rating and review count
 */
function updateBeachRating(string $beachId): void {
    $db = getDb();

    // Calculate average
    $stats = queryOne("
        SELECT AVG(rating) as avg_rating, COUNT(*) as review_count
        FROM beach_reviews
        WHERE beach_id = :beach_id AND status = 'published'
    ", [':beach_id' => $beachId]);

    // Update beach
    $stmt = $db->prepare("
        UPDATE beaches
        SET avg_user_rating = :avg_rating, user_review_count = :review_count
        WHERE id = :beach_id
    ");
    $stmt->bindValue(':avg_rating', $stats['avg_rating'] ? round($stats['avg_rating'], 2) : null, SQLITE3_FLOAT);
    $stmt->bindValue(':review_count', $stats['review_count'] ?? 0, SQLITE3_INTEGER);
    $stmt->bindValue(':beach_id', $beachId, SQLITE3_TEXT);
    $stmt->execute();
}

/**
 * Generate UUID v4
 */
function generateUuid(): string {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
