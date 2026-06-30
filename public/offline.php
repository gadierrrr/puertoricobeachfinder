<?php
/**
 * Offline Page
 * Shown when user is offline and page isn't cached
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
// This page hand-rolls its <head> (it must stay self-contained for the offline
// fallback) instead of using components/header.php, so it must pull in the CSP
// helpers itself — otherwise the cspNonceAttr() call below fatals with
// "undefined function" and the page 500s. security_headers.php also emits the
// CSP/security headers and guards against output being already sent.
require_once APP_ROOT . '/inc/security_headers.php';

$pageTitle = 'Offline';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline | Puerto Rico Beach Finder</title>
    <link rel="stylesheet" href="/assets/css/tailwind.min.css">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="text-center max-w-md">
        <div class="text-8xl mb-6 animate-bounce">🏖️</div>

        <h1 class="text-3xl font-bold text-gray-900 mb-4">
            You're Offline
        </h1>

        <p class="text-gray-600 mb-8">
            It looks like you've lost your internet connection.
            Don't worry, some beaches may still be available from your cache!
        </p>

        <div class="space-y-4">
            <button data-action="reloadPage"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-lg font-medium transition-colors">
                Try Again
            </button>

            <a href="/"
               class="block w-full bg-gray-200 hover:bg-gray-300 text-gray-700 py-3 px-6 rounded-lg font-medium transition-colors">
                Go to Home (Cached)
            </a>
        </div>

        <div class="mt-8 p-4 bg-blue-50 a11y-on-light-blue rounded-lg">
            <h2 class="font-medium text-blue-900 mb-2">Tip</h2>
            <p class="text-blue-700 text-sm">
                Visit your favorite beaches while online to cache them for offline viewing.
            </p>
        </div>
    </div>

    <script <?= cspNonceAttr() ?>>
        // Check for connection and reload if back online
        window.addEventListener('online', () => {
            window.location.reload();
        });
    </script>
</body>
</html>
