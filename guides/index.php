<?php
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/constants.php';

$pageTitle = 'Puerto Rico Beach Guides';
$pageDescription = 'Expert guides to help you plan the perfect Puerto Rico beach vacation. From safety tips to transportation, snorkeling spots to photography advice.';

$guides = [
    [
        'title' => 'Getting to Puerto Rico Beaches',
        'slug' => 'getting-to-puerto-rico-beaches',
        'description' => 'Complete transportation guide including car rentals, ferries, public transit, and tips for reaching every beach.',
        'icon' => '🚗',
        'readTime' => '12 min read'
    ],
    [
        'title' => 'Beach Safety Tips',
        'slug' => 'beach-safety-tips',
        'description' => 'Essential safety information covering rip currents, sun protection, marine life, and emergency contacts.',
        'icon' => '🛟',
        'readTime' => '10 min read'
    ],
    [
        'title' => 'Best Time to Visit',
        'slug' => 'best-time-visit-puerto-rico-beaches',
        'description' => 'Month-by-month breakdown of weather patterns, peak seasons, and ideal times for your beach getaway.',
        'icon' => '📅',
        'readTime' => '11 min read'
    ],
    [
        'title' => 'Beach Packing List',
        'slug' => 'beach-packing-list',
        'description' => 'Comprehensive checklist of everything you need for a perfect day at Puerto Rico\'s beaches.',
        'icon' => '🎒',
        'readTime' => '8 min read'
    ],
    [
        'title' => 'Culebra vs Vieques',
        'slug' => 'culebra-vs-vieques',
        'description' => 'Side-by-side comparison of Puerto Rico\'s two island paradise destinations to help you choose.',
        'icon' => '🏝️',
        'readTime' => '13 min read'
    ],
    [
        'title' => 'Bioluminescent Bays Guide',
        'slug' => 'bioluminescent-bays',
        'description' => 'Everything you need to know about Puerto Rico\'s magical glowing waters and how to visit them.',
        'icon' => '✨',
        'readTime' => '10 min read'
    ],
    [
        'title' => 'Snorkeling Guide',
        'slug' => 'snorkeling-guide',
        'description' => 'Top snorkeling spots, equipment tips, techniques, and what marine life you\'ll encounter.',
        'icon' => '🤿',
        'readTime' => '14 min read'
    ],
    [
        'title' => 'Surfing Guide',
        'slug' => 'surfing-guide',
        'description' => 'Best surf breaks, seasonal patterns, board rentals, surf schools, and etiquette for all skill levels.',
        'icon' => '🏄',
        'readTime' => '12 min read'
    ],
    [
        'title' => 'Beach Photography Tips',
        'slug' => 'beach-photography-tips',
        'description' => 'Capture stunning beach photos with expert tips on lighting, composition, equipment, and drone rules.',
        'icon' => '📸',
        'readTime' => '9 min read'
    ],
    [
        'title' => 'Family Beach Vacation Planning',
        'slug' => 'family-beach-vacation-planning',
        'description' => 'Plan the perfect family beach trip with kid-friendly beaches, sample itineraries, and budget tips.',
        'icon' => '👨‍👩‍👧‍👦',
        'readTime' => '15 min read'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle); ?> - Puerto Rico Beach Finder</title>
    <meta name="description" content="<?php echo h($pageDescription); ?>">
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "CollectionPage",
        "name": "<?php echo h($pageTitle); ?>",
        "description": "<?php echo h($pageDescription); ?>",
        "url": "https://puertoricobeachfinder.com/guides/",
        "breadcrumb": {
            "@type": "BreadcrumbList",
            "itemListElement": [
                {
                    "@type": "ListItem",
                    "position": 1,
                    "name": "Home",
                    "item": "https://puertoricobeachfinder.com/"
                },
                {
                    "@type": "ListItem",
                    "position": 2,
                    "name": "Guides",
                    "item": "https://puertoricobeachfinder.com/guides/"
                }
            ]
        }
    }
    </script>
</head>
<body class="bg-gray-50" data-theme="light">
    <?php include __DIR__ . '/../components/header.php'; ?>

    <!-- Hero Section -->
    <?php
    $breadcrumbs = [
        ['name' => 'Home', 'url' => '/'],
        ['name' => 'Guides']
    ];
    include __DIR__ . '/../components/hero-guide.php';
    ?>

    <!-- Guides Grid -->
    <main class="container mx-auto px-4 container-padding py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($guides as $guide): ?>
                <a href="/guides/<?php echo h($guide['slug']); ?>.php"
                   class="block bg-white rounded-lg shadow-card hover:shadow-lg transition-all duration-300 overflow-hidden group">
                    <div class="p-6">
                        <div class="text-5xl mb-4"><?php echo $guide['icon']; ?></div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-3 group-hover:text-green-600 transition-colors">
                            <?php echo h($guide['title']); ?>
                        </h2>
                        <p class="text-gray-600 mb-4 leading-relaxed">
                            <?php echo h($guide['description']); ?>
                        </p>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-green-600 font-medium"><?php echo h($guide['readTime']); ?></span>
                            <span class="text-gray-400 group-hover:text-green-600 transition-colors">Read guide →</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- CTA Section -->
        <div class="mt-16 bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-8 text-center">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Ready to Explore?</h2>
            <p class="text-lg text-gray-700 mb-6 max-w-2xl mx-auto">
                Browse our collection of 230+ beaches across Puerto Rico and find your perfect beach destination.
            </p>
            <a href="/" class="inline-block bg-green-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
                Browse All Beaches
            </a>
        </div>
    </main>

    <?php include __DIR__ . '/../components/footer.php'; ?>
</body>
</html>
