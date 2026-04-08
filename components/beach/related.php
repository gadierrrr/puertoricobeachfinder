<?php
/**
 * Beach Detail: Related Guides + Similar Beaches
 * Planning guides linked by tag, and similar beach cards.
 *
 * Expects: $beach, $lang, $beachReferralBottom
 */
?>
    <!-- Related Planning Guides -->
    <?php
    $relatedGuides = getRelatedGuides($beach['tags'], 3);
    if (!empty($relatedGuides)):
    ?>
    <section class="mt-8 pt-6 border-t border-warm-200">
        <h2 class="text-lg font-bold text-warm-900 mb-4 flex items-center gap-2">
            <i data-lucide="book-open" class="w-5 h-5 text-sunset-400" aria-hidden="true"></i>
            <?= h(__('beach.planning_visit')) ?>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php foreach ($relatedGuides as $guide): ?>
            <a href="<?= h($guide['url']) ?>" class="block bg-warm-50 hover:bg-warm-100 rounded-xl p-5 border border-warm-200 hover:border-sunset-400/50 transition-all group">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-sunset-400/20 flex items-center justify-center group-hover:bg-sunset-400/30 transition-colors">
                        <i data-lucide="<?= h($guide['icon']) ?>" class="w-5 h-5 text-sunset-400" aria-hidden="true"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-warm-900 text-sm mb-1 group-hover:text-sunset-400 transition-colors">
                            <?= h($guide['title']) ?>
                        </h3>
                        <p class="text-xs text-warm-500"><?= h(__('beach.essential_tips')) ?></p>
                    </div>
                    <i data-lucide="arrow-right" class="w-4 h-4 text-warm-400 group-hover:text-sunset-400 transition-colors flex-shrink-0" aria-hidden="true"></i>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Similar Beaches - Full Width Below -->
    <?php
    $similarBeaches = getSimilarBeaches($beach['id'], $beach['tags'], 4);
    if (!empty($similarBeaches)):
    ?>
    <section class="mt-8 pt-6 border-t border-warm-200">
        <h2 class="text-lg font-bold text-warm-900 mb-4 flex items-center gap-2">
            <i data-lucide="sparkles" class="w-5 h-5 text-sunset-400" aria-hidden="true"></i>
            <?= h(__('beach.similar_beaches')) ?>
        </h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($similarBeaches as $similar): ?>
            <a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $similar['slug']])) ?>" class="group beach-detail-card overflow-hidden hover:border-sunset-400/30 transition-all">
                <div class="aspect-video relative overflow-hidden">
                    <img src="<?= h(getThumbnailUrl($similar['cover_image'])) ?>" alt="<?= h($similar['name']) ?>"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                </div>
                <div class="p-3">
                    <h3 class="font-semibold text-warm-900 text-sm group-hover:text-sunset-400 transition-colors line-clamp-1"><?= h($similar['name']) ?></h3>
                    <p class="text-xs text-warm-500"><?= h($similar['municipality']) ?></p>
                    <?php if ($similar['google_rating']): ?>
                    <div class="flex items-center gap-1 mt-1">
                        <svg class="w-3 h-3" viewBox="0 0 24 24"><path fill="#FACC15" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <span class="text-xs font-medium text-sunset-400"><?= number_format($similar['google_rating'], 1) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</div>
</div>
