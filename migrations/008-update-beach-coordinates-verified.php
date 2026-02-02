<?php
/**
 * Migration: Update beach coordinates - API Verified (Safe)
 * Only includes corrections < 500m from Google Places API
 * Generated: 2025-01-25 - High confidence corrections only
 *
 * Run: php migrations/008-update-beach-coordinates-verified.php
 */

require_once __DIR__ . '/../inc/db.php';

// Only corrections where Google API returned a location < 500m from current
// These are highly likely to be accurate refinements
$corrections = [
    // 143m off - Google: "Playa Caña Gorda"
    'Balneario Caña Gorda' => [17.9529302, -66.884031],
    // 302m off - Google: "Balneario del Escambrón"
    'Balneario El Escambrón' => [18.4669267, -66.0899411],
    // 210m off - Google: "Playa Sardinera"
    'Balneario Manuel “Nolo” Morales (Dorado Public Beach)' => [18.4736874, -66.2815264],
    // 300m off - Google: "Punta Guilarte"
    'Balneario Punta Guilarte' => [17.9637985, -66.0378481],
    // 434m off - Google: "Reserva Natural Cabezas de San Juan - Para la Naturaleza"
    'Cabezas de San Juan Reserve Shore' => [18.3813719, -65.620674],
    // 215m off - Google: "The Tryst Beachfront Hotel"
    'Condado Beach (Oceanfront)' => [18.4571237, -66.0723525],
    // 441m off - Google: "Mirador Playa Crash Boat"
    'Crash Boat North Reef (outer)' => [18.4571273, -67.1626734],
    // 301m off - Google: "Balneario El Escambrón"
    'Escambrón – Bateria del Escambrón Cove' => [18.4668598, -66.0899681],
    // 418m off - Google: "El Malecón La Esperanza"
    'Esperanza Beach (Malecón)' => [18.0946399, -65.4721588],
    // 110m off - Google: "Playa Tortuga"
    'Isla Culebrita – Tortuga Beach' => [18.3184249, -65.2280577],
    // 476m off - Google: "Isla Verde Beach West"
    'Isla Verde – Balneario West Sector' => [18.4447958, -66.0186136],
    // 485m off - Google: "Kikita Surf Beach"
    'Kikita Beach' => [18.4775248, -66.2622866],
    // 345m off - Google: "Mameyito Beach"
    'Mameyito (Aguada pocket)' => [18.3916775, -67.1959535],
    // 119m off - Google: "Guayanés Beach"
    'Playa Guayanés (Yabucoa)' => [18.0618737, -65.8207119],
    // 267m off - Google: "Arroyo Beach"
    'Playa Las Mareas (Arroyo)' => [17.9583056, -66.0567222],
    // 408m off - Google: "El Pastillo Community"
    'Poza El Pastillo (inner pool)' => [18.4918382, -66.9794683],
    // 402m off - Google: "Pool's Beach"
    'Spanish Wall Beach' => [18.3697189, -67.261046],
    // 149m off - Google: "Surfer's Beach"
    'Surfer’s Beach' => [18.5050767, -67.1414638],
    // 223m off - Google: "Zoni Beach"
    'Zoni Beach (Zoní)' => [18.319692, -65.2551024],
];

$db = getDb();
$updated = 0;
$failed = [];

echo "Beach Coordinate Migration - API Verified (Safe)\n";
echo "=================================================\n\n";

foreach ($corrections as $name => $coords) {
    $stmt = $db->prepare("UPDATE beaches SET lat = :lat, lng = :lng, updated_at = CURRENT_TIMESTAMP WHERE name = :name");
    $stmt->bindValue(':lat', $coords[0], SQLITE3_FLOAT);
    $stmt->bindValue(':lng', $coords[1], SQLITE3_FLOAT);
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->execute();

    if ($db->changes() > 0) {
        echo "✓ Updated: $name\n";
        $updated++;
    } else {
        $failed[] = $name;
    }
}

echo "\n=================================================\n";
echo "✅ Migration completed: $updated beaches updated\n";

if ($failed) {
    echo "\n⚠️  Failed to match " . count($failed) . " names:\n";
    foreach ($failed as $name) {
        echo "   - $name\n";
    }
}
