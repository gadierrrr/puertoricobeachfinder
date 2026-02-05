<?php
/**
 * API: Submit Beach Review
 *
 * POST /api/submit-review.php
 * Body: beach_id, rating, title, review_text, visit_date, visit_type, would_recommend, csrf_token
 * Returns JSON response
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
session_start();
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';

header('Content-Type: application/json');

// Require authentication
if (!isAuthenticated()) {
    jsonResponse(['success' => false, 'error' => 'Please sign in to leave a review'], 401);
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

// Validate CSRF token
$csrfToken = $_POST['csrf_token'] ?? '';
if (!validateCsrf($csrfToken)) {
    jsonResponse(['success' => false, 'error' => 'Invalid security token. Please refresh and try again.'], 403);
}

// Get and validate input
$beachId = trim($_POST['beach_id'] ?? '');
$rating = (int)($_POST['rating'] ?? 0);
$title = trim($_POST['title'] ?? '');
$reviewText = trim($_POST['review_text'] ?? '');
$visitDate = trim($_POST['visit_date'] ?? '');
$visitType = trim($_POST['visit_type'] ?? '');
$wouldRecommend = isset($_POST['would_recommend']) ? 1 : 0;

// Validation
$errors = [];

if (!$beachId) {
    $errors[] = 'Beach ID is required';
}

if ($rating < 1 || $rating > 5) {
    $errors[] = 'Rating must be between 1 and 5';
}

if (strlen($title) > 100) {
    $errors[] = 'Title must be 100 characters or less';
}

if (strlen($reviewText) > 2000) {
    $errors[] = 'Review must be 2000 characters or less';
}

if ($reviewText && strlen($reviewText) < 10) {
    $errors[] = 'Review must be at least 10 characters';
}

$allowedVisitTypes = ['solo', 'couple', 'family', 'friends', 'group'];
if ($visitType && !in_array($visitType, $allowedVisitTypes)) {
    $errors[] = 'Invalid visit type';
}

if ($visitDate && !preg_match('/^\d{4}-\d{2}(-\d{2})?$/', $visitDate)) {
    $errors[] = 'Invalid visit date format';
}

if (!empty($errors)) {
    jsonResponse(['success' => false, 'error' => implode('. ', $errors)], 400);
}

// Verify beach exists
$beach = queryOne('SELECT id, name FROM beaches WHERE id = :id', [':id' => $beachId]);
if (!$beach) {
    jsonResponse(['success' => false, 'error' => 'Beach not found'], 404);
}

$userId = $_SESSION['user_id'];

// Check if user already reviewed this beach
$existingReview = queryOne(
    'SELECT id FROM beach_reviews WHERE beach_id = :beach_id AND user_id = :user_id',
    [':beach_id' => $beachId, ':user_id' => $userId]
);

if ($existingReview) {
    // Update existing review
    $stmt = getDB()->prepare('
        UPDATE beach_reviews SET
            rating = :rating,
            title = :title,
            review_text = :review_text,
            visit_date = :visit_date,
            visit_type = :visit_type,
            would_recommend = :would_recommend,
            updated_at = datetime("now"),
            status = "published"
        WHERE id = :id
    ');
    $stmt->bindValue(':rating', $rating, SQLITE3_INTEGER);
    $stmt->bindValue(':title', $title ?: null, SQLITE3_TEXT);
    $stmt->bindValue(':review_text', $reviewText ?: null, SQLITE3_TEXT);
    $stmt->bindValue(':visit_date', $visitDate ?: null, SQLITE3_TEXT);
    $stmt->bindValue(':visit_type', $visitType ?: null, SQLITE3_TEXT);
    $stmt->bindValue(':would_recommend', $wouldRecommend, SQLITE3_INTEGER);
    $stmt->bindValue(':id', $existingReview['id'], SQLITE3_TEXT);

    if ($stmt->execute()) {
        // Update beach average rating
        updateBeachRating($beachId);
        jsonResponse([
            'success' => true,
            'message' => 'Review updated successfully',
            'review_id' => $existingReview['id']
        ]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Failed to update review'], 500);
    }
} else {
    // Create new review
    $reviewId = uuid();

    $stmt = getDB()->prepare('
        INSERT INTO beach_reviews (id, beach_id, user_id, rating, title, review_text, visit_date, visit_type, would_recommend, created_at, updated_at, status)
        VALUES (:id, :beach_id, :user_id, :rating, :title, :review_text, :visit_date, :visit_type, :would_recommend, datetime("now"), datetime("now"), "published")
    ');
    $stmt->bindValue(':id', $reviewId, SQLITE3_TEXT);
    $stmt->bindValue(':beach_id', $beachId, SQLITE3_TEXT);
    $stmt->bindValue(':user_id', $userId, SQLITE3_TEXT);
    $stmt->bindValue(':rating', $rating, SQLITE3_INTEGER);
    $stmt->bindValue(':title', $title ?: null, SQLITE3_TEXT);
    $stmt->bindValue(':review_text', $reviewText ?: null, SQLITE3_TEXT);
    $stmt->bindValue(':visit_date', $visitDate ?: null, SQLITE3_TEXT);
    $stmt->bindValue(':visit_type', $visitType ?: null, SQLITE3_TEXT);
    $stmt->bindValue(':would_recommend', $wouldRecommend, SQLITE3_INTEGER);

    if ($stmt->execute()) {
        // Update beach average rating
        updateBeachRating($beachId);
        jsonResponse([
            'success' => true,
            'message' => 'Review submitted successfully',
            'review_id' => $reviewId
        ]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Failed to submit review'], 500);
    }
}

/**
 * Update beach's average user rating
 */
function updateBeachRating(string $beachId): void {
    $stats = queryOne('
        SELECT AVG(rating) as avg_rating, COUNT(*) as review_count
        FROM beach_reviews
        WHERE beach_id = :id AND status = "published"
    ', [':id' => $beachId]);

    if ($stats) {
        execute('
            UPDATE beaches SET
                avg_user_rating = :avg_rating,
                user_review_count = :review_count,
                updated_at = datetime("now")
            WHERE id = :id
        ', [
            ':avg_rating' => $stats['avg_rating'],
            ':review_count' => $stats['review_count'],
            ':id' => $beachId
        ]);
    }
}
