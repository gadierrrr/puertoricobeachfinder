<?php
/**
 * Beach Detail: Reviews Section
 * User reviews with ratings, helpful votes. Only rendered when reviews exist.
 *
 * Expects: $beach, $lang, $reviews, $avgUserRating, $userReviewCount
 */
?>
            <!-- Reviews - Hidden when empty -->
            <?php if (!empty($reviews)): ?>
            <section id="reviews">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-bold text-warm-900"><?= h(__('beach.reviews_title')) ?></h2>
                        <?php if ($avgUserRating): ?>
                        <div class="flex items-center gap-1 text-sm">
                            <span class="text-sunset-400">★</span>
                            <span class="text-warm-900"><?= number_format($avgUserRating, 1) ?></span>
                            <span class="text-warm-500">(<?= $userReviewCount ?>)</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if (isAuthenticated()): ?>
                    <button data-action="openReviewForm" data-action-args='["<?= h($beach['id']) ?>","<?= h(addslashes($beach['name'])) ?>"]'
                            class="bg-sunset-400 hover:bg-sunset-300 text-ocean-900 px-3 py-1.5 rounded-lg font-medium text-sm transition-colors">
                        <?= h(__('beach.write_review')) ?>
                    </button>
                    <?php else: ?>
                    <a href="<?= h(routeUrl('login', $lang)) ?>?redirect=<?= urlencode(routeUrl('beach_detail', $lang, ['slug' => $beach['slug']]) . '#reviews') ?>"
                       class="text-sm text-sunset-400 hover:text-sunset-300 font-medium"><?= h(__('beach.sign_in_to_review')) ?></a>
                    <?php endif; ?>
                </div>
                <?php if (!empty($reviews)): ?>
                <div class="space-y-3">
                    <?php foreach ($reviews as $review): ?>
                    <?php include APP_ROOT . '/components/review-card.php'; ?>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-sm text-warm-500"><?= h(__('beach.no_reviews_yet')) ?></p>
                <?php endif; ?>
            </section>

        </div><!-- End Left Column -->

        <?php endif; // reviews ?>
