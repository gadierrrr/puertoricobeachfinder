<?php
/**
 * Best Beaches in Puerto Rico - SEO Landing Page
 * Target keywords: best beaches puerto rico, top beaches puerto rico
 */

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/constants.php';
require_once __DIR__ . '/components/seo-schemas.php';

// Page metadata
$pageTitle = '15 Best Beaches in Puerto Rico (2025 Guide)';
$pageDescription = 'Discover the 15 best beaches in Puerto Rico for 2025. From Flamenco Beach in Culebra to hidden gems on Vieques, find your perfect Caribbean paradise with insider tips and directions.';
$canonicalUrl = ($_ENV['APP_URL'] ?? 'https://www.puertoricobeachfinder.com') . '/best-beaches';

// Fetch top beaches by rating
$topBeaches = query("
    SELECT b.*,
           GROUP_CONCAT(DISTINCT bt.tag) as tag_list,
           GROUP_CONCAT(DISTINCT ba.amenity) as amenity_list
    FROM beaches b
    LEFT JOIN beach_tags bt ON b.id = bt.beach_id
    LEFT JOIN beach_amenities ba ON b.id = ba.beach_id
    WHERE b.publish_status = 'published'
    AND b.google_rating IS NOT NULL
    GROUP BY b.id
    ORDER BY b.google_rating DESC, b.google_review_count DESC
    LIMIT 15
");

// Process tags and amenities
foreach ($topBeaches as &$beach) {
    $beach['tags'] = $beach['tag_list'] ? explode(',', $beach['tag_list']) : [];
    $beach['amenities'] = $beach['amenity_list'] ? explode(',', $beach['amenity_list']) : [];
}
unset($beach);

// Generate structured data
$extraHead = articleSchema(
    $pageTitle,
    $pageDescription,
    '/best-beaches',
    $topBeaches[0]['cover_image'] ?? null,
    '2025-01-01'
);
$extraHead .= collectionPageSchema($pageTitle, $pageDescription, $topBeaches);
$extraHead .= websiteSchema();

// FAQ data
$pageFaqs = [
    [
        'question' => 'What is the best beach in Puerto Rico?',
        'answer' => 'Flamenco Beach in Culebra is consistently ranked as the best beach in Puerto Rico and one of the top beaches in the world. It features pristine white sand, crystal-clear turquoise waters, and a serene atmosphere. Other top contenders include La Chiva Beach in Vieques and Luquillo Beach on the main island.'
    ],
    [
        'question' => 'Which Puerto Rico beaches are best for families?',
        'answer' => 'The best family beaches in Puerto Rico include Luquillo Beach (calm waters, food kiosks, lifeguards), Boqueron Beach (shallow waters, facilities), and Condado Beach in San Juan (close to hotels, restaurants). These beaches have gentle waves and good amenities for children.'
    ],
    [
        'question' => 'What are the best beaches near San Juan?',
        'answer' => 'The best beaches near San Juan include Isla Verde Beach (10 minutes from airport), Condado Beach (in the hotel district), Ocean Park Beach (trendy locals spot), and Escambron Beach (great for snorkeling). All are within 20 minutes of Old San Juan.'
    ],
    [
        'question' => 'Do you need a passport to visit Puerto Rico beaches?',
        'answer' => 'No passport is required for US citizens to visit Puerto Rico as it is a US territory. You only need a valid government-issued ID for domestic flights. International visitors should check visa requirements for US territories.'
    ],
    [
        'question' => 'When is the best time to visit Puerto Rico beaches?',
        'answer' => 'The best time to visit Puerto Rico beaches is during the dry season from December to April. This period offers sunny weather, calm seas, and ideal swimming conditions. For surfing, visit November through March when winter swells arrive on the north and west coasts.'
    ]
];
$extraHead .= faqSchema($pageFaqs);

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'Best Beaches in Puerto Rico']
];

include __DIR__ . '/components/header.php';
?>

<!-- Hero Section -->
<?php
$heroSubtext = 'Updated January 2025 | Based on 233+ beaches analyzed';
include __DIR__ . '/components/hero-collection.php';
?>

<!-- Quick Navigation -->
<section class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex flex-wrap gap-2 justify-center text-sm">
            <span class="text-gray-500">Jump to:</span>
            <a href="#top-beaches" class="text-blue-600 hover:underline">Top 15 List</a>
            <span class="text-gray-300">|</span>
            <a href="#by-activity" class="text-blue-600 hover:underline">By Activity</a>
            <span class="text-gray-300">|</span>
            <a href="#faq" class="text-blue-600 hover:underline">FAQs</a>
            <span class="text-gray-300">|</span>
            <a href="#map" class="text-blue-600 hover:underline">Map</a>
        </div>
    </div>
</section>

<!-- Introduction -->
<section class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4">
        <div class="prose prose-lg max-w-none beach-description">
            <p>Puerto Rico boasts over <strong>270 miles of coastline</strong> with nearly <strong>300 beaches</strong> to explore. From the powdery white sands of Culebra to the dramatic cliffs of Cabo Rojo, the island offers incredible diversity for beach lovers.</p>

            <p>Whether you're seeking the perfect snorkeling spot, a family-friendly bay, or world-class surf breaks, this guide covers the absolute best beaches Puerto Rico has to offer in 2025.</p>
        </div>
    </div>
