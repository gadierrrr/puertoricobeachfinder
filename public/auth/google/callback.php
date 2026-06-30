<?php
/**
 * Google OAuth Callback Handler
 * Handles the redirect from Google after user authorizes
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
session_start();
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/google-oauth.php';

// Idempotency guard for duplicate callback requests.
//
// Browsers, prefetchers, and mobile retries sometimes fire this callback more
// than once for a single login. The first request consumes the one-time OAuth
// state token and Google's single-use authorization code and logs the user in;
// a duplicate that arrives afterward (carrying the now-current, post-login
// session cookie) would otherwise fail the state check and show a misleading
// "Invalid request. Please try again." If we're already authenticated, the work
// is done — treat the duplicate as success instead of an error.
if (isAuthenticated()) {
    $redirectUrl = sanitizeInternalRedirect($_SESSION['google_oauth_redirect'] ?? '/', '/');
    unset($_SESSION['google_oauth_redirect']);
    redirectInternal($redirectUrl);
}

// Check for errors from Google
if (isset($_GET['error'])) {
    $error = $_GET['error'];
    $errorDesc = $_GET['error_description'] ?? 'Unknown error';
    error_log("Google OAuth error: $error - $errorDesc");
    redirect('/login?error=google_denied');
}

// Verify authorization code is present
if (!isset($_GET['code'])) {
    redirect('/login?error=no_code');
}

// Verify state token (CSRF protection)
$state = $_GET['state'] ?? '';
$expectedState = $_SESSION['google_oauth_state'] ?? '';

if (!$state || !$expectedState || !hash_equals($expectedState, $state)) {
    error_log("Google OAuth state mismatch");
    redirect('/login?error=invalid_state');
}

// Clear state token (one-time use)
unset($_SESSION['google_oauth_state']);

// Exchange code for access token
$tokenData = exchangeCodeForToken($_GET['code']);

if (!$tokenData) {
    redirect('/login?error=token_failed');
}

// Fetch user info from Google
$googleUser = getGoogleUserInfo($tokenData['access_token']);

if (!$googleUser) {
    redirect('/login?error=userinfo_failed');
}

// Verify email is verified
if (!$googleUser['verified_email']) {
    redirect('/login?error=email_not_verified');
}

// Find or create user
$user = findOrCreateGoogleUser($googleUser);

if (!$user) {
    redirect('/login?error=user_creation_failed');
}

// Login the user
loginUser($user);

// Get redirect URL from session or default to home
$redirectUrl = $_SESSION['google_oauth_redirect'] ?? '/';
unset($_SESSION['google_oauth_redirect']);

$redirectUrl = sanitizeInternalRedirect($redirectUrl, '/');

// Redirect new users to onboarding (if not already completed)
if (empty($user['onboarding_completed'])) {
    $redirectUrl = '/onboarding' . ($redirectUrl !== '/' ? '?redirect=' . urlencode($redirectUrl) : '');
}

redirectInternal($redirectUrl);
