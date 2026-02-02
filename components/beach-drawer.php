<?php
/**
 * Beach Details Drawer Content
 * Loaded via HTMX into the drawer overlay
 *
 * @param array $beach - Full beach data
 * @param array|null $weather - Weather data (optional)
 * @param array $reviews - User reviews
 * @param array $safety - Safety information
 */

require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/constants.php';
require_once __DIR__ . '/../inc/geo.php';
require_once __DIR__ . '/../inc/weather.php';

// $beach should be set before including this file
$beach = $beach ?? [];
$reviews = $reviews ?? [];
$safety = $safety ?? [];

$name = $beach['name'] ?? 'Unknown Beach';
$municipality = $beach['municipality'] ?? '';
$description = $beach['description'] ?? '';
$coverImage = $beach['cover_image'] ?? '';
$accessLabel = $beach['access_label'] ?? '';
$notes = $beach['notes'] ?? '';
$sargassum = $beach['sargassum'] ?? null;
$surf = $beach['surf'] ?? null;
$wind = $beach['wind'] ?? null;
$googleRating = $beach['google_rating'] ?? null;
$googleReviewCount = $beach['google_review_count'] ?? 0;
$lat = $beach['lat'] ?? 0;
$lng = $beach['lng'] ?? 0;
$parkingDetails = $beach['parking_details'] ?? '';
$safetyInfo = $beach['safety_info'] ?? '';
$bestTime = $beach['best_time'] ?? '';

// User ratings
$avgUserRating = $beach['avg_user_rating'] ?? null;
$userReviewCount = $beach['user_review_count'] ?? 0;

// Safety data
$swimDifficulty = $beach['swim_difficulty'] ?? $safety['swim_difficulty'] ?? 3;
$hasLifeguard = $beach['has_lifeguard'] ?? $safety['has_lifeguard'] ?? 0;
$safeForChildren = $beach['safe_for_children'] ?? $safety['safe_for_children'] ?? 1;

// Related data
$tags = $beach['tags'] ?? [];
$amenities = $beach['amenities'] ?? [];
$gallery = $beach['gallery'] ?? [];
$features = $beach['features'] ?? [];
$tips = $beach['tips'] ?? [];

// Get WebP version of cover image
$webpImage = getWebPImage($coverImage);

// Fetch weather for this beach
$weather = null;
if ($lat && $lng) {
    $weather = getWeatherForLocation((float)$lat, (float)$lng);
}
?>

<!-- Drawer Handle (mobile) -->
<div class="drawer-handle md:hidden" aria-hidden="true"></div>

<!-- Header with Image -->
<div class="relative h-48 md:h-64 overflow-hidden">
    <?php if ($coverImage): ?>
    <picture>
        <?php if ($webpImage['webp']): ?>
        <source srcset="<?= h($webpImage['webp']) ?>" type="image/webp">
        <?php endif; ?>
        <img src="<?= h($coverImage) ?>" alt="<?= h($name) ?>" class="w-full h-full object-cover">
    </picture>
    <?php else: ?>
    <div class="w-full h-full bg-gradient-to-br from-brand-dark to-brand-darker flex items-center justify-center">
        <i data-lucide="umbrella" class="w-16 h-16 text-brand-yellow/80" aria-hidden="true"></i>
    </div>
    <?php endif; ?>

    <!-- Gradient overlay -->
    <div class="absolute inset-0 bg-gradient-to-t from-brand-darker via-brand-darker/60 to-transparent"></div>

    <!-- Close button -->
    <button onclick="closeBeachDrawer()"
            class="absolute top-4 right-4 w-10 h-10 flex items-center justify-center rounded-full bg-black/50 backdrop-blur-sm text-white hover:bg-black/70 transition-colors border border-white/10"
            aria-label="Close drawer">
        <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
    </button>

    <!-- Title overlay -->
    <div class="absolute bottom-0 left-0 right-0 p-4 text-white">
        <span class="text-xs text-brand-yellow uppercase tracking-wider font-medium"><?= h($municipality) ?></span>
        <h2 class="text-2xl font-bold mt-0.5"><?= h($name) ?></h2>
    </div>

    <!-- Weather Badge (compact) -->
    <?php if ($weather && isset($weather['current'])): ?>
    <div class="absolute top-4 left-4 bg-black/50 backdrop-blur-sm rounded-lg px-3 py-2 border border-white/10">
        <div class="flex items-center gap-2">
            <span class="text-xl"><?= $weather['current']['icon'] ?></span>
            <div>
                <div class="font-semibold text-brand-yellow"><?= round($weather['current']['temperature']) ?>°F</div>
                <div class="text-xs text-gray-300"><?= h($weather['current']['description']) ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Content -->
