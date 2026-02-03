<?php
/**
 * Hidden Beaches in Puerto Rico - SEO Landing Page
 * Target keywords: hidden beaches puerto rico, secret beaches puerto rico
 */

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/constants.php';
require_once __DIR__ . '/components/seo-schemas.php';

// Page metadata
$pageTitle = '15 Hidden Beaches in Puerto Rico (Secret Gems 2025)';
$pageDescription = 'Discover 15 secret and hidden beaches in Puerto Rico. Off-the-beaten-path paradise spots, secluded coves, and remote island destinations for adventure seekers. Includes access guides and coordinates.';
$canonicalUrl = ($_ENV['APP_URL'] ?? 'https://www.puertoricobeachfinder.com') . '/hidden-beaches-puerto-rico';

// Fetch hidden beaches - secluded/remote beaches with lower review counts
$hiddenBeaches = query("
    SELECT b.*,
           GROUP_CONCAT(DISTINCT bt.tag) as tag_list,
           GROUP_CONCAT(DISTINCT ba.amenity) as amenity_list
    FROM beaches b
    LEFT JOIN beach_tags bt ON b.id = bt.beach_id
    LEFT JOIN beach_amenities ba ON b.id = ba.beach_id
    WHERE b.publish_status = 'published'
    AND (
        b.id IN (SELECT beach_id FROM beach_tags WHERE tag IN ('secluded', 'remote', 'wild'))
        OR b.google_review_count < 200
    )
    GROUP BY b.id
    HAVING tag_list LIKE '%secluded%' OR tag_list LIKE '%remote%' OR tag_list LIKE '%wild%'
    ORDER BY b.google_rating DESC, b.google_review_count ASC
    LIMIT 15
");

// Process tags and amenities
foreach ($hiddenBeaches as &$beach) {
    $beach['tags'] = $beach['tag_list'] ? explode(',', $beach['tag_list']) : [];
    $beach['amenities'] = $beach['amenity_list'] ? explode(',', $beach['amenity_list']) : [];
}
unset($beach);

// Generate structured data
$extraHead = articleSchema(
    $pageTitle,
    $pageDescription,
    '/hidden-beaches-puerto-rico',
    $hiddenBeaches[0]['cover_image'] ?? null,
    '2025-01-15'
);
$extraHead .= collectionPageSchema($pageTitle, $pageDescription, $hiddenBeaches);
$extraHead .= websiteSchema();

// FAQ data
$pageFaqs = [
    [
        'question' => 'What makes a beach "hidden" in Puerto Rico?',
        'answer' => 'A hidden beach in Puerto Rico typically has limited accessibility (requiring boat access, hiking, or 4WD vehicles), few or no facilities, low visitor numbers, and minimal commercial development. Many hidden beaches have fewer than 200 Google reviews, indicating they remain relatively unknown to tourists. These secluded spots offer pristine natural beauty and a more authentic, crowd-free beach experience.'
    ],
    [
        'question' => 'Do I need a 4WD vehicle to reach hidden beaches?',
        'answer' => 'Some hidden beaches require 4WD vehicles due to rough, unpaved roads with potholes and steep inclines. Beaches like Playa Resaca in Culebra and certain spots in Vieques are best accessed with high-clearance vehicles. However, many secret beaches can be reached by boat, kayak, or short hikes. Always check access requirements before visiting and consider renting a 4WD SUV if planning to explore multiple remote beaches.'
    ],
    [
        'question' => 'Are hidden beaches safe to visit?',
        'answer' => 'Hidden beaches typically lack lifeguards, facilities, and emergency services, so visitors must be self-sufficient and take extra safety precautions. Always check weather conditions before visiting, bring plenty of water and supplies, inform someone of your plans, avoid swimming alone in rough conditions, and be prepared for limited or no cell phone service. These beaches are safe for experienced beachgoers who respect the ocean and come prepared.'
    ],
    [
        'question' => 'Can I camp at hidden beaches in Puerto Rico?',
        'answer' => 'Camping is generally prohibited on most Puerto Rico beaches without permits. However, some areas like Culebra and Vieques have designated camping areas near beaches. Always check local regulations before planning to camp. For true remote beach camping experiences, consider booking official campsites at Flamenco Beach in Culebra or through the US Fish and Wildlife Service for certain Vieques beaches.'
    ],
    [
        'question' => 'What should I bring to a beach with no facilities?',
        'answer' => 'For hidden beaches without facilities, bring: plenty of drinking water (1 gallon per person), snacks and food, sunscreen and sun protection, first aid kit, trash bags (pack out everything), toilet paper and trowel, snorkeling gear if applicable, waterproof phone case, portable phone charger, cash for parking or boat operators, and a dry bag for valuables. Also bring reef-safe sunscreen to protect coral ecosystems.'
    ],
    [
        'question' => 'How do I find hidden beaches in Puerto Rico?',
        'answer' => 'To discover hidden beaches: research online beach databases and local forums, ask locals at nearby beaches or towns, explore coastal roads and look for unmarked beach access points, use satellite maps to identify secluded coves, hire local guides who know secret spots, visit smaller islands like Culebra and Vieques, and explore beaches with low Google review counts. Always respect private property and follow Leave No Trace principles.'
    ],
    [
        'question' => 'What is the best time to visit hidden beaches?',
        'answer' => 'The best time to visit hidden beaches in Puerto Rico is during the dry season from December to April when seas are calmer and weather is more predictable. For the absolute best experience, visit on weekdays during off-peak hours (early morning or late afternoon) to avoid crowds. Check tides and weather forecasts before visiting remote locations, and avoid hurricane season (June-November) for boat-access beaches.'
    ],
    [
        'question' => 'Are there hidden beaches accessible without a boat?',
        'answer' => 'Yes, many hidden beaches are accessible by foot or car without requiring boat transport. Examples include Wilderness Beach in Aguadilla (short walk), Pastillo Beach in Isabela (roadside access), Guaniquilla Reserve Shore in Cabo Rojo (walking trail), and various secluded coves in Vieques accessible by rental car. These beaches remain "hidden" due to limited signage, rough access roads, or being overshadowed by more famous nearby beaches.'
    ]
];
$extraHead .= faqSchema($pageFaqs);

// HowTo Schema for finding hidden beaches
$howToSteps = [
    [
        'name' => 'Research online beach databases',
        'text' => 'Use Puerto Rico beach finder websites and filter for beaches with low review counts (under 200 reviews) and tags like "secluded" or "remote". Look for beaches with limited photos or information, as these are often less visited.'
    ],
    [
        'name' => 'Check satellite maps',
        'text' => 'Use Google Maps satellite view to scan coastlines for isolated coves, beaches with no nearby roads, or coastal areas without commercial development. Look for beaches accessible only by trails or boat.'
    ],
    [
        'name' => 'Ask local residents',
        'text' => 'Visit local surf shops, dive shops, or beach towns and ask residents about their favorite secret spots. Locals often know hidden beaches that do not appear in tourist guides. Be respectful and follow their advice about access and conservation.'
    ],
    [
        'name' => 'Explore smaller islands',
        'text' => 'Visit Culebra, Vieques, or take boat trips to offshore cays like Cayo Enrique, Isla Culebrita, or Caja de Muertos. These islands have numerous secluded beaches with limited development and fewer visitors than the main island.'
    ],
    [
        'name' => 'Follow Leave No Trace principles',
        'text' => 'When visiting hidden beaches, pack out all trash, avoid disturbing wildlife, stay on designated trails, use reef-safe sunscreen, and minimize your impact. Help keep these secret spots pristine for future visitors by practicing responsible tourism.'
    ]
];
$extraHead .= howToSchema(
    'How to Find Hidden Beaches in Puerto Rico',
    'A comprehensive guide to discovering secret and secluded beaches across Puerto Rico',
    $howToSteps
);

// Breadcrumbs
$breadcrumbs = [
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'Hidden Beaches in Puerto Rico']
];

