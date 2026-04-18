<?php
/**
 * Beach Card Component - Tropical Daytime Design
 *
 * @param array $beach - Beach data from database
 * @param float|null $distance - Distance in meters (if user location available)
 * @param bool $isFavorite - Whether the beach is in user's favorites
 * @param array|null $crowdData - Crowd level data (optional)
 * @param array|null $weatherData - Weather data (unused, kept for call-site compatibility)
 */

require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/constants.php';
if (!function_exists('__')) {
    require_once __DIR__ . '/../inc/i18n.php';
}
$cardT = static function (string $key, string $fallback, array $params = []): string {
    if (function_exists('__')) {
        return __($key, $params);
    }

    $replacements = [];
    foreach ($params as $param => $replacement) {
        $replacements[':' . $param] = (string) $replacement;
    }

    return strtr($fallback, $replacements);
};

// $beach, $distance, $isFavorite, $crowdData, $weatherData should be set before including this file
$beach = $beach ?? [];
$distance = $distance ?? null;
$isFavorite = $isFavorite ?? false;
$crowdData = $crowdData ?? null;

$slug = $beach['slug'] ?? '';
$name = $beach['name'] ?? $cardT('beach.unknown', 'Unknown Beach');
$municipality = $beach['municipality'] ?? '';
$coverImage = $beach['cover_image'] ?? '/images/beaches/placeholder-beach.webp';
$googleRating = $beach['google_rating'] ?? null;
$googleReviewCount = $beach['google_review_count'] ?? 0;
$description = $beach['description'] ?? '';
$lat = $beach['lat'] ?? 0;
$lng = $beach['lng'] ?? 0;

// Get tags (should be joined in query)
$tags = $beach['tags'] ?? [];
$primaryTag = !empty($tags) ? getTagLabel($tags[0]) : $cardT('beach.beach_label', 'Beach');

// Format distance
$distanceFormatted = $distance !== null ? formatDistanceDisplay($distance) : null;

// Get score badge class (function defined in helpers.php)
$scoreBadgeClass = $googleRating ? getScoreBadgeClass((float)$googleRating) : '';

// Get responsive image attributes
$imageAttrs = getResponsiveImageAttrs($coverImage);

// Build locale-aware beach detail URL
$_cardLang = function_exists('getCurrentLanguage') ? getCurrentLanguage() : 'en';
$beachUrl = function_exists('routeUrl')
    ? routeUrl('beach_detail', $_cardLang, ['slug' => $slug])
    : '/beach/' . $slug;

// Get WebP version if available
$webpImage = getWebPImage($coverImage);

?>

