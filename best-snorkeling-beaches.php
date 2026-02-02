<?php
/**
 * Best Snorkeling Beaches in Puerto Rico - SEO Landing Page
 * Target keywords: snorkeling puerto rico, best snorkeling beaches puerto rico
 */

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/constants.php';
require_once __DIR__ . '/components/seo-schemas.php';

// Page metadata
$pageTitle = 'Best Snorkeling Beaches in Puerto Rico (2025 Guide)';
$pageDescription = 'Discover the best snorkeling beaches in Puerto Rico for 2025. Crystal-clear waters, vibrant coral reefs, and tropical marine life await at these top snorkeling spots.';
$canonicalUrl = ($_ENV['APP_URL'] ?? 'https://www.puertoricobeachfinder.com') . '/best-snorkeling-beaches';

// Fetch snorkeling beaches
$snorkelingBeaches = query("
    SELECT b.*,
           GROUP_CONCAT(DISTINCT bt.tag) as tag_list,
           GROUP_CONCAT(DISTINCT ba.amenity) as amenity_list
    FROM beaches b
    LEFT JOIN beach_tags bt ON b.id = bt.beach_id
    LEFT JOIN beach_amenities ba ON b.id = ba.beach_id
    WHERE b.publish_status = 'published'
    AND EXISTS (SELECT 1 FROM beach_tags bt2 WHERE bt2.beach_id = b.id AND bt2.tag = 'snorkeling')
    GROUP BY b.id
    ORDER BY b.google_rating DESC, b.google_review_count DESC
    LIMIT 15
");

// Process tags and amenities
foreach ($snorkelingBeaches as &$beach) {
    $beach['tags'] = $beach['tag_list'] ? explode(',', $beach['tag_list']) : [];
    $beach['amenities'] = $beach['amenity_list'] ? explode(',', $beach['amenity_list']) : [];
}
unset($beach);

// Generate structured data
$extraHead = articleSchema(
    $pageTitle,
    $pageDescription,
    '/best-snorkeling-beaches',
    $snorkelingBeaches[0]['cover_image'] ?? null,
    '2025-01-01'
);
$extraHead .= collectionPageSchema($pageTitle, $pageDescription, $snorkelingBeaches);
$extraHead .= websiteSchema();

// FAQ data
$pageFaqs = [
    [
        'question' => 'Where is the best snorkeling in Puerto Rico?',
        'answer' => 'The best snorkeling in Puerto Rico is found at Culebra Island (especially Tamarindo Beach and Carlos Rosario), Steps Beach in Rincon, Escambron Beach in San Juan, and the beaches of Vieques. These locations offer crystal-clear water, healthy coral reefs, and abundant marine life.'
    ],
    [
        'question' => 'Do I need to bring my own snorkeling gear to Puerto Rico?',
        'answer' => 'While you can bring your own gear, most popular snorkeling beaches have nearby rental shops. Prices typically range from $10-20 per day for mask, snorkel, and fins. For the best fit and hygiene, many visitors prefer to bring their own mask and snorkel.'
    ],
    [
        'question' => 'What marine life can I see snorkeling in Puerto Rico?',
        'answer' => 'Puerto Rico\'s waters are home to sea turtles, tropical fish (parrotfish, angelfish, sergeant majors), rays, octopus, lobster, and various coral species. Manatees are occasionally spotted in certain bays. The best diversity is found around coral reef areas.'
    ],
    [
        'question' => 'When is the best time for snorkeling in Puerto Rico?',
        'answer' => 'The best snorkeling conditions are from December through April when seas are calmest and visibility is highest (often 50-100+ feet). Summer months are also good but may have more rain. Avoid snorkeling after heavy rains when runoff reduces visibility.'
    ],
    [
        'question' => 'Is snorkeling safe in Puerto Rico?',
        'answer' => 'Snorkeling is generally safe in Puerto Rico. Always check water conditions, avoid areas with strong currents, never snorkel alone, and respect marine life. Use reef-safe sunscreen to protect the coral ecosystem. Some beaches have lifeguards during peak hours.'
    ]
];
$extraHead .= faqSchema($pageFaqs);

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'Best Beaches', 'url' => '/best-beaches'],
    ['name' => 'Snorkeling Beaches']
];

include __DIR__ . '/components/header.php';
?>

<!-- Hero Section -->
<section class="hero-gradient text-white py-16 md:py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <!-- Breadcrumbs -->
        <div class="mb-6">
            <?php include __DIR__ . '/components/breadcrumbs.php'; ?>
        </div>
        <h1 class="text-3xl md:text-5xl font-bold mb-6">
            Best Snorkeling Beaches in Puerto Rico
        </h1>
        <p class="text-lg md:text-xl opacity-90 max-w-3xl mx-auto page-description">
            Explore Puerto Rico's underwater paradise. From vibrant coral reefs to sea turtle encounters, discover the island's best spots for snorkeling adventures.
        </p>
        <p class="text-sm mt-4 opacity-75">Updated January 2025 | 62+ snorkeling beaches reviewed</p>
    </div>
</section>

