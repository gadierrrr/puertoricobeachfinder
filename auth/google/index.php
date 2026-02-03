<?php
/**
 * Google OAuth Initiation
 * Redirects user to Google for authentication
 */

require_once __DIR__ . '/../../inc/session.php';
session_start();
require_once __DIR__ . '/../../inc/db.php';
require_once __DIR__ . '/../../inc/helpers.php';
require_once __DIR__ . '/../../inc/google-oauth.php';

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
