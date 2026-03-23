<?php
/**
 * Migration: Update beach coordinates - v2 Verified
 * LLM-reviewed corrections from verify-coordinates-v2.php
 * Generated: 2026-03-23
 *
 * 17 corrections where Google name matches our beach name (high confidence).
 * 12 entries rejected due to name mismatch (wrong place_id in DB).
 *
 * IMPORTANT: Back up database before running!
 *   php scripts/backup-db.php
 */

require_once __DIR__ . '/../inc/db.php';

// Corrections: id => [lat, lng]
// Only includes entries where Google Places name matches our beach
$corrections = [
    // MEDIUM: 289m - Cabezas de San Juan Reserve Shore -> "Cabezas de San Juan"
    '80a0d8ae-8860-44db-b6c7-78c3ae50de69' => [18.38134, -65.6179393],
    // HIGH: 636m - Cayo Enrique -> "Cayo Enrique"
    '62eaf96b-f62e-4efe-909b-d2c7e8db4805' => [17.9573231, -67.0427504],
    // HIGH: 689m - Cayo Icacos (La Cordillera) -> "Cayo ICACOS"
    'e7c87206-2f4b-49f5-9e98-687be15c882e' => [18.3843017, -65.5906874],
    // MEDIUM: 198m - Charco El Hippie -> "Charco El Hippie"
    'a8219a0f-358e-4195-9892-25905b189774' => [18.2458405, -65.7870139],
    // MEDIUM: 265m - Playa Azul -> "Playa Azul"
    'f33eb9b1-b40b-4e06-8e5e-734830e2bd8b' => [18.3807836, -65.7184957],
    // HIGH: 560m - Playa Ballena (Guánica) -> "Playa Ballena"
    '5d689927-ac2f-488a-b4cd-b4993fd996eb' => [17.9566297, -66.8562165],
    // MEDIUM: 326m - Playa Blaydin -> "Playa Blaydin"
    'e33f3131-a959-4727-88ac-72677b1a8da7' => [18.1318165, -65.5126709],
    // MEDIUM: 432m - Playa De Vega -> "Playa De Vega"
    '835b4f0f-63b3-46e1-9b3e-8afd1adb2520' => [18.4906477, -66.3956018],
    // MEDIUM: 455m - Playa India -> "Playa India"
    '1324b25e-0d1b-4831-8bba-060c1ffcd18a' => [18.4634085, -67.1634694],
    // MEDIUM: 300m - Playa Los Machos -> "Playa Los Machos"
    '33192fbe-bec5-4e1b-8e8e-291246120d98' => [18.2651619, -65.6311517],
    // HIGH: 702m - Playa San Miguel (Nature Reserve edge) -> "Playa San Miguel"
    '7c7edff0-97ed-47e7-bb1f-f48bcc962f18' => [18.3661875, -65.6892656],
    // MEDIUM: 150m - Puerto Nuevo West Pocket -> "Puerto Nuevo"
    '8c8d843b-091e-47c0-afba-7f0d814a105d' => [18.4826784, -66.4092941],
    // HIGH: 1805m - Playa Corecega -> "Playa Córcega"
    '0f1b280d-acdf-4cf0-8115-d7a8b9de4784' => [18.3129175, -67.2410718],
    // HIGH: 743m - Playa El Combate -> "Playa El Combate Beach"
    'ae0ef758-63ea-4a9a-afdb-9ba69206d59e' => [17.9766404, -67.2127867],
    // HIGH: 758m - Playas Los Tubos -> "Los Tubos Beach"
    'd9afa8d1-cdb8-47ea-819d-155e13223df5' => [18.4694548, -66.4556088],
    // HIGH: 604m - Zoni Beach -> "Zoni Beach"
    'a5d387cd-4842-424b-be88-49cebb9cc032' => [18.319692, -65.2551024],
    // HIGH: 816m - Balneario. Vega Baja -> "Playa De Vega" (same area)
    '9727c31f-1775-4c09-8feb-035d28123179' => [18.4906477, -66.3956018],
];

$db = getDb();
$updated = 0;

echo "Beach Coordinate Migration - v2 Verified\n";
echo "==========================================\n\n";

foreach ($corrections as $id => $coords) {
    $stmt = $db->prepare("UPDATE beaches SET lat = :lat, lng = :lng, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
    $stmt->bindValue(':lat', $coords[0], SQLITE3_FLOAT);
    $stmt->bindValue(':lng', $coords[1], SQLITE3_FLOAT);
    $stmt->bindValue(':id', $id, SQLITE3_TEXT);
    $stmt->execute();

    if ($db->changes() > 0) {
        // Fetch name for display
        $name = $db->querySingle("SELECT name FROM beaches WHERE id = '{$id}'");
        echo "  Updated: {$name}\n";
        $updated++;
    } else {
        echo "  SKIP (not found): {$id}\n";
    }
}

echo "\n==========================================\n";
echo "Migration completed: {$updated} beaches updated\n";
