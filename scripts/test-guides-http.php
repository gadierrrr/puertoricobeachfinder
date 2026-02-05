#!/usr/bin/env php
<?php
/**
 * HTTP simulation test for all guide pages
 */

// Simulate HTTP environment
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['SERVER_PORT'] = '80';
$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../public') ?: (__DIR__ . '/../public');

$guides = [
    '/guides/' => 'index.php',
    '/guides/getting-to-puerto-rico-beaches.php' => 'getting-to-puerto-rico-beaches.php',
    '/guides/beach-safety-tips.php' => 'beach-safety-tips.php',
    '/guides/best-time-visit-puerto-rico-beaches.php' => 'best-time-visit-puerto-rico-beaches.php',
    '/guides/beach-packing-list.php' => 'beach-packing-list.php',
    '/guides/culebra-vs-vieques.php' => 'culebra-vs-vieques.php',
    '/guides/bioluminescent-bays.php' => 'bioluminescent-bays.php',
    '/guides/snorkeling-guide.php' => 'snorkeling-guide.php',
    '/guides/surfing-guide.php' => 'surfing-guide.php',
    '/guides/beach-photography-tips.php' => 'beach-photography-tips.php',
    '/guides/family-beach-vacation-planning.php' => 'family-beach-vacation-planning.php'
];

echo "\n" . str_repeat("=", 70) . "\n";
echo "Guide Pages HTTP Simulation Test\n";
echo str_repeat("=", 70) . "\n\n";

$results = [];
$passed = 0;
$failed = 0;

foreach ($guides as $uri => $file) {
    $_SERVER['REQUEST_URI'] = $uri;

    $filepath = 'public/guides/' . $file;

    // Capture output
    ob_start();

    try {
        include $filepath;
        $content = ob_get_clean();
        $error = null;
    } catch (Exception $e) {
        ob_end_clean();
        $content = '';
        $error = $e->getMessage();
    }

    // Analyze content
    $result = [
        'uri' => $uri,
        'status' => 'PASS',
        'checks' => []
    ];

    if ($error) {
        $result['status'] = 'FAIL';
        $result['checks'][] = "ERROR: $error";
        $failed++;
    } else {
        // Perform checks
        $checks = [
            'Has <!DOCTYPE html>' => preg_match('/<!DOCTYPE html>/i', $content),
            'Has <h1> heading' => preg_match('/<h1[^>]*>(.+?)<\/h1>/is', $content, $h1_match),
            'Has schema markup' => preg_match('/"@type":\s*"(HowTo|FAQPage|CollectionPage)"/i', $content, $schema_match),
            'Has title tag' => preg_match('/<title>(.+?)<\/title>/is', $content),
            'Has content (>5KB)' => strlen($content) > 5000,
            'Has navigation' => preg_match('/<nav[^>]*>/i', $content),
            'Has footer' => preg_match('/<footer[^>]*>/i', $content)
        ];

        $all_passed = true;
        foreach ($checks as $check => $passed_check) {
            if ($passed_check) {
                $result['checks'][] = "✓ $check";
                if ($check === 'Has <h1> heading' && isset($h1_match[1])) {
                    $result['h1'] = trim(strip_tags($h1_match[1]));
                }
                if ($check === 'Has schema markup' && isset($schema_match[1])) {
                    $result['schema'] = $schema_match[1];
                }
            } else {
                $result['checks'][] = "✗ $check";
                $all_passed = false;
            }
        }

        if ($all_passed) {
            $passed++;
        } else {
            $result['status'] = 'FAIL';
            $failed++;
        }

        $result['size'] = strlen($content);
    }

    $results[] = $result;
}

// Display results
foreach ($results as $result) {
    $status_color = $result['status'] === 'PASS' ? "\033[32m" : "\033[31m";
    $reset_color = "\033[0m";

    echo "{$status_color}{$result['status']}{$reset_color} {$result['uri']}\n";

    if (isset($result['h1'])) {
        echo "  H1: " . substr($result['h1'], 0, 60) . "...\n";
    }
    if (isset($result['schema'])) {
        echo "  Schema: {$result['schema']}\n";
    }
    if (isset($result['size'])) {
        echo "  Size: " . number_format($result['size']) . " bytes\n";
    }

    foreach ($result['checks'] as $check) {
        if (strpos($check, '✗') !== false) {
            echo "  {$status_color}{$check}{$reset_color}\n";
        }
    }

    echo "\n";
}

// Summary
echo str_repeat("=", 70) . "\n";
echo "Summary\n";
echo str_repeat("=", 70) . "\n";
echo "Total:  " . count($guides) . " pages\n";
echo "\033[32mPassed: $passed\033[0m\n";
if ($failed > 0) {
    echo "\033[31mFailed: $failed\033[0m\n";
} else {
    echo "Failed: $failed\n";
}
echo "\n";

exit($failed > 0 ? 1 : 0);