</section>

<!-- Top 15 Beaches List -->
<section id="top-beaches" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            The 15 Best Beaches in Puerto Rico for 2025
        </h2>

        <div class="space-y-8">
            <?php foreach ($topBeaches as $index => $beach): ?>
            <article class="bg-white rounded-xl shadow-md overflow-hidden md:flex">
                <div class="md:w-1/3 relative">
                    <?php if ($beach['cover_image']): ?>
                    <img src="<?= h($beach['cover_image']) ?>"
                         alt="<?= h($beach['name']) ?>"
                         class="w-full h-48 md:h-full object-cover"
                         loading="<?= $index < 3 ? 'eager' : 'lazy' ?>">
                    <?php else: ?>
                    <div class="w-full h-48 md:h-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                        <span class="text-6xl">🏖️</span>
                    </div>
                    <?php endif; ?>
                    <div class="absolute top-4 left-4 bg-blue-600 text-white px-3 py-1 rounded-full font-bold">
                        #<?= $index + 1 ?>
                    </div>
                </div>
                <div class="md:w-2/3 p-6">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">
                                <a href="/beach/<?= h($beach['slug']) ?>" class="hover:text-blue-600">
                                    <?= h($beach['name']) ?>
                                </a>
                            </h3>
                            <p class="text-gray-600"><?= h($beach['municipality']) ?>, Puerto Rico</p>
                        </div>
                        <?php if ($beach['google_rating']): ?>
                        <div class="flex items-center bg-yellow-50 px-3 py-1 rounded-full">
                            <span class="text-yellow-500 mr-1">★</span>
                            <span class="font-semibold"><?= number_format($beach['google_rating'], 1) ?></span>
                            <span class="text-gray-500 text-sm ml-1">(<?= number_format($beach['google_review_count']) ?>)</span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <p class="text-gray-700 mb-4">
                        <?= h(substr($beach['description'] ?? '', 0, 200)) ?>...
                    </p>

                    <?php if (!empty($beach['tags'])): ?>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <?php foreach (array_slice($beach['tags'], 0, 4) as $tag): ?>
                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                            <?= h(getTagLabel($tag)) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="flex gap-3">
                        <a href="/beach/<?= h($beach['slug']) ?>"
                           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            View Details
                        </a>
                        <a href="https://www.google.com/maps/dir/?api=1&destination=<?= urlencode($beach['lat'] . ',' . $beach['lng']) ?>"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            Get Directions
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Beaches by Activity -->
<section id="by-activity" class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Best Puerto Rico Beaches by Activity
        </h2>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <a href="/best-snorkeling-beaches" class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group">
                <div class="text-4xl mb-4">🤿</div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600">Best for Snorkeling</h3>
                <p class="text-gray-600 text-sm mt-2">Crystal-clear waters and vibrant coral reefs</p>
            </a>

            <a href="/best-surfing-beaches" class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group">
                <div class="text-4xl mb-4">🏄</div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600">Best for Surfing</h3>
                <p class="text-gray-600 text-sm mt-2">World-class waves on the west coast</p>
            </a>

            <a href="/best-family-beaches" class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group">
                <div class="text-4xl mb-4">👨‍👩‍👧‍👦</div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600">Best for Families</h3>
                <p class="text-gray-600 text-sm mt-2">Calm waters, facilities, and lifeguards</p>
            </a>

            <a href="/beaches-near-san-juan" class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group">
                <div class="text-4xl mb-4">🏙️</div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600">Near San Juan</h3>
                <p class="text-gray-600 text-sm mt-2">Easy access from the capital city</p>
            </a>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section id="faq" class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Frequently Asked Questions
        </h2>

        <div class="space-y-4">
            <?php foreach ($pageFaqs as $faq): ?>
            <details class="bg-white rounded-lg shadow-md group">
                <summary class="flex items-center justify-between p-6 cursor-pointer font-semibold text-gray-900">
                    <?= h($faq['question']) ?>
                    <span class="text-blue-600 group-open:rotate-180 transition-transform">▼</span>
                </summary>
                <div class="px-6 pb-6 text-gray-700">
                    <?= h($faq['answer']) ?>
                </div>
            </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Map Section -->
<section id="map" class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Explore All Beaches on the Map
        </h2>
        <div class="text-center">
            <a href="/?view=map" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                <span>🗺️</span>
                <span>Open Interactive Map</span>
            </a>
            <p class="text-gray-600 mt-4">View all 233+ beaches with filters for activities, amenities, and more.</p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-12 bg-blue-600 text-white">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-4">Not Sure Which Beach is Right for You?</h2>
        <p class="text-lg opacity-90 mb-6">Take our quick quiz and get personalized beach recommendations based on your preferences.</p>
        <a href="/quiz.php" class="inline-block bg-white text-blue-600 hover:bg-blue-50 px-8 py-3 rounded-lg font-semibold transition-colors">
            Take the Beach Match Quiz
        </a>
    </div>
</section>

<?php include __DIR__ . '/components/footer.php'; ?>
