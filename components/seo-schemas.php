<?php
/**
 * SEO Schema Components
 * Generates JSON-LD structured data for beaches
 */

/**
 * Generate Beach structured data (schema.org/Beach)
 */
function beachSchema(array $beach): string {
    $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8082';

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Beach',
        '@id' => $appUrl . '/beach/' . $beach['slug'],
        'name' => $beach['name'],
        'description' => $beach['description'] ?? "Explore {$beach['name']} in {$beach['municipality']}, Puerto Rico.",
        'url' => $appUrl . '/beach/' . $beach['slug'],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => $beach['lat'],
            'longitude' => $beach['lng']
        ],
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => $beach['municipality'],
            'addressRegion' => 'PR',
            'addressCountry' => 'US'
        ],
        'isAccessibleForFree' => true,
        'publicAccess' => true
    ];

    // Add image
    if (!empty($beach['cover_image'])) {
        $schema['image'] = strpos($beach['cover_image'], 'http') === 0
            ? $beach['cover_image']
            : $appUrl . $beach['cover_image'];
    }

    // Add rating if available
    if (!empty($beach['google_rating'])) {
        $schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $beach['google_rating'],
            'reviewCount' => $beach['google_review_count'] ?? 1,
            'bestRating' => 5,
            'worstRating' => 1
        ];
    }

    // Add amenities
    if (!empty($beach['amenities'])) {
        $amenityList = [];
        foreach ($beach['amenities'] as $amenity) {
            $amenityList[] = [
                '@type' => 'LocationFeatureSpecification',
                'name' => ucwords(str_replace('-', ' ', $amenity)),
                'value' => true
            ];
        }
        $schema['amenityFeature'] = $amenityList;
    }

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate ItemList for beach listings
 */
function beachListSchema(array $beaches, string $listName = 'Puerto Rico Beaches'): string {
    $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8082';

    $items = [];
    foreach ($beaches as $index => $beach) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $beach['name'],
            'url' => $appUrl . '/beach/' . $beach['slug']
        ];
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => $listName,
        'description' => 'Discover beautiful beaches across Puerto Rico',
        'numberOfItems' => count($beaches),
        'itemListElement' => $items
    ];

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate Organization schema for the site
 */
function organizationSchema(): string {
    $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8082';
    $appName = $_ENV['APP_NAME'] ?? 'Puerto Rico Beach Finder';

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $appName,
        'url' => $appUrl,
        'logo' => $appUrl . '/assets/icons/icon-512x512.png',
        'description' => 'Discover 230+ beautiful beaches across Puerto Rico with detailed information, weather conditions, and user reviews.',
        'sameAs' => []
    ];

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate WebSite schema with search action
 */
function websiteSchema(): string {
    $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8082';
    $appName = $_ENV['APP_NAME'] ?? 'Puerto Rico Beach Finder';

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $appName,
        'url' => $appUrl,
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => $appUrl . '/?search={search_term_string}'
            ],
            'query-input' => 'required name=search_term_string'
        ]
    ];

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate BreadcrumbList schema
 */
