<?php
/**
 * Viator Affiliate API integration.
 *
 * API calls are CLI-only in normal operation. Public requests read the local
 * cache and redirect through exact, unmodified productUrl values returned by
 * Viator so affiliate attribution remains intact.
 */

if (defined('VIATOR_INCLUDED')) {
    return;
}
define('VIATOR_INCLUDED', true);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function viatorNormalizeLocale(?string $locale): string
{
    return strtolower(trim((string) $locale)) === 'es' ? 'es' : 'en';
}

function viatorAcceptLanguage(string $locale): string
{
    return viatorNormalizeLocale($locale) === 'es' ? 'es' : 'en-US';
}

function viatorApiBaseUrl(): string
{
    $configured = rtrim(trim((string) env('VIATOR_API_BASE_URL', 'https://api.viator.com/partner')), '/');
    $host = strtolower((string) parse_url($configured, PHP_URL_HOST));
    $scheme = strtolower((string) parse_url($configured, PHP_URL_SCHEME));
    if ($scheme !== 'https' || !in_array($host, ['api.viator.com', 'api.sandbox.viator.com'], true)) {
        throw new RuntimeException('VIATOR_API_BASE_URL must use an official HTTPS Viator API host.');
    }
    return $configured;
}

function viatorIsConfigured(): bool
{
    return envBool('VIATOR_API_ENABLED', false)
        && trim((string) env('VIATOR_API_KEY', '')) !== '';
}

function viatorTableExists(string $table): bool
{
    $row = queryOne(
        'SELECT name FROM sqlite_master WHERE type = "table" AND name = :name LIMIT 1',
        [':name' => $table]
    );
    return is_array($row);
}

/**
 * @return array{data: array, status: int, headers: array}
 */
function viatorApiRequest(string $method, string $path, string $locale = 'en', array $query = [], ?array $body = null): array
{
    $apiKey = trim((string) env('VIATOR_API_KEY', ''));
    if ($apiKey === '') {
        throw new RuntimeException('VIATOR_API_KEY is not configured.');
    }

    $method = strtoupper($method);
    $url = viatorApiBaseUrl() . '/' . ltrim($path, '/');
    if ($query !== []) {
        $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    $responseHeaders = [];
    $curl = curl_init($url);
    if ($curl === false) {
        throw new RuntimeException('Unable to initialize cURL for Viator.');
    }

    $headers = [
        'exp-api-key: ' . $apiKey,
        'Accept: application/json;version=2.0',
        'Accept-Language: ' . viatorAcceptLanguage($locale),
        'Accept-Encoding: gzip',
    ];
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json;version=2.0';
    }

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_ENCODING => '',
        CURLOPT_USERAGENT => 'PuertoRicoBeachFinder/1.0 ViatorAffiliateSync',
        CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$responseHeaders): int {
            $length = strlen($line);
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
            return $length;
        },
    ]);
    if ($body !== null) {
        $encoded = json_encode($body, JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded)) {
            curl_close($curl);
            throw new RuntimeException('Unable to encode Viator request body.');
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $encoded);
    }

    $raw = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    if (!is_string($raw)) {
        throw new RuntimeException('Viator request failed: ' . ($error !== '' ? $error : 'empty response'));
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Viator returned invalid JSON (HTTP ' . $status . ').');
    }
    if ($status < 200 || $status >= 300) {
        $message = trim((string) ($decoded['message'] ?? $decoded['code'] ?? 'request failed'));
        throw new RuntimeException('Viator API HTTP ' . $status . ': ' . $message);
    }

    return ['data' => $decoded, 'status' => $status, 'headers' => $responseHeaders];
}

function viatorArrayFirstScalarByKeys(array $value, array $keys): mixed
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $value) && is_scalar($value[$key])) {
            return $value[$key];
        }
    }
    foreach ($value as $child) {
        if (!is_array($child)) {
            continue;
        }
        $found = viatorArrayFirstScalarByKeys($child, $keys);
        if ($found !== null && $found !== '') {
            return $found;
        }
    }
    return null;
}

