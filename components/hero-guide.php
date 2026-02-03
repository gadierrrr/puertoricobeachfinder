<?php
/**
 * Guide Hero Component
 * Consistent green gradient hero for all guide pages
 *
 * Required variables:
 * - $pageTitle (string): Main heading
 * - $pageDescription (string): Subtitle text
 * Optional variables:
 * - $breadcrumbs (array): Breadcrumb navigation items
 */
?>
<section class="hero-gradient-guide text-white py-16">
    <div class="container mx-auto px-4 container-padding">
        <?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
        <nav class="text-sm mb-6 text-green-100" aria-label="Breadcrumb">
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <?php if ($index > 0): ?><span class="mx-2" aria-hidden="true">&gt;</span><?php endif; ?>
                <?php if (isset($crumb['url'])): ?>
                    <a href="<?= h($crumb['url']) ?>" class="hover:text-white"><?= h($crumb['name']) ?></a>
                <?php else: ?>
                    <span aria-current="page"><?= h($crumb['name']) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>

        <h1 class="text-4xl md:text-5xl font-bold mb-4"><?= h($pageTitle) ?></h1>
        <p class="text-xl text-green-50 max-w-3xl"><?= h($pageDescription) ?></p>
    </div>
</section>
