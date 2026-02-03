<?php
/**
 * Collection Hero Component
 * Dark gradient hero matching homepage aesthetic
 *
 * Required variables:
 * - $pageTitle (string): Main heading
 * - $pageDescription (string): Subtitle text
 * Optional variables:
 * - $breadcrumbs (array): Breadcrumb navigation items
 * - $heroSubtext (string): Metadata line like "Updated January 2025"
 */
?>
<section class="hero-gradient-dark text-white py-16 md:py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
        <div class="mb-6">
            <?php include __DIR__ . '/breadcrumbs.php'; ?>
        </div>
        <?php endif; ?>

        <h1 class="text-3xl md:text-5xl font-bold mb-6">
            <?= h($pageTitle) ?>
        </h1>
        <p class="text-lg md:text-xl opacity-90 max-w-3xl mx-auto">
            <?= h($pageDescription) ?>
        </p>

        <?php if (isset($heroSubtext)): ?>
        <p class="text-sm mt-4 opacity-75"><?= h($heroSubtext) ?></p>
        <?php endif; ?>
    </div>
</section>
