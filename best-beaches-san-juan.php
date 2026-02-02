<?php
/**
 * Best Beaches in San Juan - SEO Landing Page
 * Target keywords: best beaches san juan, san juan beaches, beaches in san juan puerto rico
 * Monthly searches: 6,600
 */

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/constants.php';
require_once __DIR__ . '/components/seo-schemas.php';

// Page metadata
$pageTitle = '12 Best Beaches in San Juan, Puerto Rico (2025 Guide)';
$pageDescription = 'Discover the best beaches in San Juan for 2025. From Condado and Isla Verde to Ocean Park and Escambrón, find pristine urban beaches with Caribbean beauty and city convenience.';
$canonicalUrl = ($_ENV['APP_URL'] ?? 'https://www.puertoricobeachfinder.com') . '/best-beaches-san-juan';

// Fetch top beaches in San Juan metro area (San Juan + Carolina municipalities)
$topBeaches = query("
    SELECT b.*,
           GROUP_CONCAT(DISTINCT bt.tag) as tag_list,
           GROUP_CONCAT(DISTINCT ba.amenity) as amenity_list
    FROM beaches b
    LEFT JOIN beach_tags bt ON b.id = bt.beach_id
    LEFT JOIN beach_amenities ba ON b.id = ba.beach_id
    WHERE b.publish_status = 'published'
    AND b.municipality IN ('San Juan', 'Carolina')
    AND b.google_rating IS NOT NULL
    GROUP BY b.id
    ORDER BY b.google_rating DESC, b.google_review_count DESC
    LIMIT 12
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
    '/best-beaches-san-juan',
    $topBeaches[0]['cover_image'] ?? null,
    '2025-01-15'
);
$extraHead .= collectionPageSchema($pageTitle, $pageDescription, $topBeaches);
$extraHead .= websiteSchema();

// FAQ data
$pageFaqs = [
    [
        'question' => 'Which San Juan beach is closest to Old San Juan?',
        'answer' => 'Balneario El Escambrón is the closest beach to Old San Juan, located just 1.5 miles east. You can reach it in 5-10 minutes by taxi or Uber, or take a scenic 30-minute walk along the coast. This sheltered beach offers excellent snorkeling and calm waters perfect for families.'
    ],
    [
        'question' => 'Are San Juan beaches safe for swimming?',
        'answer' => 'Yes, most San Juan beaches are safe for swimming year-round. Condado, Isla Verde, and Ocean Park have lifeguards during peak hours. The waters are generally calm, though ocean conditions can vary. Always check for flags (green = safe, yellow = caution, red = dangerous) and swim near lifeguard stations when possible.'
    ],
    [
        'question' => 'Can you walk to beaches from San Juan cruise port?',
        'answer' => 'While you cannot walk directly to swimming beaches from the cruise port, Escambrón Beach is only 1.5 miles away (30-minute walk or 5-minute taxi). Most cruise visitors take a short taxi ride to Condado Beach (3 miles) or Isla Verde (7 miles) for better beach experiences with more amenities.'
    ],
    [
        'question' => 'What is the difference between Condado and Isla Verde beaches?',
        'answer' => 'Condado Beach is in the upscale hotel district, walkable from Old San Juan, with a more tourist-oriented atmosphere and easy access to restaurants and nightlife. Isla Verde is closer to the airport, has a longer stretch of sand, slightly calmer waters, and a mix of hotels and local character. Both offer excellent urban beach experiences.'
    ],
    [
        'question' => 'Are there public beaches in San Juan?',
        'answer' => 'Yes, all beaches in Puerto Rico are public by law. In San Juan, popular public beaches include Balneario de Carolina (Isla Verde), Ocean Park Beach, Condado Beach, and Balneario El Escambrón. While some have hotels nearby, beach access is always free and open to everyone.'
    ],
    [
        'question' => 'Which San Juan beach is best for families?',
        'answer' => 'Balneario El Escambrón is the best family beach in San Juan. It features calm, protected waters ideal for children, lifeguards on duty, restrooms, showers, and shaded areas. The beach has minimal waves thanks to a natural reef barrier, making it perfect for young swimmers and snorkelers.'
    ],
    [
        'question' => 'How far is Isla Verde Beach from Old San Juan?',
        'answer' => 'Isla Verde Beach is approximately 7 miles (11 km) from Old San Juan, about 15-20 minutes by car or taxi. It is also just 10 minutes from Luis Muñoz Marín International Airport. The T5 public bus connects Old San Juan to Isla Verde for budget-friendly transportation.'
    ],
    [
        'question' => 'Do San Juan beaches have parking?',
        'answer' => 'Yes, most San Juan beaches have parking options. Balneario El Escambrón and Balneario de Carolina (Isla Verde) have public parking lots. In Condado and Ocean Park, street parking is available but can be limited during peak times. Many visitors use taxis, Ubers, or walk from nearby hotels.'
    ]
];
$extraHead .= faqSchema($pageFaqs);

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'Best Beaches', 'url' => '/best-beaches'],
    ['name' => 'Best Beaches in San Juan']
];

