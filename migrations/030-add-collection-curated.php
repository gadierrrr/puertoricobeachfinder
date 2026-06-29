#!/usr/bin/env php
<?php
/**
 * Migration 030: Add collection_curated table and seed best-beaches list.
 *
 * Creates a table for manually curated beach rankings per collection,
 * and populates the best-beaches collection with a research-backed top 15.
 */

require_once __DIR__ . '/../inc/db.php';

$db = getDb();

// Create the curated collection table
$db->exec("
    CREATE TABLE IF NOT EXISTS collection_curated (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        collection_key TEXT NOT NULL,
        beach_id TEXT NOT NULL,
        rank INTEGER NOT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(collection_key, beach_id),
        UNIQUE(collection_key, rank),
        FOREIGN KEY (beach_id) REFERENCES beaches(id)
    )
");

$db->exec('CREATE INDEX IF NOT EXISTS idx_collection_curated_key ON collection_curated(collection_key)');

echo "Created collection_curated table.\n";

// Seed best-beaches curated list
$curatedBeaches = [
    ['best-beaches', 'd5d51681-f744-4054-b76a-2f9b98a7b2f6', 1],  // Flamenco Beach
    ['best-beaches', '7ee92e9a-71fa-4127-bd5c-fc961fe68ab5', 2],  // La Playuela (Playa Sucia)
    ['best-beaches', '55ee2125-b27e-45b0-a83f-f9ab53421d49', 3],  // La Chiva (Blue Beach)
    ['best-beaches', '18ca775f-4e0c-4f92-b913-8a73e1b3fede', 4],  // Crash Boat Beach
    ['best-beaches', '1769058c-2428-4eb6-814d-38bc53c840c4', 5],  // Balneario La Monserrate (Luquillo)
    ['best-beaches', '323b4100-09b9-4898-81c5-d2ef8af390b1', 6],  // Sun Bay Beach
    ['best-beaches', 'cc867644-ea79-4dc4-99d3-4bfd9809eebd', 7],  // Isla Culebrita - Tortuga Beach
    ['best-beaches', '30958a5b-c2c6-4678-9312-1c0e84e71e88', 8],  // Carlos Rosario Beach
    ['best-beaches', '2525903c-85b5-4d60-b0ad-05c6f8f47d4a', 9],  // Mar Chiquita
    ['best-beaches', '35f5415d-c1e6-4cb9-9ec8-b4f89b5c3029', 10], // Jobos Beach
    ['best-beaches', 'a1a63138-ce11-4aed-a395-539485fde9c5', 11], // Condado Beach
    ['best-beaches', '1d30e33d-3570-45dc-82fd-c2c2bfdc0734', 12], // Escambron Beach
    ['best-beaches', '62898af8-8601-489e-9dee-675b6dd76a77', 13], // Buye Beach
    ['best-beaches', '0b4f5888-f849-4c25-82f8-b5e759b82f02', 14], // Isla Verde Beach
    ['best-beaches', 'b225eae8-1627-4a33-8f66-239338be77e0', 15], // Balneario de Boqueron
];

// Only seed beaches that exist (INSERT OR IGNORE does not suppress FOREIGN
// KEY failures, so guard with EXISTS to stay safe on an empty/fresh DB, e.g. CI).
$stmt = $db->prepare(
    'INSERT OR IGNORE INTO collection_curated (collection_key, beach_id, rank)
     SELECT :key, :beach_id, :rank
     WHERE EXISTS (SELECT 1 FROM beaches WHERE id = :beach_id)'
);

$inserted = 0;
foreach ($curatedBeaches as [$key, $beachId, $rank]) {
    $stmt->bindValue(':key', $key, SQLITE3_TEXT);
    $stmt->bindValue(':beach_id', $beachId, SQLITE3_TEXT);
    $stmt->bindValue(':rank', $rank, SQLITE3_INTEGER);
    if ($stmt->execute()) {
        $inserted++;
    }
    $stmt->reset();
}

echo "Inserted {$inserted} curated beaches for best-beaches collection.\n";
