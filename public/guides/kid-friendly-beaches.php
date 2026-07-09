<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/components/seo-schemas.php';

$pageTitle = 'Best Kid-Friendly Beaches in Puerto Rico';
$pageDescription = 'A practical guide to the best beaches in Puerto Rico for families with kids — calm water, bathrooms, parking, shade, and honest tips for every beach.';

// Slug variants: local dev slugs + production slugs (different DB sources)
$kidBeachSlugs = [
    'luquillo-beach', 'balneario-la-monserrate-luquillo-luquillo-18383-65723',
    'carolina-public-beach', 'balneario-de-carolina-carolina-18442-65997',
    'escambron-beach', 'balneario-del-escambr-n',
    'playa-boqueron', 'balneario-de-boquern-cabo-rojo-18022-67171', 'balneario-p-blico-de-boquer-n',
    'caracas-beach',
    'combate-beach', 'combate-beach-cabo-rojo-17968-67175',
    'flamenco-beach', 'flamenco-beach-culebra-18329-65318',
    'isla-verde-beach',
    'puerto-nuevo-playa-de-vega-baja-beach',
    'montones-beach', 'montones-beach-isabela-18506-67081',
    'playa-buye',
    'balneario-seven-seas', 'seven-seas-beach-fajardo-18377-65631',
    'crash-boat-beach', 'crash-boat-beach-aguadilla-18458-67164',
    'playa-de-pi-ones',
];
$placeholders = implode(',', array_fill(0, count($kidBeachSlugs), '?'));
$kid_beaches = query(
    "SELECT id, slug, name, municipality, lat, lng, cover_image,
        description, google_rating, google_review_count,
        access_label, has_lifeguard, safe_for_children
        FROM beaches WHERE slug IN ($placeholders) ORDER BY name ASC",
    $kidBeachSlugs
);

attachBeachMetadata($kid_beaches);

$beachBySlug = [];
foreach ($kid_beaches as $b) {
    $beachBySlug[$b['slug']] = $b;
}

// Map canonical keys to whichever slug variant exists in this DB
function guideBeach(array $map, string ...$slugs): ?array {
    foreach ($slugs as $s) {
        if (isset($map[$s])) {
            return $map[$s];
        }
    }
    return null;
}
$gb = [
    'luquillo'     => guideBeach($beachBySlug, 'luquillo-beach', 'balneario-la-monserrate-luquillo-luquillo-18383-65723'),
    'carolina'     => guideBeach($beachBySlug, 'carolina-public-beach', 'balneario-de-carolina-carolina-18442-65997'),
    'escambron'    => guideBeach($beachBySlug, 'escambron-beach', 'balneario-del-escambr-n'),
    'boqueron'     => guideBeach($beachBySlug, 'playa-boqueron', 'balneario-de-boquern-cabo-rojo-18022-67171', 'balneario-p-blico-de-boquer-n'),
    'caracas'      => guideBeach($beachBySlug, 'caracas-beach'),
    'combate'      => guideBeach($beachBySlug, 'combate-beach', 'combate-beach-cabo-rojo-17968-67175'),
    'flamenco'     => guideBeach($beachBySlug, 'flamenco-beach', 'flamenco-beach-culebra-18329-65318'),
    'isla-verde'   => guideBeach($beachBySlug, 'isla-verde-beach'),
    'puerto-nuevo' => guideBeach($beachBySlug, 'puerto-nuevo-playa-de-vega-baja-beach'),
    'montones'     => guideBeach($beachBySlug, 'montones-beach', 'montones-beach-isabela-18506-67081'),
    'buye'         => guideBeach($beachBySlug, 'playa-buye'),
    'seven-seas'   => guideBeach($beachBySlug, 'balneario-seven-seas', 'seven-seas-beach-fajardo-18377-65631'),
    'crash-boat'   => guideBeach($beachBySlug, 'crash-boat-beach', 'crash-boat-beach-aguadilla-18458-67164'),
    'pinones'      => guideBeach($beachBySlug, 'playa-de-pi-ones'),
];

$kidMapBeachIds = array_values(array_filter(array_map(static function ($id): string {
    if (!is_scalar($id)) {
        return '';
    }
    return trim((string)$id);
}, array_column($kid_beaches, 'id'))));

$relatedGuides = [
    ['title' => 'Family Beach Vacation Planning', 'slug' => 'family-beach-vacation-planning'],
    ['title' => 'Beach Safety Tips', 'slug' => 'beach-safety-tips'],
    ['title' => 'Beach Packing List', 'slug' => 'beach-packing-list']
];

$faqs = [
    ['question' => 'What is the best kid-friendly beach in Puerto Rico?', 'answer' => 'Luquillo Beach (Balneario La Monserrate) is the strongest all-around choice. It has calm water, lifeguards, restrooms, showers, food kiosks, natural shade from palm trees, and is about 45 minutes from San Juan. Families who prioritize calmer water in a metro setting may prefer Playa El Escambrón instead.'],
    ['question' => 'Are Puerto Rico beaches safe for toddlers?', 'answer' => 'Many beaches have calm, shallow water that works well for toddlers. Balnearios like Luquillo, Carolina, and Boquerón have lifeguards and restrooms. Natural rock pools at Puerto Nuevo in Vega Baja and Montones in Isabela create protected wading areas perfect for very young children. Always check same-day conditions before letting kids into the water.'],
    ['question' => 'Which kid-friendly beaches are closest to San Juan?', 'answer' => 'Playa El Escambrón is walking distance from Condado and Old San Juan with reef-buffered calm water. Balneario de Carolina is a short drive from Isla Verde with lifeguards and facilities. The Isla Verde balneario sections are within walking distance of Isla Verde hotels. Luquillo is about 45 minutes east and offers the most complete family beach experience.'],
    ['question' => 'What does balneario mean in Puerto Rico?', 'answer' => 'A balneario is a government-run public beach. These beaches typically have maintained restrooms, showers, lifeguards during operating hours, and designated parking. They are often the most convenient choice for families because they solve multiple logistical problems at once. The balneario designation signals facilities and maintenance, not water conditions.'],
    ['question' => 'Do I need to bring my own shade to Puerto Rico beaches?', 'answer' => 'A portable pop-up canopy or beach tent is worth its luggage space. Natural shade is limited at most Puerto Rico beaches outside of the palm-lined sections at Luquillo. Do not rely on finding shade when you arrive, especially if you are visiting with infants or toddlers.'],
    ['question' => 'Is Flamenco Beach in Culebra good for kids?', 'answer' => 'Yes, Flamenco Beach has calm, crystal-clear water and is genuinely stunning. However, getting there requires a ferry from Ceiba (roughly 45 minutes each way) or a small-plane flight. Ferry reservations fill up on weekends and peak season. It is best treated as a planned excursion for families with older kids who can handle the travel.']
];

