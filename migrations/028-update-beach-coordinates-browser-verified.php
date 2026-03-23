<?php
/**
 * Migration: Update beach coordinates - Browser Verified
 * 14 corrections verified via Google Maps browser lookups + LLM review
 * Generated: 2026-03-23
 *
 * These are beaches where the DB coordinates were clearly wrong
 * (wrong part of the island, wrong coast, or >2km from Google Maps pin).
 *
 * IMPORTANT: Back up database before running!
 *   php scripts/backup-db.php
 */

require_once __DIR__ . '/../inc/db.php';

// Corrections: id => [lat, lng, description]
$corrections = [
    // Sandy Beach (Rincón) - was in Vieques (18.135, -65.42), should be west coast Rincón
    '1efad281-5772-49ab-8465-766cad8cd9ee' => [18.3706613, -67.2577597],
    // Sandy Beach East (Rincón) - same Vieques coords, eastern portion of Sandy Beach
    'e158174a-82dc-4125-b46f-8dbebef26b13' => [18.3725, -67.2540],
    // Playa Salinas - was at -67.191 (west coast!), should be south coast Salinas
    'eeb22dbc-a15a-4920-8a64-e7d449a15034' => [17.9649, -66.3083],
    // Playa Sardinera (Isabela) - was at -65.636 (Fajardo coast!), should be NW coast
    '99ef0bfb-0c86-4175-adcc-648449780508' => [18.5102871, -67.020755],
    // Playa Las Marías (Barceloneta) - was at -67.269 (Rincón!), should be north coast
    '69cc5165-416b-4257-99ac-17a503826307' => [18.4790055, -66.5164272],
    // Blue Beach / La Chiva (Vieques) - was 7km off, confirmed Google pin
    '338021f6-4b18-47b2-ad28-2912f811a828' => [18.1128874, -65.3874707],
    // Playa Grande (Vieques) - was 16km off, confirmed Google pin
    '474b46c1-27b3-458d-95be-3c23543f6a3c' => [18.0896744, -65.5130475],
    // Guaniquilla Reserve Shore (Cabo Rojo) - was 15km north of actual reserve
    '83eb2483-a20c-4d92-ac93-4f3376d976d1' => [18.0414439, -67.2027776],
    // Cayo Matías (Salinas) - 2.9km off, confirmed Google pin
    '1ccd1e8b-fb77-4a4b-b68c-5fe287e4efdc' => [17.9353628, -66.2879745],
    // Playa Medio Mundo (Ceiba) - 4.8km off
    '298558ca-6bae-4e72-88cf-8a3b1611020a' => [18.2603367, -65.6282372],
    // Playa Punta Lima (Naguabo) - 2.1km off, confirmed Google pin
    'a6b7b6ed-1d6d-42a0-90d0-dc4b68ce686d' => [18.1861674, -65.694125],
    // Playa Dorado Del Mar Beach - 2.6km off, confirmed Google pin
    '95b59edf-fde9-4eaa-bb8a-499610f98a2b' => [18.4767223, -66.2771501],
    // Luquillo - 3.9km off from main Luquillo Beach
    '2b28383e-990b-4ebe-a6c6-17717a467e5e' => [18.3843827, -65.7295596],
    // Playa Tropical (Naguabo) - 2.9km off, confirmed Google pin
    'c039b91f-e4d2-4492-ba22-bc827ae185b9' => [18.1866198, -65.7273968],
];

$db = getDb();
$updated = 0;

echo "Beach Coordinate Migration - Browser Verified\n";
echo "================================================\n\n";

foreach ($corrections as $id => $coords) {
    $stmt = $db->prepare("UPDATE beaches SET lat = :lat, lng = :lng, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
    $stmt->bindValue(':lat', $coords[0], SQLITE3_FLOAT);
    $stmt->bindValue(':lng', $coords[1], SQLITE3_FLOAT);
    $stmt->bindValue(':id', $id, SQLITE3_TEXT);
    $stmt->execute();

    if ($db->changes() > 0) {
        $name = $db->querySingle("SELECT name FROM beaches WHERE id = '{$id}'");
        echo "  Updated: {$name}\n";
        $updated++;
    } else {
        echo "  SKIP (not found): {$id}\n";
    }
}

echo "\n================================================\n";
echo "Migration completed: {$updated} beaches updated\n";
