#!/usr/bin/env php
<?php
/**
 * Extract schema from guide page with HowTo
 */

function fetchPageContent($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $html = curl_exec($ch);
    curl_close($ch);
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

// Fetch guide page
$url = 'https://puertoricobeachfinder.com/guides/snorkeling-guide.php';
$html = fetchPageContent($url);
$schemas = extractJsonLdSchemas($html);

echo "Guide Page Schema Extraction: Snorkeling Guide\n";
echo str_repeat("=", 80) . "\n\n";

foreach ($schemas as $i => $schema) {
    $type = $schema['@type'] ?? 'Unknown';

    if ($type === 'HowTo') {
        echo "HowTo Schema\n";
        echo str_repeat("-", 80) . "\n";
        echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
    } elseif ($type === 'Article') {
        echo "Article Schema\n";
        echo str_repeat("-", 80) . "\n";
        // Show only key properties
        echo "Type: {$schema['@type']}\n";
        echo "Headline: {$schema['headline']}\n";
        echo "Description: " . substr($schema['description'] ?? '', 0, 100) . "...\n";
        echo "Author: {$schema['author']['name']}\n";
        echo "Publisher: {$schema['publisher']['name']}\n\n";
    } else {
        echo "$type Schema: ✅\n";
    }
}