function viatorCollectNumericValuesByKeys(array $value, array $keys, array &$found): void
{
    foreach ($value as $key => $child) {
        if (in_array((string) $key, $keys, true) && is_numeric($child)) {
            $number = (float) $child;
            if ($number > 0) {
                $found[] = $number;
            }
        }
        if (is_array($child)) {
            viatorCollectNumericValuesByKeys($child, $keys, $found);
        }
    }
}

function viatorExtractImageUrl(array $product): string
{
    $candidates = [];
    foreach (($product['images'] ?? []) as $image) {
        if (!is_array($image)) {
            continue;
        }
        foreach (($image['variants'] ?? []) as $variant) {
            if (!is_array($variant)) {
                continue;
            }
            $url = trim((string) ($variant['url'] ?? ''));
            if ($url === '' || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') {
                continue;
            }
            $width = (int) ($variant['width'] ?? 0);
            $height = (int) ($variant['height'] ?? 0);
            $coverBonus = !empty($image['isCover']) ? 1000000 : 0;
            $sizeScore = min(max($width, $height), 1600);
            $candidates[] = ['url' => $url, 'score' => $coverBonus + $sizeScore];
        }
    }
    usort($candidates, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);
    return (string) ($candidates[0]['url'] ?? '');
}

/** @return array{0:?int,1:?int} */
function viatorExtractDuration(array $product): array
{
    $duration = is_array($product['duration'] ?? null) ? $product['duration'] : [];
    $fixed = (int) ($duration['fixedDurationInMinutes'] ?? 0);
    if ($fixed > 0) {
        return [$fixed, $fixed];
    }

    $min = (int) ($duration['variableDurationFromMinutes'] ?? $duration['fromMinutes'] ?? 0);
    $max = (int) ($duration['variableDurationToMinutes'] ?? $duration['toMinutes'] ?? 0);
    return [$min > 0 ? $min : null, $max > 0 ? $max : ($min > 0 ? $min : null)];
}

function viatorExtractDepartureSummary(array $product): string
{
    $logistics = is_array($product['logistics'] ?? null) ? $product['logistics'] : [];
    $start = is_array($logistics['start'] ?? null) ? $logistics['start'] : [];
    $value = viatorArrayFirstScalarByKeys($start, ['name', 'address', 'description']);
    return mb_substr(trim((string) $value), 0, 240);
}

/** @return array{price:?float,currency:string} */
function viatorExtractPrice(array $schedule): array
{
    $values = [];
    viatorCollectNumericValuesByKeys(
        $schedule,
        ['fromPrice', 'recommendedRetailPrice', 'recommendedRetailPriceAmount'],
        $values
    );
    $price = $values !== [] ? min($values) : null;
    $currency = strtoupper(trim((string) viatorArrayFirstScalarByKeys(
        $schedule,
        ['currency', 'currencyCode', 'currencyCodeSupplier']
    )));
    if (!preg_match('/^[A-Z]{3}$/', $currency)) {
        $currency = 'USD';
    }
    return ['price' => $price, 'currency' => $currency];
}

function viatorExtractFreeCancellation(array $product): ?int
{
    $encoded = json_encode($product, JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded)) {
        return null;
    }
    if (str_contains(strtoupper($encoded), 'FREE_CANCELLATION')) {
        return 1;
    }
    return null;
}

function viatorProductUrlIsValid(string $url): bool
{
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return $scheme === 'https' && ($host === 'viator.com' || str_ends_with($host, '.viator.com'));
}

