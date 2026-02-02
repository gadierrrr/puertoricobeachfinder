<?php
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../components/seo-schemas.php';

$pageTitle = 'Snorkeling in Puerto Rico: Complete Guide';
$pageDescription = 'Comprehensive snorkeling guide for Puerto Rico covering top spots, equipment, techniques, marine life, safety, and the best beaches for underwater exploration.';

$snorkel_beaches = query("SELECT id, name, municipality, slug FROM beaches WHERE id IN (
    SELECT beach_id FROM beach_tags WHERE tag = 'snorkeling' LIMIT 5
)");

$relatedGuides = [
    ['title' => 'Beach Safety Tips', 'slug' => 'beach-safety-tips'],
    ['title' => 'Beach Packing List', 'slug' => 'beach-packing-list'],
    ['title' => 'Culebra vs Vieques', 'slug' => 'culebra-vs-vieques']
];

$faqs = [
    ['question' => 'What are the best beaches for snorkeling in Puerto Rico?', 'answer' => 'Top snorkeling beaches include Flamenco and Tamarindo (Culebra), Carlos Rosario (Culebra), Seven Seas (Fajardo), Crash Boat (Aguadilla), and beaches on Vieques. These offer healthy coral reefs, clear water, and abundant marine life close to shore.'],
    ['question' => 'Do I need to bring my own snorkel gear to Puerto Rico?', 'answer' => 'While you can rent equipment ($10-15/day) at popular beaches, bringing your own mask and snorkel ensures proper fit and hygiene. Consider packing compact travel snorkel sets and renting fins on-island if luggage space is limited.'],
    ['question' => 'What marine life will I see snorkeling in Puerto Rico?', 'answer' => 'Expect to see colorful tropical fish (parrotfish, angelfish, tangs), sea turtles, stingrays, small reef sharks, octopus, and vibrant coral formations. Lucky snorkelers may spot eagle rays or dolphin pods.'],
    ['question' => 'Is snorkeling safe for beginners in Puerto Rico?', 'answer' => 'Yes! Many beaches have calm, shallow reef areas perfect for beginners. Start at beaches with lifeguards like Luquillo or Seven Seas. Practice in shallow water before venturing deeper, and always snorkel with a buddy.'],
    ['question' => 'When is the best time to snorkel in Puerto Rico?', 'answer' => 'Year-round snorkeling is excellent due to warm water (78-85°F). Best conditions are typically morning (8-11 AM) when water is calmest and visibility highest. Avoid snorkeling after heavy rain when runoff reduces visibility.']
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
    echo articleSchema($pageTitle, $pageDescription, 'https://puertoricobeachfinder.com/guides/snorkeling-guide.php', '2024-01-15');
    echo howToSchema('How to Snorkel in Puerto Rico', 'Complete guide to snorkeling at Puerto Rico beaches', [
        ['name' => 'Choose the Right Beach', 'text' => 'Select a beach with calm conditions, healthy reefs, and good visibility. Check current conditions and weather before heading out.'],
        ['name' => 'Get Proper Equipment', 'text' => 'Use a well-fitting mask that doesn\'t leak, comfortable snorkel tube, and fins for easier swimming. Test mask fit on dry land first.'],
        ['name' => 'Apply Reef-Safe Sunscreen', 'text' => 'Use mineral-based sunscreen at least 15 minutes before entering water to protect skin and coral reefs.'],
        ['name' => 'Enter Water Safely', 'text' => 'Walk backwards with fins on, or put fins on once in waist-deep water. Don\'t step on coral or rocks.'],
        ['name' => 'Practice Breathing', 'text' => 'Start in shallow water. Breathe slowly through your mouth, keeping snorkel above water. Clear water from snorkel by exhaling forcefully.'],
        ['name' => 'Explore Responsibly', 'text' => 'Stay at surface, don\'t touch coral or marine life, maintain buoyancy control, and always snorkel with a buddy.']
    ]);
    echo faqSchema($faqs);
    echo breadcrumbSchema([
        ['name' => 'Home', 'url' => 'https://puertoricobeachfinder.com/'],
        ['name' => 'Guides', 'url' => 'https://puertoricobeachfinder.com/guides/'],
        ['name' => 'Snorkeling Guide', 'url' => 'https://puertoricobeachfinder.com/guides/snorkeling-guide.php']
    ]);
    ?>
    <style>.toc-sticky { position: sticky; top: 100px; max-height: calc(100vh - 120px); overflow-y: auto; }</style>
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
                <span>Snorkeling Guide</span>
            </nav>
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Snorkeling in Puerto Rico: Complete Guide</h1>
            <p class="text-xl text-green-50 max-w-3xl">
                Discover Puerto Rico's underwater world with our comprehensive snorkeling guide covering equipment, techniques, top spots, and marine life.
            </p>
        </div>
    </section>

    <main class="container mx-auto px-4 container-padding py-12">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <aside class="lg:col-span-1">
                <div class="toc-sticky bg-white rounded-lg shadow-card p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Table of Contents</h2>
                    <nav class="space-y-2">
                        <a href="#why" class="block text-green-600 hover:text-green-700 text-sm">Why Puerto Rico?</a>
                        <a href="#equipment" class="block text-green-600 hover:text-green-700 text-sm">Equipment</a>
                        <a href="#technique" class="block text-green-600 hover:text-green-700 text-sm">Techniques</a>
                        <a href="#top-spots" class="block text-green-600 hover:text-green-700 text-sm">Top 10 Spots</a>
                        <a href="#marine-life" class="block text-green-600 hover:text-green-700 text-sm">Marine Life</a>
                        <a href="#safety" class="block text-green-600 hover:text-green-700 text-sm">Safety</a>
                        <a href="#faq" class="block text-green-600 hover:text-green-700 text-sm">FAQ</a>
                    </nav>
                </div>
            </aside>

            <article class="lg:col-span-3 bg-white rounded-lg shadow-card p-8">
                <div class="prose prose-lg max-w-none">
                    <p class="lead text-xl text-gray-700 mb-8">
                        Puerto Rico offers world-class snorkeling with pristine coral reefs, abundant marine life, and crystal-clear Caribbean waters. From the protected reefs of Culebra to the underwater landscapes of Vieques, snorkeling here reveals a vibrant underwater world accessible to beginners and experts alike. This guide covers everything you need for unforgettable snorkeling experiences.
                    </p>

                    <h2 id="why" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Why Puerto Rico for Snorkeling?</h2>

                    <ul class="list-disc list-inside space-y-3 text-gray-700 mb-6">
                        <li><strong>Year-round warm water</strong> (78-85°F) requires no wetsuit</li>
                        <li><strong>Excellent visibility</strong> often 50-100+ feet in clear conditions</li>
                        <li><strong>Protected reefs</strong> at Culebra National Wildlife Refuge</li>
                        <li><strong>Abundant marine life</strong> including sea turtles, rays, tropical fish</li>
                        <li><strong>Easy shore access</strong> at many beaches—no boat needed</li>
                        <li><strong>Diverse environments</strong> from shallow reefs to deeper walls</li>
                    </ul>

                    <h2 id="equipment" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Essential Snorkeling Equipment</h2>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Mask</h3>
                    <p class="mb-4">
                        <strong>Proper fit is critical.</strong> Test mask by pressing to face without using strap—it should stay on by suction. Common mistakes: choosing based on color rather than fit, buying masks that leak, or using old masks with deteriorated silicone.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Snorkel Tube</h3>
                    <p class="mb-4">
                        <strong>Simple is better.</strong> Avoid complex designs with multiple valves that can fail. A basic J-shaped tube with a comfortable mouthpiece works best. Purge valves at the bottom help clear water.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Fins</h3>
                    <p class="mb-4">
                        <strong>Full-foot vs adjustable.</strong> Full-foot fins are lighter and better for warm water snorkeling. Adjustable fins work with booties for rocky entries. Longer fins provide more power but require stronger legs.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Additional Gear</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Rash guard or wetsuit top</strong> - Protects from sun and jellyfish</li>
                        <li><strong>Anti-fog solution</strong> - Prevents mask fogging (or use toothpaste/spit)</li>
                        <li><strong>Snorkel vest</strong> - Adds buoyancy and safety for weak swimmers</li>
                        <li><strong>Waterproof camera</strong> - Capture underwater memories</li>
                        <li><strong>Mesh gear bag</strong> - Allows equipment to drain and dry</li>
                    </ul>

                    <h2 id="technique" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Snorkeling Techniques</h2>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">For Beginners</h3>
                    <ol class="list-decimal list-inside space-y-3 text-gray-700 mb-6">
                        <li><strong>Start in shallow, calm water</strong> to practice before venturing deeper</li>
                        <li><strong>Adjust mask strap</strong> snug but not too tight (causes leaks)</li>
                        <li><strong>Breathe slowly through mouth</strong> keeping snorkel above water</li>
                        <li><strong>Relax and float</strong> face-down to conserve energy</li>
                        <li><strong>Clear water from snorkel</strong> by exhaling forcefully or using purge valve</li>
                        <li><strong>Equalize ears</strong> if diving below surface by pinching nose and gentle blowing</li>
                    </ol>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Advanced Tips</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li>Use efficient flutter kick keeping legs mostly underwater</li>
                        <li>Duck dive to explore deeper: point head down, lift legs vertical for downward momentum</li>
                        <li>Control buoyancy by adjusting lung volume (fuller lungs = more buoyant)</li>
                        <li>Scan slowly side to side rather than swimming quickly past features</li>
                        <li>Look under ledges and in crevices where shy creatures hide</li>
                    </ul>

                    <h2 id="top-spots" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Top 10 Snorkeling Beaches</h2>

                    <?php if (!empty($snorkel_beaches)): ?>
                    <div class="space-y-4 mb-8">
                        <?php $counter = 1; foreach ($snorkel_beaches as $beach): ?>
                        <div class="bg-green-50 border-l-4 border-green-600 p-4">
                            <h4 class="font-bold text-green-900"><?php echo $counter; ?>. <a href="/beach.php?id=<?php echo $beach['id']; ?>" class="hover:underline"><?php echo h($beach['name']); ?></a></h4>
                            <p class="text-green-800 text-sm"><?php echo h($beach['municipality']); ?></p>
                        </div>
                        <?php $counter++; endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <h2 id="marine-life" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Marine Life You'll Encounter</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-8">
                        <div class="bg-blue-50 rounded-lg p-6">
                            <h3 class="text-xl font-bold text-blue-900 mb-3">Common Fish Species</h3>
                            <ul class="space-y-1 text-gray-700">
                                <li>• Parrotfish (rainbow colors, "beak" mouth)</li>
                                <li>• Blue Tang (bright blue, "Dory" fish)</li>
                                <li>• French Angelfish (black/yellow stripes)</li>
                                <li>• Sergeant Major (black vertical stripes)</li>
                                <li>• Stoplight Parrotfish (males: green/pink/blue)</li>
                            </ul>
                        </div>
                        <div class="bg-green-50 rounded-lg p-6">
                            <h3 class="text-xl font-bold text-green-900 mb-3">Special Encounters</h3>
                            <ul class="space-y-1 text-gray-700">
                                <li>• Green Sea Turtles (common, surface to breathe)</li>
                                <li>• Southern Stingrays (often buried in sand)</li>
                                <li>• Octopus (masters of camouflage)</li>
                                <li>• Eagle Rays (occasional, graceful swimmers)</li>
                                <li>• Nurse Sharks (harmless, rest under ledges)</li>
                            </ul>
                        </div>
                    </div>

                    <h2 id="safety" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Safety Guidelines</h2>

                    <ul class="list-disc list-inside space-y-3 text-gray-700 mb-6">
                        <li><strong>Always snorkel with a buddy</strong> - Never go alone</li>
                        <li><strong>Check conditions before entering</strong> - Avoid strong currents, high surf</li>
                        <li><strong>Stay aware of boat traffic</strong> - Use dive flag if swimming far from shore</li>
                        <li><strong>Don't touch coral or marine life</strong> - Protects you and ecosystem</li>
                        <li><strong>Watch for rip currents</strong> - Swim parallel to shore to escape</li>
                        <li><strong>Use reef-safe sunscreen</strong> - Protects coral health</li>
                        <li><strong>Know your limits</strong> - Don't venture into deep water if uncomfortable</li>
                        <li><strong>Exit before exhaustion</strong> - Conserve energy for return swim</li>
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
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Find Snorkeling Beaches</h2>
                        <p class="text-gray-700 mb-6">
                            Browse beaches with excellent snorkeling to plan your underwater adventure.
                        </p>
                        <a href="/?tags=snorkeling" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                            View Snorkeling Beaches
                        </a>
                    </div>
                </div>

                <div class="mt-12 pt-8 border-t border-gray-200">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">Related Guides</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <?php foreach ($relatedGuides as $guide): ?>
                        <a href="/guides/<?php echo h($guide['slug']); ?>.php" class="block p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <span class="text-green-600 font-semibold"><?php echo h($guide['title']); ?></span>
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
