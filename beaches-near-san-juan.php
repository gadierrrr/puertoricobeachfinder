<?php
/**
 * Beaches Near San Juan - SEO Landing Page
 * Target keywords: beaches near san juan, san juan beaches, beaches puerto rico capital
 */

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/constants.php';
require_once __DIR__ . '/components/seo-schemas.php';

// Page metadata
$pageTitle = 'Best Beaches Near San Juan, Puerto Rico (2025 Guide)';
$pageDescription = 'Discover the best beaches near San Juan, Puerto Rico. From Condado and Isla Verde to hidden local favorites, find the perfect beach just minutes from the capital.';
$canonicalUrl = ($_ENV['APP_URL'] ?? 'https://www.puertoricobeachfinder.com') . '/beaches-near-san-juan';

// San Juan coordinates for distance calculation
$sanJuanLat = 18.4655;
$sanJuanLng = -66.1057;

// Fetch beaches near San Juan (within ~30km radius)
$sanJuanBeaches = query("
    SELECT b.*,
           GROUP_CONCAT(DISTINCT bt.tag) as tag_list,
           GROUP_CONCAT(DISTINCT ba.amenity) as amenity_list,
           (6371 * acos(cos(radians(?)) * cos(radians(b.lat)) * cos(radians(b.lng) - radians(?)) + sin(radians(?)) * sin(radians(b.lat)))) AS distance
    FROM beaches b
    LEFT JOIN beach_tags bt ON b.id = bt.beach_id
    LEFT JOIN beach_amenities ba ON b.id = ba.beach_id
    WHERE b.publish_status = 'published'
    AND b.lat IS NOT NULL
    AND b.lng IS NOT NULL
    GROUP BY b.id
    HAVING distance < 30
    ORDER BY distance ASC
    LIMIT 15
", [$sanJuanLat, $sanJuanLng, $sanJuanLat]);

// Process tags and amenities
foreach ($sanJuanBeaches as &$beach) {
    $beach['tags'] = $beach['tag_list'] ? explode(',', $beach['tag_list']) : [];
    $beach['amenities'] = $beach['amenity_list'] ? explode(',', $beach['amenity_list']) : [];
}
unset($beach);

// Generate structured data
$extraHead = articleSchema(
    $pageTitle,
    $pageDescription,
    '/beaches-near-san-juan',
    $sanJuanBeaches[0]['cover_image'] ?? null,
    '2025-01-01'
);
$extraHead .= collectionPageSchema($pageTitle, $pageDescription, $sanJuanBeaches);
$extraHead .= websiteSchema();

// FAQ data
$pageFaqs = [
    [
        'question' => 'What is the closest beach to San Juan?',
        'answer' => 'Condado Beach is the closest major beach to Old San Juan, just a 10-minute drive or 20-minute walk from the cruise port. Isla Verde Beach is about 15 minutes from Old San Juan and offers a wider, more resort-lined stretch of sand.'
    ],
    [
        'question' => 'Can you walk to beaches from Old San Juan?',
        'answer' => 'While Old San Juan itself doesn\'t have sandy beaches (it\'s a historic walled city), you can walk to Escambron Beach in about 25-30 minutes along the coast. For Condado Beach, it\'s a 20-25 minute walk or quick Uber ride. Most visitors take a taxi or rideshare to the beach areas.'
    ],
    [
        'question' => 'Which San Juan beach is best for swimming?',
        'answer' => 'Condado Beach and Isla Verde Beach are both excellent for swimming with generally calm conditions. For the calmest waters, try the lagoon side of Condado or Balneario Escambron, which is protected by a natural reef. Ocean Park is popular but can have stronger currents.'
    ],
    [
        'question' => 'Are San Juan beaches free to access?',
        'answer' => 'Yes, all beaches in Puerto Rico are public and free to access. Some beach areas may charge for parking or chair/umbrella rentals, but the beach itself is always free. Hotel beaches are also public - you can use the sand and water even if not a hotel guest.'
    ],
    [
        'question' => 'What is the nicest beach in San Juan?',
        'answer' => 'Isla Verde Beach is often considered the nicest beach in the San Juan area, with its wide stretch of golden sand, clear waters, and backdrop of luxury hotels. Ocean Park Beach is favored by locals for its relaxed vibe, while Condado offers the best combination of beach and urban amenities.'
    ]
];
$extraHead .= faqSchema($pageFaqs);

include __DIR__ . '/components/header.php';
?>

<!-- Hero Section -->
<section class="relative w-full py-16 md:py-20 overflow-hidden">
    <!-- Dark background with overlay -->
    <div class="absolute inset-0 -z-10">
        <div class="w-full h-full hero-gradient-dark"></div>
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
    </div>

    <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <!-- ADD breadcrumbs -->
        <nav class="text-white/60 text-sm mb-6" aria-label="Breadcrumb">
            <a href="/" class="hover:text-brand-yellow transition-colors">Home</a>
            <span class="mx-2 text-white/40">/</span>
            <a href="/" class="hover:text-brand-yellow transition-colors">Beaches</a>
            <span class="mx-2 text-white/40">/</span>
            <span class="text-white/80">Near San Juan</span>
        </nav>

        <!-- Add explicit text-white -->
        <h1 class="text-3xl md:text-5xl font-bold text-white mb-6">
            Beaches Near San Juan, Puerto Rico
        </h1>

        <!-- Change opacity-90 to text-gray-200 -->
        <p class="text-lg md:text-xl text-gray-200 max-w-3xl mx-auto">
            Discover beautiful beaches just minutes from Puerto Rico's capital. From the urban shores of Condado to the resort paradise of Isla Verde.
        </p>

        <!-- Change opacity-75 to text-white/60 -->
        <p class="text-sm text-white/60 mt-4">Updated January 2025 | All beaches within 30 minutes of San Juan</p>
    </div>
</section>

<!-- Quick Navigation -->
<section class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex flex-wrap gap-2 justify-center text-sm">
            <span class="text-gray-500">Jump to:</span>
            <a href="#beaches" class="text-blue-600 hover:underline">Beach List</a>
            <span class="text-gray-300">|</span>
            <a href="#neighborhoods" class="text-blue-600 hover:underline">By Neighborhood</a>
            <span class="text-gray-300">|</span>
            <a href="#getting-there" class="text-blue-600 hover:underline">Getting There</a>
            <span class="text-gray-300">|</span>
            <a href="#faq" class="text-blue-600 hover:underline">FAQs</a>
        </div>
    </div>
</section>

<!-- Introduction -->
<section class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4">
        <div class="prose prose-lg max-w-none beach-description">
            <p>San Juan, Puerto Rico's vibrant capital, offers <strong>easy access to beautiful Caribbean beaches</strong> just minutes from the city center. Whether you're staying in the historic Old San Juan, the trendy Condado district, or near the airport in Isla Verde, you're never far from pristine shores.</p>

            <p>From urban beaches with waterfront restaurants to quiet coves perfect for snorkeling, the San Juan area has options for every type of beach lover. All beaches listed here are <strong>within 30 minutes</strong> of central San Juan.</p>
        </div>
    </div>
</section>

<!-- Beaches List -->
<section id="beaches" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Beaches Near San Juan
        </h2>

        <div class="space-y-8">
            <?php foreach ($sanJuanBeaches as $index => $beach): ?>
            <article class="bg-white rounded-xl shadow-md overflow-hidden md:flex">
                <div class="md:w-1/3 relative">
                    <?php if ($beach['cover_image']): ?>
                    <img src="<?= h($beach['cover_image']) ?>"
                         alt="<?= h($beach['name']) ?> near San Juan"
                         class="w-full h-48 md:h-full object-cover"
                         loading="<?= $index < 3 ? 'eager' : 'lazy' ?>">
                    <?php else: ?>
                    <div class="w-full h-48 md:h-full bg-gradient-to-br from-purple-400 to-indigo-600 flex items-center justify-center">
                        <span class="text-6xl">🏙️</span>
                    </div>
                    <?php endif; ?>
                    <div class="absolute top-4 left-4 bg-purple-600 text-white px-3 py-1 rounded-full font-bold text-sm">
                        <?= round($beach['distance'], 1) ?> km
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
                            <p class="text-gray-600"><?= h($beach['municipality']) ?> • ~<?= round($beach['distance'] * 2) ?> min drive</p>
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

<!-- By Neighborhood -->
<section id="neighborhoods" class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            San Juan Beach Neighborhoods
        </h2>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-lg font-bold text-gray-900 mb-3">Condado</h3>
                <p class="text-gray-600 text-sm mb-3">Upscale neighborhood with resorts, restaurants, and a long beach strip. Great for people-watching.</p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• Condado Beach</li>
                    <li>• La Ventana al Mar</li>
                    <li>• Playita del Condado</li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-lg font-bold text-gray-900 mb-3">Isla Verde</h3>
                <p class="text-gray-600 text-sm mb-3">Near the airport with the widest beaches in San Juan. Resort hotels and water sports.</p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• Isla Verde Beach</li>
                    <li>• Pine Grove Beach</li>
                    <li>• Balneario Carolina</li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-lg font-bold text-gray-900 mb-3">Ocean Park</h3>
                <p class="text-gray-600 text-sm mb-3">Trendy residential area popular with locals. Kite surfing and LGBT-friendly beaches.</p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• Ocean Park Beach</li>
                    <li>• Ultimo Trolley</li>
                    <li>• Parque Barbosa</li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-lg font-bold text-gray-900 mb-3">Puerta de Tierra</h3>
                <p class="text-gray-600 text-sm mb-3">Between Old San Juan and Condado. Protected beach perfect for families.</p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• Escambron Beach</li>
                    <li>• Playita Escambron</li>
                    <li>• Third Millennium Park</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Getting There -->
<section id="getting-there" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Getting to San Juan Beaches
        </h2>

        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🚕</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Taxi / Uber</h3>
                <p class="text-gray-600 text-sm">Most convenient option. Uber and local taxis are widely available. From Old San Juan to Condado: ~$12-15. To Isla Verde: ~$20-25.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🚌</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Public Bus (AMA)</h3>
                <p class="text-gray-600 text-sm">Bus T5 runs from Old San Juan through Condado, Ocean Park, and Isla Verde. Only $0.75 but can be slow. Limited on weekends.</p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🚶</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Walking</h3>
                <p class="text-gray-600 text-sm">Condado is walkable from Old San Juan (~25 min). A scenic coastal walk connects many beaches. Not ideal in hot midday sun.</p>
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
            San Juan Beaches: FAQs
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
            View San Juan Area Beaches
        </h2>
        <div class="text-center">
            <a href="/?view=map&near=san-juan" class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                <span>🗺️</span>
                <span>Open Map Near San Juan</span>
            </a>
            <p class="text-gray-600 mt-4">See all beaches within 30 minutes of San Juan on the interactive map.</p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-12 bg-purple-600 text-white">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-4">Planning a Day Trip from San Juan?</h2>
        <p class="text-lg opacity-90 mb-6">Take our quiz to find the perfect beach based on what you're looking for - whether it's snorkeling, surfing, or just relaxing.</p>
        <a href="/quiz.php" class="inline-block bg-white text-purple-600 hover:bg-purple-50 px-8 py-3 rounded-lg font-semibold transition-colors">
            Take the Beach Match Quiz
        </a>
    </div>
</section>

<?php include __DIR__ . '/components/footer.php'; ?>
