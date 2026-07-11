#!/usr/bin/env php
<?php
/**
 * Nightly Viator catalog sync: taxonomy, per-destination product search
 * sweeps (EN + ES), and beach auto-matching.
 *
 * The hourly scripts/sync-viator-products.php keeps the small curated set
 * fresh; this script maintains the broad catalog that powers auto-matched
 * placements across all beach pages.
 *
 * Usage:
 *   php scripts/sync-viator-catalog.php
 *   php scripts/sync-viator-catalog.php --sandbox
 *   php scripts/sync-viator-catalog.php --skip-taxonomy
 *   php scripts/sync-viator-catalog.php --match-only
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/viator.php';

$options = getopt('', ['sandbox', 'skip-taxonomy', 'match-only', 'help']);
if (isset($options['help'])) {
    echo "Usage: php scripts/sync-viator-catalog.php [--sandbox] [--skip-taxonomy] [--match-only]\n";
    exit(0);
}

if (isset($options['sandbox'])) {
    $_ENV['VIATOR_API_BASE_URL'] = 'https://api.sandbox.viator.com/partner';
    putenv('VIATOR_API_BASE_URL=https://api.sandbox.viator.com/partner');
}

foreach (['viator_destinations', 'viator_tags', 'viator_municipality_destinations', 'viator_beach_products'] as $table) {
    if (!viatorTableExists($table)) {
        fwrite(STDERR, "Missing table {$table}. Run php scripts/migrate.php first.\n");
        exit(3);
    }
}

$matchOnly = isset($options['match-only']);
if (!$matchOnly && !viatorIsConfigured()) {
    fwrite(STDERR, "Set VIATOR_API_ENABLED=1 and configure VIATOR_API_KEY. No data changed.\n");
    exit(2);
}

$runId = uuid();
$environment = str_contains(viatorApiBaseUrl(), 'sandbox') ? 'sandbox' : 'production';
execute(
    'INSERT INTO viator_sync_runs
        (id, status, environment, products_attempted, products_updated, errors_count, started_at, created_at, summary_json)
     VALUES (:id, "running", :environment, 0, 0, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, :summary)',
    [':id' => $runId, ':environment' => $environment, ':summary' => json_encode(['kind' => 'catalog'])]
);

$attempted = 0;
$updated = 0;
$errors = [];
$summary = ['kind' => 'catalog', 'environment' => $environment];

try {
    if (!$matchOnly) {
        if (!isset($options['skip-taxonomy'])) {
            $summary['destinations'] = viatorSyncDestinations();
            echo "[ok] destinations: {$summary['destinations']}\n";
            $summary['tags'] = viatorSyncTags();
            echo "[ok] tags: {$summary['tags']}\n";
            $summary['municipality_mappings_added'] = viatorRefreshMunicipalityDestinations();
            echo "[ok] new municipality mappings: {$summary['municipality_mappings_added']}\n";
        }

        $destinationRows = query(
            'SELECT DISTINCT destination_id FROM viator_municipality_destinations
             UNION SELECT ' . VIATOR_PUERTO_RICO_DESTINATION_ID
        );
        $destinationIds = array_values(array_unique(array_map(
            static fn(array $row): int => (int) $row['destination_id'],
            is_array($destinationRows) ? $destinationRows : []
        )));

        foreach ($destinationIds as $destinationId) {
            $campaignValue = viatorCampaignValueForDestination($destinationId);
            foreach (['en', 'es'] as $locale) {
                $attempted++;
                $label = 'destination ' . $destinationId . ' / ' . $locale . ' / ' . $campaignValue;
                try {
                    $products = viatorSearchDestinationProducts($destinationId, $locale, $campaignValue);
                    foreach ($products as $product) {
                        viatorUpsertSearchProduct($product, $locale, $campaignValue);
                    }
                    $updated++;
                    echo '[ok] ' . $label . ' (' . count($products) . " products)\n";
                } catch (Throwable $e) {
                    $errors[] = $label . ': ' . $e->getMessage();
                    fwrite(STDERR, '[error] ' . end($errors) . "\n");
                    if (str_contains($e->getMessage(), 'HTTP 401')) {
                        throw new RuntimeException('Authentication failed; aborting sweep.');
                    }
                }
                // Stay polite to the shared partner-API rate budget.
                usleep(300000);
            }
        }

        // A product missing from every sweep for 7+ days is stale: it can no
        // longer be verified as bookable, so stop matching it.
        execute(
            'UPDATE viator_products SET status = "STALE"
             WHERE source = "catalog_search" AND status = "ACTIVE"
               AND fetched_at < datetime("now", "-7 days")'
        );
        $summary['stale_products'] = getDb()->changes();
    }

    $matchStats = viatorRebuildBeachMatches();
    $summary['beaches_matched'] = $matchStats['beaches_matched'];
    $summary['beach_product_matches'] = $matchStats['matches'];
    echo "[ok] auto-matching: {$matchStats['matches']} matches across {$matchStats['beaches_matched']} beaches\n";
} catch (Throwable $e) {
    $errors[] = $e->getMessage();
    fwrite(STDERR, '[fatal] ' . $e->getMessage() . "\n");
}

$summary['attempted'] = $attempted;
$summary['updated'] = $updated;
$summary['errors'] = count($errors);
$status = $errors === [] ? 'completed' : ($updated > 0 ? 'completed_with_errors' : 'failed');
if ($matchOnly && $errors === []) {
    $status = 'completed';
}

execute(
    'UPDATE viator_sync_runs
     SET status = :status, products_attempted = :attempted, products_updated = :updated,
         errors_count = :errors, finished_at = CURRENT_TIMESTAMP,
         summary_json = :summary, error_log = :error_log
     WHERE id = :id',
    [
        ':status' => $status,
        ':attempted' => $attempted,
        ':updated' => $updated,
        ':errors' => count($errors),
        ':summary' => json_encode($summary, JSON_UNESCAPED_SLASHES),
        ':error_log' => $errors !== [] ? implode("\n", $errors) : null,
        ':id' => $runId,
    ]
);

echo json_encode($summary, JSON_UNESCAPED_SLASHES) . "\n";
exit($status === 'failed' ? 1 : 0);
