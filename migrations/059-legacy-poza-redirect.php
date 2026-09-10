#!/usr/bin/env php
<?php
/** Point the oldest Poza alias directly at the published record.
 * The previous target was retired in migration 056, so a one-hop lookup 404ed.
 */
require_once __DIR__ . '/../inc/db.php';
if (!execute('UPDATE beach_slug_redirects
    SET beach_id = (SELECT id FROM beaches WHERE slug = "poza-del-obispo" AND publish_status = "published")
    WHERE old_slug = "poza-de-los-pjaros-arecibo-18495-66709"
    AND EXISTS (SELECT 1 FROM beaches WHERE slug = "poza-del-obispo" AND publish_status = "published")')) {
    throw new RuntimeException('Could not repair legacy Poza redirect');
}
echo "Repaired legacy Poza redirect where its published destination exists.\n";
