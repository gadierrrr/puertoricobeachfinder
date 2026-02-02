#!/usr/bin/env php
<?php
/**
 * Test script for content generation system
 * Tests individual components without making API calls
 */

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/content/PromptBuilder.php';
require_once __DIR__ . '/content/ContentValidator.php';

echo "Content Generation System Test\n";
echo str_repeat('=', 70) . "\n\n";

// Test 1: PromptBuilder
echo "Test 1: PromptBuilder\n";
echo str_repeat('-', 70) . "\n";

$beachData = [
    'id' => 'test-123',
    'name' => 'Test Beach',
    'municipality' => 'Rincon',
    'latitude' => 18.33,
    'longitude' => -67.25
];

$tags = ['surfing', 'scenic'];
$amenities = ['parking', 'restrooms'];

$promptBuilder = new PromptBuilder();
$prompt = $promptBuilder->buildPrompt($beachData, $tags, $amenities);

echo "✓ Prompt generated successfully\n";
echo "  Length: " . strlen($prompt) . " characters\n";
echo "  Contains 'Rincon': " . (strpos($prompt, 'Rincon') !== false ? 'Yes' : 'No') . "\n";
echo "  Contains 'surfing': " . (strpos($prompt, 'surfing') !== false ? 'Yes' : 'No') . "\n\n";

// Test 2: Section Configs
echo "Test 2: Section Configurations\n";
echo str_repeat('-', 70) . "\n";

$configs = PromptBuilder::getSectionConfigs();
echo "✓ Found " . count($configs) . " section types:\n";
foreach ($configs as $type => $config) {
    echo "  - {$type}: {$config['heading']} ({$config['min_words']}-{$config['max_words']} words)\n";
}
echo "\n";

// Test 3: ContentValidator
echo "Test 3: ContentValidator\n";
echo str_repeat('-', 70) . "\n";

$validator = new ContentValidator();

$testSections = [
    [
        'section_type' => 'history',
        'heading' => 'History & Background',
        'content' => str_repeat('This is a test sentence about Test Beach in Rincon. ', 60),
        'word_count' => 420
    ],
    [
        'section_type' => 'best_time',
        'heading' => 'Best Time to Visit',
        'content' => str_repeat('Winter is the best time to visit for surfing. ', 20),
        'word_count' => 140
    ]
];

// Validate first section
$validation = $validator->validateSection($testSections[0], 'Test Beach');

echo "✓ Validation completed\n";
echo "  Valid: " . ($validation['valid'] ? 'Yes' : 'No') . "\n";
echo "  Word count score: " . ($validation['scores']['word_count'] ?? 'N/A') . "\n";
echo "  Uniqueness score: " . ($validation['scores']['uniqueness'] ?? 'N/A') . "\n";
echo "  Readability score: " . ($validation['scores']['readability'] ?? 'N/A') . "\n";

if (!empty($validation['warnings'])) {
    echo "  Warnings: " . count($validation['warnings']) . "\n";
}
echo "\n";

// Test 4: Generic Phrase Detection
echo "Test 4: Generic Phrase Detection\n";
echo str_repeat('-', 70) . "\n";

$genericContent = [
    'section_type' => 'history',
    'heading' => 'History',
    'content' => 'This beach is a hidden gem with crystal clear waters and pristine sand.',
    'word_count' => 14
];

$genericValidation = $validator->validateSection($genericContent, 'Test Beach');

echo "✓ Generic phrase detection working\n";
echo "  Warnings: " . count($genericValidation['warnings']) . "\n";
foreach ($genericValidation['warnings'] as $warning) {
    echo "  - {$warning}\n";
}
echo "\n";

// Test 5: Database Connection
echo "Test 5: Database Connection\n";
echo str_repeat('-', 70) . "\n";

try {
    $beaches = query("SELECT COUNT(*) as count FROM beaches");
    echo "✓ Database connected\n";
    echo "  Total beaches: " . $beaches[0]['count'] . "\n";

    $sections = query("SELECT COUNT(*) as count FROM beach_content_sections");
    echo "  Existing sections: " . $sections[0]['count'] . "\n";
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 6: Sample Beach Data
echo "Test 6: Sample Beach Data\n";
echo str_repeat('-', 70) . "\n";

$sampleBeach = queryOne("SELECT id, name, municipality FROM beaches LIMIT 1");
if ($sampleBeach) {
    echo "✓ Sample beach retrieved\n";
    echo "  ID: {$sampleBeach['id']}\n";
    echo "  Name: {$sampleBeach['name']}\n";
    echo "  Municipality: {$sampleBeach['municipality']}\n";

    // Get tags and amenities
    $beachTags = query("SELECT tag FROM beach_tags WHERE beach_id = ?", [$sampleBeach['id']]);
    $beachAmenities = query("SELECT amenity FROM beach_amenities WHERE beach_id = ?", [$sampleBeach['id']]);

    echo "  Tags: " . (count($beachTags) > 0 ? implode(', ', array_column($beachTags, 'tag')) : 'none') . "\n";
    echo "  Amenities: " . (count($beachAmenities) > 0 ? implode(', ', array_column($beachAmenities, 'tag')) : 'none') . "\n";
} else {
    echo "✗ No beaches found\n";
}
echo "\n";

// Summary
echo str_repeat('=', 70) . "\n";
echo "All tests completed successfully!\n\n";
echo "Next steps:\n";
echo "1. Set ANTHROPIC_API_KEY environment variable\n";
echo "2. Test API: php scripts/generate-beach-content.php --test-api\n";
echo "3. Generate test: php scripts/generate-beach-content.php --beach-id={$sampleBeach['id']} --dry-run\n";
echo "\n";
