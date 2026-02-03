#!/usr/bin/env php
<?php
/**
 * Extract and display raw JSON-LD schema from a sample beach page
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

// Fetch a sample beach page
$url = 'https://puertoricobeachfinder.com/beach/pitahaya';
$html = fetchPageContent($url);
$schemas = extractJsonLdSchemas($html);

echo "Sample Beach Page Schema Extraction\n";
echo "URL: $url\n";
echo str_repeat("=", 80) . "\n\n";

foreach ($schemas as $i => $schema) {
    $type = $schema['@type'] ?? 'Unknown';
    echo "Schema #" . ($i + 1) . ": $type\n";
    echo str_repeat("-", 80) . "\n";
    echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
}
