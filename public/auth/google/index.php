<?php
/**
 * Google OAuth Initiation
 * Redirects user to Google for authentication
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

// Old parameterized links remain usable, but GET is an ordinary noindex login
// page. Only a deliberate form POST starts OAuth; crawlers cannot mint sessions
// at Google by following thousands of redirect= URL variations.
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Cache-Control: no-store');
if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
    require PUBLIC_ROOT . '/login.php';
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: GET, HEAD, POST');
    exit;
}

require_once APP_ROOT . '/inc/session.php';
session_start();
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/google-oauth.php';

if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo 'Please return to the sign-in page and try again.';
    exit;
}

// Check if Google OAuth is configured
if (!isGoogleOAuthEnabled()) {
    redirect('/login?error=google_not_configured');
}

// Get optional redirect URL
$redirectAfterLogin = null;
if (isset($_POST['redirect'])) {
    $redirectAfterLogin = sanitizeInternalRedirect($_POST['redirect'], '/');
}

// Redirect to Google's authorization page
$authUrl = getGoogleAuthUrl($redirectAfterLogin);
redirect($authUrl);
