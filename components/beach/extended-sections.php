<?php
/**
 * Beach Detail: Extended Content Sections
 * Renders grouped sections: "Plan Your Visit" (best_time, what_to_bring)
 * then "About This Beach" (history, nearby, local_tips).
 * Skips getting_there.
 *
 * Expects: $extendedSections, $lang
 */
?>
            <?php if (!empty($extendedSections)): ?>
            <div class="extended-content space-y-6 mt-8">
                <?php
                $planSections = ["best_time", "what_to_bring"];
                $aboutSections = ["history", "nearby", "local_tips"];
                $planHeadingShown = false;
                $aboutHeadingShown = false;

                // Reorder: Plan sections first, then About sections
                $orderedSections = [];
                $planGroup = [];
                $aboutGroup = [];
                $otherGroup = [];
                foreach ($extendedSections as $s) {
                    if ($s['section_type'] === 'getting_there') continue;
                    if (in_array($s['section_type'], $planSections)) $planGroup[] = $s;
                    elseif (in_array($s['section_type'], $aboutSections)) $aboutGroup[] = $s;
                    else $otherGroup[] = $s;
                }
                $orderedSections = array_merge($planGroup, $aboutGroup, $otherGroup);
                ?>
                <?php foreach ($orderedSections as $section): ?>
                <?php if (!$planHeadingShown && in_array($section["section_type"], $planSections)): $planHeadingShown = true; ?>
                <div class="flex items-center gap-3 mt-4 mb-2">
                    <span class="text-xs font-medium uppercase tracking-widest text-warm-400 whitespace-nowrap"><?= h($lang === "es" ? "Planifica Tu Visita" : "Plan Your Visit") ?></span>
                    <div class="flex-1 h-px bg-warm-100"></div>
                </div>
                <?php endif; ?>
                <?php if (!$aboutHeadingShown && in_array($section["section_type"], $aboutSections)): $aboutHeadingShown = true; ?>
                <div class="flex items-center gap-3 mt-4 mb-2">
                    <span class="text-xs font-medium uppercase tracking-widest text-warm-400 whitespace-nowrap"><?= h($lang === "es" ? "Sobre Esta Playa" : "About This Beach") ?></span>
                    <div class="flex-1 h-px bg-warm-100"></div>
                </div>
                <?php endif; ?>
                    <?php
                    $_sectionHeading = ($lang === 'es' && !empty($section['heading_es']))
                        ? $section['heading_es'] : $section['heading'];
                    $_sectionContent = ($lang === 'es' && !empty($section['content_es']))
                        ? $section['content_es'] : $section['content'];
                    // Map section_type to nav-friendly hyphenated id; local_tips is exposed as "tips" in section nav.
                    $_idMap = ['local_tips' => 'tips'];
                    $_sectionId = 'section-' . str_replace('_', '-', $_idMap[$section['section_type']] ?? $section['section_type']);
                    ?>
                    <details id="<?= h($_sectionId) ?>" class="group beach-detail-card rounded-xl overflow-hidden scroll-mt-[120px]">
                        <summary class="flex items-center justify-between gap-3 px-5 py-4 cursor-pointer list-none select-none hover:bg-warm-50 transition-colors">
                            <h2 class="text-lg font-bold text-warm-900 flex items-center gap-2">
                                <i data-lucide="<?= h(CONTENT_SECTIONS[$section['section_type']]['icon'] ?? 'info') ?>" class="w-5 h-5 text-sunset-400" aria-hidden="true"></i>
                                <span><?= h($_sectionHeading) ?></span>
                            </h2>
                            <i data-lucide="chevron-down" class="w-5 h-5 text-warm-400 flex-shrink-0 transition-transform group-open:rotate-180" aria-hidden="true"></i>
                        </summary>
                        <div class="px-5 pb-5">
                            <div class="prose prose-brand max-w-none text-base">
                                <?= sanitizeContentHtml($_sectionContent) ?>
                            </div>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Gallery (if exists) -->
            <?php if (!empty($beach['gallery'])): ?>
            <section>
                <h2 class="text-lg font-bold text-warm-900 mb-3 flex items-center gap-2">
                    <i data-lucide="images" class="w-5 h-5 text-sunset-400" aria-hidden="true"></i>
                    <?= h(__('beach.photos')) ?>
                </h2>
                <div class="gallery-grid">
                    <?php foreach ($beach['gallery'] as $idx => $image): ?>
                    <img src="<?= h($image) ?>" alt="<?= h($beach['name']) ?> - <?= h(__('beach.photo_label')) ?> <?= $idx + 1 ?>"
                         class="rounded-lg cursor-pointer hover:opacity-90 transition-opacity gallery-image"
                         data-gallery-index="<?= $idx ?>" data-action="openLightbox" data-action-args='[<?= $idx ?>]' loading="lazy">
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