function viatorUpsertProduct(array $campaign, string $productCode, string $locale, array $product, array $schedule): void
{
    $locale = viatorNormalizeLocale($locale);
    [$durationMin, $durationMax] = viatorExtractDuration($product);
    $pricing = viatorExtractPrice($schedule);
    $reviews = is_array($product['reviews'] ?? null) ? $product['reviews'] : [];
    $productUrl = trim((string) ($product['productUrl'] ?? ''));
    $status = strtoupper(trim((string) ($product['status'] ?? 'UNKNOWN')));

    execute(
        'INSERT INTO viator_products
            (product_code, locale, status, title, description, image_url, rating, review_count,
             duration_minutes_min, duration_minutes_max, departure_summary, free_cancellation,
             price_from, currency, viator_last_updated_at, fetched_at, raw_json)
         VALUES
            (:product_code, :locale, :status, :title, :description, :image_url, :rating, :review_count,
             :duration_min, :duration_max, :departure, :free_cancellation,
             :price_from, :currency, :last_updated, CURRENT_TIMESTAMP, :raw_json)
         ON CONFLICT(product_code, locale) DO UPDATE SET
            status = excluded.status, title = excluded.title, description = excluded.description,
            image_url = excluded.image_url, rating = excluded.rating, review_count = excluded.review_count,
            duration_minutes_min = excluded.duration_minutes_min,
            duration_minutes_max = excluded.duration_minutes_max,
            departure_summary = excluded.departure_summary,
            free_cancellation = excluded.free_cancellation,
            price_from = excluded.price_from, currency = excluded.currency,
            viator_last_updated_at = excluded.viator_last_updated_at,
            fetched_at = CURRENT_TIMESTAMP, raw_json = excluded.raw_json',
        [
            ':product_code' => $productCode,
            ':locale' => $locale,
            ':status' => $status,
            ':title' => trim((string) ($product['title'] ?? '')),
            ':description' => trim((string) ($product['description'] ?? '')),
            ':image_url' => viatorExtractImageUrl($product),
            ':rating' => is_numeric($reviews['combinedAverageRating'] ?? null) ? (float) $reviews['combinedAverageRating'] : null,
            ':review_count' => max(0, (int) ($reviews['totalReviews'] ?? 0)),
            ':duration_min' => $durationMin,
            ':duration_max' => $durationMax,
            ':departure' => viatorExtractDepartureSummary($product),
            ':free_cancellation' => viatorExtractFreeCancellation($product),
            ':price_from' => $pricing['price'],
            ':currency' => $pricing['currency'],
            ':last_updated' => trim((string) ($product['lastUpdatedAt'] ?? '')),
            ':raw_json' => json_encode($product, JSON_UNESCAPED_SLASHES),
        ]
    );

    if ($status === 'ACTIVE' && viatorProductUrlIsValid($productUrl)) {
        execute(
            'INSERT INTO viator_product_links
                (campaign_id, product_code, locale, campaign_value, product_url, fetched_at)
             VALUES (:campaign_id, :product_code, :locale, :campaign_value, :product_url, CURRENT_TIMESTAMP)
             ON CONFLICT(campaign_id, locale) DO UPDATE SET
                product_code = excluded.product_code,
                campaign_value = excluded.campaign_value,
                product_url = excluded.product_url,
                fetched_at = CURRENT_TIMESTAMP',
            [
                ':campaign_id' => $campaign['id'],
                ':product_code' => $productCode,
                ':locale' => $locale,
                ':campaign_value' => $campaign['slug'],
                ':product_url' => $productUrl,
            ]
        );
    } elseif ($status !== 'ACTIVE') {
        execute(
            'DELETE FROM viator_product_links WHERE campaign_id = :campaign_id AND locale = :locale',
            [':campaign_id' => $campaign['id'], ':locale' => $locale]
        );
    }
}

function viatorSyncCampaign(array $campaign, string $productCode, string $locale): array
{
    $locale = viatorNormalizeLocale($locale);
    $productResponse = viatorApiRequest(
        'GET',
        '/products/' . rawurlencode($productCode),
        $locale,
        ['campaign-value' => (string) $campaign['slug']]
    );

    $schedule = [];
    try {
        $scheduleResponse = viatorApiRequest(
            'GET',
            '/availability/schedules/' . rawurlencode($productCode),
            $locale,
            ['currency' => 'USD']
        );
        $schedule = $scheduleResponse['data'];
    } catch (Throwable $e) {
        // Product status/content and exact attribution URL remain more valuable
        // than price. Keep them fresh even when schedule pricing is unavailable.
        error_log('Viator schedule sync warning for ' . $productCode . ': ' . $e->getMessage());
    }

    viatorUpsertProduct($campaign, $productCode, $locale, $productResponse['data'], $schedule);
    return $productResponse['data'];
}

