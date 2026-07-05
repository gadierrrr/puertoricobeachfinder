<?php
/**
 * Featured local listings — paid business placements on beach pages.
 *
 * Listings are sold manually (leads via /advertise), managed in /admin/listings,
 * assigned to specific beaches, and click-tracked through /local-out (which
 * validates + logs, then redirects). Cards are always labeled as sponsored and
 * outbound links carry rel="sponsored nofollow" for FTC/Google compliance.
 */

if (defined('LISTINGS_INCLUDED')) {
    return;
}
define('LISTINGS_INCLUDED', true);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

const LOCAL_LISTING_CATEGORIES = [
    'food'     => ['en' => 'Food & Drinks', 'es' => 'Comida y Bebidas', 'icon' => '🍽️'],
    'tours'    => ['en' => 'Tours & Rentals', 'es' => 'Tours y Alquileres', 'icon' => '🚤'],
    'surf'     => ['en' => 'Surf & Lessons', 'es' => 'Surf y Clases', 'icon' => '🏄'],
    'shop'     => ['en' => 'Shops', 'es' => 'Tiendas', 'icon' => '🛍️'],
    'lodging'  => ['en' => 'Stays', 'es' => 'Hospedaje', 'icon' => '🏨'],
    'services' => ['en' => 'Services', 'es' => 'Servicios', 'icon' => '🔧'],
];

function listingCategoryLabel(string $category, string $lang): string
{
    $entry = LOCAL_LISTING_CATEGORIES[$category] ?? null;
    if (!$entry) {
        return ucfirst($category);
    }
    return $entry[$lang === 'es' ? 'es' : 'en'];
}

function listingCategoryIcon(string $category): string
{
    return LOCAL_LISTING_CATEGORIES[$category]['icon'] ?? '📍';
}

/**
 * Whether a listing is live: active status + inside its paid date window.
 */
function listingIsCurrentlyActive(array $listing): bool
{
    if (($listing['status'] ?? '') !== 'active') {
        return false;
    }
    $now = date('Y-m-d H:i:s');
    $from = trim((string) ($listing['active_from'] ?? ''));
    $to = trim((string) ($listing['active_to'] ?? ''));
    if ($from !== '' && $now < $from) {
        return false;
    }
    if ($to !== '' && $now > $to) {
        return false;
    }
    return true;
}

function listingsForBeach(string $beachId, int $limit = 3): array
{
    $rows = query(
        'SELECT l.*, lb.display_order
         FROM local_listing_beaches lb
         INNER JOIN local_listings l ON l.id = lb.listing_id
         WHERE lb.beach_id = :beach_id AND l.status = "active"
         ORDER BY (l.tier = "featured") DESC, lb.display_order ASC, l.created_at ASC',
        [':beach_id' => $beachId]
    );

    if (!is_array($rows)) {
        return [];
    }

    $active = [];
    foreach ($rows as $row) {
        if (listingIsCurrentlyActive($row)) {
            $active[] = $row;
        }
        if (count($active) >= $limit) {
            break;
        }
    }

    return $active;
}

/**
 * Click-tracked outbound URL. Actions: website | instagram | call | whatsapp.
 */
function listingClickUrl(string $listingId, string $beachId, string $action): string
{
    return '/local-out?' . http_build_query(['l' => $listingId, 'b' => $beachId, 'a' => $action]);
}

/**
 * Render the "local favorites" section. $variant: 'classic' or 'redesign'.
 * Always renders the self-serve "feature your business" teaser — on beaches
 * with no listings yet, the teaser alone is the sales funnel.
 */
