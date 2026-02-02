<?php
/**
 * BatchProcessor - Manages batch processing with checkpointing and resumption
 */

class BatchProcessor {

    private $db;
    private $generator;
    private $validator;
    private $checkpointFile;
    private $logFile;
    private $batchSize = 50;
    private $saveInterval = 10; // Save progress every N beaches

    public function __construct($dbConnection, ContentGenerator $generator, ContentValidator $validator) {
        $this->db = $dbConnection;
        $this->generator = $generator;
        $this->validator = $validator;

        // Set up checkpoint and log files
        $scriptsDir = dirname(__DIR__);
        $this->checkpointFile = $scriptsDir . '/content-generation-checkpoint.json';
        $this->logFile = $scriptsDir . '/content-generation.log';
    }

    /**
     * Process beaches in batches
     */
    public function processBatches(array $options = []): array {
        $startBeachId = $options['start_beach_id'] ?? null;
        $singleBeachId = $options['beach_id'] ?? null;
        $batchSize = $options['batch_size'] ?? $this->batchSize;
        $dryRun = $options['dry_run'] ?? false;
        $validateOnly = $options['validate_only'] ?? false;

        $stats = [
            'total' => 0,
            'processed' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
            'start_time' => time()
        ];

        // Load checkpoint if resuming
        $checkpoint = $this->loadCheckpoint();

        // Get beaches to process
        if ($singleBeachId) {
            $beaches = $this->getBeach($singleBeachId);
        } else {
            $beaches = $this->getBeachesToProcess($startBeachId, $checkpoint);
        }

        $stats['total'] = count($beaches);
        $this->log("Starting batch processing: {$stats['total']} beaches");

        // Process in batches
        $batchNum = 1;
        $batches = array_chunk($beaches, $batchSize);

        foreach ($batches as $batch) {
            $this->log("Processing batch {$batchNum}/" . count($batches) . " (" . count($batch) . " beaches)");

            foreach ($batch as $beach) {
                try {
                    $result = $this->processBeach($beach, $dryRun, $validateOnly);

                    $stats['processed']++;

                    if ($result['success']) {
                        $stats['succeeded']++;
                        $this->log("✓ {$beach['name']} - Score: {$result['score']}");
                    } else {
                        $stats['failed']++;
                        $this->log("✗ {$beach['name']} - {$result['error']}", 'ERROR');
                        $stats['errors'][] = [
                            'beach_id' => $beach['id'],
                            'beach_name' => $beach['name'],
                            'error' => $result['error']
                        ];
                    }

                    // Save checkpoint periodically
                    if ($stats['processed'] % $this->saveInterval === 0) {
                        $this->saveCheckpoint([
                            'last_beach_id' => $beach['id'],
                            'last_beach_name' => $beach['name'],
                            'processed' => $stats['processed'],
                            'timestamp' => date('Y-m-d H:i:s')
                        ]);
                        $this->log("Checkpoint saved: {$stats['processed']} beaches processed");
                    }

                } catch (Exception $e) {
                    $stats['failed']++;
                    $errorMsg = "Exception processing {$beach['name']}: " . $e->getMessage();
                    $this->log($errorMsg, 'ERROR');
                    $stats['errors'][] = [
                        'beach_id' => $beach['id'],
                        'beach_name' => $beach['name'],
                        'error' => $e->getMessage()
                    ];
                }
            }

            $batchNum++;

            // Brief pause between batches
            if ($batchNum <= count($batches)) {
                sleep(2);
            }
        }

        $stats['end_time'] = time();
        $stats['duration'] = $stats['end_time'] - $stats['start_time'];

        // Clear checkpoint on successful completion
        if ($stats['failed'] === 0 && !$singleBeachId) {
            $this->clearCheckpoint();
        }

        return $stats;
    }