$extraHead = $extraHead ?? "";
$extraHead .= articleSchema($pageTitle, $pageDescription, '/guides/kid-friendly-beaches', null, '2026-04-02');
$extraHead .= faqSchema($faqs);
$extraHead .= breadcrumbSchema([
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'Guides', 'url' => '/guides/'],
    ['name' => 'Kid-Friendly Beaches', 'url' => '/guides/kid-friendly-beaches']
]);

$pageTheme = "guide";
$redesignLayout = useRedesign();
$pageShellMode = "start";
include APP_ROOT . "/components/page-shell.php";
?>
<?php
$breadcrumbs = [
    ['name' => 'Home', 'url' => '/'],
    ['name' => 'Guides', 'url' => '/guides/'],
    ['name' => 'Kid-Friendly Beaches']
];
include APP_ROOT . '/components/hero-guide.php';
?>
<main class="guide-layout">
<aside class="guide-sidebar"><div class="guide-toc">
<h2 class="text-lg font-bold text-gray-900 mb-4">Table of Contents</h2>
<nav class="space-y-2"><a href="#how-to-choose" class="guide-toc-link">How to Choose</a><a href="#quick-picks" class="guide-toc-link">Quick Picks</a><a href="#best-beaches" class="guide-toc-link">Best Beaches</a><a href="#more-beaches" class="guide-toc-link">More Beaches for Kids</a><a href="#comparison" class="guide-toc-link">Comparison Table</a><a href="#by-category" class="guide-toc-link">Beaches by Category</a><a href="#tips" class="guide-toc-link">Planning Tips</a><a href="#faq" class="guide-toc-link">FAQ</a></nav>
</div></aside>
<article class="guide-article bg-white rounded-lg shadow-card p-8">
<div class="prose prose-lg max-w-none">
<p class="lead text-xl text-gray-700 mb-8">Most "best beaches" lists rank Puerto Rico's coastline by beauty or Instagram appeal. That approach fails families traveling with a toddler in swim diapers, a cooler full of snacks, and a two-hour nap window. The beaches that actually work for parents are the ones with calm water on a typical day, a bathroom within walking distance, parking that does not require a 20-minute hike, and enough shade to keep a one-year-old out of direct sun. This guide ranks beaches by those practical filters.</p>

<h2 id="how-to-choose" class="text-3xl font-bold text-gray-900 mt-12 mb-6">How to Choose a Kid-Friendly Beach</h2>

<h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Calm Water Comes First</h3>
<p class="mb-4">For families with toddlers or non-swimmers, water conditions matter more than scenery. Beaches that are generally calmer under typical conditions — often because of a protective reef, cove shape, or sheltered bay — tend to be easier for young kids to wade in safely.</p>
<p class="mb-4"><strong>A critical caveat:</strong> no beach in Puerto Rico (or anywhere) is guaranteed calm every day. Swells, storms, and seasonal changes affect conditions significantly. Always check local surf reports and flag conditions the morning of your visit before letting kids into the water.</p>

<h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Facilities Can Make or Break the Day</h3>
<p class="mb-4">A gorgeous beach with no bathrooms becomes a stressful experience when you are traveling with a potty-training three-year-old. Bathrooms, outdoor showers, parking proximity, shade structures, and nearby food options are the logistical details that separate a relaxing family beach day from an exhausting one.</p>
<p class="mb-4">Shade deserves special attention. Some Puerto Rico beaches have natural tree cover (like the coconut palms at Luquillo), while others are wide-open sand with little protection. If you are visiting with infants or toddlers, bringing a pop-up shade tent is a practical backup regardless of the beach you choose.</p>

<h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Why Balnearios Are Often the Easiest Choice</h3>
<p class="mb-4">A <strong>balneario</strong> is a government-run public beach in Puerto Rico. These maintained beaches typically include restrooms, lifeguards, fenced parking, and other facilities that make a beach day more pleasant. For families, balnearios solve multiple logistical problems at once.</p>
<p class="mb-6">One thing to keep in mind: "balneario" signals facilities and maintenance, not water conditions. A balneario can still have rough surf on a given day. Treat the balneario designation as a strong indicator of convenience, then check water conditions separately.</p>

