#!/usr/bin/env php
<?php
/**
 * Content Generation CLI - Orchestrates batch content generation for beaches
 *
 * Usage:
 *   php generate-beach-content.php [options]
 *
 * Options:
 *   --beach-id=ID         Generate content for a single beach
 *   --start=ID            Start from specific beach ID
 *   --batch-size=N        Process N beaches per batch (default: 50)
 *   --validate-only       Only validate existing content, don't generate
 *   --dry-run             Generate but don't save to database
 *   --approve             Approve all generated content (set status='published')
 *   --test-api            Test API connection and exit
 *   --help                Show this help message
 *
 * Examples:
 *   php generate-beach-content.php
 *   php generate-beach-content.php --beach-id=abc123
 *   php generate-beach-content.php --start=abc123 --batch-size=25
 *   php generate-beach-content.php --validate-only
 *   php generate-beach-content.php --dry-run
 */

// Ensure running from CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Load dependencies
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/content/PromptBuilder.php';
require_once __DIR__ . '/content/ContentValidator.php';
require_once __DIR__ . '/content/ContentGenerator.php';
require_once __DIR__ . '/content/BatchProcessor.php';

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

function success($msg) {
    echo colorize("✓ {$msg}", 'green') . "\n";
}

function error($msg) {
    echo colorize("✗ {$msg}", 'red') . "\n";
}

function warning($msg) {
    echo colorize("⚠ {$msg}", 'yellow') . "\n";
}

function info($msg) {
    echo colorize("ℹ {$msg}", 'blue') . "\n";
}

// Parse command line arguments
function parseArgs($argv) {
    $options = [];
    foreach ($argv as $arg) {
        if (strpos($arg, '--') === 0) {
            $parts = explode('=', substr($arg, 2), 2);
            $key = $parts[0];
            $value = $parts[1] ?? true;
            $options[$key] = $value;
        }
    }
    return $options;
}

// Show help
function showHelp() {
    global $argv;
    $script = basename($argv[0]);

    echo "\n";
    echo colorize("Content Generation CLI", 'blue') . "\n";
    echo str_repeat('=', 70) . "\n\n";
    echo "Generate extended content for Puerto Rico beaches using Claude AI.\n\n";
    echo "Usage:\n";
    echo "  php {$script} [options]\n\n";
    echo "Options:\n";
    echo "  --beach-id=ID         Generate content for a single beach\n";
    echo "  --start=ID            Start from specific beach ID\n";
    echo "  --batch-size=N        Process N beaches per batch (default: 50)\n";
    echo "  --validate-only       Only validate existing content, don't generate\n";
    echo "  --dry-run             Generate but don't save to database\n";
    echo "  --approve             Approve all generated content (set status='published')\n";
    echo "  --test-api            Test API connection and exit\n";
    echo "  --help                Show this help message\n\n";
    echo "Examples:\n";
    echo "  php {$script}\n";
    echo "  php {$script} --beach-id=abc123\n";
    echo "  php {$script} --start=abc123 --batch-size=25\n";
    echo "  php {$script} --validate-only\n";
    echo "  php {$script} --dry-run\n\n";
    echo "Environment:\n";
    echo "  ANTHROPIC_API_KEY     Required: Your Anthropic API key\n\n";
}

// Main execution
function main($argv) {
    $options = parseArgs($argv);

    // Show help
    if (isset($options['help'])) {
        showHelp();
        return 0;
    }

    echo "\n";
    echo colorize("Beach Content Generator", 'blue') . "\n";
    echo str_repeat('=', 70) . "\n";

    // Check for API key
    $apiKey = getenv('ANTHROPIC_API_KEY');
    if (!$apiKey) {
        error("ANTHROPIC_API_KEY environment variable not set");
        echo "\nSet your API key:\n";
        echo "  export ANTHROPIC_API_KEY='your-key-here'\n\n";
        return 1;
    }

    // Initialize components
    try {
        $generator = new ContentGenerator($apiKey);
        $validator = new ContentValidator();
        $processor = new BatchProcessor(null, $generator, $validator);

        // Test API connection if requested
        if (isset($options['test-api'])) {
            info("Testing API connection...");
            if ($generator->testConnection()) {
                success("API connection successful");
                info("Model: " . $generator->getModel());
                return 0;
            } else {
                error("API connection failed");
                return 1;
            }
        }

        // Configure batch processor
        if (isset($options['batch-size'])) {
            $processor->setBatchSize((int)$options['batch-size']);
        }

        // Prepare processing options
        $processOptions = [
            'beach_id' => $options['beach-id'] ?? null,
            'start_beach_id' => $options['start'] ?? null,
            'batch_size' => isset($options['batch-size']) ? (int)$options['batch-size'] : 50,
            'dry_run' => isset($options['dry-run']),
            'validate_only' => isset($options['validate-only'])
        ];

        // Show configuration
        echo "\nConfiguration:\n";
        echo str_repeat('-', 70) . "\n";
        echo "Model:           " . $generator->getModel() . "\n";
        if ($processOptions['beach_id']) {
            echo "Mode:            Single beach\n";
            echo "Beach ID:        " . $processOptions['beach_id'] . "\n";
        } else {
            echo "Mode:            Batch processing\n";
            echo "Batch size:      " . $processOptions['batch_size'] . "\n";
            if ($processOptions['start_beach_id']) {
                echo "Start from:      " . $processOptions['start_beach_id'] . "\n";
            }
        }
        if ($processOptions['validate_only']) {
            echo "Action:          Validate only\n";
        } elseif ($processOptions['dry_run']) {
            echo "Action:          Dry run (no save)\n";
        } else {
            echo "Action:          Generate and save\n";
        }
        echo str_repeat('-', 70) . "\n\n";

        // Confirm before proceeding
        if (!$processOptions['validate_only'] && !$processOptions['dry_run'] && !$processOptions['beach_id']) {
            echo "This will generate content for all beaches. Continue? [y/N] ";
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            if (trim(strtolower($line)) !== 'y') {
                echo "Cancelled.\n";
                return 0;
            }
        }

        // Process beaches
        info("Starting content generation...\n");

        $stats = $processor->processBatches($processOptions);

        // Show report
        $report = $processor->generateReport($stats);
        echo $report;

        // Approve content if requested
        if (isset($options['approve']) && $stats['succeeded'] > 0) {
            info("\nApproving generated content...");
            approveContent();
            success("Content approved and published");
        }

        // Exit code based on results
        if ($stats['failed'] > 0) {
            warning("Completed with errors");
            return 1;
        } else {
            success("All beaches processed successfully");
            return 0;
        }

    } catch (Exception $e) {
        error("Fatal error: " . $e->getMessage());
        echo "\nStack trace:\n" . $e->getTraceAsString() . "\n\n";
        return 1;
    }
}

/**
 * Approve all draft content (set status to published)
 */
function approveContent() {
    require_once __DIR__ . '/../inc/db.php';
    execute(
        "UPDATE beach_content_sections SET status = 'published', approved_at = datetime('now') WHERE status = 'draft'"
    );
}

// Run main
exit(main($argv));
