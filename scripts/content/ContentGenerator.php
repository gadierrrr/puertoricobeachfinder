<?php
/**
 * ContentGenerator - AI generation engine using Claude API
 */

class ContentGenerator {

    private $apiKey;
    private $model = 'claude-3-5-sonnet-20241022';
    private $maxTokens = 4000;
    private $lastRequestTime = 0;
    private $minDelay = 2; // 2 seconds between requests

    public function __construct(string $apiKey) {
        $this->apiKey = $apiKey;
    }

    /**
     * Generate content for a beach (all 6 sections in one call)
     */
    public function generateBeachContent(array $beachData, array $tags, array $amenities): array {
        // Rate limiting
        $this->enforceRateLimit();

        // Build prompt
        $promptBuilder = new PromptBuilder();
        $prompt = $promptBuilder->buildPrompt($beachData, $tags, $amenities);

        // Call Claude API
        $response = $this->callClaudeAPI($prompt);

        // Parse response
        $parsed = $this->parseResponse($response);

        return $parsed;
    }

    /**
     * Call Claude API via HTTP
     */
    private function callClaudeAPI(string $prompt): string {
        $url = 'https://api.anthropic.com/v1/messages';

        $payload = [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
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
     * Parse Claude's response into structured sections
     */
    private function parseResponse(string $response): array {
        // Remove markdown code blocks if present
        $cleaned = preg_replace('/^```json\s*/m', '', $response);
        $cleaned = preg_replace('/^```\s*/m', '', $cleaned);
        $cleaned = trim($cleaned);

        // Parse JSON
        $data = json_decode($cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to parse JSON response: " . json_last_error_msg() . "\nResponse: " . substr($response, 0, 500));
        }

        if (!isset($data['sections']) || !is_array($data['sections'])) {
            throw new Exception("Response missing 'sections' array");
        }

        // Validate structure
        $sections = $data['sections'];
        $expectedTypes = ['history', 'best_time', 'getting_there', 'what_to_bring', 'nearby', 'local_tips'];

        foreach ($expectedTypes as $type) {
            $found = false;
            foreach ($sections as $section) {
                if (($section['section_type'] ?? '') === $type) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw new Exception("Missing required section: {$type}");
            }
        }

        // Enrich sections with actual word counts
        foreach ($sections as &$section) {
            if (isset($section['content'])) {
                $section['word_count'] = str_word_count($section['content']);
            }
        }

        return $sections;
    }

    /**
     * Enforce rate limiting
     */
    private function enforceRateLimit(): void {
        if ($this->lastRequestTime > 0) {
            $elapsed = microtime(true) - $this->lastRequestTime;
            $remaining = $this->minDelay - $elapsed;

            if ($remaining > 0) {
                usleep((int)($remaining * 1000000)); // Convert to microseconds
            }
        }

        $this->lastRequestTime = microtime(true);
    }

    /**
     * Test API connection
     */
    public function testConnection(): bool {
        try {
            $testPrompt = "Respond with only: OK";

            $url = 'https://api.anthropic.com/v1/messages';

            $payload = [
                'model' => $this->model,
                'max_tokens' => 10,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $testPrompt
                    ]
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
     * Set model (for testing with different models)
     */
    public function setModel(string $model): void {
        $this->model = $model;
    }

    /**
     * Get current model
     */
    public function getModel(): string {
        return $this->model;
    }

    /**
     * Set minimum delay between requests
     */
    public function setMinDelay(int $seconds): void {
        $this->minDelay = $seconds;
    }
}
