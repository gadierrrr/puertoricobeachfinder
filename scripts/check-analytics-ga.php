#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Guardrail check for GA4 (gtag.js) script injection in rendered HTML.
 *
 * Usage examples:
 *   php scripts/check-analytics-ga.php --url=http://127.0.0.1:8082/
 *   php scripts/check-analytics-ga.php --urls=http://127.0.0.1:8082/,http://127.0.0.1:8082/best-beaches
 *   php scripts/check-analytics-ga.php --url=https://www.example.com --expect-measurement-id=G-XXXXXXXXXX
 */

function usage(): void {
    fwrite(STDOUT, "Usage: php scripts/check-analytics-ga.php [--url=<url>] [--urls=<comma-separated-urls>] [--expect-measurement-id=<G-...>] [--timeout=<seconds>] [--require-analytics-wrapper] [--json]\n");
}

function parseStatusCode(array $headers): int {
    if (empty($headers)) {
        return 0;
    }
    if (preg_match('/\s(\d{3})(?:\s|$)/', (string) $headers[0], $matches) !== 1) {
        return 0;
    }
    return (int) $matches[1];
}

/**
 * @return array{status_code:int,error:string,body:string}
 */
function fetchHtml(string $url, int $timeout): array {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => $timeout,
            'ignore_errors' => true,
            'header' => "User-Agent: beach-finder-ci-analytics-check/1.0\r\nAccept: text/html\r\n",
        ],
    ]);

    $body = @file_get_contents($url, false, $context);
    $headers = $http_response_header ?? [];
    $statusCode = parseStatusCode($headers);
    if (!is_string($body)) {
        $error = (string) (error_get_last()['message'] ?? 'Failed to fetch URL');
        return ['status_code' => $statusCode, 'error' => $error, 'body' => ''];
    }

    return ['status_code' => $statusCode, 'error' => '', 'body' => $body];
}

/**
 * @return array{
 *   analytics_wrapper_present:bool,
 *   ga_tag_present:bool,
 *   ga_script_src:string,
 *   ga_measurement_id:string
 * }
 */
function inspectHtml(string $html): array {
    $result = [
        'analytics_wrapper_present' => false,
        'ga_tag_present' => false,
        'ga_script_src' => '',
        'ga_measurement_id' => '',
    ];

    if ($html === '') {
        return $result;
    }

    $previousErrors = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $loaded = @$dom->loadHTML($html);
    libxml_clear_errors();
    libxml_use_internal_errors($previousErrors);

    if ($loaded !== true) {
        return $result;
    }

    foreach ($dom->getElementsByTagName('script') as $script) {
        $src = trim((string) $script->getAttribute('src'));
        if ($src === '') {
            continue;
        }

        if (str_contains($src, '/assets/js/analytics.js')) {
            $result['analytics_wrapper_present'] = true;
        }

        if (str_contains($src, 'googletagmanager.com/gtag/js')
            && preg_match('/[?&]id=(G-[A-Z0-9]+)/i', $src, $matches) === 1) {
            $result['ga_tag_present'] = true;
            $result['ga_script_src'] = $src;
            $result['ga_measurement_id'] = $matches[1];
        }
    }

    return $result;
}

function normalizeUrlsFromOptions(array $options): array {
    $urls = [];

    if (isset($options['url'])) {
        $values = is_array($options['url']) ? $options['url'] : [$options['url']];
        foreach ($values as $value) {
            $candidate = trim((string) $value);
            if ($candidate !== '') {
                $urls[] = $candidate;
            }
        }
    }

    if (isset($options['urls'])) {
        $values = is_array($options['urls']) ? $options['urls'] : [$options['urls']];
        foreach ($values as $value) {
            foreach (explode(',', (string) $value) as $candidate) {
                $candidate = trim($candidate);
                if ($candidate !== '') {
                    $urls[] = $candidate;
                }
            }
        }
    }

    if (empty($urls)) {
        $defaultBase = rtrim((string) (getenv('APP_URL') ?: 'http://127.0.0.1:8082'), '/');
        $urls[] = $defaultBase . '/';
    }

    return array_values(array_unique($urls));
}

$options = getopt('', [
    'url:',
    'urls:',
    'expect-measurement-id::',
    'timeout::',
    'require-analytics-wrapper',
    'json',
    'help',
]);

if ($options === false || isset($options['help'])) {
    usage();
    exit(0);
}

$timeout = (int) ($options['timeout'] ?? 10);
if ($timeout < 1) {
    $timeout = 10;
}

$expectedMeasurementId = isset($options['expect-measurement-id']) ? trim((string) $options['expect-measurement-id']) : '';
$emitJson = isset($options['json']);
$requireAnalyticsWrapper = isset($options['require-analytics-wrapper']);
$urls = normalizeUrlsFromOptions($options);

$allOk = true;
$results = [];

foreach ($urls as $url) {
    $entry = [
        'url' => $url,
        'ok' => true,
        'errors' => [],
        'status_code' => 0,
        'analytics_wrapper_present' => false,
        'ga_tag_present' => false,
        'ga_script_src' => '',
        'ga_measurement_id' => '',
    ];

    $fetch = fetchHtml($url, $timeout);
    $entry['status_code'] = $fetch['status_code'];
    if ($fetch['error'] !== '') {
        $entry['ok'] = false;
        $entry['errors'][] = $fetch['error'];
    }

    if ($fetch['status_code'] < 200 || $fetch['status_code'] >= 400) {
        $entry['ok'] = false;
        $entry['errors'][] = 'Unexpected HTTP status code';
    }

    $inspection = inspectHtml($fetch['body']);
    $entry = array_merge($entry, $inspection);

    if ($requireAnalyticsWrapper && !$inspection['analytics_wrapper_present']) {
        $entry['ok'] = false;
        $entry['errors'][] = 'Missing /assets/js/analytics.js script';
    }

    if (!$inspection['ga_tag_present']) {
        $entry['ok'] = false;
        $entry['errors'][] = 'Missing GA4 gtag.js script tag';
    }

    if ($expectedMeasurementId !== '' && strcasecmp($inspection['ga_measurement_id'], $expectedMeasurementId) !== 0) {
        $entry['ok'] = false;
        $entry['errors'][] = 'GA4 measurement ID mismatch';
    }

    if ($entry['ok'] !== true) {
        $allOk = false;
    }

    $results[] = $entry;
}

if ($emitJson) {
    fwrite(STDOUT, json_encode([
        'ok' => $allOk,
        'results' => $results,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    exit($allOk ? 0 : 1);
}

foreach ($results as $result) {
    $status = $result['ok'] ? 'PASS' : 'FAIL';
    fwrite(STDOUT, sprintf("[%s] %s (status=%d)\n", $status, $result['url'], $result['status_code']));
    fwrite(STDOUT, sprintf("  analytics.js:   %s\n", $result['analytics_wrapper_present'] ? 'present' : 'missing'));
    fwrite(STDOUT, sprintf("  gtag.js tag:    %s\n", $result['ga_tag_present'] ? 'present' : 'missing'));
    fwrite(STDOUT, sprintf("  measurement id: %s\n", $result['ga_measurement_id'] !== '' ? $result['ga_measurement_id'] : '<empty>'));

    if (!$result['ok']) {
        foreach ($result['errors'] as $error) {
            fwrite(STDOUT, "  - {$error}\n");
        }
    }
}

exit($allOk ? 0 : 1);
