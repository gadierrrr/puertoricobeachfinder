<?php
/**
 * Beach Card Component (Simplified)
 *
 * @param array $beach - Beach data from database
 * @param float|null $distance - Distance in meters (if user location available)
 * @param bool $isFavorite - Whether the beach is in user's favorites
 */

require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/constants.php';

// $beach, $distance, and $isFavorite should be set before including this file
$beach = $beach ?? [];
$distance = $distance ?? null;
$isFavorite = $isFavorite ?? false;

$slug = $beach['slug'] ?? '';
$name = $beach['name'] ?? 'Unknown Beach';
$municipality = $beach['municipality'] ?? '';
$coverImage = $beach['cover_image'] ?? '/assets/images/placeholder.jpg';
$googleRating = $beach['google_rating'] ?? null;
$googleReviewCount = $beach['google_review_count'] ?? 0;
$sargassum = $beach['sargassum'] ?? null;
$surf = $beach['surf'] ?? null;
$lat = $beach['lat'] ?? 0;
$lng = $beach['lng'] ?? 0;

// Get tags (should be joined in query)
$tags = $beach['tags'] ?? [];

// Format distance
$distanceFormatted = $distance !== null ? formatDistanceDisplay($distance) : null;
?>

<article class="beach-card bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300"
         data-beach-id="<?= h($beach['id']) ?>"
         data-lat="<?= h($lat) ?>"
         data-lng="<?= h($lng) ?>"
         role="article"
         aria-label="<?= h($name) ?> beach">

    <!-- Image Container -->
    <div class="relative aspect-video overflow-hidden">
        <img src="<?= h(getThumbnailUrl($coverImage)) ?>"
             alt="<?= h($name) ?> beach in <?= h($municipality) ?>"
             class="w-full h-full object-cover"
             loading="lazy">

        <!-- Distance Badge (top right) -->
        <?php if ($distanceFormatted): ?>
        <div class="distance-badge absolute top-3 right-3 bg-blue-600 text-white text-xs font-semibold px-2.5 py-1 rounded-full shadow" aria-label="<?= h($distanceFormatted) ?> away">
            <?= h($distanceFormatted) ?>
        </div>
        <?php endif; ?>

        <!-- Favorite Button (top left, if logged in) -->
        <?php if (isAuthenticated()): ?>
        <button class="favorite-btn absolute top-3 left-3 w-9 h-9 flex items-center justify-center rounded-full bg-white/90 shadow hover:bg-white transition-colors"
                hx-post="/api/toggle-favorite.php"
                hx-target="this"
                hx-swap="outerHTML"
                hx-vals='{"beach_id": "<?= h($beach['id']) ?>", "csrf_token": "<?= h(csrfToken()) ?>"}'
                aria-label="<?= $isFavorite ? 'Remove from favorites' : 'Add to favorites' ?>"
                aria-pressed="<?= $isFavorite ? 'true' : 'false' ?>">
            <i data-lucide="heart" class="w-5 h-5 <?= $isFavorite ? 'text-red-500 fill-red-500' : 'text-gray-400' ?>" aria-hidden="true"></i>
        </button>
        <?php endif; ?>
    </div>

    <!-- Content - Simplified hierarchy -->
    <div class="p-4">
        <!-- Primary: Name & Location -->
        <div class="mb-2">
            <h3 class="font-semibold text-lg text-gray-900 line-clamp-1"><?= h($name) ?></h3>
            <p class="text-sm text-gray-500"><?= h($municipality) ?></p>
        </div>

        <!-- Secondary: Rating & Top Tags -->
        <div class="flex items-center gap-3 mb-3">
            <?php if ($googleRating): ?>
            <div class="flex items-center gap-1 text-sm bg-amber-50 px-2 py-0.5 rounded-full" aria-label="Google rating: <?= number_format($googleRating, 1) ?> out of 5">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#FACC15" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
                <span class="font-medium text-amber-800"><?= number_format($googleRating, 1) ?></span>
                <?php if ($googleReviewCount): ?>
                <span class="text-amber-600/70 text-xs">(<?= number_format($googleReviewCount) ?>)</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($tags)): ?>
            <div class="flex flex-wrap gap-1" role="list" aria-label="Beach features">
                <?php
                $displayTags = array_slice($tags, 0, 2);
                foreach ($displayTags as $tag):
                ?>
                <span class="inline-block bg-blue-50 text-blue-700 text-xs px-2 py-0.5 rounded-full" role="listitem">
                    <?= h(getTagLabel($tag)) ?>
                </span>
                <?php endforeach; ?>
                <?php if (count($tags) > 2): ?>
                <span class="inline-block bg-gray-100 text-gray-500 text-xs px-2 py-0.5 rounded-full">
                    +<?= count($tags) - 2 ?>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Action Buttons - Simplified -->
        <div class="flex gap-2 card-actions">
            <button onclick="openBeachDrawer('<?= h($beach['id']) ?>')"
                    class="flex-1 flex items-center justify-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2.5 rounded-lg transition-colors">
                <i data-lucide="book-open" class="w-4 h-4" aria-hidden="true"></i>
                <span>View Details</span>
            </button>
            <a href="<?= h(getDirectionsUrl($beach)) ?>"
               target="_blank"
               rel="noopener noreferrer"
               class="flex items-center justify-center gap-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium py-2.5 px-4 rounded-lg transition-colors"
               aria-label="Get directions to <?= h($name) ?>">
                <i data-lucide="navigation" class="w-4 h-4" aria-hidden="true"></i>
                <span class="sr-only">Directions</span>
            </a>
            <button onclick="shareBeach('<?= h($slug) ?>', '<?= h(addslashes($name)) ?>')"
                    class="flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm p-2.5 rounded-lg transition-colors"
                    aria-label="Share <?= h($name) ?>">
                <i data-lucide="share-2" class="w-4 h-4" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</article>
