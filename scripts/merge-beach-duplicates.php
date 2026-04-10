<?php
/**
 * Merge two duplicate beach records into one canonical record.
 *
 * Migrates every child table that references the FROM beach onto the TO beach,
 * deduping where possible, then inserts a 301 redirect from the FROM slug to
 * the TO beach and deletes the FROM record.
 *
 * Usage:
 *   php scripts/merge-beach-duplicates.php --from <slug> --to <slug>           # dry run
 *   php scripts/merge-beach-duplicates.php --from <slug> --to <slug> --apply
 *
 * Optional flags:
 *   --force           skip the coordinate-mismatch safety check
 *   --prefer-from     when content_sections collide, prefer FROM over the
 *                     longer-word-count winner (default: prefer richer)
 *
 * Tables handled (21):
 *   PK beach_id (1:1 singletons): beach_safety, beach_parking, beach_accessibility, place_id_audit
 *   UNIQUE(beach_id, X) (insert-or-ignore): beach_tags, beach_amenities,
 *     beach_list_items, user_favorites, collection_curated, chat_channels
 *   UNIQUE(beach_id, section_type, version) (richer-wins): beach_content_sections
 *   No UNIQUE, content dedup needed: beach_features, beach_tips, beach_aliases
 *   No UNIQUE, just rewrite beach_id: beach_gallery, beach_images, beach_photos,
 *     beach_reviews, beach_checkins, beach_referral_placements
 *   Special: beach_slug_redirects (rewrite inbound + insert new)
 */

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

// ---------- arg parse ----------
$args = $argv;
array_shift($args);
$opts = [
    'from'        => null,
    'to'          => null,
    'apply'       => false,
    'force'       => false,
    'prefer-from' => false,
];
for ($i = 0; $i < count($args); $i++) {
    $a = $args[$i];
    if ($a === '--from')         { $opts['from']        = $args[++$i] ?? null; }
    elseif ($a === '--to')       { $opts['to']          = $args[++$i] ?? null; }
    elseif ($a === '--apply')    { $opts['apply']       = true; }
    elseif ($a === '--force')    { $opts['force']       = true; }
    elseif ($a === '--prefer-from') { $opts['prefer-from'] = true; }
    else {
        fwrite(STDERR, "Unknown arg: $a\n");
        usage();
        exit(2);
    }
}

function usage(): void {
    fwrite(STDERR, "Usage: php scripts/merge-beach-duplicates.php --from <slug> --to <slug> [--apply] [--force] [--prefer-from]\n");
}

if (!$opts['from'] || !$opts['to']) {
    usage();
    exit(2);
}
if ($opts['from'] === $opts['to']) {
    fwrite(STDERR, "ERROR: --from and --to must differ.\n");
    exit(2);
}

$mode = $opts['apply'] ? 'APPLY' : 'DRY-RUN';
echo "=== merge-beach-duplicates ($mode) ===\n";
echo "from: {$opts['from']}\n";
echo "to:   {$opts['to']}\n\n";

// ---------- load both records ----------
$fromBeach = queryOne('SELECT * FROM beaches WHERE slug = :slug', [':slug' => $opts['from']]);
$toBeach   = queryOne('SELECT * FROM beaches WHERE slug = :slug', [':slug' => $opts['to']]);

if (!$fromBeach) {
    fwrite(STDERR, "ERROR: --from slug '{$opts['from']}' not found.\n");
    exit(3);
}
if (!$toBeach) {
    fwrite(STDERR, "ERROR: --to slug '{$opts['to']}' not found.\n");
    exit(3);
}
if ($fromBeach['id'] === $toBeach['id']) {
    fwrite(STDERR, "ERROR: --from and --to resolve to the same beach id.\n");
    exit(3);
}

// Refuse if either slug is already in the redirect table — ambiguous semantics.
$fromInRedirects = queryOne('SELECT beach_id FROM beach_slug_redirects WHERE old_slug = :s', [':s' => $opts['from']]);
if ($fromInRedirects) {
    fwrite(STDERR, "ERROR: --from slug '{$opts['from']}' already exists in beach_slug_redirects (points to beach_id={$fromInRedirects['beach_id']}). Refusing.\n");
    exit(3);
}
$toInRedirects = queryOne('SELECT beach_id FROM beach_slug_redirects WHERE old_slug = :s', [':s' => $opts['to']]);
if ($toInRedirects) {
    fwrite(STDERR, "ERROR: --to slug '{$opts['to']}' is itself a redirect old_slug. Refusing.\n");
    exit(3);
}

