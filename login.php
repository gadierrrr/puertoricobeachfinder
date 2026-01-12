<?php
/**
 * Login Page - Google OAuth + Magic Link Authentication
 */

require_once __DIR__ . '/inc/session.php';
session_start();
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/google-oauth.php';

// If already logged in, redirect
if (isAuthenticated()) {
    redirect($_GET['redirect'] ?? '/');
}

$error = '';
$success = '';
$redirectUrl = $_GET['redirect'] ?? '/';

// Handle OAuth error codes from callback
if (isset($_GET['error'])) {
    $errorMessages = [
        'google_denied' => 'Google sign-in was cancelled.',
        'no_code' => 'Authentication failed. Please try again.',
        'invalid_state' => 'Invalid request. Please try again.',
        'token_failed' => 'Failed to authenticate with Google. Please try again.',
        'userinfo_failed' => 'Failed to get your profile from Google.',
        'email_not_verified' => 'Please verify your Google email address first.',
        'user_creation_failed' => 'Failed to create account. Please try again.',
        'google_not_configured' => 'Google sign-in is not configured.',
    ];
    $error = $errorMessages[$_GET['error']] ?? 'Authentication error. Please try again.';
}

// Handle session timeout
if (isset($_GET['timeout'])) {
    $error = 'Your session has expired. Please sign in again.';
}

// Handle form submission (magic link)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/inc/auth.php';

    // Validate CSRF
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $result = sendMagicLink($email);

        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['error'];
        }
    }
}

$googleEnabled = isGoogleOAuthEnabled();

$pageTitle = 'Sign In';
include __DIR__ . '/components/header.php';
?>

<div class="min-h-[60vh] flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <span class="text-6xl">🏖️</span>
            <h1 class="text-3xl font-bold text-gray-900 mt-4">Sign In</h1>
            <p class="text-gray-600 mt-2">Access your favorites and leave reviews</p>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?= h($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
            <p class="font-medium">Check your email!</p>
            <p class="text-sm mt-1"><?= h($success) ?></p>
        </div>
        <?php else: ?>

        <div class="bg-white shadow-lg rounded-xl p-8">
            <?php if ($googleEnabled): ?>
            <!-- Google Sign In Button -->
            <a href="/auth/google/<?= $redirectUrl !== '/' ? '?redirect=' . urlencode($redirectUrl) : '' ?>"
               class="w-full flex items-center justify-center gap-3 bg-white border-2 border-gray-200 hover:border-gray-300 hover:bg-gray-50 text-gray-700 py-3 px-4 rounded-lg font-medium transition-all">
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                <span>Continue with Google</span>
            </a>

            <p class="text-center text-sm text-gray-500 mt-4">
                Sign in securely with your Google account.
            </p>
            <?php else: ?>
            <div class="text-center py-4 text-gray-500">
                <p>Sign-in is temporarily unavailable.</p>
                <p class="text-sm mt-2">Please try again later.</p>
            </div>
            <?php endif; ?>
        </div>

        <?php endif; ?>

        <div class="text-center mt-6">
            <a href="/" class="text-blue-600 hover:text-blue-700 text-sm">
                ← Back to Beach Finder
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>
