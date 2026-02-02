<?php
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../components/seo-schemas.php';

$pageTitle = 'Bioluminescent Bays Guide: Puerto Rico\'s Glowing Waters';
$pageDescription = 'Complete guide to visiting Puerto Rico\'s three bioluminescent bays including Mosquito Bay Vieques, Laguna Grande Fajardo, and La Parguera with tour info.';

$relatedGuides = [
    ['title' => 'Culebra vs Vieques', 'slug' => 'culebra-vs-vieques'],
    ['title' => 'Getting to Puerto Rico Beaches', 'slug' => 'getting-to-puerto-rico-beaches'],
    ['title' => 'Best Time to Visit Puerto Rico Beaches', 'slug' => 'best-time-visit-puerto-rico-beaches']
];

$faqs = [
    ['question' => 'What causes bioluminescence in Puerto Rico bays?', 'answer' => 'Bioluminescence is caused by dinoflagellates (Pyrodinium bahamense), microscopic organisms that emit blue-green light when disturbed. Puerto Rico\'s bays have ideal conditions: mangrove-lined entrances, shallow warm water, and minimal light pollution.'],
    ['question' => 'Which bio bay is the brightest?', 'answer' => 'Mosquito Bay in Vieques is the world\'s brightest bioluminescent bay, with the highest concentration of dinoflagellates. La Parguera is dimmer but more accessible. Laguna Grande falls in between.'],
    ['question' => 'When is the best time to see bioluminescence?', 'answer' => 'New moon periods (darkest nights) show the brightest bioluminescence. Avoid full moons. Tours run year-round, but summer months (June-September) have longer darkness and warmer water for swimming.'],
    ['question' => 'Can you swim in bioluminescent bays?', 'answer' => 'Swimming is allowed and encouraged at Mosquito Bay (Vieques) and sometimes Laguna Grande. La Parguera allows swimming. Movement in water creates spectacular glowing trails around your body.'],
    ['question' => 'How much do bio bay tours cost?', 'answer' => 'Tours typically cost $45-75 per person for 2-hour kayak or boat experiences. Kayak tours are more intimate and interactive. Book in advance, especially during peak season.']
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
    echo articleSchema($pageTitle, $pageDescription, 'https://puertoricobeachfinder.com/guides/bioluminescent-bays.php', '2024-01-15');
    echo faqSchema($faqs);
    echo breadcrumbSchema([
        ['name' => 'Home', 'url' => 'https://puertoricobeachfinder.com/'],
        ['name' => 'Guides', 'url' => 'https://puertoricobeachfinder.com/guides/'],
        ['name' => 'Bioluminescent Bays', 'url' => 'https://puertoricobeachfinder.com/guides/bioluminescent-bays.php']
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
                <span>Bioluminescent Bays</span>
            </nav>
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Bioluminescent Bays: Puerto Rico's Glowing Waters</h1>
            <p class="text-xl text-green-50 max-w-3xl">
                Experience the magical phenomenon of glowing waters at Puerto Rico's three bioluminescent bays.
            </p>
        </div>
    </section>

    <main class="guide-layout">
        <aside class="guide-sidebar">
            <div class="guide-toc">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Table of Contents</h2>
                    <nav class="space-y-2">
                        <a href="#overview" class="guide-toc-link">What is Bioluminescence?</a>
                        <a href="#mosquito-bay" class="guide-toc-link">Mosquito Bay</a>
                        <a href="#laguna-grande" class="guide-toc-link">Laguna Grande</a>
                        <a href="#la-parguera" class="guide-toc-link">La Parguera</a>
                        <a href="#tips" class="guide-toc-link">Visiting Tips</a>
                        <a href="#faq" class="guide-toc-link">FAQ</a>
                    </nav>
                </div>
            </aside>

        <article class="guide-article bg-white rounded-lg shadow-card p-8">
                <div class="prose prose-lg max-w-none">
                    <p class="lead text-xl text-gray-700 mb-8">
                        Puerto Rico is home to three of the world's five bioluminescent bays—rare ecosystems where microscopic organisms create an ethereal blue-green glow when disturbed. This natural phenomenon offers one of the most memorable experiences available in Puerto Rico, with kayak tours through glowing waters creating trails of light with every paddle stroke.
                    </p>

                    <h2 id="overview" class="text-3xl font-bold text-gray-900 mt-12 mb-6">What Causes Bioluminescence?</h2>

                    <p class="mb-4">
                        The glow comes from <strong>dinoflagellates</strong>, single-celled organisms called Pyrodinium bahamense that emit light when agitated. Puerto Rico's bays provide perfect conditions: narrow entrances limiting water exchange, mangrove-lined shores providing nutrients, warm shallow water, and crucially, minimal light pollution. When you move through the water, millions of these organisms light up simultaneously, creating magical blue-green sparkles.
                    </p>

                    <h2 id="mosquito-bay" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Mosquito Bay, Vieques</h2>

                    <p class="mb-4">
                        <strong>The World's Brightest Bioluminescent Bay.</strong> Mosquito Bay (Bahía Mosquito) holds the Guinness World Record for brightest bioluminescent bay, with concentrations reaching 720,000 dinoflagellates per gallon. Located on Vieques' south coast, it offers the most intense glow of any bio bay globally.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Getting There</h3>
                    <p class="mb-4">
                        Reach Vieques by ferry from Ceiba ($2-4) or flight from San Juan ($100-200 round-trip). Once on island, drive to Esperanza town. Most tours depart from the town pier.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Tours and Costs</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Kayak tours:</strong> $55-75 per person, 2 hours, more interactive</li>
                        <li><strong>Boat tours:</strong> $45-60 per person, easier for less active travelers</li>
                        <li><strong>Best operators:</strong> Bieque Eco Trips, Abe's Snorkeling, Jak Water Sports</li>
                        <li><strong>Swimming allowed:</strong> Yes! Incredible experience creating glowing outlines</li>
                    </ul>

                    <h2 id="laguna-grande" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Laguna Grande, Fajardo</h2>

                    <p class="mb-4">
                        <strong>Most Accessible from San Juan.</strong> Located in Fajardo on Puerto Rico's northeast coast, Laguna Grande is just 45 minutes from San Juan, making it the easiest bio bay to visit as a day trip from the capital.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Getting There</h3>
                    <p class="mb-4">
                        Drive or Uber from San Juan via Highway 3. Tours depart from Las Croabas area in Fajardo. Free parking at most tour operators.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Tours and Costs</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Kayak tours:</strong> $50-70 per person, includes mangrove channel paddle</li>
                        <li><strong>Best operators:</strong> Kayaking Puerto Rico, Pure Adventure, Eco Action Tours</li>
                        <li><strong>Swimming:</strong> Sometimes allowed depending on conditions</li>
                        <li><strong>Advantage:</strong> No inter-island travel required, easy logistics</li>
                    </ul>

                    <h2 id="la-parguera" class="text-3xl font-bold text-gray-900 mt-12 mb-6">La Parguera, Lajas</h2>

                    <p class="mb-4">
                        <strong>Most Accessible but Dimmest.</strong> La Parguera on the southwest coast is the easiest bio bay to visit logistically but has the dimmest glow due to boat traffic and development. Still worth visiting if on the west coast.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Getting There</h3>
                    <p class="mb-4">
                        Drive to Lajas on Highway 116. Tours leave from La Parguera village waterfront. Many operators offer walk-up availability.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Tours and Costs</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Boat tours:</strong> $20-40 per person, 1 hour, budget-friendly</li>
                        <li><strong>Kayak tours:</strong> $45-60 per person, longer experience</li>
                        <li><strong>Swimming:</strong> Allowed, though glow is less intense</li>
                        <li><strong>Advantage:</strong> Cheapest option, no advance booking usually needed</li>
                    </ul>

                    <h2 id="comparison" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Which Bay Should You Visit?</h2>

                    <div class="bg-gray-50 rounded-lg p-6 my-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Quick Comparison</h3>
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left p-2">Bay</th>
                                    <th class="text-left p-2">Brightness</th>
                                    <th class="text-left p-2">Access</th>
                                    <th class="text-left p-2">Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b">
                                    <td class="p-2"><strong>Mosquito Bay</strong></td>
                                    <td class="p-2">★★★★★</td>
                                    <td class="p-2">Difficult</td>
                                    <td class="p-2">$$$</td>
                                </tr>
                                <tr class="border-b">
                                    <td class="p-2"><strong>Laguna Grande</strong></td>
                                    <td class="p-2">★★★★☆</td>
                                    <td class="p-2">Easy</td>
                                    <td class="p-2">$$</td>
                                </tr>
                                <tr>
                                    <td class="p-2"><strong>La Parguera</strong></td>
                                    <td class="p-2">★★★☆☆</td>
                                    <td class="p-2">Very Easy</td>
                                    <td class="p-2">$</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <h2 id="tips" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Tips for Best Experience</h2>

                    <ul class="list-disc list-inside space-y-3 text-gray-700 mb-6">
                        <li><strong>Visit during new moon</strong> for darkest skies and brightest glow</li>
                        <li><strong>Book 1-2 weeks ahead</strong> during peak season</li>
                        <li><strong>Wear dark clothing</strong> to reduce light reflection</li>
                        <li><strong>Use reef-safe/biodegradable sunscreen only</strong> to protect ecosystem</li>
                        <li><strong>Bring waterproof phone case</strong> for photos (difficult to capture glow)</li>
                        <li><strong>Listen to your guide</strong> about environmental protection</li>
                        <li><strong>Don't use flash photography</strong> or bright lights</li>
                    </ul>

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
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Plan Your Bio Bay Adventure</h2>
                        <p class="text-gray-700 mb-6">
                            Explore beaches near each bioluminescent bay to make the most of your trip.
                        </p>
                        <a href="/?municipality=Vieques" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                            Vieques Beaches
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
