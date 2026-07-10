#!/usr/bin/env php
<?php
/**
 * Refresh the eight curated Viator products in English and Spanish.
 *
 * Usage:
 *   php scripts/sync-viator-products.php
 *   php scripts/sync-viator-products.php --sandbox
 *   php scripts/sync-viator-products.php --product=14939P2 --locale=en
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/viator.php';

$options = getopt('', ['sandbox', 'product:', 'locale:', 'help']);
if (isset($options['help'])) {
    echo "Usage: php scripts/sync-viator-products.php [--sandbox] [--product=CODE] [--locale=en|es]\n";
    exit(0);
}

if (isset($options['sandbox'])) {
    $_ENV['VIATOR_API_BASE_URL'] = 'https://api.sandbox.viator.com/partner';
    putenv('VIATOR_API_BASE_URL=https://api.sandbox.viator.com/partner');
}

if (!viatorIsConfigured()) {
    fwrite(STDERR, "Set VIATOR_API_ENABLED=1 and configure VIATOR_API_KEY. No data changed.\n");
    exit(2);
}

$productFilter = strtoupper(trim((string) ($options['product'] ?? '')));
$localeFilter = viatorNormalizeLocale((string) ($options['locale'] ?? ''));
$locales = isset($options['locale']) ? [$localeFilter] : ['en', 'es'];
$mappings = viatorCampaignProductMappings();
if ($productFilter !== '') {
    $mappings = array_values(array_filter(
        $mappings,
        static fn(array $row): bool => strtoupper((string) $row['product_code']) === $productFilter
    ));
}

if ($mappings === []) {
    fwrite(STDERR, "No active curated Viator campaign mappings found. Run migrations first.\n");
    exit(3);
}

$runId = uuid();
$environment = str_contains(viatorApiBaseUrl(), 'sandbox') ? 'sandbox' : 'production';
execute(
    'INSERT INTO viator_sync_runs
        (id, status, environment, products_attempted, products_updated, errors_count, started_at, created_at)
     VALUES (:id, "running", :environment, 0, 0, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
    [':id' => $runId, ':environment' => $environment]
);

$attempted = 0;
$updated = 0;
$errors = [];
$abortAuthentication = false;

foreach ($mappings as $campaign) {
    foreach ($locales as $locale) {
        $attempted++;
        $label = $campaign['slug'] . ' / ' . $campaign['product_code'] . ' / ' . $locale;
        try {
            $product = viatorSyncCampaign($campaign, (string) $campaign['product_code'], $locale);
            $updated++;
            echo '[ok] ' . $label . ' (' . ($product['status'] ?? 'UNKNOWN') . ")\n";
        } catch (Throwable $e) {
            $errors[] = $label . ': ' . $e->getMessage();
            fwrite(STDERR, '[error] ' . end($errors) . "\n");
            if (str_contains($e->getMessage(), 'HTTP 401')) {
                $abortAuthentication = true;
                break 2;
            }
        }
    }
}

$summary = [
    'attempted' => $attempted,
    'updated' => $updated,
    'errors' => count($errors),
    'environment' => $environment,
    'authentication_aborted' => $abortAuthentication,
];
$status = $errors === [] ? 'completed' : ($updated > 0 ? 'completed_with_errors' : 'failed');
execute(
    'UPDATE viator_sync_runs
     SET status = :status,
         products_attempted = :attempted,
         products_updated = :updated,
         errors_count = :errors,
         finished_at = CURRENT_TIMESTAMP,
         summary_json = :summary,
         error_log = :error_log
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
