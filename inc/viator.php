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
 * Retries transient failures (HTTP 429 and 5xx) with exponential backoff,
 * honoring Retry-After when Viator provides one.
 *
 * @return array{data: array, status: int, headers: array}
 */
function viatorApiRequest(string $method, string $path, string $locale = 'en', array $query = [], ?array $body = null, int $maxAttempts = 3): array
{
    $attempt = 0;
    while (true) {
        $attempt++;
        try {
            return viatorApiRequestOnce($method, $path, $locale, $query, $body);
        } catch (ViatorRetryableException $e) {
            if ($attempt >= $maxAttempts || PHP_SAPI !== 'cli') {
                throw new RuntimeException($e->getMessage(), 0, $e);
            }
            $delay = $e->retryAfterSeconds > 0
                ? min($e->retryAfterSeconds, 30)
                : min(2 ** $attempt, 20);
            sleep((int) $delay);
        }
    }
}

class ViatorRetryableException extends RuntimeException
{
    public int $retryAfterSeconds = 0;
}

/**
 * @return array{data: array, status: int, headers: array}
 */
function viatorApiRequestOnce(string $method, string $path, string $locale = 'en', array $query = [], ?array $body = null): array
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
        if ($status === 429 || $status >= 500) {
            $retryable = new ViatorRetryableException('Viator API HTTP ' . $status . ': ' . $message);
            $retryable->retryAfterSeconds = max(0, (int) ($responseHeaders['retry-after'] ?? 0));
            throw $retryable;
        }
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
    // Product-details responses carry the policy object, not the search
    // response's FREE_CANCELLATION flag. STANDARD = full refund up to 24h
    // before departure.
    $policyType = strtoupper(trim((string) ($product['cancellationPolicy']['type'] ?? '')));
    if ($policyType === 'STANDARD') {
        return 1;
    }
    if ($policyType === 'ALL_SALES_FINAL') {
        return 0;
    }
    $encoded = json_encode($product, JSON_UNESCAPED_SLASHES);
    if (!is_string($encoded)) {
        return null;
    }
    if (str_contains(strtoupper($encoded), 'FREE_CANCELLATION')) {
        return 1;
    }
    return null;
}

/**
 * Compact inclusion highlights ("Lunch & drinks", "Gear included") derived
 * from a cached viator_products row's raw payload. Conservative keyword
 * matching — plain bottled water does not count as drinks. Returns at most
 * two localized chip labels.
 */
function viatorInclusionChips(array $row, string $lang): array
{
    $raw = json_decode((string) ($row['raw_json'] ?? ''), true);
    if (!is_array($raw) || empty($raw['inclusions']) || !is_array($raw['inclusions'])) {
        return [];
    }
    $lunch = $drinks = $gear = false;
    foreach ($raw['inclusions'] as $inc) {
        if (!is_array($inc)) {
            continue;
        }
        $text = strtolower(trim((string) ($inc['typeDescription'] ?? '') . ' ' . (string) ($inc['otherDescription'] ?? '')));
        if ($text === '') {
            continue;
        }
        foreach (['lunch', 'almuerzo', 'breakfast', 'desayuno', 'dinner', 'cena'] as $kw) {
            if (str_contains($text, $kw)) {
                $lunch = true;
            }
        }
        foreach (['alcoholic', 'alcohólica', 'alcoholica', 'carbonated', 'carbonatada', 'open bar', 'barra libre', 'soft drink', 'refresco'] as $kw) {
            if (str_contains($text, $kw)) {
                $drinks = true;
            }
        }
        $mentionsEquipment = str_contains($text, 'equipment') || str_contains($text, 'gear') || str_contains($text, 'equipo');
        if ((str_contains($text, 'snorkel') || str_contains($text, 'esnórquel')) && $mentionsEquipment) {
            $gear = true;
        }
        foreach (['all gear', 'todo el equipo', 'use of equipment', 'uso del equipo'] as $kw) {
            if (str_contains($text, $kw)) {
                $gear = true;
            }
        }
    }
    $isEs = $lang === 'es';
    $chips = [];
    if ($lunch && $drinks) {
        $chips[] = $isEs ? 'Almuerzo y bebidas' : 'Lunch & drinks';
    } elseif ($lunch) {
        $chips[] = $isEs ? 'Almuerzo incluido' : 'Lunch included';
    } elseif ($drinks) {
        $chips[] = $isEs ? 'Bebidas incluidas' : 'Drinks included';
    }
    if ($gear) {
        $chips[] = $isEs ? 'Equipo incluido' : 'Gear included';
    }
    return array_slice($chips, 0, 2);
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
             price_from, currency, viator_last_updated_at, fetched_at, raw_json,
             product_url, campaign_value, tags_json, destination_ids_json, source)
         VALUES
            (:product_code, :locale, :status, :title, :description, :image_url, :rating, :review_count,
             :duration_min, :duration_max, :departure, :free_cancellation,
             :price_from, :currency, :last_updated, CURRENT_TIMESTAMP, :raw_json,
             :product_url, :campaign_value, :tags_json, :destination_ids_json, "curated_sync")
         ON CONFLICT(product_code, locale) DO UPDATE SET
            status = excluded.status, title = excluded.title, description = excluded.description,
            image_url = excluded.image_url, rating = excluded.rating, review_count = excluded.review_count,
            duration_minutes_min = excluded.duration_minutes_min,
            duration_minutes_max = excluded.duration_minutes_max,
            departure_summary = excluded.departure_summary,
            free_cancellation = excluded.free_cancellation,
            price_from = excluded.price_from, currency = excluded.currency,
            viator_last_updated_at = excluded.viator_last_updated_at,
            fetched_at = CURRENT_TIMESTAMP, raw_json = excluded.raw_json,
            product_url = excluded.product_url, campaign_value = excluded.campaign_value,
            tags_json = excluded.tags_json, destination_ids_json = excluded.destination_ids_json,
            source = "curated_sync"',
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
            ':product_url' => viatorProductUrlIsValid($productUrl) ? $productUrl : '',
            ':campaign_value' => (string) ($campaign['slug'] ?? ''),
            ':tags_json' => json_encode(array_values(array_filter(array_map('intval', (array) ($product['tags'] ?? []))))),
            ':destination_ids_json' => json_encode(viatorExtractDestinationIds($product)),
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

