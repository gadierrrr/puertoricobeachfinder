<?php
/**
 * Lead block for the best-family-beaches guide.
 *
 * Rendered by collection/explorer.php between the hero and the toolbar via
 * $collectionLeadInclude: sticky section nav, guide intro with the winter-swell
 * rule, and the ranking-methodology aside.
 */
?>
<nav class="guide-jumpnav" aria-label="<?= h(__('pages.best_family_beaches.jump_to')) ?>">
    <div class="guide-jumpnav__inner">
        <span class="guide-jumpnav__label" aria-hidden="true"><?= h(__('pages.best_family_beaches.jump_to')) ?></span>
        <a href="#collection-results"><?= h(__('pages.best_family_beaches.jump_top_beaches')) ?></a>
        <a href="#natural-pools"><?= h(__('pages.best_family_beaches.jump_pools')) ?></a>
        <a href="#more-spots"><?= h(__('pages.best_family_beaches.jump_more')) ?></a>
        <a href="#safety"><?= h(__('pages.best_family_beaches.jump_safety')) ?></a>
        <a href="#faq"><?= h(__('pages.best_family_beaches.jump_faq')) ?></a>
        <a href="#map"><?= h(__('pages.best_family_beaches.jump_map')) ?></a>
    </div>
</nav>
<div class="guide-lead">
    <div class="guide-lead__intro">
        <p><?= __('pages.best_family_beaches.intro_p1') ?></p>
        <p><?= __('pages.best_family_beaches.intro_p2') ?></p>
        <div class="guide-rulebox"><?= __('pages.best_family_beaches.intro_p3') ?></div>
    </div>
    <aside class="guide-lead__aside">
        <h2><?= h(__('pages.best_family_beaches.ranking_title')) ?></h2>
        <p><?= __('pages.best_family_beaches.ranking_body') ?></p>
    </aside>
</div>