<h2 id="quick-picks" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Quick Picks by Use Case</h2>
<div class="grid md:grid-cols-2 gap-4 mb-8">
<div class="border-l-4 border-palm-400 p-4 rounded-r-lg" style="background:rgba(15,23,42,0.55)">
<p class="font-bold text-white mb-1">Best All-Around Family Beach</p>
<p class="text-gray-300 text-sm"><?php if (isset($gb['luquillo'])): ?><a href="/beach/<?= h($gb['luquillo']['slug']) ?>" class="text-yellow-300 underline hover:no-underline"><?= h($gb['luquillo']['name']) ?></a><?php else: ?>Luquillo Beach<?php endif; ?> — facilities, food, parking, and generally calm conditions.</p>
</div>
<div class="border-l-4 border-ocean-400 p-4 rounded-r-lg" style="background:rgba(15,23,42,0.55)">
<p class="font-bold text-white mb-1">Best Near San Juan</p>
<p class="text-gray-300 text-sm"><?php if (isset($gb['carolina'])): ?><a href="/beach/<?= h($gb['carolina']['slug']) ?>" class="text-yellow-300 underline hover:no-underline"><?= h($gb['carolina']['name']) ?></a><?php else: ?>Balneario de Carolina<?php endif; ?> — maintained beach close to hotels without a long drive.</p>
</div>
<div class="border-l-4 border-yellow-400 p-4 rounded-r-lg" style="background:rgba(15,23,42,0.55)">
<p class="font-bold text-white mb-1">Best for Toddlers Near San Juan</p>
<p class="text-gray-300 text-sm"><?php if (isset($gb['escambron'])): ?><a href="/beach/<?= h($gb['escambron']['slug']) ?>" class="text-yellow-300 underline hover:no-underline"><?= h($gb['escambron']['name']) ?></a><?php else: ?>Playa El Escambrón<?php endif; ?> — reef-buffered calm water, compact beach for easy supervision.</p>
</div>
<div class="border-l-4 border-purple-400 p-4 rounded-r-lg" style="background:rgba(15,23,42,0.55)">
<p class="font-bold text-white mb-1">Best West Coast Option</p>
<p class="text-gray-300 text-sm"><?php if (isset($gb['boqueron'])): ?><a href="/beach/<?= h($gb['boqueron']['slug']) ?>" class="text-yellow-300 underline hover:no-underline"><?= h($gb['boqueron']['name']) ?></a><?php else: ?>Boquerón Beach<?php endif; ?> — facilities, walkable town, typically calmer bay water.</p>
</div>
<div class="border-l-4 border-red-400 p-4 rounded-r-lg" style="background:rgba(15,23,42,0.55)">
<p class="font-bold text-white mb-1">Best Natural Rock Pool</p>
<p class="text-gray-300 text-sm"><?php if (isset($gb['puerto-nuevo'])): ?><a href="/beach/<?= h($gb['puerto-nuevo']['slug']) ?>" class="text-yellow-300 underline hover:no-underline"><?= h($gb['puerto-nuevo']['name']) ?></a><?php else: ?>Playa Puerto Nuevo<?php endif; ?> — protected natural pool with calm water on one side.</p>
</div>
<div class="border-l-4 border-ocean-400 p-4 rounded-r-lg" style="background:rgba(15,23,42,0.55)">
<p class="font-bold text-white mb-1">Best Scenic Excursion</p>
<p class="text-gray-300 text-sm"><?php if (isset($gb['flamenco'])): ?><a href="/beach/<?= h($gb['flamenco']['slug']) ?>" class="text-yellow-300 underline hover:no-underline"><?= h($gb['flamenco']['name']) ?></a><?php else: ?>Flamenco Beach<?php endif; ?> — worth the ferry for one of the Caribbean's most striking beaches.</p>
</div>
</div>

<h2 id="best-beaches" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Best Kid-Friendly Beaches in Puerto Rico</h2>

<!-- Luquillo -->
<h3 class="text-2xl font-bold text-gray-900 mt-10 mb-2"><?php if (isset($gb['luquillo'])): ?><a href="/beach/<?= h($gb['luquillo']['slug']) ?>" class="text-yellow-400 hover:text-yellow-300 no-underline hover:underline"><?= h($gb['luquillo']['name']) ?></a><?php else: ?>Luquillo Beach<?php endif; ?> / Balneario La Monserrate</h3>
<p class="text-sm text-gray-500 mb-4">Luquillo · ~45 min from San Juan</p>
<p class="mb-4">Luquillo is the beach most frequently recommended to families, and the recommendation holds up. The crescent of sand lined with coconut palms provides natural shade in spots. The famous kioskos (food stalls) just outside the beach area mean you do not need to pack every meal. Water conditions along the main balneario section are generally approachable under typical conditions.</p>
<div class="grid md:grid-cols-2 gap-4 mb-6">
<div><p class="font-bold text-palm-400 mb-2">Pros</p>
<ul class="list-disc list-inside space-y-1 text-gray-700 text-sm"><li>Maintained bathrooms and showers</li><li>Nearby kioskos for food and drinks</li><li>Parking on site (fee may apply)</li><li>Natural shade from palm trees along the tree line</li><li>Calm water under typical conditions</li></ul></div>
<div><p class="font-bold text-red-400 mb-2">Cons</p>
<ul class="list-disc list-inside space-y-1 text-gray-700 text-sm"><li>Gets busy on weekends and holidays</li><li>Not always calm — check conditions before going</li><li>Shade is uneven; bring your own if sitting farther from the tree line</li></ul></div>
</div>
<?php if (isset($gb['luquillo'])): $beach = $gb['luquillo']; include APP_ROOT . '/components/guide-beach-card.php'; endif; ?>

<!-- Carolina -->
<h3 class="text-2xl font-bold text-gray-900 mt-10 mb-2"><?php if (isset($gb['carolina'])): ?><a href="/beach/<?= h($gb['carolina']['slug']) ?>" class="text-yellow-400 hover:text-yellow-300 no-underline hover:underline"><?= h($gb['carolina']['name']) ?></a><?php else: ?>Balneario de Carolina<?php endif; ?></h3>
<p class="text-sm text-gray-500 mb-4">Carolina · Just east of Isla Verde</p>
<p class="mb-4">Balneario de Carolina sits just east of Isla Verde, making it one of the most accessible options for families staying in the San Juan metro area. A long stretch of sand with maintained facilities across multiple sections, including restrooms, lifeguards, and fenced parking.</p>
<div class="grid md:grid-cols-2 gap-4 mb-6">
<div><p class="font-bold text-palm-400 mb-2">Pros</p>
<ul class="list-disc list-inside space-y-1 text-gray-700 text-sm"><li>Close to San Juan hotels (Isla Verde and Carolina)</li><li>Restrooms, showers, and lifeguards during operating hours</li><li>Long beach stretch with room to spread out</li></ul></div>
<div><p class="font-bold text-red-400 mb-2">Cons</p>
<ul class="list-disc list-inside space-y-1 text-gray-700 text-sm"><li>Water conditions vary — surf can be stronger than Luquillo</li><li>Less natural shade; bring your own canopy</li></ul></div>
</div>
<?php if (isset($gb['carolina'])): $beach = $gb['carolina']; include APP_ROOT . '/components/guide-beach-card.php'; endif; ?>

<!-- Escambrón -->
<h3 class="text-2xl font-bold text-gray-900 mt-10 mb-2"><?php if (isset($gb['escambron'])): ?><a href="/beach/<?= h($gb['escambron']['slug']) ?>" class="text-yellow-400 hover:text-yellow-300 no-underline hover:underline"><?= h($gb['escambron']['name']) ?></a><?php else: ?>Playa El Escambrón<?php endif; ?></h3>
<p class="text-sm text-gray-500 mb-4">San Juan · Puerta de Tierra, near Old San Juan</p>
<p class="mb-4">A smaller, crescent-shaped beach in San Juan with a coral reef offshore that buffers wave energy. The water is often calmer than other nearby beaches. Free parking, restrooms, security, and Blue Flag beach status. Walking distance from Condado and Old San Juan — convenient for families without a car.</p>
<div class="grid md:grid-cols-2 gap-4 mb-6">
<div><p class="font-bold text-palm-400 mb-2">Pros</p>
<ul class="list-disc list-inside space-y-1 text-gray-700 text-sm"><li>Reef-buffered water — generally calmer than open-coast beaches</li><li>Free parking and restrooms</li><li>Walking distance from Condado and Old San Juan</li><li>Snorkeling for older kids along the reef</li></ul></div>
<div><p class="font-bold text-red-400 mb-2">Cons</p>
<ul class="list-disc list-inside space-y-1 text-gray-700 text-sm"><li>Smaller beach — can feel crowded on popular days</li><li>Urban setting, less "tropical escape" feel</li><li>Limited natural shade</li></ul></div>
</div>
<?php if (isset($gb['escambron'])): $beach = $gb['escambron']; include APP_ROOT . '/components/guide-beach-card.php'; endif; ?>

