<?php
/**
 * EnrichmentProcessor - Batch processes bare beaches with AI-generated structured data
 *
 * Handles API calls, validation, database writes, checkpointing, and resumption.
 */

require_once __DIR__ . '/../../inc/constants.php';

class EnrichmentProcessor {

    private $apiKey;
    private $model = 'claude-sonnet-4-5-20250929';
    private $maxTokens = 2000;
    private $lastRequestTime = 0;
    private $minDelay = 2;
    private $promptBuilder;
    private $checkpointFile;
    private $logFile;
    private $saveInterval = 10;

    // Banned generic phrases for quality check
    private const BANNED_PHRASES = [
        'crystal clear waters',
        'hidden gem',
        'paradise',
        'pristine',
        'breathtaking',
        'slice of heaven',
        'tropical paradise',
        'untouched'
    ];

    // Valid access labels
    private const VALID_ACCESS_LABELS = [
        'short path',
        '10-min walk',
        'moderate hike',
        'difficult hike'
    ];

    // Valid tip categories
    private const VALID_TIP_CATEGORIES = [
        'Timing', 'Safety', 'Equipment', 'Parking', 'Food',
        'Local Custom', 'Photography', 'Budget'
    ];

    public function __construct(string $apiKey) {
        $this->apiKey = $apiKey;
        $this->promptBuilder = new EnrichmentPromptBuilder();

        $scriptsDir = dirname(__DIR__);
        $this->checkpointFile = $scriptsDir . '/enrichment-checkpoint.json';
        $this->logFile = $scriptsDir . '/enrichment.log';
    }

    /**
     * Process beaches in batches
     */
    public function processBatches(array $options = []): array {
        $singleBeachId = $options['beach_id'] ?? null;
        $startBeachId = $options['start_beach_id'] ?? null;
        $batchSize = $options['batch_size'] ?? 50;
        $dryRun = $options['dry_run'] ?? false;

        $stats = [
            'total' => 0,
            'processed' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
            'start_time' => time()
        ];

        $checkpoint = $this->loadCheckpoint();

        if ($singleBeachId) {
            $beaches = $this->getSingleBeach($singleBeachId);
        } else {
            $beaches = $this->getBareBeaches($startBeachId, $checkpoint);
        }

        $stats['total'] = count($beaches);
        $this->log("Starting enrichment: {$stats['total']} bare beaches to process");

        if ($stats['total'] === 0) {
            $this->log("No bare beaches found to enrich");
            $stats['end_time'] = time();
            $stats['duration'] = 0;
            return $stats;
        }

        $batches = array_chunk($beaches, $batchSize);
        $batchNum = 1;

        foreach ($batches as $batch) {
            $this->log("Batch {$batchNum}/" . count($batches) . " (" . count($batch) . " beaches)");

            foreach ($batch as $beach) {
                try {
                    $result = $this->processBeach($beach, $dryRun);
                    $stats['processed']++;

                    if ($result['success']) {
                        $stats['succeeded']++;
                        $tagCount = count($result['data']['tags'] ?? []);
                        $amenityCount = count($result['data']['amenities'] ?? []);
                        $this->log("  OK {$beach['name']} — {$tagCount} tags, {$amenityCount} amenities");
                    } else {
                        $stats['failed']++;
                        $this->log("  FAIL {$beach['name']} — {$result['error']}", 'ERROR');
                        $stats['errors'][] = [
                            'beach_id' => $beach['id'],
                            'beach_name' => $beach['name'],
                            'error' => $result['error']
                        ];
                    }

                    if ($stats['processed'] % $this->saveInterval === 0) {
                        $this->saveCheckpoint([
                            'last_beach_id' => $beach['id'],
                            'last_beach_name' => $beach['name'],
                            'processed' => $stats['processed'],
                            'timestamp' => date('Y-m-d H:i:s')
                        ]);
                        $this->log("  Checkpoint saved ({$stats['processed']} processed)");
                    }

                } catch (Exception $e) {
                    $stats['failed']++;
                    $this->log("  EXCEPTION {$beach['name']}: " . $e->getMessage(), 'ERROR');
                    $stats['errors'][] = [
                        'beach_id' => $beach['id'],
                        'beach_name' => $beach['name'],
                        'error' => $e->getMessage()
                    ];
                }
            }

            $batchNum++;
            if ($batchNum <= count($batches)) {
                sleep(2);
            }
        }

        $stats['end_time'] = time();
        $stats['duration'] = $stats['end_time'] - $stats['start_time'];

        if ($stats['failed'] === 0 && !$singleBeachId) {
            $this->clearCheckpoint();
        }

        return $stats;
    }