function breadcrumbSchema(array $items): string {
    $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8082';

    $listItems = [];
    foreach ($items as $index => $item) {
        $listItems[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['name'],
            'item' => strpos($item['url'], 'http') === 0 ? $item['url'] : $appUrl . $item['url']
        ];
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $listItems
    ];

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate FAQPage schema
 */
function faqSchema(array $faqs): string {
    $items = [];
    foreach ($faqs as $faq) {
        $items[] = [
            '@type' => 'Question',
            'name' => $faq['question'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $faq['answer']
            ]
        ];
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $items
    ];

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate Review schema for user reviews
 */
function reviewsSchema(array $beach, array $reviews): string {
    if (empty($reviews)) return '';

    $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8082';

    $reviewItems = [];
    foreach ($reviews as $review) {
        $reviewItems[] = [
            '@type' => 'Review',
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => $review['rating'],
                'bestRating' => 5,
                'worstRating' => 1
            ],
            'author' => [
                '@type' => 'Person',
                'name' => $review['user_name'] ?? 'Anonymous'
            ],
            'reviewBody' => $review['review_text'] ?? '',
            'datePublished' => $review['created_at'] ?? date('Y-m-d')
        ];
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Beach',
        'name' => $beach['name'],
        'url' => $appUrl . '/beach/' . $beach['slug'],
        'review' => $reviewItems
    ];

    if (!empty($beach['avg_user_rating'])) {
        $schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => round($beach['avg_user_rating'], 1),
            'reviewCount' => $beach['user_review_count'] ?? count($reviews),
            'bestRating' => 5,
            'worstRating' => 1
        ];
    }

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate TouristAttraction schema for travel search visibility
 */
function touristAttractionSchema(array $beach): string {
    $appUrl = $_ENV['APP_URL'] ?? 'https://www.puertoricobeachfinder.com';

    // Map tags to tourist types
    $touristTypes = ['Beach Lovers', 'Nature Enthusiasts'];
    $tags = $beach['tags'] ?? [];
    if (in_array('family-friendly', $tags)) $touristTypes[] = 'Families';
    if (in_array('surfing', $tags)) $touristTypes[] = 'Surfers';
    if (in_array('snorkeling', $tags) || in_array('scuba-diving', $tags)) $touristTypes[] = 'Divers';
    if (in_array('romantic', $tags)) $touristTypes[] = 'Couples';
    if (in_array('hiking', $tags)) $touristTypes[] = 'Adventure Seekers';

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'TouristAttraction',
        'name' => $beach['name'],
        'description' => $beach['description'] ?? "Beautiful beach in {$beach['municipality']}, Puerto Rico",
        'url' => $appUrl . '/beach/' . $beach['slug'],
        'touristType' => array_unique($touristTypes),
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => $beach['lat'],
            'longitude' => $beach['lng']
        ],
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => $beach['municipality'],
            'addressRegion' => 'Puerto Rico',
            'addressCountry' => 'US'
        ],
        'isAccessibleForFree' => true,
        'publicAccess' => true
    ];

    // Add image
    if (!empty($beach['cover_image'])) {
        $schema['image'] = strpos($beach['cover_image'], 'http') === 0
            ? $beach['cover_image']
            : $appUrl . $beach['cover_image'];
    }

    // Add rating if available
    if (!empty($beach['google_rating'])) {
        $schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $beach['google_rating'],
            'reviewCount' => $beach['google_review_count'] ?? 1,
            'bestRating' => 5,
            'worstRating' => 1
        ];
    }

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate dynamic FAQs for a beach
 */
