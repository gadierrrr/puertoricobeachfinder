<?php
/**
 * Submit (or resubmit) the site's sitemap to Google Search Console.
 *
 * Uses the existing OAuth refresh token in data/gsc-tokens.json. Requires
 * the 'webmasters' OAuth scope (read/write). If the token only has
 * 'webmasters.readonly' the PUT will fail with 403 and the user must
 * resubmit via the GSC UI instead.
 *
 * Usage:
 *   php scripts/gsc-submit-sitemap.php
 */

$tokensPath = __DIR__ . '/../data/gsc-tokens.json';
$siteUrl    = 'sc-domain:puertoricobeachfinder.com';
$sitemapUrl = 'https://www.puertoricobeachfinder.com/sitemap.xml';

$tokens = json_decode((string) file_get_contents($tokensPath), true);
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
$tokenData   = json_decode((string) $tokenResp, true);
$accessToken = $tokenData['access_token'] ?? null;
$scope       = $tokenData['scope'] ?? '(unknown)';
if (!$accessToken) {
    fwrite(STDERR, "No access_token in response: $tokenResp\n");
    exit(1);
}
echo "Got access_token. Scope: $scope\n";

// 2. PUT /webmasters/v3/sites/{siteUrl}/sitemaps/{feedpath}
$endpoint = 'https://searchconsole.googleapis.com/webmasters/v3/sites/'
    . rawurlencode($siteUrl)
    . '/sitemaps/'
    . rawurlencode($sitemapUrl);

echo "PUT $endpoint\n";

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST  => 'PUT',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $accessToken,
        'Content-Length: 0',
    ],
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP $code\n";
if ($resp !== '' && $resp !== false) {
    echo "Body: $resp\n";
}

if ($code !== 200 && $code !== 204) {
    fwrite(STDERR, "Submission failed.\n");
    exit(2);
}

echo "\nSitemap submitted successfully. Google will re-parse within a few hours.\n";

// 3. Optional: fetch the sitemap status to confirm Google sees it.
echo "\nFetching current sitemap status...\n";
$statusEndpoint = 'https://searchconsole.googleapis.com/webmasters/v3/sites/'
    . rawurlencode($siteUrl) . '/sitemaps/' . rawurlencode($sitemapUrl);
$ch = curl_init($statusEndpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
]);
$statusResp = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($statusCode === 200) {
    $j = json_decode($statusResp, true);
    echo "  lastSubmitted: " . ($j['lastSubmitted'] ?? '-') . "\n";
    echo "  lastDownloaded: " . ($j['lastDownloaded'] ?? '-') . "\n";
    echo "  isPending: " . (($j['isPending'] ?? false) ? 'yes' : 'no') . "\n";
    echo "  isSitemapsIndex: " . (($j['isSitemapsIndex'] ?? false) ? 'yes' : 'no') . "\n";
    echo "  type: " . ($j['type'] ?? '-') . "\n";
    echo "  errors: " . ($j['errors'] ?? 0) . "\n";
    echo "  warnings: " . ($j['warnings'] ?? 0) . "\n";
}