function renderLocalListingsSection(array $beach, string $lang, string $variant = 'classic'): string
{
    $beachId = (string) ($beach['id'] ?? '');
    $beachSlug = (string) ($beach['slug'] ?? '');
    if ($beachId === '') {
        return '';
    }

    $isEs = $lang === 'es';
    $listings = listingsForBeach($beachId, 3);

    $heading = $isEs ? 'Favoritos locales' : 'Local favorites';
    $sponsored = $isEs ? 'Patrocinado' : 'Sponsored';
    $teaserText = $isEs
        ? '¿Tienes un negocio cerca de esta playa? Destácalo aquí.'
        : 'Run a business near this beach? Feature it here.';
    $teaserCta = $isEs ? 'Anúnciate' : 'Advertise';
    $advertiseUrl = '/advertise?beach=' . urlencode($beachSlug);

    // No listings: compact teaser only (never an empty "section").
    if ($listings === []) {
        if ($variant === 'redesign') {
            return '<div class="advert-teaser"><span>' . h($teaserText) . '</span>'
                . '<a href="' . h($advertiseUrl) . '">' . h($teaserCta) . ' →</a></div>';
        }
        return '<div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-dashed border-stone-300 bg-stone-50 px-4 py-3">'
            . '<span class="text-sm text-gray-600">' . h($teaserText) . '</span>'
            . '<a href="' . h($advertiseUrl) . '" class="text-sm font-semibold text-sunset-600 hover:text-sunset-700">' . h($teaserCta) . ' &rarr;</a>'
            . '</div>';
    }

    $cards = '';
    foreach ($listings as $listing) {
        $id = (string) $listing['id'];
        $name = (string) $listing['business_name'];
        $tagline = $isEs && !empty($listing['tagline_es']) ? (string) $listing['tagline_es'] : (string) ($listing['tagline'] ?? '');
        $category = (string) ($listing['category'] ?? 'food');
        $catLabel = listingCategoryLabel($category, $lang);
        $icon = listingCategoryIcon($category);
        $img = trim((string) ($listing['image_url'] ?? ''));

        $trackAttrs = ' data-bf-track="listing-impression" data-bf-listing-id="' . h($id) . '" data-bf-listing-beach="' . h($beachSlug) . '"';

        $actions = [];
        if (!empty($listing['website_url'])) {
            $actions[] = ['website', $isEs ? 'Sitio web' : 'Website'];
        }
        if (!empty($listing['whatsapp'])) {
            $actions[] = ['whatsapp', 'WhatsApp'];
        }
        if (!empty($listing['phone'])) {
            $actions[] = ['call', $isEs ? 'Llamar' : 'Call'];
        }
        if (!empty($listing['instagram'])) {
            $actions[] = ['instagram', 'Instagram'];
        }
        $actions = array_slice($actions, 0, 2);

        $actionHtml = '';
        foreach ($actions as [$action, $label]) {
            $url = listingClickUrl($id, $beachId, $action);
            if ($variant === 'redesign') {
                $actionHtml .= '<a class="act" href="' . h($url) . '" target="_blank" rel="nofollow sponsored noopener"'
                    . ' data-bf-track="listing-click" data-bf-listing-id="' . h($id) . '" data-bf-listing-action="' . h($action) . '" data-bf-listing-beach="' . h($beachSlug) . '">'
                    . h($label) . '</a>';
            } else {
                $actionHtml .= '<a href="' . h($url) . '" target="_blank" rel="nofollow sponsored noopener"'
                    . ' data-bf-track="listing-click" data-bf-listing-id="' . h($id) . '" data-bf-listing-action="' . h($action) . '" data-bf-listing-beach="' . h($beachSlug) . '"'
                    . ' class="inline-flex items-center gap-1 rounded-lg border border-stone-200 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:border-sunset-400 hover:text-sunset-600 transition-colors">'
                    . h($label) . '</a>';
            }
        }

        if ($variant === 'redesign') {
            $cards .= '<div class="local-card"' . $trackAttrs . '>'
                . ($img !== '' ? '<div class="ph" style="background-image:url(\'' . h($img) . '\')"></div>' : '<div class="ph ic-ph">' . $icon . '</div>')
                . '<div class="bd">'
                . '<div class="cat">' . $icon . ' ' . h($catLabel) . ' · <i>' . h($sponsored) . '</i></div>'
                . '<div class="nm">' . h($name) . '</div>'
                . ($tagline !== '' ? '<div class="tg">' . h($tagline) . '</div>' : '')
                . '<div class="acts">' . $actionHtml . '</div>'
                . '</div></div>';
        } else {
            $cards .= '<div class="flex gap-4 rounded-xl border border-stone-200 bg-white p-4"' . $trackAttrs . '>'
                . ($img !== ''
                    ? '<div class="h-16 w-16 shrink-0 rounded-lg bg-cover bg-center" style="background-image:url(\'' . h($img) . '\')"></div>'
                    : '<div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg bg-stone-100 text-2xl">' . $icon . '</div>')
                . '<div class="min-w-0 flex-1">'
                . '<div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">' . h($catLabel) . ' · <span class="italic normal-case">' . h($sponsored) . '</span></div>'
                . '<div class="font-semibold text-gray-900 mt-0.5">' . h($name) . '</div>'
                . ($tagline !== '' ? '<div class="text-sm text-gray-600 mt-0.5">' . h($tagline) . '</div>' : '')
                . '<div class="flex gap-2 mt-2">' . $actionHtml . '</div>'
                . '</div></div>';
        }
    }

    if ($variant === 'redesign') {
        return '<section id="local" class="block">'
            . '<h2 class="h2">' . h($heading) . '</h2>'
            . '<div class="local-cards">' . $cards . '</div>'
            . '<div class="advert-teaser"><span>' . h($teaserText) . '</span>'
            . '<a href="' . h($advertiseUrl) . '">' . h($teaserCta) . ' →</a></div>'
            . '</section>';
    }

    return '<section id="section-local" class="scroll-mt-[120px]">'
        . '<h2 class="text-xl font-bold text-gray-900 mb-4">' . h($heading) . '</h2>'
        . '<div class="space-y-3">' . $cards . '</div>'
        . '<div class="mt-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-dashed border-stone-300 bg-stone-50 px-4 py-3">'
        . '<span class="text-sm text-gray-600">' . h($teaserText) . '</span>'
        . '<a href="' . h($advertiseUrl) . '" class="text-sm font-semibold text-sunset-600 hover:text-sunset-700">' . h($teaserCta) . ' &rarr;</a>'
        . '</div>'
        . '</section>';
}
