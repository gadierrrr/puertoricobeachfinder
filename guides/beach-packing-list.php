<?php
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../components/seo-schemas.php';

$pageTitle = 'Ultimate Beach Packing List for Puerto Rico';
$pageDescription = 'Complete beach packing checklist covering sun protection, swim gear, comfort items, and adventure essentials for Puerto Rico beaches.';

$relatedGuides = [
    ['title' => 'Beach Safety Tips', 'slug' => 'beach-safety-tips'],
    ['title' => 'Best Time to Visit Puerto Rico Beaches', 'slug' => 'best-time-visit-puerto-rico-beaches'],
    ['title' => 'Snorkeling Guide', 'slug' => 'snorkeling-guide']
];

$faqs = [
    ['question' => 'What sunscreen is allowed in Puerto Rico?', 'answer' => 'Puerto Rico requires reef-safe sunscreen free of oxybenzone and octinoxate. Look for mineral-based formulas with zinc oxide or titanium dioxide labeled "reef-safe" or "reef-friendly."'],
    ['question' => 'Do I need to bring beach towels to Puerto Rico?', 'answer' => 'Most hotels provide beach towels, but budget accommodations and Airbnbs may not. Bring a quick-dry microfiber towel that packs small and dries fast. You can also purchase inexpensive beach towels at Walmart or Walgreens in Puerto Rico.'],
    ['question' => 'What should I wear to Puerto Rico beaches?', 'answer' => 'Pack multiple swimsuits so you always have a dry one. Bring a rash guard or UV swim shirt for extended sun exposure, flip flops or water shoes, sunglasses, and a wide-brimmed hat. Cover-ups or lightweight clothing for walking to/from beaches.'],
    ['question' => 'Do I need water shoes for Puerto Rico beaches?', 'answer' => 'Water shoes are helpful at rocky beaches, reef areas, and beaches with sea urchins. Sandy beaches like Flamenco and Luquillo don\'t require them. Pack neoprene water shoes if planning to snorkel or explore tide pools.'],
    ['question' => 'Can I bring my own snorkel gear to Puerto Rico?', 'answer' => 'Yes, bringing your own mask and snorkel ensures proper fit and hygiene. Many travelers pack compact travel snorkel sets. However, fins can be bulky—consider renting fins on-island if luggage space is limited.']
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
    echo articleSchema($pageTitle, $pageDescription, 'https://puertoricobeachfinder.com/guides/beach-packing-list.php', '2024-01-15');
    echo howToSchema(
        'How to Pack for Puerto Rico Beaches',
        'Complete packing strategy for beach vacation in Puerto Rico',
        [
            ['name' => 'Start with Sun Protection', 'text' => 'Pack reef-safe SPF 50+ sunscreen, wide-brimmed hat, UV sunglasses, and lip balm with SPF. The Caribbean sun is intense year-round.'],
            ['name' => 'Pack Multiple Swimsuits', 'text' => 'Bring 2-3 swimsuits so you always have a dry option. Include a rash guard for extended sun exposure.'],
            ['name' => 'Add Water Activity Gear', 'text' => 'Include snorkel mask, waterproof phone case, dry bag for valuables, and water shoes if visiting rocky beaches.'],
            ['name' => 'Include Comfort Essentials', 'text' => 'Pack beach towel, lightweight beach chair or mat, cooler bag for drinks, and reusable water bottle.'],
            ['name' => 'Prepare First Aid Kit', 'text' => 'Bring band-aids, antibiotic ointment, pain relievers, antihistamine, and any personal medications.'],
            ['name' => 'Don\'t Forget Electronics Protection', 'text' => 'Pack waterproof cases for phone and camera, portable charger, and Ziplock bags for extra protection.'],
            ['name' => 'Add Beach Entertainment', 'text' => 'Consider bringing snorkeling gear, boogie board, beach games, waterproof speaker, and books for downtime.']
        ]
    );
    echo faqSchema($faqs);
    echo breadcrumbSchema([
        ['name' => 'Home', 'url' => 'https://puertoricobeachfinder.com/'],
        ['name' => 'Guides', 'url' => 'https://puertoricobeachfinder.com/guides/'],
        ['name' => 'Beach Packing List', 'url' => 'https://puertoricobeachfinder.com/guides/beach-packing-list.php']
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
                <span>Beach Packing List</span>
            </nav>
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Ultimate Beach Packing List for Puerto Rico</h1>
            <p class="text-xl text-green-50 max-w-3xl">
                Everything you need for the perfect beach day, from essential sun protection to optional adventure gear.
            </p>
        </div>
    </section>

    <main class="guide-layout">
        <aside class="guide-sidebar">
            <div class="guide-toc">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Categories</h2>
                    <nav class="space-y-2">
                        <a href="#essentials" class="guide-toc-link">Essential Items</a>
                        <a href="#sun-protection" class="guide-toc-link">Sun Protection</a>
                        <a href="#swim-gear" class="guide-toc-link">Swim Gear</a>
                        <a href="#comfort" class="guide-toc-link">Comfort Items</a>
                        <a href="#safety" class="guide-toc-link">Safety & First Aid</a>
                        <a href="#electronics" class="guide-toc-link">Electronics</a>
                        <a href="#optional" class="guide-toc-link">Optional Extras</a>
                        <a href="#faq" class="guide-toc-link">FAQ</a>
                    </nav>
                </div>
            </aside>

        <article class="guide-article bg-white rounded-lg shadow-card p-8">
                <div class="prose prose-lg max-w-none">
                    <p class="lead text-xl text-gray-700 mb-8">
                        Packing efficiently for Puerto Rico beaches ensures you have everything needed for comfort, safety, and fun without overpacking. This comprehensive checklist covers absolute essentials, recommended items, and optional extras based on your planned activities. Whether you're visiting calm Caribbean beaches or adventurous north coast surf breaks, this guide has you covered.
                    </p>

                    <h2 id="essentials" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Absolute Essentials</h2>

                    <p class="mb-4">
                        <strong>These items are non-negotiable</strong> for any beach day in Puerto Rico. The Caribbean sun is intense year-round, and being unprepared can ruin your vacation with painful sunburn, dehydration, or lost valuables.
                    </p>

                    <div class="bg-green-50 border-l-4 border-green-600 p-6 my-6">
                        <h3 class="text-xl font-bold text-green-900 mb-4">Must-Have Checklist</h3>
                        <div class="space-y-2">
                            <div class="checklist-item">Reef-safe sunscreen (SPF 30-50+, broad spectrum)</div>
                            <div class="checklist-item">Swimsuit (bring 2-3 so you always have a dry option)</div>
                            <div class="checklist-item">Quick-dry beach towel or microfiber towel</div>
                            <div class="checklist-item">Reusable water bottle (1 liter minimum)</div>
                            <div class="checklist-item">Waterproof bag or dry sack for valuables</div>
                            <div class="checklist-item">Cash (small bills for parking, food, lockers)</div>
                            <div class="checklist-item">ID and health insurance card</div>
                            <div class="checklist-item">Flip flops or sandals</div>
                            <div class="checklist-item">Hat with wide brim (3+ inches)</div>
                            <div class="checklist-item">Sunglasses with UV protection</div>
                        </div>
                    </div>

                    <h2 id="sun-protection" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Sun Protection Arsenal</h2>

                    <p class="mb-4">
                        The Caribbean sun is relentless, capable of causing severe sunburn in just 15 minutes for fair skin. <strong>Comprehensive sun protection is critical</strong>, not optional. Puerto Rico law now prohibits sunscreens containing oxybenzone and octinoxate, so verify your products are reef-safe before packing.
                    </p>

                    <div class="bg-gray-50 rounded-lg p-6 my-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Complete Sun Protection Kit</h3>
                        <div class="space-y-3">
                            <div class="checklist-item"><strong>Reef-safe mineral sunscreen</strong> - SPF 50+, apply every 2 hours (brands: ThinkSport, Stream2Sea, Blue Lizard)</div>
                            <div class="checklist-item"><strong>Face-specific sunscreen</strong> - Higher SPF for facial skin, non-comedogenic to prevent breakouts</div>
                            <div class="checklist-item"><strong>Lip balm with SPF</strong> - Lips burn easily and heal slowly; reapply frequently</div>
                            <div class="checklist-item"><strong>Rash guard or UV swim shirt</strong> - UPF 50+ rated for all-day water activities</div>
                            <div class="checklist-item"><strong>Wide-brimmed hat</strong> - 3+ inches all around to shade face, ears, and neck</div>
                            <div class="checklist-item"><strong>UV-blocking sunglasses</strong> - 100% UVA/UVB protection, wraparound style ideal</div>
                            <div class="checklist-item"><strong>Lightweight cover-up</strong> - Long sleeve shirt or beach dress for walking to/from water</div>
                            <div class="checklist-item"><strong>Beach umbrella or tent</strong> - Portable shade for all-day beach sessions</div>
                            <div class="checklist-item"><strong>Aloe vera gel</strong> - For treating minor sunburn despite precautions</div>
                        </div>
                    </div>

                    <h2 id="swim-gear" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Swimming and Water Activity Gear</h2>

                    <p class="mb-4">
                        Beyond your swimsuit, several items enhance safety, comfort, and enjoyment in the water. <strong>Quality makes a difference</strong>—ill-fitting snorkel masks or leaky waterproof cases can ruin experiences.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Snorkeling Equipment</h3>

                    <div class="space-y-3 mb-6">
                        <div class="checklist-item"><strong>Snorkel mask and tube</strong> - Bring your own for proper fit and hygiene; rental masks often leak</div>
                        <div class="checklist-item"><strong>Snorkel fins</strong> - Optional to pack (bulky); can rent on-island for $5-10/day</div>
                        <div class="checklist-item"><strong>Anti-fog spray or gel</strong> - Essential for clear underwater visibility</div>
                        <div class="checklist-item"><strong>Mesh gear bag</strong> - Allows snorkel equipment to drain and dry between uses</div>
                    </div>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Additional Water Gear</h3>

                    <div class="space-y-3 mb-6">
                        <div class="checklist-item"><strong>Water shoes or reef walkers</strong> - Protect feet from rocks, coral, sea urchins; essential for rocky entries</div>
                        <div class="checklist-item"><strong>Goggles</strong> - For swimming laps or kids who don't use snorkel masks</div>
                        <div class="checklist-item"><strong>Pool noodle or float</strong> - Helpful for weak swimmers or just relaxing</div>
                        <div class="checklist-item"><strong>Boogie board</strong> - Fun for wave riding; can buy cheap ($15-30) or rent on-island</div>
                        <div class="checklist-item"><strong>Waterproof phone case or pouch</strong> - Protect phone in water while capturing photos/videos</div>
                        <div class="checklist-item"><strong>Underwater camera</strong> - GoPro or waterproof point-and-shoot for underwater memories</div>
                    </div>

                    <h2 id="comfort" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Comfort and Convenience Items</h2>

                    <p class="mb-4">
                        <strong>These items transform a basic beach visit into a comfortable, all-day experience</strong>. While not strictly necessary, they significantly improve enjoyment, especially for families or extended beach sessions.
                    </p>

                    <div class="bg-gray-50 rounded-lg p-6 my-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Beach Comfort Checklist</h3>
                        <div class="space-y-3">
                            <div class="checklist-item"><strong>Beach chair or compact lounger</strong> - Lightweight, backpack-style chairs pack easily</div>
                            <div class="checklist-item"><strong>Beach mat or blanket</strong> - Sand-resistant mats shake clean; blankets for larger groups</div>
                            <div class="checklist-item"><strong>Cooler or insulated bag</strong> - Keep drinks cold, store snacks; soft-sided coolers pack flat</div>
                            <div class="checklist-item"><strong>Reusable ice packs</strong> - Freeze overnight for all-day cooling</div>
                            <div class="checklist-item"><strong>Extra water bottles</strong> - 1 gallon per person for full beach day</div>
                            <div class="checklist-item"><strong>Snacks and lunch</strong> - Granola bars, fruit, sandwiches; save money vs beach vendors</div>
                            <div class="checklist-item"><strong>Wet wipes or baby wipes</strong> - Clean hands before eating, remove salt/sand</div>
                            <div class="checklist-item"><strong>Trash bags</strong> - Pack out all garbage; leave beaches cleaner than you found them</div>
                            <div class="checklist-item"><strong>Hand sanitizer</strong> - Especially important if no facilities nearby</div>
                            <div class="checklist-item"><strong>Beach tent or cabana</strong> - Pop-up shade for families with young children</div>
                            <div class="checklist-item"><strong>Spray bottle with water</strong> - Cool off quickly between swims</div>
                        </div>
                    </div>

                    <h2 id="safety" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Safety and First Aid</h2>

                    <p class="mb-4">
                        <strong>A compact first aid kit handles most minor beach injuries</strong>—cuts from shells, scrapes from rocks, jellyfish stings, or splinters. Packing these items prevents minor issues from derailing your day.
                    </p>

                    <div class="bg-yellow-50 border-l-4 border-yellow-600 p-6 my-6">
                        <h3 class="text-xl font-bold text-yellow-900 mb-4">Beach First Aid Kit</h3>
                        <div class="space-y-3">
                            <div class="checklist-item"><strong>Adhesive bandages</strong> - Various sizes for cuts and blisters</div>
                            <div class="checklist-item"><strong>Antibiotic ointment</strong> - Apply to any cuts before bandaging</div>
                            <div class="checklist-item"><strong>Pain relievers</strong> - Ibuprofen or acetaminophen for headaches, pain</div>
                            <div class="checklist-item"><strong>Antihistamine</strong> - For allergic reactions to stings, bites, or food</div>
                            <div class="checklist-item"><strong>Tweezers</strong> - Remove splinters, sea urchin spines, or bee stingers</div>
                            <div class="checklist-item"><strong>Vinegar or baking soda</strong> - Treat jellyfish stings (vinegar) or fire coral (baking soda paste)</div>
                            <div class="checklist-item"><strong>Motion sickness medication</strong> - If taking boats to Culebra or Vieques</div>
                            <div class="checklist-item"><strong>Prescription medications</strong> - Bring full supply; pharmacies may not carry your brand</div>
                            <div class="checklist-item"><strong>Emergency contact card</strong> - List hotel address, emergency numbers, medical conditions</div>
                        </div>
                    </div>

                    <h2 id="electronics" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Electronics and Protection</h2>

                    <p class="mb-4">
                        <strong>Sand, salt water, and sun are enemy to electronics</strong>, yet most travelers want to capture memories and stay connected. Proper protection prevents expensive damage while allowing you to document your beach adventures.
                    </p>

                    <div class="space-y-3 mb-6">
                        <div class="checklist-item"><strong>Waterproof phone case</strong> - Hard case or soft pouch; test in bathtub before trusting in ocean</div>
                        <div class="checklist-item"><strong>Portable charger/power bank</strong> - 10,000+ mAh for multiple device charges</div>
                        <div class="checklist-item"><strong>Ziplock bags</strong> - Extra protection layer for phone, camera, wallet</div>
                        <div class="checklist-item"><strong>Waterproof camera</strong> - GoPro, Olympus Tough, or smartphone in case</div>
                        <div class="checklist-item"><strong>Bluetooth speaker</strong> - Waterproof model for beach music (keep volume reasonable)</div>
                        <div class="checklist-item"><strong>E-reader or tablet</strong> - In protective case for beach reading</div>
                        <div class="checklist-item"><strong>Charging cables</strong> - Backup cables in case primary gets damaged</div>
                        <div class="checklist-item"><strong>Headphones or earbuds</strong> - Waterproof models for swimming with music</div>
                    </div>

                    <h2 id="optional" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Optional Extras and Activity-Specific Gear</h2>

                    <p class="mb-4">
                        <strong>Customize your packing list</strong> based on planned activities, interests, and travel style. These items aren't necessary for everyone but enhance specific beach experiences.
                    </p>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">For Photography Enthusiasts</h3>
                    <div class="space-y-2 mb-6">
                        <div class="checklist-item">DSLR or mirrorless camera with weather-sealed body</div>
                        <div class="checklist-item">Waterproof camera housing for underwater shots</div>
                        <div class="checklist-item">Polarizing filter to reduce water glare</div>
                        <div class="checklist-item">Drone (check regulations; registration required)</div>
                        <div class="checklist-item">Lens cleaning kit for salt spray</div>
                        <div class="checklist-item">Tripod for long exposures or sunset shots</div>
                    </div>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">For Families with Young Children</h3>
                    <div class="space-y-2 mb-6">
                        <div class="checklist-item">Sand toys (buckets, shovels, molds)</div>
                        <div class="checklist-item">Life jackets or floaties (verified Coast Guard approved)</div>
                        <div class="checklist-item">Baby powder (removes sand from skin easily)</div>
                        <div class="checklist-item">Swim diapers for babies and toddlers</div>
                        <div class="checklist-item">Portable changing mat</div>
                        <div class="checklist-item">Extra clothes for accidents</div>
                        <div class="checklist-item">Beach tent for naps and diaper changes</div>
                    </div>

                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">For Adventure Seekers</h3>
                    <div class="space-y-2 mb-6">
                        <div class="checklist-item">Surfboard or paddleboard (or rent on-island)</div>
                        <div class="checklist-item">Wetsuit top for extended surf sessions</div>
                        <div class="checklist-item">Kayak dry bag for multi-hour paddling</div>
                        <div class="checklist-item">Fishing gear (if beach fishing)</div>
                        <div class="checklist-item">Volleyball or football for beach games</div>
                        <div class="checklist-item">Frisbee or paddleball set</div>
                        <div class="checklist-item">Hammock for beach trees</div>
                    </div>

                    <h2 id="packing-tips" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Smart Packing Strategies</h2>

                    <p class="mb-4">
                        <strong>How you pack matters as much as what you pack</strong>. These strategies keep gear organized, accessible, and protected during beach days.
                    </p>

                    <ul class="list-disc list-inside space-y-3 text-gray-700 mb-6">
                        <li><strong>Use mesh beach bags</strong> - Allows sand to fall through, items dry quickly, easy to spot contents</li>
                        <li><strong>Pack a separate beach day bag</strong> - Don't bring your full suitcase to the beach; curate day-specific gear</li>
                        <li><strong>Double-bag electronics</strong> - Ziplock inside waterproof case provides two failure points</li>
                        <li><strong>Freeze water bottles overnight</strong> - They melt slowly providing cold water throughout the day</li>
                        <li><strong>Wear your hat and sunglasses</strong> - Saves packing space, ensures you don't forget them</li>
                        <li><strong>Pack sunscreen in checked luggage</strong> - TSA limits liquids to 3.4oz in carry-on</li>
                        <li><strong>Bring reusable bags</strong> - For wet swimsuits, dirty towels, shells you collect</li>
                        <li><strong>Create a beach kit</strong> - Pre-pack items used every beach day to grab and go</li>
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
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Ready to Explore?</h2>
                        <p class="text-gray-700 mb-6">
                            Now that you know what to pack, find the perfect beach for your adventure.
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
