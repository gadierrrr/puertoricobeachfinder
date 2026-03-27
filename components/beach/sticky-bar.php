<?php
/**
 * Beach Detail: Sticky Action Bar (Mobile)
 * Fixed bottom bar with Directions, Save, Share buttons.
 *
 * Expects: $beach, $lang, $isFavorite
 */
?>
<!-- Sticky Quick Actions Bar (Mobile Only): Directions / Save / Share -->
<?php
$directionsUrl = getDirectionsUrl($beach);
$isFavorite = false;
if (isAuthenticated()) {
    $existingFav = queryOne(
        'SELECT id FROM user_favorites WHERE user_id = :user_id AND beach_id = :beach_id',
        [':user_id' => $_SESSION['user_id'], ':beach_id' => $beach['id']]
    );
    $isFavorite = (bool)$existingFav;
}
?>
<div class="beach-sticky-bar"
     aria-label="Quick actions"
     data-bf-beach-id="<?= h($beach['id']) ?>"
     data-bf-beach-slug="<?= h($beach['slug']) ?>"
     data-bf-municipality="<?= h($beach['municipality']) ?>"
     data-bf-source="beach_page_sticky">
    <a href="<?= h($directionsUrl) ?>"
       target="_blank"
       rel="noopener"
       data-bf-track="directions"
       class="sticky-directions"
       aria-label="Get directions">
        <i data-lucide="navigation" class="w-4 h-4" aria-hidden="true"></i>
        <span><?= h(__('beach.directions')) ?></span>
    </a>

    <button type="button"
            class="sticky-directions"
            style="background: rgba(255,255,255,0.10); color: white;"
            data-action="toggleStickyFavorite"
            aria-label="<?= $isFavorite ? 'Remove from favorites' : 'Add to favorites' ?>"
            aria-pressed="<?= $isFavorite ? 'true' : 'false' ?>"
            id="sticky-favorite-btn">
        <span id="sticky-favorite-icon" aria-hidden="true"><?= $isFavorite ? '❤️' : '🤍' ?></span>
        <span><?= h(__('beach.save')) ?></span>
    </button>

    <button type="button"
            class="sticky-directions"
            style="background: rgba(255,255,255,0.10); color: white;"
            data-action="shareBeach" data-action-args='["<?= h($beach['slug']) ?>","<?= h(addslashes($beach['name'])) ?>"]'
            aria-label="<?= h(__('beach.share')) ?>">
        <i data-lucide="share-2" class="w-4 h-4" aria-hidden="true"></i>
        <span><?= h(__('beach.share')) ?></span>
    </button>
</div>

