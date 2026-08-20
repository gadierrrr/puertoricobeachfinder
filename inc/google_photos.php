<?php
/**
 * Google Places photo proxy — budget-capped, live-fetch only.
 *
 * Policy constraints (Google Maps Platform ToS): only place_id may be stored;
 * photo resource names and images must be requested live and never cached
 * server-side. Both endpoints therefore pass content straight through.
 *
 * Budget: media requests bill the "Place Details Photos" SKU — 1,000 free per
 * month, then $7/1,000. The media cap of 2,200 bounds worst-case spend at
 * (2,200 - 1,000) x $7/1,000 = $8.40/month. Place Details with a photos-only
 * field mask bills the Essentials (IDs Only) SKU; the 4,500 cap keeps it
 * inside every plausible free tier ($0). Combined worst case stays under the
 * $10/month ceiling. Caps are enforced BEFORE any Google call is made.
 */

require_once __DIR__ . '/db.php';

function googlePhotosApiKey(): string
{
    return trim((string) env('GOOGLE_MAPS_API_KEY', ''));
}

function googlePhotosMediaMonthlyCap(): int
{
    return max(0, (int) env('GOOGLE_PHOTOS_MEDIA_MONTHLY_CAP', 2200));
}

function googlePhotosDetailsMonthlyCap(): int
{
    return max(0, (int) env('GOOGLE_PHOTOS_DETAILS_MONTHLY_CAP', 4500));
}

/**
 * Atomically consume one unit of this month's budget for $kind.
 * Returns false (and consumes nothing) once the cap is reached.
 */
function googlePhotosBudgetConsume(string $kind, int $cap): bool
{
    if ($cap <= 0) {
        return false;
    }
    $db = getDb();
    $period = gmdate('Y-m');
    if (!$db->exec('BEGIN IMMEDIATE')) {
        return false;
    }
    try {
        $stmt = $db->prepare('SELECT count FROM api_usage_counters WHERE period = :p AND kind = :k');
        $stmt->bindValue(':p', $period);
        $stmt->bindValue(':k', $kind);
        $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        if (((int) ($row['count'] ?? 0)) >= $cap) {
            $db->exec('COMMIT');
            return false;
        }
        $stmt = $db->prepare(
            'INSERT INTO api_usage_counters (period, kind, count, updated_at)
             VALUES (:p, :k, 1, CURRENT_TIMESTAMP)
             ON CONFLICT(period, kind) DO UPDATE SET count = count + 1, updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->bindValue(':p', $period);
        $stmt->bindValue(':k', $kind);
        $stmt->execute();
        $db->exec('COMMIT');
        return true;
    } catch (\Throwable $e) {
        $db->exec('ROLLBACK');
        error_log('googlePhotosBudgetConsume failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * The endpoints only respond to same-origin browser traffic: fetch()/<img>
 * from our own pages sends Sec-Fetch-Site: same-origin. Fall back to a
 * Referer host check for older browsers; bare requests (curl, scrapers,
 * hotlinks) are refused so they cannot burn the budget.
 */
function googlePhotosRequestIsSameOrigin(): bool
{
    $secFetch = strtolower(trim((string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '')));
    if ($secFetch !== '') {
        return $secFetch === 'same-origin';
    }
    $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    if ($referer === '') {
        return false;
    }
    $refHost = strtolower((string) parse_url($referer, PHP_URL_HOST));
    $ownHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $ownHost = preg_replace('/:\d+$/', '', $ownHost);
    $refHost = preg_replace('/:\d+$/', '', $refHost);
    return $refHost !== '' && $refHost === $ownHost;
}

function googlePhotosHttpGetJson(string $url, array $headers): ?array
{
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $body = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    if (!is_string($body) || $status < 200 || $status >= 300) {
        return null;
    }
    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : null;
}

/**
 * Live Place Details (New) request with a photos-only field mask.
 * Returns up to 10 photo descriptors, or null on failure.
 */
function googlePhotosFetchList(string $placeId): ?array
{
    $key = googlePhotosApiKey();
    if ($key === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $placeId)) {
        return null;
    }
    $data = googlePhotosHttpGetJson(
        'https://places.googleapis.com/v1/places/' . rawurlencode($placeId),
        ['X-Goog-Api-Key: ' . $key, 'X-Goog-FieldMask: photos']
    );
    if ($data === null) {
        return null;
    }
    $photos = [];
    foreach (array_slice(is_array($data['photos'] ?? null) ? $data['photos'] : [], 0, 10) as $photo) {
        $name = (string) ($photo['name'] ?? '');
        if ($name === '') {
            continue;
        }
        $author = is_array($photo['authorAttributions'] ?? null) ? ($photo['authorAttributions'][0] ?? []) : [];
        $photos[] = [
            'name' => $name,
            'width' => (int) ($photo['widthPx'] ?? 0),
            'height' => (int) ($photo['heightPx'] ?? 0),
            'author' => trim((string) ($author['displayName'] ?? '')),
            'author_url' => trim((string) ($author['uri'] ?? '')),
        ];
    }
    return $photos;
}

/**
 * Resolve a photo resource name to its short-lived googleusercontent URI.
 */
function googlePhotosFetchMediaUri(string $photoName, int $maxWidthPx): ?string
{
    $key = googlePhotosApiKey();
    if ($key === '') {
        return null;
    }
    $data = googlePhotosHttpGetJson(
        'https://places.googleapis.com/v1/' . str_replace('%2F', '/', rawurlencode($photoName))
            . '/media?maxWidthPx=' . $maxWidthPx . '&skipHttpRedirect=true',
        ['X-Goog-Api-Key: ' . $key]
    );
    $uri = (string) ($data['photoUri'] ?? '');
    if (!str_starts_with($uri, 'https://')) {
        return null;
    }
    return $uri;
}
