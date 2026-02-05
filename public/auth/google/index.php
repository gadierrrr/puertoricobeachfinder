<?php
/**
 * Google OAuth Initiation
 * Redirects user to Google for authentication
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
session_start();
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/google-oauth.php';

// Check if Google OAuth is configured
if (!isGoogleOAuthEnabled()) {
    redirect('/login.php?error=google_not_configured');
}

// Get optional redirect URL
$redirectAfterLogin = null;
if (isset($_GET['redirect'])) {
    $redirectAfterLogin = sanitizeInternalRedirect($_GET['redirect'], '/');
}

// Redirect to Google's authorization page
$authUrl = getGoogleAuthUrl($redirectAfterLogin);
redirect($authUrl);
