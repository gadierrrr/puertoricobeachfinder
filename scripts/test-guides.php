#!/usr/bin/env php
<?php
/**
 * Test script to verify all guide pages load correctly
 * Tests for HTTP 200, h1 heading, and schema markup
 */

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../public') ?: (__DIR__ . '/../public');
chdir(__DIR__ . '/..');
ob_start();

$guidePages = [
    'index.php' => 'Puerto Rico Beach Guides',
    'getting-to-puerto-rico-beaches.php' => 'Getting to Puerto Rico Beaches',
    'beach-safety-tips.php' => 'Beach Safety Tips',
    'best-time-visit-puerto-rico-beaches.php' => 'Best Time to Visit',
    'beach-packing-list.php' => 'Beach Packing List',
    'culebra-vs-vieques.php' => 'Culebra vs Vieques',
    'bioluminescent-bays.php' => 'Bioluminescent Bays',
    'snorkeling-guide.php' => 'Snorkeling Guide',
    'surfing-guide.php' => 'Surfing Guide',
    'beach-photography-tips.php' => 'Beach Photography Tips',
    'family-beach-vacation-planning.php' => 'Family Beach Vacation Planning'
];

$results = [];
$passed = 0;
$failed = 0;

echo "\n========================================\n";
echo "Testing Guide Pages\n";
echo "========================================\n\n";

foreach ($guidePages as $file => $name) {
	    $path = "public/guides/$file";
	    $_SERVER['REQUEST_URI'] = $file === 'index.php' ? '/guides/' : "/guides/{$file}";
	    $result = [
	        'name' => $name,
	        'file' => $file,
	        'exists' => false,
	        'has_h1' => false,
	        'has_schema' => false,
	        'status' => 'FAIL',
	        'errors' => []
	    ];

    // Check if file exists
    if (!file_exists($path)) {
        $result['errors'][] = "File not found";
        $results[] = $result;
        $failed++;
        continue;
    }

    $result['exists'] = true;

    // Capture output
	    ob_start();
	    try {
	        include $path;
	        $content = ob_get_clean();
	    } catch (Throwable $e) {
	        ob_end_clean();
	        $result['errors'][] = "PHP Error: " . $e->getMessage();
	        $results[] = $result;
	        $failed++;
	        continue;
    }

    // Check for h1 heading
    if (preg_match('/<h1[^>]*>(.+?)<\/h1>/is', $content, $matches)) {
        $result['has_h1'] = true;
        $result['h1_text'] = strip_tags($matches[1]);
    } else {
        $result['errors'][] = "Missing h1 heading";
    }

    // Check for schema markup (HowTo or FAQPage)
    if (preg_match('/"@type":\s*"(HowTo|FAQPage|CollectionPage)"/i', $content, $matches)) {
        $result['has_schema'] = true;
        $result['schema_type'] = $matches[1];
    } else {
        $result['errors'][] = "Missing HowTo/FAQPage schema markup";
    }

    // Check for title tag
    if (preg_match('/<title>(.+?)<\/title>/is', $content, $matches)) {
        $result['title'] = trim($matches[1]);
    }

    // Determine pass/fail
    if ($result['exists'] && $result['has_h1'] && $result['has_schema'] && empty($result['errors'])) {
        $passed++;
        $result['status'] = 'PASS';
    } else {
        $failed++;
        $result['status'] = 'FAIL';
    }

    $results[] = $result;
}

// Display results
foreach ($results as $result) {
    $status_icon = $result['status'] === 'PASS' ? '✓' : '✗';
    $status_color = $result['status'] === 'PASS' ? "\033[32m" : "\033[31m";
    $reset_color = "\033[0m";

    echo "{$status_color}{$status_icon}{$reset_color} {$result['name']}\n";
    echo "  File: {$result['file']}\n";

    if ($result['exists']) {
        echo "  Exists: Yes\n";
    } else {
        echo "  Exists: No\n";
    }

    if ($result['has_h1']) {
        echo "  H1: Yes (" . substr($result['h1_text'], 0, 50) . "...)\n";
    } else {
        echo "  H1: No\n";
    }

    if ($result['has_schema']) {
        echo "  Schema: Yes ({$result['schema_type']})\n";
    } else {
        echo "  Schema: No\n";
    }

    if (isset($result['title'])) {
        echo "  Title: " . substr($result['title'], 0, 60) . "...\n";
    }

    if (!empty($result['errors'])) {
        echo "  {$status_color}Errors:{$reset_color}\n";
        foreach ($result['errors'] as $error) {
            echo "    - $error\n";
        }
    }

    echo "\n";
}

// Summary
echo "========================================\n";
echo "Summary\n";
echo "========================================\n";
echo "Total: " . count($guidePages) . " pages\n";
echo "{$status_color}Passed: $passed{$reset_color}\n";
if ($failed > 0) {
    echo "\033[31mFailed: $failed\033[0m\n";
} else {
    echo "Failed: $failed\n";
}
echo "\n";

// Exit with appropriate code
$exitCode = $failed > 0 ? 1 : 0;
ob_end_flush();
exit($exitCode);