<!-- Boquerón -->
<h3 class="text-2xl font-bold text-gray-900 mt-10 mb-2"><?php if (isset($gb['boqueron'])): ?><a href="/beach/<?= h($gb['boqueron']['slug']) ?>" class="text-yellow-400 hover:text-yellow-300 no-underline hover:underline"><?= h($gb['boqueron']['name']) ?></a><?php else: ?>Boquerón Beach<?php endif; ?></h3>
<p class="text-sm text-gray-500 mb-4">Cabo Rojo · Southwest coast</p>
<p class="mb-4">A balneario on Puerto Rico's west coast with a reputation for easier parking, maintained facilities, and typically calmer bay water. The town of Boquerón is within walking distance, adding food options without requiring a car trip.</p>
<div class="grid md:grid-cols-2 gap-4 mb-6">
<div><p class="font-bold text-palm-400 mb-2">Pros</p>
<ul class="list-disc list-inside space-y-1 text-gray-700 text-sm"><li>Bathrooms, showers, and parking at the balneario</li><li>Walkable town nearby with restaurants and shops</li><li>Generally calmer water along the bay</li></ul></div>
<div><p class="font-bold text-red-400 mb-2">Cons</p>
<ul class="list-disc list-inside space-y-1 text-gray-700 text-sm"><li>Far from San Juan (~2.5-hour drive)</li><li>Can get busy on weekends with local families</li></ul></div>
</div>
<?php if (isset($gb['boqueron'])): $beach = $gb['boqueron']; include APP_ROOT . '/components/guide-beach-card.php'; endif; ?>

<!-- Playa Caracas -->
<h3 class="text-2xl font-bold text-gray-900 mt-10 mb-2"><?php if (isset($gb['caracas'])): ?><a href="/beach/<?= h($gb['caracas']['slug']) ?>" class="text-yellow-400 hover:text-yellow-300 no-underline hover:underline"><?= h($gb['caracas']['name']) ?></a><?php else: ?>Playa Caracas<?php endif; ?></h3>
<p class="text-sm text-gray-500 mb-4">Vieques · Within the National Wildlife Refuge</p>
<p class="mb-4">Also called Red Beach, Playa Caracas is located within the Vieques National Wildlife Refuge. The sheltered bay typically offers gentler conditions than open-coast beaches, in a scenic, uncrowded setting compared to mainland balnearios.</p>
<div class="grid md:grid-cols-2 gap-4 mb-6">
<div><p class="font-bold text-palm-400 mb-2">Pros</p>
<ul class="list-disc list-inside space-y-1 text-gray-700 text-sm"><li>Generally calm water in a sheltered bay</li><li>Scenic, uncrowded setting</li><li>Basic facilities including portable restrooms</li></ul></div>
<div><p class="font-bold text-red-400 mb-2">Cons</p>
<ul class="list-disc list-inside space-y-1 text-gray-700 text-sm"><li>Requires travel to Vieques by ferry or small plane</li><li>Fewer facilities than mainland balnearios</li><li>Limited shade and no food access — pack everything</li></ul></div>
</div>
<?php if (isset($gb['caracas'])): $beach = $gb['caracas']; include APP_ROOT . '/components/guide-beach-card.php'; endif; ?>

<!-- Combate -->
<h3 class="text-2xl font-bold text-gray-900 mt-10 mb-2"><?php if (isset($gb['combate'])): ?><a href="/beach/<?= h($gb['combate']['slug']) ?>" class="text-yellow-400 hover:text-yellow-300 no-underline hover:underline"><?= h($gb['combate']['name']) ?></a><?php else: ?>Combate Beach<?php endif; ?></h3>
<p class="text-sm text-gray-500 mb-4">Cabo Rojo · Near the salt flats</p>
<p class="mb-4">Another west coast option near Cabo Rojo with a reputation for calmer, shallower water along parts of its stretch. More relaxed atmosphere than busier balnearios, with small restaurants along the road for food access. Pairs well with a visit to the Cabo Rojo salt flats.</p>
<div class="grid md:grid-cols-2 gap-4 mb-6">
<div><p class="font-bold text-palm-400 mb-2">Pros</p>
<ul class="list-disc list-inside space-y-1 text-gray-700 text-sm"><li>Shallow, generally calm water along sections</li><li>Nearby food options along the beachfront road</li><li>Less crowded than metro-area beaches</li></ul></div>
<div><p class="font-bold text-red-400 mb-2">Cons</p>
<ul class="list-disc list-inside space-y-1 text-gray-700 text-sm"><li>Limited formal facilities</li><li>Far from San Juan — southwest coast option only</li><li>Parking can be informal</li></ul></div>
</div>
<?php if (isset($gb['combate'])): $beach = $gb['combate']; include APP_ROOT . '/components/guide-beach-card.php'; endif; ?>

