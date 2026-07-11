<?php
/**
 * Tours & activities sections for beach and guide pages (Viator via the
 * referral system).
 *
 * Placement tiers: curated link_type='tour_product' campaigns attach to exact
 * beaches (and to guides via guide_tour_placements); auto-matched products
 * from the catalog cache (viator_beach_products) fill remaining product
 * slots; regional link_type='tour' campaigns remain the browse fallback.
 * Clicks route through /go (referral_clicks + GA4 R2_referral_click), which
 * prefers exact API-attributed product URLs and otherwise appends the
 * configured Viator partner pid from VIATOR_PID env.
 */

if (defined('TOURS_INCLUDED')) {
    return;
}
define('TOURS_INCLUDED', true);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/referrals.php';
require_once __DIR__ . '/island_chart.php';
require_once __DIR__ . '/viator.php';

/**
 * Active tour offers for a beach.
 *
 * Exact, editorially-curated product placements come first. One regional
 * destination campaign is then appended as a lower-emphasis browse fallback.
 */
function toursCampaignsForBeach(array $beach, int $limit = 2): array
{
    if ($limit < 1) {
        return [];
    }

    $municipality = (string) ($beach['municipality'] ?? '');
    $muniSlug = slugify($municipality);
    $region = islandRegionForMunicipality($municipality) ?? '';
    $active = [];
    $seen = [];

    $beachId = trim((string) ($beach['id'] ?? ''));
    if ($beachId !== '') {
        $curatedRows = query(
            'SELECT c.*, p.slug AS provider_slug, p.name AS provider_name,
                    p.default_disclosure_en, p.default_disclosure_es,
                    bp.display_order AS placement_order
             FROM beach_referral_placements bp
             INNER JOIN referral_campaigns c ON c.id = bp.campaign_id
             INNER JOIN referral_providers p ON p.id = c.provider_id
             WHERE bp.beach_id = :beach_id
               AND bp.anchor_key = "tours_curated"
               AND bp.enabled = 1
               AND bp.locale = "all"
               AND c.link_type = "tour_product"
               AND c.status = "active"
               AND p.status = "active"
             ORDER BY bp.display_order ASC, c.priority ASC',
            [':beach_id' => $beachId]
        );

        $curatedLimit = $limit > 1 ? $limit - 1 : 1;
        foreach (is_array($curatedRows) ? $curatedRows : [] as $row) {
            if (!referralCampaignIsCurrentlyActive($row)) {
                continue;
            }
            if (viatorCampaignProductIsInactive((string) ($row['id'] ?? ''))) {
                continue;
            }
            $campaignId = (string) ($row['id'] ?? '');
            $active[] = $row;
            $seen[$campaignId] = true;
            if (count($active) >= $curatedLimit) {
                break;
            }
        }
    }

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

    $browseRows = query(
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

    foreach (is_array($browseRows) ? $browseRows : [] as $row) {
        if (count($active) >= $limit) {
            break;
        }
        $campaignId = (string) ($row['id'] ?? '');
        if (isset($seen[$campaignId]) || !referralCampaignIsCurrentlyActive($row)) {
            continue;
        }
        $active[] = $row;
        break;
    }

    return $active;
}

/**
 * Localized details for the product pages curated in migration 047.
 * Prices and review totals are intentionally omitted because they change live.
 */
function toursCuratedOfferMeta(string $slug, string $lang): array
{
    $offers = [
        'viator-product-icacos-catamaran' => [
            'product_code' => '14939P2',
            'image_url' => 'https://media.tacdn.com/media/attractions-splice-spp-674x446/13/e0/e3/15.jpg',
            'en' => ['Icacos catamaran & snorkeling day', 'Sail from Fajardo for snorkeling, beach time, lunch and drinks at Icacos.', 'Fajardo → Icacos', 'About 5 hr 30 min', 'A boat-access beach day'],
            'es' => ['Día de catamarán y snorkel en Icacos', 'Navega desde Fajardo para hacer snorkel y disfrutar la playa, almuerzo y bebidas.', 'Fajardo → Icacos', 'Aprox. 5 h 30 min', 'Un día de playa con acceso en bote'],
        ],
        'viator-product-culebra-snorkel' => [
            'product_code' => '41096P7',
            'image_url' => 'https://media.tacdn.com/media/attractions-splice-spp-674x446/12/db/2d/18.jpg',
            'en' => ['Culebra snorkel & beach day', 'A full boat day from Fajardo with snorkeling stops, lunch, drinks and beach time.', 'Fajardo → Culebra', 'About 6 hours', 'Reaching Culebra without planning the ferry'],
            'es' => ['Día de playa y snorkel en Culebra', 'Un día en bote desde Fajardo con snorkel, almuerzo, bebidas y tiempo de playa.', 'Fajardo → Culebra', 'Aprox. 6 horas', 'Llegar a Culebra sin coordinar el ferry'],
        ],
        'viator-product-vieques-biobay-kayak' => [
            'product_code' => '225672P1',
            'image_url' => 'https://media.tacdn.com/media/attractions-splice-spp-674x446/0b/9a/41/58.jpg',
            'en' => ['Mosquito Bay bioluminescent kayak', 'An after-dark guided paddle in clear and semi-clear-bottom kayaks on Vieques.', 'Vieques stay → Mosquito Bay', 'About 2 hours', 'Travelers staying overnight on Vieques'],
            'es' => ['Kayak bioluminiscente en Mosquito Bay', 'Remada guiada de noche en kayaks transparentes y semitransparentes en Vieques.', 'Estadía en Vieques → Mosquito Bay', 'Aprox. 2 horas', 'Viajeros que pasan la noche en Vieques'],
        ],
        'viator-product-rincon-snorkel' => [
            'product_code' => '10972P1',
            'image_url' => 'https://media.tacdn.com/media/attractions-splice-spp-674x446/15/0e/7c/d5.jpg',
            'en' => ['Tres Palmas guided snorkeling', 'A shore-entry small-group tour through the marine reserve with a certified guide.', 'Rincón → Tres Palmas reef', 'Up to 3 hours', 'Comfortable open-water swimmers'],
            'es' => ['Snorkel guiado en Tres Palmas', 'Tour en grupo pequeño desde la orilla por la reserva marina con guía certificado.', 'Rincón → Arrecife Tres Palmas', 'Hasta 3 horas', 'Nadadores cómodos en mar abierto'],
        ],
        'viator-product-rincon-surf-lesson' => [
            'product_code' => '489026P2',
            'image_url' => 'https://media.tacdn.com/media/attractions-splice-spp-674x446/15/89/c9/c7.jpg',
            'en' => ['Rincón surf lesson', 'Personalized instruction from local surfers, matched to the day’s conditions.', 'Rincón → Local surf break', 'About 1 hr 30 min', 'First-time and improving surfers'],
            'es' => ['Clase de surf en Rincón', 'Instrucción personalizada con surfistas locales según las condiciones del día.', 'Rincón → Spot de surf local', 'Aprox. 1 h 30 min', 'Principiantes y surfistas en progreso'],
        ],
        'viator-product-escambron-snorkel' => [
            'product_code' => '393101P7',
            'image_url' => 'https://media.tacdn.com/media/attractions-splice-spp-674x446/15/55/0e/70.jpg',
            'en' => ['Escambrón snorkeling & turtle spotting', 'A guided small-group snorkeling session that meets right at Escambrón Beach.', 'Meet at Escambrón → Reef', 'About 2 hours', 'Snorkeling without leaving San Juan'],
            'es' => ['Snorkel y tortugas en Escambrón', 'Sesión guiada en grupo pequeño con punto de encuentro en Playa Escambrón.', 'Encuentro en Escambrón → Arrecife', 'Aprox. 2 horas', 'Hacer snorkel sin salir de San Juan'],
        ],
        'viator-product-cueva-del-indio' => [
            'product_code' => '322734P3',
            'image_url' => 'https://media.tacdn.com/media/attractions-splice-spp-674x446/12/e6/f8/e1.jpg',
            'en' => ['Cueva del Indio hike & beach day', 'A guided coastal hike to Taíno carvings and the Seven Arches, with transport from San Juan.', 'San Juan → Cueva del Indio', 'About 6 hours', 'Active travelers on uneven coastal terrain'],
            'es' => ['Caminata y playa en Cueva del Indio', 'Caminata costera guiada a petroglifos taínos y los Siete Arcos, con transporte desde San Juan.', 'San Juan → Cueva del Indio', 'Aprox. 6 horas', 'Viajeros activos en terreno costero irregular'],
        ],
        'viator-product-la-parguera-biobay' => [
            'product_code' => '5535976P5',
            'image_url' => 'https://media.tacdn.com/media/attractions-splice-spp-674x446/16/58/81/0a.jpg',
            'en' => ['La Parguera bioluminescent bay boat tour', 'A compact night boat trip from the local dock to see the bay glow and swim when conditions allow.', 'La Parguera dock → Bio Bay', 'About 1 hr 15 min', 'A short, locally departing night tour'],
            'es' => ['Tour en bote por la bahía bioluminiscente', 'Paseo nocturno desde el muelle local para ver el brillo y nadar cuando las condiciones lo permiten.', 'Muelle de La Parguera → Bahía', 'Aprox. 1 h 15 min', 'Un tour nocturno corto con salida local'],
        ],
    ];

    $offer = $offers[$slug] ?? [];
    if ($offer === []) {
        return [];
    }

    $entry = $offer[$lang === 'es' ? 'es' : 'en'] ?? [];
    $result = [
        'title' => (string) ($entry[0] ?? ''),
        'description' => (string) ($entry[1] ?? ''),
        'route' => (string) ($entry[2] ?? ''),
        'duration' => (string) ($entry[3] ?? ''),
        'best_for' => (string) ($entry[4] ?? ''),
        'product_code' => (string) ($offer['product_code'] ?? ''),
        'image_url' => (string) ($offer['image_url'] ?? ''),
        'api_hydrated' => false,
        'price_from' => null,
        'currency' => '',
        'rating' => null,
        'review_count' => 0,
        'free_cancellation' => null,
    ];

    $hydrated = viatorHydratedProduct((string) ($offer['product_code'] ?? ''), $lang);
    if (is_array($hydrated) && strtoupper((string) ($hydrated['status'] ?? '')) === 'ACTIVE') {
        $result['api_hydrated'] = true;
        $result['title'] = trim((string) ($hydrated['title'] ?? '')) ?: $result['title'];
        $result['image_url'] = trim((string) ($hydrated['image_url'] ?? '')) ?: $result['image_url'];
        $liveDuration = viatorFormatDuration(
            isset($hydrated['duration_minutes_min']) ? (int) $hydrated['duration_minutes_min'] : null,
            isset($hydrated['duration_minutes_max']) ? (int) $hydrated['duration_minutes_max'] : null,
            $lang
        );
        $result['duration'] = $liveDuration !== '' ? $liveDuration : $result['duration'];
        $result['price_from'] = is_numeric($hydrated['price_from'] ?? null) ? (float) $hydrated['price_from'] : null;
        $result['currency'] = (string) ($hydrated['currency'] ?? 'USD');
        $result['rating'] = is_numeric($hydrated['rating'] ?? null) ? (float) $hydrated['rating'] : null;
        $result['review_count'] = max(0, (int) ($hydrated['review_count'] ?? 0));
        $result['free_cancellation'] = isset($hydrated['free_cancellation'])
            ? (int) $hydrated['free_cancellation']
            : null;
    }

    return $result;
}

/**
 * Localized browse-card copy per regional campaign slug.
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

/** Word-boundary truncation for API descriptions used in card copy. */
function toursTruncate(string $text, int $maxChars = 150): string
{
    $text = trim($text);
    if (mb_strlen($text) <= $maxChars) {
        return $text;
    }
    $cut = mb_substr($text, 0, $maxChars);
    $lastSpace = mb_strrpos($cut, ' ');
    if ($lastSpace !== false && $lastSpace > (int) ($maxChars * 0.6)) {
        $cut = mb_substr($cut, 0, $lastSpace);
    }
    return rtrim($cut, " \t.,;:") . '…';
}

/**
 * Card meta for an auto-matched product, hydrated entirely from the
 * viator_products cache row.
 */
function toursAutoProductCardMeta(array $row, string $lang): array
{
    return [
        'title' => trim((string) ($row['title'] ?? '')),
        'description' => toursTruncate((string) ($row['description'] ?? '')),
        'route' => '',
        'duration' => viatorFormatDuration(
            isset($row['duration_minutes_min']) ? (int) $row['duration_minutes_min'] : null,
            isset($row['duration_minutes_max']) ? (int) $row['duration_minutes_max'] : null,
            $lang
        ),
        'best_for' => '',
        'product_code' => trim((string) ($row['product_code'] ?? '')),
        'image_url' => trim((string) ($row['image_url'] ?? '')),
        'api_hydrated' => true,
        'price_from' => is_numeric($row['price_from'] ?? null) ? (float) $row['price_from'] : null,
        'currency' => (string) ($row['currency'] ?? 'USD'),
        'rating' => is_numeric($row['rating'] ?? null) ? (float) $row['rating'] : null,
        'review_count' => max(0, (int) ($row['review_count'] ?? 0)),
        'free_cancellation' => isset($row['free_cancellation']) ? (int) $row['free_cancellation'] : null,
    ];
}

/**
 * Render one tour card (product or browse) in either visual variant.
 *
 * $opts: lang, variant ('classic'|'redesign'), position (1-based),
 *        match_type, context (referral context base), kicker (product cards).
 */
function toursRenderCard(array $campaign, array $meta, array $opts): string
{
    $isEs = ($opts['lang'] ?? 'en') === 'es';
    $variant = (string) ($opts['variant'] ?? 'classic');
    $position = max(1, (int) ($opts['position'] ?? 1));
    $matchType = (string) ($opts['match_type'] ?? 'regional_browse');
    $context = is_array($opts['context'] ?? null) ? $opts['context'] : [];
    $kicker = (string) ($opts['kicker'] ?? '');

    $slug = (string) ($campaign['slug'] ?? '');
    $isProduct = ($meta['kind'] ?? 'product') !== 'browse';
    $title = (string) ($meta['title'] ?? '');
    if ($title === '') {
        $title = (string) ($campaign['name'] ?? 'Tours');
    }
    $desc = (string) ($meta['description'] ?? '');
    $productCode = (string) ($meta['product_code'] ?? '');
    $imageUrl = (string) ($meta['image_url'] ?? '');
    $imageAlt = $isEs
        ? 'Foto oficial de ' . $title . ' en Viator'
        : 'Official photo for ' . $title . ' on Viator';
    $apiHydrated = $isProduct && !empty($meta['api_hydrated']);

    $goUrl = referralCreateGoUrl($slug, array_merge($context, [
        'product_code' => $productCode,
        'card_position' => (string) $position,
        'match_type' => $matchType,
        'api_hydrated' => $apiHydrated ? '1' : '0',
    ]));

    $track = ' data-bf-track="referral-click"'
        . ' data-bf-referral-provider="' . h((string) ($campaign['provider_slug'] ?? '')) . '"'
        . ' data-bf-referral-campaign="' . h($slug) . '"'
        . ' data-bf-referral-placement="' . h((string) ($context['placement'] ?? 'tours_section')) . '"'
        . ' data-bf-referral-page-type="' . h((string) ($context['page_type'] ?? '')) . '"'
        . ' data-bf-referral-page-slug="' . h((string) ($context['page_slug'] ?? '')) . '"'
        . ' data-bf-referral-locale="' . ($isEs ? 'es' : 'en') . '"'
        . ' data-bf-referral-product="' . h($productCode) . '"'
        . ' data-bf-referral-position="' . $position . '"'
        . ' data-bf-referral-match="' . h($matchType) . '"'
        . ' data-bf-referral-hydrated="' . ($apiHydrated ? '1' : '0') . '"'
        . ' data-bf-referral-impression="1"';

    $commerceFacts = '';
    if ($isProduct && is_numeric($meta['price_from'] ?? null)) {
        $currency = strtoupper((string) ($meta['currency'] ?? 'USD'));
        $priceLabel = $currency === 'USD'
            ? '$' . number_format((float) $meta['price_from'], 0)
            : $currency . ' ' . number_format((float) $meta['price_from'], 0);
        $commerceFacts .= '<span>' . h(($isEs ? 'Desde ' : 'From ') . $priceLabel) . '</span>';
    }
    if ($isProduct && is_numeric($meta['rating'] ?? null) && (int) ($meta['review_count'] ?? 0) > 0) {
        $commerceFacts .= '<span>★ ' . h(number_format((float) $meta['rating'], 1)) . ' (' . h(number_format((int) $meta['review_count'])) . ')</span>';
    }

    $route = (string) ($meta['route'] ?? '');
    $duration = (string) ($meta['duration'] ?? '');
    $bestFor = (string) ($meta['best_for'] ?? '');

    if ($variant === 'redesign') {
        if ($isProduct) {
            $facts = '';
            if ($duration !== '') {
                $facts .= '<span>◷ ' . h($duration) . '</span>';
            }
            if ($bestFor !== '') {
                $facts .= '<span>✓ ' . h($bestFor) . '</span>';
            }
            $facts .= $commerceFacts;

            return '<a class="tour-card is-featured" href="' . h($goUrl) . '" target="_blank" rel="nofollow sponsored noopener"' . $track . '>'
                . '<span class="tour-route">'
                . ($imageUrl !== ''
                    ? '<img src="' . h($imageUrl) . '" alt="' . h($imageAlt) . '" width="674" height="446" loading="lazy" decoding="async" referrerpolicy="no-referrer">'
                      . '<span class="tour-photo-source">' . h($isEs ? 'Foto de Viator' : 'Viator photo') . '</span>'
                    : '')
                . ($route !== '' ? '<span class="tour-route-label">' . h($route) . '</span>' : '')
                . '</span>'
                . '<span class="tx">'
                . ($kicker !== '' ? '<span class="tour-kicker">' . h($kicker) . '</span>' : '')
                . '<b>' . h($title) . '</b><small>' . h($desc) . '</small>'
                . ($facts !== '' ? '<span class="tour-facts">' . $facts . '</span>' : '')
                . '</span>'
                . '<span class="go">' . h($isEs ? 'Ver en Viator' : 'View on Viator') . ' ↗</span>'
                . '</a>';
        }

        return '<a class="tour-card is-browse" href="' . h($goUrl) . '" target="_blank" rel="nofollow sponsored noopener"' . $track . '>'
            . '<span class="ic" aria-hidden="true">＋</span>'
            . '<span class="tx"><b>' . h($title) . '</b><small>' . h($desc) . '</small></span>'
            . '<span class="go">' . h($isEs ? 'Explorar tours' : 'Browse tours') . ' ↗</span>'
            . '</a>';
    }

    return '<a href="' . h($goUrl) . '" target="_blank" rel="nofollow sponsored noopener"' . $track
        . ' class="flex items-center gap-4 rounded-xl border border-stone-200 bg-white p-4 hover:border-sunset-400 hover:shadow-card transition-all group">'
        . ($isProduct && $imageUrl !== ''
            ? '<img src="' . h($imageUrl) . '" alt="' . h($imageAlt) . '" width="88" height="64" loading="lazy" decoding="async" referrerpolicy="no-referrer" class="shrink-0 rounded-lg object-cover">'
            : '<span class="text-3xl shrink-0" aria-hidden="true">＋</span>')
        . '<span class="min-w-0 flex-1">'
        . '<span class="block font-semibold text-gray-900 group-hover:text-sunset-600 transition-colors">' . h($title) . '</span>'
        . '<span class="block text-sm text-gray-600 mt-0.5">' . h($desc) . '</span>'
        . ($isProduct && ($route !== '' || $duration !== '')
            ? '<span class="block text-xs font-semibold text-teal-700 mt-2">' . h(trim($route . ($route !== '' && $duration !== '' ? ' · ' : '') . $duration)) . '</span>'
            : '')
        . ($commerceFacts !== '' ? '<span class="flex flex-wrap gap-3 text-xs font-semibold text-blue-700 mt-1">' . $commerceFacts . '</span>' : '')
        . '</span>'
        . '<span class="shrink-0 text-sm font-semibold text-sunset-600">'
        . h($isProduct ? ($isEs ? 'Ver en Viator' : 'View on Viator') : ($isEs ? 'Explorar tours' : 'Browse tours'))
        . ' &rarr;</span>'
        . '</a>';
}

/**
 * Render the tours section. $variant: 'classic' (Tailwind, legacy beach page)
 * or 'redesign' (rd- styles, templates/redesign/beach.php).
 */
function renderToursSection(array $beach, string $lang, string $variant = 'classic'): string
{
    // Up to two curated product placements plus one regional browse fallback.
    $campaigns = toursCampaignsForBeach($beach, 3);
    if ($campaigns === []) {
        return '';
    }

    $curated = array_values(array_filter(
        $campaigns,
        static fn(array $campaign): bool => ($campaign['link_type'] ?? '') === 'tour_product'
    ));
    $browse = array_values(array_filter(
        $campaigns,
        static fn(array $campaign): bool => ($campaign['link_type'] ?? '') === 'tour'
    ));
    $browseCampaign = $browse[0] ?? null;

    $isEs = $lang === 'es';
    $context = [
        'page_type' => 'beach',
        'page_slug' => (string) ($beach['slug'] ?? ''),
        'placement' => 'tours_section',
        'locale' => $isEs ? 'es' : 'en',
    ];

    $curatedMetas = [];
    $curatedCodes = [];
    foreach ($curated as $campaign) {
        $meta = toursCuratedOfferMeta((string) $campaign['slug'], $lang);
        $curatedMetas[] = $meta;
        if (($meta['product_code'] ?? '') !== '') {
            $curatedCodes[] = (string) $meta['product_code'];
        }
    }

    // Auto-matched products fill remaining product slots. They use the
    // regional browse campaign as their click/reporting bucket; /go resolves
    // the exact product URL from the cache at redirect time.
    $autoRows = [];
    $autoLimit = max(0, 2 - count($curated));
    if ($autoLimit > 0 && $browseCampaign !== null) {
        $beachId = trim((string) ($beach['id'] ?? ''));
        if ($beachId !== '') {
            $autoRows = viatorAutoMatchedProductsForBeach($beachId, $lang, $autoLimit, $curatedCodes);
        }
    }

    $hasProduct = $curated !== [] || $autoRows !== [];
    $beachName = trim((string) ($beach['name'] ?? ''));
    $heading = $hasProduct
        ? ($isEs ? 'Cómo disfrutar ' . $beachName : 'Ways to experience ' . $beachName)
        : ($isEs ? 'Experiencias cerca' : 'Experiences nearby');
    $sub = $hasProduct
        ? ($isEs
            ? 'Recomendaciones seleccionadas por su acceso, ubicación y actividades.'
            : 'Handpicked recommendations based on access, location and activities.')
        : ($isEs
            ? 'Explora actividades reservables en esta zona.'
            : 'Explore bookable activities around this part of the island.');
    $disclosure = referralDisclosureText($isEs ? 'es' : 'en', $campaigns[0]);

    $cards = '';
    $position = 0;

    foreach ($curated as $index => $campaign) {
        $position++;
        $meta = $curatedMetas[$index] + ['kind' => 'product'];
        $cards .= toursRenderCard($campaign, $meta, [
            'lang' => $lang,
            'variant' => $variant,
            'position' => $position,
            'match_type' => 'curated_beach',
            'context' => $context,
            'kicker' => $isEs ? 'Elegido para esta playa' : 'Curated for this beach',
        ]);
    }

    foreach ($autoRows as $row) {
        $position++;
        $meta = toursAutoProductCardMeta($row, $lang) + ['kind' => 'product'];
        $cards .= toursRenderCard($browseCampaign, $meta, [
            'lang' => $lang,
            'variant' => $variant,
            'position' => $position,
            'match_type' => 'auto_product',
            'context' => $context,
            'kicker' => $isEs ? 'Popular cerca de esta playa' : 'Popular near this beach',
        ]);
    }

    if ($browseCampaign !== null) {
        $position++;
        [$browseTitle, $browseDesc] = toursCardCopy((string) $browseCampaign['slug'], $lang);
        $cards .= toursRenderCard($browseCampaign, [
            'kind' => 'browse',
            'title' => $browseTitle,
            'description' => $browseDesc,
        ], [
            'lang' => $lang,
            'variant' => $variant,
            'position' => $position,
            'match_type' => 'regional_browse',
            'context' => $context,
        ]);
    }

    if ($variant === 'redesign') {
        return '<section id="tours" class="block">'
            . '<div class="tour-head"><div><h2 class="h2">' . h($heading) . '</h2><p>' . h($sub) . '</p></div>'
            . '<span class="tour-provider">' . h($isEs ? 'Socio Viator' : 'Viator partner') . '</span></div>'
            . '<div class="tour-cards">' . $cards . '</div>'
            . '<p class="tour-disclosure">' . h($disclosure) . '</p>'
            . '</section>';
    }

    return '<section id="section-tours" class="scroll-mt-[120px]">'
        . '<h2 class="text-xl font-bold text-gray-900 mb-1">' . h($heading) . '</h2>'
        . '<p class="text-sm text-gray-600 mb-4">' . h($sub) . '</p>'
        . '<div class="space-y-3">' . $cards . '</div>'
        . '<p class="text-xs text-gray-500 italic mt-3">' . h($disclosure) . '</p>'
        . '</section>';
}

/**
 * Active tour placements for a guide page, curated in guide_tour_placements.
 * Product campaigns come first; browse campaigns keep their seeded order.
 */
function toursPlacementsForGuide(string $guideSlug): array
{
    if (!viatorTableExists('guide_tour_placements')) {
        return [];
    }

    $rows = query(
        'SELECT c.*, p.slug AS provider_slug, p.name AS provider_name,
                p.default_disclosure_en, p.default_disclosure_es,
                gp.display_order AS placement_order
         FROM guide_tour_placements gp
         INNER JOIN referral_campaigns c ON c.id = gp.campaign_id
         INNER JOIN referral_providers p ON p.id = c.provider_id
         WHERE gp.guide_slug = :guide_slug
           AND gp.enabled = 1
           AND c.status = "active"
           AND p.status = "active"
         ORDER BY gp.display_order ASC, c.priority ASC',
        [':guide_slug' => $guideSlug]
    );

    $active = [];
    foreach (is_array($rows) ? $rows : [] as $row) {
        if (!referralCampaignIsCurrentlyActive($row)) {
            continue;
        }
        if (($row['link_type'] ?? '') === 'tour_product'
            && viatorCampaignProductIsInactive((string) ($row['id'] ?? ''))) {
            continue;
        }
        $active[] = $row;
    }
    return $active;
}

/**
 * Tours section for guide pages (Tailwind layout, matches the guide theme).
 * Renders nothing when the guide has no active placements.
 */
function renderGuideToursSection(string $guideSlug, string $lang): string
{
    $placements = toursPlacementsForGuide($guideSlug);
    if ($placements === []) {
        return '';
    }

    $isEs = $lang === 'es';
    $context = [
        'page_type' => 'guide',
        'page_slug' => $guideSlug,
        'placement' => 'guide_tours',
        'locale' => $isEs ? 'es' : 'en',
    ];

    $cards = '';
    $position = 0;
    foreach ($placements as $campaign) {
        $position++;
        $isProduct = ($campaign['link_type'] ?? '') === 'tour_product';
        if ($isProduct) {
            $meta = toursCuratedOfferMeta((string) $campaign['slug'], $lang) + ['kind' => 'product'];
            $matchType = 'curated_guide';
        } else {
            [$title, $desc] = toursCardCopy((string) $campaign['slug'], $lang);
            $meta = ['kind' => 'browse', 'title' => $title, 'description' => $desc];
            $matchType = 'regional_browse';
        }
        $cards .= toursRenderCard($campaign, $meta, [
            'lang' => $lang,
            'variant' => 'classic',
            'position' => $position,
            'match_type' => $matchType,
            'context' => $context,
        ]);
    }

    $heading = $isEs ? 'Reserva estas experiencias' : 'Book these experiences';
    $sub = $isEs
        ? 'Tours seleccionados que combinan con esta guía. Reservas en Viator.'
        : 'Handpicked tours that pair with this guide. Booking happens on Viator.';
    $disclosure = referralDisclosureText($isEs ? 'es' : 'en', $placements[0]);

    return '<section id="guide-tours" class="mt-12 pt-8 border-t border-gray-200">'
        . '<h2 class="text-xl font-bold text-gray-900 mb-1">' . h($heading) . '</h2>'
        . '<p class="text-sm text-gray-600 mb-4">' . h($sub) . '</p>'
        . '<div class="space-y-3">' . $cards . '</div>'
        . '<p class="text-xs text-gray-500 italic mt-3">' . h($disclosure) . '</p>'
        . '</section>';
}
