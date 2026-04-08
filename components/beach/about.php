<?php
/**
 * Beach Detail: About + Visitor Tips
 * Beach description, feature badges, and bullet-point visitor tips.
 *
 * Expects: $beach, $lang
 */
?>
            <!-- About + Highlights Merged -->
            <section class="border-l-2 border-sunset-400/20 pl-5">
                <h2 class="text-2xl font-bold text-warm-900 mb-3"><?= h(__('beach.about')) ?> <?= h($beach['name']) ?></h2>
                <?php
                $_aboutDesc = ($lang === 'es' && !empty($beach['description_es']))
                    ? $beach['description_es']
                    : ($beach['description'] ?? '');
                if ($_aboutDesc): ?>
                <p class="text-warm-600 leading-relaxed mb-4"><?= nl2br(h($_aboutDesc)) ?></p>
                <?php endif; ?>

                <?php if (!empty($beach['features'])): ?>
                <div class="flex flex-wrap gap-2 mt-3">
                    <?php foreach ($beach['features'] as $feature): ?>
                    <span class="inline-flex items-center gap-1.5 bg-warm-50 border border-warm-200 text-warm-700 px-3 py-1.5 rounded-lg text-sm">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-sunset-400" aria-hidden="true"></i>
                        <?= h(($lang === 'es' && !empty($feature['title_es'])) ? $feature['title_es'] : $feature['title']) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>

            <!-- Visitor Tips -->
            <?php if (!empty($beach['tips'])): ?>
            <section>
                <h2 class="text-lg font-bold text-warm-900 mb-3 flex items-center gap-2">
                    <i data-lucide="lightbulb" class="w-5 h-5 text-sunset-400" aria-hidden="true"></i>
                    <span><?= h(__('beach.visitor_tips')) ?></span>
                </h2>
                <ul class="space-y-3">
                    <?php foreach ($beach['tips'] as $tip): ?>
                    <li class="flex items-start gap-3">
                        <span class="yellow-bullet mt-[7px] flex-shrink-0"></span>
                        <span class="text-warm-600 text-base leading-relaxed"><?= h(($lang === 'es' && !empty($tip['tip_es'])) ? $tip['tip_es'] : $tip['tip']) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php endif; ?>

            <!-- Extended Content Sections -->
