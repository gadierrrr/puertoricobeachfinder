<?php
/**
 * Beach Detail: Sidebar (Right Column)
 * Weather widget, map, amenities, practical info.
 *
 * Expects: $beach, $lang, $sunTimes
 */
?>
            <!-- Right Column: Sidebar -->
        <div class="lg:w-[37%] mt-8 lg:mt-0">
            <div class="lg:sticky lg:top-24 space-y-4">

                <!-- Weather Widget (loaded client-side) — desktop only; mobile uses .weather-strip in beach.php -->
                <div class="beach-detail-card p-4 hidden lg:block" id="weather-widget-container" style="max-height: 200px; overflow: hidden;"
                     data-lat="<?= h($beach['lat']) ?>" data-lng="<?= h($beach['lng']) ?>">
                    <div class="animate-pulse">
                        <div class="flex items-center justify-between mb-3">
                            <div class="h-6 bg-warm-100 rounded w-32"></div>
                            <div class="h-8 bg-warm-100 rounded w-16"></div>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <div class="h-14 bg-warm-100 rounded"></div>
                            <div class="h-14 bg-warm-100 rounded"></div>
                            <div class="h-14 bg-warm-100 rounded"></div>
                        </div>
                    </div>
                </div>

                <!-- Map + Directions -->
                <div id="section-map" class="scroll-mt-[120px]"></div>
                <div class="beach-detail-card overflow-hidden">
                    <div id="beach-map" class="h-40"></div>
                    <div class="p-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-warm-500 inline-flex items-center gap-1">
                                <i data-lucide="map-pin" class="w-3 h-3 text-sunset-400" aria-hidden="true"></i>
                                <?= h($beach['municipality']) ?>
                            </span>
                            <?php if ($beach['lat'] && $beach['lng']): ?>
                            <span class="text-xs text-warm-500"><?= number_format($beach['lat'], 4) ?>°N, <?= number_format(abs($beach['lng']), 4) ?>°W</span>
                            <?php endif; ?>
                        </div>
                        <a href="<?= h(getDirectionsUrl($beach)) ?>" target="_blank"
                           data-bf-track="directions"
                           data-bf-beach-id="<?= h($beach['id']) ?>"
                           data-bf-beach-slug="<?= h($beach['slug']) ?>"
                           data-bf-municipality="<?= h($beach['municipality']) ?>"
                           data-bf-source="beach_page_map"
                           class="mt-2 block w-full text-center bg-sunset-400 hover:bg-sunset-300 text-ocean-900 py-2 rounded-lg font-medium text-sm transition-colors">
                            <?= h(__('beach.get_directions')) ?>
                        </a>
                    </div>
                </div>

                <!-- Report conditions (check-in) — opens the previously-unreachable check-in modal -->
                <div class="beach-detail-card p-4">
                    <h3 class="font-bold text-warm-900 text-sm mb-1"><?= h(__('beach.checkin_sidebar_title')) ?></h3>
                    <p class="text-xs text-warm-500 mb-3"><?= h(__('beach.checkin_sidebar_desc')) ?></p>
                    <button type="button"
                            data-action="openCheckinModal"
                            data-action-args='<?= h(json_encode([$beach['id'], $beach['name']])) ?>'
                            class="w-full inline-flex items-center justify-center gap-2 bg-ocean-500 hover:bg-ocean-600 text-white py-2 rounded-lg font-medium text-sm transition-colors">
                        <i data-lucide="map-pin" class="w-4 h-4" aria-hidden="true"></i>
                        <?= h(__('beach.checkin_sidebar_cta')) ?>
                    </button>
                </div>

                <!-- Amenities -->
                <?php if (!empty($beach['amenities'])): ?>
                <div class="beach-detail-card p-4">
                    <h3 class="font-bold text-warm-900 text-sm mb-3"><?= h(__('beach.amenities_title')) ?></h3>
                    <div class="flex flex-wrap gap-1.5">
                        <?php foreach ($beach['amenities'] as $amenity): ?>
                        <span class="inline-flex items-center gap-1 text-xs bg-warm-50 text-warm-600 px-2 py-1 rounded">
                            <i data-lucide="check" class="w-3 h-3 text-green-400" aria-hidden="true"></i>
                            <?= h(getAmenityLabel($amenity)) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Practical Info -->
                <div class="beach-detail-card p-4 space-y-3">
                    <h3 class="font-bold text-warm-900 text-sm"><?= h(__('beach.practical_info')) ?></h3>
                    <?php if ($beach['safety_info']): ?>
                    <div class="text-sm">
                        <span class="text-amber-400 inline-flex items-center gap-1"><i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i> <?= h(__('beach.safety')) ?></span>
                        <p class="text-warm-500 text-xs mt-0.5"><?= h(($lang === 'es' && !empty($beach['safety_info_es'])) ? $beach['safety_info_es'] : $beach['safety_info']) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($sunTimes): ?>
                    <div class="flex gap-4 text-xs">
                        <span class="text-warm-500 inline-flex items-center gap-1"><i data-lucide="sunrise" class="w-3.5 h-3.5 text-orange-400"></i> <?= h($sunTimes['sunrise']) ?></span>
                        <span class="text-warm-500 inline-flex items-center gap-1"><i data-lucide="sunset" class="w-3.5 h-3.5 text-rose-400"></i> <?= h($sunTimes['sunset']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div><!-- End Right Column -->

    </div><!-- End Two-Column Layout -->

    <?php if ($beachReferralBottom !== ''): ?>
    <section class="mt-8">
        <?= $beachReferralBottom ?>
    </section>
    <?php endif; ?>
