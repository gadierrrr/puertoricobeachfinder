<?php
/**
 * XML Sitemap Generator
 * Generates sitemap.xml dynamically
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';

$appUrl = getPublicBaseUrl();

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
        <loc><?= h($appUrl) ?>/quiz</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>

    <!-- Quiz Results Landing (shareable results links are noindex via token) -->
    <url>
        <loc><?= h($appUrl) ?>/quiz-results</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.4</priority>
    </url>

    <!-- Compare Beaches -->
    <url>
        <loc><?= h($appUrl) ?>/compare</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>

    <!-- Editorial Beach Pages -->
    <url>
        <loc><?= h($appUrl) ?>/best-beaches</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/best-beaches-san-juan</loc>
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
    <url>
        <loc><?= h($appUrl) ?>/beaches-near-san-juan-airport</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/hidden-beaches-puerto-rico</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>

    <!-- Beach Planning Guides -->
    <url>
        <loc><?= h($appUrl) ?>/guides/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/guides/getting-to-puerto-rico-beaches</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/guides/beach-safety-tips</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/guides/best-time-visit-puerto-rico-beaches</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/guides/beach-packing-list</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/guides/culebra-vs-vieques</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/guides/bioluminescent-bays</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/guides/snorkeling-guide</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/guides/surfing-guide</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/guides/beach-photography-tips</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/guides/family-beach-vacation-planning</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
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

    <!-- Municipality Landing Pages -->
<?php
function stripAccents($str) {
    $map = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u',
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N','Ü'=>'U'];
    return strtr($str, $map);
}

$municipalities = array_unique(array_column(
    query("SELECT DISTINCT municipality FROM beaches WHERE publish_status = 'published' ORDER BY municipality"),
    'municipality'
));

foreach ($municipalities as $municipality):
    $slug = strtolower(str_replace(' ', '-', stripAccents($municipality)));
?>
    <url>
        <loc><?= h($appUrl) ?>/beaches-in-<?= h($slug) ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
<?php endforeach; ?>

</urlset>
