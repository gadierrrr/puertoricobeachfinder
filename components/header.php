<?php
/**
 * Site Header Component
 * Include at the top of all pages
 */

require_once __DIR__ . '/../inc/session.php';
session_start();
require_once __DIR__ . '/../inc/security_headers.php';
require_once __DIR__ . '/../inc/helpers.php';

$user = currentUser();
$appName = $_ENV['APP_NAME'] ?? 'Beach Finder';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? h($pageTitle) . ' | ' : '' ?><?= h($appName) ?></title>

    <?php if (isset($pageDescription)): ?>
    <meta name="description" content="<?= h($pageDescription) ?>">
    <?php endif; ?>

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Beach Finder">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/icons/icon-152x152.png">

    <!-- Open Graph / Social -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= h($appName) ?>">
    <meta property="og:title" content="<?= isset($pageTitle) ? h($pageTitle) : h($appName) ?>">
    <?php if (isset($pageDescription)): ?>
    <meta property="og:description" content="<?= h($pageDescription) ?>">
    <?php endif; ?>
    <?php if (isset($ogImage)): ?>
    <meta property="og:image" content="<?= h($ogImage) ?>">
    <?php else: ?>
    <meta property="og:image" content="<?= h($_ENV['APP_URL'] ?? '') ?>/assets/icons/icon-512x512.png">
    <?php endif; ?>

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@PRBeachFinder">
    <meta name="twitter:creator" content="@PRBeachFinder">

    <!-- Canonical URL -->
    <?php
    $appUrl = $_ENV['APP_URL'] ?? 'https://www.puertoricobeachfinder.com';
    if (isset($canonicalUrl)) {
        // Use explicitly set canonical
        $canonical = $canonicalUrl;
    } elseif (isset($beach['slug'])) {
        $canonical = $appUrl . '/beach/' . $beach['slug'];
    } elseif ($_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] === '/index.php') {
        $canonical = $appUrl . '/';
    } else {
        $canonical = $appUrl . strtok($_SERVER['REQUEST_URI'], '?');
    }
    ?>
    <link rel="canonical" href="<?= h($canonical) ?>">
    <meta property="og:url" content="<?= h($canonical) ?>">

    <!-- Geographic Meta Tags -->
    <meta name="geo.region" content="US-PR">
    <meta name="geo.placename" content="Puerto Rico">
    <meta name="geo.position" content="18.2208;-66.5901">
    <meta name="ICBM" content="18.2208, -66.5901">

    <!-- Preconnect to CDNs (placed early for optimal performance) -->
    <link rel="preconnect" href="https://basemaps.cartocdn.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Nunito Font (Nomads.com inspired) -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (local build - no render-blocking JS) -->
    <link rel="stylesheet" href="/assets/css/tailwind.min.css">

    <!-- MapLibre GL CSS -->
    <link href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css" rel="stylesheet">

    <!-- Tom Select CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Custom styles -->
    <link rel="stylesheet" href="/assets/css/styles.css">

    <!-- Deferred scripts (non-blocking) -->
    <script defer src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script defer src="https://unpkg.com/htmx.org@1.9.10"></script>

    <?php if (isset($extraHead)) echo $extraHead; ?>

    <!-- Theme initialization (inline to prevent flash) -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
