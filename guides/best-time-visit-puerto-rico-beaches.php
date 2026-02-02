<?php
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/constants.php';
require_once __DIR__ . '/../components/seo-schemas.php';

$pageTitle = 'Best Time to Visit Puerto Rico Beaches: Month-by-Month Guide';
$pageDescription = 'Find the perfect time for your Puerto Rico beach vacation with our month-by-month breakdown of weather, crowds, and seasonal highlights.';

$relatedGuides = [
    ['title' => 'Getting to Puerto Rico Beaches', 'slug' => 'getting-to-puerto-rico-beaches'],
    ['title' => 'Beach Safety Tips', 'slug' => 'beach-safety-tips'],
    ['title' => 'Beach Packing List', 'slug' => 'beach-packing-list']
];

$faqs = [
    ['question' => 'What is the best month to visit Puerto Rico beaches?', 'answer' => 'April and May offer ideal conditions: warm weather, low rainfall, smaller crowds, and better prices than peak winter season. September and October also provide excellent value with warm water, though hurricane risk is higher.'],
    ['question' => 'When is hurricane season in Puerto Rico?', 'answer' => 'Hurricane season runs June 1 - November 30, with peak activity in August, September, and October. However, direct hits are rare. Many travelers visit during these months and experience perfect weather, taking advantage of lower prices.'],
    ['question' => 'Is winter a good time for Puerto Rico beaches?', 'answer' => 'Winter (December-March) offers perfect weather with average temperatures of 80°F and minimal rain. However, it\'s peak season with higher prices, larger crowds, and less accommodation availability. Book 3-6 months in advance.'],
    ['question' => 'What is the rainiest month in Puerto Rico?', 'answer' => 'September and October typically see the most rainfall. However, rain usually comes in brief afternoon showers that clear quickly. Morning beach time is usually sunny even during wet season.'],
    ['question' => 'When is the best time for surfing in Puerto Rico?', 'answer' => 'November through March brings the best surf to Puerto Rico\'s north and west coasts, with consistent swells from Atlantic storms. Rincón hosts international surfing competitions during winter. Summer offers smaller, gentler waves ideal for beginners.'],
    ['question' => 'What are the cheapest months to visit Puerto Rico?', 'answer' => 'May, September, and October offer the lowest prices on flights and hotels—sometimes 30-50% cheaper than peak season. These shoulder season months still provide excellent beach weather with fewer tourists.'],
    ['question' => 'Is the water warm in Puerto Rico year-round?', 'answer' => 'Yes, ocean temperatures range from 78°F in winter to 85°F in summer. The water is comfortably warm for swimming, snorkeling, and diving throughout the year without a wetsuit needed.'],
    ['question' => 'When do Puerto Rico beaches get crowded?', 'answer' => 'Major U.S. holidays (Thanksgiving, Christmas, New Year, Spring Break, Easter) see the heaviest crowds. Local holidays also bring Puerto Rican families to beaches. Weekdays are less crowded than weekends year-round.']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle); ?> - Puerto Rico Beach Finder</title>
    <meta name="description" content="<?php echo h($pageDescription); ?>">
    <link rel="stylesheet" href="/assets/css/tailwind.min.css">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <?php
    echo articleSchema($pageTitle, $pageDescription, 'https://puertoricobeachfinder.com/guides/best-time-visit-puerto-rico-beaches.php', '2024-01-15');
    echo faqSchema($faqs);
    echo breadcrumbSchema([
        ['name' => 'Home', 'url' => 'https://puertoricobeachfinder.com/'],
        ['name' => 'Guides', 'url' => 'https://puertoricobeachfinder.com/guides/'],
        ['name' => 'Best Time to Visit', 'url' => 'https://puertoricobeachfinder.com/guides/best-time-visit-puerto-rico-beaches.php']
    ]);
    ?>
