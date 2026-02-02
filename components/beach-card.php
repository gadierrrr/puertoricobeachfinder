<?php
/**
 * Beach Card Component - Dark Glassmorphism Design
 *
 * @param array $beach - Beach data from database
 * @param float|null $distance - Distance in meters (if user location available)
 * @param bool $isFavorite - Whether the beach is in user's favorites
 * @param array|null $crowdData - Crowd level data (optional)
 * @param array|null $weatherData - Weather data (optional)
 */

require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/constants.php';

// $beach, $distance, $isFavorite, $crowdData, $weatherData should be set before including this file
$beach = $beach ?? [];
$distance = $distance ?? null;
$isFavorite = $isFavorite ?? false;
$crowdData = $crowdData ?? null;
$weatherData = $weatherData ?? null;

$slug = $beach['slug'] ?? '';
$name = $beach['name'] ?? 'Unknown Beach';
$municipality = $beach['municipality'] ?? '';
$coverImage = $beach['cover_image'] ?? '/images/beaches/placeholder-beach.webp';
$googleRating = $beach['google_rating'] ?? null;
$googleReviewCount = $beach['google_review_count'] ?? 0;
$description = $beach['description'] ?? '';
$lat = $beach['lat'] ?? 0;
$lng = $beach['lng'] ?? 0;

// Get tags (should be joined in query)
$tags = $beach['tags'] ?? [];
$primaryTag = !empty($tags) ? getTagLabel($tags[0]) : 'Beach';

// Format distance
$distanceFormatted = $distance !== null ? formatDistanceDisplay($distance) : null;

// Get score badge class (function defined in helpers.php)
$scoreBadgeClass = $googleRating ? getScoreBadgeClass((float)$googleRating) : '';

// Get responsive image attributes
$imageAttrs = getResponsiveImageAttrs($coverImage);

// Get WebP version if available
$webpImage = getWebPImage($coverImage);

// Get beach conditions
$sargassum = $beach['sargassum'] ?? null;
$surf = $beach['surf'] ?? null;
$wind = $beach['wind'] ?? null;
$hasConditions = $sargassum || $surf || $wind;
?>