</head>
<body class="min-h-screen flex flex-col font-['Nunito',system-ui,sans-serif]" style="background-color: var(--color-bg-secondary);">
    <!-- Navigation -->
    <nav class="shadow-sm border-b sticky top-0 z-40" style="background-color: var(--color-card-bg); border-color: var(--color-border);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/" class="flex items-center space-x-2">
                        <i data-lucide="umbrella" class="w-7 h-7 text-blue-600"></i>
                        <span class="font-bold text-xl" style="color: var(--color-text-primary);"><?= h($appName) ?></span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden sm:flex sm:items-center sm:space-x-4">
                    <a href="/" class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium inline-flex items-center gap-1.5">
                        <i data-lucide="compass" class="w-4 h-4"></i>
                        <span>Explore</span>
                    </a>
                    <a href="/quiz.php" class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium inline-flex items-center gap-1.5">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                        <span>Beach Match</span>
                    </a>
                    <?php if ($user): ?>
                        <a href="/favorites.php" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium inline-flex items-center gap-1">
                            <i data-lucide="heart" class="w-4 h-4 text-red-500 fill-red-500"></i>
                            <span>Favorites</span>
                        </a>
                        <div class="flex items-center gap-3 ml-4 pl-4 border-l border-gray-200">
                            <?php if (!empty($user['avatar_url'])): ?>
                            <img src="<?= h($user['avatar_url']) ?>" alt="" class="w-8 h-8 rounded-full">
                            <?php else: ?>
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium text-sm">
                                <?= strtoupper(substr($user['name'] ?? $user['email'], 0, 1)) ?>
                            </div>
                            <?php endif; ?>
                            <span class="text-sm text-gray-600"><?= h($user['name'] ?? 'User') ?></span>
                            <a href="/logout.php" class="text-sm text-red-600 hover:text-red-700">Logout</a>
                        </div>
                    <?php else: ?>
                        <a href="/login.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                            Sign In
                        </a>
                    <?php endif; ?>

                    <!-- Theme Toggle -->
                    <button id="theme-toggle"
                            onclick="toggleTheme()"
                            class="theme-toggle ml-2"
                            aria-label="Toggle dark mode">
                        <i data-lucide="sun" class="w-5 h-5 icon-sun"></i>
                        <i data-lucide="moon" class="w-5 h-5 icon-moon"></i>
                    </button>
                </div>

                <!-- Mobile menu button -->
                <div class="flex items-center gap-2 sm:hidden">
                    <!-- Mobile Theme Toggle -->
                    <button id="mobile-theme-toggle"
                            onclick="toggleTheme()"
                            class="theme-toggle"
                            aria-label="Toggle dark mode">
                        <i data-lucide="sun" class="w-5 h-5 icon-sun"></i>
                        <i data-lucide="moon" class="w-5 h-5 icon-moon"></i>
                    </button>
                    <button type="button"
                            onclick="document.getElementById('mobile-menu').classList.toggle('hidden')"
                            class="p-2" style="color: var(--color-text-secondary);">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="mobile-menu" class="hidden sm:hidden border-t" style="background-color: var(--color-card-bg); border-color: var(--color-border);">
            <div class="px-4 py-3 space-y-2">
                <a href="/" class="flex items-center gap-2 text-gray-600 hover:text-gray-900 py-2">
                    <i data-lucide="compass" class="w-5 h-5"></i>
                    <span>Explore</span>
                </a>
                <a href="/quiz.php" class="flex items-center gap-2 text-gray-600 hover:text-gray-900 py-2">
                    <i data-lucide="sparkles" class="w-5 h-5"></i>
                    <span>Beach Match</span>
                </a>
                <?php if ($user): ?>
                    <a href="/favorites.php" class="flex items-center gap-2 text-gray-600 hover:text-gray-900 py-2">
                        <i data-lucide="heart" class="w-4 h-4 text-red-500 fill-red-500"></i>
                        <span>Favorites</span>
                    </a>
                    <div class="pt-2 mt-2 border-t border-gray-200">
                        <div class="flex items-center gap-3 py-2">
                            <?php if (!empty($user['avatar_url'])): ?>
                            <img src="<?= h($user['avatar_url']) ?>" alt="" class="w-8 h-8 rounded-full">
                            <?php else: ?>
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium text-sm">
                                <?= strtoupper(substr($user['name'] ?? $user['email'], 0, 1)) ?>
                            </div>
                            <?php endif; ?>
                            <span class="text-sm text-gray-600"><?= h($user['name'] ?? 'User') ?></span>
                        </div>
                        <a href="/logout.php" class="block text-red-600 hover:text-red-700 py-2">Logout</a>
                    </div>
                <?php else: ?>
                    <a href="/login.php" class="block bg-blue-600 text-white text-center py-2 rounded-md mt-2">Sign In</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1">