/** @return array<int> Destination IDs referenced by a product payload. */
function viatorExtractDestinationIds(array $product): array
{
    $ids = [];
    foreach ((array) ($product['destinations'] ?? []) as $destination) {
        if (!is_array($destination)) {
            continue;
        }
        $ref = (int) ($destination['ref'] ?? 0);
        if ($ref > 0) {
            $ids[] = $ref;
        }
    }
    return array_values(array_unique($ids));
}

/* ---------------------------------------------------------------------------
 * Catalog layer: destination/tag taxonomy, /products/search sweeps, and
 * beach auto-matching. All API calls remain CLI-only; public pages read the
 * local cache exactly like the curated flow.
 * ------------------------------------------------------------------------ */

const VIATOR_PUERTO_RICO_DESTINATION_ID = 36;

/** Sync the Puerto Rico subtree of Viator's destination taxonomy. */
function viatorSyncDestinations(): int
{
    $response = viatorApiRequest('GET', '/destinations');
    $stored = 0;
    foreach ((array) ($response['data']['destinations'] ?? []) as $destination) {
        if (!is_array($destination)) {
            continue;
        }
        $destId = (int) ($destination['destinationId'] ?? 0);
        $lookupId = trim((string) ($destination['lookupId'] ?? ''));
        $inPuertoRico = $destId === VIATOR_PUERTO_RICO_DESTINATION_ID
            || preg_match('/(^|\.)' . VIATOR_PUERTO_RICO_DESTINATION_ID . '(\.|$)/', $lookupId) === 1;
        if ($destId <= 0 || !$inPuertoRico) {
            continue;
        }

        $center = is_array($destination['center'] ?? null) ? $destination['center'] : [];
        execute(
            'INSERT INTO viator_destinations
                (destination_id, name, destination_type, parent_destination_id, lookup_id,
                 latitude, longitude, raw_json, fetched_at)
             VALUES (:id, :name, :type, :parent, :lookup, :lat, :lng, :raw, CURRENT_TIMESTAMP)
             ON CONFLICT(destination_id) DO UPDATE SET
                name = excluded.name, destination_type = excluded.destination_type,
                parent_destination_id = excluded.parent_destination_id,
                lookup_id = excluded.lookup_id, latitude = excluded.latitude,
                longitude = excluded.longitude, raw_json = excluded.raw_json,
                fetched_at = CURRENT_TIMESTAMP',
            [
                ':id' => $destId,
                ':name' => trim((string) ($destination['name'] ?? '')),
                ':type' => trim((string) ($destination['type'] ?? '')),
                ':parent' => (int) ($destination['parentDestinationId'] ?? 0) ?: null,
                ':lookup' => $lookupId,
                ':lat' => is_numeric($center['latitude'] ?? null) ? (float) $center['latitude'] : null,
                ':lng' => is_numeric($center['longitude'] ?? null) ? (float) $center['longitude'] : null,
                ':raw' => json_encode($destination, JSON_UNESCAPED_SLASHES),
            ]
        );
        $stored++;
    }
    return $stored;
}

