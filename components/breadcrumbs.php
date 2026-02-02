<?php
/**
 * Breadcrumb Navigation Component
 *
 * Usage:
 * $breadcrumbs = [
 *     ['name' => 'Home', 'url' => '/'],
 *     ['name' => 'Category', 'url' => '/category'],
 *     ['name' => 'Current Page'] // No URL for current page
 * ];
 * include __DIR__ . '/components/breadcrumbs.php';
 *
 * Options:
 * $breadcrumbStyle = 'light' | 'dark' (default: 'dark')
 * $breadcrumbClass = additional CSS classes
 */

$breadcrumbs = $breadcrumbs ?? [];
$breadcrumbStyle = $breadcrumbStyle ?? 'dark';
$breadcrumbClass = $breadcrumbClass ?? '';

if (empty($breadcrumbs)) {
    return;
}

// Style configurations
$styles = [
    'dark' => [
        'nav' => 'text-white/60',
        'link' => 'hover:text-brand-yellow transition-colors',
        'separator' => 'text-white/40',
        'current' => 'text-white/90 font-medium'
    ],
    'light' => [
        'nav' => 'text-gray-500',
        'link' => 'hover:text-blue-600 transition-colors',
        'separator' => 'text-gray-400',
        'current' => 'text-gray-900 font-medium'
    ]
];

$style = $styles[$breadcrumbStyle] ?? $styles['dark'];
?>
<nav class="breadcrumbs text-sm <?= $style['nav'] ?> <?= h($breadcrumbClass) ?>" aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-1" itemscope itemtype="https://schema.org/BreadcrumbList">
        <?php foreach ($breadcrumbs as $index => $crumb):
            $isLast = $index === count($breadcrumbs) - 1;
            $position = $index + 1;
        ?>
        <li class="flex items-center" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <?php if (!$isLast && isset($crumb['url'])): ?>
                <a href="<?= h($crumb['url']) ?>"
                   class="<?= $style['link'] ?>"
                   itemprop="item">
                    <span itemprop="name"><?= h($crumb['name']) ?></span>
                </a>
                <meta itemprop="position" content="<?= $position ?>">
                <span class="mx-2 <?= $style['separator'] ?>" aria-hidden="true">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </span>
            <?php else: ?>
                <span class="<?= $style['current'] ?>" itemprop="name" aria-current="page"><?= h($crumb['name']) ?></span>
                <meta itemprop="position" content="<?= $position ?>">
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ol>
</nav>
