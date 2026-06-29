<?php
/**
 * ⚠️ INACTIVE / WIP — unshipped redesign, not wired into any live page.
 *    Do not build on without reviving. See docs/mobile-homepage-bug-report.md.
 *
 * Beach Detail: Local Tips card (wireframe)
 * Sand-gradient tinted card with byline + bullet-style tips.
 *
 * Expects: $beach, $lang
 */
$tipsRaw = ($lang === 'es' && !empty($beach['local_tips_es'])) ? $beach['local_tips_es'] : ($beach['local_tips'] ?? '');
$tipsRaw = trim((string)$tipsRaw);
if ($tipsRaw === '') return;

$lines = preg_split('/\r?\n+/', $tipsRaw);
$lines = array_values(array_filter(array_map('trim', $lines), fn($l) => $l !== ''));
?>
<section class="local-tips-card" aria-label="<?= h(__('beach.local_tips_title')) ?>">
    <h3 class="local-tips-title"><?= h(__('beach.local_tips_title')) ?></h3>
    <div class="local-tips-byline">
        <?= h(__('beach.local_tips_byline', ['author' => 'Gadiel', 'place' => $beach['municipality']])) ?>
    </div>
    <?php if (count($lines) > 1): ?>
    <ul class="local-tips-list">
        <?php foreach ($lines as $line): ?>
        <li><?= h(ltrim($line, '-•* ')) ?></li>
        <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p class="local-tips-body"><?= nl2br(h($tipsRaw)) ?></p>
    <?php endif; ?>
</section>
