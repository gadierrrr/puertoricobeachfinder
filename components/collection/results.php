<?php
/**
 * Collection explorer results list.
 *
 * Required:
 * - $collectionData (array)
 * - $collectionState (array)
 * - $userFavorites (array<int|string>)
 */

$collectionData = $collectionData ?? [];
$collectionState = $collectionState ?? [];
$userFavorites = $userFavorites ?? [];

$beaches = $collectionData['beaches'] ?? [];
$total = intval($collectionData['total'] ?? 0);
$viewMode = in_array($collectionState['view'] ?? 'cards', ['cards', 'list', 'grid'], true)
    ? $collectionState['view']
    : 'cards';
$page = max(1, intval($collectionState['page'] ?? 1));
$limit = max(1, intval($collectionState['limit'] ?? 15));
$startRank = (($page - 1) * $limit) + 1;
$contextFallback = !empty($collectionData['context_fallback']);
?>
<section class="collection-results">
    <div class="collection-results__header">
        <p class="collection-results__count">
            Showing <strong><?= number_format(count($beaches)) ?></strong>
            of <strong><?= number_format($total) ?></strong> beaches
        </p>
        <div class="collection-view-switch" role="group" aria-label="Switch collection view">
            <?php foreach (['cards' => 'Cards', 'list' => 'List', 'grid' => 'Grid'] as $mode => $label): ?>
            <button type="button"
                    class="collection-view-switch__btn <?= $mode === $viewMode ? 'is-active' : '' ?>"
                    data-ce-action="set-view"
                    data-ce-view="<?= h($mode) ?>"
                    aria-pressed="<?= $mode === $viewMode ? 'true' : 'false' ?>"
                    aria-label="<?= h($label) ?> view">
                <?= h($label) ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($contextFallback): ?>
    <div class="collection-results__notice" role="status">
        No beaches matched this page's default context, so we're showing all beaches.
    </div>
    <?php endif; ?>

    <?php if (empty($beaches)): ?>
    <div class="collection-empty">
        <h3>No beaches match the current filters.</h3>
        <p>Try clearing filters or switching to all beaches.</p>
        <button type="button" class="collection-empty__btn" data-ce-action="clear-all">Clear all filters</button>
    </div>
    <?php else: ?>
    <div class="collection-results__list collection-results__list--<?= h($viewMode) ?>">
        <?php foreach ($beaches as $index => $beach):
            $rank = $startRank + $index;
            $isFavorite = in_array($beach['id'], $userFavorites, true);
            include __DIR__ . '/card.php';
        endforeach; ?>
    </div>
    <?php endif; ?>
</section>
