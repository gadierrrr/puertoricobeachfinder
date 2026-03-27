<?php
/**
 * Beach Detail: About + Visitor Tips
 * Beach description, feature badges, and bullet-point visitor tips.
 *
 * Expects: $beach, $lang
 */
?>
            <!-- About + Highlights Merged -->
            <section>
                <h2 class="text-xl font-bold text-white mb-3"><?= h(__('beach.about')) ?> <?= h($beach['name']) ?></h2>
                <?php
                $_aboutDesc = ($lang === 'es' && !empty($beach['description_es']))
                    ? $beach['description_es']
                    : ($beach['description'] ?? '');
                if ($_aboutDesc): ?>
                <p class="text-gray-300 leading-relaxed mb-4"><?= nl2br(h($_aboutDesc)) ?></p>
                <?php endif; ?>

                <?php if (!empty($beach['features'])): ?>
                <div class="flex flex-wrap gap-2 mt-3">
                    <?php foreach ($beach['features'] as $feature): ?>
                    <span class="inline-flex items-center gap-1.5 bg-white/5 border border-white/10 text-white/80 px-3 py-1.5 rounded-lg text-sm">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-brand-yellow" aria-hidden="true"></i>
                        <?= h(($lang === 'es' && !empty($feature['title_es'])) ? $feature['title_es'] : $feature['title']) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>

            <!-- Visitor Tips -->
            <?php if (!empty($beach['tips'])): ?>
            <section>
                <h2 class="text-lg font-bold text-white mb-3 flex items-center gap-2">
                    <i data-lucide="lightbulb" class="w-5 h-5 text-brand-yellow" aria-hidden="true"></i>
                    <span><?= h(__('beach.visitor_tips')) ?></span>
                </h2>
                <ul class="space-y-2">
                    <?php foreach ($beach['tips'] as $tip): ?>
                    <li class="flex items-start gap-2 text-sm">
                        <span class="yellow-bullet mt-1.5 flex-shrink-0"></span>
                        <span class="text-gray-300"><?= h(($lang === 'es' && !empty($tip['tip_es'])) ? $tip['tip_es'] : $tip['tip']) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php endif; ?>

            <!-- Extended Content Sections -->
