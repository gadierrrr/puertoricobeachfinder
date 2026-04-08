<?php
/**
 * Guide Beach Card — light-background card for embedding in guide articles.
 *
 * Expected variable:
 *   $beach (array) — full beach row with tags/amenities attached.
 */

require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/constants.php';

$beach = $beach ?? [];
$slug = $beach['slug'] ?? '';
$name = $beach['name'] ?? 'Unknown Beach';
$municipality = $beach['municipality'] ?? '';
$coverImage = $beach['cover_image'] ?? '/images/beaches/placeholder-beach.webp';
$googleRating = $beach['google_rating'] ?? null;
$googleReviewCount = intval($beach['google_review_count'] ?? 0);
$description = trim((string)($beach['description'] ?? ''));
$excerpt = $description !== '' ? substr($description, 0, 180) . (strlen($description) > 180 ? '...' : '') : '';
$tags = array_slice($beach['tags'] ?? [], 0, 4);
$amenities = $beach['amenities'] ?? [];

$webpImage = getWebPImage($coverImage);
$imageAttrs = getResponsiveImageAttrs($coverImage);

$traits = [];
if (!empty($beach['access_label'])) {
    $traits[] = ucfirst(str_replace('-', ' ', (string)$beach['access_label']));
}
if (in_array('parking', $amenities, true)) {
    $traits[] = 'Parking';
}
if (!empty($beach['has_lifeguard'])) {
    $traits[] = 'Lifeguard';
}
if (!empty($beach['safe_for_children'])) {
    $traits[] = 'Family-friendly';
}
if (in_array('restrooms', $amenities, true)) {
    $traits[] = 'Restrooms';
}
if (in_array('showers', $amenities, true)) {
    $traits[] = 'Showers';
}
$traits = array_slice(array_values(array_unique($traits)), 0, 5);
?>
<div class="guide-beach-card not-prose my-6 rounded-xl overflow-hidden transition-shadow bg-white border border-warm-200 shadow-sm">
    <div class="flex flex-col sm:flex-row">
        <a href="/beach/<?= h($slug) ?>" class="block sm:w-64 flex-shrink-0">
            <picture>
                <?php if ($webpImage['webp']): ?>
                <source srcset="<?= h($webpImage['webp']) ?>" type="image/webp">
                <?php endif; ?>
                <img src="<?= h($imageAttrs['src']) ?>"
                     alt="<?= h(getBeachImageAlt($beach)) ?>"
                     class="w-full h-48 sm:h-full object-cover"
                     loading="lazy" decoding="async">
            </picture>
        </a>
        <div class="p-4 flex flex-col gap-2 min-w-0 flex-1">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h4 class="text-lg font-bold text-warm-900 leading-tight m-0">
                        <a href="/beach/<?= h($slug) ?>" class="text-warm-900 hover:text-ocean-600 no-underline"><?= h($name) ?></a>
                    </h4>
                    <p class="text-sm text-warm-500 m-0 mt-0.5"><?= h($municipality) ?>, Puerto Rico</p>
                </div>
                <?php if ($googleRating): ?>
                <div class="text-right flex-shrink-0">
                    <div class="text-yellow-400 text-sm leading-none">★★★★★</div>
                    <div class="text-sm font-bold text-warm-900"><?= number_format((float)$googleRating, 1) ?></div>
                    <?php if ($googleReviewCount > 0): ?>
                    <div class="text-xs text-warm-500"><?= number_format($googleReviewCount) ?> reviews</div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($excerpt): ?>
            <p class="text-sm text-warm-600 m-0 leading-relaxed line-clamp-2"><?= h($excerpt) ?></p>
            <?php endif; ?>
            <?php if (!empty($tags)): ?>
            <div class="flex flex-wrap gap-1.5">
                <?php foreach ($tags as $tag): ?>
                <span class="inline-block text-xs font-medium rounded-full px-2.5 py-0.5 bg-sunset-100 border border-sunset-300 text-sunset-700"><?= h(getTagLabel($tag)) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($traits)): ?>
            <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-warm-500">
                <?php foreach ($traits as $trait): ?>
                <span><?= h($trait) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="flex gap-2 mt-auto pt-1">
                <a href="/beach/<?= h($slug) ?>" class="inline-flex items-center justify-center gap-1.5 text-sm font-semibold px-4 py-2 rounded-lg transition-colors no-underline bg-sunset-400 text-ocean-900 hover:bg-sunset-300">View Details</a>
                <a href="<?= h(getDirectionsUrl($beach)) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-1.5 text-warm-700 text-sm font-medium px-4 py-2 rounded-lg transition-colors no-underline bg-warm-100 border border-warm-200 hover:bg-warm-200">Directions</a>
            </div>
        </div>
    </div>
</div>
<?php