$fromId = (string) $fromBeach['id'];
$toId   = (string) $toBeach['id'];

echo "from id: $fromId  ({$fromBeach['name']}, {$fromBeach['municipality']}, lat={$fromBeach['lat']}, lng={$fromBeach['lng']})\n";
echo "to   id: $toId  ({$toBeach['name']}, {$toBeach['municipality']}, lat={$toBeach['lat']}, lng={$toBeach['lng']})\n\n";

// ---------- coord-mismatch safety check ----------
$dLat = abs(((float) $fromBeach['lat']) - ((float) $toBeach['lat']));
$dLng = abs(((float) $fromBeach['lng']) - ((float) $toBeach['lng']));
$coordOk = ($dLat <= 0.005 && $dLng <= 0.005); // ~550m
if (!$coordOk) {
    $msg = sprintf("WARNING: coordinates differ by lat=%.5f, lng=%.5f (~%dm)", $dLat, $dLng, (int) (max($dLat, $dLng) * 111000));
    if ($opts['force']) {
        echo "$msg — proceeding due to --force.\n\n";
    } else {
        fwrite(STDERR, "$msg — refusing without --force.\n");
        exit(3);
    }
}

// ---------- plan helpers ----------
$plan = [];
function plan(string $line): void {
    global $plan;
    $plan[] = $line;
}

// ---------- 1. beach scalar field merge (TO row updates) ----------
// For these fields, fill TO's empty/null/placeholder with FROM's value.
// We never overwrite a non-empty TO value — TO is the chosen canonical and its
// authored content takes precedence. The exception is cover_image where
// "placeholder-*" counts as empty.
$fillableFields = [
    'description', 'cover_image', 'access_label', 'notes',
    'parking_details', 'safety_info', 'local_tips', 'best_time',
    'sargassum', 'surf', 'wind',
    // Google Places data — when TO is the editorially-richer record but lacks
    // a place_id/rating, pull these in from FROM so we keep the Google match.
    'place_id', 'google_rating', 'google_review_count',
];
$beachUpdates = [];
foreach ($fillableFields as $f) {
    $toVal   = $toBeach[$f] ?? null;
    $fromVal = $fromBeach[$f] ?? null;
    $toEmpty = ($toVal === null || $toVal === '' || $toVal === 0 || $toVal === '0' || ($f === 'cover_image' && stripos((string) $toVal, 'placeholder') !== false));
    $fromHas = ($fromVal !== null && $fromVal !== '' && $fromVal !== 0 && $fromVal !== '0' && ($f !== 'cover_image' || stripos((string) $fromVal, 'placeholder') === false));
    if ($toEmpty && $fromHas) {
        $beachUpdates[$f] = $fromVal;
        plan(sprintf('  beaches.%s: TO empty/null/placeholder, copying FROM value (%s)', $f, is_numeric($fromVal) ? (string) $fromVal : 'len=' . strlen((string) $fromVal)));
    }
}
if (!$beachUpdates) {
    plan('  beaches scalar fields: no updates needed (TO already populated)');
}

// ---------- 2. child-table migration plans ----------
function countRows(string $table, string $beachId): int {
    $r = queryOne("SELECT COUNT(*) AS c FROM $table WHERE beach_id = :id", [':id' => $beachId]);
    return (int) ($r['c'] ?? 0);
}

// 2a. UNIQUE(beach_id, X) tables — insert-or-ignore (no conflict-resolution work)
$insertOrIgnoreTables = [
    'beach_tags'      => ['(beach_id, tag)',     'tag'],
    'beach_amenities' => ['(beach_id, amenity)', 'amenity'],
];
foreach ($insertOrIgnoreTables as $t => [$cols, $col]) {
    $fc = countRows($t, $fromId);
    $tc = countRows($t, $toId);
    plan("  $t: from=$fc, to=$tc — INSERT OR IGNORE (UNIQUE$cols), then DELETE FROM rows");
}

// 2b. beach_features — no UNIQUE; dedup by (title, description)
$ff = countRows('beach_features', $fromId);
$tf = countRows('beach_features', $toId);
plan("  beach_features: from=$ff, to=$tf — INSERT non-duplicate (title,description) rows, DELETE FROM rows");

