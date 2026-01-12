<?php
/**
 * XML Sitemap Generator
 * Generates sitemap.xml dynamically
 */

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';

$appUrl = rtrim($_ENV['APP_URL'] ?? 'https://www.puertoricobeachfinder.com', '/');

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

    <!-- Homepage -->
    <url>
        <loc><?= h($appUrl) ?>/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- Beach Match Quiz -->
    <url>
        <loc><?= h($appUrl) ?>/quiz.php</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>

    <!-- SEO Landing Pages -->
    <url>
        <loc><?= h($appUrl) ?>/best-beaches</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/best-snorkeling-beaches</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/best-surfing-beaches</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/best-family-beaches</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/beaches-near-san-juan</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>

    <!-- Individual Beach Pages -->
<?php
$beaches = query("
    SELECT slug, name, cover_image, updated_at
    FROM beaches
    WHERE publish_status = 'published'
    ORDER BY name
");

foreach ($beaches as $beach):
    $lastmod = $beach['updated_at'] ? date('Y-m-d', strtotime($beach['updated_at'])) : date('Y-m-d');
    $imageUrl = strpos($beach['cover_image'], 'http') === 0
        ? $beach['cover_image']
        : $appUrl . $beach['cover_image'];
?>
    <url>
        <loc><?= h($appUrl) ?>/beach/<?= h($beach['slug']) ?></loc>
        <lastmod><?= $lastmod ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
        <?php if ($beach['cover_image'] && strpos($beach['cover_image'], 'placeholder') === false): ?>
        <image:image>
            <image:loc><?= h($imageUrl) ?></image:loc>
            <image:title><?= h($beach['name']) ?></image:title>
        </image:image>
        <?php endif; ?>
    </url>
<?php endforeach; ?>

    <!-- Municipality Filter Pages -->
<?php
$municipalities = array_column(
    query("SELECT DISTINCT municipality FROM beaches WHERE publish_status = 'published' ORDER BY municipality"),
    'municipality'
);

foreach ($municipalities as $municipality):
?>
    <url>
        <loc><?= h($appUrl) ?>/?municipality=<?= urlencode($municipality) ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
<?php endforeach; ?>

    <!-- Tag Filter Pages -->
<?php
$tags = array_column(
    query("SELECT DISTINCT tag FROM beach_tags ORDER BY tag"),
    'tag'
);

foreach ($tags as $tag):
?>
    <url>
        <loc><?= h($appUrl) ?>/?tags[]=<?= urlencode($tag) ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.5</priority>
    </url>
<?php endforeach; ?>

</urlset>
