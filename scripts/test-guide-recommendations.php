<?php
declare(strict_types=1);

/**
 * Exercise the actual static-guide recommendation queries against isolated
 * SQLite fixtures. Does not bootstrap the app or read/write its database.
 *
 * Usage: php scripts/test-guide-recommendations.php
 */

$guideRoot = __DIR__ . '/../public/guides/';
$files = [
    'culebra-vs-vieques.php' => 2,
    'surfing-guide.php' => 1,
    'snorkeling-guide.php' => 1,
    'family-beach-vacation-planning.php' => 1,
    'kid-friendly-beaches.php' => 1,
];

$kidSource = (string) file_get_contents($guideRoot . 'kid-friendly-beaches.php');
if (!preg_match('/\$kidBeachSlugs\s*=\s*\[(.*?)\];/s', $kidSource, $slugArray)) {
    throw new RuntimeException('Could not read the kid-friendly recommendation slugs.');
}
preg_match_all("/'([^']+)'/", $slugArray[1], $slugMatches);
$kidSlugs = $slugMatches[1];
if (count($kidSlugs) < 8) {
    throw new RuntimeException('Expected enough kid-friendly slugs to exercise multiple visibility states.');
}

$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE beaches (
    id INTEGER PRIMARY KEY, slug TEXT, name TEXT, municipality TEXT,
    publish_status TEXT, lat REAL, lng REAL, cover_image TEXT, description TEXT,
    google_rating REAL, google_review_count INTEGER, access_label TEXT,
    has_lifeguard INTEGER, safe_for_children INTEGER
)');
$db->exec('CREATE TABLE beach_tags (beach_id INTEGER, tag TEXT)');
$db->exec('CREATE TABLE beach_amenities (beach_id INTEGER, amenity TEXT)');
$insertBeach = $db->prepare('INSERT INTO beaches (id, slug, name, municipality, publish_status) VALUES (?, ?, ?, ?, ?)');
$insertTag = $db->prepare('INSERT INTO beach_tags (beach_id, tag) VALUES (?, ?)');
$insertAmenity = $db->prepare('INSERT INTO beach_amenities (beach_id, amenity) VALUES (?, ?)');
$publishedIds = [];
$id = 0;

// Six matching but unpublished records come first: filtering must happen
// before the recommendation limit, or snorkeling can lose every result.
foreach (['Culebra', 'Vieques'] as $municipality) {
    foreach (['draft', 'draft', 'archived', 'archived', 'held', null, 'published', 'published', 'published', 'published', 'published', 'published'] as $index => $status) {
        $id++;
        $slug = $municipality === 'Culebra' ? ($kidSlugs[$index] ?? 'fixture-' . $id) : 'fixture-' . $id;
        $insertBeach->execute([$id, $slug, 'Fixture ' . $id, $municipality, $status]);
        $insertTag->execute([$id, 'surfing']);
        $insertTag->execute([$id, 'snorkeling']);
        // Only the controlled-vocabulary key is present, so the old plural
        // spelling cannot accidentally pass via another family amenity.
        $insertAmenity->execute([$id, 'lifeguard']);
        if ($status === 'published') {
            $publishedIds[] = $id;
        }
    }
}

$checks = 0;
foreach ($files as $file => $expectedQueries) {
    $source = (string) file_get_contents($guideRoot . $file);
    preg_match_all('/query\(\s*"(SELECT[^"]*FROM beaches[^"]*)"/s', $source, $matches);
    if (count($matches[1]) !== $expectedQueries) {
        throw new RuntimeException("$file: recommendation query coverage changed; update this test.");
    }
    foreach ($matches[1] as $sql) {
        $params = [];
        if (str_contains($sql, '$placeholders')) {
            $sql = str_replace('$placeholders', implode(',', array_fill(0, count($kidSlugs), '?')), $sql);
            $params = $kidSlugs;
        }
        $statement = $db->prepare($sql);
        $statement->execute($params);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
        $expectedCount = $file === 'culebra-vs-vieques.php' ? 3 : ($file === 'kid-friendly-beaches.php' ? 6 : 5);
        if (count($rows) !== $expectedCount) {
            throw new RuntimeException("$file: expected $expectedCount published recommendations, got " . count($rows));
        }
        foreach ($rows as $row) {
            if (!in_array((int) $row['id'], $publishedIds, true)) {
                throw new RuntimeException("$file: unpublished record leaked into recommendations.");
            }
        }
        $checks++;
    }
}

echo "Passed $checks guide recommendation queries against in-memory visibility fixtures.\n";