/** Sync Viator's product tag taxonomy (names drive local matching). */
function viatorSyncTags(): int
{
    $response = viatorApiRequest('GET', '/products/tags');
    $stored = 0;
    foreach ((array) ($response['data']['tags'] ?? []) as $tag) {
        if (!is_array($tag)) {
            continue;
        }
        $tagId = (int) ($tag['tagId'] ?? 0);
        if ($tagId <= 0) {
            continue;
        }
        $names = is_array($tag['allNamesByLocale'] ?? null) ? $tag['allNamesByLocale'] : [];
        execute(
            'INSERT INTO viator_tags (tag_id, name_en, name_es, parent_tag_ids_json, fetched_at)
             VALUES (:id, :en, :es, :parents, CURRENT_TIMESTAMP)
             ON CONFLICT(tag_id) DO UPDATE SET
                name_en = excluded.name_en, name_es = excluded.name_es,
                parent_tag_ids_json = excluded.parent_tag_ids_json,
                fetched_at = CURRENT_TIMESTAMP',
            [
                ':id' => $tagId,
                ':en' => trim((string) ($names['en'] ?? '')),
                ':es' => trim((string) ($names['es'] ?? '')),
                ':parents' => json_encode(array_values(array_map('intval', (array) ($tag['parentTagIds'] ?? [])))),
            ]
        );
        $stored++;
    }
    return $stored;
}

/**
 * Map beach municipalities to Viator destinations by normalized name.
 * Seeded and manual mappings are preserved; only missing ones are added.
 */
function viatorRefreshMunicipalityDestinations(): int
{
    $destinations = query('SELECT destination_id, name FROM viator_destinations');
    $byName = [];
    foreach (is_array($destinations) ? $destinations : [] as $row) {
        $slug = slugify((string) $row['name']);
        if ($slug !== '') {
            $byName[$slug] = (int) $row['destination_id'];
        }
    }

    $added = 0;
    $municipalities = query('SELECT DISTINCT municipality FROM beaches WHERE municipality != ""');
    foreach (is_array($municipalities) ? $municipalities : [] as $row) {
        $muniSlug = slugify((string) $row['municipality']);
        if ($muniSlug === '' || !isset($byName[$muniSlug])) {
            continue;
        }
        $existing = queryOne(
            'SELECT municipality_slug FROM viator_municipality_destinations WHERE municipality_slug = :slug',
            [':slug' => $muniSlug]
        );
        if (is_array($existing)) {
            continue;
        }
        execute(
            'INSERT INTO viator_municipality_destinations (municipality_slug, destination_id, source)
             VALUES (:slug, :dest, "taxonomy_match")',
            [':slug' => $muniSlug, ':dest' => $byName[$muniSlug]]
        );
        $added++;
    }
    return $added;
}

function viatorMunicipalityDestinationId(string $municipality): ?int
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        if (viatorTableExists('viator_municipality_destinations')) {
            foreach (query('SELECT municipality_slug, destination_id FROM viator_municipality_destinations') as $row) {
                $cache[(string) $row['municipality_slug']] = (int) $row['destination_id'];
            }
        }
    }
    return $cache[slugify($municipality)] ?? null;
}

/**
 * Regional browse campaign used as the click/reporting bucket for
 * auto-matched products (municipality > region > global, same precedence as
 * toursCampaignsForBeach).
 */
function viatorBrowseCampaignForMunicipality(string $municipality): ?array
{
    require_once __DIR__ . '/island_chart.php';

    $scopes = array_values(array_filter(array_unique([
        slugify($municipality),
        (string) (islandRegionForMunicipality($municipality) ?? ''),
        'global',
    ])));

    foreach ($scopes as $scope) {
        $row = queryOne(
            'SELECT c.*, p.slug AS provider_slug, p.name AS provider_name,
                    p.default_disclosure_en, p.default_disclosure_es
             FROM referral_campaigns c
             INNER JOIN referral_providers p ON p.id = c.provider_id
             WHERE c.link_type = "tour" AND c.status = "active" AND p.status = "active"
               AND c.destination_scope = :scope
             ORDER BY c.priority ASC LIMIT 1',
            [':scope' => $scope]
        );
        if (is_array($row)) {
            return $row;
        }
    }
    return null;
}

/** campaign-value used when sweeping a destination's products. */
function viatorCampaignValueForDestination(int $destinationId): string
{
    if (viatorTableExists('viator_municipality_destinations')) {
        $row = queryOne(
            'SELECT municipality_slug FROM viator_municipality_destinations
             WHERE destination_id = :dest ORDER BY municipality_slug ASC LIMIT 1',
            [':dest' => $destinationId]
        );
        $municipality = str_replace('-', ' ', (string) ($row['municipality_slug'] ?? ''));
        if ($municipality !== '') {
            $campaign = viatorBrowseCampaignForMunicipality($municipality);
            $slug = trim((string) ($campaign['slug'] ?? ''));
            if ($slug !== '') {
                return $slug;
            }
        }
    }
    $global = viatorBrowseCampaignForMunicipality('');
    return trim((string) ($global['slug'] ?? 'viator-tours-pr'));
}

/**
 * Top products for one destination via /products/search.
 *
 * @return array<int,array> product summaries as returned by the API
 */
