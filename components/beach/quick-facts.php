<?php
/**
 * Beach Detail: Quick Facts Grid
 * 2x2 grid with Best For, Best Time, Parking, Access cards.
 * Best Time and Parking are clickable links to full sections.
 *
 * Expects: $beach, $lang, $beachReferralMid
 */
?>
            <!-- Quick Facts - Condensed 2x2 Grid -->
            <div id="section-overview"></div>
            <section>
                <h2 class="text-lg font-bold text-white mb-3 flex items-center gap-2">
                    <i data-lucide="clipboard-list" class="w-5 h-5 text-brand-yellow" aria-hidden="true"></i>
                    <span><?= h(__('beach.quick_facts')) ?></span>
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    <?php if (!empty($beach['tags'])): ?>
                    <div class="bg-white/5 border border-white/10 rounded-lg p-3 flex gap-3">
                        <div class="bg-brand-yellow/20 rounded p-2 flex-shrink-0 self-start">
                            <span class="text-brand-yellow text-sm">🏄</span>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] uppercase tracking-wider text-white/40"><?= h(__('beach.best_for')) ?></div>
                            <div class="text-white font-semibold text-sm"><?= h(getTagLabel($beach['tags'][0])) ?></div>
                            <?php if (count($beach['tags']) > 1): ?>
                            <div class="text-white/40 text-xs"><?= h(__('beach.tags_more', ['count' => count($beach['tags']) - 1])) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($beach['best_time']): ?>
                    <a href="#section-best_time" class="bg-white/5 border border-white/10 rounded-lg p-3 flex gap-3 hover:border-brand-yellow/30 transition-colors group cursor-pointer">
                        <div class="bg-brand-yellow/20 rounded p-2 flex-shrink-0 self-start">
                            <span class="text-brand-yellow text-sm">🕐</span>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] uppercase tracking-wider text-white/40"><?= h(__('beach.best_time')) ?></div>
                            <div class="text-white font-semibold text-sm"><?php
                                $_btRaw = ($lang === 'es' && !empty($beach['best_time_es'])) ? $beach['best_time_es'] : $beach['best_time'];
                                $_btClean = strip_tags($_btRaw);
                                if (preg_match('/^([^.!?]{10,45}[.!?])/', $_btClean, $_btM)) {
                                    echo h(trim($_btM[1]));
                                } else {
                                    $_v = mb_substr($_btClean, 0, 35);
                                    $_sp = mb_strrpos($_v, ' ');
                                    if ($_sp > 15) $_v = mb_substr($_v, 0, $_sp);
                                    echo h(rtrim($_v, ',;: '));
                                }
                            ?></div>
                            <div class="text-brand-yellow/60 text-xs group-hover:text-brand-yellow"><?= h($lang === 'es' ? 'Más ↓' : 'More ↓') ?></div>
                        </div>
                    </a>
                    <?php endif; ?>

                    <?php if ($beach['parking_details']): ?>
                    <a href="#section-map" class="bg-white/5 border border-white/10 rounded-lg p-3 flex gap-3 hover:border-brand-yellow/30 transition-colors group cursor-pointer">
                        <div class="bg-brand-yellow/20 rounded p-2 flex-shrink-0 self-start">
                            <span class="text-brand-yellow text-sm">🚗</span>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] uppercase tracking-wider text-white/40"><?= h(__('beach.parking')) ?></div>
                            <div class="text-white font-semibold text-sm"><?php
                                $_parkingVal = ($lang === 'es' && !empty($beach['parking_details_es'])) ? $beach['parking_details_es'] : $beach['parking_details'];
                                $_pkClean = strip_tags($_parkingVal);
                                if (preg_match('/^([^.!?]{10,45}[.!?])/', $_pkClean, $_pkM)) {
                                    echo h(trim($_pkM[1]));
                                } else {
                                    $_v = mb_substr($_pkClean, 0, 35);
                                    $_sp = mb_strrpos($_v, ' ');
                                    if ($_sp > 15) $_v = mb_substr($_v, 0, $_sp);
                                    echo h(rtrim($_v, ',;: '));
                                }
                            ?></div>
                            <div class="text-brand-yellow/60 text-xs group-hover:text-brand-yellow"><?= h($lang === 'es' ? 'Detalles ↓' : 'Details ↓') ?></div>
                        </div>
                    </a>
                    <?php endif; ?>

                    <?php if ($beach['access_label']): ?>
                    <div class="bg-white/5 border border-white/10 rounded-lg p-3 flex gap-3">
                        <div class="bg-brand-yellow/20 rounded p-2 flex-shrink-0 self-start">
                            <span class="text-brand-yellow text-sm">🚶</span>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] uppercase tracking-wider text-white/40"><?= h(__('beach.access')) ?></div>
                            <div class="text-white font-semibold text-sm"><?= h($beach['access_label']) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