function generateBeachFAQs(array $beach): array {
    $faqs = [];
    $name = $beach['name'];
    $municipality = $beach['municipality'];
    $tags = $beach['tags'] ?? [];
    $amenities = $beach['amenities'] ?? [];

    // Location FAQ
    $faqs[] = [
        'question' => "Where is {$name} located?",
        'answer' => "{$name} is located in {$municipality}, Puerto Rico. " .
                   "The exact coordinates are {$beach['lat']}, {$beach['lng']}. " .
                   "You can use GPS navigation or follow signs to {$municipality} and look for beach access points."
    ];

    // Swimming FAQ
    $swimmingInfo = "Yes, {$name} is a public beach open for swimming.";
    if (in_array('calm-waters', $tags)) {
        $swimmingInfo .= " The beach has calm waters, making it ideal for swimming.";
    } elseif (in_array('surfing', $tags)) {
        $swimmingInfo .= " Note that this beach is known for surfing conditions, so waves can be strong.";
    }
    if (in_array('lifeguard', $amenities)) {
        $swimmingInfo .= " Lifeguards are available on duty.";
    }
    $faqs[] = [
        'question' => "Is {$name} good for swimming?",
        'answer' => $swimmingInfo
    ];

    // Facilities FAQ
    $facilitiesAnswer = "";
    if (!empty($amenities)) {
        $amenityNames = array_map(function($a) {
            return ucwords(str_replace('-', ' ', $a));
        }, $amenities);
        $facilitiesAnswer = "{$name} offers the following facilities: " . implode(', ', $amenityNames) . ".";
    } else {
        $facilitiesAnswer = "{$name} is a natural beach. Facilities may be limited, so consider bringing your own supplies.";
    }
    $faqs[] = [
        'question' => "What facilities are available at {$name}?",
        'answer' => $facilitiesAnswer
    ];

    // Best activities FAQ
    $activities = [];
    if (in_array('swimming', $tags) || in_array('calm-waters', $tags)) $activities[] = 'swimming';
    if (in_array('snorkeling', $tags)) $activities[] = 'snorkeling';
    if (in_array('surfing', $tags)) $activities[] = 'surfing';
    if (in_array('kayaking', $tags)) $activities[] = 'kayaking';
    if (in_array('paddleboarding', $tags)) $activities[] = 'paddleboarding';
    if (in_array('fishing', $tags)) $activities[] = 'fishing';
    if (in_array('hiking', $tags)) $activities[] = 'hiking nearby';
    if (empty($activities)) $activities = ['swimming', 'relaxing', 'sunbathing'];

    $faqs[] = [
        'question' => "What activities can I do at {$name}?",
        'answer' => "Popular activities at {$name} include " . implode(', ', $activities) .
                   ". The beach is " . (in_array('family-friendly', $tags) ? "family-friendly and " : "") .
                   "perfect for a day trip from " . $municipality . "."
    ];

    // Parking FAQ
    $parkingAnswer = "";
    if (in_array('parking', $amenities) || in_array('free-parking', $amenities)) {
        $parkingAnswer = "Yes, {$name} has parking available" .
                        (in_array('free-parking', $amenities) ? " and it's free" : "") . ".";
    } else {
        $parkingAnswer = "Parking near {$name} may be limited. It's recommended to arrive early, especially on weekends and holidays.";
    }
    $faqs[] = [
        'question' => "Is there parking at {$name}?",
        'answer' => $parkingAnswer
    ];

    // Best time FAQ
    $faqs[] = [
        'question' => "What is the best time to visit {$name}?",
        'answer' => "The best time to visit {$name} is during Puerto Rico's dry season from December to April. " .
                   "For fewer crowds, visit on weekday mornings. " .
                   (in_array('surfing', $tags) ? "For surfing, winter months (November-March) typically have the best swells. " : "") .
                   "Always check weather conditions before visiting."
    ];

    return $faqs;
}

/**
 * Generate Speakable schema for voice assistants
 */
function speakableSchema(): string {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'speakable' => [
            '@type' => 'SpeakableSpecification',
            'cssSelector' => [
                '.beach-description',
                '.beach-highlights',
                '.beach-facts',
                'h1',
                '.page-description'
            ]
        ]
    ];

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate Article schema for landing pages
 */
function articleSchema(string $title, string $description, string $url, ?string $image = null, ?string $datePublished = null): string {
    $appUrl = $_ENV['APP_URL'] ?? 'https://www.puertoricobeachfinder.com';
    $appName = $_ENV['APP_NAME'] ?? 'Puerto Rico Beach Finder';

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $title,
        'description' => $description,
        'url' => strpos($url, 'http') === 0 ? $url : $appUrl . $url,
        'author' => [
            '@type' => 'Organization',
            'name' => $appName,
            'url' => $appUrl
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => $appName,
            'url' => $appUrl,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $appUrl . '/assets/icons/icon-512x512.png'
            ]
        ],
        'datePublished' => $datePublished ?? date('Y-m-d'),
        'dateModified' => date('Y-m-d')
    ];

    if ($image) {
        $schema['image'] = strpos($image, 'http') === 0 ? $image : $appUrl . $image;
    }

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate CollectionPage schema for list pages
 */
function collectionPageSchema(string $title, string $description, array $beaches): string {
    $appUrl = $_ENV['APP_URL'] ?? 'https://www.puertoricobeachfinder.com';

    $items = [];
    foreach (array_slice($beaches, 0, 20) as $index => $beach) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'item' => [
                '@type' => 'Beach',
                'name' => $beach['name'],
                'url' => $appUrl . '/beach/' . $beach['slug'],
                'description' => substr($beach['description'] ?? '', 0, 150)
            ]
        ];
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $title,
        'description' => $description,
        'mainEntity' => [
            '@type' => 'ItemList',
            'numberOfItems' => count($beaches),
            'itemListElement' => $items
        ]
    ];

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}
