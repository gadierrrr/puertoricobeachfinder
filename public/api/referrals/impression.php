<?php
/** Record a real viewport impression for referral conversion analysis. */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/referrals.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

$userAgent = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
if ($userAgent !== '' && preg_match('/bot|crawl|spider|slurp|preview|validator|monitor|pingdom|uptime/i', $userAgent)) {
    echo json_encode(['ok' => true, 'ignored' => true]);
    exit;
}

$raw = file_get_contents('php://input');
$payload = is_string($raw) ? json_decode($raw, true) : null;
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
    exit;
}

$eventId = trim((string) ($payload['event_id'] ?? ''));
$campaignSlug = trim((string) ($payload['campaign'] ?? ''));
if (!preg_match('/^[A-Za-z0-9-]{16,80}$/', $eventId) || !preg_match('/^[a-z0-9-]{3,120}$/', $campaignSlug)) {
    http_response_code(422);
    echo json_encode(['ok' => false]);
    exit;
}

$campaign = referralGetCampaignBySlug($campaignSlug, true);
if (!$campaign) {
    http_response_code(404);
    echo json_encode(['ok' => false]);
    exit;
}

$limited = static function (mixed $value, int $length = 160): string {
    $value = preg_replace('/[\x00-\x1f\x7f]/', '', trim((string) $value));
    return mb_substr((string) $value, 0, $length);
};
$ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
$anon = trim((string) ($_COOKIE['BF_ANON_ID'] ?? ''));

execute(
    'INSERT OR IGNORE INTO referral_impressions
        (id, event_id, provider_id, campaign_id, page_type, page_slug, placement_key,
         locale, match_type, product_code, api_hydrated, anon_id, ip_hash, ua_hash, viewed_at)
     VALUES
        (:id, :event_id, :provider_id, :campaign_id, :page_type, :page_slug, :placement,
         :locale, :match_type, :product_code, :api_hydrated, :anon_id, :ip_hash, :ua_hash, CURRENT_TIMESTAMP)',
    [
        ':id' => uuid(),
        ':event_id' => $eventId,
        ':provider_id' => $campaign['provider_id'],
        ':campaign_id' => $campaign['id'],
        ':page_type' => $limited($payload['page_type'] ?? '', 40),
        ':page_slug' => $limited($payload['page_slug'] ?? ''),
        ':placement' => $limited($payload['placement'] ?? '', 80),
        ':locale' => referralNormalizeLocale($payload['locale'] ?? 'en'),
        ':match_type' => $limited($payload['match_type'] ?? '', 40),
        ':product_code' => $limited($payload['product_code'] ?? '', 60),
        ':api_hydrated' => ((string) ($payload['api_hydrated'] ?? '0')) === '1' ? 1 : 0,
        ':anon_id' => $limited($anon, 100),
        ':ip_hash' => $ip !== '' ? referralHashValue($ip) : null,
        ':ua_hash' => $userAgent !== '' ? referralHashValue($userAgent) : null,
    ]
);

echo json_encode(['ok' => true]);
