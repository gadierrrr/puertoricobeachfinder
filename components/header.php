<?php
/**
 * Site Header Component
 * Include at the top of all pages
 */

require_once __DIR__ . '/../inc/session.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../inc/security_headers.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/i18n.php';

$user = currentUser();
$appName = $_ENV['APP_NAME'] ?? 'Beach Finder';
$currentLang = getCurrentLanguage();
?>
<!DOCTYPE html>
<html lang="<?= getHtmlLang() ?>" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? h($pageTitle) . ' | ' : '' ?><?= h($appName) ?></title>

    <?php if (isset($pageDescription)): ?>
    <meta name="description" content="<?= h($pageDescription) ?>">
    <?php endif; ?>

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#1a2c32">
    <meta name="mobile-web-app-capable" content="yes">
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
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?= isset($pageTitle) ? h($pageTitle) : 'Puerto Rico Beach' ?>">
    <?php else: ?>
    <meta property="og:image" content="<?= h($_ENV['APP_URL'] ?? '') ?>/assets/icons/icon-512x512.png">
    <meta property="og:image:width" content="512">
    <meta property="og:image:height" content="512">
    <?php endif; ?>

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@PRBeachFinder">
    <meta name="twitter:creator" content="@PRBeachFinder">
    <meta name="twitter:title" content="<?= isset($pageTitle) ? h($pageTitle) : h($appName) ?>">
    <?php if (isset($pageDescription)): ?>
    <meta name="twitter:description" content="<?= h($pageDescription) ?>">
    <?php endif; ?>
    <?php if (isset($ogImage)): ?>
    <meta name="twitter:image" content="<?= h($ogImage) ?>">
    <meta name="twitter:image:alt" content="<?= isset($pageTitle) ? h($pageTitle) : 'Puerto Rico Beach' ?>">
    <?php else: ?>
    <meta name="twitter:image" content="<?= h($_ENV['APP_URL'] ?? '') ?>/assets/icons/icon-512x512.png">
    <?php endif; ?>

    <!-- Canonical URL -->
    <?php
    $appUrl = $_ENV['APP_URL'] ?? 'https://www.puertoricobeachfinder.com';
    if (isset($canonicalUrl)) {
        // Use explicitly set canonical
        $canonical = $canonicalUrl;
    } elseif (isset($beach['slug'])) {
        $canonical = $appUrl . '/beach/' . $beach['slug'];
    } elseif ($_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] === '/index.php' || strpos($_SERVER['REQUEST_URI'], '/?') === 0) {
        // Homepage and all filtered views (/?municipality=X, /?tags[]=Y) canonicalize to homepage
        $canonical = $appUrl . '/';
    } else {
        $canonical = $appUrl . strtok($_SERVER['REQUEST_URI'], '?');
    }
    ?>
    <link rel="canonical" href="<?= h($canonical) ?>">
    <meta property="og:url" content="<?= h($canonical) ?>">

    <!-- Robots Meta Tags -->
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">

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

    <!-- DNS Prefetch for third-party resources -->
    <link rel="dns-prefetch" href="https://basemaps.cartocdn.com">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">

    <!-- Preload critical CSS -->
    <link rel="preload" href="/assets/css/tailwind.min.css?v=3.0" as="style">
    <link rel="preload" href="/assets/css/styles.css?v=3.1" as="style">

    <!-- Inter + Playfair Display Fonts - loaded asynchronously to avoid render blocking -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@1,400;1,500;1,600;1,700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@1,400;1,500;1,600;1,700&display=swap" rel="stylesheet"></noscript>

    <!-- Tailwind CSS (local build - no render-blocking JS) -->
    <link rel="stylesheet" href="/assets/css/tailwind.min.css?v=3.2">

    <?php if (!isset($skipMapCSS) || !$skipMapCSS): ?>
    <!-- MapLibre GL CSS - loaded asynchronously to avoid render blocking -->
    <link rel="preload"
          href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css"
          as="style"
          onload="this.onload=null;this.rel='stylesheet'"
          integrity="sha384-p5cy4wHtKSqjnLUNjQ+8ffCwUp0vlLS+6lg1lc3qqXax2E1EmVCMCAimU+R0MOZH"
          crossorigin="anonymous">
    <noscript><link href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css" rel="stylesheet"></noscript>
    <?php endif; ?>

    <!-- Analytics (Umami) -->
    <script defer src="https://cloud.umami.is/script.js" data-website-id="df9e4019-b262-4db8-b267-a64a12aacf71"></script>

    <!-- Lucide Icons (pinned version, deferred for performance) -->
    <script defer
            src="https://unpkg.com/lucide@0.294.0/dist/umd/lucide.min.js"
            integrity="sha384-43WP8IQ+5H0ncT+LNM4dZnu+hPINYmeOuNMhTvHfszzXdFjBEji77gkq7TyjQl/U"
            crossorigin="anonymous"
            onload="window.lucideLoaded=true;if(typeof lucide!=='undefined')lucide.createIcons()"></script>

    <!-- Custom styles -->
    <link rel="stylesheet" href="/assets/css/styles.css?v=3.3">

    <!-- Deferred scripts (non-blocking) -->
    <script defer
            src="https://unpkg.com/htmx.org@1.9.10/dist/htmx.min.js"
            integrity="sha384-D1Kt99CQMDuVetoL1lrYwg5t+9QdHe7NLX/SoJYkXDFfX37iInKRy5xLSi8nO7UC"
            crossorigin="anonymous"></script>

    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body class="min-h-screen flex flex-col font-sans bg-brand-darker text-brand-text">
    <!-- Skip Links for Accessibility -->
    <a href="#main-content" class="skip-link sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:bg-cyan-500 focus:text-white focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-lg focus:outline-none">
        Skip to main content
    </a>
    <a href="#beach-grid" class="skip-link sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-48 focus:z-50 focus:bg-cyan-500 focus:text-white focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-lg focus:outline-none">
        Skip to beaches
    </a>

    <!-- Navigation - Dark Glassmorphism -->
    <nav id="main-nav" class="fixed top-0 w-full z-50 px-4 sm:px-6 py-4 transition-all duration-300" role="navigation" aria-label="Main navigation">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <!-- Logo with rotating sun -->
            <a href="/" class="flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-brand-yellow focus:ring-offset-2 focus:ring-offset-brand-darker rounded-lg p-1" aria-label="<?= h($appName) ?> - Home">
                <i data-lucide="sun" class="w-7 h-7 text-brand-yellow hover-spin transition-all" aria-hidden="true"></i>
                <span class="text-xl font-bold text-white"><?= h($appName) ?></span>
            </a>

            <!-- Center Navigation Pill (Desktop) -->
            <div class="hidden md:flex items-center bg-brand-darker/50 backdrop-blur-md px-4 py-2 rounded-full border border-white/10" role="menubar">
                <!-- Beaches Dropdown -->
                <div class="relative" id="beaches-dropdown">
                    <button type="button"
                            onclick="toggleBeachesDropdown()"
                            class="flex items-center gap-1 text-sm text-white/80 hover:text-brand-yellow px-4 py-1 transition-colors"
                            role="menuitem"
                            aria-expanded="false"
                            aria-haspopup="true">
                        <span>Beaches</span>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                    </button>
                    <div id="beaches-dropdown-menu" class="hidden absolute left-0 top-full mt-3 w-56 bg-brand-dark/95 backdrop-blur-md rounded-xl shadow-glass border border-white/10 py-2 z-50">
                        <div class="px-3 py-2 text-xs text-white/40 uppercase tracking-wider">Find by Activity</div>
                        <a href="/?tags[]=surfing#beaches" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:text-brand-yellow hover:bg-white/5 transition-colors">
                            <span class="text-lg">🏄‍♂️</span>
                            <span>Surfing</span>
                        </a>
                        <a href="/?tags[]=snorkeling#beaches" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:text-brand-yellow hover:bg-white/5 transition-colors">
                            <span class="text-lg">🤿</span>
                            <span>Snorkeling</span>
                        </a>
                        <a href="/?tags[]=family-friendly#beaches" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:text-brand-yellow hover:bg-white/5 transition-colors">
                            <span class="text-lg">👨‍👩‍👧</span>
                            <span>Family Friendly</span>
                        </a>
                        <a href="/?tags[]=secluded#beaches" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:text-brand-yellow hover:bg-white/5 transition-colors">
                            <span class="text-lg">🌴</span>
                            <span>Secluded</span>
                        </a>
                        <a href="/?tags[]=swimming#beaches" class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:text-brand-yellow hover:bg-white/5 transition-colors">
                            <span class="text-lg">🏊</span>
                            <span>Swimming</span>
                        </a>
                        <div class="border-t border-white/10 mt-2 pt-2">
                            <a href="/#beaches" class="flex items-center gap-3 px-4 py-2.5 text-sm text-brand-yellow hover:bg-white/5 transition-colors">
                                <i data-lucide="compass" class="w-4 h-4"></i>
                                <span>View All Beaches</span>
                            </a>
                        </div>
                    </div>
                </div>

                <a href="/quiz.php" class="text-sm text-white/80 hover:text-brand-yellow px-4 py-1 transition-colors" role="menuitem">Quiz</a>
                <a href="/?view=map" class="text-sm text-white/80 hover:text-brand-yellow px-4 py-1 transition-colors" role="menuitem">Map</a>
            </div>

            <!-- Right Side - Auth & Language -->
            <div class="hidden md:flex items-center gap-4">
                <!-- Language Switcher -->
                <div class="relative" id="lang-dropdown">
                    <button type="button"
                            onclick="toggleLangDropdown()"
                            class="flex items-center gap-1 px-2 py-1.5 text-sm text-white/70 hover:text-white rounded-lg transition-colors"
                            aria-label="<?= __('nav.language') ?>"
                            aria-expanded="false"
                            aria-haspopup="true">
                        <span><?= getLanguageFlag($currentLang) ?></span>
                        <span class="hidden sm:inline"><?= strtoupper($currentLang) ?></span>
                        <i data-lucide="chevron-down" class="w-3 h-3"></i>
                    </button>
                    <div id="lang-dropdown-menu" class="hidden absolute right-0 mt-1 w-32 bg-brand-dark/95 backdrop-blur-md rounded-lg shadow-glass border border-white/10 py-1 z-50">
                        <button type="button" onclick="setLanguage('en')" class="flex items-center gap-2 w-full px-3 py-2 text-sm text-left hover:bg-white/10 <?= $currentLang === 'en' ? 'text-brand-yellow' : 'text-white/80' ?>">
                            <span>🇺🇸</span> English
                        </button>
                        <button type="button" onclick="setLanguage('es')" class="flex items-center gap-2 w-full px-3 py-2 text-sm text-left hover:bg-white/10 <?= $currentLang === 'es' ? 'text-brand-yellow' : 'text-white/80' ?>">
                            <span>🇵🇷</span> Español
                        </button>
                    </div>
                </div>

                <?php if ($user): ?>
                    <div class="flex items-center gap-3">
                        <a href="/profile.php?tab=favorites" class="text-white/70 hover:text-brand-yellow transition-colors">
                            <i data-lucide="heart" class="w-5 h-5"></i>
                        </a>
                        <a href="/profile.php" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
                            <?php if (!empty($user['avatar_url'])): ?>
                            <img src="<?= h($user['avatar_url']) ?>" alt="" class="w-8 h-8 rounded-full border border-white/20">
                            <?php else: ?>
                            <div class="w-8 h-8 rounded-full bg-brand-yellow/20 flex items-center justify-center text-brand-yellow font-medium text-sm border border-brand-yellow/30">
                                <?= strtoupper(substr($user['name'] ?? $user['email'], 0, 1)) ?>
                            </div>
                            <?php endif; ?>
                        </a>
                        <a href="/logout.php" class="text-sm text-white/60 hover:text-white transition-colors">Logout</a>
                    </div>
                <?php else: ?>
                    <a href="/login.php" class="bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-5 py-2 rounded-full text-sm font-semibold transition-colors">
                        Sign In
                    </a>
                <?php endif; ?>
            </div>

            <!-- Mobile menu button -->
            <div class="flex items-center gap-2 md:hidden">
                <button type="button"
                        id="mobile-menu-button"
                        onclick="toggleMobileMenu()"
                        class="p-2 rounded-lg text-white/80 hover:text-white focus:outline-none focus:ring-2 focus:ring-brand-yellow"
                        aria-expanded="false"
                        aria-controls="mobile-menu"
                        aria-label="Open main menu">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="mobile-menu" class="hidden md:hidden mt-4 bg-brand-dark/95 backdrop-blur-md rounded-2xl border border-white/10 overflow-hidden" role="menu" aria-labelledby="mobile-menu-button">
            <div class="px-4 py-4 space-y-1">
                <!-- Beaches Section -->
                <div class="text-xs text-white/40 uppercase tracking-wider px-3 pt-2 pb-1">Find Beaches</div>
                <a href="/?tags[]=surfing#beaches" class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-2.5 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                    <span class="text-lg">🏄‍♂️</span>
                    <span>Surfing</span>
                </a>
                <a href="/?tags[]=snorkeling#beaches" class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-2.5 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                    <span class="text-lg">🤿</span>
                    <span>Snorkeling</span>
                </a>
                <a href="/?tags[]=family-friendly#beaches" class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-2.5 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                    <span class="text-lg">👨‍👩‍👧</span>
                    <span>Family Friendly</span>
                </a>
                <a href="/?tags[]=secluded#beaches" class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-2.5 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                    <span class="text-lg">🌴</span>
                    <span>Secluded</span>
                </a>
                <a href="/#beaches" class="flex items-center gap-3 text-brand-yellow py-2.5 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                    <i data-lucide="compass" class="w-5 h-5" aria-hidden="true"></i>
                    <span>All Beaches</span>
                </a>

                <!-- Tools Section -->
                <div class="border-t border-white/10 mt-3 pt-3">
                    <div class="text-xs text-white/40 uppercase tracking-wider px-3 pt-1 pb-1">Tools</div>
                    <a href="/quiz.php" class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-2.5 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                        <i data-lucide="sparkles" class="w-5 h-5" aria-hidden="true"></i>
                        <span>Find My Beach Quiz</span>
                    </a>
                    <a href="/?view=map" class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-2.5 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                        <i data-lucide="map" class="w-5 h-5" aria-hidden="true"></i>
                        <span>Map View</span>
                    </a>
                </div>
                <?php if ($user): ?>
                    <a href="/profile.php" class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-3 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                        <i data-lucide="user" class="w-5 h-5"></i>
                        <span>My Profile</span>
                    </a>
                    <a href="/profile.php?tab=favorites" class="flex items-center gap-3 text-white/80 hover:text-brand-yellow py-3 px-3 rounded-lg hover:bg-white/5 transition-colors" role="menuitem">
                        <i data-lucide="heart" class="w-5 h-5 text-red-400 fill-red-400"></i>
                        <span>Favorites</span>
                    </a>
                    <div class="pt-3 mt-3 border-t border-white/10">
                        <div class="flex items-center gap-3 py-2 px-3">
                            <?php if (!empty($user['avatar_url'])): ?>
                            <img src="<?= h($user['avatar_url']) ?>" alt="" class="w-8 h-8 rounded-full border border-white/20">
                            <?php else: ?>
                            <div class="w-8 h-8 rounded-full bg-brand-yellow/20 flex items-center justify-center text-brand-yellow font-medium text-sm">
                                <?= strtoupper(substr($user['name'] ?? $user['email'], 0, 1)) ?>
                            </div>
                            <?php endif; ?>
                            <span class="text-sm text-white/70"><?= h($user['name'] ?? 'User') ?></span>
                        </div>
                        <a href="/logout.php" class="block text-red-400 hover:text-red-300 py-2 px-3">Logout</a>
                    </div>
                <?php else: ?>
                    <a href="/login.php" class="block bg-brand-yellow text-brand-darker text-center py-3 rounded-lg mt-3 font-semibold">Sign In</a>
                <?php endif; ?>

                <!-- Mobile Language Switcher -->
                <div class="pt-3 mt-3 border-t border-white/10">
                    <label class="block text-xs text-white/50 uppercase tracking-wide mb-2 px-3">Language</label>
                    <div class="flex gap-2 px-3">
                        <button type="button" onclick="setLanguage('en')" class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-medium transition-colors <?= $currentLang === 'en' ? 'bg-brand-yellow text-brand-darker' : 'bg-white/10 text-white/80 hover:bg-white/20' ?>">
                            <span>🇺🇸</span> English
                        </button>
                        <button type="button" onclick="setLanguage('es')" class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-medium transition-colors <?= $currentLang === 'es' ? 'bg-brand-yellow text-brand-darker' : 'bg-white/10 text-white/80 hover:bg-white/20' ?>">
                            <span>🇵🇷</span> Español
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main id="main-content" class="flex-1" role="main" aria-label="Page content">

    <script>
    // Mobile menu toggle with accessibility
    function toggleMobileMenu() {
        const menu = document.getElementById('mobile-menu');
        const button = document.getElementById('mobile-menu-button');
        const isExpanded = button.getAttribute('aria-expanded') === 'true';

        menu.classList.toggle('hidden');
        button.setAttribute('aria-expanded', !isExpanded);
        button.setAttribute('aria-label', isExpanded ? 'Open main menu' : 'Close main menu');
    }

    // Language dropdown
    function toggleLangDropdown() {
        const menu = document.getElementById('lang-dropdown-menu');
        menu.classList.toggle('hidden');
        // Close beaches dropdown if open
        const beachesMenu = document.getElementById('beaches-dropdown-menu');
        if (beachesMenu) beachesMenu.classList.add('hidden');
    }

    // Beaches dropdown
    function toggleBeachesDropdown() {
        const menu = document.getElementById('beaches-dropdown-menu');
        menu.classList.toggle('hidden');
        // Close language dropdown if open
        const langMenu = document.getElementById('lang-dropdown-menu');
        if (langMenu) langMenu.classList.add('hidden');
    }

    function setLanguage(lang) {
        fetch('/api/set-language.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'lang=' + encodeURIComponent(lang)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        })
        .catch(err => console.error('Language switch failed:', err));
    }

    // Close dropdowns on outside click
    document.addEventListener('click', function(e) {
        const langDropdown = document.getElementById('lang-dropdown');
        const langMenu = document.getElementById('lang-dropdown-menu');
        if (langDropdown && !langDropdown.contains(e.target) && langMenu) {
            langMenu.classList.add('hidden');
        }
        const beachesDropdown = document.getElementById('beaches-dropdown');
        const beachesMenu = document.getElementById('beaches-dropdown-menu');
        if (beachesDropdown && !beachesDropdown.contains(e.target) && beachesMenu) {
            beachesMenu.classList.add('hidden');
        }
    });

    // Close mobile menu on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const menu = document.getElementById('mobile-menu');
            const button = document.getElementById('mobile-menu-button');
            if (menu && !menu.classList.contains('hidden')) {
                menu.classList.add('hidden');
                button.setAttribute('aria-expanded', 'false');
                button.setAttribute('aria-label', 'Open main menu');
                button.focus();
            }
            // Also close language dropdown
            const langMenu = document.getElementById('lang-dropdown-menu');
            if (langMenu) langMenu.classList.add('hidden');
            // Also close beaches dropdown
            const beachesMenu = document.getElementById('beaches-dropdown-menu');
            if (beachesMenu) beachesMenu.classList.add('hidden');
        }
    });
    </script>
