<?php
/**
 * Migration 041: Viator tours campaigns + featured local listings system.
 *
 * Part A — Viator: fixes the half-created "Aviator" provider (typo of Viator),
 * removes its dead draft campaign, and seeds destination-scoped tour campaigns
 * pointing at verified viator.com destination pages. The affiliate pid is NOT
 * stored on the campaign — /go appends it at redirect time from VIATOR_PID env
 * (see referralBuildTargetUrl), so campaigns go live before the partner account
 * is approved and start attributing the moment the env var is set.
 *
 * Part B — Local listings: paid "featured local business" placements on beach
 * pages. Listings are sold manually (leads come in via /advertise), managed in
 * /admin/listings, joined to beaches, and click-tracked through /local-out.
 *
 * Idempotent — safe to re-run.
 */

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

echo "Starting migration: Viator campaigns + local listings\n";

$db = getDb();

// ---------------------------------------------------------------------------
// Part A: Viator provider + campaigns
// ---------------------------------------------------------------------------

// Fix the misspelled provider if it exists; otherwise create the real one.
$viator = queryOne("SELECT id FROM referral_providers WHERE slug = 'viator'");
$aviator = queryOne("SELECT id FROM referral_providers WHERE slug = 'aviator'");

$disclosureEn = 'We may earn a commission when you book through Viator links, at no extra cost to you.';
$disclosureEs = 'Podemos recibir una comisión cuando reservas a través de enlaces de Viator, sin costo adicional para ti.';

if (!$viator && $aviator) {
    execute(
        "UPDATE referral_providers
         SET slug = 'viator', name = 'Viator',
             default_disclosure_en = :en, default_disclosure_es = :es,
             status = 'active', updated_at = CURRENT_TIMESTAMP
         WHERE id = :id",
        [':en' => $disclosureEn, ':es' => $disclosureEs, ':id' => $aviator['id']]
    );
    $viatorId = (string) $aviator['id'];
    echo "Renamed provider 'aviator' -> 'viator'\n";
} elseif ($viator) {
    $viatorId = (string) $viator['id'];
    echo "Provider 'viator' already exists\n";
} else {
    $viatorId = uuid();
    execute(
        "INSERT INTO referral_providers (id, slug, name, status, default_disclosure_en, default_disclosure_es)
         VALUES (:id, 'viator', 'Viator', 'active', :en, :es)",
        [':id' => $viatorId, ':en' => $disclosureEn, ':es' => $disclosureEs]
    );
    echo "Created provider 'viator'\n";
}

// The old draft campaign had the wrong link type and no target URL.
execute("DELETE FROM referral_campaigns WHERE slug = 'aviator-flights-global'");

// destination_scope matches islandRegionForMunicipality() regions, or 'global'.
// Priority: lower = shown first; regional pages beat the PR-wide fallback.
$campaigns = [
    ['viator-tours-pr', 'Puerto Rico Tours & Activities', 'global', 'https://www.viator.com/Puerto-Rico/d36-ttd', 50],
    ['viator-tours-san-juan', 'San Juan Tours & Activities', 'metro', 'https://www.viator.com/San-Juan/d903-ttd', 10],
    ['viator-tours-fajardo', 'Fajardo Tours: Bio Bay, Culebra & Snorkeling', 'east', 'https://www.viator.com/Fajardo/d23854-ttd', 10],
    ['viator-tours-culebra', 'Culebra Island Tours & Day Trips', 'cays', 'https://www.viator.com/Puerto-Rico-attractions/Culebra-Island/d36-a19414', 10],
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

// ---------------------------------------------------------------------------
// Part B: Local listings
// ---------------------------------------------------------------------------

$db->exec("CREATE TABLE IF NOT EXISTS local_listings (
    id TEXT PRIMARY KEY,
    business_name TEXT NOT NULL,
    category TEXT NOT NULL DEFAULT 'food',
    tagline TEXT,
    tagline_es TEXT,
    description TEXT,
    description_es TEXT,
    image_url TEXT,
    website_url TEXT,
    instagram TEXT,
    phone TEXT,
    whatsapp TEXT,
    address TEXT,
    municipality TEXT,
    tier TEXT NOT NULL DEFAULT 'featured',
    status TEXT NOT NULL DEFAULT 'draft',
    active_from TEXT,
    active_to TEXT,
    notes TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_local_listings_status ON local_listings(status)");

$db->exec("CREATE TABLE IF NOT EXISTS local_listing_beaches (
    listing_id TEXT NOT NULL,
    beach_id TEXT NOT NULL,
    display_order INTEGER NOT NULL DEFAULT 0,
    PRIMARY KEY (listing_id, beach_id),
    FOREIGN KEY (listing_id) REFERENCES local_listings(id) ON DELETE CASCADE,
    FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_listing_beaches_beach ON local_listing_beaches(beach_id)");

$db->exec("CREATE TABLE IF NOT EXISTS local_listing_clicks (
    id TEXT PRIMARY KEY,
    listing_id TEXT NOT NULL,
    beach_id TEXT,
    action TEXT NOT NULL DEFAULT 'website',
    locale TEXT NOT NULL DEFAULT 'en',
    ip_hash TEXT,
    ua_hash TEXT,
    referrer TEXT,
    clicked_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES local_listings(id) ON DELETE CASCADE
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_listing_clicks_listing ON local_listing_clicks(listing_id, clicked_at)");

$db->exec("CREATE TABLE IF NOT EXISTS local_listing_leads (
    id TEXT PRIMARY KEY,
    business_name TEXT NOT NULL,
    contact_name TEXT,
    email TEXT NOT NULL,
    phone TEXT,
    message TEXT,
    beaches_interest TEXT,
    source_page TEXT,
    locale TEXT NOT NULL DEFAULT 'en',
    status TEXT NOT NULL DEFAULT 'new',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_listing_leads_status ON local_listing_leads(status, created_at)");

echo "Created local_listings, local_listing_beaches, local_listing_clicks, local_listing_leads\n";
echo "Migration 041 complete.\n";
