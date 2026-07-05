<?php
/**
 * Tours & activities section for beach pages (Viator via the referral system).
 *
 * Campaigns with link_type='tour' are auto-matched to beaches by region —
 * destination_scope holds an islandRegionForMunicipality() region or 'global'
 * — so no per-beach placement rows are needed. Clicks route through /go
 * (referral_clicks + GA4 R2_referral_click), and the Viator pid is appended
 * at redirect time from VIATOR_PID env (see referralProviderTrackingParams).
 */

if (defined('TOURS_INCLUDED')) {
    return;
}
define('TOURS_INCLUDED', true);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/referrals.php';
require_once __DIR__ . '/island_chart.php';

/**
 * Active tour campaigns for a beach, most specific scope first:
 * municipality slug (e.g. 'vieques') > island region > 'global' PR-wide.
 */
function toursCampaignsForBeach(array $beach, int $limit = 2): array
{
    $municipality = (string) ($beach['municipality'] ?? '');
    $muniSlug = slugify($municipality);
    $region = islandRegionForMunicipality($municipality) ?? '';

    $scopes = array_values(array_filter(array_unique([$muniSlug, $region, 'global'])));

    $placeholders = [];
    $params = [];
    $orderCases = [];
    foreach ($scopes as $i => $scope) {
        $key = ':scope' . $i;
        $placeholders[] = $key;
        $params[$key] = $scope;
        $orderCases[] = 'WHEN ' . $key . ' THEN ' . $i;
    }

    $rows = query(
        'SELECT c.*, p.slug AS provider_slug, p.name AS provider_name,
                p.default_disclosure_en, p.default_disclosure_es
         FROM referral_campaigns c
         INNER JOIN referral_providers p ON p.id = c.provider_id
         WHERE c.link_type = "tour"
           AND c.status = "active"
           AND p.status = "active"
           AND c.destination_scope IN (' . implode(', ', $placeholders) . ')
         ORDER BY CASE c.destination_scope ' . implode(' ', $orderCases) . ' ELSE 99 END ASC,
                  c.priority ASC',
        $params
    );

    if (!is_array($rows)) {
        return [];
    }

    $active = [];
    foreach ($rows as $row) {
        if (referralCampaignIsCurrentlyActive($row)) {
            $active[] = $row;
        }
        if (count($active) >= $limit) {
            break;
        }
    }

    return $active;
}

/**
 * Localized card copy per campaign slug. Falls back to the campaign name.
 */
function toursCardCopy(string $slug, string $lang): array
{
    $copy = [
        'viator-tours-san-juan' => [
            'en' => ['San Juan tours & experiences', 'Old San Juan walks, catamarans, El Yunque day trips and more.'],
            'es' => ['Tours y experiencias en San Juan', 'Viejo San Juan, catamaranes, El Yunque y más.'],
        ],
        'viator-tours-fajardo' => [
            'en' => ['Bio bay, Culebra & snorkeling trips', 'Kayak the bioluminescent bay or sail to Culebra from Fajardo.'],
            'es' => ['Bahía bioluminiscente, Culebra y snorkel', 'Kayak en la bahía bioluminiscente o catamarán a Culebra desde Fajardo.'],
        ],
        'viator-tours-culebra' => [
            'en' => ['Culebra island tours & day trips', 'Flamenco Beach day trips, snorkeling and water taxis.'],
            'es' => ['Tours y excursiones a Culebra', 'Excursiones a Playa Flamenco, snorkel y transporte.'],
        ],
        'viator-tours-pr' => [
            'en' => ['Top-rated Puerto Rico tours', 'Snorkeling, bio bays, rainforest hikes and boat days across the island.'],
            'es' => ['Tours mejor valorados en Puerto Rico', 'Snorkel, bahías bioluminiscentes, El Yunque y días de bote en toda la isla.'],
        ],
        'viator-tours-rincon' => [
            'en' => ['Rincón & west coast adventures', 'Surf lessons, Tres Palmas snorkeling, horseback rides and sunset sails.'],
            'es' => ['Aventuras en Rincón y el oeste', 'Clases de surf, snorkel en Tres Palmas, cabalgatas y veleros al atardecer.'],
        ],
        'viator-tours-arecibo' => [
            'en' => ['North coast caves & adventures', 'Cueva del Indio, cave tubing, waterfalls and eco tours near Arecibo.'],
            'es' => ['Cuevas y aventuras del norte', 'Cueva del Indio, ríos subterráneos, cascadas y ecoturismo cerca de Arecibo.'],
        ],
        'viator-tours-ponce' => [
            'en' => ['Ponce & south coast tours', 'City walks, La Parguera bio bay and island day trips from the south.'],
            'es' => ['Tours en Ponce y el sur', 'Recorridos por Ponce, bahía bioluminiscente de La Parguera y excursiones.'],
        ],
        'viator-tours-vieques' => [
            'en' => ['Vieques bio bay & island tours', 'Mosquito Bay kayaks, beach hopping and snorkeling on Vieques.'],
            'es' => ['Bahía bioluminiscente y tours en Vieques', 'Kayak en Mosquito Bay, playas escondidas y snorkel en Vieques.'],
        ],
    ];

    $entry = $copy[$slug][$lang === 'es' ? 'es' : 'en'] ?? null;
    return $entry ?? ['', ''];
}

