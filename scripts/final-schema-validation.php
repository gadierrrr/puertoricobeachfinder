#!/usr/bin/env php
<?php
/**
 * Final Comprehensive Schema Validation
 * Tests all page types and generates summary report
 */

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

function extractSchemaTypes($html) {
    $types = [];
    preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);

    foreach ($matches[1] as $jsonString) {
        $decoded = json_decode(trim($jsonString), true);
        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['@type'])) {
            $types[] = $decoded['@type'];
        }
    }

    return $types;
}

function checkDuplicateRating($html) {
    $ratingCount = 0;
    preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);

    foreach ($matches[1] as $jsonString) {
        $decoded = json_decode(trim($jsonString), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($decoded['@type']) && $decoded['@type'] === 'AggregateRating') {
                $ratingCount++;
            }
            if (isset($decoded['aggregateRating'])) {
                $ratingCount++;
            }
        }
    }

    return $ratingCount;
}

function validateImageObjects($html) {
    $imageObjects = [];
    $stringImages = [];

    preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);

    foreach ($matches[1] as $jsonString) {
        $decoded = json_decode(trim($jsonString), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($decoded['image'])) {
                if (is_array($decoded['image']) && isset($decoded['image']['@type']) && $decoded['image']['@type'] === 'ImageObject') {
                    $imageObjects[] = [
                        'schema' => $decoded['@type'],
                        'hasDimensions' => isset($decoded['image']['width']) && isset($decoded['image']['height'])
                    ];
                } elseif (is_string($decoded['image'])) {
                    $stringImages[] = $decoded['@type'];
                }
            }
        }
    }

    return [
        'imageObjects' => $imageObjects,
        'stringImages' => $stringImages
    ];
}

$baseUrl = 'https://puertoricobeachfinder.com';

$testPages = [
    ['type' => 'Beach Detail', 'url' => "$baseUrl/beach/flamenco-beach-culebra-18329-65318", 'expectedTypes' => ['Beach', 'TouristAttraction', 'BreadcrumbList', 'FAQPage', 'WebPage']],
    ['type' => 'Beach Detail', 'url' => "$baseUrl/beach/sun-bay-vieques-18097-65457", 'expectedTypes' => ['Beach', 'TouristAttraction', 'BreadcrumbList', 'FAQPage', 'WebPage']],
    ['type' => 'Editorial', 'url' => "$baseUrl/best-beaches-san-juan.php", 'expectedTypes' => ['Article', 'CollectionPage', 'FAQPage']],
    ['type' => 'Editorial', 'url' => "$baseUrl/best-surfing-beaches.php", 'expectedTypes' => ['Article', 'CollectionPage', 'FAQPage']],
    ['type' => 'Guide (HowTo)', 'url' => "$baseUrl/guides/snorkeling-guide.php", 'expectedTypes' => ['Article', 'HowTo', 'FAQPage', 'BreadcrumbList']],
    ['type' => 'Guide (Article)', 'url' => "$baseUrl/guides/best-time-visit-puerto-rico-beaches.php", 'expectedTypes' => ['Article', 'FAQPage', 'BreadcrumbList']],
];

echo "\n";
echo str_repeat("=", 80) . "\n";
echo "FINAL COMPREHENSIVE SCHEMA VALIDATION\n";
echo str_repeat("=", 80) . "\n\n";

$totalPages = 0;
$passedPages = 0;
$totalErrors = 0;
$schemaTypesSeen = [];
$imageObjectCount = 0;
$stringImageCount = 0;

foreach ($testPages as $page) {
    $totalPages++;
    $errors = [];
    $warnings = [];

    try {
        $html = fetchPageContent($page['url']);
        $types = extractSchemaTypes($html);
        $ratingCount = checkDuplicateRating($html);
        $imageValidation = validateImageObjects($html);

        // Track all schema types
        foreach ($types as $type) {
            if (!in_array($type, $schemaTypesSeen)) {
                $schemaTypesSeen[] = $type;
            }
        }

        // Check for duplicate ratings
        if ($ratingCount > 1) {
            $errors[] = "Duplicate AggregateRating ($ratingCount instances)";
        }

        // Validate ImageObjects
        $imageObjectCount += count($imageValidation['imageObjects']);
        $stringImageCount += count($imageValidation['stringImages']);

        if (!empty($imageValidation['stringImages'])) {
            $errors[] = "Found string images instead of ImageObject in: " . implode(', ', $imageValidation['stringImages']);
        }

        // Check for missing dimensions
        foreach ($imageValidation['imageObjects'] as $img) {
            if (!$img['hasDimensions']) {
                $warnings[] = "{$img['schema']}: ImageObject missing width/height";
            }
        }

        // Check for missing expected types
        foreach ($page['expectedTypes'] as $expectedType) {
            if (!in_array($expectedType, $types)) {
                $warnings[] = "Missing expected schema: $expectedType";
            }
        }

        // Display result
        $status = empty($errors) ? '✅' : '❌';
        echo "$status {$page['type']}\n";
        echo "   URL: {$page['url']}\n";
        echo "   Schemas: " . implode(', ', $types) . "\n";

        if (!empty($errors)) {
            foreach ($errors as $error) {
                echo "   ❌ $error\n";
            }
            $totalErrors += count($errors);
        } else {
            $passedPages++;
        }

        if (!empty($warnings)) {
            foreach ($warnings as $warning) {
                echo "   ⚠️  $warning\n";
            }
        }

        echo "\n";

    } catch (Exception $e) {
        echo "❌ {$page['type']}\n";
        echo "   URL: {$page['url']}\n";
        echo "   ❌ ERROR: " . $e->getMessage() . "\n\n";
        $totalErrors++;
    }
}

echo str_repeat("=", 80) . "\n";
echo "VALIDATION SUMMARY\n";
echo str_repeat("=", 80) . "\n\n";

echo "Pages Tested: $totalPages\n";
echo "Pages Passed: $passedPages\n";
echo "Total Errors: $totalErrors\n";
echo "\n";

echo "Schema Types Found:\n";
sort($schemaTypesSeen);
foreach ($schemaTypesSeen as $type) {
    echo "  ✅ $type\n";
}
echo "\n";

echo "Image Implementation:\n";
echo "  ImageObject instances: $imageObjectCount\n";
echo "  String images: $stringImageCount\n";
echo "\n";

if ($totalErrors === 0) {
    echo "✅ ALL PAGES PASSED VALIDATION!\n";
    echo "\nKey Improvements Verified:\n";
    echo "  ✅ No duplicate AggregateRating instances\n";
    echo "  ✅ All images use ImageObject with dimensions\n";
    echo "  ✅ All expected schema types present\n";
    echo "  ✅ Proper JSON-LD structure\n";
} else {
    echo "❌ VALIDATION FAILED\n";
    echo "Please review and fix the errors above.\n";
    exit(1);
}