include __DIR__ . '/components/header.php';

// Override page title and description for hero component
$pageTitle = '15 Hidden Beaches in Puerto Rico';
$pageDescription = 'Escape the crowds and discover Puerto Rico\'s best-kept secrets. From remote island cays to secluded mainland coves, these hidden gems offer pristine beauty and authentic Caribbean adventure.';

// Include hero component
include __DIR__ . '/components/hero-guide.php';
?>

<!-- Quick Navigation -->
<section class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex flex-wrap gap-2 justify-center text-sm">
            <span class="text-gray-500">Jump to:</span>
            <a href="#hidden-beaches" class="text-teal-600 hover:underline">Top 15 Secret Beaches</a>
            <span class="text-gray-300">|</span>
            <a href="#by-region" class="text-teal-600 hover:underline">By Region</a>
            <span class="text-gray-300">|</span>
            <a href="#access-guide" class="text-teal-600 hover:underline">Access Guide</a>
            <span class="text-gray-300">|</span>
            <a href="#what-to-bring" class="text-teal-600 hover:underline">What to Bring</a>
            <span class="text-gray-300">|</span>
            <a href="#faq" class="text-teal-600 hover:underline">FAQs</a>
        </div>
    </div>
</section>

<!-- Introduction -->
<section class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4">
        <div class="prose prose-lg max-w-none beach-description">
            <p>While Puerto Rico's famous beaches like Flamenco and Luquillo draw thousands of visitors daily, the island harbors dozens of <strong>secret beaches</strong> that remain blissfully uncrowded. These hidden gems offer something increasingly rare in the Caribbean: <strong>authentic solitude</strong> and pristine natural beauty.</p>

            <p>What makes a beach truly "hidden"? It's not just about being hard to find. The best secret beaches in Puerto Rico share several characteristics: <strong>limited accessibility</strong> (requiring boats, 4WD vehicles, or hiking), <strong>minimal facilities</strong> (no food kiosks or beach chair rentals), <strong>low visitor numbers</strong> (typically under 200 Google reviews), and <strong>preserved natural landscapes</strong> free from commercial development.</p>

            <p>These secluded spots demand more from visitors than popular beaches. You'll need to be <strong>self-sufficient</strong>, bringing your own water, food, and supplies. You'll navigate <strong>rough roads</strong> or hire boat operators. You might hike through <strong>coastal trails</strong> or kayak across <strong>turquoise channels</strong>. But the reward is extraordinary: powder-soft sand unmarred by footprints, crystalline waters teeming with marine life, and the rare privilege of having a Caribbean paradise practically to yourself.</p>

            <p>This guide reveals 15 of Puerto Rico's most spectacular hidden beaches, from <strong>offshore cays</strong> like Cayo Enrique to <strong>remote mainland coves</strong> in Isabela and Cabo Rojo. We'll share <strong>access instructions</strong>, <strong>coordinates</strong>, and <strong>essential tips</strong> for visiting these secret spots responsibly. Remember: these beaches remain pristine because visitors respect them. Always practice <strong>Leave No Trace principles</strong>, pack out all trash, and help preserve these natural treasures for future adventurers.</p>
        </div>
    </div>