/**
 * Render the tours section. $variant: 'classic' (Tailwind, legacy beach page)
 * or 'redesign' (rd- styles, templates/redesign/beach.php).
 */
function renderToursSection(array $beach, string $lang, string $variant = 'classic'): string
{
    $campaigns = toursCampaignsForBeach($beach, 2);
    if ($campaigns === []) {
        return '';
    }

    $isEs = $lang === 'es';
    $context = [
        'page_type' => 'beach',
        'page_slug' => (string) ($beach['slug'] ?? ''),
        'placement' => 'tours_section',
        'locale' => $isEs ? 'es' : 'en',
    ];

    $heading = $isEs ? 'Tours y experiencias cerca' : 'Tours & experiences nearby';
    $sub = $isEs
        ? 'Reserva actividades con cancelación gratis en la mayoría de los tours.'
        : 'Book activities with free cancellation on most tours.';
    $ctaLabel = $isEs ? 'Ver tours' : 'See tours';
    $disclosure = referralDisclosureText($isEs ? 'es' : 'en', $campaigns[0]);

    $cards = '';
    foreach ($campaigns as $campaign) {
        [$title, $desc] = toursCardCopy((string) $campaign['slug'], $lang);
        if ($title === '') {
            $title = (string) ($campaign['name'] ?? 'Tours');
        }
        $goUrl = referralCreateGoUrl((string) $campaign['slug'], $context);
        $track = ' data-bf-track="referral-click"'
            . ' data-bf-referral-provider="' . h((string) ($campaign['provider_slug'] ?? '')) . '"'
            . ' data-bf-referral-campaign="' . h((string) $campaign['slug']) . '"'
            . ' data-bf-referral-placement="tours_section"'
            . ' data-bf-referral-page-type="beach"'
            . ' data-bf-referral-page-slug="' . h((string) ($beach['slug'] ?? '')) . '"'
            . ' data-bf-referral-locale="' . ($isEs ? 'es' : 'en') . '"';

        if ($variant === 'redesign') {
            $cards .= '<a class="tour-card" href="' . h($goUrl) . '" target="_blank" rel="nofollow sponsored noopener"' . $track . '>'
                . '<span class="ic">🎟️</span>'
                . '<span class="tx"><b>' . h($title) . '</b><small>' . h($desc) . '</small></span>'
                . '<span class="go">' . h($ctaLabel) . ' →</span>'
                . '</a>';
        } else {
            $cards .= '<a href="' . h($goUrl) . '" target="_blank" rel="nofollow sponsored noopener"' . $track
                . ' class="flex items-center gap-4 rounded-xl border border-stone-200 bg-white p-4 hover:border-sunset-400 hover:shadow-card transition-all group">'
                . '<span class="text-3xl shrink-0" aria-hidden="true">🎟️</span>'
                . '<span class="min-w-0 flex-1">'
                . '<span class="block font-semibold text-gray-900 group-hover:text-sunset-600 transition-colors">' . h($title) . '</span>'
                . '<span class="block text-sm text-gray-600 mt-0.5">' . h($desc) . '</span>'
                . '</span>'
                . '<span class="shrink-0 text-sm font-semibold text-sunset-600">' . h($ctaLabel) . ' &rarr;</span>'
                . '</a>';
        }
    }

    if ($variant === 'redesign') {
        return '<section id="tours" class="block">'
            . '<h2 class="h2">' . h($heading) . '</h2>'
            . '<p style="font-size:.9rem;color:var(--ink-60);margin:-6px 0 14px">' . h($sub) . '</p>'
            . '<div class="tour-cards">' . $cards . '</div>'
            . '<p style="font-size:.72rem;color:var(--ink-60);font-style:italic;margin-top:10px">' . h($disclosure) . '</p>'
            . '</section>';
    }

    return '<section id="section-tours" class="scroll-mt-[120px]">'
        . '<h2 class="text-xl font-bold text-gray-900 mb-1">' . h($heading) . '</h2>'
        . '<p class="text-sm text-gray-600 mb-4">' . h($sub) . '</p>'
        . '<div class="space-y-3">' . $cards . '</div>'
        . '<p class="text-xs text-gray-500 italic mt-3">' . h($disclosure) . '</p>'
        . '</section>';
}
