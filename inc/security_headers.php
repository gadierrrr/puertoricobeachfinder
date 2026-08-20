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
// *.googleusercontent.com serves the Google Places beach photos the /api/
// beach-photo-media.php proxy 302s to (host prefix varies: lh3, lh5, ...).
$imgSources = ["'self'", 'data:', 'blob:', 'https://media.tacdn.com', 'https://*.tripadvisor.com', 'https://*.googleusercontent.com', 'https://*.basemaps.cartocdn.com', 'https://a.basemaps.cartocdn.com', 'https://b.basemaps.cartocdn.com', 'https://c.basemaps.cartocdn.com', 'https://d.basemaps.cartocdn.com'];
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

/**
 * Decide whether this HTML response may be stored in a shared (edge) cache.
 *
 * Cacheable means: the exact bytes we are about to emit are correct for every
 * anonymous visitor asking for this URL. Anything visitor-specific — a session,
 * a cookie we are about to set, a language the URL does not pin down — must
 * fail this check, because Cloudflare keys on the URL alone.
 */
function pageIsEdgeCacheable(): bool
{
    // Only idempotent reads. POSTs mutate and must never be replayed from cache.
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        return false;
    }

    // Signed-in visitors get personalized HTML (favorites, review/check-in state).
    // Anonymous visitors never receive this cookie — sessions only start when it
    // is already present (components/header.php) — so its absence is a reliable
    // "this response is not personalized" signal.
    if (isset($_COOKIE['BEACH_FINDER_SESSION'])) {
        return false;
    }

    // Only 200s. quiz-results.php sets its 404 before including the header, and
    // this guard keeps that (and anything like it) out of the shared cache.
    if (http_response_code() !== 200) {
        return false;
    }

    // ?ref= captures an invite into a Set-Cookie; ?rdedit=1 is the admin preview.
    // Neither may be stored and replayed for other visitors.
    if (isset($_GET['ref']) || isset($_GET['rdedit'])) {
        return false;
    }

    $path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

    // Account, auth and admin surfaces are per-visitor by definition. Several of
    // these also call session_start() unconditionally, which emits a Set-Cookie.
    // `/advertise` mints a per-render, time-limited lead-form token
    // (advertisingLeadFormToken). A shared cache would hand the same token to
    // everyone and, once the entry went stale, keep serving one that had already
    // expired — silently failing every lead submission. Any page that embeds a
    // per-render token belongs on this list.
    $privatePrefixes = [
        '/admin', '/api/', '/auth/', '/login', '/logout', '/profile',
        '/favorites', '/lists', '/list', '/onboarding', '/verify',
        '/quiz-results', '/go', '/ad-out', '/local-out', '/advertise',
    ];
    foreach ($privatePrefixes as $prefix) {
        if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
            return false;
        }
    }

    // The rendered language must be decidable from the URL alone. getCurrentLanguage()
    // falls back to the session/`lang` cookie when the path does not pin a locale, so
    // caching such a URL could serve one visitor's language to everyone. Registered
    // routes (including the unprefixed English ones) resolve here; anything else
    // stays uncached rather than risk it.
    require_once __DIR__ . '/locale_routes.php';
    if (resolveLocaleFromPath($path) === null) {
        return false;
    }

    return true;
}

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
    } elseif (pageIsEdgeCacheable()) {
        // Anonymous, locale-pinned HTML: let Cloudflare serve it from the edge.
        //
        // Browsers still revalidate every navigation (max-age=0, must-revalidate),
        // so a signed-in user never sees a stale shell; the win is that the
        // revalidation terminates at the edge instead of at PHP on a 2-core box.
        // CDN-Cache-Control governs Cloudflare specifically and takes precedence
        // there; s-maxage is the fallback for any shared cache that ignores it.
        //
        // NOTE: the CSP nonce below is per-response, so a cached page pins one
        // nonce for the life of the entry. That is a deliberate, bounded tradeoff
        // taken when edge caching was enabled — keep the TTL short.
        header('Cache-Control: public, max-age=0, s-maxage=300, must-revalidate');
        header('CDN-Cache-Control: public, s-maxage=300, stale-while-revalidate=86400');
        // Locale comes from the path and Accept-Language changes nothing, so the
        // only dimension worth varying on is the encoding.
        header('Vary: Accept-Encoding');
    } else {
        // Personalized, non-GET, or a URL whose language the path does not pin.
        header('Cache-Control: private, no-cache, max-age=0, must-revalidate');
        header('Vary: Cookie, Accept-Language');
    }
}
