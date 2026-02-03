#!/usr/bin/env php
<?php
/**
 * Schema Markup Validation Script
 * Extracts and validates JSON-LD schema from beach pages
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
        throw new Exception("HTTP $httpCode for $url");
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
        } else {
            $schemas[] = [
                'error' => 'JSON decode failed: ' . json_last_error_msg(),
                'raw' => substr(trim($jsonString), 0, 200)
            ];
        }
    }

    return $schemas;
}

function validateBeachSchema($schema, $pageName) {
    $errors = [];
    $warnings = [];

    // Check for required @type
    if (!isset($schema['@type'])) {
        $errors[] = "Missing @type";
        return ['errors' => $errors, 'warnings' => $warnings];
    }

    $type = $schema['@type'];

    // Validate based on schema type
    switch ($type) {
        case 'Beach':
            if (!isset($schema['name'])) $errors[] = "Beach: Missing 'name'";
            if (!isset($schema['description'])) $warnings[] = "Beach: Missing 'description'";

            // Check for new imageObject structure
            if (isset($schema['image'])) {
                if (is_array($schema['image']) && isset($schema['image']['@type'])) {
                    if ($schema['image']['@type'] !== 'ImageObject') {
                        $errors[] = "Beach: image @type should be 'ImageObject', got '{$schema['image']['@type']}'";
                    }
                    if (!isset($schema['image']['url'])) {
                        $errors[] = "Beach: ImageObject missing 'url'";
                    }
                    if (!isset($schema['image']['width']) || !isset($schema['image']['height'])) {
                        $warnings[] = "Beach: ImageObject missing width/height dimensions";
                    }
                } elseif (is_string($schema['image'])) {
                    $warnings[] = "Beach: image should be ImageObject, found string URL";
                }
            } else {
                $warnings[] = "Beach: Missing 'image'";
            }

            // Check geo coordinates
            if (!isset($schema['geo'])) {
                $errors[] = "Beach: Missing 'geo' coordinates";
            } elseif (!isset($schema['geo']['@type']) || $schema['geo']['@type'] !== 'GeoCoordinates') {
                $errors[] = "Beach: geo @type should be 'GeoCoordinates'";
            }

            // Check address
            if (!isset($schema['address'])) {
                $warnings[] = "Beach: Missing 'address'";
            }

            break;

        case 'TouristAttraction':
            if (!isset($schema['name'])) $errors[] = "TouristAttraction: Missing 'name'";
            if (!isset($schema['description'])) $warnings[] = "TouristAttraction: Missing 'description'";

            // Check for includesAttraction
            if (isset($schema['includesAttraction'])) {
                $warnings[] = "TouristAttraction: Has 'includesAttraction' (should only be on collection pages)";
            }
            break;

        case 'AggregateRating':
            $errors[] = "AggregateRating: Should not be standalone (should be nested in Beach or TouristAttraction)";
            break;

        case 'Review':
            if (!isset($schema['author'])) $errors[] = "Review: Missing 'author'";
            if (!isset($schema['reviewBody'])) $errors[] = "Review: Missing 'reviewBody'";
            if (!isset($schema['reviewRating'])) $warnings[] = "Review: Missing 'reviewRating'";
            break;

        case 'BreadcrumbList':
            if (!isset($schema['itemListElement'])) {
                $errors[] = "BreadcrumbList: Missing 'itemListElement'";
            } elseif (!is_array($schema['itemListElement'])) {
                $errors[] = "BreadcrumbList: 'itemListElement' should be an array";
            }
            break;

        case 'Article':
            if (!isset($schema['headline'])) $errors[] = "Article: Missing 'headline'";
            if (!isset($schema['author'])) $warnings[] = "Article: Missing 'author'";
            if (!isset($schema['datePublished'])) $warnings[] = "Article: Missing 'datePublished'";
            break;

        case 'CollectionPage':
            if (!isset($schema['name'])) $errors[] = "CollectionPage: Missing 'name'";
            if (!isset($schema['description'])) $warnings[] = "CollectionPage: Missing 'description'";
            break;

        case 'FAQPage':
            if (!isset($schema['mainEntity'])) {
                $errors[] = "FAQPage: Missing 'mainEntity'";
            } elseif (!is_array($schema['mainEntity'])) {
                $errors[] = "FAQPage: 'mainEntity' should be an array";
            }
            break;

        case 'HowTo':
            if (!isset($schema['name'])) $errors[] = "HowTo: Missing 'name'";
            if (!isset($schema['step'])) {
                $errors[] = "HowTo: Missing 'step'";
            } elseif (!is_array($schema['step'])) {
                $errors[] = "HowTo: 'step' should be an array";
            }
            break;
    }

    return ['errors' => $errors, 'warnings' => $warnings];
}

function checkDuplicateAggregateRating($schemas) {
    $aggregateRatingCount = 0;

    foreach ($schemas as $schema) {
        // Check standalone AggregateRating
        if (isset($schema['@type']) && $schema['@type'] === 'AggregateRating') {
            $aggregateRatingCount++;
        }

        // Check nested AggregateRating
        if (isset($schema['aggregateRating'])) {
            $aggregateRatingCount++;
        }
    }

    return $aggregateRatingCount;
}

function reportSchemas($pageName, $url, $schemas) {
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "PAGE: $pageName\n";
    echo "URL: $url\n";
    echo str_repeat("=", 80) . "\n";

    if (empty($schemas)) {
        echo "❌ NO SCHEMA FOUND\n";
        return;
    }

    echo "Found " . count($schemas) . " schema(s)\n\n";

    $allErrors = [];
    $allWarnings = [];

    // Check for duplicate AggregateRating
    $ratingCount = checkDuplicateAggregateRating($schemas);
    if ($ratingCount > 1) {
        $error = "DUPLICATE: Found $ratingCount AggregateRating instances (should only have 1)";
        $allErrors[] = $error;
        echo "❌ ERROR: $error\n";
    }

    foreach ($schemas as $i => $schema) {
        echo "Schema #" . ($i + 1) . ": ";

        if (isset($schema['error'])) {
            echo "❌ INVALID JSON\n";
            echo "  Error: {$schema['error']}\n";
            echo "  Raw: {$schema['raw']}\n";
            continue;
        }

        $type = $schema['@type'] ?? 'Unknown';
        echo "$type\n";

        // Validate
        $validation = validateBeachSchema($schema, $pageName);

        if (!empty($validation['errors'])) {
            foreach ($validation['errors'] as $error) {
                echo "  ❌ ERROR: $error\n";
                $allErrors[] = "$type: $error";
            }
        }

        if (!empty($validation['warnings'])) {
            foreach ($validation['warnings'] as $warning) {
                echo "  ⚠️  WARNING: $warning\n";
                $allWarnings[] = "$type: $warning";
            }
        }

        if (empty($validation['errors']) && empty($validation['warnings'])) {
            echo "  ✅ Valid\n";
        }

        // Show key properties
        if ($type === 'Beach' || $type === 'TouristAttraction') {
            if (isset($schema['image'])) {
                if (is_array($schema['image'])) {
                    $imgType = $schema['image']['@type'] ?? 'unknown';
                    $imgUrl = $schema['image']['url'] ?? 'missing';
                    $dimensions = '';
                    if (isset($schema['image']['width']) && isset($schema['image']['height'])) {
                        $dimensions = " ({$schema['image']['width']}x{$schema['image']['height']})";
                    }
                    echo "  📷 Image: $imgType - " . basename($imgUrl) . $dimensions . "\n";
                } else {
                    echo "  📷 Image: " . basename($schema['image']) . " (string)\n";
                }
            }

            if (isset($schema['aggregateRating'])) {
                $rating = $schema['aggregateRating']['ratingValue'] ?? 'N/A';
                $count = $schema['aggregateRating']['reviewCount'] ?? 'N/A';
                echo "  ⭐ Rating: $rating ($count reviews)\n";
            }
        }

        if ($type === 'BreadcrumbList') {
            $itemCount = count($schema['itemListElement'] ?? []);
            echo "  🔗 Breadcrumbs: $itemCount items\n";
        }

        if ($type === 'FAQPage') {
            $faqCount = count($schema['mainEntity'] ?? []);
            echo "  ❓ FAQs: $faqCount questions\n";
        }

        if ($type === 'HowTo') {
            $stepCount = count($schema['step'] ?? []);
            echo "  📝 Steps: $stepCount\n";
        }
    }

    echo "\n";

    return [
        'errors' => $allErrors,
        'warnings' => $allWarnings,
        'schemaCount' => count($schemas)
    ];
}

// Main execution
try {
    $baseUrl = 'https://puertoricobeachfinder.com';

    $testPages = [
        // Beach pages
        ['Beach: Pitahaya', "$baseUrl/beach/pitahaya"],
        ['Beach: Las Picúas', "$baseUrl/beach/las-picas-ro-grande-18428-65771"],
        ['Beach: Pozita de los Tubos', "$baseUrl/beach/pozita-de-los-tubos-manat-1845-66483"],

        // Editorial page
        ['Editorial: Best Beaches San Juan', "$baseUrl/best-beaches-san-juan.php"],

        // Guide pages
        ['Guide: Best Time to Visit', "$baseUrl/guides/best-time-visit-puerto-rico-beaches.php"],
        ['Guide: Snorkeling Guide', "$baseUrl/guides/snorkeling-guide.php"],
    ];

    $totalErrors = 0;
    $totalWarnings = 0;
    $summary = [];

    foreach ($testPages as $page) {
        [$name, $url] = $page;

        try {
            $html = fetchPageContent($url);
            $schemas = extractJsonLdSchemas($html);
            $result = reportSchemas($name, $url, $schemas);

            $totalErrors += count($result['errors']);
            $totalWarnings += count($result['warnings']);

            $summary[] = [
                'name' => $name,
                'schemaCount' => $result['schemaCount'],
                'errors' => count($result['errors']),
                'warnings' => count($result['warnings'])
            ];

        } catch (Exception $e) {
            echo "\n" . str_repeat("=", 80) . "\n";
            echo "PAGE: $name\n";
            echo "URL: $url\n";
            echo str_repeat("=", 80) . "\n";
            echo "❌ ERROR: " . $e->getMessage() . "\n";
            $totalErrors++;
        }
    }

    // Summary
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "VALIDATION SUMMARY\n";
    echo str_repeat("=", 80) . "\n\n";

    foreach ($summary as $item) {
        $status = $item['errors'] > 0 ? '❌' : ($item['warnings'] > 0 ? '⚠️ ' : '✅');
        echo "$status {$item['name']}\n";
        echo "   Schemas: {$item['schemaCount']} | Errors: {$item['errors']} | Warnings: {$item['warnings']}\n";
    }

    echo "\n";
    echo "Total Errors: $totalErrors\n";
    echo "Total Warnings: $totalWarnings\n";

    if ($totalErrors > 0) {
        exit(1);
    }

} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
