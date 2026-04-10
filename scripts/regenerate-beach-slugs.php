<?php
/**
 * Regenerate broken beach slugs.
 *
 * Walks all beaches, computes a fresh slug via generateUniqueBeachSlug(),
 * and rewrites any whose current slug matches a "known broken" pattern:
 *   1. coordinate suffix (-1xxxx-xxxxx) from the old import generator,
 *   2. UUID8 suffix (-xxxxxxxx) from the admin/quick-add paths,
 *   3. matches what the legacy slugify() would have produced from the name
 *      (i.e. it was auto-generated, not human-customized).
 *
 * Conservative by design: human-customized slugs are left alone, even if
 * the new generator would produce something different.
 *
 * Usage:
 *   php scripts/regenerate-beach-slugs.php             # dry run (default)
 *   php scripts/regenerate-beach-slugs.php --apply     # commit changes
 */

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

$apply = in_array('--apply', $argv ?? [], true);
$mode  = $apply ? 'APPLY' : 'DRY-RUN';
echo "=== regenerate-beach-slugs ($mode) ===\n\n";

/**
 * Mirrors the buggy slugify() that produced the bad slugs in the first place.
 * Local to this script — must NOT be added to inc/helpers.php.
 */
function slugify_legacy(string $str): string {
    $str = strtolower(trim($str));
    $str = preg_replace('/[^a-z0-9-]/', '-', $str);
    return trim((string) preg_replace('/-+/', '-', $str), '-');
}

function isBrokenSlug(string $current, string $name): bool {
    // Coordinate suffix from migrations/010 generateUniqueSlug:
    // round(lat*100) . '-' . abs(round(lng*100)) — produces 3-5 digit pairs.
    if (preg_match('/-\d{3,5}-\d{3,5}$/', $current)) {
        return true;
    }
    // UUID8 suffix from admin/quick-add paths.
    if (preg_match('/-[0-9a-f]{8}$/', $current)) {
        return true;
    }
    // Legacy slugify() output — auto-generated, safe to rewrite.
    if ($current === slugify_legacy($name)) {
        return true;
    }
    return false;
}

$beaches = query('SELECT id, slug, name, municipality FROM beaches ORDER BY slug');
echo "Loaded " . count($beaches) . " beach rows.\n\n";

$changes      = [];
$skippedClean = 0;
$skippedHuman = 0;

foreach ($beaches as $beach) {
    $current = (string) $beach['slug'];
    $name    = (string) $beach['name'];
    $muni    = (string) $beach['municipality'];
    $id      = (string) $beach['id'];

    try {
        $proposed = generateUniqueBeachSlug($name, $muni, $id);
    } catch (Throwable $e) {
        echo "ERROR: id=$id name=$name :: {$e->getMessage()}\n";
        exit(2);
    }

    if ($proposed === $current) {
        $skippedClean++;
        continue;
    }

    if (!isBrokenSlug($current, $name)) {
        $skippedHuman++;
        continue;
    }

    $changes[] = [
        'id'      => $id,
        'old'     => $current,
        'new'     => $proposed,
        'name'    => $name,
        'muni'    => $muni,
    ];
}

// --- Validate invariants on the proposed change set ---
$errors = [];

foreach ($changes as $c) {
    if ($c['new'] === '') {
        $errors[] = "Empty new slug for id={$c['id']} name={$c['name']}";
    }
}

$seen = [];
foreach ($changes as $c) {
    if (isset($seen[$c['new']])) {
        $errors[] = "Duplicate proposed slug '{$c['new']}' (ids: {$seen[$c['new']]} and {$c['id']})";
    } else {
        $seen[$c['new']] = $c['id'];
    }
}

$changingIds = array_flip(array_column($changes, 'id'));
foreach ($changes as $c) {
    $hit = queryOne('SELECT id FROM beaches WHERE slug = :slug', [':slug' => $c['new']]);
    if ($hit && !isset($changingIds[$hit['id']])) {
        $errors[] = "Proposed slug '{$c['new']}' collides with unchanged beach id={$hit['id']}";
    }
}

foreach ($changes as $c) {
    $hit = queryOne('SELECT beach_id FROM beach_slug_redirects WHERE old_slug = :slug', [':slug' => $c['new']]);
    if ($hit) {
        $errors[] = "Proposed slug '{$c['new']}' collides with existing redirect (beach_id={$hit['beach_id']})";
    }
}

// --- Print summary + diff ---
echo "Proposed changes: " . count($changes) . "\n";
echo "Skipped (already clean):     $skippedClean\n";
echo "Skipped (human-customized):  $skippedHuman\n";
echo "Invariant errors:            " . count($errors) . "\n\n";

if ($errors) {
    echo "INVARIANT ERRORS:\n";
    foreach ($errors as $e) {
        echo "  - $e\n";
    }
    echo "\nAborting (no changes written).\n";
    exit(3);
}

if (!$changes) {
    echo "Nothing to do.\n";
    exit(0);
}

echo "DIFF:\n";
foreach ($changes as $c) {
    printf("  %-60s -> %s\n", $c['old'], $c['new']);
}
echo "\n";

if (!$apply) {
    echo "Dry run only. Re-run with --apply to commit.\n";
    exit(0);
}

// --- Apply ---
$dbBackupPath = __DIR__ . '/../data/beach-finder.db.bak-slugfix-' . date('Ymd_His');
echo "Backing up database to: " . basename($dbBackupPath) . "\n";
if (!copy(__DIR__ . '/../data/beach-finder.db', $dbBackupPath)) {
    echo "ERROR: failed to back up database. Aborting.\n";
    exit(4);
}

$db = getDb();
$db->exec('BEGIN');

try {
    $insertRedirect = $db->prepare('INSERT OR IGNORE INTO beach_slug_redirects (old_slug, beach_id) VALUES (:old, :id)');
    $updateBeach    = $db->prepare('UPDATE beaches SET slug = :new WHERE id = :id');

    foreach ($changes as $c) {
        $insertRedirect->bindValue(':old', $c['old'], SQLITE3_TEXT);
        $insertRedirect->bindValue(':id',  $c['id'],  SQLITE3_TEXT);
        if (!$insertRedirect->execute()) {
            throw new RuntimeException("INSERT redirect failed for {$c['old']}");
        }
        $insertRedirect->reset();

        $updateBeach->bindValue(':new', $c['new'], SQLITE3_TEXT);
        $updateBeach->bindValue(':id',  $c['id'],  SQLITE3_TEXT);
        if (!$updateBeach->execute()) {
            throw new RuntimeException("UPDATE beach failed for {$c['id']}");
        }
        $updateBeach->reset();
    }

    $db->exec('COMMIT');
    echo "\nApplied " . count($changes) . " slug changes.\n";
    echo "Backup: $dbBackupPath\n";
} catch (Throwable $e) {
    $db->exec('ROLLBACK');
    echo "\nROLLBACK: {$e->getMessage()}\n";
    exit(5);
}
