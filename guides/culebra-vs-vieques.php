<?php
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../components/seo-schemas.php';

$pageTitle = 'Culebra vs Vieques: Which Puerto Rico Island to Visit?';
$pageDescription = 'Side-by-side comparison of Culebra and Vieques including beaches, transportation, activities, accommodation, and costs to help you choose the perfect island.';

$culebra_beaches = query("SELECT id, name, slug FROM beaches WHERE municipality = 'Culebra' LIMIT 3");
$vieques_beaches = query("SELECT id, name, slug FROM beaches WHERE municipality = 'Vieques' LIMIT 3");

$relatedGuides = [
    ['title' => 'Getting to Puerto Rico Beaches', 'slug' => 'getting-to-puerto-rico-beaches'],
    ['title' => 'Bioluminescent Bays Guide', 'slug' => 'bioluminescent-bays'],
    ['title' => 'Snorkeling Guide', 'slug' => 'snorkeling-guide']
];

$faqs = [
    ['question' => 'Which island has better beaches, Culebra or Vieques?', 'answer' => 'Culebra has more pristine, postcard-perfect beaches including Flamenco Beach (often rated top 10 globally). Vieques offers more variety with wild, untouched beaches and bioluminescent bio bay. For pure beach beauty, Culebra edges ahead. For adventure and diversity, choose Vieques.'],
    ['question' => 'Is Culebra or Vieques easier to reach?', 'answer' => 'Both require ferries from Ceiba or small flights. Ferry difficulty is similar, but Culebra has slightly more flight options. Neither is significantly easier—plan for similar travel times and advance ferry bookings for either island.'],
    ['question' => 'Which island is better for families?', 'answer' => 'Culebra is more family-friendly with calm, shallow beaches perfect for young children. Vieques requires more driving on unpaved roads and has more isolated beaches. Culebra also has more amenities and services concentrated in Dewey.'],
    ['question' => 'Can you visit both islands in one trip?', 'answer' => 'Yes, but it\'s logistically challenging and time-consuming. Each island deserves 2-3 days minimum. If you have a week, spend 3 days on one island and 3-4 on the other, accounting for a full day lost to inter-island travel.'],
    ['question' => 'Which island has better snorkeling?', 'answer' => 'Both offer world-class snorkeling. Culebra has easier access to reefs at Tamarindo and Carlos Rosario beaches. Vieques has more remote snorkel spots requiring boat access. Coral health is excellent at both—it\'s a tie for snorkeling quality.']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle); ?> - Puerto Rico Beach Finder</title>
    <meta name="description" content="<?php echo h($pageDescription); ?>">
    <?php
    echo articleSchema($pageTitle, $pageDescription, 'https://puertoricobeachfinder.com/guides/culebra-vs-vieques.php', '2024-01-15');
    echo faqSchema($faqs);
    echo breadcrumbSchema([
        ['name' => 'Home', 'url' => 'https://puertoricobeachfinder.com/'],
        ['name' => 'Guides', 'url' => 'https://puertoricobeachfinder.com/guides/'],
        ['name' => 'Culebra vs Vieques', 'url' => 'https://puertoricobeachfinder.com/guides/culebra-vs-vieques.php']
    ]);
    ?>
