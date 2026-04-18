<?php
/**
 * Beach Detail: FAQ Section
 * Renders the auto-generated FAQs as visible content with accordion UI.
 * The same $faqs array is already used for JSON-LD FAQPage schema in beach.php.
 *
 * Expects: $faqs (array of ['question'=>..., 'answer'=>...]), $lang, $beach
 */
if (empty($faqs)) return;
$isEs = ($lang === 'es');

// Build contextual internal links for FAQ answers
$municipalitySlug = strtolower(str_replace(' ', '-', $beach['municipality']));
$municipalityUrl = routeUrl('municipality', $lang, ['municipality' => $municipalitySlug]);
$tags = $beach['tags'] ?? [];
$amenities = $beach['amenities'] ?? [];

// Tag slug -> English URL slug for /beaches/{slug} pages
$tagPageSlugs = [
    'swimming' => 'swimming', 'calm-waters' => 'calm-waters', 'scenic' => 'scenic',
    'fishing' => 'fishing', 'diving' => 'diving', 'camping' => 'camping',
    'accessible' => 'accessible', 'popular' => 'popular',
];
// Tag slug -> Spanish URL slug for /es/playas/{slug} pages
$tagPageSlugsEs = [
    'swimming' => 'natacion', 'calm-waters' => 'aguas-tranquilas', 'scenic' => 'escenicas',
    'fishing' => 'pesca', 'diving' => 'buceo', 'camping' => 'acampar',
    'accessible' => 'accesibles', 'popular' => 'populares',
];
// Amenity slug -> English URL slug for /beaches/{slug} pages
$amenityPageSlugs = [
    'parking' => 'with-parking', 'free-parking' => 'with-parking',
    'restrooms' => 'with-restrooms', 'showers' => 'with-showers',
    'lifeguard' => 'with-lifeguard', 'picnic-area' => 'with-picnic-areas',
    'food' => 'with-food',
];
$amenityPageSlugsEs = [
    'parking' => 'con-estacionamiento', 'free-parking' => 'con-estacionamiento',
    'restrooms' => 'con-banos', 'showers' => 'con-duchas',
    'lifeguard' => 'con-salvavidas', 'picnic-area' => 'con-areas-picnic',
    'food' => 'con-comida',
];

/** Build a tag/amenity page URL */
function _faqTagUrl(string $enSlug, string $lang, array $esMap): string {
    if ($lang === 'es' && isset($esMap[$enSlug])) {
        return '/es/playas/' . $esMap[$enSlug];
    }
    return '/beaches/' . $enSlug;
}
?>
    <section id="faq" class="mt-8 pt-6 border-t border-warm-200">
        <h2 class="text-lg font-bold text-warm-900 mb-4 flex items-center gap-2">
            <i data-lucide="help-circle" class="w-5 h-5 text-sunset-400" aria-hidden="true"></i>
            <?= $isEs ? 'Preguntas Frecuentes' : 'Frequently Asked Questions' ?>
        </h2>
        <div class="space-y-3">
            <?php foreach ($faqs as $i => $faq): ?>
            <details class="group bg-warm-50 rounded-xl border border-warm-200 overflow-hidden">
                <summary class="flex items-center justify-between gap-3 px-5 py-4 cursor-pointer list-none select-none hover:bg-warm-100 transition-colors">
                    <span class="font-semibold text-warm-900 text-sm"><?= h($faq['question']) ?></span>
                    <i data-lucide="chevron-down" class="w-4 h-4 text-warm-400 flex-shrink-0 transition-transform group-open:rotate-180" aria-hidden="true"></i>
                </summary>
                <div class="px-5 pb-4 text-sm text-warm-700 leading-relaxed">
                    <?= h($faq['answer']) ?>
                    <?php if ($i === 0): // Location FAQ — link to municipality page ?>
                    <p class="mt-2 text-xs">
                        <a href="<?= h($municipalityUrl) ?>" class="text-ocean-600 hover:text-ocean-700 underline">
                            <?= $isEs
                                ? 'Ver todas las playas en ' . h($beach['municipality'])
                                : 'See all beaches in ' . h($beach['municipality']) ?>
                        </a>
                    </p>
                    <?php elseif ($i === 1): // Swimming FAQ — link to swimming/calm-waters page
                        $swimTag = in_array('calm-waters', $tags) ? 'calm-waters' : 'swimming';
                        if (isset($tagPageSlugs[$swimTag])):
                            $swimUrl = _faqTagUrl($tagPageSlugs[$swimTag], $lang, $tagPageSlugsEs);
                    ?>
                    <p class="mt-2 text-xs">
                        <a href="<?= h($swimUrl) ?>" class="text-ocean-600 hover:text-ocean-700 underline">
                            <?= $isEs
                                ? 'Explorar playas para nadar en Puerto Rico'
                                : 'Explore swimming beaches in Puerto Rico' ?>
                        </a>
                    </p>
                    <?php endif;
                    elseif ($i === 2): // Facilities FAQ — link to amenity pages
                        $facilityLinks = [];
                        foreach ($amenities as $a) {
                            if (isset($amenityPageSlugs[$a]) && count($facilityLinks) < 2) {
                                $enSlug = $amenityPageSlugs[$a];
                                $url = _faqTagUrl($enSlug, $lang, $amenityPageSlugsEs);
                                $label = ucwords(str_replace('-', ' ', $enSlug));
                                $facilityLinks[] = '<a href="' . h($url) . '" class="text-ocean-600 hover:text-ocean-700 underline">' . h($label) . '</a>';
                            }
                        }
                        if (!empty($facilityLinks)):
                    ?>
                    <p class="mt-2 text-xs">
                        <?= $isEs ? 'Buscar playas con: ' : 'Find beaches with: ' ?>
                        <?= implode(', ', $facilityLinks) ?>
                    </p>
                    <?php endif;
                    elseif ($i === 3): // Activities FAQ — link to relevant tag pages
                        $activityLinks = [];
                        foreach ($tags as $t) {
                            if (isset($tagPageSlugs[$t]) && count($activityLinks) < 2) {
                                $enSlug = $tagPageSlugs[$t];
                                $url = _faqTagUrl($enSlug, $lang, $tagPageSlugsEs);
                                $label = ucwords(str_replace('-', ' ', $enSlug));
                                $activityLinks[] = '<a href="' . h($url) . '" class="text-ocean-600 hover:text-ocean-700 underline">' . h($label) . '</a>';
                            }
                        }
                        if (!empty($activityLinks)):
                    ?>
                    <p class="mt-2 text-xs">
                        <?= $isEs ? 'Más playas para: ' : 'More beaches for: ' ?>
                        <?= implode(', ', $activityLinks) ?>
                    </p>
                    <?php endif;
                    endif; ?>
                </div>
            </details>
            <?php endforeach; ?>
        </div>
    </section>
