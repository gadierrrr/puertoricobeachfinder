#!/usr/bin/env php
<?php
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/inc/db.php';
if (appEnv() !== 'dev') throw new RuntimeException('Use a disposable development database.');
$aliases = require APP_ROOT . '/config/beach-slug-aliases.php';
$db = getDb();
$db->exec('BEGIN');
try {
    foreach ($aliases as $slug => $oldSlugs) {
        execute('INSERT OR IGNORE INTO beaches(id,slug,name,municipality,lat,lng,cover_image,publish_status)
            VALUES(:id,:slug,"Alias fixture","Test",0,0,"/images/placeholder-beach.jpg","published")', [':id'=>uuid(), ':slug'=>$slug]);
    }
    ob_start();
    include APP_ROOT . '/migrations/060-verified-beach-aliases.php';
    include APP_ROOT . '/migrations/060-verified-beach-aliases.php';
    ob_end_clean();
    foreach ($aliases as $slug => $oldSlugs) {
        foreach ($oldSlugs as $oldSlug) {
            // Existing published pages take precedence over an alias.
            if (queryOne('SELECT id FROM beaches WHERE slug=:slug AND publish_status="published"', [':slug'=>$oldSlug])) continue;
            $target = queryOne('SELECT b.slug FROM beach_slug_redirects r JOIN beaches b ON b.id=r.beach_id
                WHERE r.old_slug=:old AND b.publish_status="published"', [':old'=>$oldSlug]);
            if (($target['slug'] ?? '') !== $slug) throw new RuntimeException('Invalid alias ' . $oldSlug);
        }
    }
    execute('UPDATE beaches SET slug="jobos-fixture-renamed" WHERE slug="jobos-beach"');
    $target = queryOne('SELECT b.slug FROM beach_slug_redirects r JOIN beaches b ON b.id=r.beach_id WHERE r.old_slug="jobos"');
    if (($target['slug'] ?? '') !== 'jobos-fixture-renamed') throw new RuntimeException('Alias did not follow canonical rename');
    execute('UPDATE beaches SET publish_status="draft" WHERE slug="jobos-fixture-renamed"');
    $target = queryOne('SELECT b.slug FROM beach_slug_redirects r JOIN beaches b ON b.id=r.beach_id WHERE r.old_slug="jobos" AND b.publish_status="published"');
    if ($target) throw new RuntimeException('Unpublished destination exposed');
    echo "All verified beach aliases pass repeat migration, rename and unpublished-target checks.\n";
} finally {
    $db->exec('ROLLBACK');
}
