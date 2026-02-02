<?php
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../components/seo-schemas.php';

$pageTitle = 'Surfing in Puerto Rico: Complete Guide for All Levels';
$pageDescription = 'Expert surfing guide covering best breaks, surf seasons, board rentals, surf schools, and etiquette for Puerto Rico waves from beginners to pros.';
$surf_beaches = query("SELECT id, name, municipality FROM beaches WHERE id IN (SELECT beach_id FROM beach_tags WHERE tag = 'surfing') LIMIT 5");
$relatedGuides = [['title' => 'Beach Safety Tips', 'slug' => 'beach-safety-tips'],['title' => 'Best Time to Visit', 'slug' => 'best-time-visit-puerto-rico-beaches'],['title' => 'Getting to Puerto Rico Beaches', 'slug' => 'getting-to-puerto-rico-beaches']];
$faqs = [
    ['question' => 'When is surf season in Puerto Rico?', 'answer' => 'November through March brings the best surf with consistent swells from Atlantic storms. North and west coast beaches like Rincón and Isabela have world-class waves. Summer offers smaller, gentler waves perfect for beginners.'],
    ['question' => 'Where are the best surf spots in Puerto Rico?', 'answer' => 'Rincón is the surf capital with breaks like Domes, Maria\'s, and Steps. Isabela has Jobos and Middles. Aguadilla offers Crash Boat and Wilderness. Each area has spots for different skill levels.'],
    ['question' => 'Can beginners surf in Puerto Rico?', 'answer' => 'Yes! Summer months (May-September) have small, manageable waves perfect for learning. Surf schools in Rincón, Aguadilla, and Isabela offer lessons ($60-90) with certified instructors.'],
    ['question' => 'Do I need to bring my own surfboard?', 'answer' => 'Rentals are widely available ($25-40/day for shortboards, $30-50 for longboards). Airlines charge $100-150 each way for surfboards. Rent for first few days to test local conditions before committing to bringing boards.'],
    ['question' => 'Are there surf competitions in Puerto Rico?', 'answer' => 'Yes, Rincón hosts international competitions during winter including the Rincón International Film Festival and various Pro-Am events. These attract top surfers worldwide.']
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
    echo articleSchema($pageTitle, $pageDescription, 'https://puertoricobeachfinder.com/guides/surfing-guide.php', '2024-01-15');
    echo howToSchema('How to Surf in Puerto Rico', 'Guide to surfing Puerto Rico waves', [
        ['name' => 'Choose Right Season', 'text' => 'Winter (Nov-Mar) for experienced surfers, summer (May-Sep) for beginners.'],
        ['name' => 'Select Appropriate Beach', 'text' => 'Match break to skill level. Beginners start at mellow beach breaks, advanced surfers tackle reef breaks.'],
        ['name' => 'Rent or Bring Equipment', 'text' => 'Rent boards locally or bring your own. Beginners use longboards (8-9ft), advanced use shortboards (5-7ft).'],
        ['name' => 'Check Conditions', 'text' => 'Use Surfline or Magic Seaweed to check swell, wind, and tide forecasts before heading out.'],
        ['name' => 'Warm Up and Stretch', 'text' => 'Prepare muscles with stretches and warm-up exercises to prevent injury.'],
        ['name' => 'Respect Surf Etiquette', 'text' => 'Don\'t drop in, wait your turn, communicate, and respect locals and their breaks.']
    ]);
    echo faqSchema($faqs);
    echo breadcrumbSchema([
        ['name' => 'Home', 'url' => 'https://puertoricobeachfinder.com/'],
        ['name' => 'Guides', 'url' => 'https://puertoricobeachfinder.com/guides/'],
        ['name' => 'Surfing Guide', 'url' => 'https://puertoricobeachfinder.com/guides/surfing-guide.php']
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
                <span>Surfing Guide</span>
            </nav>
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Surfing in Puerto Rico: Complete Guide</h1>
            <p class="text-xl text-green-50 max-w-3xl">From world-class reef breaks to beginner-friendly beach breaks, Puerto Rico offers exceptional surfing for all skill levels.</p>
        </div>
    </section>
    <main class="guide-layout">
        <aside class="guide-sidebar">
            <div class="guide-toc">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">Table of Contents</h2>
                    <nav class="space-y-2">
                        <a href="#why" class="guide-toc-link">Why Puerto Rico?</a>
                        <a href="#regions" class="guide-toc-link">Surf Regions</a>
                        <a href="#breaks" class="guide-toc-link">Top Breaks</a>
                        <a href="#seasons" class="guide-toc-link">Surf Seasons</a>
                        <a href="#rentals" class="guide-toc-link">Rentals & Schools</a>
                        <a href="#etiquette" class="guide-toc-link">Etiquette</a>
                        <a href="#faq" class="guide-toc-link">FAQ</a>
                    </nav>
                </div>
            </aside>
        <article class="guide-article bg-white rounded-lg shadow-card p-8">
                <div class="prose prose-lg max-w-none">
                    <p class="lead text-xl text-gray-700 mb-8">
                        Puerto Rico ranks among the Caribbean's premier surf destinations, offering world-class waves that attract international competitions and professional surfers. From the legendary breaks of Rincón to the consistent reef breaks of Isabela, the island delivers year-round surf with winter swells producing some of the best waves in the Atlantic. This comprehensive guide covers everything surfers need to know.
                    </p>
                    <h2 id="why" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Why Surf Puerto Rico?</h2>
                    <ul class="list-disc list-inside space-y-3 text-gray-700 mb-6">
                        <li><strong>Consistent winter swells</strong> (Nov-Mar) from Atlantic storms</li>
                        <li><strong>Warm water year-round</strong> (78-85°F) - no wetsuit needed</li>
                        <li><strong>Variety of breaks</strong> - beach breaks, reef breaks, point breaks</li>
                        <li><strong>All skill levels</strong> - from mellow summer waves to powerful winter barrels</li>
                        <li><strong>Surf culture</strong> - welcoming community, competitions, surf shops</li>
                        <li><strong>No passport needed</strong> for U.S. citizens, easy travel</li>
                    </ul>
                    <h2 id="regions" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Surf Regions</h2>
                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Northwest Coast (Rincón, Aguada, Aguadilla)</h3>
                    <p class="mb-4"><strong>The surf capital.</strong> Rincón is Puerto Rico's most famous surf town with world-class breaks. Winter swells produce powerful waves 6-15+ feet. Reef breaks dominate, requiring experience. Surf culture thrives with shops, schools, and competitions.</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">North Coast (Isabela, Arecibo, Hatillo)</h3>
                    <p class="mb-4"><strong>Consistent and accessible.</strong> Isabela offers excellent reef breaks like Jobos and Middles. Less crowded than Rincón but equally powerful winter waves. Good infrastructure with rentals and accommodations.</p>
                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Northeast Coast (Luquillo, Fajardo)</h3>
                    <p class="mb-4"><strong>Beginner-friendly summer waves.</strong> Protected from winter swells, these beaches have gentle waves May-September perfect for learning. Balneario de Luquillo has surf schools and calm conditions.</p>
                    <h2 id="breaks" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Top Surf Breaks by Skill Level</h2>
                    <?php if (!empty($surf_beaches)): ?>
                    <div class="space-y-4 mb-8">
                        <?php foreach ($surf_beaches as $beach): ?>
                        <div class="bg-green-50 border-l-4 border-green-600 p-4">
                            <a href="/beach.php?id=<?php echo $beach['id']; ?>" class="text-green-900 font-bold hover:underline">
                                <?php echo h($beach['name']); ?>
                            </a>
                            <p class="text-green-800 text-sm"><?php echo h($beach['municipality']); ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <h2 id="seasons" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Surf Seasons</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 my-8">
                        <div class="bg-blue-50 rounded-lg p-6">
                            <h3 class="text-xl font-bold text-blue-900 mb-3">Winter (Nov-Mar)</h3>
                            <ul class="space-y-2 text-gray-700">
                                <li><strong>Swell:</strong> 6-15+ feet</li>
                                <li><strong>Best for:</strong> Experienced surfers</li>
                                <li><strong>Crowds:</strong> Highest, competitions</li>
                                <li><strong>Spots:</strong> North/west coasts fire</li>
                            </ul>
                        </div>
                        <div class="bg-green-50 rounded-lg p-6">
                            <h3 class="text-xl font-bold text-green-900 mb-3">Summer (May-Sep)</h3>
                            <ul class="space-y-2 text-gray-700">
                                <li><strong>Swell:</strong> 2-5 feet</li>
                                <li><strong>Best for:</strong> Beginners, longboarders</li>
                                <li><strong>Crowds:</strong> Lower, locals out</li>
                                <li><strong>Spots:</strong> South/east coasts better</li>
                            </ul>
                        </div>
                    </div>
                    <h2 id="rentals" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Board Rentals and Surf Schools</h2>
                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Rentals</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Shortboards:</strong> $25-40/day</li>
                        <li><strong>Longboards:</strong> $30-50/day</li>
                        <li><strong>Soft-tops (beginners):</strong> $20-35/day</li>
                        <li><strong>Weekly rates:</strong> Often 20-30% discount</li>
                    </ul>
                    <h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Surf Schools</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
                        <li><strong>Group lessons:</strong> $60-90 for 2 hours</li>
                        <li><strong>Private lessons:</strong> $100-150 per hour</li>
                        <li><strong>Multi-day packages:</strong> Better value, faster progress</li>
                        <li><strong>Include:</strong> Board, rash guard, instruction, safety briefing</li>
                    </ul>
                    <h2 id="etiquette" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Surf Etiquette</h2>
                    <ul class="list-disc list-inside space-y-3 text-gray-700 mb-6">
                        <li><strong>Don't drop in</strong> - Surfer closest to peak has right of way</li>
                        <li><strong>Don't snake</strong> - Paddling around someone to get inside position</li>
                        <li><strong>Communicate</strong> - Call out when taking wave or paddling out</li>
                        <li><strong>Respect locals</strong> - They surf here daily, show humility</li>
                        <li><strong>Wait your turn</strong> - Don't paddle straight to best position</li>
                        <li><strong>Apologize for mistakes</strong> - Acknowledge when you mess up</li>
                        <li><strong>Help others</strong> - Warn of hazards, assist in emergencies</li>
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
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Find Surf Breaks</h2>
                        <p class="text-gray-700 mb-6">Browse beaches with excellent surfing conditions across Puerto Rico.</p>
                        <a href="/?tags=surfing" class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">View Surf Beaches</a>
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
