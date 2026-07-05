<?php
/**
 * Migration 042: Viator regional campaign coverage for every coast.
 *
 * 041 covered metro (San Juan), east (Fajardo) and the cays; west, north and
 * south beaches only got the PR-wide fallback. This adds verified destination
 * pages for Rincón (west), Arecibo (north) and Ponce (south), a Vieques
 * campaign, and re-scopes the Culebra campaign from the 'cays' region to the
 * 'culebra' municipality slug so Vieques beaches don't show Culebra tours
 * (toursCampaignsForBeach matches municipality slug > region > global).
 *
 * All URLs verified live 2026-07-05. Idempotent — safe to re-run.
 */

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

echo "Starting migration: Viator regional campaigns\n";

$viator = queryOne("SELECT id FROM referral_providers WHERE slug = 'viator'");
if (!$viator) {
    echo "ERROR: viator provider not found — run migration 041 first.\n";
    exit(1);
}
$viatorId = (string) $viator['id'];

// Culebra: region scope -> municipality scope.
execute(
    "UPDATE referral_campaigns SET destination_scope = 'culebra', updated_at = CURRENT_TIMESTAMP
     WHERE slug = 'viator-tours-culebra' AND destination_scope = 'cays'"
);
echo "Re-scoped viator-tours-culebra to municipality 'culebra'\n";

$campaigns = [
    ['viator-tours-rincon', 'Rincón & West Coast Tours', 'west', 'https://www.viator.com/Rincon/d25616-ttd', 10],
    ['viator-tours-arecibo', 'Arecibo & North Coast Adventures', 'north', 'https://www.viator.com/Arecibo/d34207-ttd', 10],
    ['viator-tours-ponce', 'Ponce & South Coast Tours', 'south', 'https://www.viator.com/San-Juan-attractions/Ponce/d903-a2459', 10],
    ['viator-tours-vieques', 'Vieques Bio Bay & Island Tours', 'vieques', 'https://www.viator.com/Vieques/d22812-ttd', 10],
];

foreach ($campaigns as [$slug, $name, $scope, $url, $priority]) {
    $existing = queryOne('SELECT id FROM referral_campaigns WHERE slug = :slug', [':slug' => $slug]);
    if ($existing) {
        echo "Campaign {$slug} already exists\n";
        continue;
    }
    execute(
        "INSERT INTO referral_campaigns
            (id, provider_id, slug, name, link_type, destination_scope, target_url, utm_json, priority, status)
         VALUES
            (:id, :provider_id, :slug, :name, 'tour', :scope, :url, '{}', :priority, 'active')",
        [
            ':id' => uuid(),
            ':provider_id' => $viatorId,
            ':slug' => $slug,
            ':name' => $name,
            ':scope' => $scope,
            ':url' => $url,
            ':priority' => $priority,
        ]
    );
    echo "Created campaign {$slug} ({$scope})\n";
}

echo "Migration 042 complete.\n";