<article class="beach-card relative group rounded-2xl overflow-hidden bg-white shadow-card border border-warm-200 hover:border-sunset-400/30 transition-all duration-300 cursor-pointer"
         data-beach-id="<?= h($beach['id']) ?>"
         data-lat="<?= h($lat) ?>"
         data-lng="<?= h($lng) ?>"
         role="button"
         tabindex="0"
         aria-label="<?= h($cardT('beach.card_aria', 'View details for :name beach in :municipality', ['name' => $name, 'municipality' => $municipality])) ?>"
         data-action="openBeachDrawer" data-action-args='["<?= h($beach['id']) ?>"]'
         data-on="click,keydown" data-action-keys="Enter, " data-action-prevent>

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
                    data-action-stop data-action="noop" data-on="click"
                    aria-label="<?= $isFavorite ? h($cardT('beach.remove_favorite', 'Remove from favorites')) : h($cardT('beach.add_favorite', 'Add to favorites')) ?>"
                    aria-pressed="<?= $isFavorite ? 'true' : 'false' ?>">
                <i data-lucide="heart" class="w-4 h-4 <?= $isFavorite ? 'text-red-400 fill-red-400' : 'text-white/80' ?>" aria-hidden="true"></i>
            </button>
            <?php else: ?>
            <button class="favorite-btn w-9 h-9 flex items-center justify-center rounded-full bg-black/40 backdrop-blur-sm border border-white/20 hover:bg-black/60 transition-colors"
                    data-action-stop data-action="showSignupPrompt" data-action-args='["favorites"]'
                    aria-label="<?= h($cardT('beach.sign_in_to_save', 'Sign in to save this beach')) ?>"
                    title="<?= h($cardT('beach.sign_in_to_save', 'Sign in to save this beach')) ?>">
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
        <div class="distance-badge absolute top-14 right-3 bg-sunset-400 text-ocean-900 text-xs font-semibold px-2.5 py-1 rounded-full z-20" aria-label="<?= h($distanceFormatted) ?> away">
            <?= h($distanceFormatted) ?>
        </div>
        <?php endif; ?>

        <!-- Score Badge (bottom-right) -->
        <?php if ($googleRating): ?>
        <div class="score-badge <?= $scoreBadgeClass ?>"
             aria-label="Rating: <?= number_format($googleRating, 1) ?> out of 5">
            <span class="score-value"><?= number_format($googleRating, 1) ?></span>
            <span class="score-label"><?= $googleReviewCount ? number_format($googleReviewCount) . ' ' . h($cardT('beach.reviews', 'Reviews')) : h($cardT('beach.rating_label', 'Rating')) ?></span>
        </div>
        <?php endif; ?>

        <!-- Bottom content overlay -->
        <div class="absolute bottom-0 left-0 right-16 p-4 z-20 text-shadow-hero">
            <span class="text-xs text-sunset-400 uppercase tracking-wider font-medium"><?= h($municipality) ?></span>
            <h3 class="text-lg font-bold text-white mt-0.5 line-clamp-1"><a href="<?= h($beachUrl) ?>" class="text-white hover:text-sunset-400 no-underline" data-action="noop" data-action-stop><?= h($name) ?></a></h3>
        </div>
    </div>

    <!-- Card Actions - Dark glass style -->
    <div class="p-4 bg-white">
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
        <div class="flex items-center gap-2 mb-3">
            <span class="inline-flex items-center gap-1 text-xs <?= $crowdColorClass ?> px-2 py-0.5 rounded-full" title="<?= h($crowdData['time_label'] ?? '') ?>">
                <span>👥</span>
                <span class="font-medium"><?= h($crowdData['label'] ?? $cardT('beach.unknown_crowd', 'Unknown')) ?></span>
            </span>
        </div>
        <?php endif; ?>

        <!-- Action Buttons - Primary + Split Secondary -->
        <div class="card-actions flex flex-col gap-2">
            <button type="button"
                    data-action-stop data-action="openBeachDrawer" data-action-args='["<?= h($beach['id']) ?>"]'
                    class="w-full flex items-center justify-center gap-1.5 bg-ocean-500 hover:bg-ocean-600 text-white text-sm font-semibold h-10 px-3 rounded-lg transition-colors">
                <i data-lucide="book-open" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
                <span><?= h($cardT('beach.details', 'Details')) ?></span>
            </button>
            <div class="flex gap-2">
                <a href="<?= h(getDirectionsUrl($beach)) ?>"
                   target="_blank"
                   rel="noopener noreferrer"
                   data-action-stop data-action="noop" data-on="click"
                   data-bf-track="directions"
                   data-bf-beach-id="<?= h($beach['id']) ?>"
                   data-bf-beach-slug="<?= h($slug) ?>"
                   data-bf-municipality="<?= h($municipality) ?>"
                   data-bf-source="card"
                   class="flex-1 flex items-center justify-center gap-1.5 bg-warm-100 hover:bg-warm-200 text-warm-700 text-sm font-medium h-10 px-3 rounded-lg transition-colors border border-warm-200"
                   aria-label="<?= h($cardT('beach.directions', 'Directions')) ?> <?= h($name) ?>">
                    <i data-lucide="compass" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
                    <span><?= h($cardT('beach.directions', 'Directions')) ?></span>
                </a>
                <button type="button"
                        data-action-stop data-action="shareBeach" data-action-args='["<?= h($slug) ?>","<?= h(addslashes($name)) ?>"]'
                        class="flex-1 flex items-center justify-center gap-1.5 bg-warm-100 hover:bg-warm-200 text-warm-700 text-sm font-medium h-10 px-3 rounded-lg transition-colors border border-warm-200"
                        aria-label="<?= h($cardT('common.share', 'Share')) ?> <?= h($name) ?>"
                        title="<?= h($cardT('common.share', 'Share')) ?>">
                    <i data-lucide="share-2" class="w-4 h-4 shrink-0" aria-hidden="true"></i>
                    <span><?= h($cardT('common.share', 'Share')) ?></span>
                </button>
            </div>
        </div>
    </div>
</article>
