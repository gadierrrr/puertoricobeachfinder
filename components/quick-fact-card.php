<?php
/**
 * Quick Fact Card Component for Beach Detail Page
 * Displays a single fact with yellow icon box (IslaFinder style)
 *
 * @param string $icon - Lucide icon name
 * @param string $label - Small uppercase label
 * @param string $value - Main value text
 * @param string $subtext - Optional smaller text below value
 */

$icon = $icon ?? 'info';
$label = $label ?? '';
$value = $value ?? '';
$subtext = $subtext ?? '';
?>
<div class="quick-fact-card">
    <div class="icon-box">
        <i data-lucide="<?= h($icon) ?>" aria-hidden="true"></i>
    </div>
    <div class="quick-fact-content min-w-0">
        <div class="quick-fact-label text-xs text-gray-500 uppercase tracking-wide"><?= h($label) ?></div>
        <div class="quick-fact-value text-white font-medium"><?= h($value) ?></div>
        <?php if ($subtext): ?>
        <div class="quick-fact-subtext text-sm text-gray-400"><?= h($subtext) ?></div>
        <?php endif; ?>
    </div>
</div>
