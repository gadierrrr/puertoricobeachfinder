<?php
/**
 * Collection explorer hero.
 *
 * Required:
 * - $collectionContext (array)
 */

$hero = $collectionContext['hero'] ?? [];
$heroTitle = $hero['title'] ?? 'Puerto Rico Beach Collections';
$heroSubtitle = $hero['subtitle'] ?? 'Explore curated beaches with filters and map view.';
$heroMeta = $hero['meta'] ?? '';

// Resolve translated hero strings from i18n if available
$pageKey = $collectionContext['page_key'] ?? '';
if ($pageKey !== '' && function_exists('__')) {
    $t = __('pages.' . $pageKey . '.hero_title');
    if ($t !== 'pages.' . $pageKey . '.hero_title') {
        $heroTitle = $t;
    }
    $t = __('pages.' . $pageKey . '.hero_subtitle');
    if ($t !== 'pages.' . $pageKey . '.hero_subtitle') {
        $heroSubtitle = $t;
    }
    $t = __('pages.' . $pageKey . '.hero_meta');
    if ($t !== 'pages.' . $pageKey . '.hero_meta') {
        $heroMeta = $t;
    }
}
?>
<section class="collection-hero managed-page-hero"<?= pageHeroAttributes('listings') ?>>
    <div class="collection-hero__inner">
        <?php if (!empty($breadcrumbs) && is_array($breadcrumbs)): ?>
        <nav class="text-sm mb-6 text-gray-200" aria-label="Breadcrumb">
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <?php if ($index > 0): ?><span class="mx-2" aria-hidden="true">&gt;</span><?php endif; ?>
                <?php if (isset($crumb['url'])): ?>
                    <a href="<?= h($crumb['url']) ?>" class="hover:text-white"><?= h($crumb['name']) ?></a>
                <?php else: ?>
                    <span aria-current="page"><?= h($crumb['name']) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <?= function_exists('breadcrumbSchema') ? breadcrumbSchema($breadcrumbs) : '' ?>
        <?php endif; ?>
        <h1 class="collection-hero__title"><?= h($heroTitle) ?></h1>
        <p class="collection-hero__subtitle"><?= h($heroSubtitle) ?></p>
        <?php if ($heroMeta !== ''): ?>
        <p class="collection-hero__meta"><?= h($heroMeta) ?></p>
        <?php endif; ?>
    </div>
</section>
