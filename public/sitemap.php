<?php
/**
 * XML Sitemap Generator
 * Generates sitemap.xml dynamically
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/locale_routes.php';

$appUrl = getPublicBaseUrl();

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

// Build lastmod lookup for static routes based on file modification time
$routeScriptLastmod = [];
foreach (localeRoutes() as $routeKey => $route) {
    if (!($route['indexable'] ?? false)) continue;
    $script = $route['script'] ?? '';
    if ($script !== '') {
        $scriptFile = $_SERVER['DOCUMENT_ROOT'] . $script;
        if (file_exists($scriptFile)) {
            $routeScriptLastmod[$routeKey] = date('Y-m-d', filemtime($scriptFile));
        }
    }
}
$fallbackDate = '2026-02-01';

// Pre-query municipality lastmod dates
$municipalityLastmod = [];
$muniRows = query("SELECT municipality, MAX(updated_at) as last_update FROM beaches WHERE publish_status = 'published' GROUP BY municipality");
foreach ($muniRows ?: [] as $row) {
    $municipalityLastmod[$row['municipality']] = $row['last_update']
        ? date('Y-m-d', strtotime($row['last_update']))
        : $fallbackDate;
}

// Lastmod for derived listing pages (near/tag) reflects when their underlying
// beach data actually changed, never the generation date.
$globalRow = query("SELECT MAX(updated_at) as last_update FROM beaches WHERE publish_status = 'published'");
$globalBeachLastmod = ($globalRow && $globalRow[0]['last_update'])
    ? date('Y-m-d', strtotime($globalRow[0]['last_update']))
    : $fallbackDate;

$tagLastmod = [];
$tagRows = query("
    SELECT bt.tag AS slug, MAX(b.updated_at) AS last_update
    FROM beach_tags bt JOIN beaches b ON b.id = bt.beach_id
    WHERE b.publish_status = 'published' GROUP BY bt.tag
");
foreach ($tagRows ?: [] as $row) {
    $tagLastmod[$row['slug']] = $row['last_update'] ? date('Y-m-d', strtotime($row['last_update'])) : $fallbackDate;
}
$amenityRows = query("
    SELECT ba.amenity AS slug, MAX(b.updated_at) AS last_update
    FROM beach_amenities ba JOIN beaches b ON b.id = ba.beach_id
    WHERE b.publish_status = 'published' GROUP BY ba.amenity
");
foreach ($amenityRows ?: [] as $row) {
    $tagLastmod['with-' . $row['slug']] = $row['last_update'] ? date('Y-m-d', strtotime($row['last_update'])) : $fallbackDate;
}

// hreflang alternates for a bilingual URL pair, mirroring the on-page
// <link rel="alternate"> set (en, es, es-PR, x-default) so both signals agree.
$hreflangAlternates = static function (string $enPath, string $esPath) use ($appUrl): string {
    $en = h($appUrl . $enPath);
    $es = h($appUrl . $esPath);
    return "        <xhtml:link rel=\"alternate\" hreflang=\"en\" href=\"{$en}\"/>\n"
         . "        <xhtml:link rel=\"alternate\" hreflang=\"es\" href=\"{$es}\"/>\n"
         . "        <xhtml:link rel=\"alternate\" hreflang=\"es-PR\" href=\"{$es}\"/>\n"
         . "        <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"{$en}\"/>\n";
};

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">

<?php
foreach (sitemapLocaleRoutes() as $entry):
    // Single-URL bilingual routes (e.g. /advertise) list the same path for both
    // locales — emit it once.
    $localePaths = array_unique([$entry['en'], $entry['es']]);
    // Alternates only for routes with a genuine translated counterpart.
    $routeAlternates = ($entry['localized'] && count($localePaths) > 1)
        ? $hreflangAlternates($entry['en'], $entry['es'])
        : '';
    // Determine lastmod from script file
    $routeLastmod = $fallbackDate;
    foreach (localeRoutes() as $routeKey => $route) {
        if (normalizeLocalePath((string)$route['en']) === $entry['en']) {
            $routeLastmod = $routeScriptLastmod[$routeKey] ?? $fallbackDate;
            break;
        }
    }
    foreach ($localePaths as $localePath):
?>
    <url>
        <loc><?= h($appUrl) ?><?= h($localePath) ?></loc>
<?= $routeAlternates ?>        <lastmod><?= $routeLastmod ?></lastmod>
        <changefreq><?= h($entry['changefreq']) ?></changefreq>
        <priority><?= h($entry['priority']) ?></priority>
    </url>
<?php
    endforeach;
endforeach;
?>

    <!-- Individual Beach Pages -->
<?php
$beaches = query("
    SELECT slug, name, cover_image, updated_at
    FROM beaches
    WHERE publish_status = 'published'
    ORDER BY name
");

foreach ($beaches as $beach):
    $lastmod = $beach['updated_at'] ? date('Y-m-d', strtotime($beach['updated_at'])) : $fallbackDate;
    $resolvedImage = getBeachImageUrl($beach, 'medium');
    $imageUrl = strpos($resolvedImage, 'http') === 0
        ? $resolvedImage
        : $appUrl . $resolvedImage;
    $beachAlternates = $hreflangAlternates('/beach/' . $beach['slug'], '/es/playa/' . $beach['slug']);
?>
    <url>
        <loc><?= h($appUrl) ?>/beach/<?= h($beach['slug']) ?></loc>
<?= $beachAlternates ?>        <lastmod><?= $lastmod ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
        <?php if (strpos($resolvedImage, 'placeholder') === false): ?>
        <image:image>
            <image:loc><?= h($imageUrl) ?></image:loc>
            <image:title><?= h($beach['name']) ?></image:title>
        </image:image>
        <?php endif; ?>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/es/playa/<?= h($beach['slug']) ?></loc>
<?= $beachAlternates ?>        <lastmod><?= $lastmod ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
        <?php if (strpos($resolvedImage, 'placeholder') === false): ?>
        <image:image>
            <image:loc><?= h($imageUrl) ?></image:loc>
            <image:title><?= h($beach['name']) ?></image:title>
        </image:image>
        <?php endif; ?>
    </url>
<?php endforeach; ?>

    <!-- Municipality Landing Pages -->
<?php
$municipalities = array_unique(array_column(
    query("SELECT DISTINCT municipality FROM beaches WHERE publish_status = 'published' ORDER BY municipality"),
    'municipality'
));

foreach ($municipalities as $municipality):
    $slug = strtolower(str_replace(' ', '-', stripAccents($municipality)));
    $muniLastmod = $municipalityLastmod[$municipality] ?? $fallbackDate;
    $muniAlternates = $hreflangAlternates('/beaches-in-' . $slug, '/es/playas-en-' . $slug);
?>
    <url>
        <loc><?= h($appUrl) ?>/beaches-in-<?= h($slug) ?></loc>
<?= $muniAlternates ?>        <lastmod><?= $muniLastmod ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/es/playas-en-<?= h($slug) ?></loc>
<?= $muniAlternates ?>        <lastmod><?= $muniLastmod ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
<?php endforeach; ?>



    <!-- Beaches Near Location Pages -->
<?php
$nearLocations = [
    "ponce" => "ponce", "aguadilla" => "aguadilla", "rincon" => "rincon",
    "fajardo" => "fajardo", "mayaguez" => "mayaguez", "humacao" => "humacao",
    "arecibo" => "arecibo", "cabo-rojo" => "cabo-rojo", "vega-baja" => "vega-baja",
    "dorado" => "dorado",
];
foreach ($nearLocations as $enSlug => $esSlug):
    $nearAlternates = $hreflangAlternates('/beaches-near-' . $enSlug, '/es/playas-cerca-de-' . $esSlug);
?>
    <url>
        <loc><?= h($appUrl) ?>/beaches-near-<?= h($enSlug) ?></loc>
<?= $nearAlternates ?>        <lastmod><?= $globalBeachLastmod ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/es/playas-cerca-de-<?= h($esSlug) ?></loc>
<?= $nearAlternates ?>        <lastmod><?= $globalBeachLastmod ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
<?php endforeach; ?>

    <!-- Tag/Amenity Landing Pages -->
<?php
$tagPages = [
    "swimming" => "natacion",
    "scenic" => "escenicas",
    "calm-waters" => "aguas-tranquilas",
    "fishing" => "pesca",
    "accessible" => "accesibles",
    "diving" => "buceo",
    "camping" => "acampar",
    "popular" => "populares",
    "surfing" => "surf",
    "snorkeling" => "snorkel",
    "family-friendly" => "familiares",
    "secluded" => "aisladas",
    "with-parking" => "con-estacionamiento",
    "with-restrooms" => "con-banos",
    "with-showers" => "con-duchas",
    "with-lifeguard" => "con-salvavidas",
    "with-picnic-areas" => "con-areas-picnic",
    "with-food" => "con-comida",
];
foreach ($tagPages as $enSlug => $esSlug):
    $pageLastmod = $tagLastmod[$enSlug] ?? $globalBeachLastmod;
    $tagAlternates = $hreflangAlternates('/beaches/' . $enSlug, '/es/playas/' . $esSlug);
?>
    <url>
        <loc><?= h($appUrl) ?>/beaches/<?= h($enSlug) ?></loc>
<?= $tagAlternates ?>        <lastmod><?= $pageLastmod ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/es/playas/<?= h($esSlug) ?></loc>
<?= $tagAlternates ?>        <lastmod><?= $pageLastmod ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
<?php endforeach; ?>

</urlset>