<article class="beach-card relative group rounded-2xl overflow-hidden bg-brand-darker/50 border border-white/10 hover:border-brand-yellow/30 transition-all duration-300 cursor-pointer"
         data-beach-id="<?= h($beach['id']) ?>"
         data-lat="<?= h($lat) ?>"
         data-lng="<?= h($lng) ?>"
         role="button"
         tabindex="0"
         aria-label="View details for <?= h($name) ?> beach in <?= h($municipality) ?>"
         onclick="openBeachDrawer('<?= h($beach['id']) ?>')"
         onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openBeachDrawer('<?= h($beach['id']) ?>');}">

    <!-- Image Container with gradient overlay -->
    <div class="relative aspect-[4/3] overflow-hidden">
        <picture>
            <?php if ($webpImage['webp']): ?>
            <source srcset="<?= h($webpImage['webp']) ?>" type="image/webp">
            <?php endif; ?>
            <img src="<?= h($imageAttrs['src']) ?>"
                 <?php if ($imageAttrs['srcset']): ?>
                 srcset="<?= h($imageAttrs['srcset']) ?>"
                 sizes="<?= h($imageAttrs['sizes']) ?>"
                 <?php endif; ?>
                 alt="<?= h(getBeachImageAlt($beach)) ?>"
                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                 loading="lazy"
                 decoding="async">
        </picture>

        <!-- Subtle gradient for text readability only at bottom -->
        <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-black/70 to-transparent"></div>

        <!-- Top badges row -->
        <div class="absolute top-3 left-3 right-3 flex justify-between items-start z-20">
            <!-- Favorite Button -->
            <?php if (isAuthenticated()): ?>
            <button class="favorite-btn w-9 h-9 flex items-center justify-center rounded-full bg-black/40 backdrop-blur-sm border border-white/20 hover:bg-black/60 transition-colors"
                    hx-post="/api/toggle-favorite.php"
                    hx-target="this"
                    hx-swap="outerHTML"
                    hx-vals='{"beach_id": "<?= h($beach['id']) ?>", "csrf_token": "<?= h(csrfToken()) ?>"}'
                    onclick="event.stopPropagation()"
                    aria-label="<?= $isFavorite ? 'Remove from favorites' : 'Add to favorites' ?>"
                    aria-pressed="<?= $isFavorite ? 'true' : 'false' ?>">
                <i data-lucide="heart" class="w-4 h-4 <?= $isFavorite ? 'text-red-400 fill-red-400' : 'text-white/80' ?>" aria-hidden="true"></i>
            </button>
            <?php else: ?>
            <button class="favorite-btn w-9 h-9 flex items-center justify-center rounded-full bg-black/40 backdrop-blur-sm border border-white/20 hover:bg-black/60 transition-colors"
                    onclick="event.stopPropagation(); showSignupPrompt('favorites')"
                    aria-label="Sign in to save this beach"
                    title="Sign in to save favorites">
                <i data-lucide="heart" class="w-4 h-4 text-white/50" aria-hidden="true"></i>
            </button>
            <?php endif; ?>

            <!-- Tag badge -->
            <div class="bg-black/40 backdrop-blur-md rounded-full px-3 py-1 border border-white/20">
                <span class="text-xs text-white font-medium"><?= h($primaryTag) ?></span>
            </div>
        </div>

        <!-- Distance Badge (if available) -->
        <?php if ($distanceFormatted): ?>
        <div class="distance-badge absolute top-14 right-3 bg-brand-yellow text-brand-darker text-xs font-semibold px-2.5 py-1 rounded-full z-20" aria-label="<?= h($distanceFormatted) ?> away">
            <?= h($distanceFormatted) ?>
        </div>
        <?php endif; ?>

        <!-- Score Badge (bottom-right) -->
        <?php if ($googleRating): ?>
        <div class="score-badge <?= $scoreBadgeClass ?>"
             aria-label="Rating: <?= number_format($googleRating, 1) ?> out of 5">
            <span class="score-value"><?= number_format($googleRating, 1) ?></span>
            <span class="score-label"><?= $googleReviewCount ? number_format($googleReviewCount) . ' reviews' : 'Rating' ?></span>
        </div>
        <?php endif; ?>

        <!-- Bottom content overlay -->
        <div class="absolute bottom-0 left-0 right-16 p-4 z-20" style="text-shadow: 0 1px 3px rgba(0,0,0,0.8), 0 2px 8px rgba(0,0,0,0.6);">
            <span class="text-xs text-brand-yellow uppercase tracking-wider font-medium"><?= h($municipality) ?></span>
            <h3 class="text-lg font-bold text-white mt-0.5 line-clamp-1"><?= h($name) ?></h3>
        </div>
    </div>

    <!-- Card Actions - Dark glass style -->
    <div class="p-4 bg-brand-darker/80">
        <!-- Live Data: Conditions, Weather & Crowd -->
        <div class="flex flex-wrap items-center gap-2 mb-3 <?= (!$hasConditions && !$crowdData) ? 'weather-row-placeholder' : '' ?>"
             data-beach-id="<?= h($beach['id']) ?>">
            <!-- Weather badge (loaded async via JS) -->
            <span class="weather-badge inline-flex items-center gap-1 text-xs bg-white/10 text-white/80 px-2 py-0.5 rounded-full hidden"
                  data-beach-id="<?= h($beach['id']) ?>">
                <span class="weather-icon">🌤️</span>
                <span class="weather-temp font-medium"></span>
            </span>

            <?php if ($crowdData): ?>
            <?php
            $crowdColors = [
                'green' => 'bg-green-500/20 text-green-400',
                'yellow' => 'bg-yellow-500/20 text-yellow-400',
                'orange' => 'bg-orange-500/20 text-orange-400',
                'red' => 'bg-red-500/20 text-red-400',
                'gray' => 'bg-white/10 text-white/60'
            ];
            $crowdColorClass = $crowdColors[$crowdData['color']] ?? $crowdColors['gray'];
            ?>
            <span class="inline-flex items-center gap-1 text-xs <?= $crowdColorClass ?> px-2 py-0.5 rounded-full" title="<?= h($crowdData['time_label'] ?? '') ?>">
                <span>👥</span>
                <span class="font-medium"><?= h($crowdData['label'] ?? 'Unknown') ?></span>
            </span>
            <?php endif; ?>

            <?php if ($hasConditions): ?>
            <!-- Condition Indicators -->
            <div class="condition-indicators flex items-center gap-1.5 ml-auto" aria-label="Beach conditions">
                <?php if ($sargassum): ?>
                <span class="condition-dot <?= getConditionDotClass($sargassum) ?>"
                      title="Sargassum: <?= h(getConditionLabel('sargassum', $sargassum)) ?>"
                      aria-label="Sargassum: <?= h(getConditionLabel('sargassum', $sargassum)) ?>">
                    <i data-lucide="leaf" class="w-3 h-3" aria-hidden="true"></i>
                </span>
                <?php endif; ?>
                <?php if ($surf): ?>
                <span class="condition-dot <?= getConditionDotClass($surf) ?>"
                      title="Surf: <?= h(getConditionLabel('surf', $surf)) ?>"
                      aria-label="Surf: <?= h(getConditionLabel('surf', $surf)) ?>">
                    <i data-lucide="waves" class="w-3 h-3" aria-hidden="true"></i>
                </span>
                <?php endif; ?>
                <?php if ($wind): ?>
                <span class="condition-dot <?= getConditionDotClass($wind) ?>"
                      title="Wind: <?= h(getConditionLabel('wind', $wind)) ?>"
                      aria-label="Wind: <?= h(getConditionLabel('wind', $wind)) ?>">
                    <i data-lucide="wind" class="w-3 h-3" aria-hidden="true"></i>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Action Buttons - Single Row -->
        <div class="card-actions flex gap-2">
            <button type="button"
                    onclick="event.stopPropagation(); openBeachDrawer('<?= h($beach['id']) ?>')"
                    class="flex-1 flex items-center justify-center gap-1.5 bg-brand-yellow hover:bg-yellow-300 text-brand-darker text-sm font-semibold h-10 px-3 rounded-lg transition-colors">
                <i data-lucide="book-open" class="w-4 h-4" aria-hidden="true"></i>
                <span>Details</span>
            </button>
            <a href="<?= h(getDirectionsUrl($beach)) ?>"
               target="_blank"
               rel="noopener noreferrer"
               onclick="event.stopPropagation()"
               class="flex-1 flex items-center justify-center gap-1.5 bg-white/10 hover:bg-white/20 text-white text-sm font-medium h-10 px-2 rounded-lg transition-colors border border-white/10"
               aria-label="Get directions to <?= h($name) ?>">
                <i data-lucide="navigation" class="w-4 h-4" aria-hidden="true"></i>
                <span>Go</span>
            </a>
            <button type="button"
                    onclick="event.stopPropagation(); toggleCompare('<?= h($beach['id']) ?>', '<?= h(addslashes($name)) ?>', '<?= h($coverImage) ?>', this)"
                    class="compare-btn flex-1 flex items-center justify-center gap-1 bg-white/10 hover:bg-white/20 text-white text-sm h-10 px-2 rounded-lg transition-colors border border-white/10"
                    aria-label="Add <?= h($name) ?> to comparison"
                    data-beach-id="<?= h($beach['id']) ?>">
                <i data-lucide="git-compare" class="w-4 h-4" aria-hidden="true"></i>
                <span class="hidden sm:inline">Compare</span>
            </button>
            <button type="button"
                    onclick="event.stopPropagation(); shareBeach('<?= h($slug) ?>', '<?= h(addslashes($name)) ?>')"
                    class="flex-1 flex items-center justify-center gap-1 bg-white/10 hover:bg-white/20 text-white text-sm h-10 px-2 rounded-lg transition-colors border border-white/10"
                    aria-label="Share <?= h($name) ?>">
                <i data-lucide="share-2" class="w-4 h-4" aria-hidden="true"></i>
                <span class="hidden sm:inline">Share</span>
            </button>
        </div>
    </div>
</article>
