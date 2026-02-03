#!/usr/bin/env php
<?php
/**
 * Extended Schema Validation - Test 10 random beaches
 */

require_once __DIR__ . '/../inc/db.php';

function fetchPageContent($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("HTTP $httpCode");
    }

    return $html;
}

function extractJsonLdSchemas($html) {
    $schemas = [];
    preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);

    foreach ($matches[1] as $jsonString) {
        $decoded = json_decode(trim($jsonString), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $schemas[] = $decoded;
        }
    }

    return $schemas;
}

function checkDuplicateRating($schemas) {
    $ratingCount = 0;
    foreach ($schemas as $schema) {
        if (isset($schema['@type']) && $schema['@type'] === 'AggregateRating') {
            $ratingCount++;
        }
        if (isset($schema['aggregateRating'])) {
            $ratingCount++;
        }
    }
    return $ratingCount;
}

function validateImageObject($schema) {
    if (!isset($schema['image'])) {
        return ['valid' => false, 'reason' => 'No image'];
    }

    $image = $schema['image'];

    if (is_string($image)) {
        return ['valid' => false, 'reason' => 'Image is string, not ImageObject'];
    }

    if (!isset($image['@type']) || $image['@type'] !== 'ImageObject') {
        return ['valid' => false, 'reason' => 'Not an ImageObject'];
    }

    if (!isset($image['url'])) {
        return ['valid' => false, 'reason' => 'Missing URL'];
    }

    $hasWidth = isset($image['width']) && $image['width'] > 0;
    $hasHeight = isset($image['height']) && $image['height'] > 0;

    if (!$hasWidth || !$hasHeight) {
        return ['valid' => true, 'warning' => 'Missing dimensions'];
    }

    return ['valid' => true];
}

// Get 10 random beaches
$beaches = query('SELECT id, name, slug FROM beaches ORDER BY RANDOM() LIMIT 10');

$baseUrl = 'https://puertoricobeachfinder.com';
$totalErrors = 0;
$totalWarnings = 0;
$results = [];

echo "Extended Schema Validation - 10 Random Beaches\n";
echo str_repeat("=", 80) . "\n\n";

foreach ($beaches as $beach) {
    $url = "$baseUrl/beach/{$beach['slug']}";
    $errors = [];
    $warnings = [];

    try {
        $html = fetchPageContent($url);
        $schemas = extractJsonLdSchemas($html);

        // Check for expected schema count
        if (count($schemas) !== 5) {
            $warnings[] = "Expected 5 schemas, found " . count($schemas);
        }

        // Check for duplicate ratings
        $ratingCount = checkDuplicateRating($schemas);
        if ($ratingCount > 1) {
            $errors[] = "Duplicate AggregateRating ($ratingCount instances)";
        }

        // Validate Beach and TouristAttraction schemas
        foreach ($schemas as $schema) {
            $type = $schema['@type'] ?? 'Unknown';

            if ($type === 'Beach') {
                // Check ImageObject
                $imgValidation = validateImageObject($schema);
                if (!$imgValidation['valid']) {
                    $errors[] = "Beach: " . $imgValidation['reason'];
                } elseif (isset($imgValidation['warning'])) {
                    $warnings[] = "Beach: " . $imgValidation['warning'];
                }

                // Check for rating
                if (!isset($schema['aggregateRating'])) {
                    $warnings[] = "Beach: No aggregateRating";
                }
            }

            if ($type === 'TouristAttraction') {
                // Check ImageObject
                $imgValidation = validateImageObject($schema);
                if (!$imgValidation['valid']) {
                    $errors[] = "TouristAttraction: " . $imgValidation['reason'];
                } elseif (isset($imgValidation['warning'])) {
                    $warnings[] = "TouristAttraction: " . $imgValidation['warning'];
                }

                // Should NOT have rating
                if (isset($schema['aggregateRating'])) {
                    $errors[] = "TouristAttraction: Has aggregateRating (should not)";
                }
            }
        }

        $status = empty($errors) ? '✅' : '❌';
        echo "$status {$beach['name']}\n";

        if (!empty($errors)) {
            foreach ($errors as $error) {
                echo "   ❌ $error\n";
            }
        }

        if (!empty($warnings)) {
            foreach ($warnings as $warning) {
                echo "   ⚠️  $warning\n";
            }
        }

        $totalErrors += count($errors);
        $totalWarnings += count($warnings);

        $results[] = [
            'name' => $beach['name'],
            'errors' => count($errors),
            'warnings' => count($warnings)
        ];

    } catch (Exception $e) {
        echo "❌ {$beach['name']}\n";
        echo "   ❌ ERROR: " . $e->getMessage() . "\n";
        $totalErrors++;
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 80) . "\n";
echo "Beaches tested: " . count($beaches) . "\n";
echo "Total Errors: $totalErrors\n";
echo "Total Warnings: $totalWarnings\n";

if ($totalErrors === 0) {
    echo "\n✅ All beaches passed validation!\n";
} else {
    echo "\n❌ Some beaches have errors\n";
    exit(1);
}
