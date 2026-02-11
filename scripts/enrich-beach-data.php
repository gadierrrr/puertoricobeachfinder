#!/usr/bin/env php
<?php
/**
 * Beach Enrichment CLI - Classifies bare beaches with tags, amenities, features, tips
 *
 * Usage:
 *   php enrich-beach-data.php [options]
 *
 * Options:
 *   --beach-id=ID     Enrich a single beach
 *   --start=ID        Start from specific beach ID
 *   --batch-size=N    Process N beaches per batch (default: 50)
 *   --dry-run         Generate but don't save to database
 *   --test-api        Test API connection and exit
 *   --help            Show this help message
 *
 * Environment:
 *   ANTHROPIC_API_KEY  Required: Your Anthropic API key
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Load dependencies
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/content/EnrichmentPromptBuilder.php';
require_once __DIR__ . '/content/EnrichmentProcessor.php';

// Color output helpers
function colorize($text, $color) {
    $colors = [
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'reset' => "\033[0m"
    ];
    return ($colors[$color] ?? '') . $text . $colors['reset'];
}

function success($msg) { echo colorize("OK {$msg}", 'green') . "\n"; }
function error($msg) { echo colorize("FAIL {$msg}", 'red') . "\n"; }
function warning($msg) { echo colorize("WARN {$msg}", 'yellow') . "\n"; }
function info($msg) { echo colorize("INFO {$msg}", 'blue') . "\n"; }

function parseArgs($argv) {
    $options = [];
    foreach ($argv as $arg) {
        if (strpos($arg, '--') === 0) {
            $parts = explode('=', substr($arg, 2), 2);
            $options[$parts[0]] = $parts[1] ?? true;
        }
    }
    return $options;
}

function showHelp() {
    global $argv;
    $script = basename($argv[0]);

    echo "\n";
    echo colorize("Beach Enrichment CLI", 'blue') . "\n";
    echo str_repeat('=', 70) . "\n\n";
    echo "Classify bare beaches with tags, amenities, features, tips, and field data.\n\n";
    echo "Usage:\n";
    echo "  php {$script} [options]\n\n";
    echo "Options:\n";
    echo "  --beach-id=ID     Enrich a single beach\n";
    echo "  --start=ID        Start from specific beach ID\n";
    echo "  --batch-size=N    Process N beaches per batch (default: 50)\n";
    echo "  --dry-run         Generate but don't save to database\n";
    echo "  --test-api        Test API connection and exit\n";
    echo "  --help            Show this help message\n\n";
    echo "Environment:\n";
    echo "  ANTHROPIC_API_KEY  Required\n\n";
    echo "Examples:\n";
    echo "  php {$script} --test-api\n";
    echo "  php {$script} --batch-size=5 --dry-run\n";
    echo "  php {$script} --batch-size=50\n";
    echo "  php {$script} --beach-id=abc123\n\n";
}

function showCurrentStats() {
    $totalBeaches = queryOne("SELECT COUNT(*) as c FROM beaches")['c'];
    $withTags = queryOne("SELECT COUNT(DISTINCT beach_id) as c FROM beach_tags")['c'];
    $withAmenities = queryOne("SELECT COUNT(DISTINCT beach_id) as c FROM beach_amenities")['c'];
    $withFeatures = queryOne("SELECT COUNT(DISTINCT beach_id) as c FROM beach_features")['c'];
    $withTips = queryOne("SELECT COUNT(DISTINCT beach_id) as c FROM beach_tips")['c'];
    $withBestTime = queryOne("SELECT COUNT(*) as c FROM beaches WHERE best_time IS NOT NULL AND best_time <> ''")['c'];
    $bareCount = queryOne("SELECT COUNT(*) as c FROM beaches WHERE id NOT IN (SELECT DISTINCT beach_id FROM beach_tags) AND (best_time IS NULL OR best_time = '')")['c'];

    echo "\nCurrent Database State:\n";
    echo str_repeat('-', 40) . "\n";
    echo "Total beaches:       {$totalBeaches}\n";
    echo "With tags:           {$withTags}\n";
    echo "With amenities:      {$withAmenities}\n";
    echo "With features:       {$withFeatures}\n";
    echo "With tips:           {$withTips}\n";
    echo "With best_time:      {$withBestTime}\n";
    echo "Bare (to enrich):    {$bareCount}\n";
    echo str_repeat('-', 40) . "\n";
}

function main($argv) {
    $options = parseArgs($argv);

    if (isset($options['help'])) {
        showHelp();
        return 0;
    }

    echo "\n";
    echo colorize("Beach Data Enrichment", 'blue') . "\n";
    echo str_repeat('=', 70) . "\n";

    // Check API key
    $apiKey = getenv('ANTHROPIC_API_KEY');
    if (!$apiKey) {
        error("ANTHROPIC_API_KEY environment variable not set");
        echo "\nSet your API key:\n";
        echo "  export ANTHROPIC_API_KEY='your-key-here'\n\n";
        return 1;
    }

    $processor = new EnrichmentProcessor($apiKey);

    // Test API
    if (isset($options['test-api'])) {
        info("Testing API connection...");
        if ($processor->testConnection()) {
            success("API connection successful");
            info("Model: " . $processor->getModel());
            showCurrentStats();
            return 0;
        } else {
            error("API connection failed");
            return 1;
        }
    }

    // Show stats
    showCurrentStats();

    // Configure
    $processOptions = [
        'beach_id' => $options['beach-id'] ?? null,
        'start_beach_id' => $options['start'] ?? null,
        'batch_size' => isset($options['batch-size']) ? (int)$options['batch-size'] : 50,
        'dry_run' => isset($options['dry-run'])
    ];

    echo "\nConfiguration:\n";
    echo str_repeat('-', 40) . "\n";
    echo "Model:           " . $processor->getModel() . "\n";
    if ($processOptions['beach_id']) {
        echo "Mode:            Single beach\n";
        echo "Beach ID:        {$processOptions['beach_id']}\n";
    } else {
        echo "Mode:            Batch processing\n";
        echo "Batch size:      {$processOptions['batch_size']}\n";
        if ($processOptions['start_beach_id']) {
            echo "Start from:      {$processOptions['start_beach_id']}\n";
        }
    }
    if ($processOptions['dry_run']) {
        echo "Action:          Dry run (no save)\n";
    } else {
        echo "Action:          Enrich and save\n";
    }
    echo str_repeat('-', 40) . "\n\n";

    // Confirm
    if (!$processOptions['dry_run'] && !$processOptions['beach_id']) {
        echo "This will enrich bare beaches with AI-generated data. Continue? [y/N] ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim(strtolower($line)) !== 'y') {
            echo "Cancelled.\n";
            return 0;
        }
    }

    info("Starting enrichment...\n");

    $stats = $processor->processBatches($processOptions);
    $report = $processor->generateReport($stats);
    echo $report;

    // Show updated stats
    if (!$processOptions['dry_run'] && $stats['succeeded'] > 0) {
        showCurrentStats();
    }

    if ($stats['failed'] > 0) {
        warning("Completed with errors — check scripts/enrichment.log");
        return 1;
    }

    success("All beaches enriched successfully");
    return 0;
}

exit(main($argv));