</section>

<!-- Hidden Beaches List -->
<section id="hidden-beaches" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            15 Secret Beaches in Puerto Rico for 2025
        </h2>

        <div class="space-y-8">
            <?php foreach ($hiddenBeaches as $index => $beach): ?>
            <article class="bg-white rounded-xl shadow-md overflow-hidden md:flex">
                <div class="md:w-1/3 relative">
                    <?php if ($beach['cover_image']): ?>
                    <img src="<?= h($beach['cover_image']) ?>"
                         alt="<?= h($beach['name']) ?>"
                         class="w-full h-48 md:h-full object-cover"
                         loading="<?= $index < 3 ? 'eager' : 'lazy' ?>">
                    <?php else: ?>
                    <div class="w-full h-48 md:h-full bg-gradient-to-br from-teal-400 to-cyan-600 flex items-center justify-center">
                        <span class="text-6xl">🏝️</span>
                    </div>
                    <?php endif; ?>
                    <div class="absolute top-4 left-4 bg-teal-600 text-white px-3 py-1 rounded-full font-bold">
                        #<?= $index + 1 ?>
                    </div>
                    <?php if ($beach['google_review_count'] && $beach['google_review_count'] < 100): ?>
                    <div class="absolute top-4 right-4 bg-yellow-500 text-white px-3 py-1 rounded-full text-xs font-semibold">
                        Ultra Secret
                    </div>
                    <?php endif; ?>
                </div>
                <div class="md:w-2/3 p-6">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">
                                <a href="/beach/<?= h($beach['slug']) ?>" class="hover:text-teal-600">
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
                        <?php foreach (array_slice($beach['tags'], 0, 5) as $tag): ?>
                        <span class="bg-teal-100 text-teal-800 text-xs px-2 py-1 rounded">
                            <?= h(getTagLabel($tag)) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (empty($beach['amenities']) || count($beach['amenities']) < 2): ?>
                    <div class="bg-amber-50 border-l-4 border-amber-400 p-3 mb-4">
                        <p class="text-sm text-amber-800">
                            <strong>⚠️ No facilities:</strong> Bring water, food, and supplies. No restrooms or lifeguards.
                        </p>
                    </div>
                    <?php endif; ?>

                    <div class="flex gap-3">
                        <a href="/beach/<?= h($beach['slug']) ?>"
                           class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
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

