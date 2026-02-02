<?php
/**
 * Municipality Landing Pages
 * Dynamic SEO-optimized pages for each municipality
 * URL: /beaches-in-{municipality-slug}
 */

require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/constants.php';
require_once __DIR__ . '/components/seo-schemas.php';

// Get municipality from slug or query parameter
$municipalitySlug = $_GET['m'] ?? '';

if (!$municipalitySlug) {
    http_response_code(404);
    header('Location: /');
    exit;
}

// Convert slug back to municipality name (e.g., "san-juan" -> "San Juan")
$municipality = ucwords(str_replace('-', ' ', $municipalitySlug));

// Validate municipality exists
if (!isValidMunicipality($municipality)) {
    http_response_code(404);
    header('Location: /');
    exit;
}

// Fetch beaches in this municipality
$beaches = query("
    SELECT b.*
    FROM beaches b
    WHERE b.municipality = :municipality
    AND b.publish_status = 'published'
    ORDER BY
        CASE WHEN b.google_rating IS NOT NULL THEN 1 ELSE 2 END,
        b.google_rating DESC,
        b.name ASC
", [':municipality' => $municipality]);

if (empty($beaches)) {
    http_response_code(404);
    header('Location: /');
    exit;
}

// Attach metadata (tags, amenities)
attachBeachMetadata($beaches);

// Calculate stats
$beachCount = count($beaches);
$avgRating = 0;
$ratedBeaches = array_filter($beaches, fn($b) => !empty($b['google_rating']));
if (!empty($ratedBeaches)) {
    $avgRating = array_sum(array_column($ratedBeaches, 'google_rating')) / count($ratedBeaches);
}

// Get top beaches
$topBeaches = array_slice($beaches, 0, 3);

// Get popular tags for this municipality
$tagCounts = [];
foreach ($beaches as $beach) {
    foreach ($beach['tags'] ?? [] as $tag) {
        $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
    }
}
arsort($tagCounts);
$topTags = array_slice(array_keys($tagCounts), 0, 5);

// Page metadata
$pageTitle = "Best Beaches in {$municipality}, Puerto Rico ({$beachCount} Beaches)";
$pageDescription = "Discover {$beachCount} beautiful beaches in {$municipality}, Puerto Rico. Find the perfect beach with our comprehensive guide including ratings, amenities, directions, and real-time conditions.";
$canonicalUrl = ($_ENV['APP_URL'] ?? 'https://www.puertoricobeachfinder.com') . '/beaches-in-' . $municipalitySlug;

// Structured data
$extraHead = articleSchema(
    $pageTitle,
    $pageDescription,
    '/beaches-in-' . $municipalitySlug,
    $topBeaches[0]['cover_image'] ?? null
);
$extraHead .= collectionPageSchema($pageTitle, $pageDescription, $beaches);

// Dynamic FAQs based on municipality
$pageFaqs = [
    [
        'question' => "How many beaches are in {$municipality}?",
        'answer' => "{$municipality} has {$beachCount} beaches ranging from popular tourist spots to secluded hidden gems. Our database includes detailed information on all public beaches in the area."
    ],
    [
        'question' => "What is the best beach in {$municipality}?",
        'answer' => !empty($topBeaches)
            ? "{$topBeaches[0]['name']} is one of the top-rated beaches in {$municipality}" .
              ($topBeaches[0]['google_rating'] ? " with a {$topBeaches[0]['google_rating']} star rating" : "") . ". " .
              (substr($topBeaches[0]['description'] ?? '', 0, 150))
            : "{$municipality} has many beautiful beaches to choose from."
    ],
    [
        'question' => "What activities can I do at {$municipality} beaches?",
        'answer' => "Beaches in {$municipality} offer " .
            (!empty($topTags) ? implode(', ', array_map('getTagLabel', array_slice($topTags, 0, 3))) : "various activities") .
            ". Each beach has unique characteristics - some are perfect for families, others for surfing or snorkeling."
    ],
    [
        'question' => "How do I get to beaches in {$municipality}?",
        'answer' => "Most beaches in {$municipality} are accessible by car. Use our 'Get Directions' button on each beach card for GPS navigation. Some beaches may require a short walk from parking areas."
    ]
];
$extraHead .= faqSchema($pageFaqs);

// Breadcrumbs
$extraHead .= breadcrumbSchema([
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'Beaches by Municipality', 'url' => '/#beaches'],
    ['name' => $municipality, 'url' => '/beaches-in-' . $municipalitySlug]
]);

include __DIR__ . '/components/header.php';
?>

