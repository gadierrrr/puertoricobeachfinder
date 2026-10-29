#!/usr/bin/env php
<?php
/** Restore verified beach URLs without publishing quarantined records. */
require_once __DIR__ . '/../inc/db.php';
$aliases = require __DIR__ . '/../config/beach-slug-aliases.php';
$count = 0;
foreach ($aliases as $targetSlug => $oldSlugs) {
    foreach ($oldSlugs as $oldSlug) {
        if (!execute('INSERT INTO beach_slug_redirects (old_slug, beach_id)
            SELECT :old_slug, id FROM beaches
            WHERE slug = :target_slug AND publish_status = "published"
              AND NOT EXISTS (SELECT 1 FROM beaches WHERE slug = :old_slug AND publish_status = "published")
            ON CONFLICT(old_slug) DO UPDATE SET beach_id = excluded.beach_id',
            [':old_slug' => $oldSlug, ':target_slug' => $targetSlug])) {
            throw new RuntimeException('Could not restore beach alias: ' . $oldSlug);
        }
        $count++;
    }
}
echo "Checked {$count} verified beach aliases against published destinations.\n";
