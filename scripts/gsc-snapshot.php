<?php
/**
 * Archives the current data/gsc-reports/*.json slices into
 * data/gsc-reports/history/YYYY-MM-DD/ so gsc-analyze.php can diff
 * week-over-week. Run right after gsc-fetch.php:
 *
 *   php scripts/gsc-fetch.php && php scripts/gsc-snapshot.php && php scripts/gsc-analyze.php
 */

$reportsDir = __DIR__ . '/../data/gsc-reports';
$historyDir = $reportsDir . '/history/' . date('Y-m-d');

$slices = glob($reportsDir . '/*.json');
if (!$slices) {
    fwrite(STDERR, "No reports in $reportsDir — run gsc-fetch.php first.\n");
    exit(1);
}

if (!is_dir($historyDir) && !mkdir($historyDir, 0755, true)) {
    fwrite(STDERR, "Cannot create $historyDir\n");
    exit(1);
}

foreach ($slices as $file) {
    copy($file, $historyDir . '/' . basename($file));
}
fwrite(STDERR, 'Snapshotted ' . count($slices) . " slices to $historyDir\n");

// Keep a year of weekly snapshots; prune anything older.
$cutoff = date('Y-m-d', strtotime('-370 days'));
foreach (glob($reportsDir . '/history/*', GLOB_ONLYDIR) ?: [] as $dir) {
    if (basename($dir) < $cutoff) {
        foreach (glob($dir . '/*') ?: [] as $f) {
            unlink($f);
        }
        rmdir($dir);
        fwrite(STDERR, 'Pruned old snapshot ' . basename($dir) . "\n");
    }
}
