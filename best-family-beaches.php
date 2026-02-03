<?php
/**
 * Best Family Beaches in Puerto Rico - SEO Landing Page
 * Target keywords: family beaches puerto rico, kid-friendly beaches puerto rico
 */

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/constants.php';
require_once __DIR__ . '/components/seo-schemas.php';

// Page metadata
$pageTitle = 'Best Family Beaches in Puerto Rico (2025 Guide)';
$pageDescription = 'Discover the best family-friendly beaches in Puerto Rico for 2025. Calm waters, lifeguards, amenities, and fun for kids of all ages at these top family beach destinations.';
$canonicalUrl = ($_ENV['APP_URL'] ?? 'https://www.puertoricobeachfinder.com') . '/best-family-beaches';

// Fetch family-friendly beaches
$familyBeaches = query("
    SELECT b.*,
           GROUP_CONCAT(DISTINCT bt.tag) as tag_list,
           GROUP_CONCAT(DISTINCT ba.amenity) as amenity_list
    FROM beaches b
    LEFT JOIN beach_tags bt ON b.id = bt.beach_id
    LEFT JOIN beach_amenities ba ON b.id = ba.beach_id
    WHERE b.publish_status = 'published'
    AND EXISTS (SELECT 1 FROM beach_tags bt2 WHERE bt2.beach_id = b.id AND bt2.tag = 'family-friendly')
    GROUP BY b.id
    ORDER BY b.google_rating DESC, b.google_review_count DESC
    LIMIT 15
");

// Process tags and amenities
foreach ($familyBeaches as &$beach) {
    $beach['tags'] = $beach['tag_list'] ? explode(',', $beach['tag_list']) : [];
    $beach['amenities'] = $beach['amenity_list'] ? explode(',', $beach['amenity_list']) : [];
}
unset($beach);

// Generate structured data
$extraHead = articleSchema(
    $pageTitle,
    $pageDescription,
    '/best-family-beaches',
    $familyBeaches[0]['cover_image'] ?? null,
    '2025-01-01'
);
$extraHead .= collectionPageSchema($pageTitle, $pageDescription, $familyBeaches);
$extraHead .= websiteSchema();

// FAQ data
$pageFaqs = [
    [
        'question' => 'What is the best family beach in Puerto Rico?',
        'answer' => 'Luquillo Beach (Balneario La Monserrate) is widely considered the best family beach in Puerto Rico. It offers calm, shallow waters protected by a reef, lifeguards on duty, food kiosks, restrooms, showers, and ample parking. The beach has a gentle slope perfect for small children.'
    ],
    [
        'question' => 'Are Puerto Rico beaches safe for kids?',
        'answer' => 'Many Puerto Rico beaches are excellent for kids, especially designated "balnearios" (public beaches) which typically have lifeguards, facilities, and calmer waters. Always supervise children, check for posted warnings, and choose beaches known for calm conditions like Luquillo, Seven Seas, or Boqueron.'
    ],
    [
        'question' => 'Which Puerto Rico beaches have lifeguards?',
        'answer' => 'Most public beaches (balnearios) in Puerto Rico have lifeguards during peak hours. Popular beaches with lifeguards include Luquillo Beach, Condado Beach, Isla Verde Beach, Seven Seas Beach, Boqueron Beach, and Sun Bay in Vieques. Hours are typically 8:30 AM to 5:00 PM.'
    ],
    [
        'question' => 'What should I bring to a Puerto Rico beach with kids?',
        'answer' => 'Pack reef-safe sunscreen (SPF 50+), rash guards for sun protection, water shoes (some beaches have rocky areas), plenty of water, snacks, a beach tent or umbrella for shade, sand toys, and snorkeling gear for older kids. Many beaches have food vendors, but it\'s good to have supplies.'
    ],
    [
        'question' => 'Are there beaches with playgrounds in Puerto Rico?',
        'answer' => 'Several beaches have nearby playgrounds or picnic areas. Luquillo Beach has a playground near the food kiosks, and many balnearios have picnic pavilions and grassy areas for play. Condado Beach and Ocean Park have nearby parks and facilities popular with families.'
    ]
];
$extraHead .= faqSchema($pageFaqs);

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'Best Beaches', 'url' => '/best-beaches'],
    ['name' => 'Family Beaches']
];

include __DIR__ . '/components/header.php';
?>

<!-- Hero Section -->
<?php
$heroSubtext = 'Updated January 2025 | 89+ family beaches reviewed';
include __DIR__ . '/components/hero-collection.php';
?>
</section>

<!-- Quick Navigation -->
<section class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex flex-wrap gap-2 justify-center text-sm">
            <span class="text-gray-500">Jump to:</span>
            <a href="#top-beaches" class="text-blue-600 hover:underline">Top Beaches</a>
            <span class="text-gray-300">|</span>
            <a href="#tips" class="text-blue-600 hover:underline">Family Tips</a>
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
            <p>Puerto Rico is a <strong>fantastic family vacation destination</strong> with beaches perfect for children of all ages. The island's balnearios (public beaches) offer supervised swimming areas, restrooms, and food vendors, making beach days hassle-free for parents.</p>

            <p>From the famous food kiosks at Luquillo to the calm crescent of Seven Seas Beach, these family-friendly shores combine safety, convenience, and natural beauty for the ultimate Caribbean family getaway.</p>
        </div>
    </div>
</section>