<div class="p-4 md:p-6 space-y-6 bg-brand-darker">

    <!-- Ratings Row -->
    <div class="drawer-badges flex flex-wrap gap-2 sm:gap-3">
        <!-- Google Rating -->
        <?php if ($googleRating): ?>
        <div class="flex items-center gap-1.5 bg-white/10 border border-white/10 px-3 py-1.5 rounded-full" aria-label="Google rating: <?= number_format($googleRating, 1) ?> out of 5">
            <svg class="w-4 h-4" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#FACC15" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
            <span class="font-semibold text-brand-yellow"><?= number_format($googleRating, 1) ?></span>
            <span class="text-gray-400 text-xs font-medium">Google</span>
            <span class="text-gray-500 text-xs">(<?= number_format($googleReviewCount) ?>)</span>
        </div>
        <?php endif; ?>

        <!-- Community Rating -->
        <?php if ($avgUserRating): ?>
        <div class="flex items-center gap-1.5 bg-white/10 border border-white/10 px-3 py-1.5 rounded-full" aria-label="Community rating: <?= number_format($avgUserRating, 1) ?> out of 5">
            <i data-lucide="star" class="w-4 h-4 text-brand-yellow fill-brand-yellow" aria-hidden="true"></i>
            <span class="font-semibold text-brand-yellow"><?= number_format($avgUserRating, 1) ?></span>
            <span class="text-gray-400 text-xs font-medium">Community</span>
            <span class="text-gray-500 text-xs">(<?= $userReviewCount ?>)</span>
        </div>
        <?php endif; ?>

        <!-- Safety Badges -->
        <?php if ($hasLifeguard): ?>
        <div class="flex items-center gap-1 bg-green-500/20 text-green-400 px-3 py-1.5 rounded-full text-sm border border-green-500/30">
            <i data-lucide="life-buoy" class="w-4 h-4" aria-hidden="true"></i>
            <span>Lifeguard</span>
        </div>
        <?php endif; ?>

        <?php if ($safeForChildren): ?>
        <div class="flex items-center gap-1 bg-purple-500/20 text-purple-400 px-3 py-1.5 rounded-full text-sm border border-purple-500/30">
            <i data-lucide="users" class="w-4 h-4" aria-hidden="true"></i>
            <span>Family Friendly</span>
        </div>
        <?php endif; ?>

        <!-- Swim Difficulty -->
        <div class="flex items-center gap-1 px-3 py-1.5 rounded-full text-sm <?= getSwimDifficultyClassDark($swimDifficulty) ?>">
            <i data-lucide="waves" class="w-4 h-4" aria-hidden="true"></i>
            <span><?= getSwimDifficultyLabel($swimDifficulty) ?></span>
        </div>
    </div>

    <!-- Tags -->
    <?php if (!empty($tags)): ?>
    <div class="flex flex-wrap gap-2">
        <?php foreach ($tags as $tag): ?>
        <span class="inline-block bg-brand-yellow/10 text-brand-yellow text-sm px-3 py-1 rounded-full border border-brand-yellow/20">
            <?= h(getTagLabel($tag)) ?>
        </span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Weather Section (Full) -->
    <?php if ($weather): ?>
    <div>
        <h3 class="font-semibold text-white mb-3 flex items-center gap-2">
            <i data-lucide="cloud-sun" class="w-5 h-5 text-brand-yellow" aria-hidden="true"></i>
            <span>Today's Weather</span>
        </h3>
        <?php
        $size = 'full';
        include __DIR__ . '/weather-widget.php';
        ?>
    </div>
    <?php endif; ?>

    <!-- Safety Information -->
    <div class="beach-detail-card p-4">
        <h3 class="font-semibold text-white mb-3 flex items-center gap-2">
            <i data-lucide="alert-triangle" class="w-5 h-5 text-brand-yellow" aria-hidden="true"></i>
            <span>Safety Information</span>
        </h3>

        <div class="drawer-safety-grid grid grid-cols-1 xs:grid-cols-2 gap-3 text-sm">
            <!-- Swim Difficulty -->
            <div class="bg-white/5 p-3 rounded-lg border border-white/10">
                <div class="text-gray-500 text-xs mb-1">Swimming Difficulty</div>
                <div class="font-medium text-white"><?= getSwimDifficultyLabel($swimDifficulty) ?></div>
                <div class="flex gap-0.5 mt-1">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div class="w-4 h-1.5 rounded <?= $i <= $swimDifficulty ? 'bg-brand-yellow' : 'bg-white/20' ?>"></div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Lifeguard -->
            <div class="bg-white/5 p-3 rounded-lg border border-white/10">
                <div class="text-gray-500 text-xs mb-1">Lifeguard</div>
                <div class="font-medium flex items-center gap-1">
                    <?php if ($hasLifeguard): ?>
                    <i data-lucide="check" class="w-4 h-4 text-green-400" aria-hidden="true"></i>
                    <span class="text-green-400">Available</span>
                    <?php else: ?>
                    <i data-lucide="x" class="w-4 h-4 text-gray-500" aria-hidden="true"></i>
                    <span class="text-gray-500">Not available</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Child Safe -->
            <div class="bg-white/5 p-3 rounded-lg border border-white/10">
                <div class="text-gray-500 text-xs mb-1">Child Friendly</div>
                <div class="font-medium flex items-center gap-1">
                    <?php if ($safeForChildren): ?>
                    <i data-lucide="check" class="w-4 h-4 text-green-400" aria-hidden="true"></i>
                    <span class="text-green-400">Kid-friendly</span>
                    <?php else: ?>
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-brand-yellow" aria-hidden="true"></i>
                    <span class="text-brand-yellow">Caution advised</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Emergency -->
            <div class="bg-white/5 p-3 rounded-lg border border-white/10">
                <div class="text-gray-500 text-xs mb-1">Emergency</div>
                <div class="font-medium text-red-400">Call 911</div>
            </div>
        </div>

        <?php if ($safetyInfo): ?>
        <div class="mt-3 text-sm text-gray-300">
            <?= h($safetyInfo) ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Conditions -->
    <?php if ($sargassum || $surf || $wind): ?>
    <div>
        <h3 class="font-semibold text-white mb-2">Beach Conditions</h3>
        <div class="flex flex-wrap gap-2">
            <?php if ($sargassum): ?>
            <span class="inline-flex items-center gap-1.5 <?= getConditionClassDark($sargassum, 'sargassum') ?> px-3 py-1.5 rounded-lg text-sm border">
                <i data-lucide="leaf" class="w-4 h-4" aria-hidden="true"></i>
                <?= h(getConditionLabel('sargassum', $sargassum)) ?>
            </span>
            <?php endif; ?>
            <?php if ($surf): ?>
            <span class="inline-flex items-center gap-1.5 <?= getConditionClassDark($surf, 'surf') ?> px-3 py-1.5 rounded-lg text-sm border">
                <i data-lucide="waves" class="w-4 h-4" aria-hidden="true"></i>
                <?= h(getConditionLabel('surf', $surf)) ?>
            </span>
            <?php endif; ?>
            <?php if ($wind): ?>
            <span class="inline-flex items-center gap-1.5 <?= getConditionClassDark($wind, 'wind') ?> px-3 py-1.5 rounded-lg text-sm border">
                <i data-lucide="wind" class="w-4 h-4" aria-hidden="true"></i>
                <?= h(getConditionLabel('wind', $wind)) ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Amenities -->
    <?php if (!empty($amenities)): ?>
    <div>
        <h3 class="font-semibold text-white mb-2">Amenities</h3>
        <div class="grid grid-cols-2 gap-2">
            <?php foreach ($amenities as $amenity): ?>
            <div class="flex items-center gap-2 text-sm text-gray-300">
                <i data-lucide="check" class="w-4 h-4 text-green-400" aria-hidden="true"></i>
                <?= h(getAmenityLabel($amenity)) ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Description -->
    <?php if ($description): ?>
    <div>
        <h3 class="font-semibold text-white mb-2">About This Beach</h3>
        <p class="text-gray-300 text-sm leading-relaxed"><?= nl2br(h($description)) ?></p>
    </div>
    <?php endif; ?>

    <!-- Features -->
    <?php if (!empty($features)): ?>
    <div>
        <h3 class="font-semibold text-white mb-2">Highlights</h3>
        <div class="space-y-3">
            <?php foreach (array_slice($features, 0, 3) as $feature): ?>
            <div class="bg-white/5 p-3 rounded-lg border border-white/10">
                <h4 class="font-medium text-white text-sm"><?= h($feature['title']) ?></h4>
                <p class="text-gray-400 text-sm mt-1"><?= h($feature['description']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tips -->
    <?php if (!empty($tips)): ?>
    <div>
        <h3 class="font-semibold text-white mb-2">Visitor Tips</h3>
        <ul class="space-y-2">
            <?php foreach (array_slice($tips, 0, 4) as $tip): ?>
            <li class="flex items-start gap-3 text-sm">
                <span class="yellow-bullet mt-1.5"></span>
                <span class="text-gray-300"><?= h($tip['tip']) ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Additional Info -->
    <div class="drawer-info-grid grid grid-cols-1 sm:grid-cols-2 gap-3">
        <?php if ($parkingDetails): ?>
        <div class="bg-white/5 p-3 rounded-lg border border-white/10">
            <h4 class="font-medium text-white text-sm mb-1 flex items-center gap-1.5">
                <i data-lucide="car" class="w-4 h-4 text-brand-yellow" aria-hidden="true"></i>
                Parking
            </h4>
            <p class="text-gray-400 text-sm"><?= h($parkingDetails) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($bestTime): ?>
        <div class="bg-white/5 p-3 rounded-lg border border-white/10">
            <h4 class="font-medium text-white text-sm mb-1 flex items-center gap-1.5">
                <i data-lucide="clock" class="w-4 h-4 text-brand-yellow" aria-hidden="true"></i>
                Best Time
            </h4>
            <p class="text-gray-400 text-sm"><?= h($bestTime) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($accessLabel): ?>
        <div class="bg-white/5 p-3 rounded-lg border border-white/10">
            <h4 class="font-medium text-white text-sm mb-1 flex items-center gap-1.5">
                <i data-lucide="route" class="w-4 h-4 text-brand-yellow" aria-hidden="true"></i>
                Access
            </h4>
            <p class="text-gray-400 text-sm"><?= h($accessLabel) ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Notes/Warnings -->
    <?php if ($notes): ?>
    <div class="bg-brand-yellow/10 border border-brand-yellow/30 p-3 rounded-lg">
        <div class="flex gap-2">
            <i data-lucide="info" class="w-5 h-5 text-brand-yellow shrink-0" aria-hidden="true"></i>
            <p class="text-gray-300 text-sm"><?= h($notes) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- User Reviews Section -->
    <div class="border-t border-white/10 pt-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-white flex items-center gap-2">
                <i data-lucide="message-circle" class="w-5 h-5 text-brand-yellow" aria-hidden="true"></i>
                <span>Reviews</span>
                <?php if ($userReviewCount > 0): ?>
                <span class="text-sm font-normal text-gray-500">(<?= $userReviewCount ?>)</span>
                <?php endif; ?>
            </h3>
            <?php if (isAuthenticated()): ?>
            <button onclick="openReviewForm('<?= h($beach['id']) ?>', '<?= h(addslashes($name)) ?>')"
                    class="text-sm text-brand-yellow hover:text-yellow-300 font-medium">
                Write a Review
            </button>
            <?php else: ?>
            <button onclick="showSignupPrompt('reviews', '/beach/<?= h($beach['slug']) ?>')"
                    class="text-sm text-brand-yellow hover:text-yellow-300 font-medium">
                Write a Review
            </button>
            <?php endif; ?>
        </div>

        <?php if (!empty($reviews)): ?>
        <div class="space-y-4" id="reviews-list">
            <?php foreach (array_slice($reviews, 0, 3) as $review): ?>
            <?php include __DIR__ . '/review-card.php'; ?>
            <?php endforeach; ?>
        </div>
        <?php if (count($reviews) > 3): ?>
        <div class="mt-4 text-center">
            <a href="/beach/<?= h($beach['slug']) ?>#reviews"
               class="text-brand-yellow hover:text-yellow-300 text-sm font-medium">
                View all <?= count($reviews) ?> reviews →
            </a>
        </div>
        <?php endif; ?>

        <?php if (!isAuthenticated()): ?>
        <div class="mt-4 p-3 bg-brand-yellow/5 rounded-lg border border-brand-yellow/20 text-center">
            <p class="text-sm text-gray-300 mb-2">
                <span class="font-medium text-brand-yellow"><?= $userReviewCount ?> community member<?= $userReviewCount !== 1 ? 's have' : ' has' ?></span> reviewed this beach
            </p>
            <button onclick="showSignupPrompt('reviews', '/beach/<?= h($beach['slug']) ?>')"
                    class="text-sm text-brand-yellow hover:text-yellow-300 font-medium">
                Join the community →
            </button>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div class="text-center py-6 bg-white/5 rounded-lg border border-white/10">
            <i data-lucide="pen-line" class="w-8 h-8 mx-auto text-gray-500 mb-2" aria-hidden="true"></i>
            <p class="text-gray-400 text-sm mb-3">No reviews yet. Be the first to share your experience!</p>
            <?php if (!isAuthenticated()): ?>
            <button onclick="showSignupPrompt('reviews', '/beach/<?= h($beach['slug']) ?>')"
                    class="inline-flex items-center gap-2 bg-brand-yellow/10 hover:bg-brand-yellow/20 text-brand-yellow px-4 py-2 rounded-lg text-sm font-medium transition-colors border border-brand-yellow/20">
                <i data-lucide="log-in" class="w-4 h-4" aria-hidden="true"></i>
                Sign in to be the first reviewer
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Gallery -->
    <?php if (!empty($gallery)): ?>
    <div>
        <h3 class="font-semibold text-white mb-2">Photos</h3>
        <div class="gallery-grid">
            <?php foreach (array_slice($gallery, 0, 6) as $image): ?>
            <img src="<?= h($image) ?>"
                 alt="<?= h($name) ?>"
                 class="rounded-lg"
                 loading="lazy">
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="flex gap-3 pt-4 border-t border-white/10">
        <a href="<?= h(getDirectionsUrl($beach)) ?>"
           target="_blank"
           rel="noopener noreferrer"
           class="flex-1 flex items-center justify-center gap-2 bg-brand-yellow hover:bg-yellow-300 text-brand-darker py-3 rounded-lg font-semibold transition-colors"
           aria-label="Get directions to <?= h($name) ?>">
            <i data-lucide="navigation" class="w-5 h-5" aria-hidden="true"></i>
            Get Directions
        </a>
        <button onclick="shareBeach('<?= h($beach['slug']) ?>', '<?= h(addslashes($name)) ?>')"
                class="flex items-center justify-center gap-2 bg-white/10 hover:bg-white/20 text-white px-4 py-3 rounded-lg font-medium transition-colors border border-white/10"
                aria-label="Share <?= h($name) ?>">
            <i data-lucide="share-2" class="w-5 h-5" aria-hidden="true"></i>
            Share
        </button>
    </div>

    <!-- View Full Page Link -->
    <div class="text-center">
        <a href="/beach/<?= h($beach['slug']) ?>"
           class="text-brand-yellow hover:text-yellow-300 text-sm font-medium">
            View full beach page →
        </a>
    </div>
</div>
