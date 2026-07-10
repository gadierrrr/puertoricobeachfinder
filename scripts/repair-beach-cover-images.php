<?php
/**
 * Discover and repair beach cover images with Google Places photos.
 *
 * Discovery usage:
 *   php scripts/repair-beach-cover-images.php \
 *     --targets=/path/to/targets.json --output=/path/to/discovery.json
 *
 * The targets file is a JSON array with beach IDs. Discovery writes the top
 * Places candidates, their photo resource names, and distance from the beach's
 * stored coordinates. It does not mutate the database.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../inc/db.php';

$options = getopt('', [
    'targets:',
    'output:',
    'limit::',
    'resume::',
]);

$targetsPath = $options['targets'] ?? '';
$outputPath = $options['output'] ?? '';
$limit = isset($options['limit']) ? max(1, (int) $options['limit']) : null;
$resume = isset($options['resume']) ? max(0, (int) $options['resume']) : 0;

if ($targetsPath === '' || $outputPath === '') {
    fwrite(STDERR, "Required: --targets=/path/targets.json --output=/path/discovery.json\n");
    exit(1);
}

$targets = json_decode((string) file_get_contents($targetsPath), true);
if (!is_array($targets)) {
    fwrite(STDERR, "Targets must be a JSON array.\n");
    exit(1);
}

$targetIds = [];
$targetConfig = [];
foreach ($targets as $target) {
    if (is_array($target) && !empty($target['id'])) {
        $targetIds[(string) $target['id']] = true;
        $targetConfig[(string) $target['id']] = $target;
    }
}

$beaches = [];
$result = getDB()->query(
    'SELECT id, slug, name, municipality, lat, lng, cover_image, place_id '
    . 'FROM beaches ORDER BY name COLLATE NOCASE'
);
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    if (isset($targetIds[$row['id']])) {
        $beaches[] = $row;
    }
}

if ($resume > 0) {
    $beaches = array_slice($beaches, $resume);
}
if ($limit !== null) {
    $beaches = array_slice($beaches, 0, $limit);
}

$apiKey = envRequire('GOOGLE_MAPS_API_KEY');
$discoveries = [];

foreach ($beaches as $index => $beach) {
    $query = trim((string) ($targetConfig[$beach['id']]['search_query'] ?? ''));
    if ($query === '') {
        $query = sprintf('%s, %s, Puerto Rico', $beach['name'], $beach['municipality']);
    }
    $body = [
        'textQuery' => $query,
        'pageSize' => 5,
        'locationBias' => [
            'circle' => [
                'center' => [
                    'latitude' => (float) $beach['lat'],
                    'longitude' => (float) $beach['lng'],
                ],
                'radius' => 10000.0,
            ],
        ],
    ];
    $response = placesPost(
        'places:searchText',
        $body,
        'places.id,places.displayName,places.formattedAddress,places.location,'
        . 'places.primaryType,places.types,places.photos',
        $apiKey
    );

    $candidates = [];
    foreach (($response['places'] ?? []) as $place) {
        $latitude = $place['location']['latitude'] ?? null;
        $longitude = $place['location']['longitude'] ?? null;
        $photos = [];
        foreach (array_slice($place['photos'] ?? [], 0, 10) as $photo) {
            if (!empty($photo['name'])) {
                $photos[] = [
                    'name' => $photo['name'],
                    'width_px' => $photo['widthPx'] ?? null,
                    'height_px' => $photo['heightPx'] ?? null,
                    'author_attributions' => $photo['authorAttributions'] ?? [],
                ];
            }
        }
        $candidates[] = [
            'place_id' => $place['id'] ?? null,
            'name' => $place['displayName']['text'] ?? null,
            'formatted_address' => $place['formattedAddress'] ?? null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'distance_m' => ($latitude !== null && $longitude !== null)
                ? round(haversineMeters(
                    (float) $beach['lat'],
                    (float) $beach['lng'],
                    (float) $latitude,
                    (float) $longitude
                ))
                : null,
            'primary_type' => $place['primaryType'] ?? null,
            'types' => $place['types'] ?? [],
            'photos' => $photos,
        ];
    }

    $discoveries[] = [
        'beach' => $beach,
        'query' => $query,
        'candidates' => $candidates,
        'error' => $response['error'] ?? null,
    ];

    printf(
        "[%d/%d] %s: %d candidate(s)\n",
        $index + 1,
        count($beaches),
        $beach['name'],
        count($candidates)
    );
    usleep(150000);
}

$outputDir = dirname($outputPath);
if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
    fwrite(STDERR, "Unable to create output directory: {$outputDir}\n");
    exit(1);
}

file_put_contents(
    $outputPath,
    json_encode($discoveries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    . "\n"
);

printf("Wrote %d discoveries to %s\n", count($discoveries), $outputPath);

function placesPost(string $endpoint, array $body, string $fieldMask, string $apiKey): array
{
    $handle = curl_init('https://places.googleapis.com/v1/' . $endpoint);
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Goog-Api-Key: ' . $apiKey,
            'X-Goog-FieldMask: ' . $fieldMask,
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $error = curl_error($handle);
    curl_close($handle);

    if ($response === false || $status >= 400) {
        return [
            'error' => [
                'status' => $status,
                'message' => $error !== '' ? $error : (string) $response,
            ],
        ];
    }

    $decoded = json_decode((string) $response, true);
    return is_array($decoded) ? $decoded : ['error' => ['message' => 'Invalid JSON response']];
}

function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $earthRadius = 6371000.0;
    $latDelta = deg2rad($lat2 - $lat1);
    $lngDelta = deg2rad($lng2 - $lng1);
    $firstLat = deg2rad($lat1);
    $secondLat = deg2rad($lat2);
    $a = sin($latDelta / 2) ** 2
        + cos($firstLat) * cos($secondLat) * sin($lngDelta / 2) ** 2;
    return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
}
