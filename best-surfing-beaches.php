<?php
/**
 * Best Surfing Beaches in Puerto Rico - SEO Landing Page
 * Target keywords: surfing puerto rico, best surf beaches puerto rico, rincon surfing
 */

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/constants.php';
require_once __DIR__ . '/components/seo-schemas.php';

// Page metadata
$pageTitle = 'Best Surfing Beaches in Puerto Rico (2025 Guide)';
$pageDescription = 'Discover the best surfing beaches in Puerto Rico for 2025. From Rincon\'s world-class breaks to beginner-friendly spots, find your perfect wave on the island.';
$canonicalUrl = ($_ENV['APP_URL'] ?? 'https://www.puertoricobeachfinder.com') . '/best-surfing-beaches';

// Fetch surfing beaches
$surfingBeaches = query("
    SELECT b.*,
           GROUP_CONCAT(DISTINCT bt.tag) as tag_list,
           GROUP_CONCAT(DISTINCT ba.amenity) as amenity_list
    FROM beaches b
    LEFT JOIN beach_tags bt ON b.id = bt.beach_id
    LEFT JOIN beach_amenities ba ON b.id = ba.beach_id
    WHERE b.publish_status = 'published'
    AND EXISTS (SELECT 1 FROM beach_tags bt2 WHERE bt2.beach_id = b.id AND bt2.tag = 'surfing')
    GROUP BY b.id
    ORDER BY b.google_rating DESC, b.google_review_count DESC
    LIMIT 15
");

// Process tags and amenities
foreach ($surfingBeaches as &$beach) {
    $beach['tags'] = $beach['tag_list'] ? explode(',', $beach['tag_list']) : [];
    $beach['amenities'] = $beach['amenity_list'] ? explode(',', $beach['amenity_list']) : [];
}
unset($beach);

// Generate structured data
$extraHead = articleSchema(
    $pageTitle,
    $pageDescription,
    '/best-surfing-beaches',
    $surfingBeaches[0]['cover_image'] ?? null,
    '2025-01-01'
);
$extraHead .= collectionPageSchema($pageTitle, $pageDescription, $surfingBeaches);
$extraHead .= websiteSchema();

// FAQ data
$pageFaqs = [
    [
        'question' => 'Where is the best surfing in Puerto Rico?',
        'answer' => 'Rincon on Puerto Rico\'s west coast is the island\'s premier surfing destination, hosting professional competitions and offering world-class waves. Other top spots include Domes Beach, Maria\'s Beach, Tres Palmas, and Wilderness. For beginners, Jobos Beach in Isabela and Pine Grove in Isla Verde offer gentler waves.'
    ],
    [
        'question' => 'When is the best time to surf in Puerto Rico?',
        'answer' => 'The best surfing season runs from November through March when winter swells from North Atlantic storms reach the north and west coasts. Wave heights can reach 8-20+ feet during peak swells. Summer offers smaller, more consistent waves on the south coast, ideal for beginners.'
    ],
    [
        'question' => 'Can beginners surf in Puerto Rico?',
        'answer' => 'Yes! Puerto Rico has excellent beginner spots including Pine Grove Beach (Isla Verde), Jobos Beach (Isabela), and La Pared (Luquillo). Many beaches have surf schools offering lessons and board rentals. Start with a longboard or foam board in smaller waves.'
    ],
    [
        'question' => 'Do I need to bring my own surfboard to Puerto Rico?',
        'answer' => 'While you can bring your own board (most airlines charge $100-150 each way), there are numerous surf shops offering quality rentals throughout the island. Rincon, Aguadilla, and San Juan have the best selection of rental boards ranging from longboards to shortboards.'
    ],
    [
        'question' => 'Are there surf competitions in Puerto Rico?',
        'answer' => 'Yes, Puerto Rico hosts several major surf competitions. Rincon hosts events during winter including stops on professional tours. The island has produced champion surfers and remains a significant destination on the competitive surfing circuit.'
    ]
];
$extraHead .= faqSchema($pageFaqs);

include __DIR__ . '/components/header.php';
?>

<!-- Hero Section -->
<section class="hero-gradient text-white py-16 md:py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-3xl md:text-5xl font-bold mb-6">
            Best Surfing Beaches in Puerto Rico
        </h1>
        <p class="text-lg md:text-xl opacity-90 max-w-3xl mx-auto page-description">
            Ride world-class waves in the Caribbean. From Rincon's legendary breaks to hidden gems across the island, discover Puerto Rico's best surf spots.
        </p>
        <p class="text-sm mt-4 opacity-75">Updated January 2025 | 45+ surf breaks reviewed</p>
    </div>
</section>

<!-- Quick Navigation -->
<section class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex flex-wrap gap-2 justify-center text-sm">
            <span class="text-gray-500">Jump to:</span>
            <a href="#top-beaches" class="text-blue-600 hover:underline">Top Spots</a>
            <span class="text-gray-300">|</span>
            <a href="#by-level" class="text-blue-600 hover:underline">By Level</a>
            <span class="text-gray-300">|</span>
            <a href="#season" class="text-blue-600 hover:underline">Surf Season</a>
            <span class="text-gray-300">|</span>
            <a href="#faq" class="text-blue-600 hover:underline">FAQs</a>
        </div>
    </div>
</section>

<!-- Introduction -->
<section class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4">
        <div class="prose prose-lg max-w-none beach-description">
            <p>Puerto Rico is a <strong>world-renowned surfing destination</strong> with consistent swells, warm water year-round, and a variety of breaks for all skill levels. The island's unique position in the Caribbean receives swells from multiple directions.</p>

            <p>Rincon, often called the <strong>"Caribbean's Hawaii,"</strong> put Puerto Rico on the surfing map when it hosted the 1968 World Surfing Championship. Today, the island continues to attract surfers from around the globe seeking quality waves without the crowds of more popular destinations.</p>
        </div>
    </div>
</section>

<!-- Top Surfing Beaches List -->
<section id="top-beaches" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Top Surfing Beaches in Puerto Rico
        </h2>

        <div class="space-y-8">
            <?php foreach ($surfingBeaches as $index => $beach): ?>
            <article class="bg-white rounded-xl shadow-md overflow-hidden md:flex">
                <div class="md:w-1/3 relative">
                    <?php if ($beach['cover_image']): ?>
                    <img src="<?= h($beach['cover_image']) ?>"
                         alt="Surfing at <?= h($beach['name']) ?>"
                         class="w-full h-48 md:h-full object-cover"
                         loading="<?= $index < 3 ? 'eager' : 'lazy' ?>">
                    <?php else: ?>
                    <div class="w-full h-48 md:h-full bg-gradient-to-br from-orange-400 to-red-600 flex items-center justify-center">
                        <span class="text-6xl">🏄</span>
                    </div>
                    <?php endif; ?>
                    <div class="absolute top-4 left-4 bg-orange-600 text-white px-3 py-1 rounded-full font-bold">
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
                        <span class="bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded">
                            <?= h(getTagLabel($tag)) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="flex gap-3">
                        <a href="/beach/<?= h($beach['slug']) ?>"
                           class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
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

<!-- By Skill Level -->
<section id="by-level" class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Surf Spots by Skill Level
        </h2>

        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🌱</div>
                <h3 class="text-lg font-bold text-gray-900 mb-3">Beginner Friendly</h3>
                <ul class="text-gray-600 text-sm space-y-2">
                    <li><strong>Pine Grove Beach</strong> - Isla Verde, gentle waves</li>
                    <li><strong>Jobos Beach</strong> - Isabela, soft sand bottom</li>
                    <li><strong>La Pared</strong> - Luquillo, consistent small waves</li>
                    <li><strong>Aviones</strong> - Loiza, beginner lessons available</li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🌊</div>
                <h3 class="text-lg font-bold text-gray-900 mb-3">Intermediate</h3>
                <ul class="text-gray-600 text-sm space-y-2">
                    <li><strong>Crash Boat</strong> - Aguadilla, reef break</li>
                    <li><strong>Maria's Beach</strong> - Rincon, point break</li>
                    <li><strong>Sandy Beach</strong> - Rincon, beach break</li>
                    <li><strong>La Selva</strong> - Hatillo, less crowded</li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🔥</div>
                <h3 class="text-lg font-bold text-gray-900 mb-3">Advanced / Expert</h3>
                <ul class="text-gray-600 text-sm space-y-2">
                    <li><strong>Tres Palmas</strong> - Big wave spot, reef break</li>
                    <li><strong>Domes</strong> - Powerful reef break</li>
                    <li><strong>Wilderness</strong> - Remote, challenging</li>
                    <li><strong>Gas Chambers</strong> - Heavy, hollow waves</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Surf Season Info -->
<section id="season" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Puerto Rico Surf Season Guide
        </h2>

        <div class="grid md:grid-cols-2 gap-8">
            <div class="bg-blue-50 rounded-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">🌊 Winter Season (Nov-Mar)</h3>
                <ul class="text-gray-700 space-y-2">
                    <li><strong>Swell:</strong> North Atlantic winter storms</li>
                    <li><strong>Wave Height:</strong> 4-20+ feet</li>
                    <li><strong>Best Coasts:</strong> North and West</li>
                    <li><strong>Water Temp:</strong> 77-80°F</li>
                    <li><strong>Top Spots:</strong> Rincon, Aguadilla, Isabela</li>
                    <li><strong>Crowds:</strong> Peak season, more surfers</li>
                </ul>
            </div>

            <div class="bg-green-50 rounded-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">☀️ Summer Season (Apr-Oct)</h3>
                <ul class="text-gray-700 space-y-2">
                    <li><strong>Swell:</strong> Southern Caribbean swells</li>
                    <li><strong>Wave Height:</strong> 2-6 feet</li>
                    <li><strong>Best Coasts:</strong> South and East</li>
                    <li><strong>Water Temp:</strong> 82-86°F</li>
                    <li><strong>Top Spots:</strong> Playa Guanica, Ponce</li>
                    <li><strong>Crowds:</strong> Less crowded, great for learning</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Other Beach Categories -->
<section class="py-12 bg-gray-50">
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

            <a href="/best-snorkeling-beaches" class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group">
                <div class="text-4xl mb-4">🤿</div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600">Best for Snorkeling</h3>
                <p class="text-gray-600 text-sm mt-2">Crystal-clear waters and coral reefs</p>
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
<section id="faq" class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Surfing in Puerto Rico: FAQs
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
<section class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Find Surf Spots on the Map
        </h2>
        <div class="text-center">
            <a href="/?view=map&activity=surfing" class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                <span>🗺️</span>
                <span>View Surf Beaches Map</span>
            </a>
            <p class="text-gray-600 mt-4">Filter the interactive map to show all surf spots.</p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-12 bg-orange-600 text-white">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-4">Find Your Perfect Wave</h2>
        <p class="text-lg opacity-90 mb-6">Tell us your skill level and preferences, and we'll match you with the ideal surf spots in Puerto Rico.</p>
        <a href="/quiz.php" class="inline-block bg-white text-orange-600 hover:bg-orange-50 px-8 py-3 rounded-lg font-semibold transition-colors">
            Take the Beach Match Quiz
        </a>
    </div>
</section>

<?php include __DIR__ . '/components/footer.php'; ?>
