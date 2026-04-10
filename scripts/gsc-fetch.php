<?php
/**
 * Pulls Search Console data for sc-domain:puertoricobeachfinder.com
 * Uses the OAuth refresh token in data/gsc-tokens.json.
 *
 * Saves five JSON slices to data/gsc-reports/:
 *   - by-query.json     top queries
 *   - by-page.json      top pages
 *   - by-query-page.json query+page pairs
 *   - by-device.json    desktop/mobile/tablet
 *   - by-country.json   country breakdown
 */

$tokensPath = __DIR__ . '/../data/gsc-tokens.json';
$outDir     = __DIR__ . '/../data/gsc-reports';
$siteUrl    = 'sc-domain:puertoricobeachfinder.com';

if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

$tokens = json_decode(file_get_contents($tokensPath), true);
if (!$tokens || empty($tokens['refresh_token'])) {
    fwrite(STDERR, "No refresh_token in $tokensPath\n");
    exit(1);
}

// 1. Exchange refresh_token for an access_token.
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'client_id'     => $tokens['client_id'],
        'client_secret' => $tokens['client_secret'],
        'refresh_token' => $tokens['refresh_token'],
        'grant_type'    => 'refresh_token',
    ]),
]);
$tokenResp = curl_exec($ch);
$tokenCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($tokenCode !== 200) {
    fwrite(STDERR, "Token exchange failed (HTTP $tokenCode): $tokenResp\n");
    exit(1);
}
$accessToken = json_decode($tokenResp, true)['access_token'] ?? null;
if (!$accessToken) {
    fwrite(STDERR, "No access_token in response: $tokenResp\n");
    exit(1);
}
fwrite(STDERR, "Got access_token (len=" . strlen($accessToken) . ")\n");

// 2. Helper that calls searchAnalytics.query.
function gscQuery(string $accessToken, string $siteUrl, array $body): array {
    $endpoint = 'https://searchconsole.googleapis.com/webmasters/v3/sites/'
        . rawurlencode($siteUrl) . '/searchAnalytics/query';

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode($body),
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) {
        fwrite(STDERR, "GSC query failed (HTTP $code): $resp\n");
        return ['error' => $resp, 'rows' => []];
    }
    return json_decode($resp, true) ?: ['rows' => []];
}

// 3. Date range: last 28 days, with 3-day data lag.
$endDate   = date('Y-m-d', strtotime('-3 days'));
$startDate = date('Y-m-d', strtotime('-31 days'));
fwrite(STDERR, "Date range: $startDate -> $endDate\n");

$slices = [
    'by-query' => [
        'startDate'  => $startDate,
        'endDate'    => $endDate,
        'dimensions' => ['query'],
        'rowLimit'   => 1000,
    ],
    'by-page' => [
        'startDate'  => $startDate,
        'endDate'    => $endDate,
        'dimensions' => ['page'],
        'rowLimit'   => 1000,
    ],
    'by-query-page' => [
        'startDate'  => $startDate,
        'endDate'    => $endDate,
        'dimensions' => ['query', 'page'],
        'rowLimit'   => 2000,
    ],
    'by-device' => [
        'startDate'  => $startDate,
        'endDate'    => $endDate,
        'dimensions' => ['device'],
        'rowLimit'   => 25,
    ],
    'by-country' => [
        'startDate'  => $startDate,
        'endDate'    => $endDate,
        'dimensions' => ['country'],
        'rowLimit'   => 50,
    ],
];

foreach ($slices as $name => $body) {
    fwrite(STDERR, "Fetching $name ... ");
    $result = gscQuery($accessToken, $siteUrl, $body);
    $rows   = $result['rows'] ?? [];
    file_put_contents("$outDir/$name.json", json_encode($result, JSON_PRETTY_PRINT));
    fwrite(STDERR, count($rows) . " rows\n");
}

fwrite(STDERR, "Done. Reports in $outDir\n");
