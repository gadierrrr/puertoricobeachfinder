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
                <h2 class="text-lg font-bold text-warm-900 mb-3 flex items-center gap-2">
                    <i data-lucide="clipboard-list" class="w-5 h-5 text-sunset-400" aria-hidden="true"></i>
                    <span><?= h(__('beach.quick_facts')) ?></span>
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    <?php if (!empty($beach['tags'])): ?>
                    <div class="card-glass">
                        <div class="bg-sunset-400/20 rounded-lg p-2.5 flex-shrink-0 self-start">
                            <i data-lucide="waves" class="w-4 h-4 text-sunset-400" aria-hidden="true"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[11px] uppercase tracking-wider text-warm-500 mb-0.5"><?= h(__('beach.best_for')) ?></div>
                            <div class="text-warm-900 font-semibold text-sm"><?= h(getTagLabel($beach['tags'][0])) ?></div>
                            <?php if (count($beach['tags']) > 1): ?>
                            <div class="text-warm-400 text-xs"><?= h(__('beach.tags_more', ['count' => count($beach['tags']) - 1])) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($beach['best_time']): ?>
                    <a href="#section-best_time" class="card-glass card-glass--interactive group">
                        <div class="bg-sunset-400/20 rounded-lg p-2.5 flex-shrink-0 self-start">
                            <i data-lucide="clock" class="w-4 h-4 text-sunset-400" aria-hidden="true"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[11px] uppercase tracking-wider text-warm-500 mb-0.5"><?= h(__('beach.best_time')) ?></div>
                            <div class="text-warm-900 font-semibold text-sm"><?php
                                $_btRaw = ($lang === 'es' && !empty($beach['best_time_es'])) ? $beach['best_time_es'] : $beach['best_time'];
                                $_btClean = strip_tags($_btRaw);
                                // Try first complete sentence up to 50 chars
                                if (preg_match('/^([^.!?]{10,50}[.!?])/', $_btClean, $_btM)) {
                                    echo h(trim($_btM[1]));
                                } else {
                                    // Fallback: extract key phrases with bullet separator
                                    $_phrases = preg_split('/[.,;]/', $_btClean, 3);
                                    $_short = trim($_phrases[0]);
                                    if (mb_strlen($_short) > 30) $_short = mb_substr($_short, 0, mb_strrpos(mb_substr($_short, 0, 30), ' ')) . '…';
                                    echo h($_short);
                                }
                            ?></div>
                            <div class="text-sunset-400/60 text-xs group-hover:text-sunset-400"><?= h($lang === 'es' ? 'Más ↓' : 'More ↓') ?></div>
                        </div>
                    </a>
                    <?php endif; ?>

                    <?php if ($beach['parking_details']): ?>
                    <a href="#section-map" class="card-glass card-glass--interactive group">
                        <div class="bg-sunset-400/20 rounded-lg p-2.5 flex-shrink-0 self-start">
                            <i data-lucide="car" class="w-4 h-4 text-sunset-400" aria-hidden="true"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[11px] uppercase tracking-wider text-warm-500 mb-0.5"><?= h(__('beach.parking')) ?></div>
                            <div class="text-warm-900 font-semibold text-sm"><?php
                                $_parkingVal = ($lang === 'es' && !empty($beach['parking_details_es'])) ? $beach['parking_details_es'] : $beach['parking_details'];
                                $_pkClean = strip_tags($_parkingVal);
                                if (preg_match('/^([^.!?]{10,50}[.!?])/', $_pkClean, $_pkM)) {
                                    echo h(trim($_pkM[1]));
                                } else {
                                    $_phrases = preg_split('/[.,;]/', $_pkClean, 3);
                                    $_short = trim($_phrases[0]);
                                    if (mb_strlen($_short) > 30) $_short = mb_substr($_short, 0, mb_strrpos(mb_substr($_short, 0, 30), ' ')) . '…';
                                    echo h($_short);
                                }
                            ?></div>
                            <div class="text-sunset-400/60 text-xs group-hover:text-sunset-400"><?= h($lang === 'es' ? 'Detalles ↓' : 'Details ↓') ?></div>
                        </div>
                    </a>
                    <?php endif; ?>

                    <?php if ($beach['access_label']): ?>
                    <div class="card-glass">
                        <div class="bg-sunset-400/20 rounded-lg p-2.5 flex-shrink-0 self-start">
                            <i data-lucide="footprints" class="w-4 h-4 text-sunset-400" aria-hidden="true"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-[11px] uppercase tracking-wider text-warm-500 mb-0.5"><?= h(__('beach.access')) ?></div>
                            <div class="text-warm-900 font-semibold text-sm"><?= h($beach['access_label']) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