// 2c. beach_tips — dedup by (category, tip)
$ftip = countRows('beach_tips', $fromId);
$ttip = countRows('beach_tips', $toId);
plan("  beach_tips: from=$ftip, to=$ttip — INSERT non-duplicate (category,tip) rows, DELETE FROM rows");

// 2d. beach_aliases — dedup by alias text
$fa = countRows('beach_aliases', $fromId);
$ta = countRows('beach_aliases', $toId);
plan("  beach_aliases: from=$fa, to=$ta — INSERT non-duplicate alias rows, DELETE FROM rows");

// 2e. beach_content_sections — UNIQUE(beach_id, section_type, version) — richer wins
$fromSections = query('SELECT id, section_type, version, word_count, content FROM beach_content_sections WHERE beach_id = :id', [':id' => $fromId]);
$toSectionsByKey = [];
foreach (query('SELECT id, section_type, version, word_count FROM beach_content_sections WHERE beach_id = :id', [':id' => $toId]) as $r) {
    $toSectionsByKey[$r['section_type'] . '|' . $r['version']] = $r;
}
$sectionMoves = [];     // FROM rows to migrate (rewrite beach_id)
$sectionUpdates = [];   // TO rows to overwrite content
$sectionSkips = [];     // FROM rows to discard
foreach ($fromSections as $fs) {
    $key = $fs['section_type'] . '|' . $fs['version'];
    if (!isset($toSectionsByKey[$key])) {
        $sectionMoves[] = $fs;
    } else {
        $tsec = $toSectionsByKey[$key];
        $fromWins = $opts['prefer-from'] || ((int) $fs['word_count'] > (int) $tsec['word_count']);
        if ($fromWins) {
            $sectionUpdates[] = ['to_id' => $tsec['id'], 'from' => $fs];
        } else {
            $sectionSkips[] = $fs;
        }
    }
}
plan("  beach_content_sections: from=" . count($fromSections) . ", to=" . count($toSectionsByKey)
    . " — move=" . count($sectionMoves) . " update-in-place=" . count($sectionUpdates) . " skip=" . count($sectionSkips));

// 2f. Tables with no UNIQUE on beach_id — just UPDATE beach_id
$rewriteTables = [
    'beach_gallery',
    'beach_images',
    'beach_photos',
    'beach_reviews',
    'beach_checkins',
    'beach_referral_placements',
];
foreach ($rewriteTables as $t) {
    $c = countRows($t, $fromId);
    if ($c > 0) plan("  $t: $c rows — UPDATE beach_id");
    else plan("  $t: 0 rows — no-op");
}

// 2g. PK beach_id singletons — TO wins (delete FROM if TO already has)
$pkSingletons = ['beach_safety', 'beach_parking', 'beach_accessibility'];
foreach ($pkSingletons as $t) {
    $fHas = countRows($t, $fromId);
    $tHas = countRows($t, $toId);
    if ($fHas && !$tHas) {
        plan("  $t: from=1, to=0 — UPDATE beach_id (move FROM row to TO)");
    } elseif ($fHas && $tHas) {
        plan("  $t: from=1, to=1 — DELETE FROM row (TO wins)");
    } elseif (!$fHas && $tHas) {
        plan("  $t: from=0, to=1 — no-op");
    } else {
        plan("  $t: from=0, to=0 — no-op");
    }
}

// 2h. place_id_audit — UNIQUE beach_id, ON DELETE NO ACTION (must handle explicitly)
$paF = countRows('place_id_audit', $fromId);
$paT = countRows('place_id_audit', $toId);
if ($paF && !$paT) {
    plan("  place_id_audit: from=1, to=0 — UPDATE beach_id (NO ACTION FK)");
} elseif ($paF && $paT) {
    plan("  place_id_audit: from=1, to=1 — DELETE FROM row (NO ACTION FK)");
} elseif ($paF) {
    plan("  place_id_audit: from=$paF, to=$paT — UPDATE beach_id");
} else {
    plan("  place_id_audit: from=0, to=$paT — no-op");
}

// 2i. user_favorites UNIQUE(user_id, beach_id) — INSERT OR IGNORE then DELETE
$ufF = countRows('user_favorites', $fromId);
$ufT = countRows('user_favorites', $toId);
plan("  user_favorites: from=$ufF, to=$ufT — INSERT OR IGNORE then DELETE FROM rows");

