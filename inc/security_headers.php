<?php
/**
 * Security Headers
 * Include at the top of all public-facing pages
 */

// Generate a per-request CSP nonce for inline <script> blocks.
// This replaces 'unsafe-inline' in script-src, meaning only scripts
// with nonce="<this value>" will execute.
if (!defined('CSP_NONCE')) {
    define('CSP_NONCE', base64_encode(random_bytes(16)));
}

/**
 * Return the nonce value for embedding in <script nonce="...">.
 */
function cspNonce(): string {
    return CSP_NONCE;
}

/**
 * Return a full nonce="..." attribute string for use in PHP templates.
 */
function cspNonceAttr(): string {
    return 'nonce="' . CSP_NONCE . '"';
}

if (!function_exists('cspHostSourceFromUrl')) {
    function cspHostSourceFromUrl(string $url): ?string {
        $parsed = parse_url($url);
        if (!is_array($parsed)) {
            return null;
        }

        $host = isset($parsed['host']) ? trim((string) $parsed['host']) : '';
        if ($host === '') {
            return null;
        }

        $port = isset($parsed['port']) ? (int) $parsed['port'] : null;
        if ($port !== null && $port > 0) {
            return $host . ':' . $port;
        }

        return $host;
    }
}

// Security Headers
header('X-Content-Type-Options: nosniff');
// The admin homepage-design editor previews the homepage in a same-origin
// iframe (?rdedit=1). SAMEORIGIN still blocks all cross-site framing.
if (isset($_GET['rdedit']) && $_GET['rdedit'] === '1') {
    header('X-Frame-Options: SAMEORIGIN');
} else {
    header('X-Frame-Options: DENY');
}
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Content Security Policy.
// Note: 'unsafe-inline' is still required for style-src (Tailwind + inline styles).
// 'unsafe-eval' removed from script-src — no runtime eval() is used.
// If a JS library requires eval, add a specific hash/nonce instead.
$scriptSources = ["'self'", 't.puertoricobeachfinder.com', "'nonce-" . CSP_NONCE . "'", "'strict-dynamic'", 'cdn.tailwindcss.com', 'unpkg.com', 'cdn.jsdelivr.net'];
$styleSources = ["'self'", "'unsafe-inline'", 'cdn.tailwindcss.com', 'unpkg.com', 'cdn.jsdelivr.net', 'fonts.googleapis.com'];
// *.tripadvisor.com covers Viator API-hydrated product images, which move
// between TripAdvisor CDN subdomains (media-cdn, hare-media-cdn, ...).
$imgSources = ["'self'", 'data:', 'blob:', 'https://media.tacdn.com', 'https://*.tripadvisor.com', 'https://*.basemaps.cartocdn.com', 'https://a.basemaps.cartocdn.com', 'https://b.basemaps.cartocdn.com', 'https://c.basemaps.cartocdn.com', 'https://d.basemaps.cartocdn.com'];
$fontSources = ["'self'", 'data:', 'fonts.gstatic.com'];
$connectSources = ["'self'", 't.puertoricobeachfinder.com', 'https://basemaps.cartocdn.com', 'https://*.basemaps.cartocdn.com', 'unpkg.com', 'cdn.jsdelivr.net'];
$workerSources = ["'self'", 'blob:'];
// 'self' for the admin homepage-design editor preview iframe; Google hosts for
// the beach-page satellite embed (maps.google.com redirects to www.google.com,
// and CSP re-checks the post-redirect URL).
$frameSources = ["'self'", 'https://maps.google.com', 'https://www.google.com'];

// Google Analytics 4 (gtag.js) — only whitelist Google's hosts when a valid
// GA4 Measurement ID (G-XXXXXXXXXX) is configured.
$gaMeasurementId = function_exists('env') ? (string) (env('GA_MEASUREMENT_ID', '') ?? '') : '';
$gaEnabled = (bool) preg_match('/^G-[A-Z0-9]+$/i', $gaMeasurementId);

if ($gaEnabled) {
    // gtag.js loader (host-source kept for browsers without 'strict-dynamic').
    $scriptSources[] = 'www.googletagmanager.com';
    // Measurement beacons + tag config fetches.
    $connectSources[] = 'www.googletagmanager.com';
    $connectSources[] = 'www.google-analytics.com';
    $connectSources[] = '*.google-analytics.com';
    $connectSources[] = '*.analytics.google.com';
    // With Google Signals enabled, gtag beacons the APEX analytics.google.com
    // (a *.analytics.google.com wildcard does NOT match the apex) and pings
    // www.google.com / stats.g.doubleclick.net for ads signals.
    $connectSources[] = 'analytics.google.com';
    $connectSources[] = 'https://www.google.com';
    $connectSources[] = 'stats.g.doubleclick.net';
    // Legacy/pixel image beacons.
    $imgSources[] = 'www.googletagmanager.com';
    $imgSources[] = 'www.google-analytics.com';
    $imgSources[] = '*.google-analytics.com';
    $imgSources[] = 'analytics.google.com';
    $imgSources[] = 'https://www.google.com';
    $imgSources[] = 'stats.g.doubleclick.net';
}

$scriptSources = array_values(array_unique($scriptSources));
$styleSources = array_values(array_unique($styleSources));
$imgSources = array_values(array_unique($imgSources));
$fontSources = array_values(array_unique($fontSources));
$connectSources = array_values(array_unique($connectSources));
$workerSources = array_values(array_unique($workerSources));
$frameSources = array_values(array_unique($frameSources));

$csp = "default-src 'self'; "
    . 'script-src ' . implode(' ', $scriptSources) . '; '
    . 'style-src ' . implode(' ', $styleSources) . '; '
    . 'img-src ' . implode(' ', $imgSources) . '; '
    . 'font-src ' . implode(' ', $fontSources) . '; '
    . 'connect-src ' . implode(' ', $connectSources) . '; '
    . 'worker-src ' . implode(' ', $workerSources) . '; '
    . 'frame-src ' . implode(' ', $frameSources) . '; '
    . 'upgrade-insecure-requests;';

header('Content-Security-Policy: ' . $csp);

// Performance Headers
header('X-DNS-Prefetch-Control: on');

// Cache HTML pages for 5 minutes (browser) with stale-while-revalidate
if (!headers_sent()) {
    $isApiRequest = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false;
    $isAuthPage = strpos($_SERVER['REQUEST_URI'] ?? '', '/login') !== false ||
                  strpos($_SERVER['REQUEST_URI'] ?? '', '/logout') !== false ||
                  strpos($_SERVER['REQUEST_URI'] ?? '', '/auth/') !== false;

    if ($isApiRequest) {
        // API responses: short cache, allow revalidation
        header('Cache-Control: public, max-age=60, stale-while-revalidate=300');
    } elseif ($isAuthPage) {
        // Auth pages: no cache
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Vary: Cookie, Accept-Language');
    } else {
        // Locale-aware HTML should not be cached publicly.
        header('Cache-Control: private, no-cache, max-age=0, must-revalidate');
        header('Vary: Cookie, Accept-Language');
    }
}
