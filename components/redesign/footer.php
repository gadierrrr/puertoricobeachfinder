<?php
/**
 * Redesign v2 site footer. Included by components/footer.php when
 * $redesignLayout is set (mutually exclusive with the classic footer block).
 *
 * IMPORTANT: this footer must carry the same internal link hub as the classic
 * footer in components/footer.php (tools, collections, proximity pages,
 * municipalities, guides, account) — those links are load-bearing for SEO.
 * If you add or remove a link in one footer, mirror it in the other; the
 * Phase 3 SEO diff harness fails on any link lost between variants.
 */
$currentLang = $currentLang ?? getCurrentLanguage();
$homePath = $homePath ?? routeUrl('home', $currentLang);

$rdFootCollections = [
    ['best_beaches', __('footer.best_beaches')],
    ['best_beaches_san_juan', __('footer.san_juan_beaches')],
    ['best_surfing_beaches', __('footer.surfing_beaches')],
    ['best_snorkeling_beaches', __('footer.snorkeling_beaches')],
    ['best_family_beaches', __('footer.family_beaches')],
    ['hidden_beaches', __('footer.hidden_beaches')],
];
$rdFootMunicipalities = [
    'Cabo Rojo', 'Vieques', 'Aguadilla', 'Rincon',
    'Isabela', 'Arecibo', 'Manati', 'Culebra',
    'San Juan', 'Guanica', 'Dorado', 'Ponce',
];
$rdFootGuides = [
    ['guide_transportation', __('footer.transportation')],
    ['guide_safety', __('footer.safety_tips')],
    ['guide_best_time', __('footer.best_times_to_visit')],
    ['guide_packing', __('footer.packing_list')],
    ['guide_culebra_vieques', __('footer.culebra_vs_vieques')],
    ['guide_bio_bays', __('footer.bio_bays')],
    ['guide_snorkeling', __('footer.snorkeling_guide')],
    ['guide_surfing', __('footer.surfing_guide')],
];
$rdFootMoreGuides = [
    ['guide_photography', __('footer.photography_tips')],
    ['guide_family_planning', __('footer.family_planning')],
];
?>
    <footer class="rd rd-sitefoot">
        <div class="wrap">
            <div class="cols">
                <!-- Brand + Tools -->
                <div>
                    <div class="brand">
                        <svg class="sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="4.2"/><path d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M19.1 4.9l-1.8 1.8M6.7 17.3l-1.8 1.8" stroke-linecap="round"/></svg>
                        <span><?= h($_ENV['APP_NAME'] ?? 'Beach Finder') ?></span>
                    </div>
                    <p class="about"><?= h(__('footer.about', ['count' => '300+'])) ?></p>
                    <div class="colhead"><?= h(__('footer.tools')) ?></div>
                    <ul>
                        <li><a href="<?= h(routeUrl('quiz', $currentLang)) ?>"><?= h(__('footer.beach_match_quiz')) ?></a></li>
                        <li><a href="<?= h(routeUrl('compare', $currentLang)) ?>"><?= h(__('footer.compare_beaches')) ?></a></li>
                        <li><a href="<?= h($homePath . '?view=map') ?>"><?= h(__('footer.interactive_map')) ?></a></li>
                        <li><a href="<?= h(routeUrl('advertise', $currentLang)) ?>"><?= h($currentLang === 'es' ? 'Anuncia tu negocio' : 'Advertise your business') ?></a></li>
                    </ul>
                </div>

                <!-- Beaches by Activity / Location / Municipality -->
                <div>
                    <h4><?= h(__('footer.beaches_by_activity')) ?></h4>
                    <ul>
                        <?php foreach ($rdFootCollections as [$routeKey, $label]): ?>
                        <li><a href="<?= h(routeUrl($routeKey, $currentLang)) ?>"><?= h($label) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="colhead"><?= h(__('footer.by_location')) ?></div>
                    <ul>
                        <li><a href="<?= h(routeUrl('beaches_near_san_juan', $currentLang)) ?>"><?= h(__('footer.near_san_juan')) ?></a></li>
                        <li><a href="<?= h(routeUrl('beaches_near_airport', $currentLang)) ?>"><?= h(__('footer.near_airport')) ?></a></li>
                    </ul>
                    <div class="colhead"><?= h(__('footer.popular_municipalities')) ?></div>
                    <ul class="two-col">
                        <?php foreach ($rdFootMunicipalities as $muni):
                            $muniSlug = strtolower(str_replace(' ', '-', stripAccents($muni)));
                        ?>
                        <li><a href="<?= h(routeUrl('municipality', $currentLang, ['municipality' => $muniSlug])) ?>"><?= h($muni) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Planning Resources -->
                <div>
                    <h4><?= h(__('footer.planning_resources')) ?></h4>
                    <ul>
                        <li><a class="all" href="<?= h(routeUrl('guides_index', $currentLang)) ?>"><?= h(__('footer.all_guides')) ?> →</a></li>
                        <?php foreach ($rdFootGuides as [$routeKey, $label]): ?>
                        <li><a href="<?= h(routeUrl($routeKey, $currentLang)) ?>"><?= h($label) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- More Guides & Account -->
                <div>
                    <h4><?= h(__('footer.more_guides')) ?></h4>
                    <ul>
                        <?php foreach ($rdFootMoreGuides as [$routeKey, $label]): ?>
                        <li><a href="<?= h(routeUrl($routeKey, $currentLang)) ?>"><?= h($label) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if (isAuthenticated()): ?>
                    <div class="colhead"><?= h(__('footer.your_account')) ?></div>
                    <ul>
                        <li><a href="<?= h(routeUrl('favorites', $currentLang)) ?>"><?= h(__('footer.my_favorites')) ?></a></li>
                        <li><a href="<?= h(routeUrl('profile', $currentLang)) ?>"><?= h(__('nav.profile')) ?></a></li>
                    </ul>
                    <?php else: ?>
                    <a href="<?= h(routeUrl('login', $currentLang)) ?>" class="signin"><?= h(__('nav.sign_in')) ?></a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="legal">
                <p>&copy; <?= date('Y') ?> <?= h($_ENV['APP_NAME'] ?? 'Beach Finder') ?>. <?= h(__('footer.copyright')) ?></p>
            </div>
        </div>
    </footer>