<!-- Flamenco -->
<h3 class="text-2xl font-bold text-gray-900 mt-10 mb-2"><?php if (isset($gb['flamenco'])): ?><a href="/beach/<?= h($gb['flamenco']['slug']) ?>" class="text-yellow-400 hover:text-yellow-300 no-underline hover:underline"><?= h($gb['flamenco']['name']) ?></a><?php else: ?>Flamenco Beach<?php endif; ?></h3>
<p class="text-sm text-gray-500 mb-4">Culebra · Ferry from Ceiba</p>
<p class="mb-4">Flamenco consistently appears on "best beaches" lists, and the turquoise water and white sand genuinely live up to the reputation. The beach has restrooms, some shade structures, and food vendors, making it more equipped than many remote island beaches. Generally calm water along the main swimming stretch.</p>
<div class="grid md:grid-cols-2 gap-4 mb-6">
<div><p class="font-bold text-palm-400 mb-2">Pros</p>
<ul class="list-disc list-inside space-y-1 text-gray-700 text-sm"><li>Strikingly beautiful water and sand</li><li>Restrooms and food vendors on-site</li><li>Generally calm water along the main stretch</li></ul></div>
<div><p class="font-bold text-red-400 mb-2">Cons</p>
<ul class="list-disc list-inside space-y-1 text-gray-700 text-sm"><li>Requires ferry (~45 min each way) or small plane</li><li>Ferry reservations fill up on weekends and peak season</li><li>Full-day commitment — may be exhausting for very young children</li></ul></div>
</div>
<?php if (isset($gb['flamenco'])): $beach = $gb['flamenco']; include APP_ROOT . '/components/guide-beach-card.php'; endif; ?>

<!-- Isla Verde -->
<h3 class="text-2xl font-bold text-gray-900 mt-10 mb-2"><?php if (isset($gb['isla-verde'])): ?><a href="/beach/<?= h($gb['isla-verde']['slug']) ?>" class="text-yellow-400 hover:text-yellow-300 no-underline hover:underline"><?= h($gb['isla-verde']['name']) ?></a><?php else: ?>Isla Verde Beach<?php endif; ?></h3>
<p class="text-sm text-gray-500 mb-4">Carolina · Hotel zone</p>
<p class="mb-4">The Isla Verde area includes a mix of hotel-fronted beach and balneario-managed sections. Balneario sections offer bathrooms, showers, and parking. The hotel stretches offer proximity and convenience if you are already staying in the area. A low-effort option, not a destination beach.</p>
<div class="grid md:grid-cols-2 gap-4 mb-6">
<div><p class="font-bold text-palm-400 mb-2">Pros</p>
<ul class="list-disc list-inside space-y-1 text-gray-700 text-sm"><li>Walk-out convenience for families at Isla Verde hotels</li><li>Balneario sections have restrooms and showers</li><li>Restaurants and shops along the strip</li></ul></div>
<div><p class="font-bold text-red-400 mb-2">Cons</p>
<ul class="list-disc list-inside space-y-1 text-gray-700 text-sm"><li>Water conditions vary significantly by stretch and day</li><li>Some sections can have stronger currents</li><li>Can feel commercial along hotel-heavy sections</li></ul></div>
</div>
<?php if (isset($gb['isla-verde'])): $beach = $gb['isla-verde']; include APP_ROOT . '/components/guide-beach-card.php'; endif; ?>

<h2 id="more-beaches" class="text-3xl font-bold text-gray-900 mt-12 mb-6">More Beaches Worth Knowing About</h2>
<p class="mb-6">These beaches may not appear on every tourist list, but they offer unique features that make them excellent choices for families with kids.</p>

<!-- Puerto Nuevo -->
<div class="rounded-lg p-6 mb-6" style="background:rgba(15,23,42,0.55);border:1px solid rgba(255,255,255,0.1)">
<h3 class="text-xl font-bold text-gray-900 mb-2"><?php if (isset($gb['puerto-nuevo'])): ?><a href="/beach/<?= h($gb['puerto-nuevo']['slug']) ?>" class="text-yellow-400 hover:text-yellow-300 no-underline hover:underline"><?= h($gb['puerto-nuevo']['name']) ?></a><?php else: ?>Playa Puerto Nuevo<?php endif; ?></h3>
<p class="text-sm text-gray-400 mb-3">Vega Baja · North coast</p>
<p class="text-gray-300">Features a natural rock pool on one side with calm, protected waters — essentially a natural kiddie pool. The other side faces the open ocean. The balneario section has parking (small fee). The pool can get deep in spots, so keep an eye on smaller children, but it is one of the most naturally sheltered swimming areas on the island.</p>
</div>
<?php if (isset($gb['puerto-nuevo'])): $beach = $gb['puerto-nuevo']; include APP_ROOT . '/components/guide-beach-card.php'; endif; ?>

<!-- Montones -->
<div class="rounded-lg p-6 mb-6" style="background:rgba(15,23,42,0.55);border:1px solid rgba(255,255,255,0.1)">
<h3 class="text-xl font-bold text-gray-900 mb-2"><?php if (isset($gb['montones'])): ?><a href="/beach/<?= h($gb['montones']['slug']) ?>" class="text-yellow-400 hover:text-yellow-300 no-underline hover:underline"><?= h($gb['montones']['name']) ?></a><?php else: ?>Playa Montones<?php endif; ?></h3>
<p class="text-sm text-gray-400 mb-3">Isabela · Northwest coast</p>
<p class="text-gray-300">A shallow natural pond created by rocks, typically 1–3 feet deep — excellent for toddlers. There is also a deeper section for snorkeling with visible marine life. Wear beach sandals near the rocks (sea urchins are present). Parking available at Villa del Mar Hau Resort for a small fee.</p>
</div>
<?php if (isset($gb['montones'])): $beach = $gb['montones']; include APP_ROOT . '/components/guide-beach-card.php'; endif; ?>

<!-- Cerro Gordo -->
<div class="rounded-lg p-6 mb-6" style="background:rgba(15,23,42,0.55);border:1px solid rgba(255,255,255,0.1)">
<h3 class="text-xl font-bold text-gray-900 mb-2">Balneario Cerro Gordo</h3>
<p class="text-sm text-gray-400 mb-3">Vega Alta · North coast</p>
<p class="text-gray-300">Calm water with a family atmosphere. This balneario has official parking, bathrooms, showers, lifeguards, and picnic areas with BBQ grills — making it one of the best-equipped options for a full family day out. There is also a nearby ocean-view trail for older kids who want to explore.</p>
</div>

<!-- La Posita de Piñones -->
<div class="rounded-lg p-6 mb-6" style="background:rgba(15,23,42,0.55);border:1px solid rgba(255,255,255,0.1)">
<h3 class="text-xl font-bold text-gray-900 mb-2">La Posita de Piñones</h3>
<p class="text-sm text-gray-400 mb-3">Loíza · ~25 min from San Juan</p>
<p class="text-gray-300">A natural rock barrier creates a shallow, calm pool perfect for babies and toddlers. The famous Piñones kiosks are nearby with local Puerto Rican street food — some of the best on the island. Street parking only; can be crowded on weekends. One of the closest "natural pool" options to San Juan.</p>
</div>