function viatorSearchDestinationProducts(int $destinationId, string $locale, string $campaignValue, int $count = 40): array
{
    $response = viatorApiRequest(
        'POST',
        '/products/search',
        $locale,
        ['campaign-value' => $campaignValue],
        [
            'filtering' => ['destination' => (string) $destinationId],
            'sorting' => ['sort' => 'TRAVELER_RATING', 'order' => 'DESCENDING'],
            'pagination' => ['start' => 1, 'count' => max(1, min($count, 50))],
            'currency' => 'USD',
        ]
    );
    $products = $response['data']['products'] ?? [];
    return is_array($products) ? array_values(array_filter($products, 'is_array')) : [];
}

/**
 * Cache a /products/search summary. Curated rows keep their richer detail
 * content; the sweep only backfills matching metadata on them.
 */
function viatorUpsertSearchProduct(array $product, string $locale, string $campaignValue): void
{
    $locale = viatorNormalizeLocale($locale);
    $productCode = trim((string) ($product['productCode'] ?? ''));
    if ($productCode === '') {
        return;
    }

    $tagsJson = json_encode(array_values(array_filter(array_map('intval', (array) ($product['tags'] ?? [])))));
    $destinationsJson = json_encode(viatorExtractDestinationIds($product));
    $productUrl = trim((string) ($product['productUrl'] ?? ''));
    $productUrl = viatorProductUrlIsValid($productUrl) ? $productUrl : '';

    $existing = queryOne(
        'SELECT source FROM viator_products WHERE product_code = :code AND locale = :locale LIMIT 1',
        [':code' => $productCode, ':locale' => $locale]
    );
    if (is_array($existing) && (string) ($existing['source'] ?? '') === 'curated_sync') {
        execute(
            'UPDATE viator_products
             SET tags_json = :tags, destination_ids_json = :destinations
             WHERE product_code = :code AND locale = :locale',
            [':tags' => $tagsJson, ':destinations' => $destinationsJson, ':code' => $productCode, ':locale' => $locale]
        );
        return;
    }

    [$durationMin, $durationMax] = viatorExtractDuration($product);
    $pricing = viatorExtractPrice($product);
    $reviews = is_array($product['reviews'] ?? null) ? $product['reviews'] : [];

    execute(
        'INSERT INTO viator_products
            (product_code, locale, status, title, description, image_url, rating, review_count,
             duration_minutes_min, duration_minutes_max, departure_summary, free_cancellation,
             price_from, currency, viator_last_updated_at, fetched_at, raw_json,
             product_url, campaign_value, tags_json, destination_ids_json, source)
         VALUES
            (:code, :locale, "ACTIVE", :title, :description, :image_url, :rating, :review_count,
             :duration_min, :duration_max, "", :free_cancellation,
             :price_from, :currency, "", CURRENT_TIMESTAMP, :raw_json,
             :product_url, :campaign_value, :tags, :destinations, "catalog_search")
         ON CONFLICT(product_code, locale) DO UPDATE SET
            status = "ACTIVE", title = excluded.title, description = excluded.description,
            image_url = excluded.image_url, rating = excluded.rating, review_count = excluded.review_count,
            duration_minutes_min = excluded.duration_minutes_min,
            duration_minutes_max = excluded.duration_minutes_max,
            free_cancellation = excluded.free_cancellation,
            price_from = excluded.price_from, currency = excluded.currency,
            fetched_at = CURRENT_TIMESTAMP, raw_json = excluded.raw_json,
            product_url = excluded.product_url, campaign_value = excluded.campaign_value,
            tags_json = excluded.tags_json, destination_ids_json = excluded.destination_ids_json,
            source = "catalog_search"',
        [
            ':code' => $productCode,
            ':locale' => $locale,
            ':title' => trim((string) ($product['title'] ?? '')),
            ':description' => trim((string) ($product['description'] ?? '')),
            ':image_url' => viatorExtractImageUrl($product),
            ':rating' => is_numeric($reviews['combinedAverageRating'] ?? null) ? (float) $reviews['combinedAverageRating'] : null,
            ':review_count' => max(0, (int) ($reviews['totalReviews'] ?? 0)),
            ':duration_min' => $durationMin,
            ':duration_max' => $durationMax,
            ':free_cancellation' => viatorExtractFreeCancellation($product),
            ':price_from' => $pricing['price'],
            ':currency' => $pricing['currency'],
            ':raw_json' => json_encode($product, JSON_UNESCAPED_SLASHES),
            ':product_url' => $productUrl,
            ':campaign_value' => $campaignValue,
            ':tags' => $tagsJson,
            ':destinations' => $destinationsJson,
        ]
    );
}

/* ---------------------------------------------------------------------------
 * Beach auto-matching
 * ------------------------------------------------------------------------ */

/** beach_tags vocabulary -> keywords looked for in product titles/tag names */
function viatorBeachTagKeywords(): array
{
    return [
        'snorkeling' => ['snorkel'],
        'surfing' => ['surf'],
        'diving' => ['scuba', 'diving'],
        'fishing' => ['fishing'],
        'kayaking' => ['kayak'],
    ];
}

