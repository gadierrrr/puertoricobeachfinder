#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Crawl canonical public URLs in classic and redesign modes.
 *
 * Usage:
 *   php scripts/test-release-http.php --base-url=http://127.0.0.1:8082
 *   php scripts/test-release-http.php --base-url=https://staging.example.com --design=redesign
 *   php scripts/test-release-http.php --base-url=http://127.0.0.1:8082 --limit=80
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$options = getopt('', ['base-url:', 'design::', 'limit::', 'help']);
if (isset($options['help'])) {
    echo "Usage: php scripts/test-release-http.php --base-url=URL [--design=both|classic|redesign] [--limit=N]\n";
    exit(0);
}

$baseUrl = rtrim((string) ($options['base-url'] ?? ''), '/');
$design = strtolower((string) ($options['design'] ?? 'both'));
$limit = max(0, (int) ($options['limit'] ?? 0));

if ($baseUrl === '' || !filter_var($baseUrl, FILTER_VALIDATE_URL)) {
    fwrite(STDERR, "A valid --base-url is required.\n");
    exit(1);
}
if (!in_array($design, ['both', 'classic', 'redesign'], true)) {
    fwrite(STDERR, "--design must be both, classic, or redesign.\n");
    exit(1);
}

$criticalPaths = [
    '/',
    '/best-beaches',
    '/best-snorkeling-beaches',
    '/beaches-near-san-juan',
    '/beaches-in-san-juan',
    '/guides/',
    '/guides/beach-photography-tips',
    '/quiz',
    '/compare',
    '/login',
    '/privacy',
    '/terms',
    '/es',
    '/es/mejores-playas',
    '/es/guias/',
    '/es/quiz-playa',
];

$sitemap = httpGet($baseUrl . '/sitemap.xml');
if ($sitemap['status'] !== 200) {
    fwrite(STDERR, "Could not load sitemap.xml (HTTP {$sitemap['status']}).\n");
    exit(1);
}

preg_match_all('#<loc>(.*?)</loc>#s', $sitemap['body'], $matches);
$paths = $criticalPaths;
foreach ($matches[1] ?? [] as $location) {
    $path = (string) (parse_url(html_entity_decode(trim($location), ENT_QUOTES | ENT_XML1), PHP_URL_PATH) ?: '/');
    $paths[] = $path;
}
$paths = array_values(array_unique($paths));

if ($limit > 0) {
    $criticalCount = count($criticalPaths);
    $paths = array_values(array_unique(array_merge(
        $criticalPaths,
        array_slice($paths, $criticalCount, max(0, $limit - $criticalCount))
    )));
}

$designs = $design === 'both' ? ['classic', 'redesign'] : [$design];
$failures = [];
$checked = 0;
$requests = [];

foreach ($designs as $variant) {
    foreach ($paths as $path) {
        $separator = str_contains($path, '?') ? '&' : '?';
        $url = $baseUrl . $path . $separator . 'design=' . rawurlencode($variant);
        $requests[] = ['variant' => $variant, 'path' => $path, 'url' => $url];
    }
}

foreach (array_chunk($requests, 16) as $batch) {
    $responses = httpGetMany(array_column($batch, 'url'));
    foreach ($batch as $request) {
        $variant = $request['variant'];
        $path = $request['path'];
        $response = $responses[$request['url']] ?? ['status' => 0, 'body' => ''];
        ++$checked;

        if ($response['status'] !== 200) {
            $failures[] = "{$variant} {$path}: HTTP {$response['status']}";
            continue;
        }
        if ($response['body'] === '' || str_contains($response['body'], 'Application Error')) {
            $failures[] = "{$variant} {$path}: empty or application-error response";
            continue;
        }
        if (!str_contains(strtolower($response['body']), '<!doctype html>')) {
            $failures[] = "{$variant} {$path}: missing HTML document";
        }
    }

    if ($checked % 256 === 0) {
        echo "Checked {$checked}/" . count($requests) . " page(s)...\n";
    }
}

echo "Release HTTP smoke: {$checked} request(s), " . count($failures) . " failure(s).\n";
foreach ($failures as $failure) {
    echo " - {$failure}\n";
}

exit($failures === [] ? 0 : 1);

function httpGet(string $url): array
{
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'ignore_errors' => true,
            'follow_location' => 1,
            'max_redirects' => 5,
            'timeout' => 15,
            'header' => "User-Agent: beach-finder-release-smoke/1.0\r\nAccept: text/html,application/xhtml+xml\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $body = @file_get_contents($url, false, $context);
    $headers = $http_response_header ?? [];
    $status = 0;
    foreach ($headers as $header) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $match)) {
            $status = (int) $match[1];
        }
    }

    return ['status' => $status, 'body' => is_string($body) ? $body : ''];
}

function httpGetMany(array $urls): array
{
    if (!extension_loaded('curl')) {
        $responses = [];
        foreach ($urls as $url) {
            $responses[$url] = httpGet($url);
        }
        return $responses;
    }

    $multi = curl_multi_init();
    $handles = [];
    foreach ($urls as $url) {
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'beach-finder-release-smoke/1.0',
            CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        curl_multi_add_handle($multi, $handle);
        $handles[$url] = $handle;
    }

    do {
        $status = curl_multi_exec($multi, $running);
        if ($running > 0) {
            curl_multi_select($multi, 1.0);
        }
    } while ($running > 0 && $status === CURLM_OK);

    $responses = [];
    foreach ($handles as $url => $handle) {
        $body = curl_multi_getcontent($handle);
        $responses[$url] = [
            'status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE),
            'body' => is_string($body) ? $body : '',
        ];
        curl_multi_remove_handle($multi, $handle);
        curl_close($handle);
    }
    curl_multi_close($multi);

    return $responses;
}