include __DIR__ . '/components/header.php';
?>

<!-- Hero Section -->
<section class="hero-gradient-purple text-white py-16 md:py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <!-- Breadcrumbs -->
        <div class="mb-6">
            <?php include __DIR__ . '/components/breadcrumbs.php'; ?>
        </div>
        <h1 class="text-3xl md:text-5xl font-bold mb-6">
            12 Best Beaches in San Juan, Puerto Rico
        </h1>
        <p class="text-lg md:text-xl opacity-90 max-w-3xl mx-auto page-description">
            Discover San Juan's finest urban beaches where Caribbean beauty meets city convenience. From the vibrant shores of Condado to the pristine sands of Isla Verde, find your perfect San Juan beach escape.
        </p>
        <p class="text-sm mt-4 opacity-75">Updated January 2025 | Expert-curated list</p>
    </div>
</section>

<!-- Quick Navigation -->
<section class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex flex-wrap gap-2 justify-center text-sm">
            <span class="text-gray-500">Jump to:</span>
            <a href="#top-beaches" class="text-purple-600 hover:underline">Top 12 Beaches</a>
            <span class="text-gray-300">|</span>
            <a href="#neighborhoods" class="text-purple-600 hover:underline">Beach Neighborhoods</a>
            <span class="text-gray-300">|</span>
            <a href="#getting-there" class="text-purple-600 hover:underline">Getting There</a>
            <span class="text-gray-300">|</span>
            <a href="#faq" class="text-purple-600 hover:underline">FAQs</a>
        </div>
    </div>
</section>

<!-- Introduction -->
<section class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4">
        <div class="prose prose-lg max-w-none beach-description">
            <p>San Juan offers something truly special: <strong>pristine Caribbean beaches</strong> just steps from world-class hotels, restaurants, and cultural attractions. Unlike remote island getaways that require ferry rides or long drives, San Juan's beaches let you enjoy <strong>turquoise waters and golden sand</strong> while staying connected to urban amenities.</p>

            <p>The San Juan metro area stretches along <strong>5 miles of spectacular Atlantic coastline</strong>, featuring distinct beach neighborhoods each with its own character. Whether you're staying in Old San Juan's historic district, Condado's luxury hotel zone, or near the airport in Isla Verde, you're never more than 15 minutes from an excellent beach.</p>

            <p>From the protected snorkeling cove at Escambrón to the social scene at Ocean Park, San Juan's beaches cater to every type of traveler. Families appreciate the calm waters and facilities at balnearios (public beaches), while surfers and kitesurfers find their playground where the Atlantic meets the Caribbean.</p>

            <p>Most San Juan beaches feature <strong>lifeguards, restrooms, and showers</strong>, making them convenient for cruise ship passengers and hotel guests alike. The walkability factor is exceptional—many beaches are accessible on foot from major hotels, and the waterfront promenades offer scenic strolls between beach neighborhoods.</p>
        </div>
    </div>
