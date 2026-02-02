<?php
/**
 * ContentValidator - Quality control for generated content
 */

class ContentValidator {

    // Generic phrases that indicate low-quality content
    private const GENERIC_PHRASES = [
        'crystal clear waters',
        'crystal clear water',
        'crystal-clear waters',
        'hidden gem',
        'hidden treasure',
        'paradise',
        'pristine',
        'pristine beach',
        'untouched',
        'untouched paradise',
        'perfect destination',
        'breathtaking',
        'breathtaking views',
        'slice of heaven',
        'tropical paradise',
        'nature\'s beauty',
        'nature\'s gift',
        'escape the crowds',
        'off the beaten path',
        'off the beaten track',
        'picture perfect',
        'picture-perfect',
        'dream destination',
        'must-see',
        'must visit',
        'world-class',
        'undiscovered'
    ];

    // Cache for duplicate detection
    private $seenSentences = [];
    private $db;

    public function __construct($dbConnection = null) {
        $this->db = $dbConnection;
    }

    /**
     * Validate a single section
     */
    public function validateSection(array $section, string $beachName): array {
        $errors = [];
        $warnings = [];
        $scores = [];

        // Extract section data
        $type = $section['section_type'] ?? '';
        $content = $section['content'] ?? '';
        $wordCount = $section['word_count'] ?? 0;

        // Get expected word count range
        $sectionConfigs = PromptBuilder::getSectionConfigs();
        $config = $sectionConfigs[$type] ?? null;

        if (!$config) {
            $errors[] = "Unknown section type: {$type}";
            return [
                'valid' => false,
                'errors' => $errors,
                'warnings' => $warnings,
                'scores' => $scores
            ];
        }

        // 1. Word count validation
        $actualWordCount = str_word_count($content);
        if ($actualWordCount < $config['min_words']) {
            $errors[] = "{$type}: Too short ({$actualWordCount} words, need {$config['min_words']}-{$config['max_words']})";
        } elseif ($actualWordCount > $config['max_words'] * 1.2) { // 20% buffer
            $warnings[] = "{$type}: Too long ({$actualWordCount} words, target {$config['min_words']}-{$config['max_words']})";
        }
        $scores['word_count'] = $this->scoreWordCount($actualWordCount, $config['min_words'], $config['max_words']);

        // 2. Generic phrase detection
        $genericPhrases = $this->detectGenericPhrases($content);
        if (!empty($genericPhrases)) {
            foreach ($genericPhrases as $phrase) {
                $warnings[] = "{$type}: Contains generic phrase: '{$phrase}'";
            }
        }
        $scores['uniqueness'] = 100 - (count($genericPhrases) * 20); // -20 per generic phrase

        // 3. Content quality checks
        $qualityIssues = $this->checkContentQuality($content, $beachName, $type);
        foreach ($qualityIssues as $issue) {
            $warnings[] = "{$type}: {$issue}";
        }

        // 4. Readability score (Flesch Reading Ease approximation)
        $scores['readability'] = $this->calculateReadability($content);

        // 5. Check for duplicate sentences
        $duplicates = $this->detectDuplicateSentences($content, $type);
        if (!empty($duplicates)) {
            foreach ($duplicates as $dup) {
                $warnings[] = "{$type}: Duplicate sentence: '{$dup}'";
            }
        }

        // 6. Check factual consistency with beach data
        // (Basic check - ensure beach name appears at least once)
        if (stripos($content, $beachName) === false) {
            $warnings[] = "{$type}: Beach name not mentioned in content";
        }

        // Calculate overall score
        $scores['overall'] = ($scores['word_count'] + $scores['uniqueness'] + $scores['readability']) / 3;

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'scores' => $scores,
            'word_count' => $actualWordCount
        ];
    }

    /**
     * Validate all sections for a beach
     */
    public function validateBeach(array $sections, string $beachName): array {
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'sections' => [],
            'overall_score' => 0
        ];

        $totalScore = 0;
        $sectionCount = 0;

        foreach ($sections as $section) {
            $validation = $this->validateSection($section, $beachName);

            $results['sections'][$section['section_type']] = $validation;

            if (!$validation['valid']) {
                $results['valid'] = false;
            }

            $results['errors'] = array_merge($results['errors'], $validation['errors']);
            $results['warnings'] = array_merge($results['warnings'], $validation['warnings']);

            if (isset($validation['scores']['overall'])) {
                $totalScore += $validation['scores']['overall'];
                $sectionCount++;
            }
        }

        if ($sectionCount > 0) {
            $results['overall_score'] = round($totalScore / $sectionCount, 2);
        }

        // Check cross-section issues
        $crossSectionIssues = $this->checkCrossSectionIssues($sections);
        $results['warnings'] = array_merge($results['warnings'], $crossSectionIssues);

        return $results;
    }

    /**
     * Score word count (0-100)
     */
    private function scoreWordCount(int $actual, int $min, int $max): int {
        if ($actual < $min) {
            return (int) (($actual / $min) * 100);
        } elseif ($actual > $max) {
            $overage = $actual - $max;
            $penalty = min(50, $overage / 10); // -5 points per 10 words over
            return max(50, 100 - $penalty);
        } else {
            return 100; // Perfect
        }
    }

    /**
     * Detect generic/cliché phrases
     */
    private function detectGenericPhrases(string $content): array {
        $found = [];
        $lowerContent = strtolower($content);

        foreach (self::GENERIC_PHRASES as $phrase) {
            if (stripos($lowerContent, strtolower($phrase)) !== false) {
                $found[] = $phrase;
            }
        }

        return array_unique($found);
    }

    /**
     * Check content quality issues
     */
    private function checkContentQuality(string $content, string $beachName, string $sectionType): array {
        $issues = [];

        // Check for very short sentences (might indicate choppy writing)
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $shortSentences = 0;
        foreach ($sentences as $sentence) {
            if (str_word_count(trim($sentence)) < 5) {
                $shortSentences++;
            }
        }
        if ($shortSentences > 2) {
            $issues[] = "Multiple very short sentences (choppy flow)";
        }

        // Check for repetitive sentence starts
        $starts = [];
        foreach ($sentences as $sentence) {
            $trimmed = trim($sentence);
            $firstWord = strtok($trimmed, ' ');
            if ($firstWord) {
                $starts[] = strtolower($firstWord);
            }
        }
        $startCounts = array_count_values($starts);
        foreach ($startCounts as $word => $count) {
            if ($count > 2 && strlen($word) > 3) {
                $issues[] = "Repetitive sentence starts with '{$word}' ({$count} times)";
            }
        }

        // Check for excessive use of "the beach"
        $beachMentions = substr_count(strtolower($content), 'the beach');
        if ($beachMentions > 5) {
            $issues[] = "Overuse of 'the beach' phrase ({$beachMentions} times)";
        }

        return $issues;
    }

    /**
     * Calculate readability score (simplified Flesch Reading Ease)
     */
    private function calculateReadability(string $content): int {
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentenceCount = count($sentences);

        if ($sentenceCount === 0) {
            return 0;
        }

        $wordCount = str_word_count($content);
        $syllableCount = $this->estimateSyllables($content);

        if ($wordCount === 0) {
            return 0;
        }

        // Flesch Reading Ease formula
        $avgSentenceLength = $wordCount / $sentenceCount;
        $avgSyllablesPerWord = $syllableCount / $wordCount;

        $score = 206.835 - (1.015 * $avgSentenceLength) - (84.6 * $avgSyllablesPerWord);

        // Normalize to 0-100 scale (60-100 is good)
        $normalized = max(0, min(100, $score));

        return (int) $normalized;
    }

    /**
     * Estimate syllable count (approximation)
     */
    private function estimateSyllables(string $text): int {
        $words = str_word_count(strtolower($text), 1);
        $syllables = 0;

        foreach ($words as $word) {
            $syllables += $this->countSyllablesInWord($word);
        }

        return $syllables;
    }

    /**
     * Count syllables in a word (rough approximation)
     */
    private function countSyllablesInWord(string $word): int {
        $word = strtolower($word);
        $count = 0;
        $vowels = ['a', 'e', 'i', 'o', 'u', 'y'];
        $previousWasVowel = false;

        for ($i = 0; $i < strlen($word); $i++) {
            $isVowel = in_array($word[$i], $vowels);
            if ($isVowel && !$previousWasVowel) {
                $count++;
            }
            $previousWasVowel = $isVowel;
        }

        // Adjust for silent 'e'
        if (substr($word, -1) === 'e') {
            $count--;
        }

        // Minimum one syllable per word
        return max(1, $count);
    }

    /**
     * Detect duplicate sentences across beaches
     */
    private function detectDuplicateSentences(string $content, string $sectionType): array {
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $duplicates = [];

        foreach ($sentences as $sentence) {
            $trimmed = trim($sentence);
            if (strlen($trimmed) < 20) continue; // Skip very short sentences

            $normalized = strtolower($trimmed);

            if (isset($this->seenSentences[$sectionType][$normalized])) {
                $duplicates[] = substr($trimmed, 0, 50) . '...';
            } else {
                $this->seenSentences[$sectionType][$normalized] = true;
            }
        }

        return $duplicates;
    }

    /**
     * Check for issues across all sections
     */
    private function checkCrossSectionIssues(array $sections): array {
        $issues = [];

        // Ensure all 6 sections are present
        $expectedTypes = ['history', 'best_time', 'getting_there', 'what_to_bring', 'nearby', 'local_tips'];
        $actualTypes = array_map(function($s) { return $s['section_type'] ?? ''; }, $sections);

        $missing = array_diff($expectedTypes, $actualTypes);
        if (!empty($missing)) {
            $issues[] = "Missing sections: " . implode(', ', $missing);
        }

        return $issues;
    }

    /**
     * Reset duplicate detection cache
     */
    public function resetCache(): void {
        $this->seenSentences = [];
    }

    /**
     * Get validation summary statistics
     */
    public function getValidationStats(array $results): array {
        return [
            'total_errors' => count($results['errors']),
            'total_warnings' => count($results['warnings']),
            'overall_score' => $results['overall_score'] ?? 0,
            'is_valid' => $results['valid'],
            'section_count' => count($results['sections'])
        ];
    }
}