    /**
     * Process a single beach
     */
    private function processBeach(array $beach, bool $dryRun = false, bool $validateOnly = false): array {
        // Get tags and amenities
        $tags = $this->getBeachTags($beach['id']);
        $amenities = $this->getBeachAmenities($beach['id']);

        if ($validateOnly) {
            // Just validate existing content
            $existingSections = $this->getExistingSections($beach['id']);
            if (empty($existingSections)) {
                return ['success' => false, 'error' => 'No existing content to validate'];
            }

            $validation = $this->validator->validateBeach($existingSections, $beach['name']);
            $score = $validation['overall_score'];

            return [
                'success' => $validation['valid'],
                'score' => $score,
                'validation' => $validation
            ];
        }

        // Generate content
        $sections = $this->generator->generateBeachContent($beach, $tags, $amenities);

        // Validate generated content
        $validation = $this->validator->validateBeach($sections, $beach['name']);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => 'Validation failed: ' . implode('; ', $validation['errors']),
                'validation' => $validation
            ];
        }

        $score = $validation['overall_score'];

        // Save to database (unless dry run)
        if (!$dryRun) {
            $this->saveSections($beach['id'], $sections);
        }

        return [
            'success' => true,
            'score' => $score,
            'validation' => $validation,
            'sections_count' => count($sections)
        ];
    }

    /**
     * Get beaches to process
     */
    private function getBeachesToProcess(?string $startBeachId, ?array $checkpoint): array {
        $sql = "SELECT id, name, municipality, latitude, longitude FROM beaches WHERE 1=1";
        $params = [];

        // Resume from checkpoint or start point
        if ($startBeachId) {
            $sql .= " AND id >= ?";
            $params[] = $startBeachId;
        } elseif ($checkpoint && isset($checkpoint['last_beach_id'])) {
            $sql .= " AND id > ?";
            $params[] = $checkpoint['last_beach_id'];
        }

        $sql .= " ORDER BY id";

        require_once __DIR__ . '/../../inc/db.php';
        return query($sql, $params);
    }

    /**
     * Get a single beach by ID
     */
    private function getBeach(string $beachId): array {
        require_once __DIR__ . '/../../inc/db.php';
        $beach = queryOne("SELECT id, name, municipality, latitude, longitude FROM beaches WHERE id = ?", [$beachId]);

        if (!$beach) {
            throw new Exception("Beach not found: {$beachId}");
        }

        return [$beach];
    }

    /**
     * Get beach tags
     */
    private function getBeachTags(string $beachId): array {
        require_once __DIR__ . '/../../inc/db.php';
        $rows = query("SELECT tag FROM beach_tags WHERE beach_id = ?", [$beachId]);
        return array_column($rows, 'tag');
    }

    /**
     * Get beach amenities
     */
    private function getBeachAmenities(string $beachId): array {
        require_once __DIR__ . '/../../inc/db.php';
        $rows = query("SELECT amenity FROM beach_amenities WHERE beach_id = ?", [$beachId]);
        return array_column($rows, 'amenity');
    }

    /**
     * Get existing sections for validation
     */
    private function getExistingSections(string $beachId): array {
        require_once __DIR__ . '/../../inc/db.php';
        return query(
            "SELECT section_type, heading, content, word_count FROM beach_content_sections WHERE beach_id = ? AND status = 'draft' ORDER BY display_order",
            [$beachId]
        );
    }

    /**
     * Save sections to database
     */
    private function saveSections(string $beachId, array $sections): void {
        require_once __DIR__ . '/../../inc/db.php';

        // Delete existing draft sections
        execute("DELETE FROM beach_content_sections WHERE beach_id = ? AND status = 'draft'", [$beachId]);

        // Insert new sections
        $displayOrder = 1;
        foreach ($sections as $section) {
            $id = $this->generateId();

            execute(
                "INSERT INTO beach_content_sections (id, beach_id, section_type, heading, content, word_count, display_order, status, generated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', datetime('now'))",
                [
                    $id,
                    $beachId,
                    $section['section_type'],
                    $section['heading'],
                    $section['content'],
                    $section['word_count'],
                    $displayOrder
                ]
            );

            $displayOrder++;
        }
    }

    /**
     * Generate UUID v4
     */
    private function generateId(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Save checkpoint
     */
    private function saveCheckpoint(array $data): void {
        file_put_contents($this->checkpointFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Load checkpoint
     */
    private function loadCheckpoint(): ?array {
        if (!file_exists($this->checkpointFile)) {
            return null;
        }

        $data = file_get_contents($this->checkpointFile);
        return json_decode($data, true);
    }

    /**
     * Clear checkpoint
     */
    private function clearCheckpoint(): void {
        if (file_exists($this->checkpointFile)) {
            unlink($this->checkpointFile);
        }
    }

    /**
     * Log message
     */
    private function log(string $message, string $level = 'INFO'): void {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}\n";

        // Write to log file
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);

        // Also output to console
        echo $logEntry;
    }

    /**
     * Generate summary report
     */
    public function generateReport(array $stats): string {
        $duration = gmdate('H:i:s', $stats['duration']);
        $successRate = $stats['total'] > 0 ? round(($stats['succeeded'] / $stats['total']) * 100, 2) : 0;

        $report = "\n" . str_repeat('=', 70) . "\n";
        $report .= "CONTENT GENERATION SUMMARY\n";
        $report .= str_repeat('=', 70) . "\n";
        $report .= "Total beaches:     {$stats['total']}\n";
        $report .= "Processed:         {$stats['processed']}\n";
        $report .= "Succeeded:         {$stats['succeeded']}\n";
        $report .= "Failed:            {$stats['failed']}\n";
        $report .= "Skipped:           {$stats['skipped']}\n";
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

    /**
     * Set batch size
     */
    public function setBatchSize(int $size): void {
        $this->batchSize = $size;
    }

    /**
     * Set save interval
     */
    public function setSaveInterval(int $interval): void {
        $this->saveInterval = $interval;
    }
}