<!-- Hero Section -->
<section class="hero-gradient text-white py-12 md:py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumbs -->
        <nav class="text-white/50 text-sm mb-4" aria-label="Breadcrumb">
            <a href="/" class="hover:text-brand-yellow transition-colors">Home</a>
            <span class="mx-2">/</span>
            <a href="/#beaches" class="hover:text-brand-yellow transition-colors">Beaches</a>
            <span class="mx-2">/</span>
            <span class="text-white/70"><?= h($municipality) ?></span>
        </nav>

        <h1 class="text-3xl md:text-5xl font-bold mb-4">
            Beaches in <?= h($municipality) ?>, Puerto Rico
        </h1>
        <p class="text-lg md:text-xl opacity-90 max-w-3xl page-description">
            Explore <?= $beachCount ?> stunning beaches in <?= h($municipality) ?>. From world-class surfing spots to tranquil swimming coves, discover the perfect beach for your Caribbean adventure.
        </p>

        <!-- Stats Bar -->
        <div class="flex flex-wrap gap-6 mt-6 text-sm">
            <div class="flex items-center gap-2">
                <i data-lucide="map-pin" class="w-5 h-5 text-brand-yellow"></i>
                <span><strong><?= $beachCount ?></strong> Beaches</span>
            </div>
            <?php if ($avgRating > 0): ?>
            <div class="flex items-center gap-2">
                <i data-lucide="star" class="w-5 h-5 text-brand-yellow"></i>
                <span><strong><?= number_format($avgRating, 1) ?></strong> Avg Rating</span>
            </div>
            <?php endif; ?>
            <?php if (!empty($topTags)): ?>
            <div class="flex items-center gap-2">
                <i data-lucide="activity" class="w-5 h-5 text-brand-yellow"></i>
                <span><?= h(getTagLabel($topTags[0])) ?>, <?= h(getTagLabel($topTags[1] ?? $topTags[0])) ?> & more</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Quick Filter Tags -->
<?php if (!empty($topTags)): ?>
<section class="bg-brand-dark border-b border-white/10 py-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3 overflow-x-auto hide-scrollbar">
            <span class="text-sm text-white/60 whitespace-nowrap">Popular:</span>
            <?php foreach ($topTags as $tag): ?>
            <a href="/?municipality=<?= urlencode($municipality) ?>&tags[]=<?= h($tag) ?>#beaches"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/10 hover:bg-brand-yellow/20 border border-white/10 hover:border-brand-yellow/30 text-white/80 hover:text-brand-yellow text-sm transition-colors whitespace-nowrap">
                <?= h(getTagLabel($tag)) ?>
                <span class="text-xs text-white/50"><?= $tagCounts[$tag] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Main Content -->
<section class="py-12 md:py-16 bg-brand-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Intro Paragraph (SEO Content) -->
        <div class="max-w-4xl mb-8 text-gray-300 leading-relaxed">
            <p class="mb-4">
                <?= h($municipality) ?> is home to <?= $beachCount ?> diverse beaches along Puerto Rico's beautiful coastline.
                Whether you're seeking <?= !empty($topTags) ? strtolower(getTagLabel($topTags[0])) : 'adventure' ?>,
                family-friendly swimming spots, or secluded natural beauty, <?= h($municipality) ?>'s beaches offer something for every visitor.
            </p>
            <?php if (!empty($topBeaches)): ?>
            <p>
                Popular beaches include <strong><?= h($topBeaches[0]['name']) ?></strong>
                <?php if (isset($topBeaches[1])): ?>, <strong><?= h($topBeaches[1]['name']) ?></strong><?php endif; ?>
                <?php if (isset($topBeaches[2])): ?>, and <strong><?= h($topBeaches[2]['name']) ?></strong><?php endif; ?>.
                Each beach features detailed information including GPS coordinates, amenities, current conditions, and visitor reviews to help you plan the perfect beach day.
            </p>
            <?php endif; ?>
        </div>

        <!-- Beach Grid -->
        <div id="beach-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            foreach ($beaches as $beach):
                $isFavorite = false;
                if (isAuthenticated()) {
                    $userFavorites = query('SELECT beach_id FROM user_favorites WHERE user_id = :user_id', [':user_id' => $_SESSION['user_id']]);
                    $isFavorite = in_array($beach['id'], array_column($userFavorites, 'beach_id'));
                }
                include __DIR__ . '/components/beach-card.php';
            endforeach;
            ?>
        </div>

        <!-- FAQs Section -->
        <div class="mt-16 max-w-4xl">
            <h2 class="text-2xl font-bold text-white mb-6">Frequently Asked Questions</h2>
            <div class="space-y-4">
                <?php foreach ($pageFaqs as $faq): ?>
                <details class="bg-white/5 border border-white/10 rounded-lg p-4 hover:bg-white/10 transition-colors">
                    <summary class="font-semibold text-white cursor-pointer"><?= h($faq['question']) ?></summary>
                    <p class="mt-3 text-gray-300 leading-relaxed"><?= h($faq['answer']) ?></p>
                </details>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php
$extraScripts = '<script defer src="/assets/js/map.js"></script>';
include __DIR__ . '/components/footer.php';
?>