/**
 * Beach-name tokens too generic to identify a specific beach in a product
 * title (common English/Spanish words that appear in unrelated tour names).
 */
function viatorGenericNameTokens(): array
{
    return [
        'seven', 'sandy', 'steps', 'table', 'rock', 'rocks', 'stone', 'tower',
        'light', 'house', 'shore', 'coast', 'costa', 'wilderness', 'middles',
        'sunset', 'sunrise', 'coco', 'palm', 'palmas', 'shell', 'coral',
        'turtle', 'pelican', 'angel', 'paradise', 'paraiso', 'escondida',
        'escondido', 'hidden', 'secret', 'crash', 'jungle', 'river', 'mango',
        // Generic Spanish coastal/geographic words that appear in unrelated
        // tour titles ("Puerto Rico", cave tours, swimming holes).
        'puerto', 'rico', 'cueva', 'cuevas', 'charco', 'charcos', 'poza',
        'pozas', 'boca', 'caleta', 'ensenada', 'laguna', 'playas', 'beaches',
        'arena', 'arenas',
        // Common words that produced audited false positives (hotel taxis,
        // "tour in Spanish", Condado Lagoon on other lagoons, ...).
        'public', 'private', 'hotel', 'resort', 'villa', 'paseo', 'spanish',
        'tropical', 'lagoon', 'mosquito', 'fishing', 'harbor', 'harbour',
        'oceanfront',
    ];
}

/** @return array{phrase: string, tokens: array<string>} */
function viatorBeachNameSignature(array $beach): array
{
    $stopwords = [
        'beach', 'playa', 'playita', 'balneario', 'bahia', 'bay', 'punta',
        'point', 'cayo', 'cay', 'isla', 'island', 'the', 'and', 'del', 'de',
        'la', 'el', 'los', 'las', 'san', 'santa', 'santo', 'norte', 'sur',
        'este', 'oeste', 'east', 'west', 'north', 'south', 'grande', 'chica',
        'chico', 'vieja', 'viejo', 'nueva', 'nuevo', 'negra', 'negro',
        'blanca', 'blanco', 'verde', 'azul', 'mar', 'sea', 'entry', 'access',
        'area', 'park', 'parque', 'reserva', 'reserve', 'natural', 'zone',
    ];

    $tokens = array_values(array_filter(
        explode('-', slugify((string) ($beach['name'] ?? ''))),
        static fn(string $token): bool => $token !== '' && !in_array($token, $stopwords, true)
    ));

    $phrase = count($tokens) >= 2 ? implode('-', $tokens) : '';
    $generic = viatorGenericNameTokens();
    $tokens = array_values(array_filter(
        $tokens,
        static fn(string $token): bool => strlen($token) >= 5 && !in_array($token, $generic, true)
    ));

    return ['phrase' => $phrase, 'tokens' => $tokens];
}

/**
 * City-level destination coordinates for the geographic-consistency guard.
 * Destinations with implausible (non-PR) coordinates are ignored.
 *
 * @return array<int,array{0:float,1:float}>
 */
function viatorCityDestinationCoords(): array
{
    static $coords = null;
    if ($coords === null) {
        $coords = [];
        if (viatorTableExists('viator_destinations')) {
            $rows = query(
                'SELECT destination_id, latitude, longitude FROM viator_destinations
                 WHERE destination_id != ' . VIATOR_PUERTO_RICO_DESTINATION_ID . '
                   AND latitude IS NOT NULL AND longitude IS NOT NULL'
            );
            foreach (is_array($rows) ? $rows : [] as $row) {
                $lat = (float) $row['latitude'];
                $lng = (float) $row['longitude'];
                if ($lat >= 17.4 && $lat <= 18.8 && $lng >= -68.0 && $lng <= -65.0) {
                    $coords[(int) $row['destination_id']] = [$lat, $lng];
                }
            }
        }
    }
    return $coords;
}

/** Approximate distance in km between two lat/lng points (PR-scale). */
function viatorDistanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $x = ($lng2 - $lng1) * cos(deg2rad(($lat1 + $lat2) / 2));
    $y = $lat2 - $lat1;
    return sqrt($x * $x + $y * $y) * 111.32;
}

