<?php
/**
 * Chat API: Report a message.
 * POST /api/chat/report  body: message_id, reason, details, csrf_token
 * Requires authentication.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/session.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once APP_ROOT . '/inc/chat.php';
require_once APP_ROOT . '/inc/chat_moderation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

if (!isAuthenticated()) {
    jsonResponse(['success' => false, 'error' => 'Please sign in to report messages.'], 401);
}

$csrfToken = (string)($_POST['csrf_token'] ?? '');
if (!validateCsrf($csrfToken)) {
    jsonResponse(['success' => false, 'error' => 'Invalid security token.'], 403);
}

// Rate limit reports
require_once APP_ROOT . '/inc/rate_limiter.php';
$user = currentUser();
$reportLimiter = new RateLimiter(getDB());
$reportCheck = $reportLimiter->check($user['id'], 'chat_report', 10, 5);
if (!$reportCheck['allowed']) {
    jsonResponse(['success' => false, 'error' => 'Too many reports. Please try again later.'], 429);
}

$messageId = trim((string)($_POST['message_id'] ?? ''));
$reason = trim((string)($_POST['reason'] ?? ''));
$details = trim((string)($_POST['details'] ?? '')) ?: null;

if ($messageId === '' || $reason === '') {
    jsonResponse(['success' => false, 'error' => 'message_id and reason are required.'], 400);
}

$result = chatHandleReport($messageId, $user['id'], $reason, $details);

if (!$result['success']) {
    jsonResponse(['success' => false, 'error' => $result['error']], 400);
}

jsonResponse(['success' => true, 'message' => __('chat.report_submitted')]);
