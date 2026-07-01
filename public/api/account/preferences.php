<?php
/**
 * Update the current user's notification preferences (weekly digest opt in/out).
 * POST { csrf_token, weekly_digest: '1'|'0' } -> JSON.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
session_start();
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}
if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    jsonResponse(['error' => 'Invalid request'], 403);
}

$userId = $_SESSION['user_id'];
$weeklyDigest = (isset($_POST['weekly_digest']) && in_array((string) $_POST['weekly_digest'], ['1', 'true', 'on'], true)) ? 1 : 0;

execute(
    'UPDATE user_preferences SET weekly_digest = :w, updated_at = datetime("now") WHERE user_id = :id',
    [':w' => $weeklyDigest, ':id' => $userId]
);
if (getDB()->changes() === 0) {
    execute(
        'INSERT OR IGNORE INTO user_preferences (user_id, weekly_digest, notifications_enabled, updated_at) VALUES (:id, :w, 1, datetime("now"))',
        [':id' => $userId, ':w' => $weeklyDigest]
    );
}

jsonResponse(['success' => true, 'weekly_digest' => $weeklyDigest]);