// 2j. beach_list_items UNIQUE(list_id, beach_id) — INSERT OR IGNORE then DELETE
$liF = countRows('beach_list_items', $fromId);
$liT = countRows('beach_list_items', $toId);
plan("  beach_list_items: from=$liF, to=$liT — INSERT OR IGNORE then DELETE FROM rows");

// 2k. collection_curated UNIQUE(collection_key, beach_id) AND UNIQUE(collection_key, rank), ON DELETE NO ACTION
$ccF = countRows('collection_curated', $fromId);
$ccT = countRows('collection_curated', $toId);
plan("  collection_curated: from=$ccF, to=$ccT — INSERT OR IGNORE (preserves TO ranks) then DELETE FROM rows (NO ACTION FK)");

// 2l. chat_channels — partial UNIQUE(type, beach_id) WHERE beach_id IS NOT NULL
$ccfF = countRows('chat_channels', $fromId);
$ccfT = countRows('chat_channels', $toId);
plan("  chat_channels: from=$ccfF, to=$ccfT — for each FROM channel, UPDATE if no TO collision else delete FROM channel (cascade kills its messages)");

// 2m. beach_slug_redirects inbound rewrite + outbound insert
$inbound = queryOne('SELECT COUNT(*) AS c FROM beach_slug_redirects WHERE beach_id = :id', [':id' => $fromId]);
$inboundCount = (int) ($inbound['c'] ?? 0);
plan("  beach_slug_redirects: $inboundCount rows currently point at FROM — UPDATE to point at TO");
plan("  beach_slug_redirects: INSERT new ('{$opts['from']}' -> $toId)");

// ---------- print plan ----------
echo "PLAN:\n";
foreach ($plan as $l) echo "$l\n";
echo "\nFinally: DELETE FROM beaches WHERE id = '$fromId'\n\n";

if (!$opts['apply']) {
    echo "Dry run only. Re-run with --apply to commit.\n";
    exit(0);
}

// ---------- APPLY ----------
$dbBackupPath = __DIR__ . '/../data/beach-finder.db.bak-merge-' . date('Ymd_His');
echo "Backing up database to: " . basename($dbBackupPath) . "\n";
if (!copy(__DIR__ . '/../data/beach-finder.db', $dbBackupPath)) {
    fwrite(STDERR, "ERROR: failed to back up database.\n");
    exit(4);
}

$db = getDb();
$db->exec('BEGIN');