<!-- Buyé -->
<div class="rounded-lg p-6 mb-6" style="background:rgba(15,23,42,0.55);border:1px solid rgba(255,255,255,0.1)">
<h3 class="text-xl font-bold text-gray-900 mb-2"><?php if (isset($gb['buye'])): ?><a href="/beach/<?= h($gb['buye']['slug']) ?>" class="text-yellow-400 hover:text-yellow-300 no-underline hover:underline"><?= h($gb['buye']['name']) ?></a><?php else: ?>Playa Buyé<?php endif; ?></h3>
<p class="text-sm text-gray-400 mb-3">Cabo Rojo · Southwest coast</p>
<p class="text-gray-300">Gentle waves and soft sand with calm Caribbean waters ideal for families. Usually has gentle waves and a relaxed atmosphere. Private parking lot (fee) or street parking available. A quieter alternative to Boquerón for families already on the west coast.</p>
</div>
<?php if (isset($gb['buye'])): $beach = $gb['buye']; include APP_ROOT . '/components/guide-beach-card.php'; endif; ?>

<!-- Seven Seas -->
<div class="rounded-lg p-6 mb-6" style="background:rgba(15,23,42,0.55);border:1px solid rgba(255,255,255,0.1)">
<h3 class="text-xl font-bold text-gray-900 mb-2"><?php if (isset($gb['seven-seas'])): ?><a href="/beach/<?= h($gb['seven-seas']['slug']) ?>" class="text-yellow-400 hover:text-yellow-300 no-underline hover:underline"><?= h($gb['seven-seas']['name']) ?></a><?php else: ?>Playa Seven Seas<?php endif; ?></h3>
<p class="text-sm text-gray-400 mb-3">Fajardo · East coast</p>
<p class="text-gray-300">Calm, clear blue waters with plenty of space and an active family atmosphere. Official parking lot (fee), bathrooms, showers, and kiosks. Arrive early on weekends. Bonus: there is a short trail to a hidden beach called Playa Escondida for families with older kids who want a mini adventure.</p>
</div>
<?php if (isset($gb['seven-seas'])): $beach = $gb['seven-seas']; include APP_ROOT . '/components/guide-beach-card.php'; endif; ?>

<!-- Crash Boat -->
<div class="rounded-lg p-6 mb-6" style="background:rgba(15,23,42,0.55);border:1px solid rgba(255,255,255,0.1)">
<h3 class="text-xl font-bold text-gray-900 mb-2"><?php if (isset($gb['crash-boat'])): ?><a href="/beach/<?= h($gb['crash-boat']['slug']) ?>" class="text-yellow-400 hover:text-yellow-300 no-underline hover:underline"><?= h($gb['crash-boat']['name']) ?></a><?php else: ?>Playa Crash Boat<?php endif; ?></h3>
<p class="text-sm text-gray-400 mb-3">Aguadilla · Northwest coast</p>
<p class="text-gray-300">Shallow swimming near shore with turquoise water. Snorkeling by the pier with visible fish. Older kids enjoy pier jumping. Colorful boats and a classic Puerto Rican atmosphere. Free public parking but arrive early for good spots. Food kiosks nearby. The beach is lively into the evening.</p>
</div>
<?php if (isset($gb['crash-boat'])): $beach = $gb['crash-boat']; include APP_ROOT . '/components/guide-beach-card.php'; endif; ?>

<h2 id="comparison" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Beach Comparison Table</h2>
<div class="overflow-x-auto mb-8 rounded-xl" style="border:1px solid rgba(255,255,255,0.1)">
<table class="w-full text-sm">
<thead>
<tr style="background:rgba(250,204,21,0.12)">
<th class="text-left px-4 py-3 font-bold text-yellow-300 border-b border-white/10 whitespace-nowrap">Beach</th>
<th class="text-left px-4 py-3 font-bold text-yellow-300 border-b border-white/10">Location</th>
<th class="text-center px-4 py-3 font-bold text-yellow-300 border-b border-white/10">Water</th>
<th class="text-center px-3 py-3 font-bold text-yellow-300 border-b border-white/10">Restrooms</th>
<th class="text-center px-3 py-3 font-bold text-yellow-300 border-b border-white/10">Shade</th>
<th class="text-center px-3 py-3 font-bold text-yellow-300 border-b border-white/10">Food</th>
<th class="text-left px-4 py-3 font-bold text-yellow-300 border-b border-white/10 w-full">Best For</th>
</tr>
</thead>
<tbody class="text-gray-300">
<?php
$tableBeaches = [
    ['name' => 'Luquillo', 'key' => 'luquillo', 'loc' => 'Luquillo', 'water' => 'Calm', 'waterColor' => 'green', 'wc' => 3, 'shade' => 2, 'food' => 3, 'best' => 'Best all-around'],
    ['name' => 'Carolina', 'key' => 'carolina', 'loc' => 'Carolina', 'water' => 'Variable', 'waterColor' => 'yellow', 'wc' => 3, 'shade' => 1, 'food' => 2, 'best' => 'Near San Juan'],
    ['name' => 'Escambrón', 'key' => 'escambron', 'loc' => 'San Juan', 'water' => 'Calm (reef)', 'waterColor' => 'green', 'wc' => 3, 'shade' => 1, 'food' => 2, 'best' => 'Toddlers in SJ'],
    ['name' => 'Boquerón', 'key' => 'boqueron', 'loc' => 'Cabo Rojo', 'water' => 'Calm', 'waterColor' => 'green', 'wc' => 3, 'shade' => 2, 'food' => 3, 'best' => 'West coast'],
    ['name' => 'Caracas', 'key' => 'caracas', 'loc' => 'Vieques', 'water' => 'Calm', 'waterColor' => 'green', 'wc' => 1, 'shade' => 1, 'food' => 0, 'best' => 'Vieques trips'],
    ['name' => 'Combate', 'key' => 'combate', 'loc' => 'Cabo Rojo', 'water' => 'Shallow', 'waterColor' => 'green', 'wc' => 1, 'shade' => 1, 'food' => 2, 'best' => 'Southwest outing'],
    ['name' => 'Flamenco', 'key' => 'flamenco', 'loc' => 'Culebra', 'water' => 'Calm', 'waterColor' => 'green', 'wc' => 3, 'shade' => 2, 'food' => 2, 'best' => 'Day excursion'],
    ['name' => 'Isla Verde', 'key' => 'isla-verde', 'loc' => 'Carolina', 'water' => 'Variable', 'waterColor' => 'yellow', 'wc' => 2, 'shade' => 1, 'food' => 3, 'best' => 'Hotel convenience'],
    ['name' => 'Puerto Nuevo', 'key' => 'puerto-nuevo', 'loc' => 'Vega Baja', 'water' => 'Rock pool', 'waterColor' => 'green', 'wc' => 3, 'shade' => 1, 'food' => 1, 'best' => 'Natural rock pool'],
    ['name' => 'Montones', 'key' => 'montones', 'loc' => 'Isabela', 'water' => 'Pond', 'waterColor' => 'green', 'wc' => 1, 'shade' => 1, 'food' => 1, 'best' => 'Toddler pond'],
    ['name' => 'Cerro Gordo', 'key' => null, 'loc' => 'Vega Alta', 'water' => 'Calm', 'waterColor' => 'green', 'wc' => 3, 'shade' => 2, 'food' => 2, 'best' => 'Full-day outing'],
    ['name' => 'La Posita', 'key' => null, 'loc' => 'Loíza', 'water' => 'Rock pool', 'waterColor' => 'green', 'wc' => 1, 'shade' => 1, 'food' => 3, 'best' => 'Babies near SJ'],
    ['name' => 'Buyé', 'key' => 'buye', 'loc' => 'Cabo Rojo', 'water' => 'Gentle', 'waterColor' => 'green', 'wc' => 1, 'shade' => 1, 'food' => 1, 'best' => 'Quiet west coast'],
    ['name' => 'Seven Seas', 'key' => 'seven-seas', 'loc' => 'Fajardo', 'water' => 'Calm', 'waterColor' => 'green', 'wc' => 3, 'shade' => 2, 'food' => 2, 'best' => 'East coast families'],
    ['name' => 'Crash Boat', 'key' => 'crash-boat', 'loc' => 'Aguadilla', 'water' => 'Shallow', 'waterColor' => 'green', 'wc' => 1, 'shade' => 1, 'food' => 2, 'best' => 'Snorkeling + pier'],
];
$dots = ['', '&#9679;', '&#9679;&#9679;', '&#9679;&#9679;&#9679;'];
$dotColors = [
    0 => 'text-gray-600',
    1 => 'text-red-400',
    2 => 'text-yellow-400',
    3 => 'text-palm-400',
];
$waterBadge = [
    'green' => 'background:rgba(34,197,94,0.15);color:#86efac;',
    'yellow' => 'background:rgba(250,204,21,0.15);color:#facc15;',
];
foreach ($tableBeaches as $i => $tb):
    $rowBg = $i % 2 === 0 ? 'background:rgba(15,23,42,0.4)' : 'background:rgba(15,23,42,0.65)';
    $beachLink = ($tb['key'] && isset($gb[$tb['key']])) ? '/beach/' . h($gb[$tb['key']]['slug']) : null;