</head>
<body class="bg-gray-50" data-theme="light">
    <?php include __DIR__ . '/../components/header.php'; ?>
    <?php
    $breadcrumbs = [
        ['name' => 'Home', 'url' => '/'],
        ['name' => 'Guides', 'url' => '/guides/'],
        ['name' => 'Culebra vs Vieques']
    ];
    include __DIR__ . '/../components/hero-guide.php';
    ?>

    <main class="guide-layout">
        <aside class="guide-sidebar">
            <div class="guide-toc">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Table of Contents</h2>
                    <nav class="space-y-2">
                        <a href="#overview" class="guide-toc-link">Quick Overview</a>
                        <a href="#comparison" class="guide-toc-link">Side-by-Side</a>
                        <a href="#beaches" class="guide-toc-link">Beaches</a>
                        <a href="#transportation" class="guide-toc-link">Getting There</a>
                        <a href="#accommodation" class="guide-toc-link">Where to Stay</a>
                        <a href="#activities" class="guide-toc-link">Activities</a>
                        <a href="#verdict" class="guide-toc-link">Final Verdict</a>
                        <a href="#faq" class="guide-toc-link">FAQ</a>
                    </nav>
                </div>
            </aside>

        <article class="guide-article bg-white rounded-lg shadow-card p-8">
                <div class="prose prose-lg max-w-none">
                    <p class="lead text-xl text-gray-700 mb-8">
                        Culebra and Vieques are Puerto Rico's crown jewels—two stunning islands off the east coast offering pristine beaches, world-class snorkeling, and escape from mainland crowds. While both promise Caribbean paradise, they deliver distinctly different experiences. This comprehensive comparison helps you choose which island matches your vacation style, budget, and interests.
                    </p>

                    <h2 id="overview" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Quick Overview</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-8">
                        <div class="bg-blue-50 rounded-lg p-6">
                            <h3 class="text-2xl font-bold text-blue-900 mb-4">Culebra</h3>
                            <ul class="space-y-2 text-gray-700">
                                <li><strong>Size:</strong> 10 square miles</li>
                                <li><strong>Population:</strong> ~2,000</li>
                                <li><strong>Vibe:</strong> Laid-back, pristine, quiet</li>
                                <li><strong>Famous For:</strong> Flamenco Beach, turtle nesting</li>
                                <li><strong>Best For:</strong> Pure beach relaxation, families</li>
                            </ul>
                        </div>
                        <div class="bg-green-50 rounded-lg p-6">
                            <h3 class="text-2xl font-bold text-green-900 mb-4">Vieques</h3>
                            <ul class="space-y-2 text-gray-700">
                                <li><strong>Size:</strong> 52 square miles</li>
                                <li><strong>Population:</strong> ~8,000</li>
                                <li><strong>Vibe:</strong> Adventurous, wild, eclectic</li>
                                <li><strong>Famous For:</strong> Bioluminescent bay, wild horses</li>
                                <li><strong>Best For:</strong> Adventure, nature, exploration</li>
                            </ul>
                        </div>
                    </div>

                    <h2 id="comparison" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Side-by-Side Comparison</h2>

                    <div class="overflow-x-auto my-8">
                        <table class="comparison-table w-full">
                            <thead>
                                <tr>
                                    <th class="text-left">Category</th>
                                    <th class="text-left bg-blue-50">Culebra</th>
                                    <th class="text-left bg-green-50">Vieques</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Beach Quality</strong></td>
                                    <td>Exceptional, world-famous</td>
                                    <td>Excellent, more variety</td>
                                </tr>
                                <tr>
                                    <td><strong>Crowds</strong></td>
                                    <td>Moderate at Flamenco, empty elsewhere</td>
                                    <td>Very low everywhere</td>
                                </tr>
                                <tr>
                                    <td><strong>Accessibility</strong></td>
                                    <td>Most beaches easy to reach</td>
                                    <td>Many require 4WD or hiking</td>
                                </tr>
                                <tr>
                                    <td><strong>Snorkeling</strong></td>
                                    <td>Excellent, easy shore access</td>
                                    <td>Excellent, often requires boat</td>
                                </tr>
                                <tr>
                                    <td><strong>Accommodation</strong></td>
                                    <td>Limited, basic options</td>
                                    <td>More variety, better range</td>
                                </tr>
                                <tr>
                                    <td><strong>Dining</strong></td>
                                    <td>~15 restaurants, casual</td>
                                    <td>~30+ restaurants, more variety</td>
                                </tr>
                                <tr>
                                    <td><strong>Nightlife</strong></td>
                                    <td>Minimal, very quiet</td>
                                    <td>Low-key bars, live music</td>
                                </tr>
                                <tr>
                                    <td><strong>Transportation</strong></td>
                                    <td>Golf carts, scooters, Jeeps</td>
                                    <td>Cars, Jeeps essential</td>
                                </tr>
                                <tr>
                                    <td><strong>Daily Budget</strong></td>
                                    <td>$150-250/day</td>
                                    <td>$120-220/day</td>
                                </tr>
                                <tr>
                                    <td><strong>Ideal Stay</strong></td>
                                    <td>2-3 days</td>
                                    <td>3-5 days</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h2 id="beaches" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Beach Comparison</h2>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Culebra's Best Beaches</h3>
                    
                    <?php if (!empty($culebra_beaches)): ?>
                    <div class="space-y-4 mb-6">
                        <?php foreach ($culebra_beaches as $beach): ?>
                        <div class="bg-blue-50 border-l-4 border-blue-600 p-4">
                            <a href="/beach.php?id=<?php echo $beach['id']; ?>" class="text-blue-900 font-bold hover:underline">
                                <?php echo h($beach['name']); ?>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <p class="mb-4">
                        <strong>Flamenco Beach</strong> is Culebra's flagship—a mile-long crescent of powder-white sand and turquoise water consistently ranked among the world's best beaches. It has facilities, lifeguards, food vendors, and calm waters perfect for swimming. Arrive before 10 AM on weekends for parking.
                    </p>

                    <p class="mb-4">
                        <strong>Tamarindo Beach</strong> offers excellent snorkeling with sea turtles often visible. It's a short hike from the parking area, keeping crowds minimal. The reef is close to shore, ideal for beginners.
                    </p>

                    <p class="mb-4">
                        <strong>Zoni Beach</strong> on the northeast coast is wild and windswept with dramatic views. Less crowded than Flamenco but rougher waters. Excellent for long walks and solitude.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Vieques' Best Beaches</h3>

                    <?php if (!empty($vieques_beaches)): ?>
                    <div class="space-y-4 mb-6">
                        <?php foreach ($vieques_beaches as $beach): ?>
                        <div class="bg-green-50 border-l-4 border-green-600 p-4">
                            <a href="/beach.php?id=<?php echo $beach['id']; ?>" class="text-green-900 font-bold hover:underline">
                                <?php echo h($beach['name']); ?>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <p class="mb-4">
                        <strong>Sun Bay</strong> is the main public beach with facilities, camping, and lifeguards on weekends. Wide sandy beach with calm waters good for families. Entrance fee $5.
                    </p>

                    <p class="mb-4">
                        <strong>Media Luna</strong> (Half Moon Bay) lives up to its name with a perfect crescent shape. Calm, shallow water ideal for snorkeling. Requires driving rough dirt road—4WD recommended.
                    </p>

                    <p class="mb-4">
                        <strong>Playa Negra (Black Sand Beach)</strong> is unique with volcanic black sand. Dramatic coastline, good for exploring but not swimming due to rough surf.
                    </p>

                    <h2 id="transportation" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Getting There and Around</h2>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Ferry Service</h3>

                    <p class="mb-4">
                        Both islands are served by ferries from Ceiba on the mainland. <strong>Passenger tickets cost $2-4 each way</strong>, making ferries the budget option. However, booking is challenging—reserve 2-3 weeks ahead through the maritime authority website. Ferries run multiple times daily, taking 60-70 minutes.
                    </p>

                    <div class="bg-yellow-50 border-l-4 border-yellow-600 p-6 my-6">
                        <h4 class="font-bold text-yellow-900 mb-2">Ferry Booking Tips</h4>
                        <ul class="space-y-2 text-yellow-800">
                            <li>Book exactly when reservations open (usually 2-3 weeks ahead)</li>
                            <li>Try early morning when system is less busy</li>
                            <li>Have backup dates in case preferred times sell out</li>
                            <li>Consider standby if desperate, but not guaranteed</li>
                            <li>Vehicle ferry slots are extremely limited—don't count on bringing a car</li>
                        </ul>
                    </div>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Flights</h3>

                    <p class="mb-4">
                        Small aircraft fly from San Juan (SJU) or Ceiba to both islands in 25-30 minutes. <strong>Round-trip costs $100-200</strong> per person. Airlines include Cape Air, Vieques Air Link, and Air Flamenco. Strict 40-pound baggage limits—pack light.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Island Transportation</h3>

                    <p class="mb-4">
                        <strong>Culebra:</strong> Golf carts ($60-80/day) are popular and sufficient for paved roads. Scooters ($50/day) and Jeeps ($75-100/day) also available. The island is small—everywhere is 15 minutes or less.
                    </p>

                    <p class="mb-4">
                        <strong>Vieques:</strong> Rent a Jeep or 4WD vehicle ($70-120/day). Many beaches require unpaved roads where golf carts struggle. The island is 5x larger than Culebra—expect 20-30 minute drives to remote beaches.
                    </p>

                    <h2 id="accommodation" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Where to Stay</h2>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Culebra Lodging</h3>

                    <p class="mb-4">
                        <strong>Limited options</strong> concentrated in Dewey, the main town. Expect basic guesthouses, small inns, and vacation rentals. Few amenities—this is rustic island living. Book months ahead for peak season.
                    </p>

                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Budget ($80-120/night):</strong> Guesthouses, basic rooms, shared facilities</li>
                        <li><strong>Mid-range ($120-200/night):</strong> Small inns, private rooms, A/C</li>
                        <li><strong>Luxury ($200-350/night):</strong> Limited; a few boutique properties</li>
                        <li><strong>Camping:</strong> Flamenco Beach campground ($30/night for tent site)</li>
                    </ul>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Vieques Lodging</h3>

                    <p class="mb-4">
                        <strong>More variety and availability</strong> across two towns—Isabel II (north) and Esperanza (south). Better restaurant access in both areas. More developed tourism infrastructure.
                    </p>

                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Budget ($70-110/night):</strong> Guesthouses, hostels, basic rentals</li>
                        <li><strong>Mid-range ($110-220/night):</strong> Inns, boutique hotels, full apartments</li>
                        <li><strong>Luxury ($220-500/night):</strong> W Retreat & Spa, boutique resorts, luxury villas</li>
                        <li><strong>Camping:</strong> Sun Bay campground ($35/night)</li>
                    </ul>

                    <h2 id="activities" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Activities and Attractions</h2>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Culebra Activities</h3>

                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Beach hopping:</strong> Visit 4-5 beaches in a day—island is compact</li>
                        <li><strong>Snorkeling:</strong> Shore snorkeling at multiple beaches, no boat needed</li>
                        <li><strong>Kayaking:</strong> Calm bays perfect for paddling</li>
                        <li><strong>Diving:</strong> Several dive shops offer reef and wreck dives</li>
                        <li><strong>Turtle watching:</strong> Nesting season April-November</li>
                        <li><strong>Hiking:</strong> Limited trails; mostly beach walks</li>
                        <li><strong>Town exploration:</strong> Dewey is tiny but has character</li>
                    </ul>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Vieques Activities</h3>

                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Bioluminescent bay:</strong> Mosquito Bay—world's brightest bio bay</li>
                        <li><strong>Wildlife refuge:</strong> Former Navy land now protected, wild horses roam</li>
                        <li><strong>Snorkeling and diving:</strong> Pristine reefs, often requires boat</li>
                        <li><strong>Horseback riding:</strong> Beach rides available</li>
                        <li><strong>Art galleries:</strong> Local artists showcase work</li>
                        <li><strong>Hiking:</strong> Trails through refuge and to fort ruins</li>
                        <li><strong>Fishing:</strong> Excellent sport fishing charters</li>
                    </ul>

                    <h2 id="costs" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Cost Comparison</h2>

                    <p class="mb-4">
                        <strong>Both islands are expensive</strong> due to remoteness and limited supply. Everything is imported, inflating prices 20-40% above mainland Puerto Rico.
                    </p>

                    <div class="bg-gray-50 rounded-lg p-6 my-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Daily Budget Breakdown</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-bold text-blue-900 mb-3">Culebra (per day)</h4>
                                <ul class="space-y-1 text-gray-700">
                                    <li>Accommodation: $100-200</li>
                                    <li>Vehicle rental: $60-80</li>
                                    <li>Meals: $50-80</li>
                                    <li>Activities: $0-50</li>
                                    <li><strong>Total: $210-410</strong></li>
                                </ul>
                            </div>
                            <div>
                                <h4 class="font-bold text-green-900 mb-3">Vieques (per day)</h4>
                                <ul class="space-y-1 text-gray-700">
                                    <li>Accommodation: $90-220</li>
                                    <li>Vehicle rental: $70-100</li>
                                    <li>Meals: $45-75</li>
                                    <li>Activities: $30-80 (bio bay)</li>
                                    <li><strong>Total: $235-475</strong></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <h2 id="verdict" class="text-3xl font-bold text-gray-900 mt-12 mb-6">The Verdict: Which Island Should You Choose?</h2>

                    <div class="space-y-6 my-8">
                        <div class="border-l-4 border-blue-600 pl-6 bg-blue-50 p-4 rounded-r">
                            <h3 class="text-xl font-bold text-blue-900 mb-2">Choose Culebra If You Want:</h3>
                            <ul class="space-y-1 text-gray-700">
                                <li>✓ World-class beaches with minimal planning</li>
                                <li>✓ Easy, compact island to navigate</li>
                                <li>✓ Family-friendly calm waters</li>
                                <li>✓ Simpler, more laid-back vibe</li>
                                <li>✓ Golf cart accessibility to beaches</li>
                                <li>✓ Pure beach vacation focus</li>
                            </ul>
                        </div>

                        <div class="border-l-4 border-green-600 pl-6 bg-green-50 p-4 rounded-r">
                            <h3 class="text-xl font-bold text-green-900 mb-2">Choose Vieques If You Want:</h3>
                            <ul class="space-y-1 text-gray-700">
                                <li>✓ Bioluminescent bay experience</li>
                                <li>✓ Adventure and exploration</li>
                                <li>✓ More dining and accommodation variety</li>
                                <li>✓ Wilder, less developed beaches</li>
                                <li>✓ Better nightlife (still low-key)</li>
                                <li>✓ Longer stay with more activities</li>
                            </ul>
                        </div>
                    </div>

                    <h2 id="faq" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Frequently Asked Questions</h2>

                    <div class="space-y-6">
                        <?php foreach ($faqs as $faq): ?>
                        <div class="border-l-4 border-green-600 pl-4">
                            <h3 class="text-xl font-bold text-gray-900 mb-2"><?php echo h($faq['question']); ?></h3>
                            <p class="text-gray-700"><?php echo h($faq['answer']); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-8 mt-12">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Explore Island Beaches</h2>
                        <p class="text-gray-700 mb-6">
                            Browse all beaches on Culebra and Vieques to plan your island adventure.
                        </p>
                        <div class="flex gap-4">
                            <a href="/?municipality=Culebra" class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                                Culebra Beaches
                            </a>
                            <a href="/?municipality=Vieques" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                                Vieques Beaches
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mt-12 pt-8 border-t border-gray-200">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Related Guides</h3>
                    <div class="related-guides-grid">
                        <?php foreach ($relatedGuides as $guide): ?>
                        <a href="/guides/<?php echo h($guide['slug']); ?>.php" class="related-guide-card">
                            <span class="related-guide-title"><?php echo h($guide['title']); ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>
        </div>
    </main>

    <?php include __DIR__ . '/../components/footer.php'; ?>
</body>
</html>
