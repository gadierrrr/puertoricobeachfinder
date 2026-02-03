<?php
/**
 * Google OAuth Callback Handler
 * Handles the redirect from Google after user authorizes
 */

require_once __DIR__ . '/../../inc/session.php';
session_start();
require_once __DIR__ . '/../../inc/db.php';
require_once __DIR__ . '/../../inc/helpers.php';
require_once __DIR__ . '/../../inc/google-oauth.php';

// Check for errors from Google
if (isset($_GET['error'])) {
    $error = $_GET['error'];
    $errorDesc = $_GET['error_description'] ?? 'Unknown error';
    error_log("Google OAuth error: $error - $errorDesc");
    redirect('/login.php?error=google_denied');
}

// Verify authorization code is present
if (!isset($_GET['code'])) {
    redirect('/login.php?error=no_code');
}

// Verify state token (CSRF protection)
$state = $_GET['state'] ?? '';
$expectedState = $_SESSION['google_oauth_state'] ?? '';

if (!$state || !$expectedState || !hash_equals($expectedState, $state)) {
    error_log("Google OAuth state mismatch");
    redirect('/login.php?error=invalid_state');
}

// Clear state token (one-time use)
unset($_SESSION['google_oauth_state']);

// Exchange code for access token
$tokenData = exchangeCodeForToken($_GET['code']);

if (!$tokenData) {
    redirect('/login.php?error=token_failed');
}

// Fetch user info from Google
$googleUser = getGoogleUserInfo($tokenData['access_token']);

if (!$googleUser) {
    redirect('/login.php?error=userinfo_failed');
}

// Verify email is verified
if (!$googleUser['verified_email']) {
    redirect('/login.php?error=email_not_verified');
}

// Find or create user
$user = findOrCreateGoogleUser($googleUser);

if (!$user) {
    redirect('/login.php?error=user_creation_failed');
}

// Login the user
loginUser($user);

// Get redirect URL from session or default to home
$redirectUrl = $_SESSION['google_oauth_redirect'] ?? '/';
unset($_SESSION['google_oauth_redirect']);

$redirectUrl = sanitizeInternalRedirect($redirectUrl, '/');

// Redirect new users to onboarding (if not already completed)
if (empty($user['onboarding_completed'])) {
    $redirectUrl = '/onboarding.php' . ($redirectUrl !== '/' ? '?redirect=' . urlencode($redirectUrl) : '');
}

redirectInternal($redirectUrl);