/** @return array{score: float, reasons: array<string>} */
function viatorScoreProductForBeach(array $beach, array $productRow, array $tagNamesById, ?array $beachTags = null): array
{
    $score = 0.0;
    $reasons = [];

    $titleSlug = '-' . slugify((string) ($productRow['title'] ?? '')) . '-';
    $signature = viatorBeachNameSignature($beach);

    $nameScore = 0.0;
    $nameReason = '';
    if ($signature['phrase'] !== '' && str_contains($titleSlug, '-' . $signature['phrase'] . '-')) {
        $nameScore = 60;
        $nameReason = 'name_phrase:' . $signature['phrase'];
    } else {
        foreach ($signature['tokens'] as $token) {
            if (str_contains($titleSlug, '-' . $token . '-')) {
                $nameScore = 45;
                $nameReason = 'name_token:' . $token;
                break;
            }
        }
    }

    $municipality = (string) ($beach['municipality'] ?? '');
    $muniSlug = slugify($municipality);
    $muniInTitle = strlen($muniSlug) >= 4 && str_contains($titleSlug, '-' . $muniSlug . '-');
    $muniDestination = viatorMunicipalityDestinationId($municipality);
    $productDestinations = array_map('intval', (array) json_decode((string) ($productRow['destination_ids_json'] ?? '[]'), true));

    // Geographic-consistency guard: a shared name is not a match when the
    // product operates around a distant destination (Playa Icacos in Yabucoa
    // vs Cayo Icacos charters out of Fajardo). A same-municipality title
    // vouches for the match even when the tour departs from farther away.
    if ($nameScore > 0 && !$muniInTitle) {
        $beachLat = (float) ($beach['lat'] ?? 0);
        $beachLng = (float) ($beach['lng'] ?? 0);
        $cityCoords = viatorCityDestinationCoords();
        $checked = false;
        $near = false;
        if ($beachLat !== 0.0 && $beachLng !== 0.0) {
            foreach ($productDestinations as $destId) {
                if (!isset($cityCoords[$destId])) {
                    continue;
                }
                $checked = true;
                if (viatorDistanceKm($beachLat, $beachLng, $cityCoords[$destId][0], $cityCoords[$destId][1]) <= 20.0) {
                    $near = true;
                    break;
                }
            }
        }
        if ($checked && !$near) {
            $nameScore = 0.0;
            $nameReason = '';
        }
    }

    if ($nameScore > 0) {
        $score += $nameScore;
        $reasons[] = $nameReason;
    }

    if ($muniDestination !== null && in_array($muniDestination, $productDestinations, true)) {
        $score += 25;
        $reasons[] = 'destination:' . $muniDestination;
    }
    if ($muniInTitle) {
        $score += 20;
        $reasons[] = 'municipality_in_title:' . $muniSlug;
    }

    if ($beachTags === null) {
        $beachTags = array_map(
            static fn(array $row): string => (string) $row['tag'],
            query('SELECT tag FROM beach_tags WHERE beach_id = :id', [':id' => (string) ($beach['id'] ?? '')]) ?: []
        );
    }
    $productTagNames = [];
    foreach (array_map('intval', (array) json_decode((string) ($productRow['tags_json'] ?? '[]'), true)) as $tagId) {
        if (isset($tagNamesById[$tagId])) {
            $productTagNames[] = $tagNamesById[$tagId];
        }
    }
    $haystack = strtolower((string) ($productRow['title'] ?? '')) . ' ' . implode(' ', $productTagNames);

    $tagScore = 0.0;
    foreach (viatorBeachTagKeywords() as $beachTag => $keywords) {
        if (!in_array($beachTag, $beachTags, true)) {
            continue;
        }
        foreach ($keywords as $keyword) {
            if (str_contains($haystack, $keyword)) {
                $tagScore += 10;
                $reasons[] = 'tag:' . $beachTag;
                break;
            }
        }
    }
    $score += min($tagScore, 30);

    $rating = is_numeric($productRow['rating'] ?? null) ? (float) $productRow['rating'] : 0.0;
    if ($rating >= 4.5 && (int) ($productRow['review_count'] ?? 0) >= 50) {
        $score += 5;
        $reasons[] = 'highly_rated';
    }

    return ['score' => $score, 'reasons' => $reasons];
}

/**
 * Catalog rows eligible to appear on a beach page: ACTIVE, carrying the
 * exact attributed product URL and an official product photo, and not a
 * transportation product (airport/hotel/port transfers, bus services,
 * private drivers are logistics, not experiences).
 *
 * @return array<int,array>
 */
function viatorEligibleCatalogProducts(): array
{
    $candidates = query(
        'SELECT product_code, title, rating, review_count, tags_json, destination_ids_json
         FROM viator_products
         WHERE locale = "en" AND status = "ACTIVE" AND product_url != ""
           AND image_url IS NOT NULL AND image_url != ""'
    );
    $candidates = is_array($candidates) ? $candidates : [];

    $transportTagIds = [];
    if (viatorTableExists('viator_tags')) {
        foreach (query(
            'SELECT tag_id FROM viator_tags
             WHERE name_en LIKE "%Transfer%" OR name_en LIKE "%Bus Service%"
                OR name_en LIKE "%Private Driver%" OR name_en LIKE "%Rail Service%"'
        ) as $row) {
            $transportTagIds[(int) $row['tag_id']] = true;
        }
    }
    return array_values(array_filter($candidates, static function (array $candidate) use ($transportTagIds): bool {
        if (preg_match('/\b(airport|transfer|transfers)\b/i', (string) $candidate['title'])) {
            return false;
        }
        foreach (array_map('intval', (array) json_decode((string) ($candidate['tags_json'] ?? '[]'), true)) as $tagId) {
            if (isset($transportTagIds[$tagId])) {
                return false;
            }
        }
        return true;
    }));
}

