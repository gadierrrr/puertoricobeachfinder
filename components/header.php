<?php
/**
 * Site Header Component
 * Include at the top of all pages
 */

require_once __DIR__ . '/../inc/session.php';
if (isset($_COOKIE['BEACH_FINDER_SESSION']) && session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('');   // Prevent PHP from emitting Pragma/Expires
    session_start();
}
require_once __DIR__ . '/../inc/security_headers.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/i18n.php';
require_once __DIR__ . '/../inc/locale_routes.php';
require_once __DIR__ . '/../inc/invite.php';

// Capture an invite (?ref=CODE) into a cookie before any output, on EVERY page
// (referral loop). No-op without ?ref; skips signed-in users. Lives here — not just
// in index.php — so referral links that land on beach pages, guides, etc. still attribute.
inviteCaptureRefFromRequest();

$user = currentUser();
$appName = $_ENV['APP_NAME'] ?? 'Beach Finder';
$appUrl = getPublicBaseUrl();
$currentLang = getCurrentLanguage();
$allowedBodyVariants = ['default', 'collection-light', 'collection-dark'];
$requestedBodyVariant = isset($bodyVariant) ? (string) $bodyVariant : 'default';
$bodyVariant = in_array($requestedBodyVariant, $allowedBodyVariants, true) ? $requestedBodyVariant : 'default';
$bodyClasses = trim(($bodyClasses ?? '') . ' min-h-screen flex flex-col font-sans');
if ($bodyVariant === 'collection-light') {
    $bodyClasses .= ' collection-light bg-sand-50 text-warm-900';
    $htmlTheme = 'light';
} elseif ($bodyVariant === 'collection-dark') {
    $bodyClasses .= ' collection-dark bg-ocean-900 text-white';
    $htmlTheme = 'dark';
} else {
    $bodyClasses .= ' bg-sand-50 text-warm-900';
    $htmlTheme = 'light';
}
?>
<!DOCTYPE html>
<html lang="<?= getHtmlLang() ?>" data-theme="<?= h($htmlTheme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php
        if (isset($pageTitle)) {
            // A hand-written SEO title override is rendered verbatim; otherwise
            // append the " | $appName" brand suffix as before.
            echo h($pageTitle) . (empty($pageTitleNoBrandSuffix) ? ' | ' . h($appName) : '');
        } else {
            echo h($appName);
        }
    ?></title>

    <?php if (isset($pageDescription)): ?>
    <meta name="description" content="<?= h($pageDescription) ?>">
    <?php endif; ?>

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#105258">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Beach Finder">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/icons/icon-180x180.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/assets/icons/icon-152x152.png">
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="alternate" type="application/rss+xml" title="Puerto Rico Beach Finder" href="/feed.xml">
    <link rel="icon" href="/assets/icons/icon-96x96.png" sizes="96x96" type="image/png">

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
    <meta property="og:image" content="<?= h($appUrl) ?>/images/og-default.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
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
    <meta name="twitter:image" content="<?= h($appUrl) ?>/images/og-default.jpg">
    <?php endif; ?>

    <!-- Canonical URL -->
    <?php
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $canonicalPath = '';

    $normalizeCanonicalPath = static function (string $path): string {
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . ltrim($path, '/');
        }

        if ($path === '/index.php') {
            return '/';
        }

        if ($path === '/sitemap.php') {
            return '/sitemap.xml';
        }

        // Canonicalize extensionless public pages (keep this as an allowlist to avoid surprises).
        $extensionlessPages = [
            '/best-beaches.php',
            '/best-beaches-san-juan.php',
            '/best-snorkeling-beaches.php',
            '/best-surfing-beaches.php',
            '/best-family-beaches.php',
            '/beaches-near-san-juan.php',
            '/beaches-near-san-juan-airport.php',
            '/hidden-beaches-puerto-rico.php',
            '/best-diving-beaches.php',
            '/best-fishing-beaches.php',
            '/best-accessible-beaches.php',
            '/best-scenic-beaches.php',
            '/best-swimming-beaches.php',
            '/best-camping-beaches.php',
            '/best-calm-water-beaches.php',
            '/best-secluded-beaches.php',
            '/best-beaches-cabo-rojo.php',
            '/best-beaches-rincon.php',
            '/best-beaches-isabela.php',
            '/best-beaches-fajardo.php',
            '/best-beaches-vieques.php',
            '/best-beaches-culebra.php',
            '/best-beaches-luquillo.php',
            '/quiz.php',
            '/quiz-results.php',
            '/compare.php',
            '/offline.php',
            '/login.php',
            '/logout.php',
            '/verify.php',
            '/favorites.php',
            '/profile.php',
            '/onboarding.php',
            '/terms.php',
            '/privacy.php',
        ];

        if (in_array($path, $extensionlessPages, true)) {
            return substr($path, 0, -4);
        }

        if ($path === '/guides/index.php') {
            return '/guides/';
        }

        if (str_starts_with($path, '/guides/') && str_ends_with($path, '.php')) {
            return substr($path, 0, -4);
        }

        return $path;
    };

    if (isset($canonicalUrl)) {
        $providedCanonical = (string) $canonicalUrl;
        if (preg_match('#^https?://#i', $providedCanonical)) {
            $parsed = parse_url($providedCanonical);
            $canonicalPath = (string) ($parsed['path'] ?? '/');
        } else {
            $canonicalPath = $providedCanonical;
        }
    } elseif (isset($beach['slug'])) {
        $canonicalPath = '/beach/' . $beach['slug'];
    } elseif ($requestPath === '/' || $requestPath === '/index.php') {
        // Homepage and filtered views canonicalize to homepage.
        $canonicalPath = '/';
    } else {
        $canonicalPath = $requestPath;
    }

    $canonicalPath = $normalizeCanonicalPath($canonicalPath);
    $canonicalPath = localizePath($canonicalPath, $currentLang);
    $canonical = absoluteUrl($canonicalPath);
    $canonicalEn = absoluteUrl(localizePath($canonicalPath, 'en'));
    $canonicalEs = absoluteUrl(localizePath($canonicalPath, 'es'));
    // Only emit hreflang alternates for routes that genuinely have both an
    // English and Spanish version (skips indexable English-only pages like
    // unmapped guides, which would otherwise advertise a 404'ing /es alternate).
    $emitHreflang = isIndexableLocalePath($canonicalPath) && isLocalizedLocalePath($canonicalPath);

    // Normalize before checking indexability so /login and /login.php behave the same.
    $normalizedRequestPath = $normalizeCanonicalPath($requestPath);
    $requestIndexable = isIndexableLocalePath($normalizedRequestPath);
    $robots = $requestIndexable
        ? 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1'
        : 'noindex, nofollow, noarchive';
    if (isset($robotsOverride) && is_string($robotsOverride) && trim($robotsOverride) !== '') {
        $robots = trim($robotsOverride);
    }
    ?>
    <link rel="canonical" href="<?= h($canonical) ?>">
    <meta property="og:url" content="<?= h($canonical) ?>">
    <?php if ($emitHreflang): ?>
    <link rel="alternate" hreflang="en" href="<?= h($canonicalEn) ?>">
    <link rel="alternate" hreflang="es-PR" href="<?= h($canonicalEs) ?>">
    <link rel="alternate" hreflang="x-default" href="<?= h($canonicalEn) ?>">
    <?php endif; ?>

    <!-- Robots Meta Tags -->
    <meta name="robots" content="<?= h($robots) ?>">

    <?php
    $umamiEnabled = function_exists('envBool') ? envBool('UMAMI_ENABLED', false) : false;
    $umamiWebsiteId = function_exists('env') ? (string) (env('UMAMI_WEBSITE_ID') ?? '') : '';
    $umamiScriptUrl = function_exists('env') ? (string) (env('UMAMI_SCRIPT_URL', 'https://cloud.umami.is/script.js') ?? 'https://cloud.umami.is/script.js') : 'https://cloud.umami.is/script.js';
    $umamiDomains = function_exists('env') ? (string) (env('UMAMI_DOMAINS', '') ?? '') : '';
    ?>
    <?php if ($umamiEnabled && $umamiWebsiteId !== ''): ?>
    <script defer src="<?= h($umamiScriptUrl) ?>"
            data-website-id="<?= h($umamiWebsiteId) ?>"
            <?php if ($umamiDomains !== ''): ?>data-domains="<?= h($umamiDomains) ?>"<?php endif; ?>
            <?= cspNonceAttr() ?>></script>
    <?php endif; ?>

    <!-- PostHog Analytics (Session Replay + Error Tracking) -->
    <script <?= cspNonceAttr() ?>>
        !function(t,e){var o,n,p,r;e.__SV||(window.posthog=e,e._i=[],e.init=function(i,s,a){function g(t,e){var o=e.split(".");2==o.length&&(t=t[o[0]],e=o[1]),t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}}(p=t.createElement("script")).type="text/javascript",p.crossOrigin="anonymous",p.async=!0,p.src=s.api_host.replace(".i.posthog.com","-assets.i.posthog.com")+"/static/array.js",(r=t.getElementsByTagName("script")[0]).parentNode.insertBefore(p,r);var u=e;for(void 0!==a?u=e[a]=[]:a="posthog",u.people=u.people||[],u.toString=function(t){var e="posthog";return"posthog"!==a&&(e+="."+a),t||(e+=" (stub)"),e},u.people.toString=function(){return u.toString(1)+".people (stub)"},o="init capture register register_once register_for_session unregister unregister_for_session getFeatureFlag getFeatureFlagPayload isFeatureEnabled reloadFeatureFlags updateEarlyAccessFeatureEnrollment getEarlyAccessFeatures on onFeatureFlags onSessionId getSurveys getActiveMatchingSurveys renderSurvey canRenderSurvey getNextSurveyStep identify setPersonProperties group resetGroups setPersonPropertiesForFlags resetPersonPropertiesForFlags setGroupPropertiesForFlags resetGroupPropertiesForFlags reset get_distinct_id getGroups get_session_id get_session_replay_url alias set_config startSessionRecording stopSessionRecording sessionRecordingStarted captureException loadToolbar get_property getSessionProperty createPersonProfile opt_in_capturing opt_out_capturing has_opted_in_capturing has_opted_out_capturing clear_opt_in_out_capturing debug getPageViewId".split(" "),n=0;n<o.length;n++)g(u,o[n]);e._i.push([i,s,a])},e.__SV=1)}(document,window.posthog||[]);
        posthog.init('phc_vcMhXo3ENA9N3W7hU7sPZvvvMbtojcG38uk4rMGPPwmD', {
            api_host: 'https://t.puertoricobeachfinder.com',
            person_profiles: 'identified_only',
            autocapture: true,
            capture_pageview: true,
            capture_pageleave: true,
            session_recording: {
                maskAllInputs: true,
                maskTextSelector: '[data-ph-mask]'
            }
        })
    </script>

    <?php
    $gaMeasurementId = function_exists('env') ? (string) (env('GA_MEASUREMENT_ID', '') ?? '') : '';
    $gaEnabled = (bool) preg_match('/^G-[A-Z0-9]+$/i', $gaMeasurementId);
    ?>
    <?php if ($gaEnabled): ?>
    <!-- Google Analytics 4 (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= h($gaMeasurementId) ?>" <?= cspNonceAttr() ?>></script>
    <script <?= cspNonceAttr() ?>>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?= h($gaMeasurementId) ?>');
    </script>
    <?php endif; ?>

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
    <link rel="preload" href="/assets/css/tailwind.min.css?v=3.9" as="style">
    <link rel="preload" href="/assets/css/styles.css?v=4.7" as="style">

    <!-- DM Sans + DM Serif Display Fonts - loaded asynchronously to avoid render blocking -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Serif+Display:ital@0;1&display=swap" as="style" data-lazy-style>
    <noscript><link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet"></noscript>

    <!-- Tailwind CSS (local build - no render-blocking JS) -->
    <link rel="stylesheet" href="/assets/css/tailwind.min.css?v=3.9">

    <?php if (!isset($skipMapCSS) || !$skipMapCSS): ?>
    <!-- MapLibre GL CSS - loaded asynchronously to avoid render blocking -->
    <link rel="preload"
          href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css"
          as="style"
          data-lazy-style
          integrity="sha384-p5cy4wHtKSqjnLUNjQ+8ffCwUp0vlLS+6lg1lc3qqXax2E1EmVCMCAimU+R0MOZH"
          crossorigin="anonymous">
    <noscript><link href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css" rel="stylesheet"></noscript>
    <?php endif; ?>

    <!-- Lucide Icons (pinned version, deferred for performance) -->
    <script defer
            src="https://unpkg.com/lucide@0.294.0/dist/umd/lucide.min.js"
            integrity="sha384-43WP8IQ+5H0ncT+LNM4dZnu+hPINYmeOuNMhTvHfszzXdFjBEji77gkq7TyjQl/U"
            crossorigin="anonymous"
            <?= cspNonceAttr() ?>></script>

    <!-- CSP event delegation (must load before page scripts) -->
    <script src="/assets/js/csp-bindings.js" <?= cspNonceAttr() ?>></script>

    <!-- Custom styles -->
    <link rel="stylesheet" href="/assets/css/styles.css?v=4.7">

    <?php if (!empty($redesignLayout)): ?>
    <!-- Redesign v2 (tropical) fonts + standalone stylesheet.
         Gated on $redesignLayout (set by pages that render a redesign
         template), NOT on useRedesign(), so classic-markup pages never pay
         for fonts/CSS they don't use while the flag is on. -->
    <?php
    // Display font comes from the admin homepage-design settings; the editor
    // preview (?rdedit=1, admins only) loads every picker face so switching
    // is instant.
    require_once APP_ROOT . '/inc/settings.php';
    require_once APP_ROOT . '/inc/homepage_fonts.php';
    require_once APP_ROOT . '/inc/admin.php';
    $rdDesign = getHomepageDesign();
    $rdEditorMode = isset($_GET['rdedit']) && $_GET['rdedit'] === '1' && isAdmin();
    $rdFont = homepageFont($rdDesign['font']);
    ?>
    <link href="<?= h(redesignFontsUrl($rdDesign['font'], $rdEditorMode)) ?>" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/redesign.css?v=7">
    <style>.rd{--disp:<?= $rdFont['stack'] ?>}.rd-home .headline,.rd-home .dir-head h2{font-weight:<?= (int) $rdFont['weight'] ?>}</style>
    <?php endif; ?>

    <!-- Deferred scripts (non-blocking) -->
    <script defer
            src="https://unpkg.com/htmx.org@1.9.10/dist/htmx.min.js"
            integrity="sha384-D1Kt99CQMDuVetoL1lrYwg5t+9QdHe7NLX/SoJYkXDFfX37iInKRy5xLSi8nO7UC"
            crossorigin="anonymous"
            <?= cspNonceAttr() ?>></script>

    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body class="<?= h($bodyClasses) ?><?= (!empty($redesignLayout)) ? ' redesign' : '' ?>">
    <?php if (empty($redesignLayout)): ?>
    <?php include __DIR__ . '/nav.php'; ?>
    <?php else: ?>
    <?php include __DIR__ . '/redesign/nav.php'; ?>
    <?php endif; ?>

    <!-- Main Content -->
    <main id="main-content" class="flex-1" role="main" aria-label="Page content">
