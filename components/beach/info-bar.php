<?php
/**
 * Beach Detail: Quick Info Bar
 * Rating badge, tags, directions button, share button.
 * Includes referral hero placement.
 *
 * Expects: $beach, $lang, $avgUserRating, $userReviewCount, $beachReferralHero
 */
?>
    <!-- Quick Info Bar -->
    <div class="flex flex-wrap items-center gap-3 p-3 bg-white shadow-sm rounded-xl border border-warm-200 mb-6">
        <?php if ($beach['google_rating']): ?>
        <div class="flex items-center gap-1.5 bg-sunset-400/10 border border-sunset-400/30 px-3 py-1.5 rounded-lg" aria-label="Google rating: <?= number_format($beach['google_rating'], 1) ?> out of 5">
            <svg class="w-4 h-4" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#FACC15" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
            <span class="font-bold text-sunset-400"><?= number_format($beach['google_rating'], 1) ?></span>
            <?php if ($beach['google_review_count']): ?>
            <span class="text-warm-500 text-sm">(<?= number_format($beach['google_review_count']) ?>)</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($avgUserRating): ?>
        <div class="flex items-center gap-1.5 bg-cyan-500/10 border border-cyan-500/30 px-3 py-1.5 rounded-lg" aria-label="Community rating: <?= number_format($avgUserRating, 1) ?> out of 5">
            <i data-lucide="star" class="w-4 h-4 text-cyan-400 fill-cyan-400" aria-hidden="true"></i>
            <span class="font-bold text-cyan-400"><?= number_format($avgUserRating, 1) ?></span>
            <span class="text-warm-500 text-sm">(<?= $userReviewCount ?>)</span>
        </div>
        <?php endif; ?>

        <?php if (!empty($beach['tags'])): ?>
        <div class="flex flex-wrap gap-2">
            <?php foreach (array_slice($beach['tags'], 0, 3) as $tag): ?>
            <a href="<?= h(getTagPageUrl($tag, $lang)) ?>" class="text-sm bg-warm-100 hover:bg-warm-200 text-warm-700 px-3 py-1.5 rounded-full transition-colors min-h-[36px] flex items-center">
                <?= h(getTagLabel($tag)) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="ml-auto">
            <button data-action="shareBeach" data-action-args='["<?= h($beach['slug']) ?>","<?= h(addslashes($beach['name'])) ?>"]' aria-label="Share this beach"
                    class="inline-flex items-center gap-1.5 bg-warm-100 hover:bg-warm-200 text-warm-900 px-3 py-2 rounded-lg text-sm transition-colors">
                <i data-lucide="share-2" class="w-4 h-4" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <?php if ($beachReferralHero !== ''): ?>
    <div class="mb-6">
        <?= $beachReferralHero ?>
    </div>
    <?php endif; ?>


