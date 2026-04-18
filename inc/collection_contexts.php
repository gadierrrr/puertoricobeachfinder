<?php
/**
 * Collection context registry for Collection Guide V2 pages.
 *
 * NOTE: keep keys aligned with page slugs and API `collection` param.
 */

if (defined('COLLECTION_CONTEXTS_INCLUDED')) {
    return;
}
define('COLLECTION_CONTEXTS_INCLUDED', true);

/**
 * @return array<string,array<string,mixed>>
 */
function collectionContextRegistry(): array {
    // Ensure i18n is available (loaded lazily by callers)
    if (!function_exists('__')) {
        require_once __DIR__ . '/i18n.php';
    }
    return [
        'best-beaches' => [
            'key' => 'best-beaches',
            'slug' => 'best-beaches',
            'page_key' => 'best_beaches',
            'mode' => 'curated',
            'default_sort' => 'curated',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_beaches.hero_title'),
                'subtitle' => __('pages.best_beaches.hero_subtitle'),
                'meta' => __('pages.best_beaches.hero_meta'),
            ],
        ],
        'best-beaches-san-juan' => [
            'key' => 'best-beaches-san-juan',
            'slug' => 'best-beaches-san-juan',
            'page_key' => 'best_beaches_san_juan',
            'mode' => 'best',
            'municipalities' => ['San Juan', 'Carolina'],
            'default_sort' => 'rating',
            'default_limit' => 12,
            'hero' => [
                'title' => __('pages.best_beaches_san_juan.hero_title'),
                'subtitle' => __('pages.best_beaches_san_juan.hero_subtitle'),
                'meta' => __('pages.best_beaches_san_juan.hero_meta'),
            ],
        ],
        'best-family-beaches' => [
            'key' => 'best-family-beaches',
            'slug' => 'best-family-beaches',
            'page_key' => 'best_family_beaches',
            'mode' => 'tag',
            'context_tag' => 'family-friendly',
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_family_beaches.hero_title'),
                'subtitle' => __('pages.best_family_beaches.hero_subtitle'),
                'meta' => __('pages.best_family_beaches.hero_meta'),
            ],
        ],
        'best-snorkeling-beaches' => [
            'key' => 'best-snorkeling-beaches',
            'slug' => 'best-snorkeling-beaches',
            'page_key' => 'best_snorkeling_beaches',
            'mode' => 'tag',
            'context_tag' => 'snorkeling',
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_snorkeling_beaches.hero_title'),
                'subtitle' => __('pages.best_snorkeling_beaches.hero_subtitle'),
                'meta' => __('pages.best_snorkeling_beaches.hero_meta'),
            ],
        ],
        'best-surfing-beaches' => [
            'key' => 'best-surfing-beaches',
            'slug' => 'best-surfing-beaches',
            'page_key' => 'best_surfing_beaches',
            'mode' => 'tag',
            'context_tag' => 'surfing',
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_surfing_beaches.hero_title'),
                'subtitle' => __('pages.best_surfing_beaches.hero_subtitle'),
                'meta' => __('pages.best_surfing_beaches.hero_meta'),
            ],
        ],
        'beaches-near-san-juan' => [
            'key' => 'beaches-near-san-juan',
            'slug' => 'beaches-near-san-juan',
            'page_key' => 'beaches_near_san_juan',
            'mode' => 'radius',
            'center_lat' => 18.4655,
            'center_lng' => -66.1057,
            'radius_km' => 30,
            'default_sort' => 'distance',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.beaches_near_san_juan.hero_title'),
                'subtitle' => __('pages.beaches_near_san_juan.hero_subtitle'),
                'meta' => __('pages.beaches_near_san_juan.hero_meta'),
            ],
        ],
        'beaches-near-san-juan-airport' => [
            'key' => 'beaches-near-san-juan-airport',
            'slug' => 'beaches-near-san-juan-airport',
            'page_key' => 'beaches_near_airport',
            'mode' => 'radius',
            'center_lat' => 18.4394,
            'center_lng' => -66.0018,
            'radius_km' => 15,
            'default_sort' => 'distance',
            'default_limit' => 10,
            'hero' => [
                'title' => __('pages.beaches_near_airport.hero_title'),
                'subtitle' => __('pages.beaches_near_airport.hero_subtitle'),
                'meta' => __('pages.beaches_near_airport.hero_meta'),
            ],
        ],
        'hidden-beaches-puerto-rico' => [
            'key' => 'hidden-beaches-puerto-rico',
            'slug' => 'hidden-beaches-puerto-rico',
            'page_key' => 'hidden_beaches',
            'mode' => 'hidden',
            'hidden_tags' => ['secluded', 'remote', 'wild'],
            'max_review_count' => 200,
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.hidden_beaches.hero_title'),
                'subtitle' => __('pages.hidden_beaches.hero_subtitle'),
                'meta' => __('pages.hidden_beaches.hero_meta'),
            ],
        ],
        'best-diving-beaches' => [
            'key' => 'best-diving-beaches',
            'slug' => 'best-diving-beaches',
            'page_key' => 'best_diving_beaches',
            'mode' => 'tag',
            'context_tag' => 'diving',
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_diving_beaches.hero_title'),
                'subtitle' => __('pages.best_diving_beaches.hero_subtitle'),
                'meta' => __('pages.best_diving_beaches.hero_meta'),
            ],
        ],
        'best-fishing-beaches' => [
            'key' => 'best-fishing-beaches',
            'slug' => 'best-fishing-beaches',
            'page_key' => 'best_fishing_beaches',
            'mode' => 'tag',
            'context_tag' => 'fishing',
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_fishing_beaches.hero_title'),
                'subtitle' => __('pages.best_fishing_beaches.hero_subtitle'),
                'meta' => __('pages.best_fishing_beaches.hero_meta'),
            ],
        ],
        'best-accessible-beaches' => [
            'key' => 'best-accessible-beaches',
            'slug' => 'best-accessible-beaches',
            'page_key' => 'best_accessible_beaches',
            'mode' => 'tag',
            'context_tag' => 'accessible',
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_accessible_beaches.hero_title'),
                'subtitle' => __('pages.best_accessible_beaches.hero_subtitle'),
                'meta' => __('pages.best_accessible_beaches.hero_meta'),
            ],
        ],
        'best-scenic-beaches' => [
            'key' => 'best-scenic-beaches',
            'slug' => 'best-scenic-beaches',
            'page_key' => 'best_scenic_beaches',
            'mode' => 'tag',
            'context_tag' => 'scenic',
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_scenic_beaches.hero_title'),
                'subtitle' => __('pages.best_scenic_beaches.hero_subtitle'),
                'meta' => __('pages.best_scenic_beaches.hero_meta'),
            ],
        ],
        'best-swimming-beaches' => [
            'key' => 'best-swimming-beaches',
            'slug' => 'best-swimming-beaches',
            'page_key' => 'best_swimming_beaches',
            'mode' => 'tag',
            'context_tag' => 'swimming',
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_swimming_beaches.hero_title'),
                'subtitle' => __('pages.best_swimming_beaches.hero_subtitle'),
                'meta' => __('pages.best_swimming_beaches.hero_meta'),
            ],
        ],
        'best-camping-beaches' => [
            'key' => 'best-camping-beaches',
            'slug' => 'best-camping-beaches',
            'page_key' => 'best_camping_beaches',
            'mode' => 'tag',
            'context_tag' => 'camping',
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_camping_beaches.hero_title'),
                'subtitle' => __('pages.best_camping_beaches.hero_subtitle'),
                'meta' => __('pages.best_camping_beaches.hero_meta'),
            ],
        ],
        'best-calm-water-beaches' => [
            'key' => 'best-calm-water-beaches',
            'slug' => 'best-calm-water-beaches',
            'page_key' => 'best_calm_water_beaches',
            'mode' => 'tag',
            'context_tag' => 'calm-waters',
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_calm_water_beaches.hero_title'),
                'subtitle' => __('pages.best_calm_water_beaches.hero_subtitle'),
                'meta' => __('pages.best_calm_water_beaches.hero_meta'),
            ],
        ],
        'best-secluded-beaches' => [
            'key' => 'best-secluded-beaches',
            'slug' => 'best-secluded-beaches',
            'page_key' => 'best_secluded_beaches',
            'mode' => 'tag',
            'context_tag' => 'secluded',
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_secluded_beaches.hero_title'),
                'subtitle' => __('pages.best_secluded_beaches.hero_subtitle'),
                'meta' => __('pages.best_secluded_beaches.hero_meta'),
            ],
        ],
        'best-beaches-cabo-rojo' => [
            'key' => 'best-beaches-cabo-rojo',
            'slug' => 'best-beaches-cabo-rojo',
            'page_key' => 'best_beaches_cabo_rojo',
            'mode' => 'best',
            'municipalities' => ['Cabo Rojo'],
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_beaches_cabo_rojo.hero_title'),
                'subtitle' => __('pages.best_beaches_cabo_rojo.hero_subtitle'),
                'meta' => __('pages.best_beaches_cabo_rojo.hero_meta'),
            ],
        ],
        'best-beaches-rincon' => [
            'key' => 'best-beaches-rincon',
            'slug' => 'best-beaches-rincon',
            'page_key' => 'best_beaches_rincon',
            'mode' => 'best',
            'municipalities' => ['Rincon'],
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_beaches_rincon.hero_title'),
                'subtitle' => __('pages.best_beaches_rincon.hero_subtitle'),
                'meta' => __('pages.best_beaches_rincon.hero_meta'),
            ],
        ],
        'best-beaches-isabela' => [
            'key' => 'best-beaches-isabela',
            'slug' => 'best-beaches-isabela',
            'page_key' => 'best_beaches_isabela',
            'mode' => 'best',
            'municipalities' => ['Isabela'],
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_beaches_isabela.hero_title'),
                'subtitle' => __('pages.best_beaches_isabela.hero_subtitle'),
                'meta' => __('pages.best_beaches_isabela.hero_meta'),
            ],
        ],
        'best-beaches-fajardo' => [
            'key' => 'best-beaches-fajardo',
            'slug' => 'best-beaches-fajardo',
            'page_key' => 'best_beaches_fajardo',
            'mode' => 'best',
            'municipalities' => ['Fajardo'],
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_beaches_fajardo.hero_title'),
                'subtitle' => __('pages.best_beaches_fajardo.hero_subtitle'),
                'meta' => __('pages.best_beaches_fajardo.hero_meta'),
            ],
        ],
        'best-beaches-vieques' => [
            'key' => 'best-beaches-vieques',
            'slug' => 'best-beaches-vieques',
            'page_key' => 'best_beaches_vieques',
            'mode' => 'best',
            'municipalities' => ['Vieques'],
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_beaches_vieques.hero_title'),
                'subtitle' => __('pages.best_beaches_vieques.hero_subtitle'),
                'meta' => __('pages.best_beaches_vieques.hero_meta'),
            ],
        ],
        'best-beaches-culebra' => [
            'key' => 'best-beaches-culebra',
            'slug' => 'best-beaches-culebra',
            'page_key' => 'best_beaches_culebra',
            'mode' => 'best',
            'municipalities' => ['Culebra'],
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_beaches_culebra.hero_title'),
                'subtitle' => __('pages.best_beaches_culebra.hero_subtitle'),
                'meta' => __('pages.best_beaches_culebra.hero_meta'),
            ],
        ],
        'best-beaches-luquillo' => [
            'key' => 'best-beaches-luquillo',
            'slug' => 'best-beaches-luquillo',
            'page_key' => 'best_beaches_luquillo',
            'mode' => 'best',
            'municipalities' => ['Luquillo'],
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => __('pages.best_beaches_luquillo.hero_title'),
                'subtitle' => __('pages.best_beaches_luquillo.hero_subtitle'),
                'meta' => __('pages.best_beaches_luquillo.hero_meta'),
            ],
        ],
    ];
}