<!-- Hidden Beaches by Region -->
<section id="by-region" class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Hidden Beaches by Region
        </h2>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-4xl mb-4">🌊</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Northwest Coast</h3>
                <p class="text-gray-600 text-sm mb-4">Aguadilla, Isabela, Quebradillas - dramatic cliffs and surf spots</p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• Wilderness Beach (Aguadilla)</li>
                    <li>• Pastillo Beach (Isabela)</li>
                    <li>• Cueva de las Golondrinas (Isabela)</li>
                    <li>• Túnel de Guajataca (Quebradillas)</li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-4xl mb-4">🏝️</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">East Coast & Islands</h3>
                <p class="text-gray-600 text-sm mb-4">Culebra, Vieques, Fajardo - pristine offshore cays</p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• Cayo Enrique (boat only)</li>
                    <li>• Isla Culebrita - Tortuga Beach</li>
                    <li>• Playa Resaca (Culebra)</li>
                    <li>• Carlos Rosario Beach</li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-4xl mb-4">🌅</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">South Coast</h3>
                <p class="text-gray-600 text-sm mb-4">Cabo Rojo, Lajas, Ponce - remote reserves and cays</p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• Guaniquilla Reserve Shore</li>
                    <li>• Pitahaya Cove (Cabo Rojo)</li>
                    <li>• Playa Pelícano (Caja de Muertos)</li>
                    <li>• Isla de Ratones</li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-4xl mb-4">🐚</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Vieques Secret Spots</h3>
                <p class="text-gray-600 text-sm mb-4">Former Navy lands with pristine beaches</p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• Pata Prieta (Secret Beach)</li>
                    <li>• La Plata / Platita</li>
                    <li>• Boca Quebrada</li>
                    <li>• Multiple unnamed coves</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Access Difficulty Guide -->
<section id="access-guide" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Access Difficulty Guide
        </h2>

        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-green-50 border-2 border-green-200 rounded-xl p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="bg-green-500 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold">
                        1
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Easy Access</h3>
                </div>
                <p class="text-gray-700 text-sm mb-4">Regular car accessible, short walk from parking</p>
                <ul class="text-sm text-gray-700 space-y-2">
                    <li class="flex items-start gap-2">
                        <span class="text-green-600 font-bold">✓</span>
                        <span>Paved or well-maintained roads</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-green-600 font-bold">✓</span>
                        <span>Parking within 5-minute walk</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-green-600 font-bold">✓</span>
                        <span>Some basic signage</span>
                    </li>
                </ul>
                <p class="text-xs text-gray-600 mt-4 italic">Examples: Wilderness Beach, Pastillo Beach</p>
            </div>

            <div class="bg-yellow-50 border-2 border-yellow-200 rounded-xl p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="bg-yellow-500 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold">
                        2
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Moderate</h3>
                </div>
                <p class="text-gray-700 text-sm mb-4">4WD recommended, rough roads or short hikes</p>
                <ul class="text-sm text-gray-700 space-y-2">
                    <li class="flex items-start gap-2">
                        <span class="text-yellow-600 font-bold">!</span>
                        <span>Unpaved roads with potholes</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-yellow-600 font-bold">!</span>
                        <span>High clearance helpful</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-yellow-600 font-bold">!</span>
                        <span>15-30 minute hike possible</span>
                    </li>
                </ul>
                <p class="text-xs text-gray-600 mt-4 italic">Examples: Playa Resaca, Guaniquilla Reserve</p>
            </div>

            <div class="bg-red-50 border-2 border-red-200 rounded-xl p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="bg-red-500 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold">
                        3
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Difficult</h3>
                </div>
                <p class="text-gray-700 text-sm mb-4">Boat access only or challenging hikes</p>
                <ul class="text-sm text-gray-700 space-y-2">
                    <li class="flex items-start gap-2">
                        <span class="text-red-600 font-bold">⚠</span>
                        <span>Requires boat or kayak</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-red-600 font-bold">⚠</span>
                        <span>Long hikes (30+ minutes)</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-red-600 font-bold">⚠</span>
                        <span>No road access whatsoever</span>
                    </li>
                </ul>
                <p class="text-xs text-gray-600 mt-4 italic">Examples: Cayo Enrique, Isla Culebrita, Cayo Diablo</p>
            </div>
        </div>
    </div>