function viatorCampaignProductMappings(): array
{
    if (!viatorTableExists('viator_campaign_products')) {
        return [];
    }
    $rows = query(
        'SELECT c.id, c.slug, c.name, c.provider_id, p.product_code
         FROM viator_campaign_products p
         INNER JOIN referral_campaigns c ON c.id = p.campaign_id
         INNER JOIN referral_providers rp ON rp.id = c.provider_id
         WHERE rp.slug = "viator" AND c.status = "active"
         ORDER BY c.slug ASC'
    );
    return is_array($rows) ? $rows : [];
}

function viatorHydratedProduct(string $productCode, string $locale): ?array
{
    if (!viatorTableExists('viator_products')) {
        return null;
    }
    $row = queryOne(
        'SELECT * FROM viator_products WHERE product_code = :product_code AND locale = :locale LIMIT 1',
        [':product_code' => $productCode, ':locale' => viatorNormalizeLocale($locale)]
    );
    return is_array($row) ? $row : null;
}

function viatorProductCodeForCampaign(string $campaignId): string
{
    if (!viatorTableExists('viator_campaign_products')) {
        return '';
    }
    $row = queryOne(
        'SELECT product_code FROM viator_campaign_products WHERE campaign_id = :campaign_id LIMIT 1',
        [':campaign_id' => $campaignId]
    );
    return trim((string) ($row['product_code'] ?? ''));
}

function viatorCampaignProductIsInactive(string $campaignId): bool
{
    $productCode = viatorProductCodeForCampaign($campaignId);
    if ($productCode === '') {
        return false;
    }
    $product = viatorHydratedProduct($productCode, 'en');
    return is_array($product) && strtoupper((string) ($product['status'] ?? '')) === 'INACTIVE';
}

function viatorExactProductUrl(string $campaignId, string $productCode, string $locale): string
{
    if (!viatorTableExists('viator_product_links') || !viatorTableExists('viator_products')) {
        return '';
    }
    $row = queryOne(
        'SELECT l.product_url
         FROM viator_product_links l
         INNER JOIN viator_products p ON p.product_code = l.product_code AND p.locale = l.locale
         WHERE l.campaign_id = :campaign_id
           AND l.product_code = :product_code
           AND l.locale = :locale
           AND p.status = "ACTIVE"
         LIMIT 1',
        [
            ':campaign_id' => $campaignId,
            ':product_code' => $productCode,
            ':locale' => viatorNormalizeLocale($locale),
        ]
    );
    $url = trim((string) ($row['product_url'] ?? ''));
    return viatorProductUrlIsValid($url) ? $url : '';
}

function viatorFormatDuration(?int $min, ?int $max, string $locale): string
{
    if (!$min && !$max) {
        return '';
    }
    $minutes = $max ?: $min;
    if (!$minutes) {
        return '';
    }
    $hours = intdiv($minutes, 60);
    $remainder = $minutes % 60;
    $prefix = viatorNormalizeLocale($locale) === 'es' ? 'Aprox. ' : 'About ';
    if ($hours > 0 && $remainder > 0) {
        return $prefix . $hours . ' h ' . $remainder . ' min';
    }
    if ($hours > 0) {
        if (viatorNormalizeLocale($locale) === 'es') {
            return $prefix . $hours . ($hours === 1 ? ' hora' : ' horas');
        }
        return $prefix . $hours . ($hours === 1 ? ' hour' : ' hours');
    }
    return $prefix . $minutes . ' min';
}