</head>
<body class="bg-gray-50" data-theme="light">
    <?php include __DIR__ . '/../components/header.php'; ?>

    <section class="bg-gradient-to-br from-green-600 to-green-700 text-white py-16">
        <div class="container mx-auto px-4 container-padding">
            <nav class="text-sm mb-6 text-green-100">
                <a href="/" class="hover:text-white">Home</a>
                <span class="mx-2">&gt;</span>
                <a href="/guides/" class="hover:text-white">Guides</a>
                <span class="mx-2">&gt;</span>
                <span>Best Time to Visit</span>
            </nav>
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Best Time to Visit Puerto Rico Beaches</h1>
            <p class="text-xl text-green-50 max-w-3xl">
                Your complete month-by-month guide to planning the perfect beach vacation in Puerto Rico.
            </p>
        </div>
    </section>

    <main class="guide-layout">
        <aside class="guide-sidebar">
            <div class="guide-toc">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Table of Contents</h2>
                    <nav class="space-y-2">
                        <a href="#overview" class="guide-toc-link">Season Overview</a>
                        <a href="#winter" class="guide-toc-link">Winter (Dec-Feb)</a>
                        <a href="#spring" class="guide-toc-link">Spring (Mar-May)</a>
                        <a href="#summer" class="guide-toc-link">Summer (Jun-Aug)</a>
                        <a href="#fall" class="guide-toc-link">Fall (Sep-Nov)</a>
                        <a href="#events" class="guide-toc-link">Events & Holidays</a>
                        <a href="#faq" class="guide-toc-link">FAQ</a>
                    </nav>
                </div>
            </aside>

        <article class="guide-article bg-white rounded-lg shadow-card p-8">
                <div class="prose prose-lg max-w-none">
                    <p class="lead text-xl text-gray-700 mb-8">
                        Puerto Rico enjoys tropical climate year-round, making it a fantastic beach destination in any season. However, understanding seasonal patterns helps you choose the best time based on your priorities—whether that's perfect weather, smaller crowds, lower prices, or specific activities like surfing or whale watching.
                    </p>

                    <h2 id="overview" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Understanding Puerto Rico's Seasons</h2>

                    <p class="mb-4">
                        Unlike mainland U.S., Puerto Rico doesn't experience dramatic seasonal temperature changes. Instead, <strong>the main differences are rainfall, crowds, and prices</strong>. Average temperatures hover between 78-85°F year-round, with ocean temperatures a comfortable 78-85°F.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 my-8">
                        <div class="bg-blue-50 rounded-lg p-6">
                            <h3 class="text-xl font-bold text-blue-900 mb-2">Peak Season</h3>
                            <p class="text-sm text-blue-800 mb-2">December - March</p>
                            <p class="text-gray-700">Perfect weather, largest crowds, highest prices. Book 3-6 months ahead.</p>
                        </div>
                        <div class="bg-green-50 rounded-lg p-6">
                            <h3 class="text-xl font-bold text-green-900 mb-2">Shoulder Season</h3>
                            <p class="text-sm text-green-800 mb-2">April-May, November</p>
                            <p class="text-gray-700">Great weather, moderate crowds, good value. Sweet spot for many travelers.</p>
                        </div>
                        <div class="bg-yellow-50 rounded-lg p-6">
                            <h3 class="text-xl font-bold text-yellow-900 mb-2">Low Season</h3>
                            <p class="text-sm text-yellow-800 mb-2">June-October</p>
                            <p class="text-gray-700">Warm, some rain, smallest crowds, best deals. Hurricane season but low risk.</p>
                        </div>
                    </div>

                    <h2 id="winter" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Winter: December - February</h2>

                    <p class="mb-4">
                        <strong>Winter is peak season</strong> in Puerto Rico, drawing visitors escaping cold northern climates. This period offers the most reliable weather—sunny skies, low humidity, and minimal rain. However, it's also the busiest and most expensive time to visit.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">December</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Weather:</strong> 80°F average, 3 inches rain, 70% sunny days</li>
                        <li><strong>Crowds:</strong> Very high, especially Christmas/New Year weeks</li>
                        <li><strong>Prices:</strong> Highest of the year, book early for deals</li>
                        <li><strong>Best for:</strong> Guaranteed sun, holiday atmosphere, whale watching begins</li>
                        <li><strong>Activities:</strong> All water sports, Las Mañanitas festival, beach parties</li>
                    </ul>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">January</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Weather:</strong> 79°F average, 2.5 inches rain, warmest ocean temps</li>
                        <li><strong>Crowds:</strong> High through MLK weekend, moderate after</li>
                        <li><strong>Prices:</strong> Very high early month, dropping late January</li>
                        <li><strong>Best for:</strong> Prime surfing on north coast, humpback whales visible</li>
                        <li><strong>Activities:</strong> Surf competitions in Rincón, San Sebastián Street Festival</li>
                    </ul>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">February</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Weather:</strong> 79°F average, 2 inches rain, driest month</li>
                        <li><strong>Crowds:</strong> Moderate to high, spike during Presidents' Day</li>
                        <li><strong>Prices:</strong> Still elevated but better than Dec-Jan</li>
                        <li><strong>Best for:</strong> Most reliable weather, Valentine's getaways, whale watching peak</li>
                        <li><strong>Activities:</strong> Coffee Harvest Festival, San Juan street art tours</li>
                    </ul>

                    <h2 id="spring" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Spring: March - May</h2>

                    <p class="mb-4">
                        <strong>Spring offers the best overall value</strong>, especially April and May. Weather remains excellent while crowds thin out and prices drop significantly. This is my top recommendation for budget-conscious travelers seeking great conditions.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">March</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Weather:</strong> 80°F average, 2.5 inches rain, increasing warmth</li>
                        <li><strong>Crowds:</strong> High during Spring Break (mid-month), moderate otherwise</li>
                        <li><strong>Prices:</strong> Moderate, good deals outside Spring Break</li>
                        <li><strong>Best for:</strong> Balance of weather and value, college crowd if you want energy</li>
                        <li><strong>Activities:</strong> Emancipation Day celebrations, kite festivals</li>
                    </ul>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">April</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Weather:</strong> 82°F average, 3 inches rain, perfect beach weather</li>
                        <li><strong>Crowds:</strong> Low to moderate except Easter week</li>
                        <li><strong>Prices:</strong> Great value, 20-30% lower than winter</li>
                        <li><strong>Best for:</strong> Ideal conditions without crowds, honeymoons, couples</li>
                        <li><strong>Activities:</strong> San Juan Food Festival, sugar harvest season</li>
                    </ul>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">May</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Weather:</strong> 83°F average, 4 inches rain (brief afternoon showers)</li>
                        <li><strong>Crowds:</strong> Low, excellent for quiet beaches</li>
                        <li><strong>Prices:</strong> Excellent deals, transition to low season</li>
                        <li><strong>Best for:</strong> Budget travelers, peaceful getaways, turtle nesting begins</li>
                        <li><strong>Activities:</strong> Heineken JazzFest, mangrove kayaking, bio bay tours</li>
                    </ul>

                    <h2 id="summer" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Summer: June - August</h2>

                    <p class="mb-4">
                        <strong>Summer brings warmth, humidity, and occasional rain</strong>—but also incredible deals and authentic local atmosphere. Hurricane season begins in June, though storms are rare before August. For budget travelers willing to accept minor weather risk, summer offers outstanding value.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">June</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Weather:</strong> 84°F average, 5 inches rain, warmer and humid</li>
                        <li><strong>Crowds:</strong> Low early month, increasing late June (summer vacation)</li>
                        <li><strong>Prices:</strong> Low, great flight and hotel deals</li>
                        <li><strong>Best for:</strong> Value seekers, fewer tourists, calm south coast beaches</li>
                        <li><strong>Activities:</strong> San Juan Bautista Day, beach festivals, mango season</li>
                    </ul>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">July</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Weather:</strong> 85°F average, 6 inches rain, hottest month</li>
                        <li><strong>Crowds:</strong> Moderate (U.S. families), but still less than winter</li>
                        <li><strong>Prices:</strong> Moderate, higher than June but reasonable</li>
                        <li><strong>Best for:</strong> Family vacations, warmest water temps, calm Caribbean side</li>
                        <li><strong>Activities:</strong> Ponce Carnival, Loíza Festival, turtle watching</li>
                    </ul>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">August</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Weather:</strong> 85°F average, 7 inches rain, hurricane season active</li>
                        <li><strong>Crowds:</strong> Low to moderate, fewer tourists post-summer vacation</li>
                        <li><strong>Prices:</strong> Good deals despite being summer</li>
                        <li><strong>Best for:</strong> Budget travel, authentic experience, risk-tolerant travelers</li>
                        <li><strong>Activities:</strong> Barranquitas Crafts Fair, bio bay tours (best darkness)</li>
                    </ul>

                    <h2 id="fall" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Fall: September - November</h2>

                    <p class="mb-4">
                        <strong>Fall is the riskiest but cheapest season</strong>. September and October are peak hurricane months, causing many travelers to avoid Puerto Rico despite excellent prices. November transitions back to ideal conditions and is an underrated gem for value-conscious visitors.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">September</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Weather:</strong> 85°F average, 6 inches rain, peak hurricane risk</li>
                        <li><strong>Crowds:</strong> Very low, locals have beaches to themselves</li>
                        <li><strong>Prices:</strong> Lowest of the year, up to 50% off peak rates</li>
                        <li><strong>Best for:</strong> Maximum savings, flexible travelers, travel insurance recommended</li>
                        <li><strong>Activities:</strong> Rincón Steps Pro-Am surf contest, seafood festivals</li>
                    </ul>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">October</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Weather:</strong> 84°F average, 6 inches rain, still hurricane season</li>
                        <li><strong>Crowds:</strong> Very low except Columbus Day weekend</li>
                        <li><strong>Prices:</strong> Very low, excellent value for risk-tolerant</li>
                        <li><strong>Best for:</strong> Adventurous travelers, last-minute deals, quiet exploration</li>
                        <li><strong>Activities:</strong> International Billfish Tournament, coffee tours</li>
                    </ul>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">November</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Weather:</strong> 82°F average, 5 inches rain, conditions improving</li>
                        <li><strong>Crowds:</strong> Low except Thanksgiving week, great shoulder season</li>
                        <li><strong>Prices:</strong> Moderate, rising into holiday season</li>
                        <li><strong>Best for:</strong> Value and weather balance, surf season begins, smaller groups</li>
                        <li><strong>Activities:</strong> Festival Jayuya, North coast swells return, migratory birds</li>
                    </ul>

                    <h2 id="events" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Annual Events and Holidays</h2>

                    <p class="mb-4">
                        <strong>Plan around these events</strong> for unique experiences or to avoid crowds and price surges:
                    </p>

                    <div class="bg-gray-50 rounded-lg p-6 my-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Major Events</h3>
                        <ul class="space-y-3 text-gray-700">
                            <li><strong>San Sebastián Street Festival (January):</strong> Massive celebration in Old San Juan, book accommodations months ahead</li>
                            <li><strong>Ponce Carnival (February):</strong> Week-long carnival on south coast, colorful parades and beach parties</li>
                            <li><strong>Heineken JazzFest (May):</strong> World-class jazz festival in San Juan</li>
                            <li><strong>San Juan Bautista Day (June 24):</strong> Traditional beach festivities, walking backwards into ocean at midnight</li>
                            <li><strong>Rincón International Film Festival (April):</strong> Independent films in surf town setting</li>
                            <li><strong>Saborea Food Festival (April):</strong> Puerto Rico's premier culinary event</li>
                        </ul>
                    </div>

                    <h2 id="recommendations" class="text-3xl font-bold text-gray-900 mt-12 mb-6">When Should You Visit?</h2>

                    <div class="space-y-6 my-8">
                        <div class="border-l-4 border-green-600 pl-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Best Overall Time: April - May</h3>
                            <p class="text-gray-700">Perfect weather, manageable crowds, reasonable prices. Ideal for first-time visitors seeking the complete package.</p>
                        </div>
                        <div class="border-l-4 border-blue-600 pl-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Best for Surfing: November - March</h3>
                            <p class="text-gray-700">Consistent north swells, international competitions, world-class breaks firing. Rincón and Isabela at their best.</p>
                        </div>
                        <div class="border-l-4 border-yellow-600 pl-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Best Budget Time: September - October</h3>
                            <p class="text-gray-700">Lowest prices of the year, empty beaches, authentic experience. Accept weather risk and buy travel insurance.</p>
                        </div>
                        <div class="border-l-4 border-purple-600 pl-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Best for Families: July - August</h3>
                            <p class="text-gray-700">School vacation timing, warm calm waters, kid-friendly events and festivals throughout the island.</p>
                        </div>
                        <div class="border-l-4 border-red-600 pl-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Best Weather Guarantee: February</h3>
                            <p class="text-gray-700">Driest month, consistent sunshine, perfect temps. Worth the premium for sun-guaranteed vacations.</p>
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
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Plan Your Perfect Beach Trip</h2>
                        <p class="text-gray-700 mb-6">
                            Now that you know when to visit, explore our beach database to plan your itinerary.
                        </p>
                        <a href="/" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                            Browse All Beaches
                        </a>
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
