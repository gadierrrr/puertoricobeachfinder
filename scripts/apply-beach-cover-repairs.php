<?php
/**
 * Preview or apply a reviewed Google Places beach-photo repair manifest.
 *
 * Preview:
 *   php scripts/apply-beach-cover-repairs.php \
 *     --manifest=/path/repairs.json --preview-dir=/tmp/beach-previews
 *
 * Apply:
 *   php scripts/apply-beach-cover-repairs.php \
 *     --manifest=/path/repairs.json --apply
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/image-optimizer.php';

$options = getopt('', ['manifest:', 'preview-dir::', 'apply', 'limit::', 'resume::']);
$manifestPath = $options['manifest'] ?? '';
$previewDir = $options['preview-dir'] ?? '';
$apply = isset($options['apply']);
$limit = isset($options['limit']) ? max(1, (int) $options['limit']) : null;
$resume = isset($options['resume']) ? max(0, (int) $options['resume']) : 0;

if ($manifestPath === '' || (!$apply && $previewDir === '')) {
    fwrite(STDERR, "Required: --manifest=... and either --preview-dir=... or --apply\n");
    exit(1);
}

$repairs = json_decode((string) file_get_contents($manifestPath), true);
if (!is_array($repairs)) {
    fwrite(STDERR, "Manifest must be a JSON array.\n");
    exit(1);
}
$repairs = array_slice($repairs, $resume, $limit);

if ($previewDir !== '' && !is_dir($previewDir)
    && !mkdir($previewDir, 0755, true) && !is_dir($previewDir)) {
    fwrite(STDERR, "Unable to create preview directory: {$previewDir}\n");
    exit(1);
}

$apiKey = envRequire('GOOGLE_MAPS_API_KEY');
$failures = [];

foreach ($repairs as $index => $repair) {
    $name = (string) ($repair['name'] ?? 'Unknown beach');
    $id = (string) ($repair['id'] ?? '');
    $slug = (string) ($repair['slug'] ?? '');
    $placeId = (string) ($repair['place_id'] ?? '');
    $photoName = (string) ($repair['photo_name'] ?? '');

    if ($id === '' || $slug === '' || $placeId === ''
        || !preg_match('#^places/[^/]+/photos/[^/]+$#', $photoName)) {
        $failures[] = "{$name}: invalid manifest entry";
        continue;
    }

    $beach = queryOne(
        'SELECT id, slug, name FROM beaches WHERE id = :id',
        [':id' => $id]
    );
    if (!$beach || $beach['slug'] !== $slug) {
        $failures[] = "{$name}: beach ID/slug did not match the database";
        continue;
    }

    $photo = downloadPlacesPhoto($photoName, $apiKey);
    if (isset($photo['error'])) {
        $failures[] = "{$name}: {$photo['error']}";
        continue;
    }

    $extension = match ($photo['mime_type']) {
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => 'jpg',
    };
    $tempPath = tempnam(sys_get_temp_dir(), 'beach-photo-') . '.' . $extension;
    file_put_contents($tempPath, $photo['bytes']);

    if (!$apply) {
        $previewPath = rtrim($previewDir, '/') . '/' . $slug . '.' . $extension;
        file_put_contents($previewPath, $photo['bytes']);
        @unlink($tempPath);
        printf("[%d/%d] Previewed %s\n", $index + 1, count($repairs), $name);
        continue;
    }

    $image = optimizeImage($tempPath, $slug, 'google-places-' . $placeId . '.' . $extension);
    @unlink($tempPath);
    if (isset($image['error']) || empty($image['success'])) {
        $failures[] = "{$name}: " . ($image['error'] ?? 'image optimization failed');
        continue;
    }

    $database = getDB();
    $database->exec('BEGIN IMMEDIATE');
    try {
        $database->exec("UPDATE beach_images SET is_cover = 0 WHERE beach_id = '"
            . SQLite3::escapeString($id) . "'");

        $maxPosition = queryOne(
            'SELECT MAX(position) AS max_position FROM beach_images WHERE beach_id = :beach_id',
            [':beach_id' => $id]
        );
        $position = ((int) ($maxPosition['max_position'] ?? -1)) + 1;

        $inserted = execute(
            'INSERT INTO beach_images ('
            . 'beach_id, filename, original_filename, original_format, file_size, '
            . 'original_size, mime_type, width, height, position, is_cover, alt_text, '
            . 'optimization_savings, created_at, uploaded_by'
            . ') VALUES ('
            . ':beach_id, :filename, :original_filename, :original_format, :file_size, '
            . ':original_size, :mime_type, :width, :height, :position, 1, :alt_text, '
            . ':optimization_savings, datetime(\'now\'), NULL'
            . ')',
            [
                ':beach_id' => $id,
                ':filename' => $image['filename'],
                ':original_filename' => $image['original_filename'],
                ':original_format' => $image['original_format'],
                ':file_size' => $image['optimization']['optimized_size'],
                ':original_size' => $image['optimization']['original_size'],
                ':mime_type' => 'image/webp',
                ':width' => $image['width'],
                ':height' => $image['height'],
                ':position' => $position,
                ':alt_text' => $name . ' in ' . (string) ($repair['municipality'] ?? 'Puerto Rico'),
                ':optimization_savings' => $image['optimization']['savings_bytes'],
            ]
        );
        $updated = execute(
            'UPDATE beaches SET cover_image = :cover_image, place_id = :place_id, '
            . 'updated_at = datetime(\'now\') WHERE id = :id AND slug = :slug',
            [
                ':cover_image' => $image['urls']['medium'],
                ':place_id' => $placeId,
                ':id' => $id,
                ':slug' => $slug,
            ]
        );

        if (!$inserted || !$updated) {
            throw new RuntimeException('database update failed');
        }
        $database->exec('COMMIT');
        printf("[%d/%d] Repaired %s\n", $index + 1, count($repairs), $name);
    } catch (Throwable $error) {
        $database->exec('ROLLBACK');
        deleteImageFiles($image['filename']);
        $failures[] = "{$name}: {$error->getMessage()}";
    }
}

if ($failures) {
    fwrite(STDERR, "\nFailures:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

printf("Completed %d beach photo %s.\n", count($repairs), $apply ? 'repairs' : 'previews');

function downloadPlacesPhoto(string $photoName, string $apiKey): array
{
    $metadataUrl = 'https://places.googleapis.com/v1/' . $photoName . '/media'
        . '?maxWidthPx=1600&maxHeightPx=1600&skipHttpRedirect=true';
    $metadata = httpGet($metadataUrl, ['X-Goog-Api-Key: ' . $apiKey]);
    if (isset($metadata['error'])) {
        return $metadata;
    }
    $decoded = json_decode((string) $metadata['bytes'], true);
    $photoUri = $decoded['photoUri'] ?? null;
    if (!is_string($photoUri) || $photoUri === '') {
        return ['error' => 'Places photo response did not include photoUri'];
    }

    return httpGet($photoUri, [], true);
}

function httpGet(string $url, array $headers = [], bool $followRedirects = false): array
{
    $handle = curl_init($url);
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => $followRedirects,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'PuertoRicoBeachFinder/1.0',
    ]);
    $bytes = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $mimeType = (string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
    $error = curl_error($handle);
    curl_close($handle);

    if ($bytes === false || $status >= 400) {
        return ['error' => $error !== '' ? $error : "HTTP {$status}"];
    }

    return [
        'bytes' => $bytes,
        'mime_type' => strtolower(trim(explode(';', $mimeType)[0])),
    ];
}
