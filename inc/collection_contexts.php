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
    return [
        'best-beaches' => [
            'key' => 'best-beaches',
            'slug' => 'best-beaches',
            'mode' => 'best',
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => '15 Best Beaches in Puerto Rico',
                'subtitle' => 'Find your perfect Caribbean paradise with insider tips and directions',
                'meta' => 'Updated January 2025 • 233+ beaches analyzed',
            ],
        ],
        'best-beaches-san-juan' => [
            'key' => 'best-beaches-san-juan',
            'slug' => 'best-beaches-san-juan',
            'mode' => 'best',
            'municipalities' => ['San Juan', 'Carolina'],
            'default_sort' => 'rating',
            'default_limit' => 12,
            'hero' => [
                'title' => 'Best Beaches in San Juan',
                'subtitle' => 'Urban beaches with Caribbean water and city convenience',
                'meta' => 'Updated January 2025 • Expert-curated list',
            ],
        ],
        'best-family-beaches' => [
            'key' => 'best-family-beaches',
            'slug' => 'best-family-beaches',
            'mode' => 'tag',
            'context_tag' => 'family-friendly',
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => 'Best Family Beaches in Puerto Rico',
                'subtitle' => 'Calm waters, amenities, and easy access for all ages',
                'meta' => 'Updated January 2025 • Family-focused picks',
            ],
        ],
        'best-snorkeling-beaches' => [
            'key' => 'best-snorkeling-beaches',
            'slug' => 'best-snorkeling-beaches',
            'mode' => 'tag',
            'context_tag' => 'snorkeling',
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => 'Best Snorkeling Beaches in Puerto Rico',
                'subtitle' => 'Crystal-clear water, reefs, and marine life hotspots',
                'meta' => 'Updated January 2025 • Top snorkeling spots',
            ],
        ],
        'best-surfing-beaches' => [
            'key' => 'best-surfing-beaches',
            'slug' => 'best-surfing-beaches',
            'mode' => 'tag',
            'context_tag' => 'surfing',
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => 'Best Surfing Beaches in Puerto Rico',
                'subtitle' => 'From beginner breaks to world-class waves',
                'meta' => 'Updated January 2025 • Surf-ready picks',
            ],
        ],
        'beaches-near-san-juan' => [
            'key' => 'beaches-near-san-juan',
            'slug' => 'beaches-near-san-juan',
            'mode' => 'radius',
            'center_lat' => 18.4655,
            'center_lng' => -66.1057,
            'radius_km' => 30,
            'default_sort' => 'distance',
            'default_limit' => 15,
            'hero' => [
                'title' => 'Beaches Near San Juan, Puerto Rico',
                'subtitle' => 'Find beaches within a short drive of the capital',
                'meta' => 'Updated January 2025 • Within 30 minutes of San Juan',
            ],
        ],
        'beaches-near-san-juan-airport' => [
            'key' => 'beaches-near-san-juan-airport',
            'slug' => 'beaches-near-san-juan-airport',
            'mode' => 'radius',
            'center_lat' => 18.4394,
            'center_lng' => -66.0018,
            'radius_km' => 15,
            'default_sort' => 'distance',
            'default_limit' => 10,
            'hero' => [
                'title' => 'Beaches Near San Juan Airport',
                'subtitle' => 'Quick beach escapes for layovers and travel days',
                'meta' => 'Updated January 2025 • Within 15km of SJU',
            ],
        ],
        'hidden-beaches-puerto-rico' => [
            'key' => 'hidden-beaches-puerto-rico',
            'slug' => 'hidden-beaches-puerto-rico',
            'mode' => 'hidden',
            'hidden_tags' => ['secluded', 'remote', 'wild'],
            'max_review_count' => 200,
            'default_sort' => 'rating',
            'default_limit' => 15,
            'hero' => [
                'title' => 'Hidden Beaches in Puerto Rico',
                'subtitle' => 'Off-the-beaten-path beaches and secret island gems',
                'meta' => 'Updated January 2025 • Curated hidden-beach guide',
            ],
        ],
    ];
}
