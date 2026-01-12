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

if (!$token) {
    $pageTitle = 'Invalid Link';
    include __DIR__ . '/components/header.php';
    echo '<div class="max-w-md mx-auto px-4 py-16 text-center">
            <div class="text-6xl mb-4">❌</div>
            <h1 class="text-2xl font-bold text-gray-900 mb-4">Invalid Link</h1>
            <p class="text-gray-600 mb-6">This login link is invalid or missing.</p>
            <a href="/login.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                Request New Link
            </a>
          </div>';
    include __DIR__ . '/components/footer.php';
    exit;
}

$result = verifyMagicLink($token);

if (!$result['success']) {
    $pageTitle = 'Link Expired';
    include __DIR__ . '/components/header.php';
    echo '<div class="max-w-md mx-auto px-4 py-16 text-center">
            <div class="text-6xl mb-4">⏰</div>
            <h1 class="text-2xl font-bold text-gray-900 mb-4">Link Expired</h1>
            <p class="text-gray-600 mb-6">' . h($result['error']) . '</p>
            <a href="/login.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                Request New Link
            </a>
          </div>';
    include __DIR__ . '/components/footer.php';
    exit;
}

// Success - redirect to intended destination or home
$redirect = $_GET['redirect'] ?? '/';
redirect($redirect);
