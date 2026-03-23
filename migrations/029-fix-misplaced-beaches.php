<?php
/**
 * Migration: Fix 3 beaches with coordinates in wrong part of PR
 * Discovered via browser satellite verification + geographic sanity check
 * Generated: 2026-03-23
 *
 * - Playa La Esperanza (Tierras Nuevas): coords were in Vieques, should be Manatí north coast
 * - Playa Pelícano (Caja de Muertos): coords were in Vieques, should be Caja de Muertos island south of Ponce
 * - Punta Las Marías: coords were in Rincón, should be San Juan north coast
 */

require_once __DIR__ . '/../inc/db.php';

$corrections = [
    // Playa La Esperanza (Tierras Nuevas) - was at 18.0917, -65.4697 (Vieques!)
    // Correct: Manatí north coast, Tierras Nuevas area
    '8b116d2e-e7d1-4bf8-bcd8-56566dac37d1' => [18.4839, -66.5258],

    // Playa Pelícano (Caja de Muertos) - was at 18.095, -65.448 (Vieques!)
    // Correct: Caja de Muertos island, south of Ponce
    '7ae17aac-8906-46a8-af20-5edc9b715b73' => [17.8886488, -66.5267888],

    // Punta Las Marías - was at 18.3697, -67.2687 (Rincón!)
    // Correct: San Juan north coast, between Ocean Park and Isla Verde
    'c8a07445-43b7-4751-8608-42b17191cd4c' => [18.449976, -66.0368305],
];

$db = getDb();
$updated = 0;

echo "Fix Misplaced Beaches\n";
echo "======================\n\n";

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
    }
}

echo "\n======================\n";
echo "Migration completed: {$updated} beaches updated\n";
