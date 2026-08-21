#!/usr/bin/env php
<?php
/**
 * Migration 056: Family-beaches guide curation and data fixes.
 *
 * 1. Seeds the best-family-beaches curated list (editorial top 10, Aug 2026).
 * 2. Sets has_lifeguard on the reliably lifeguarded balnearios (incl. Flamenco,
 *    whose card was missing the badge).
 * 3. Removes Condado's main ocean beach from lifeguard/family-friendly
 *    metadata site-wide — rip-current record, swimming officially discouraged.
 *    Playita del Condado keeps its family metadata.
 * 4. Unpublishes "Poza de los Pájaros": it shares its Google listing with
 *    "Poza del Obispo" (same place data, near-identical review counts), so the
 *    slug 301s to poza-del-obispo instead.
 * 5. Reclassifies Ojo de Agua (Vega Baja) as a freshwater swimming hole — it
 *    is a neighborhood spring, not a beach, so it leaves beach collections
 *    while its detail page stays reachable.
 */

require_once __DIR__ . '/../inc/db.php';

$db = getDb();

// ---------------------------------------------------------------------------
// 1. Curated editorial ranking for best-family-beaches
// ---------------------------------------------------------------------------
$curatedSlugs = [
    1  => 'balneario-la-monserrate-luquillo', // Luquillo Beach
    2  => 'balneario-de-carolina',
    3  => 'seven-seas-beach',
    4  => 'flamenco-beach',
    5  => 'sun-bay-beach',
    6  => 'balneario-de-boqueron',
    7  => 'escambron-beach',                  // Balneario El Escambrón (canonical record)
    8  => 'buye-beach',                       // Playa Buyé
    9  => 'playita-del-condado',
    10 => 'puerto-nuevo-beach',               // Balneario Mar Bella
];

$db->exec("DELETE FROM collection_curated WHERE collection_key = 'best-family-beaches'");

$stmt = $db->prepare(
    'INSERT INTO collection_curated (collection_key, beach_id, rank)
     SELECT :key, id, :rank FROM beaches WHERE slug = :slug'
);
$inserted = 0;
foreach ($curatedSlugs as $rank => $slug) {
    $stmt->bindValue(':key', 'best-family-beaches', SQLITE3_TEXT);
    $stmt->bindValue(':rank', $rank, SQLITE3_INTEGER);
    $stmt->bindValue(':slug', $slug, SQLITE3_TEXT);
    $stmt->execute();
    $inserted += $db->changes();
    $stmt->reset();
}
echo "Seeded {$inserted}/" . count($curatedSlugs) . " curated beaches for best-family-beaches.\n";

// ---------------------------------------------------------------------------
// 2. Lifeguard flags for reliably guarded balnearios (duplicate records
//    included so every visible card agrees with the guide)
// ---------------------------------------------------------------------------
$lifeguardSlugs = [
    'balneario-la-monserrate-luquillo',
    'balneario-de-carolina',
    'seven-seas-beach',
    'flamenco-beach',
    'sun-bay-beach',
    'sun-bay',
    'balneario-de-boqueron',
    'balneario-publico-de-boqueron',
    'escambron-beach',
    'balneario-el-escambron',
    'playita-del-condado',
    'puerto-nuevo-beach',
    'balneario-cerro-gordo',
];
$placeholders = "'" . implode("','", $lifeguardSlugs) . "'";
$db->exec("UPDATE beaches SET has_lifeguard = 1 WHERE slug IN ($placeholders)");
echo "Set has_lifeguard=1 on " . $db->changes() . " beaches.\n";

// ---------------------------------------------------------------------------
// 3. Condado main ocean beach: not a family swim beach
// ---------------------------------------------------------------------------
$db->exec("UPDATE beaches SET safe_for_children = 0, has_lifeguard = 0
           WHERE slug IN ('condado-beach', 'condado-beach-oceanfront')");
echo "Cleared family metadata on " . $db->changes() . " Condado records.\n";
$db->exec("DELETE FROM beach_tags WHERE tag = 'family-friendly'
           AND beach_id IN (SELECT id FROM beaches WHERE slug IN ('condado-beach', 'condado-beach-oceanfront'))");

// ---------------------------------------------------------------------------
// 4. Poza de los Pájaros duplicates Poza del Obispo's Google listing
// ---------------------------------------------------------------------------
$obispo = $db->querySingle("SELECT id FROM beaches WHERE slug = 'poza-del-obispo'");
if ($obispo) {
    $db->exec("UPDATE beaches SET publish_status = 'draft' WHERE slug = 'poza-de-los-pajaros'");
    if ($db->changes() > 0) {
        $stmt = $db->prepare('INSERT OR REPLACE INTO beach_slug_redirects (old_slug, beach_id) VALUES (:s, :id)');
        $stmt->bindValue(':s', 'poza-de-los-pajaros', SQLITE3_TEXT);
        $stmt->bindValue(':id', $obispo, SQLITE3_TEXT);
        $stmt->execute();
        echo "Unpublished poza-de-los-pajaros; slug now redirects to poza-del-obispo.\n";
    } else {
        echo "poza-de-los-pajaros already unpublished or absent; skipped.\n";
    }
} else {
    echo "poza-del-obispo not found; skipped Pájaros dedup.\n";
}

// ---------------------------------------------------------------------------
// 5. Ojo de Agua is a spring, not a beach
// ---------------------------------------------------------------------------
$db->exec("UPDATE beaches SET location_type = 'swimming-hole' WHERE slug = 'ojo-de-agua-beach'");
echo "Reclassified " . $db->changes() . " record(s) as swimming-hole.\n";