<!-- Quick Navigation -->
<section class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex flex-wrap gap-2 justify-center text-sm">
            <span class="text-gray-500">Jump to:</span>
            <a href="#top-beaches" class="text-blue-600 hover:underline">Top Spots</a>
            <span class="text-gray-300">|</span>
            <a href="#tips" class="text-blue-600 hover:underline">Snorkeling Tips</a>
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
            <p>Puerto Rico offers some of the <strong>Caribbean's best snorkeling</strong> with warm, clear waters year-round. The island's diverse marine ecosystems include coral reefs, seagrass beds, and rocky coastlines teeming with tropical fish and sea turtles.</p>

            <p>Whether you're a beginner looking for calm, shallow waters or an experienced snorkeler seeking vibrant reef systems, these carefully selected beaches offer the best underwater experiences in Puerto Rico.</p>
        </div>
    </div>
</section>

<!-- Top Snorkeling Beaches List -->
<section id="top-beaches" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Top Snorkeling Beaches in Puerto Rico
        </h2>

        <div class="space-y-8">
            <?php foreach ($snorkelingBeaches as $index => $beach): ?>
            <article class="bg-white rounded-xl shadow-md overflow-hidden md:flex">
                <div class="md:w-1/3 relative">
                    <?php if ($beach['cover_image']): ?>
                    <img src="<?= h($beach['cover_image']) ?>"
                         alt="Snorkeling at <?= h($beach['name']) ?>"
                         class="w-full h-48 md:h-full object-cover"
                         loading="<?= $index < 3 ? 'eager' : 'lazy' ?>">
                    <?php else: ?>
                    <div class="w-full h-48 md:h-full bg-gradient-to-br from-cyan-400 to-blue-600 flex items-center justify-center">
                        <span class="text-6xl">🤿</span>
                    </div>
                    <?php endif; ?>
                    <div class="absolute top-4 left-4 bg-cyan-600 text-white px-3 py-1 rounded-full font-bold">
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
                        <span class="bg-cyan-100 text-cyan-800 text-xs px-2 py-1 rounded">
                            <?= h(getTagLabel($tag)) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="flex gap-3">
                        <a href="/beach/<?= h($beach['slug']) ?>"
                           class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
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

<!-- Snorkeling Tips -->
<section id="tips" class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Puerto Rico Snorkeling Tips
        </h2>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🌅</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Best Time of Day</h3>
                <p class="text-gray-600 text-sm">Morning hours (8-11 AM) offer the calmest waters and best visibility. Winds typically pick up in the afternoon.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🧴</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Use Reef-Safe Sunscreen</h3>
                <p class="text-gray-600 text-sm">Protect coral reefs by using mineral-based sunscreens without oxybenzone or octinoxate. Many local shops sell reef-safe options.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🐢</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Respect Marine Life</h3>
                <p class="text-gray-600 text-sm">Don't touch coral, chase sea turtles, or stand on reef structures. Keep a respectful distance and observe quietly.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">👥</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Never Snorkel Alone</h3>
                <p class="text-gray-600 text-sm">Always snorkel with a buddy. If going solo, choose beaches with lifeguards and let someone know your plans.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🌊</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Check Conditions First</h3>
                <p class="text-gray-600 text-sm">Review wave forecasts and avoid snorkeling after heavy rain. Swell direction affects different beaches differently.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">📍</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Know Entry Points</h3>
                <p class="text-gray-600 text-sm">Some beaches have rocky entries. Water shoes are recommended for beaches with sea urchins or coral rubble.</p>
            </div>
        </div>
    </div>
</section>

<!-- Other Beach Categories -->
<section class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Explore More Beach Categories
        </h2>

        <div class="grid md:grid-cols-3 gap-6">
            <a href="/best-beaches" class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group">
                <div class="text-4xl mb-4">🏖️</div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600">Best Overall Beaches</h3>
                <p class="text-gray-600 text-sm mt-2">Top 15 beaches in Puerto Rico</p>
            </a>

            <a href="/best-surfing-beaches" class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group">
                <div class="text-4xl mb-4">🏄</div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600">Best for Surfing</h3>
                <p class="text-gray-600 text-sm mt-2">World-class waves on the west coast</p>
            </a>

            <a href="/best-family-beaches" class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group">
                <div class="text-4xl mb-4">👨‍👩‍👧‍👦</div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600">Best for Families</h3>
                <p class="text-gray-600 text-sm mt-2">Calm waters and kid-friendly facilities</p>
            </a>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section id="faq" class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Snorkeling in Puerto Rico: FAQs
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
<section id="map" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Find Snorkeling Beaches on the Map
        </h2>
        <div class="text-center">
            <a href="/?view=map&activity=snorkeling" class="inline-flex items-center gap-2 bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                <span>🗺️</span>
                <span>View Snorkeling Beaches Map</span>
            </a>
            <p class="text-gray-600 mt-4">Filter the interactive map to show all snorkeling spots.</p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-12 bg-cyan-600 text-white">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-4">Find Your Perfect Snorkeling Spot</h2>
        <p class="text-lg opacity-90 mb-6">Answer a few questions and get personalized beach recommendations based on your snorkeling experience and preferences.</p>
        <a href="/quiz.php" class="inline-block bg-white text-cyan-600 hover:bg-cyan-50 px-8 py-3 rounded-lg font-semibold transition-colors">
            Take the Beach Match Quiz
        </a>
    </div>
</section>

<?php include __DIR__ . '/components/footer.php'; ?>