try {
    // 1. beach scalar updates
    if ($beachUpdates) {
        $set = [];
        foreach ($beachUpdates as $f => $_) $set[] = "$f = :$f";
        $sql = 'UPDATE beaches SET ' . implode(', ', $set) . ' WHERE id = :id';
        $stmt = $db->prepare($sql);
        foreach ($beachUpdates as $f => $v) $stmt->bindValue(":$f", $v, SQLITE3_TEXT);
        $stmt->bindValue(':id', $toId, SQLITE3_TEXT);
        if (!$stmt->execute()) throw new RuntimeException('Failed to update TO beach scalar fields');
    }

    // 2a. tags / amenities
    foreach (['beach_tags' => 'tag', 'beach_amenities' => 'amenity'] as $t => $col) {
        $db->exec("INSERT OR IGNORE INTO $t (beach_id, $col) SELECT '$toId', $col FROM $t WHERE beach_id = '$fromId'");
        $db->exec("DELETE FROM $t WHERE beach_id = '$fromId'");
    }

    // 2b. beach_features — dedup by (title, description)
    $db->exec("INSERT INTO beach_features (beach_id, title, description, position, title_es, description_es)
               SELECT '$toId', f.title, f.description, f.position, f.title_es, f.description_es
               FROM beach_features f
               WHERE f.beach_id = '$fromId'
                 AND NOT EXISTS (
                   SELECT 1 FROM beach_features t
                   WHERE t.beach_id = '$toId' AND t.title = f.title AND t.description = f.description
                 )");
    $db->exec("DELETE FROM beach_features WHERE beach_id = '$fromId'");

    // 2c. beach_tips — dedup by (category, tip)
    $db->exec("INSERT INTO beach_tips (beach_id, category, tip, position, tip_es)
               SELECT '$toId', f.category, f.tip, f.position, f.tip_es
               FROM beach_tips f
               WHERE f.beach_id = '$fromId'
                 AND NOT EXISTS (
                   SELECT 1 FROM beach_tips t
                   WHERE t.beach_id = '$toId' AND t.category = f.category AND t.tip = f.tip
                 )");
    $db->exec("DELETE FROM beach_tips WHERE beach_id = '$fromId'");

    // 2d. beach_aliases — dedup by alias text
    $db->exec("INSERT INTO beach_aliases (beach_id, alias)
               SELECT '$toId', f.alias FROM beach_aliases f
               WHERE f.beach_id = '$fromId'
                 AND NOT EXISTS (
                   SELECT 1 FROM beach_aliases t WHERE t.beach_id = '$toId' AND t.alias = f.alias
                 )");
    $db->exec("DELETE FROM beach_aliases WHERE beach_id = '$fromId'");

    // 2e. content sections — move non-conflicting, overwrite-or-skip on conflict
    foreach ($sectionMoves as $fs) {
        $stmt = $db->prepare('UPDATE beach_content_sections SET beach_id = :to WHERE id = :id');
        $stmt->bindValue(':to', $toId, SQLITE3_TEXT);
        $stmt->bindValue(':id', $fs['id'], SQLITE3_TEXT);
        if (!$stmt->execute()) throw new RuntimeException("Failed to move content section {$fs['id']}");
    }
    foreach ($sectionUpdates as $u) {
        // Overwrite TO row with FROM row's content (FROM has higher word_count or --prefer-from).
        // Pull all FROM fields explicitly so we don't lose translations etc.
        $fr = queryOne('SELECT * FROM beach_content_sections WHERE id = :id', [':id' => $u['from']['id']]);
        if (!$fr) throw new RuntimeException("Lost reference to FROM section {$u['from']['id']}");
        $stmt = $db->prepare('UPDATE beach_content_sections SET
            heading = :heading, content = :content, word_count = :wc,
            metadata = :metadata, display_order = :ord, status = :status,
            content_es = :ces, heading_es = :hes
            WHERE id = :tid');
        $stmt->bindValue(':heading',  $fr['heading']);
        $stmt->bindValue(':content',  $fr['content']);
        $stmt->bindValue(':wc',       (int) $fr['word_count'], SQLITE3_INTEGER);
        $stmt->bindValue(':metadata', $fr['metadata']);
        $stmt->bindValue(':ord',      (int) $fr['display_order'], SQLITE3_INTEGER);
        $stmt->bindValue(':status',   $fr['status']);
        $stmt->bindValue(':ces',      $fr['content_es']);
        $stmt->bindValue(':hes',      $fr['heading_es']);
        $stmt->bindValue(':tid',      $u['to_id']);
        if (!$stmt->execute()) throw new RuntimeException("Failed to overwrite TO section {$u['to_id']}");
    }
    // Skipped + remaining FROM rows get deleted (cascade would do it, but explicit is cleaner)
    $db->exec("DELETE FROM beach_content_sections WHERE beach_id = '$fromId'");

    // 2f. Just rewrite beach_id on tables with no UNIQUE on it
    foreach ($rewriteTables as $t) {
        $db->exec("UPDATE $t SET beach_id = '$toId' WHERE beach_id = '$fromId'");
    }

    // 2g. PK beach_id singletons (safety/parking/accessibility)
    foreach ($pkSingletons as $t) {
        $fHas = countRows($t, $fromId);
        $tHas = countRows($t, $toId);
        if ($fHas && !$tHas) {
            $db->exec("UPDATE $t SET beach_id = '$toId' WHERE beach_id = '$fromId'");
        } elseif ($fHas && $tHas) {
            $db->exec("DELETE FROM $t WHERE beach_id = '$fromId'");
        }
    }

    // 2h. place_id_audit (NO ACTION FK)
    $paF2 = countRows('place_id_audit', $fromId);
    $paT2 = countRows('place_id_audit', $toId);
    if ($paF2 && !$paT2) {
        $db->exec("UPDATE place_id_audit SET beach_id = '$toId' WHERE beach_id = '$fromId'");
    } elseif ($paF2 && $paT2) {
        $db->exec("DELETE FROM place_id_audit WHERE beach_id = '$fromId'");
    } elseif ($paF2) {
        // multi-row case (shouldn't happen due to UNIQUE but defensive)
        $db->exec("DELETE FROM place_id_audit WHERE beach_id = '$fromId'");
    }

    // 2i. user_favorites
    $db->exec("INSERT OR IGNORE INTO user_favorites (id, user_id, beach_id, created_at)
               SELECT id, user_id, '$toId', created_at FROM user_favorites WHERE beach_id = '$fromId'");
    $db->exec("DELETE FROM user_favorites WHERE beach_id = '$fromId'");

    // 2j. beach_list_items
    $liCols = $db->query('PRAGMA table_info(beach_list_items)');
    $liColumnNames = [];
    while ($r = $liCols->fetchArray(SQLITE3_ASSOC)) $liColumnNames[] = $r['name'];
    // Build dynamic INSERT OR IGNORE preserving columns other than beach_id
    $cols = implode(', ', $liColumnNames);
    $sel  = implode(', ', array_map(fn($c) => $c === 'beach_id' ? "'$toId'" : $c, $liColumnNames));
    $db->exec("INSERT OR IGNORE INTO beach_list_items ($cols) SELECT $sel FROM beach_list_items WHERE beach_id = '$fromId'");
    $db->exec("DELETE FROM beach_list_items WHERE beach_id = '$fromId'");

    // 2k. collection_curated (NO ACTION FK)
    $ccCols = $db->query('PRAGMA table_info(collection_curated)');
    $ccColumnNames = [];
    while ($r = $ccCols->fetchArray(SQLITE3_ASSOC)) $ccColumnNames[] = $r['name'];
    $ccColsExceptId = array_filter($ccColumnNames, fn($c) => $c !== 'id');
    $cols = implode(', ', $ccColsExceptId);
    $sel  = implode(', ', array_map(fn($c) => $c === 'beach_id' ? "'$toId'" : $c, $ccColsExceptId));
    $db->exec("INSERT OR IGNORE INTO collection_curated ($cols) SELECT $sel FROM collection_curated WHERE beach_id = '$fromId'");
    $db->exec("DELETE FROM collection_curated WHERE beach_id = '$fromId'");

    // 2l. chat_channels — partial UNIQUE(type, beach_id) means we can have AT MOST one
    // beach-typed channel per beach. Move FROM channels that don't collide; delete those that do.
    $fromChans = query("SELECT id, type FROM chat_channels WHERE beach_id = '$fromId'");
    foreach ($fromChans as $ch) {
        $clash = queryOne(
            "SELECT id FROM chat_channels WHERE beach_id = :to AND type = :type",
            [':to' => $toId, ':type' => $ch['type']]
        );
        if ($clash) {
            $db->exec("DELETE FROM chat_channels WHERE id = '{$ch['id']}'"); // cascade kills messages
        } else {
            $db->exec("UPDATE chat_channels SET beach_id = '$toId' WHERE id = '{$ch['id']}'");
        }
    }

    // 2m. beach_slug_redirects: rewrite inbound rows (those that pointed at FROM) to point at TO
    // BEFORE deleting the FROM beach (otherwise CASCADE kills them).
    $db->exec("UPDATE beach_slug_redirects SET beach_id = '$toId' WHERE beach_id = '$fromId'");

    // Insert the new redirect for the FROM slug.
    $stmt = $db->prepare('INSERT OR REPLACE INTO beach_slug_redirects (old_slug, beach_id) VALUES (:s, :id)');
    $stmt->bindValue(':s',  $opts['from'], SQLITE3_TEXT);
    $stmt->bindValue(':id', $toId,         SQLITE3_TEXT);
    if (!$stmt->execute()) throw new RuntimeException('Failed to insert outbound redirect');

    // 3. finally delete the FROM beach (cascade is safe now — we migrated everything)
    $stmt = $db->prepare('DELETE FROM beaches WHERE id = :id');
    $stmt->bindValue(':id', $fromId, SQLITE3_TEXT);
    if (!$stmt->execute()) throw new RuntimeException('Failed to delete FROM beach');

    // Verify TO still exists and is the same record
    $check = queryOne('SELECT id, slug FROM beaches WHERE id = :id', [':id' => $toId]);
    if (!$check) throw new RuntimeException('TO beach disappeared during merge — aborting');
    if ($check['slug'] !== $opts['to']) throw new RuntimeException("TO slug changed unexpectedly: {$check['slug']}");

    $db->exec('COMMIT');
    echo "\nMerge complete.\n";
    echo "Backup: $dbBackupPath\n";
} catch (Throwable $e) {
    $db->exec('ROLLBACK');
    fwrite(STDERR, "\nROLLBACK: {$e->getMessage()}\n");
    exit(5);
}