</section>

<!-- Top 12 Beaches List -->
<section id="top-beaches" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            The 12 Best Beaches in San Juan for 2025
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
                    <div class="w-full h-48 md:h-full bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center">
                        <span class="text-6xl">🏖️</span>
                    </div>
                    <?php endif; ?>
                    <div class="absolute top-4 left-4 bg-purple-600 text-white px-3 py-1 rounded-full font-bold">
                        #<?= $index + 1 ?>
                    </div>
                </div>
                <div class="md:w-2/3 p-6">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">
                                <a href="/beach/<?= h($beach['slug']) ?>" class="hover:text-purple-600">
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
                        <span class="bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded">
                            <?= h(getTagLabel($tag)) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="flex gap-3">
                        <a href="/beach/<?= h($beach['slug']) ?>"
                           class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
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

<!-- San Juan Beach Neighborhoods -->
<section id="neighborhoods" class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4 text-center">
            San Juan Beach Neighborhoods
        </h2>
        <p class="text-gray-600 text-center mb-8 max-w-3xl mx-auto">
            Each beach neighborhood in San Juan offers a unique atmosphere and character. Choose based on your travel style and preferences.
        </p>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow">
                <div class="text-4xl mb-4">🏨</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Condado</h3>
                <p class="text-gray-600 text-sm mb-3">The upscale hotel district with a cosmopolitan vibe. Perfect for visitors who want easy access to dining, shopping, and nightlife.</p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• 2 miles from Old San Juan</li>
                    <li>• Luxury hotels & resorts</li>
                    <li>• Lagoon-side restaurants</li>
                    <li>• Walkable to attractions</li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow">
                <div class="text-4xl mb-4">✈️</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Isla Verde</h3>
                <p class="text-gray-600 text-sm mb-3">The longest beach stretch closest to the airport. Great for longer beach days with a mix of tourists and locals.</p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• 10 min from airport</li>
                    <li>• 1.5 miles of beachfront</li>
                    <li>• Water sports rentals</li>
                    <li>• Balneario facilities</li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow">
                <div class="text-4xl mb-4">🌊</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Ocean Park</h3>
                <p class="text-gray-600 text-sm mb-3">A trendy, bohemian neighborhood popular with locals, kitesurfers, and young travelers seeking authentic vibes.</p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• Hip, local atmosphere</li>
                    <li>• Kitesurf paradise</li>
                    <li>• Boutique guesthouses</li>
                    <li>• Beachfront yoga</li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow">
                <div class="text-4xl mb-4">🤿</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Escambrón</h3>
                <p class="text-gray-600 text-sm mb-3">A protected cove next to historic forts. The best beach for families and snorkeling with calm, crystal-clear waters.</p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• Closest to Old San Juan</li>
                    <li>• Protected reef system</li>
                    <li>• Excellent snorkeling</li>
                    <li>• Family-friendly</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Getting to San Juan Beaches -->
