<?php
/**
 * Beach Detail: About + Visitor Tips
 * Beach description, feature badges, and bullet-point visitor tips.
 *
 * Expects: $beach, $lang
 */
?>
            <!-- About + Highlights (collapsible) -->
            <?php
            $_aboutDesc = ($lang === 'es' && !empty($beach['description_es']))
                ? $beach['description_es']
                : ($beach['description'] ?? '');
            ?>
            <details id="section-about" class="group beach-detail-card rounded-xl overflow-hidden scroll-mt-[120px]">
                <summary class="flex items-center justify-between gap-3 px-5 py-4 cursor-pointer list-none select-none hover:bg-warm-50 transition-colors">
                    <h2 class="text-lg font-bold text-warm-900 flex items-center gap-2">
                        <i data-lucide="info" class="w-5 h-5 text-sunset-400" aria-hidden="true"></i>
                        <span><?= h(__('beach.about')) ?> <?= h($beach['name']) ?></span>
                    </h2>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-warm-400 flex-shrink-0 transition-transform group-open:rotate-180" aria-hidden="true"></i>
                </summary>
                <div class="px-5 pb-5">
                    <?php if ($_aboutDesc): ?>
                    <p class="text-warm-600 leading-relaxed"><?= nl2br(h($_aboutDesc)) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($beach['features'])): ?>
                    <div class="flex flex-wrap gap-2 mt-4">
                        <?php foreach ($beach['features'] as $feature): ?>
                        <span class="inline-flex items-center gap-1.5 bg-warm-50 border border-warm-200 text-warm-700 px-3 py-1.5 rounded-lg text-sm">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5 text-sunset-400" aria-hidden="true"></i>
                            <?= h(($lang === 'es' && !empty($feature['title_es'])) ? $feature['title_es'] : $feature['title']) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </details>

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
