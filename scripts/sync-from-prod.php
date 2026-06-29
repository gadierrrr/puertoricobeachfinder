<?php
/**
 * scripts/sync-from-prod.php
 *
 * LOCAL DEV ONLY. Pulls the public /api/beaches.php?format=json listing
 * from production and upserts into the local SQLite database so the
 * redesigned discovery UI renders with real content. Rewrites
 * beach_tags + beach_amenities. Does NOT touch users, reviews,
 * content_sections, uploads, or referrals — use the official backup
 * release restore (scripts/backup-restore.sh) if you need those.
 *
 * Idempotent: running twice gives the same result.
 *
 * Usage: php scripts/sync-from-prod.php
 */

require_once __DIR__ . '/../inc/db.php';

$base = 'https://www.puertoricobeachfinder.com/api/beaches.php?format=json&limit=50';
$ctx = stream_context_create(['http' => ['timeout' => 30, 'header' => "User-Agent: prbf-local-sync/1.0\r\n"]]);

$beaches = [];
$page = 1;
while (true) {
    $url = $base . '&page=' . $page;
    echo "Fetching page {$page}...\n";
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) { fwrite(STDERR, "ERROR: fetch failed for page {$page}\n"); exit(1); }
    $payload = json_decode($raw, true);
    if (!is_array($payload) || !isset($payload['data'])) { fwrite(STDERR, "ERROR: bad shape on page {$page}\n"); exit(1); }
    $rows = $payload['data'];
    if (empty($rows)) break;
    $beaches = array_merge($beaches, $rows);
    $totalPages = (int)($payload['meta']['pages'] ?? $payload['pages'] ?? 0);
    if ($totalPages && $page >= $totalPages) break;
    if (count($rows) < 50) break;
    $page++;
    if ($page > 20) break; // safety valve
}
printf("Received %d beaches across %d pages\n", count($beaches), $page);

$db = getDb();
$now = date('Y-m-d H:i:s');

$db->exec('BEGIN');

// Local dev reset: wipe beach-owned tables so we can start clean.
// FK CASCADE drops the dependent rows (tags, amenities, reviews if any).
$db->exec('DELETE FROM beach_tags');
$db->exec('DELETE FROM beach_amenities');
$db->exec('DELETE FROM beaches');

$upsertBeach = $db->prepare('
    INSERT INTO beaches
      (id, slug, name, municipality, lat, lng, sargassum, surf, wind,
       cover_image, access_label, google_rating, google_review_count,
       publish_status, created_at, updated_at, location_type)
    VALUES
      (:id, :slug, :name, :muni, :lat, :lng, :sarg, :surf, :wind,
       :cover, :access, :grating, :greviews,
       "published", :now, :now, "beach")
    ON CONFLICT(id) DO UPDATE SET
       slug = excluded.slug,
       name = excluded.name,
       municipality = excluded.municipality,
       lat = excluded.lat,
       lng = excluded.lng,
       sargassum = excluded.sargassum,
       surf = excluded.surf,
       wind = excluded.wind,
       cover_image = excluded.cover_image,
       access_label = excluded.access_label,
       google_rating = excluded.google_rating,
       google_review_count = excluded.google_review_count,
       updated_at = excluded.updated_at
');

$insertTag      = $db->prepare('INSERT OR IGNORE INTO beach_tags (beach_id, tag) VALUES (:id, :tag)');
$insertAmenity  = $db->prepare('INSERT OR IGNORE INTO beach_amenities (beach_id, amenity) VALUES (:id, :amenity)');

$inserted = 0; $tagCount = 0; $amnCount = 0;

foreach ($beaches as $b) {
    $id = (string)($b['id'] ?? '');
    if ($id === '') continue;

    $sarg = $b['sargassum'] ?? null;
    $surf = $b['surf']      ?? null;
    $wind = $b['wind']      ?? null;

    $upsertBeach->bindValue(':id',      $id);
    $upsertBeach->bindValue(':slug',    (string)($b['slug'] ?? ''));
    $upsertBeach->bindValue(':name',    (string)($b['name'] ?? ''));
    $upsertBeach->bindValue(':muni',    (string)($b['municipality'] ?? ''));
    $upsertBeach->bindValue(':lat',     (float)($b['lat'] ?? 0));
    $upsertBeach->bindValue(':lng',     (float)($b['lng'] ?? 0));
    $upsertBeach->bindValue(':sarg',    $sarg, $sarg === null ? SQLITE3_NULL : SQLITE3_TEXT);
    $upsertBeach->bindValue(':surf',    $surf, $surf === null ? SQLITE3_NULL : SQLITE3_TEXT);
    $upsertBeach->bindValue(':wind',    $wind, $wind === null ? SQLITE3_NULL : SQLITE3_TEXT);
    $upsertBeach->bindValue(':cover',   (string)($b['cover_image'] ?? ''));
    $upsertBeach->bindValue(':access',  (string)($b['access_label'] ?? ''));
    $upsertBeach->bindValue(':grating', $b['google_rating'] ?? null, isset($b['google_rating']) ? SQLITE3_FLOAT : SQLITE3_NULL);
    $upsertBeach->bindValue(':greviews',(int)($b['google_review_count'] ?? 0), SQLITE3_INTEGER);
    $upsertBeach->bindValue(':now',     $now);
    $upsertBeach->execute();
    $upsertBeach->reset();
    $inserted++;

    foreach ((array)($b['tags'] ?? []) as $t) {
        $insertTag->bindValue(':id', $id);
        $insertTag->bindValue(':tag', (string)$t);
        $insertTag->execute();
        $insertTag->reset();
        $tagCount++;
    }
    foreach ((array)($b['amenities'] ?? []) as $a) {
        $insertAmenity->bindValue(':id', $id);
        $insertAmenity->bindValue(':amenity', (string)$a);
        $insertAmenity->execute();
        $insertAmenity->reset();
        $amnCount++;
    }
}

$db->exec('COMMIT');

echo "Done.\n";
echo "  Beaches inserted: {$inserted}\n";
echo "  Tag rows:         {$tagCount}\n";
echo "  Amenity rows:     {$amnCount}\n";