</section>

<!-- What to Bring -->
<section id="what-to-bring" class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Essential Packing List for Hidden Beaches
        </h2>

        <div class="bg-white rounded-xl shadow-md p-8">
            <p class="text-gray-700 mb-6">
                Hidden beaches rarely have facilities. Being prepared ensures a safe and enjoyable adventure. Here's what to pack:
            </p>

            <div class="grid md:grid-cols-2 gap-8">
                <div>
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="text-2xl">💧</span> Hydration & Food
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span><strong>1 gallon of water per person</strong> (more if hiking in heat)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span>Electrolyte drinks or coconut water</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span>Snacks and sandwiches in sealed containers</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span>Cooler with ice (if car accessible)</span>
                        </li>
                    </ul>

                    <h3 class="font-bold text-gray-900 mb-4 mt-6 flex items-center gap-2">
                        <span class="text-2xl">☀️</span> Sun & Weather Protection
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span><strong>Reef-safe sunscreen</strong> (SPF 50+)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span>Wide-brimmed hat and sunglasses</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span>Lightweight long-sleeve shirt (rash guard)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span>Beach umbrella or pop-up shade tent</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span>Rain jacket (Caribbean weather changes fast)</span>
                        </li>
                    </ul>

                    <h3 class="font-bold text-gray-900 mb-4 mt-6 flex items-center gap-2">
                        <span class="text-2xl">🏊</span> Beach Gear
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span>Snorkeling gear (mask, snorkel, fins)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span>Water shoes (rocky entries common)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span>Beach towels and blankets</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span>Waterproof dry bag for valuables</span>
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="text-2xl">🎒</span> Safety & Navigation
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span><strong>First aid kit</strong> with bandages, antiseptic, pain relievers</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span>Portable phone charger (power bank)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span>Waterproof phone case</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span>GPS coordinates downloaded offline</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span>Whistle (emergency signaling)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span>Flashlight or headlamp (if staying late)</span>
                        </li>
                    </ul>

                    <h3 class="font-bold text-gray-900 mb-4 mt-6 flex items-center gap-2">
                        <span class="text-2xl">♻️</span> Leave No Trace Essentials
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span><strong>Trash bags</strong> (pack out EVERYTHING)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span>Toilet paper and trowel (for emergencies)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span>Hand sanitizer and wet wipes</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span>Reusable water bottles (avoid single-use plastic)</span>
                        </li>
                    </ul>

                    <h3 class="font-bold text-gray-900 mb-4 mt-6 flex items-center gap-2">
                        <span class="text-2xl">💵</span> Money & Documents
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span><strong>Cash</strong> for parking, boat operators, tips</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span>Copy of ID (keep original in car)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-teal-600">✓</span>
                            <span>Emergency contact info written down</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-8 p-4 bg-teal-50 border-l-4 border-teal-500 rounded">
                <p class="text-sm text-gray-800">
                    <strong>Pro Tip:</strong> Create a waterproof checklist on your phone and check items off before leaving your car. It's easy to forget essentials when excited about reaching a secret beach. Always tell someone your plans and expected return time when visiting remote locations.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Responsible Tourism Tips -->