<section id="getting-there" class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Getting to San Juan Beaches
        </h2>

        <div class="space-y-6">
            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-lg font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <span>🚕</span> By Taxi or Uber
                </h3>
                <p class="text-gray-700 mb-3">
                    The easiest way to reach San Juan beaches. Rides are quick and affordable due to short distances:
                </p>
                <ul class="text-gray-700 space-y-2 ml-4">
                    <li><strong>Old San Juan to Escambrón:</strong> $5-8 (5 minutes)</li>
                    <li><strong>Old San Juan to Condado:</strong> $8-12 (10 minutes)</li>
                    <li><strong>Old San Juan to Isla Verde:</strong> $15-20 (15 minutes)</li>
                    <li><strong>Airport to Isla Verde:</strong> $8-12 (10 minutes)</li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-lg font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <span>🚌</span> By Public Bus
                </h3>
                <p class="text-gray-700 mb-3">
                    The <strong>T5 bus route</strong> connects Old San Juan with all major beach neighborhoods for just $0.75. Buses run every 30 minutes from 6am to 9pm.
                </p>
                <ul class="text-gray-700 space-y-2 ml-4">
                    <li><strong>Route:</strong> Old San Juan → Condado → Ocean Park → Isla Verde → Airport</li>
                    <li><strong>Best for:</strong> Budget travelers and those without rental cars</li>
                    <li><strong>Travel time:</strong> 30-45 minutes end-to-end</li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-lg font-bold text-gray-900 mb-3 flex items-center gap-2">
                    <span>🚶</span> Walking Distances
                </h3>
                <p class="text-gray-700 mb-3">
                    Many San Juan beaches are walkable from hotels and attractions:
                </p>
                <ul class="text-gray-700 space-y-2 ml-4">
                    <li><strong>Old San Juan to Escambrón:</strong> 1.5 miles (30 minutes) along scenic waterfront</li>
                    <li><strong>Condado hotels to beach:</strong> 2-10 minutes depending on location</li>
                    <li><strong>Ocean Park to Condado:</strong> 1 mile (20 minutes) along beach path</li>
                    <li><strong>Isla Verde hotels to beach:</strong> Direct beach access from most resorts</li>
                </ul>
            </div>

            <div class="bg-purple-50 rounded-xl p-6 border border-purple-200">
                <h3 class="text-lg font-bold text-purple-900 mb-2 flex items-center gap-2">
                    <span>💡</span> Pro Tip
                </h3>
                <p class="text-purple-800">
                    Cruise ship passengers should take a taxi directly to Escambrón Beach (5 minutes, ~$8) or Condado Beach (10 minutes, ~$10) rather than walking with beach gear. Both beaches have facilities and are much better than walking around the cruise port area.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Related Pages -->
<section class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Explore More Puerto Rico Beaches
        </h2>

        <div class="grid md:grid-cols-3 gap-6">
            <a href="/best-beaches" class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group">
                <div class="text-4xl mb-4">🏝️</div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-purple-600">All Best Beaches</h3>
                <p class="text-gray-600 text-sm mt-2">Top 15 beaches across all of Puerto Rico</p>
            </a>

            <a href="/best-family-beaches" class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group">
                <div class="text-4xl mb-4">👨‍👩‍👧‍👦</div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-purple-600">Best for Families</h3>
                <p class="text-gray-600 text-sm mt-2">Calm waters, facilities, and kid-friendly amenities</p>
            </a>

            <a href="/best-snorkeling-beaches" class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group">
                <div class="text-4xl mb-4">🤿</div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-purple-600">Best for Snorkeling</h3>
                <p class="text-gray-600 text-sm mt-2">Crystal-clear waters and vibrant marine life</p>
            </a>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section id="faq" class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Frequently Asked Questions About San Juan Beaches
        </h2>

        <div class="space-y-4">
            <?php foreach ($pageFaqs as $faq): ?>
            <details class="bg-white rounded-lg shadow-md group">
                <summary class="flex items-center justify-between p-6 cursor-pointer font-semibold text-gray-900">
                    <?= h($faq['question']) ?>
                    <span class="text-purple-600 group-open:rotate-180 transition-transform">▼</span>
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
            Explore San Juan Beaches on the Map
        </h2>
        <div class="text-center">
            <a href="/?view=map&municipality=San+Juan" class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                <span>🗺️</span>
                <span>Open Interactive Map</span>
            </a>
            <p class="text-gray-600 mt-4">View all San Juan beaches with filters for activities, amenities, and more.</p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-12 bg-purple-600 text-white">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-4">Find Your Perfect San Juan Beach</h2>
        <p class="text-lg opacity-90 mb-6">Take our quick quiz and get personalized beach recommendations based on your travel style and preferences.</p>
        <a href="/quiz" class="inline-block bg-white text-purple-600 hover:bg-purple-50 px-8 py-3 rounded-lg font-semibold transition-colors">
            Take the Beach Match Quiz
        </a>
    </div>
</section>

<?php include __DIR__ . '/components/footer.php'; ?>
