<?php
/**
 * Shared collection explorer block (hero + toolbar + results).
 *
 * Required:
 * - $collectionKey (string)
 * - $collectionData (array)
 * - $collectionContext (array)
 * - $collectionState (array)
 *
 * Optional:
 * - $collectionAnchorId (string)
 * - $userFavorites (array)
 */

$collectionAnchorId = $collectionAnchorId ?? 'collection-explorer';
$userFavorites = $userFavorites ?? [];
?>
<section id="<?= h($collectionAnchorId) ?>" class="collection-page scroll-mt-24">
    <div id="collection-explorer-root"
         class="collection-page__inner"
         data-collection="<?= h($collectionKey) ?>"
         data-default-sort="<?= h($collectionContext['default_sort'] ?? 'rating') ?>"
         data-default-limit="<?= h((string)($collectionContext['default_limit'] ?? 15)) ?>"
         data-authenticated="<?= isAuthenticated() ? '1' : '0' ?>"
         data-csrf="<?= h(csrfToken()) ?>">
        <?php include __DIR__ . '/hero.php'; ?>
        <?php include __DIR__ . '/toolbar.php'; ?>
        <div id="collection-results">
            <?php include __DIR__ . '/results.php'; ?>
        </div>
    </div>
</section>
