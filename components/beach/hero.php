<?php
/**
 * Beach Detail: Hero Section
 * Full-bleed cover photo with gradient overlay, breadcrumbs, and title.
 *
 * Expects: $beach, $lang, $webpImage
 */

// Build responsive srcset for the hero image
$heroSrcset = getBeachImageSrcset($beach);
if (!$heroSrcset) {
    // Legacy image — use getResponsiveImageAttrs for thumbnail variants
    $heroAttrs = getResponsiveImageAttrs($beach['cover_image'] ?? '', '100vw');
    $heroSrcset = $heroAttrs['srcset'] ?? '';
}
$heroSizes = '100vw';
?>
<!-- Hero Image - IslaFinder Style (80vh) -->
<div class="relative h-[50vh] md:h-[60vh] lg:h-[70vh] overflow-hidden">
    <?php if ($beach['cover_image']): ?>
    <img src="<?= h(getBeachImageUrl($beach, 'large')) ?>"
         <?php if ($heroSrcset): ?>
         srcset="<?= h($heroSrcset) ?>"
         sizes="<?= h($heroSizes) ?>"
         <?php endif; ?>
         alt="<?= h(getBeachImageAlt($beach, 'scenic beach view')) ?>"
         class="w-full h-full object-cover"
         fetchpriority="high">
    <?php else: ?>
    <div class="w-full h-full bg-gradient-to-br from-ocean-800 to-ocean-900 flex items-center justify-center">
        <span class="text-8xl">🏖️</span>
    </div>
    <?php endif; ?>
    <div class="absolute inset-0 hero-gradient-beach"></div>

    <!-- Title overlay - positioned at bottom -->
    <div class="absolute bottom-0 left-0 right-0 p-6 md:p-10 lg:p-16">
        <div class="max-w-7xl mx-auto">
            <!-- Breadcrumbs -->
            <nav class="text-white/70 text-sm mb-4" aria-label="Breadcrumb">
                <a href="<?= h(routeUrl('home', $lang)) ?>" class="hover:text-sunset-400 transition-colors"><?= h(__('nav.home')) ?></a>
                <span class="mx-2">/</span>
                <a href="<?= h(routeUrl('home', $lang)) ?>" class="hover:text-sunset-400 transition-colors"><?= h(__('nav.beaches')) ?></a>
                <span class="mx-2">/</span>
                <a href="<?= h(routeUrl('municipality', $lang, ['municipality' => strtolower(str_replace(' ', '-', $beach['municipality']))])) ?>" class="hover:text-sunset-400 transition-colors"><?= h($beach['municipality']) ?></a>
                <span class="mx-2">/</span>
                <span class="text-white/70"><?= h($beach['name']) ?></span>
            </nav>

            <!-- Beach Name - Large Uppercase with Location -->
            <h1 class="text-4xl sm:text-5xl md:text-7xl lg:text-8xl xl:text-9xl font-bold text-white uppercase tracking-tight leading-none">
                <?= h($beach['name']) ?>
                <span class="block text-2xl sm:text-3xl md:text-4xl lg:text-5xl xl:text-6xl mt-2 md:mt-4 text-sunset-400 font-serif normal-case italic">
                    <?= h($beach['municipality']) ?>, Puerto Rico
                </span>
            </h1>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="bg-sand-50">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
