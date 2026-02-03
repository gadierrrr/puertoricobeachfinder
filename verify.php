<?php
/**
 * Magic Link Verification
 */

require_once __DIR__ . '/inc/session.php';
session_start();
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/auth.php';

$token = $_GET['token'] ?? '';
$skipMapCSS = true; // Auth pages don't need map

if (!$token) {
    $pageTitle = 'Invalid Link';
    include __DIR__ . '/components/header.php';
    echo '<div class="max-w-md mx-auto px-4 py-16 text-center">
            <div class="text-6xl mb-4">❌</div>
            <h1 class="text-2xl font-bold text-white mb-4">Invalid Link</h1>
            <p class="text-gray-400 mb-6">This login link is invalid or missing.</p>
            <a href="/login.php" class="inline-block bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-6 py-3 rounded-lg font-medium">
                Request New Link
            </a>
          </div>';
    include __DIR__ . '/components/footer-minimal.php';
    exit;
}

$result = verifyMagicLink($token);

if (!$result['success']) {
    $pageTitle = 'Link Expired';
    include __DIR__ . '/components/header.php';
    echo '<div class="max-w-md mx-auto px-4 py-16 text-center">
            <div class="text-6xl mb-4">⏰</div>
            <h1 class="text-2xl font-bold text-white mb-4">Link Expired</h1>
            <p class="text-gray-400 mb-6">' . h($result['error']) . '</p>
            <a href="/login.php" class="inline-block bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-6 py-3 rounded-lg font-medium">
                Request New Link
            </a>
          </div>';
    include __DIR__ . '/components/footer-minimal.php';
    exit;
}

// Success - check if user needs onboarding
$user = currentUser();
$redirect = sanitizeInternalRedirect($_GET['redirect'] ?? '/');

// Redirect new users to onboarding (if not already completed)
if ($user && empty($user['onboarding_completed'])) {
    $redirect = '/onboarding.php' . ($redirect !== '/' ? '?redirect=' . urlencode($redirect) : '');
}

redirectInternal($redirect);