?>
<tr style="<?= $rowBg ?>" class="border-b border-white/5 hover:brightness-125 transition-all">
<td class="px-4 py-3 font-semibold text-white whitespace-nowrap"><?php if ($beachLink): ?><a href="<?= $beachLink ?>" class="text-white hover:text-yellow-300 no-underline hover:underline"><?= h($tb['name']) ?></a><?php else: echo h($tb['name']); endif; ?></td>
<td class="px-4 py-3 text-gray-400 whitespace-nowrap"><?= h($tb['loc']) ?></td>
<td class="px-4 py-3 text-center"><span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap" style="<?= $waterBadge[$tb['waterColor']] ?>"><?= h($tb['water']) ?></span></td>
<td class="px-3 py-3 text-center <?= $dotColors[$tb['wc']] ?>"><?= $tb['wc'] > 0 ? $dots[$tb['wc']] : '<span class="text-gray-600">—</span>' ?></td>
<td class="px-3 py-3 text-center <?= $dotColors[$tb['shade']] ?>"><?= $tb['shade'] > 0 ? $dots[$tb['shade']] : '<span class="text-gray-600">—</span>' ?></td>
<td class="px-3 py-3 text-center <?= $dotColors[$tb['food']] ?>"><?= $tb['food'] > 0 ? $dots[$tb['food']] : '<span class="text-gray-600">—</span>' ?></td>
<td class="px-4 py-3 text-gray-200 whitespace-nowrap"><?= h($tb['best']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<p class="text-xs text-gray-500 mb-8"><span class="text-palm-400">&#9679;&#9679;&#9679;</span> Full facilities &nbsp; <span class="text-yellow-400">&#9679;&#9679;</span> Some/partial &nbsp; <span class="text-red-400">&#9679;</span> Basic/limited &nbsp; <span class="text-gray-600">—</span> None</p>

<h2 id="by-category" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Beaches by Category</h2>

<h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Best Near San Juan</h3>
<p class="mb-4"><strong>Playa El Escambrón</strong> is the closest option for families in Old San Juan or Condado, with the calmest typical water conditions among metro beaches thanks to its offshore reef.</p>
<p class="mb-4"><strong>Balneario de Carolina</strong> is a short drive from Isla Verde with a longer stretch of sand and maintained facilities. Water conditions are less predictable, so it works better on calmer days.</p>
<p class="mb-4"><strong>La Posita de Piñones</strong> is about 25 minutes from San Juan and offers a natural rock barrier pool perfect for babies and toddlers, with amazing local food at the Piñones kiosks.</p>
<p class="mb-6"><strong>Luquillo</strong> is roughly 45 minutes east and offers the most complete package. The drive is worth it for families who want a full beach day with food, shade, and facilities. Weekdays are much easier for parking.</p>

<h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Best for Toddlers</h3>
<p class="mb-4">Parents with toddlers need calm water for safe wading, bathrooms close enough for emergencies, short walks from parking to sand, and enough space for easy supervision.</p>
<ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
<li><strong>Playa El Escambrón</strong> — reef-protected calm water, compact beach for easy supervision, restrooms nearby</li>
<li><strong>Playa Puerto Nuevo</strong> — natural rock pool creates a protected wading area, essentially a natural kiddie pool</li>
<li><strong>Playa Montones</strong> — shallow natural pond (1–3 feet deep), excellent for toddlers; wear beach sandals near rocks</li>
<li><strong>Luquillo</strong> — calm water on typical days, shade near the tree line, bathrooms and kioskos for snacks</li>
<li><strong>La Posita de Piñones</strong> — rock barrier creates a shallow calm pool perfect for babies</li>
<li><strong>Boquerón</strong> — calmer bay water with nearby facilities</li>
</ul>
<p class="mb-6"><strong>For any beach, check conditions that morning.</strong> Toddlers and surf do not mix, regardless of a beach's typical reputation.</p>

<h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">Best With Full Facilities</h3>
<p class="mb-4">If your top priority is minimizing logistical friction, the balnearios are your starting point.</p>
<ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
<li><strong>Luquillo (Balneario La Monserrate)</strong> — bathrooms, showers, parking, shade, food vendors. Checks more boxes than any other beach.</li>
<li><strong>Balneario de Carolina</strong> — restrooms, lifeguards, fenced parking. Shade is limited.</li>
<li><strong>Balneario Cerro Gordo</strong> — bathrooms, showers, lifeguards, picnic areas with BBQ grills, ocean-view trail.</li>
<li><strong>Playa El Escambrón</strong> — free parking and restrooms in a compact setting.</li>
<li><strong>Seven Seas</strong> — parking, bathrooms, showers, kiosks. Full facility beach on the east coast.</li>
<li><strong>Boquerón</strong> — bathrooms, showers, parking, plus a walkable town for food and supplies.</li>
</ul>

<h2 id="tips" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Tips for Beach Days With Kids</h2>

<div class="grid md:grid-cols-2 gap-4 mb-8">
<div class="rounded-lg p-5" style="background:rgba(15,23,42,0.55);border:1px solid rgba(255,255,255,0.1)">
<p class="font-bold text-white mb-2">Go Early</p>
<p class="text-gray-300 text-sm">Arriving before 9 or 10 a.m. gives you better parking, more shade options, and smaller crowds. The midday sun in Puerto Rico is intense — early starts let you wrap up before the hottest hours.</p>
</div>
<div class="rounded-lg p-5" style="background:rgba(15,23,42,0.55);border:1px solid rgba(255,255,255,0.1)">
<p class="font-bold text-white mb-2">Bring Your Own Shade</p>
<p class="text-gray-300 text-sm">A portable pop-up canopy or beach tent is worth its luggage space. Natural shade is limited at most beaches. Do not rely on finding shade when you arrive.</p>
</div>
<div class="rounded-lg p-5" style="background:rgba(15,23,42,0.55);border:1px solid rgba(255,255,255,0.1)">
<p class="font-bold text-white mb-2">Check Conditions That Morning</p>
<p class="text-gray-300 text-sm">Surf reports and beach flag conditions can change overnight. A two-minute check before you load the car can save you from arriving at a beach that is too rough for kids.</p>
</div>
<div class="rounded-lg p-5" style="background:rgba(15,23,42,0.55);border:1px solid rgba(255,255,255,0.1)">
<p class="font-bold text-white mb-2">Go on Weekdays</p>
<p class="text-gray-300 text-sm">If your schedule allows it, visiting popular balnearios on a weekday dramatically reduces crowds and parking stress, especially at Luquillo and Boquerón.</p>
</div>
<div class="rounded-lg p-5" style="background:rgba(15,23,42,0.55);border:1px solid rgba(255,255,255,0.1)">
<p class="font-bold text-white mb-2">Pack Reef-Safe Sunscreen</p>
<p class="text-gray-300 text-sm">Puerto Rico's reefs benefit from reef-safe formulas. Also bring sun shirts/rash guards for kids — they provide better protection than sunscreen alone.</p>
</div>
<div class="rounded-lg p-5" style="background:rgba(15,23,42,0.55);border:1px solid rgba(255,255,255,0.1)">
<p class="font-bold text-white mb-2">Bring Cash for Parking</p>
<p class="text-gray-300 text-sm">Several balnearios charge a small parking fee ($3–5). Having a few dollars in cash avoids the frustration of arriving at a gated lot without the right payment.</p>
</div>
</div>

<h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">What to Pack</h3>
<ul class="list-disc list-inside space-y-2 text-gray-700 mb-6">
<li>Reef-safe sunscreen and sun shirts/rash guards</li>
<li>Pop-up shade tent or beach umbrella</li>
<li>Water shoes or beach sandals (especially for rocky beaches like Montones)</li>
<li>Waterproof phone case</li>
<li>Bug spray (for sunset when insects come out)</li>
<li>Mesh backpack (drains sand and water)</li>
<li>Snacks and water — not all beaches have food vendors</li>
<li>Life jackets for non-swimmers</li>
</ul>

<h3 class="text-2xl font-bold text-gray-900 mt-8 mb-4">A Note on Rough-Water Days</h3>
<p class="mb-6">No beach on this list is always safe for swimming. Even beaches described as "generally calm" can have strong currents or surf on a given day. Before heading out, check the National Weather Service marine forecast for Puerto Rico and look for flag warnings posted at the beach. If red or double-red flags are flying, keep kids out of the water regardless of the beach's reputation.</p>

<h2 id="faq" class="text-3xl font-bold text-gray-900 mt-12 mb-6">Frequently Asked Questions</h2>
<div class="space-y-6"><?php foreach ($faqs as $faq): ?>
<div class="border-l-4 border-yellow-400 pl-4"><h3 class="text-xl font-bold text-gray-900 mb-2"><?= h($faq['question']) ?></h3><p class="text-gray-300"><?= h($faq['answer']) ?></p></div>
<?php endforeach; ?></div>

<?php
$guideMapIds = $kidMapBeachIds;
$guideMapTitle = 'Find Kid-Friendly Beaches on the Map';
$guideMapDescription = 'Browse the beaches from this guide on an interactive map to plan your family beach day.';
$guideMapButtonLabel = 'View Beach Map';
$guideMapEmptyNotice = 'No kid-friendly beaches from this guide are available on the map right now.';
include APP_ROOT . '/components/guide-map-panel.php';
?></div>
<div class="mt-12 pt-8 border-t border-gray-200"><h3 class="text-xl font-bold text-gray-900 mb-4">Related Guides</h3>
<div class="related-guides-grid"><?php foreach ($relatedGuides as $guide): ?>
<a href="/guides/<?= h($guide['slug']) ?>" class="related-guide-card"><span class="related-guide-title"><?= h($guide['title']) ?></span></a>
<?php endforeach; ?></div></div>
</article></div></main>

<?php
$pageShellMode = "end";
include APP_ROOT . "/components/page-shell.php";
?>
