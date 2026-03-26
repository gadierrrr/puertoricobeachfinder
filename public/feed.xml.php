<?php
/**
 * RSS Feed for Puerto Rico Beach Finder
 * Outputs guides, collection pages, and recently updated beaches
 * URL: /feed.xml
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';

$appUrl = getPublicBaseUrl();

header('Content-Type: application/rss+xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

// Get recently updated beaches (top 50)
$beaches = query("
    SELECT slug, name, municipality, description, cover_image, updated_at
    FROM beaches
    WHERE publish_status = 'published' AND description IS NOT NULL
    ORDER BY updated_at DESC
    LIMIT 50
") ?: [];

// Guide pages (static list since they're file-based)
$guides = [
    ['slug' => 'spring-break-beaches-puerto-rico', 'title' => 'Spring Break Beaches in Puerto Rico: Complete Guide', 'desc' => 'The ultimate guide to Puerto Rico spring break beaches, from party hotspots to chill escapes.'],
    ['slug' => 'best-time-visit-puerto-rico-beaches', 'title' => 'Best Time to Visit Puerto Rico Beaches', 'desc' => 'Month-by-month guide to weather, crowds, and conditions at Puerto Rico beaches.'],
    ['slug' => 'snorkeling-guide', 'title' => 'Puerto Rico Snorkeling Guide', 'desc' => 'Complete snorkeling guide with the best spots, gear tips, and marine life guide.'],
    ['slug' => 'surfing-guide', 'title' => 'Puerto Rico Surfing Guide', 'desc' => 'Find the best surf breaks in Puerto Rico from beginner spots to expert waves.'],
    ['slug' => 'culebra-vs-vieques', 'title' => 'Culebra vs Vieques: Which Island Has Better Beaches?', 'desc' => 'Detailed comparison of Puerto Rico\'s two island paradises.'],
    ['slug' => 'bioluminescent-bays', 'title' => 'Puerto Rico Bioluminescent Bays Guide', 'desc' => 'Everything you need to know about visiting PR\'s magical glowing bays.'],
    ['slug' => 'getting-to-puerto-rico-beaches', 'title' => 'Getting to Puerto Rico Beaches: Transportation Guide', 'desc' => 'How to get around Puerto Rico to reach the best beaches by car, bus, or ferry.'],
    ['slug' => 'beach-packing-list', 'title' => 'Puerto Rico Beach Packing List', 'desc' => 'Essential items to pack for a beach day or vacation in Puerto Rico.'],
    ['slug' => 'beach-safety-tips', 'title' => 'Puerto Rico Beach Safety Tips', 'desc' => 'Stay safe at Puerto Rico beaches with these important safety guidelines.'],
    ['slug' => 'beach-photography-tips', 'title' => 'Puerto Rico Beach Photography Tips', 'desc' => 'Capture stunning beach photos with these expert photography tips.'],
    ['slug' => 'family-beach-vacation-planning', 'title' => 'Family Beach Vacation Planning in Puerto Rico', 'desc' => 'Plan the perfect family beach trip to Puerto Rico with kids.'],
];

// Collection / tag pages
$collections = [
    ['path' => '/best-beaches', 'title' => 'Best Beaches in Puerto Rico', 'desc' => 'Curated list of the top-rated beaches across Puerto Rico.'],
    ['path' => '/beaches/swimming', 'title' => 'Best Swimming Beaches in Puerto Rico', 'desc' => '226+ beaches with the best swimming conditions.'],
    ['path' => '/beaches/scenic', 'title' => 'Most Scenic Beaches in Puerto Rico', 'desc' => '380+ stunning beaches with dramatic views.'],
    ['path' => '/beaches/calm-waters', 'title' => 'Calm Water Beaches in Puerto Rico', 'desc' => '105+ beaches with gentle waves, perfect for families.'],
    ['path' => '/beaches/with-parking', 'title' => 'Beaches with Parking in Puerto Rico', 'desc' => '357+ beaches with convenient parking options.'],
    ['path' => '/beaches/accessible', 'title' => 'Accessible Beaches in Puerto Rico', 'desc' => 'Wheelchair and mobility-friendly beaches.'],
    ['path' => '/beaches/camping', 'title' => 'Camping Beaches in Puerto Rico', 'desc' => 'Beach camping spots and beachfront campgrounds.'],
    ['path' => '/beaches/diving', 'title' => 'Diving Beaches in Puerto Rico', 'desc' => 'Shore-entry dive sites and reef access points.'],
    ['path' => '/beaches/fishing', 'title' => 'Fishing Beaches in Puerto Rico', 'desc' => 'Best shore fishing spots across the island.'],
    ['path' => '/beaches/with-food', 'title' => 'Beaches with Food & Restaurants', 'desc' => '90+ beaches with food vendors and restaurants.'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/">
  <channel>
    <title>Puerto Rico Beach Finder</title>
    <link><?= h($appUrl) ?></link>
    <description>Discover all 466+ beaches in Puerto Rico. Find your perfect beach with our comprehensive guide, interactive quiz, and detailed beach profiles.</description>
    <language>en-us</language>
    <lastBuildDate><?= date('r') ?></lastBuildDate>
    <atom:link href="<?= h($appUrl) ?>/feed.xml" rel="self" type="application/rss+xml"/>
    <image>
      <url><?= h($appUrl) ?>/assets/images/og-image.jpg</url>
      <title>Puerto Rico Beach Finder</title>
      <link><?= h($appUrl) ?></link>
    </image>

    <!-- Guides -->
<?php foreach ($guides as $guide): ?>
    <item>
      <title><?= h($guide['title']) ?></title>
      <link><?= h($appUrl) ?>/guides/<?= h($guide['slug']) ?></link>
      <guid isPermaLink="true"><?= h($appUrl) ?>/guides/<?= h($guide['slug']) ?></guid>
      <description><?= h($guide['desc']) ?></description>
      <category>Guide</category>
    </item>
<?php endforeach; ?>

    <!-- Collection Pages -->
<?php foreach ($collections as $col): ?>
    <item>
      <title><?= h($col['title']) ?></title>
      <link><?= h($appUrl) ?><?= h($col['path']) ?></link>
      <guid isPermaLink="true"><?= h($appUrl) ?><?= h($col['path']) ?></guid>
      <description><?= h($col['desc']) ?></description>
      <category>Collection</category>
    </item>
<?php endforeach; ?>

    <!-- Recent Beaches -->
<?php foreach ($beaches as $beach):
    $imageUrl = (strpos($beach['cover_image'], 'http') === 0) ? $beach['cover_image'] : $appUrl . $beach['cover_image'];
    $pubDate = $beach['updated_at'] ? date('r', strtotime($beach['updated_at'])) : date('r');
    $desc = mb_substr(strip_tags($beach['description'] ?? ''), 0, 300);
?>
    <item>
      <title><?= h($beach['name']) ?> - <?= h($beach['municipality']) ?>, Puerto Rico</title>
      <link><?= h($appUrl) ?>/beach/<?= h($beach['slug']) ?></link>
      <guid isPermaLink="true"><?= h($appUrl) ?>/beach/<?= h($beach['slug']) ?></guid>
      <description><?= h($desc) ?></description>
      <pubDate><?= $pubDate ?></pubDate>
      <category>Beach</category>
<?php if ($beach['cover_image'] && strpos($beach['cover_image'], 'placeholder') === false): ?>
      <media:content url="<?= h($imageUrl) ?>" medium="image"/>
<?php endif; ?>
    </item>
<?php endforeach; ?>

  </channel>
</rss>