<!-- Top Family Beaches List -->
<section id="top-beaches" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Top Family-Friendly Beaches in Puerto Rico
        </h2>

        <div class="space-y-8">
            <?php foreach ($familyBeaches as $index => $beach): ?>
            <article class="bg-white rounded-xl shadow-md overflow-hidden md:flex">
                <div class="md:w-1/3 relative">
                    <?php if ($beach['cover_image']): ?>
                    <img src="<?= h($beach['cover_image']) ?>"
                         alt="Family fun at <?= h($beach['name']) ?>"
                         class="w-full h-48 md:h-full object-cover"
                         loading="<?= $index < 3 ? 'eager' : 'lazy' ?>">
                    <?php else: ?>
                    <div class="w-full h-48 md:h-full bg-gradient-to-br from-green-400 to-teal-600 flex items-center justify-center">
                        <span class="text-6xl">👨‍👩‍👧‍👦</span>
                    </div>
                    <?php endif; ?>
                    <div class="absolute top-4 left-4 bg-green-600 text-white px-3 py-1 rounded-full font-bold">
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

                    <?php if (!empty($beach['amenities'])): ?>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <?php
                        $familyAmenities = ['restrooms', 'showers', 'parking', 'lifeguard', 'food'];
                        $displayAmenities = array_intersect($beach['amenities'], $familyAmenities);
                        foreach (array_slice($displayAmenities, 0, 5) as $amenity): ?>
                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">
                            <?= h(ucfirst($amenity)) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="flex gap-3">
                        <a href="/beach/<?= h($beach['slug']) ?>"
                           class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
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

<!-- Family Beach Tips -->
<section id="tips" class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Tips for Beach Days with Kids
        </h2>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🕘</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Arrive Early</h3>
                <p class="text-gray-600 text-sm">Get to the beach by 9 AM for the best parking, calmer waters, and prime shade spots. Beaches get busier after 11 AM on weekends.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🧴</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Sun Protection</h3>
                <p class="text-gray-600 text-sm">Apply reef-safe SPF 50+ sunscreen 30 minutes before the beach. Reapply every 2 hours. Rash guards provide excellent UV protection.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">👟</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Water Shoes</h3>
                <p class="text-gray-600 text-sm">Some beaches have rocky entries or sea urchins. Water shoes protect little feet and make entering the water easier and safer.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">⛱️</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Bring Shade</h3>
                <p class="text-gray-600 text-sm">Pack a beach tent, umbrella, or canopy. Natural shade is limited at many beaches, and kids need breaks from direct sun.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🥤</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Stay Hydrated</h3>
                <p class="text-gray-600 text-sm">Bring plenty of water and healthy snacks. While many beaches have vendors, having your own supply ensures you're prepared.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🏊</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Water Safety</h3>
                <p class="text-gray-600 text-sm">Always supervise children in the water. Use floaties for non-swimmers and check for posted warnings about currents.</p>
            </div>
        </div>
    </div>
</section>

<!-- What to Look For -->
<section class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            What Makes a Beach Family-Friendly?
        </h2>

        <div class="grid md:grid-cols-2 gap-8">
            <div class="bg-blue-50 rounded-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Must-Have Features</h3>
                <ul class="text-gray-700 space-y-3">
                    <li class="flex items-start gap-2">
                        <span class="text-green-500">✓</span>
                        <span><strong>Calm, shallow waters</strong> - Gentle waves for safe swimming</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-green-500">✓</span>
                        <span><strong>Lifeguards</strong> - Extra eyes for peace of mind</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-green-500">✓</span>
                        <span><strong>Restrooms & showers</strong> - Essential for families</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-green-500">✓</span>
                        <span><strong>Parking nearby</strong> - Easy access with beach gear</span>
                    </li>
                </ul>
            </div>

            <div class="bg-green-50 rounded-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Nice-to-Have Features</h3>
                <ul class="text-gray-700 space-y-3">
                    <li class="flex items-start gap-2">
                        <span class="text-blue-500">★</span>
                        <span><strong>Food vendors</strong> - Convenient meals and snacks</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-blue-500">★</span>
                        <span><strong>Picnic areas</strong> - Shaded spots for lunch</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-blue-500">★</span>
                        <span><strong>Equipment rentals</strong> - Chairs, umbrellas, snorkel gear</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-blue-500">★</span>
                        <span><strong>Playground nearby</strong> - Extra entertainment for kids</span>
                    </li>
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

            <a href="/beaches-near-san-juan" class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group">
                <div class="text-4xl mb-4">🏙️</div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600">Near San Juan</h3>
                <p class="text-gray-600 text-sm mt-2">Easy access from the capital</p>
            </a>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section id="faq" class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Family Beach FAQs
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
            Find Family Beaches on the Map
        </h2>
        <div class="text-center">
            <a href="/?view=map&activity=family-friendly" class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                <span>🗺️</span>
                <span>View Family Beaches Map</span>
            </a>
            <p class="text-gray-600 mt-4">Filter the interactive map to show all family-friendly beaches.</p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-12 bg-green-600 text-white">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-4">Find the Perfect Beach for Your Family</h2>
        <p class="text-lg opacity-90 mb-6">Tell us about your family's preferences and get personalized beach recommendations.</p>
        <a href="/quiz.php" class="inline-block bg-white text-green-600 hover:bg-green-50 px-8 py-3 rounded-lg font-semibold transition-colors">
            Take the Beach Match Quiz
        </a>
    </div>
</section>

<?php include __DIR__ . '/components/footer.php'; ?>
