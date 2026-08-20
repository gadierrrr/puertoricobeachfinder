<?php
/**
 * Migration 055: attribution columns for beach_gallery.
 *
 * The owned-photo backfill sources openly licensed images (Wikimedia
 * Commons: CC0/PD/CC BY/CC BY-SA). CC licenses require attribution, so each
 * gallery row can now carry a credit line ("Artist · CC BY 2.0"), a link to
 * the source page, and alt text. Templates render the credit in the gallery
 * grid and lightbox.
 *
 * Idempotent: checks pragma before each ALTER.
 */

require_once __DIR__ . '/../inc/db.php';

echo "Migration 055: adding attribution columns to beach_gallery...\n";

$cols = array_column(query('PRAGMA table_info(beach_gallery)') ?: [], 'name');
foreach (['credit' => 'TEXT', 'credit_url' => 'TEXT', 'alt_text' => 'TEXT'] as $col => $type) {
    if (!in_array($col, $cols, true)) {
        getDb()->exec("ALTER TABLE beach_gallery ADD COLUMN $col $type");
        echo "  added $col\n";
    }
}

echo "Done.\n";
