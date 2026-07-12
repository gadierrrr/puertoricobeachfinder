<?php
/**
 * Migration 052: Strip redundant "Access:" prefix from parking_details.
 *
 * 103 beaches carry parking_details of the form
 * "Access: <access_label>. <parking info>" (and parking_details_es with a
 * matching "Acceso: …" sentence). Templates already render access_label
 * next to parking_details, so the embedded sentence duplicates it on both
 * the EN and ES beach pages.
 *
 * - When the embedded label matches access_label, drop the first sentence;
 *   if nothing else remains, null the column out.
 * - Two rows have an empty access_label and only the embedded sentence:
 *   promote the label into access_label instead of discarding it.
 * - parking_details_es is only touched on rows whose EN column was handled,
 *   so a Spanish "Acceso:" sentence carrying unique info can never be lost.
 *
 * Idempotent: stripped rows no longer match the prefix pattern.
 */

require_once __DIR__ . '/../inc/db.php';

echo "Migration 052: stripping Access:/Acceso: prefixes from parking_details...\n";

$rows = query("
    SELECT id, slug, access_label, parking_details, parking_details_es
    FROM beaches
    WHERE parking_details LIKE 'Access:%'
") ?: [];

$stripped = 0;
$promoted = 0;
$emptied = 0;
$skipped = 0;

foreach ($rows as $row) {
    $accessLabel = trim((string) ($row['access_label'] ?? ''));
    $pd = (string) $row['parking_details'];
    $pdEs = (string) ($row['parking_details_es'] ?? '');

    if (!preg_match('/^Access:\s*([^.]+)\.\s*/', $pd, $m)) {
        $skipped++;
        continue;
    }
    $embeddedLabel = trim($m[1]);

    if ($accessLabel === '') {
        // The embedded sentence is the only access info this beach has.
        $accessLabel = $embeddedLabel;
        $promoted++;
    } elseif (strcasecmp($embeddedLabel, $accessLabel) !== 0) {
        // Prefix says something access_label doesn't — leave it alone.
        echo "  SKIP {$row['slug']}: prefix \"{$embeddedLabel}\" != access_label \"{$accessLabel}\"\n";
        $skipped++;
        continue;
    }

    $newPd = trim(substr($pd, strlen($m[0])));
    $newPdEs = $pdEs;
    if (preg_match('/^Acceso:\s*([^.]+)\.\s*/u', $pdEs, $mEs)) {
        $newPdEs = trim(substr($pdEs, strlen($mEs[0])));
    }

    execute(
        'UPDATE beaches
         SET access_label = :access_label,
             parking_details = :pd,
             parking_details_es = :pd_es
         WHERE id = :id',
        [
            ':access_label' => $accessLabel,
            ':pd' => $newPd !== '' ? $newPd : null,
            ':pd_es' => $newPdEs !== '' ? $newPdEs : null,
            ':id' => $row['id'],
        ]
    );

    $stripped++;
    if ($newPd === '') {
        $emptied++;
    }
}

echo "Done: {$stripped} stripped ({$emptied} emptied, {$promoted} labels promoted), {$skipped} skipped.\n";