/**
 * Whether a product title reads like a water/beach experience. Used only to
 * break ties in the fallback fill so a snorkeling trip outranks an
 * equally-near city tasting or bus tour on a beach page.
 */
function viatorProductTitleIsBeachy(string $title): bool
{
    return (bool) preg_match(
        '/\b(beach|snorkel\w*|catamaran|sail\w*|boat|kayak\w*|paddle\w*|surf\w*|dive|diving|island|cays?|bio\s?bay|bioluminescent|reef|turtle|whale|coastal|cliff|waterfall|river|cave)\b/i',
        $title
    );
}

/**
 * Distance from a beach to the closest city-level destination a product
 * operates around, or null when none of the product's destinations carry
 * usable coordinates (island-wide products).
 */
function viatorNearestDestinationKm(array $beach, array $productRow): ?float
{
    $beachLat = (float) ($beach['lat'] ?? 0);
    $beachLng = (float) ($beach['lng'] ?? 0);
    if ($beachLat === 0.0 && $beachLng === 0.0) {
        return null;
    }

    $cityCoords = viatorCityDestinationCoords();
    $nearest = null;
    foreach (array_map('intval', (array) json_decode((string) ($productRow['destination_ids_json'] ?? '[]'), true)) as $destId) {
        if (!isset($cityCoords[$destId])) {
            continue;
        }
        $km = viatorDistanceKm($beachLat, $beachLng, $cityCoords[$destId][0], $cityCoords[$destId][1]);
        if ($nearest === null || $km < $nearest) {
            $nearest = $km;
        }
    }
    return $nearest;
}

/**
 * Recompute viator_beach_products for every published beach from the cached
 * catalog. Editor-blocked matches are preserved and never re-surfaced.
 *
 * Beaches where nothing clears the relevance threshold receive one
 * distance-ranked fallback product so every beach page can show a direct,
 * bookable experience instead of only the regional browse link.
 *
 * @return array{beaches_matched: int, matches: int, fallback_filled: int}
 */
