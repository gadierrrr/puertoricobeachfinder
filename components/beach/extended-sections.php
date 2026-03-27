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
                    <span class="text-[11px] uppercase tracking-wider text-white/30 whitespace-nowrap"><?= h($lang === "es" ? "Planifica Tu Visita" : "Plan Your Visit") ?></span>
                    <div class="flex-1 h-px bg-white/10"></div>
                </div>
                <?php endif; ?>
                <?php if (!$aboutHeadingShown && in_array($section["section_type"], $aboutSections)): $aboutHeadingShown = true; ?>
                <div class="flex items-center gap-3 mt-4 mb-2">
                    <span class="text-[11px] uppercase tracking-wider text-white/30 whitespace-nowrap"><?= h($lang === "es" ? "Sobre Esta Playa" : "About This Beach") ?></span>
                    <div class="flex-1 h-px bg-white/10"></div>
                </div>
                <?php endif; ?>
                    <?php
                    $_sectionHeading = ($lang === 'es' && !empty($section['heading_es']))
                        ? $section['heading_es'] : $section['heading'];
                    $_sectionContent = ($lang === 'es' && !empty($section['content_es']))
                        ? $section['content_es'] : $section['content'];
                    ?>
                    <section class="beach-detail-card p-6 rounded-xl" id="section-<?= h($section['section_type']) ?>">
                        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                            <i data-lucide="<?= h(CONTENT_SECTIONS[$section['section_type']]['icon'] ?? 'info') ?>" class="w-5 h-5 text-brand-yellow"></i>
                            <?= h($_sectionHeading) ?>
                        </h2>
                        <div class="prose prose-invert prose-brand max-w-none">
                            <?= sanitizeContentHtml($_sectionContent) ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Gallery (if exists) -->
            <?php if (!empty($beach['gallery'])): ?>
            <section>
                <h2 class="text-lg font-bold text-white mb-3 flex items-center gap-2">
                    <i data-lucide="images" class="w-5 h-5 text-brand-yellow" aria-hidden="true"></i>
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