<section class="py-12 bg-gradient-to-br from-green-50 to-teal-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Responsible Tourism: Protect What Makes These Beaches Special
        </h2>

        <div class="bg-white rounded-xl shadow-md p-8">
            <p class="text-gray-700 mb-6">
                Hidden beaches remain pristine because visitors treat them with respect. Follow these principles to help preserve Puerto Rico's secret coastal treasures:
            </p>

            <div class="space-y-6">
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-xl">
                        🚯
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-2">Leave No Trace</h3>
                        <p class="text-gray-700 text-sm">
                            Pack out everything you bring in, including organic waste like fruit peels and food scraps. Even biodegradable items can attract pests and disrupt ecosystems. Pick up any litter you find, even if it's not yours. Leave the beach cleaner than you found it.
                        </p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-xl">
                        🐠
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-2">Protect Marine Life</h3>
                        <p class="text-gray-700 text-sm">
                            Use only reef-safe sunscreen (mineral-based with zinc oxide or titanium dioxide). Never touch, stand on, or remove coral. Don't feed fish or chase marine animals. Observe sea turtles from at least 10 feet away. Avoid swimming in seagrass beds where possible.
                        </p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center text-xl">
                        🥾
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-2">Stay on Designated Trails</h3>
                        <p class="text-gray-700 text-sm">
                            Stick to established paths when hiking to beaches. Trampling vegetation causes erosion and destroys native plant habitats. Don't create shortcuts. Avoid walking on sand dunes, which protect coastlines from erosion and provide habitat for nesting birds.
                        </p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center text-xl">
                        🤝
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-2">Respect Local Communities</h3>
                        <p class="text-gray-700 text-sm">
                            Many hidden beaches are near small communities. Respect private property, don't block driveways, and be mindful of noise levels. Support local boat operators and guides rather than attempting dangerous access yourself. Ask permission before photographing locals.
                        </p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-red-100 rounded-full flex items-center justify-center text-xl">
                        🔇
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-2">Keep It Quiet</h3>
                        <p class="text-gray-700 text-sm">
                            One reason these beaches feel magical is their tranquility. Avoid loud music, shouting, or rowdy behavior. Let others enjoy the natural soundscape of waves and birds. Consider visiting during off-peak times to spread out visitor impact.
                        </p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center text-xl">
                        🤐
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-2">Share Responsibly</h3>
                        <p class="text-gray-700 text-sm">
                            When sharing photos on social media, consider not geotagging the exact location of the most fragile or ultra-secret beaches. Overtourism can quickly degrade pristine environments. Encourage responsible behavior in your posts and emphasize Leave No Trace principles.
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-8 p-4 bg-green-50 border-l-4 border-green-500 rounded">
                <p class="text-sm text-gray-800">
                    <strong>Remember:</strong> These hidden beaches are Puerto Rico's natural heritage. By practicing responsible tourism, you help ensure they remain pristine for future generations. If a beach becomes too crowded or degraded, it loses what made it special in the first place.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Related Pages -->
<section class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            More Beach Guides
        </h2>

        <div class="grid md:grid-cols-3 gap-6">
            <a href="/best-beaches" class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group">
                <div class="text-4xl mb-4">⭐</div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600 mb-2">Best Beaches in Puerto Rico</h3>
                <p class="text-gray-600 text-sm">Top-rated beaches with world-class amenities and stunning beauty</p>
            </a>

            <a href="/best-snorkeling-beaches" class="bg-gradient-to-br from-cyan-50 to-cyan-100 rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group">
                <div class="text-4xl mb-4">🤿</div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-cyan-600 mb-2">Best Snorkeling Beaches</h3>
                <p class="text-gray-600 text-sm">Crystal-clear waters with vibrant coral reefs and tropical fish</p>
            </a>

            <a href="/best-family-beaches" class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow group">
                <div class="text-4xl mb-4">👨‍👩‍👧‍👦</div>
                <h3 class="text-lg font-bold text-gray-900 group-hover:text-green-600 mb-2">Best Family Beaches</h3>
                <p class="text-gray-600 text-sm">Safe, shallow waters with facilities perfect for kids</p>
            </a>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section id="faq" class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Frequently Asked Questions
        </h2>

        <div class="space-y-4">
            <?php foreach ($pageFaqs as $faq): ?>
            <details class="bg-white rounded-lg shadow-md group">
                <summary class="flex items-center justify-between p-6 cursor-pointer font-semibold text-gray-900">
                    <?= h($faq['question']) ?>
                    <span class="text-teal-600 group-open:rotate-180 transition-transform">▼</span>
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
<section id="map" class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            Explore Hidden Beaches on the Map
        </h2>
        <div class="text-center">
            <a href="/?view=map&tags=secluded" class="inline-flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                <span>🗺️</span>
                <span>View Secluded Beaches on Map</span>
            </a>
            <p class="text-gray-600 mt-4">Filter by secluded beaches to discover more hidden gems across Puerto Rico.</p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-12 bg-gradient-to-br from-teal-600 via-cyan-600 to-blue-600 text-white">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-4">Find Your Perfect Beach Adventure</h2>
        <p class="text-lg opacity-90 mb-6">Not sure which hidden beach matches your adventure level? Take our quick quiz for personalized recommendations.</p>
        <a href="/quiz.php" class="inline-block bg-white text-teal-600 hover:bg-gray-100 px-8 py-3 rounded-lg font-semibold transition-colors">
            Take the Beach Match Quiz
        </a>
    </div>
</section>

<?php include __DIR__ . '/components/footer.php'; ?>