function viatorRebuildBeachMatches(float $threshold = 35.0, int $maxPerBeach = 2): array
{
    $tagNamesById = [];
    if (viatorTableExists('viator_tags')) {
        foreach (query('SELECT tag_id, name_en FROM viator_tags WHERE name_en != ""') as $row) {
            $tagNamesById[(int) $row['tag_id']] = strtolower((string) $row['name_en']);
        }
    }

    $candidates = viatorEligibleCatalogProducts();

    $blocked = [];
    foreach (query('SELECT beach_id, product_code FROM viator_beach_products WHERE status = "blocked"') as $row) {
        $blocked[$row['beach_id'] . '|' . $row['product_code']] = true;
    }

    execute('DELETE FROM viator_beach_products WHERE status = "active"');

    $beaches = query('SELECT id, name, municipality, lat, lng FROM beaches WHERE publish_status = "published"');
    $beachesMatched = 0;
    $totalMatches = 0;
    $uncovered = [];

    $tagsByBeach = [];
    foreach (query('SELECT beach_id, tag FROM beach_tags') as $row) {
        $tagsByBeach[(string) $row['beach_id']][] = (string) $row['tag'];
    }

    foreach (is_array($beaches) ? $beaches : [] as $beach) {
        $scored = [];
        $beachTags = $tagsByBeach[(string) $beach['id']] ?? [];
        foreach ($candidates as $candidate) {
            if (isset($blocked[$beach['id'] . '|' . $candidate['product_code']])) {
                continue;
            }
            $result = viatorScoreProductForBeach($beach, $candidate, $tagNamesById, $beachTags);
            // Geography alone (destination / municipality-in-title) never
            // qualifies: a match needs beach-name or activity-tag relevance,
            // otherwise generic city tours would attach to every beach.
            $relevant = (bool) array_filter(
                $result['reasons'],
                static fn(string $reason): bool => str_starts_with($reason, 'name_') || str_starts_with($reason, 'tag:')
            );
            if ($relevant && $result['score'] >= $threshold) {
                $scored[] = [
                    'product_code' => (string) $candidate['product_code'],
                    'score' => $result['score'],
                    'reasons' => $result['reasons'],
                ];
            }
        }
        if ($scored === []) {
            $uncovered[] = $beach;
            continue;
        }

        usort($scored, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);
        $scored = array_slice($scored, 0, max(1, $maxPerBeach));

        foreach ($scored as $order => $match) {
            execute(
                'INSERT INTO viator_beach_products
                    (beach_id, product_code, score, match_reasons, status, display_order, matched_at, updated_at)
                 VALUES (:beach_id, :code, :score, :reasons, "active", :order, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                 ON CONFLICT(beach_id, product_code) DO UPDATE SET
                    score = excluded.score, match_reasons = excluded.match_reasons,
                    display_order = excluded.display_order, updated_at = CURRENT_TIMESTAMP',
                [
                    ':beach_id' => (string) $beach['id'],
                    ':code' => $match['product_code'],
                    ':score' => $match['score'],
                    ':reasons' => json_encode($match['reasons'], JSON_UNESCAPED_SLASHES),
                    ':order' => $order,
                ]
            );
            $totalMatches++;
        }
        $beachesMatched++;
    }

    // Fallback fill: beaches with no relevant match get the closest, best
    // reviewed product so their page still offers a direct experience.
    // Distance to the product's nearest city-level destination ranks
    // candidates in 10 km bands; island-wide products without city
    // coordinates sit between genuinely nearby and far-away ones. Within a
    // band: social proof (25+ reviews), water/beach fit, rating, reviews.
    $pool = [];
    foreach ($candidates as $candidate) {
        $pool[] = [
            'candidate' => $candidate,
            'beachy' => viatorProductTitleIsBeachy((string) ($candidate['title'] ?? '')),
            'rating' => is_numeric($candidate['rating'] ?? null) ? (float) $candidate['rating'] : 0.0,
            'reviews' => max(0, (int) ($candidate['review_count'] ?? 0)),
        ];
    }

    $fallbackFilled = 0;
    foreach ($uncovered as $beach) {
        $best = null;
        $bestRank = null;
        $bestKm = null;
        foreach ($pool as $entry) {
            $candidate = $entry['candidate'];
            if (isset($blocked[$beach['id'] . '|' . $candidate['product_code']])) {
                continue;
            }
            $km = viatorNearestDestinationKm($beach, $candidate);
            $rank = [
                (int) floor(($km ?? 75.0) / 10.0),
                $entry['reviews'] >= 25 ? 0 : 1,
                $entry['beachy'] ? 0 : 1,
                -$entry['rating'],
                -$entry['reviews'],
            ];
            if ($bestRank === null || ($rank <=> $bestRank) < 0) {
                $bestRank = $rank;
                $best = $candidate;
                $bestKm = $km;
            }
        }
        if ($best === null) {
            continue;
        }

        $reasons = [
            'nearby_fallback',
            $bestKm !== null ? 'distance_km:' . round($bestKm, 1) : 'distance:unknown',
        ];
        execute(
            'INSERT INTO viator_beach_products
                (beach_id, product_code, score, match_reasons, status, display_order, matched_at, updated_at)
             VALUES (:beach_id, :code, 0, :reasons, "active", 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
             ON CONFLICT(beach_id, product_code) DO UPDATE SET
                score = excluded.score, match_reasons = excluded.match_reasons,
                display_order = excluded.display_order, updated_at = CURRENT_TIMESTAMP',
            [
                ':beach_id' => (string) $beach['id'],
                ':code' => (string) $best['product_code'],
                ':reasons' => json_encode($reasons, JSON_UNESCAPED_SLASHES),
            ]
        );
        $fallbackFilled++;
    }

    return ['beaches_matched' => $beachesMatched, 'matches' => $totalMatches, 'fallback_filled' => $fallbackFilled];
}

/**
 * Auto-matched, cache-hydrated products for a beach page.
 *
 * @return array<int,array> viator_products rows + score/display_order
 */
function viatorAutoMatchedProductsForBeach(string $beachId, string $locale, int $limit, array $excludeProductCodes = []): array
{
    if ($limit < 1 || !viatorTableExists('viator_beach_products') || !viatorTableExists('viator_products')) {
        return [];
    }

    $rows = query(
        'SELECT p.*, m.score, m.display_order AS match_order, m.match_reasons
         FROM viator_beach_products m
         INNER JOIN viator_products p ON p.product_code = m.product_code AND p.locale = :locale
         WHERE m.beach_id = :beach_id
           AND m.status = "active"
           AND p.status = "ACTIVE"
           AND p.product_url != ""
           AND p.image_url IS NOT NULL AND p.image_url != ""
         ORDER BY m.display_order ASC, m.score DESC',
        [':beach_id' => $beachId, ':locale' => viatorNormalizeLocale($locale)]
    );

    $result = [];
    foreach (is_array($rows) ? $rows : [] as $row) {
        if (in_array((string) $row['product_code'], $excludeProductCodes, true)) {
            continue;
        }
        $result[] = $row;
        if (count($result) >= $limit) {
            break;
        }
    }
    return $result;
}

/**
 * Product-level attributed URL fallback for /go when no campaign-scoped link
 * exists (auto-matched and guide placements).
 */
function viatorProductLevelUrl(string $productCode, string $locale): string
{
    if (!viatorTableExists('viator_products')) {
        return '';
    }
    $row = queryOne(
        'SELECT product_url FROM viator_products
         WHERE product_code = :code AND locale = :locale AND status = "ACTIVE"
         LIMIT 1',
        [':code' => $productCode, ':locale' => viatorNormalizeLocale($locale)]
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
