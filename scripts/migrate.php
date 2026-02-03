#!/usr/bin/env php
<?php
/**
 * Migration runner with schema tracking.
 *
 * Usage:
 *   php scripts/migrate.php
 *   php scripts/migrate.php --dry-run
 *   php scripts/migrate.php --check
 *   php scripts/migrate.php --baseline
 *   php scripts/migrate.php --include-manual
 */

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

require_once __DIR__ . '/../inc/db.php';

const MANUAL_MIGRATIONS = [
    '010-import-beach-json.php',
];

$options = getopt('', ['dry-run', 'check', 'baseline', 'include-manual', 'help']);

if (isset($options['help'])) {
    echo "Migration Runner\n";
    echo "Usage: php scripts/migrate.php [--dry-run] [--check] [--baseline] [--include-manual]\n\n";
    echo "Flags:\n";
    echo "  --dry-run        List pending migrations and exit\n";
    echo "  --check          Exit non-zero when pending migrations exist\n";
    echo "  --baseline       Mark current pending migrations as applied without executing\n";
    echo "  --include-manual Include manual/data migrations (default excludes 010-import-beach-json.php)\n";
    exit(0);
}

$includeManual = isset($options['include-manual']);
$dryRun = isset($options['dry-run']);
$check = isset($options['check']);
$baseline = isset($options['baseline']);

$db = getDb();

$db->exec(
    "CREATE TABLE IF NOT EXISTS schema_migrations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        filename TEXT NOT NULL UNIQUE,
        checksum TEXT NOT NULL,
        mode TEXT NOT NULL DEFAULT 'applied',
        applied_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )"
);

$allMigrations = listMigrationFiles(__DIR__ . '/../migrations');
$candidateMigrations = array_values(array_filter($allMigrations, function (array $migration) use ($includeManual): bool {
    if ($includeManual) {
        return true;
    }

    return !$migration['is_manual'];
}));

$appliedRows = query('SELECT filename FROM schema_migrations');
$applied = array_flip(array_map(static fn($row) => $row['filename'], $appliedRows ?: []));

$pending = array_values(array_filter($candidateMigrations, static fn($migration) => !isset($applied[$migration['filename']])));

if ($dryRun) {
    printMigrationSummary($candidateMigrations, $pending, $includeManual, true);
    exit(0);
}

if ($check) {
    printMigrationSummary($candidateMigrations, $pending, $includeManual, true);
    if (!empty($pending)) {
        exit(1);
    }
    exit(0);
}

if ($baseline) {
    foreach ($pending as $migration) {
        recordMigration($migration, 'baseline');
        echo "[baseline] {$migration['filename']}\n";
    }

    echo "\nBaseline complete. Marked " . count($pending) . " migration(s).\n";
    exit(0);
}

if (empty($pending)) {
    printMigrationSummary($candidateMigrations, $pending, $includeManual, false);
    echo "No pending migrations.\n";
    exit(0);
}

printMigrationSummary($candidateMigrations, $pending, $includeManual, false);

echo "Applying " . count($pending) . " migration(s)...\n\n";

foreach ($pending as $migration) {
    $filePath = $migration['path'];
    $filename = $migration['filename'];

    echo "--> {$filename}\n";

    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($filePath);
    $output = [];
    $exitCode = 0;
    exec($cmd, $output, $exitCode);

    foreach ($output as $line) {
        echo "    {$line}\n";
    }

    if ($exitCode !== 0) {
        fwrite(STDERR, "\nMigration failed: {$filename}\n");
        exit($exitCode);
    }

    recordMigration($migration, 'applied');
    echo "    ✓ Applied {$filename}\n\n";
}

echo "All pending migrations applied successfully.\n";
exit(0);

function listMigrationFiles(string $dir): array {
    $files = glob($dir . '/*.php') ?: [];
    natsort($files);

    $result = [];
    foreach ($files as $filePath) {
        $filename = basename($filePath);
        $result[] = [
            'filename' => $filename,
            'path' => realpath($filePath) ?: $filePath,
            'checksum' => hash_file('sha256', $filePath) ?: '',
            'is_manual' => in_array($filename, MANUAL_MIGRATIONS, true),
        ];
    }

    return $result;
}

function recordMigration(array $migration, string $mode): void {
    $db = getDb();
    $stmt = $db->prepare(
        'INSERT INTO schema_migrations (filename, checksum, mode, applied_at)
         VALUES (:filename, :checksum, :mode, CURRENT_TIMESTAMP)'
    );

    $stmt->bindValue(':filename', $migration['filename'], SQLITE3_TEXT);
    $stmt->bindValue(':checksum', $migration['checksum'], SQLITE3_TEXT);
    $stmt->bindValue(':mode', $mode, SQLITE3_TEXT);

    if (!$stmt->execute()) {
        throw new RuntimeException('Failed to record migration: ' . $migration['filename']);
    }
}

function printMigrationSummary(array $candidates, array $pending, bool $includeManual, bool $showPendingList): void {
    echo "Migration summary\n";
    echo "-----------------\n";
    echo 'Mode: ' . ($includeManual ? 'automatic + manual' : 'automatic only') . "\n";
    echo 'Total candidates: ' . count($candidates) . "\n";
    echo 'Pending: ' . count($pending) . "\n\n";

    if ($showPendingList && !empty($pending)) {
        echo "Pending migrations:\n";
        foreach ($pending as $migration) {
            $manualLabel = $migration['is_manual'] ? ' [manual]' : '';
            echo "  - {$migration['filename']}{$manualLabel}\n";
        }
        echo "\n";
    }
}