    /**
     * Process a single beach: call API, validate, save
     */
    private function processBeach(array $beach, bool $dryRun): array {
        $this->enforceRateLimit();

        $prompt = $this->promptBuilder->buildPrompt($beach);
        $response = $this->callClaudeAPI($prompt);
        $data = $this->parseResponse($response);

        $validation = $this->validate($data, $beach['name']);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => 'Validation: ' . implode('; ', $validation['errors']),
                'data' => $data
            ];
        }

        if (!$dryRun) {
            $this->saveToDatabase($beach['id'], $data);
        }

        return ['success' => true, 'data' => $data];
    }

    /**
     * Get bare beaches (no tags, no amenities, no field data)
     */
    private function getBareBeaches(?string $startId, ?array $checkpoint): array {
        require_once __DIR__ . '/../../inc/db.php';

        $sql = "SELECT b.id, b.name, b.municipality, b.lat, b.lng, b.description
                FROM beaches b
                WHERE b.id NOT IN (SELECT DISTINCT beach_id FROM beach_tags)
                  AND (b.best_time IS NULL OR b.best_time = '')";
        $params = [];

        if ($startId) {
            $sql .= " AND b.id >= ?";
            $params[] = $startId;
        } elseif ($checkpoint && isset($checkpoint['last_beach_id'])) {
            $sql .= " AND b.id > ?";
            $params[] = $checkpoint['last_beach_id'];
        }

        $sql .= " ORDER BY b.id";

        return query($sql, $params);
    }

    /**
     * Get a single beach by ID
     */
    private function getSingleBeach(string $beachId): array {
        require_once __DIR__ . '/../../inc/db.php';
        $beach = queryOne(
            "SELECT id, name, municipality, lat, lng, description FROM beaches WHERE id = ?",
            [$beachId]
        );

        if (!$beach) {
            throw new Exception("Beach not found: {$beachId}");
        }

        return [$beach];
    }

    /**
     * Call Claude API
     */
    private function callClaudeAPI(string $prompt): string {
        $url = 'https://api.anthropic.com/v1/messages';

        $payload = [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ];

        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: 2023-06-01'
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL error: {$error}");
        }

        if ($httpCode === 429) {
            // Rate limited — wait and retry once
            $this->log("  Rate limited, waiting 30s...", 'WARN');
            sleep(30);
            return $this->callClaudeAPI($prompt);
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? 'Unknown error';
            throw new Exception("API error (HTTP {$httpCode}): {$errorMsg}");
        }

        $data = json_decode($response, true);

        if (!isset($data['content'][0]['text'])) {
            throw new Exception("Unexpected API response structure");
        }

        return $data['content'][0]['text'];
    }

    /**
     * Parse API response into structured data
     */
    private function parseResponse(string $response): array {
        $cleaned = preg_replace('/^```json\s*/m', '', $response);
        $cleaned = preg_replace('/^```\s*/m', '', $cleaned);
        $cleaned = trim($cleaned);

        $data = json_decode($cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON parse error: " . json_last_error_msg() . " — " . substr($response, 0, 300));
        }

        return $data;
    }

    /**
     * Validate enrichment data against controlled vocabularies and quality rules
     */
    private function validate(array $data, string $beachName): array {
        $errors = [];
        $warnings = [];

        // Tags validation
        $tags = $data['tags'] ?? [];
        if (!is_array($tags) || count($tags) < 3 || count($tags) > 6) {
            $errors[] = "Tags: need 3-6, got " . count($tags);
        }
        foreach ($tags as $tag) {
            if (!in_array($tag, TAGS)) {
                $errors[] = "Invalid tag: {$tag}";
            }
        }
        if (count($tags) !== count(array_unique($tags))) {
            $errors[] = "Duplicate tags";
        }
        if (in_array('calm-waters', $tags) && in_array('surfing', $tags)) {
            $warnings[] = "Both calm-waters and surfing assigned";
        }
        if (in_array('secluded', $tags) && in_array('popular', $tags)) {
            $warnings[] = "Both secluded and popular assigned";
        }

        // Amenities validation
        $amenities = $data['amenities'] ?? [];
        if (!is_array($amenities) || count($amenities) < 2 || count($amenities) > 5) {
            $errors[] = "Amenities: need 2-5, got " . count($amenities);
        }
        foreach ($amenities as $amenity) {
            if (!in_array($amenity, AMENITIES)) {
                $errors[] = "Invalid amenity: {$amenity}";
            }
        }
        if (count($amenities) !== count(array_unique($amenities))) {
            $errors[] = "Duplicate amenities";
        }

        // Features validation
        $features = $data['features'] ?? [];
        if (!is_array($features) || count($features) < 2 || count($features) > 4) {
            $errors[] = "Features: need 2-4, got " . count($features);
        }
        foreach ($features as $f) {
            $title = $f['title'] ?? '';
            $desc = $f['description'] ?? '';
            if (strlen($title) < 5 || strlen($title) > 50) {
                $errors[] = "Feature title length: '{$title}' (" . strlen($title) . " chars)";
            }
            if (strlen($desc) < 50 || strlen($desc) > 200) {
                $warnings[] = "Feature desc length: " . strlen($desc) . " chars (target 50-200)";
            }
        }

        // Tips validation
        $tips = $data['tips'] ?? [];
        if (!is_array($tips) || count($tips) < 3 || count($tips) > 5) {
            $errors[] = "Tips: need 3-5, got " . count($tips);
        }
        foreach ($tips as $t) {
            $cat = $t['category'] ?? '';
            $tip = $t['tip'] ?? '';
            if (!in_array($cat, self::VALID_TIP_CATEGORIES)) {
                $warnings[] = "Non-standard tip category: {$cat}";
            }
            if (strlen($tip) < 20 || strlen($tip) > 150) {
                $warnings[] = "Tip length: " . strlen($tip) . " chars (target 20-150)";
            }
        }

        // Field data validation
        $field = $data['field_data'] ?? [];
        if (empty($field['best_time'])) {
            $errors[] = "Missing best_time";
        } elseif (str_word_count($field['best_time']) < 30) {
            $warnings[] = "best_time too short: " . str_word_count($field['best_time']) . " words";
        }
        if (empty($field['parking_details'])) {
            $errors[] = "Missing parking_details";
        }
        if (empty($field['safety_info'])) {
            $errors[] = "Missing safety_info";
        }
        if (empty($field['access_label']) || !in_array($field['access_label'], self::VALID_ACCESS_LABELS)) {
            $errors[] = "Invalid access_label: " . ($field['access_label'] ?? 'empty');
        }

        // Quality check: generic phrases
        $genericCount = 0;
        $allText = json_encode($data);
        foreach (self::BANNED_PHRASES as $phrase) {
            if (stripos($allText, $phrase) !== false) {
                $genericCount++;
            }
        }
        if ($genericCount >= 2) {
            $errors[] = "Quality: {$genericCount} generic phrases detected";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Save enrichment data to database
     */
    private function saveToDatabase(string $beachId, array $data): void {
        require_once __DIR__ . '/../../inc/db.php';

        $db = getDB();
        $db->exec('BEGIN TRANSACTION');

        try {
            // 1. Insert tags
            foreach ($data['tags'] as $tag) {
                execute(
                    "INSERT OR IGNORE INTO beach_tags (beach_id, tag) VALUES (?, ?)",
                    [$beachId, $tag]
                );
            }

            // 2. Insert amenities
            foreach ($data['amenities'] as $amenity) {
                execute(
                    "INSERT OR IGNORE INTO beach_amenities (beach_id, amenity) VALUES (?, ?)",
                    [$beachId, $amenity]
                );
            }

            // 3. Insert features
            $pos = 1;
            foreach ($data['features'] as $feature) {
                execute(
                    "INSERT INTO beach_features (beach_id, title, description, position) VALUES (?, ?, ?, ?)",
                    [$beachId, $feature['title'], $feature['description'], $pos]
                );
                $pos++;
            }

            // 4. Insert tips
            $pos = 1;
            foreach ($data['tips'] as $tip) {
                execute(
                    "INSERT INTO beach_tips (beach_id, category, tip, position) VALUES (?, ?, ?, ?)",
                    [$beachId, $tip['category'], $tip['tip'], $pos]
                );
                $pos++;
            }

            // 5. Update field data
            $field = $data['field_data'];
            execute(
                "UPDATE beaches SET best_time = ?, parking_details = ?, safety_info = ?, access_label = ? WHERE id = ?",
                [$field['best_time'], $field['parking_details'], $field['safety_info'], $field['access_label'], $beachId]
            );

            $db->exec('COMMIT');

        } catch (Exception $e) {
            $db->exec('ROLLBACK');
            throw $e;
        }
    }

    /**
     * Test API connection
     */
    public function testConnection(): bool {
        try {
            $url = 'https://api.anthropic.com/v1/messages';

            $payload = [
                'model' => $this->model,
                'max_tokens' => 10,
                'messages' => [
                    ['role' => 'user', 'content' => 'Respond with only: OK']
                ]
            ];

            $headers = [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01'
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode === 200;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Rate limiting
     */
    private function enforceRateLimit(): void {
        if ($this->lastRequestTime > 0) {
            $elapsed = microtime(true) - $this->lastRequestTime;
            $remaining = $this->minDelay - $elapsed;
            if ($remaining > 0) {
                usleep((int)($remaining * 1000000));
            }
        }
        $this->lastRequestTime = microtime(true);
    }

    // Checkpoint management

    private function saveCheckpoint(array $data): void {
        file_put_contents($this->checkpointFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function loadCheckpoint(): ?array {
        if (!file_exists($this->checkpointFile)) {
            return null;
        }
        return json_decode(file_get_contents($this->checkpointFile), true);
    }

    private function clearCheckpoint(): void {
        if (file_exists($this->checkpointFile)) {
            unlink($this->checkpointFile);
        }
    }

    // Logging

    private function log(string $message, string $level = 'INFO'): void {
        $timestamp = date('Y-m-d H:i:s');
        $entry = "[{$timestamp}] [{$level}] {$message}\n";
        file_put_contents($this->logFile, $entry, FILE_APPEND);
        echo $entry;
    }

    /**
     * Generate summary report
     */
    public function generateReport(array $stats): string {
        $duration = gmdate('H:i:s', $stats['duration'] ?? 0);
        $successRate = $stats['total'] > 0 ? round(($stats['succeeded'] / $stats['total']) * 100, 1) : 0;

        $report = "\n" . str_repeat('=', 70) . "\n";
        $report .= "ENRICHMENT SUMMARY\n";
        $report .= str_repeat('=', 70) . "\n";
        $report .= "Total beaches:     {$stats['total']}\n";
        $report .= "Processed:         {$stats['processed']}\n";
        $report .= "Succeeded:         {$stats['succeeded']}\n";
        $report .= "Failed:            {$stats['failed']}\n";
        $report .= "Success rate:      {$successRate}%\n";
        $report .= "Duration:          {$duration}\n";
        $report .= str_repeat('=', 70) . "\n";

        if (!empty($stats['errors'])) {
            $report .= "\nERRORS:\n";
            $report .= str_repeat('-', 70) . "\n";
            foreach (array_slice($stats['errors'], 0, 10) as $error) {
                $report .= "- {$error['beach_name']}: {$error['error']}\n";
            }
            if (count($stats['errors']) > 10) {
                $remaining = count($stats['errors']) - 10;
                $report .= "... and {$remaining} more errors (see log file)\n";
            }
        }

        return $report;
    }

    public function getModel(): string {
        return $this->model;
    }
}
