<?php
/**
 * Individual Beach Detail Page
 * SEO-friendly full page for each beach
 */

require_once __DIR__ . '/inc/session.php';
session_start();
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/constants.php';
require_once __DIR__ . '/components/seo-schemas.php';

// Get slug from URL (set by Nginx rewrite or query param)
$slug = $_GET['slug'] ?? '';

if (!$slug) {
    http_response_code(404);
    $pageTitle = 'Beach Not Found';
    include __DIR__ . '/components/header.php';
    echo '<div class="max-w-2xl mx-auto px-4 py-16 text-center">
            <div class="text-6xl mb-4">🏖️</div>
            <h1 class="text-2xl font-bold text-gray-900 mb-4">Beach Not Found</h1>
            <p class="text-gray-600 mb-6">The beach you\'re looking for doesn\'t exist or has been removed.</p>
            <a href="/" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                Browse All Beaches
            </a>
          </div>';
    include __DIR__ . '/components/footer.php';
    exit;
}

// Fetch beach
$beach = queryOne('SELECT * FROM beaches WHERE slug = :slug AND publish_status = "published"', [':slug' => $slug]);

if (!$beach) {
    http_response_code(404);
    $pageTitle = 'Beach Not Found';
    include __DIR__ . '/components/header.php';
    echo '<div class="max-w-2xl mx-auto px-4 py-16 text-center">
            <div class="text-6xl mb-4">🏖️</div>
            <h1 class="text-2xl font-bold text-gray-900 mb-4">Beach Not Found</h1>
            <p class="text-gray-600 mb-6">The beach you\'re looking for doesn\'t exist or has been removed.</p>
            <a href="/" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                Browse All Beaches
            </a>
          </div>';
    include __DIR__ . '/components/footer.php';
    exit;
}

// Fetch related data
$beach['tags'] = array_column(
    query('SELECT tag FROM beach_tags WHERE beach_id = :id', [':id' => $beach['id']]),
    'tag'
);
$beach['amenities'] = array_column(
    query('SELECT amenity FROM beach_amenities WHERE beach_id = :id', [':id' => $beach['id']]),
    'amenity'
);
$beach['gallery'] = array_column(
    query('SELECT image_url FROM beach_gallery WHERE beach_id = :id ORDER BY position', [':id' => $beach['id']]),
    'image_url'
);
$beach['features'] = query(
    'SELECT title, description FROM beach_features WHERE beach_id = :id ORDER BY position',
    [':id' => $beach['id']]
);
$beach['tips'] = query(
    'SELECT category, tip FROM beach_tips WHERE beach_id = :id ORDER BY position',
    [':id' => $beach['id']]
);

// Fetch user reviews
$reviews = query("
    SELECT
        r.id, r.rating, r.title, r.review_text, r.visit_date, r.visit_type,
        r.helpful_count, r.created_at, r.would_recommend, r.user_id,
        u.name as user_name, u.avatar_url
    FROM beach_reviews r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.beach_id = :id AND r.status = 'published'
    ORDER BY r.created_at DESC
", [':id' => $beach['id']]);

$userReviewCount = count($reviews);
$avgUserRating = $beach['avg_user_rating'] ?? null;

// Page metadata
$pageTitle = $beach['name'] . ' - ' . $beach['municipality'];
$pageDescription = $beach['description']
    ? substr($beach['description'], 0, 160)
    : 'Discover ' . $beach['name'] . ' in ' . $beach['municipality'] . ', Puerto Rico. View beach conditions, amenities, photos, and directions.';

// Generate structured data using SEO component
$extraHead = beachSchema($beach);

// Add TouristAttraction schema for travel queries
$extraHead .= touristAttractionSchema($beach);

// Add reviews schema if reviews exist (enables rich snippets)
if (!empty($reviews)) {
    $beach['avg_user_rating'] = $avgUserRating;
    $beach['user_review_count'] = $userReviewCount;
    $extraHead .= reviewsSchema($beach, $reviews);
}

// Add breadcrumbs
$extraHead .= breadcrumbSchema([
    ['name' => 'Home', 'url' => '/'],
    ['name' => $beach['municipality'], 'url' => '/?municipality=' . urlencode($beach['municipality'])],
    ['name' => $beach['name'], 'url' => '/beach/' . $beach['slug']]
]);

// Generate dynamic FAQ schema
$faqs = generateBeachFAQs($beach);
$extraHead .= faqSchema($faqs);

// Add speakable schema for voice assistants
$extraHead .= speakableSchema();

// Set Open Graph image
$ogImage = $beach['cover_image'] ? ($_ENV['APP_URL'] ?? '') . $beach['cover_image'] : null;

include __DIR__ . '/components/header.php';
?>

<!-- Hero Image -->
<div class="relative h-64 md:h-96 overflow-hidden">
    <?php if ($beach['cover_image']): ?>
    <img src="<?= h($beach['cover_image']) ?>"
         alt="<?= h($beach['name']) ?>"
         class="w-full h-full object-cover">
    <?php else: ?>
    <div class="w-full h-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
        <span class="text-8xl">🏖️</span>
    </div>
    <?php endif; ?>
    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>

    <!-- Title overlay -->
    <div class="absolute bottom-0 left-0 right-0 p-6 md:p-8">
        <div class="max-w-7xl mx-auto">
            <nav class="text-white/70 text-sm mb-2">
                <a href="/" class="hover:text-white">Beaches</a>
                <span class="mx-2">›</span>
                <a href="/?municipality=<?= urlencode($beach['municipality']) ?>" class="hover:text-white"><?= h($beach['municipality']) ?></a>
            </nav>
            <h1 class="text-3xl md:text-4xl font-bold text-white"><?= h($beach['name']) ?></h1>
            <p class="text-white/80 mt-1"><?= h($beach['municipality']) ?>, Puerto Rico</p>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Main Column -->
        <div class="lg:col-span-2 space-y-8">

            <!-- Quick Info Bar -->
            <div class="flex flex-wrap items-center gap-4 p-4 bg-white rounded-xl shadow-sm">
                <?php if ($beach['google_rating']): ?>
                <div class="flex items-center gap-1.5 bg-amber-50 border border-amber-200 px-3 py-2 rounded-lg" aria-label="Google rating: <?= number_format($beach['google_rating'], 1) ?> out of 5">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="#FACC15" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    <span class="font-bold text-lg text-amber-800"><?= number_format($beach['google_rating'], 1) ?></span>
                    <span class="text-amber-700 text-sm font-medium">Google</span>
                    <?php if ($beach['google_review_count']): ?>
                    <span class="text-amber-600/70 text-sm">(<?= number_format($beach['google_review_count']) ?>)</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($avgUserRating): ?>
                <div class="flex items-center gap-1.5 bg-blue-50 border border-blue-200 px-3 py-2 rounded-lg" aria-label="Community rating: <?= number_format($avgUserRating, 1) ?> out of 5">
                    <i data-lucide="star" class="w-5 h-5 text-blue-500 fill-blue-500" aria-hidden="true"></i>
                    <span class="font-bold text-lg text-blue-800"><?= number_format($avgUserRating, 1) ?></span>
                    <span class="text-blue-700 text-sm font-medium">Community</span>
                    <span class="text-blue-600/70 text-sm">(<?= $userReviewCount ?>)</span>
                </div>
                <?php endif; ?>

                <?php if ($beach['access_label']): ?>
                <div class="text-gray-600">
                    <span class="font-medium">Access:</span> <?= h($beach['access_label']) ?>
                </div>
                <?php endif; ?>

                <div class="ml-auto flex gap-2">
                    <a href="<?= h(getDirectionsUrl($beach)) ?>"
                       target="_blank"
                       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        <i data-lucide="navigation" class="w-4 h-4" aria-hidden="true"></i>
                        <span>Directions</span>
                    </a>
                    <button onclick="shareBeach('<?= h($beach['slug']) ?>', '<?= h(addslashes($beach['name'])) ?>')"
                            aria-label="Share this beach"
                            class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
                        <i data-lucide="share-2" class="w-4 h-4" aria-hidden="true"></i>
                        <span>Share</span>
                    </button>
                </div>
            </div>

            <!-- Tags -->
            <?php if (!empty($beach['tags'])): ?>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($beach['tags'] as $tag): ?>
                <a href="/?tags[]=<?= h($tag) ?>"
                   class="inline-block bg-blue-50 hover:bg-blue-100 text-blue-700 px-4 py-2 rounded-full transition-colors">
                    <?= h(getTagLabel($tag)) ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Entity-Rich Quick Facts Box -->
            <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-xl p-6 border border-blue-100">
                <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i data-lucide="clipboard-list" class="w-5 h-5 text-blue-600" aria-hidden="true"></i>
                    <span>Quick Facts: <?= h($beach['name']) ?></span>
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Location</div>
                        <div class="font-semibold text-gray-900"><?= h($beach['municipality']) ?></div>
                        <div class="text-sm text-gray-600">Puerto Rico</div>
                    </div>

                    <?php if ($beach['google_rating']): ?>
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <div class="text-xs text-amber-600 uppercase tracking-wide font-medium">Google Rating</div>
                        <div class="font-semibold text-gray-900 flex items-center gap-1">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="#FACC15" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            <?= number_format($beach['google_rating'], 1) ?>/5
                        </div>
                        <div class="text-sm text-gray-600"><?= number_format($beach['google_review_count']) ?> reviews</div>
                    </div>
                    <?php endif; ?>

                    <?php if ($beach['lat'] && $beach['lng']): ?>
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">GPS</div>
                        <div class="font-semibold text-gray-900 text-sm"><?= number_format($beach['lat'], 4) ?>°N</div>
                        <div class="text-sm text-gray-600"><?= number_format(abs($beach['lng']), 4) ?>°W</div>
                    </div>
                    <?php endif; ?>

                    <?php if ($beach['access_label']): ?>
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Access</div>
                        <div class="font-semibold text-gray-900"><?= h($beach['access_label']) ?></div>
                        <div class="text-sm text-gray-600">Public beach</div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($beach['tags'])): ?>
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Best For</div>
                        <div class="font-semibold text-gray-900"><?= h(getTagLabel($beach['tags'][0])) ?></div>
                        <?php if (count($beach['tags']) > 1): ?>
                        <div class="text-sm text-gray-600">+<?= count($beach['tags']) - 1 ?> more activities</div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($beach['amenities'])): ?>
                    <div class="bg-white rounded-lg p-3 shadow-sm">
                        <div class="text-xs text-gray-500 uppercase tracking-wide">Amenities</div>
                        <div class="font-semibold text-gray-900"><?= count($beach['amenities']) ?> available</div>
                        <div class="text-sm text-gray-600">
                            <?= in_array('parking', $beach['amenities']) ? 'Parking' : '' ?>
                            <?= in_array('restrooms', $beach['amenities']) ? (in_array('parking', $beach['amenities']) ? ', ' : '') . 'Restrooms' : '' ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($beach['best_time'] || $beach['parking_details']): ?>
                <div class="mt-4 pt-4 border-t border-blue-100 grid md:grid-cols-2 gap-4 text-sm">
                    <?php if ($beach['best_time']): ?>
                    <div class="flex items-start gap-2">
                        <i data-lucide="clock" class="w-4 h-4 text-blue-600 mt-0.5" aria-hidden="true"></i>
                        <div>
                            <span class="font-medium text-gray-900">Best Time:</span>
                            <span class="text-gray-600"><?= h($beach['best_time']) ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($beach['parking_details']): ?>
                    <div class="flex items-start gap-2">
                        <i data-lucide="car" class="w-4 h-4 text-blue-600 mt-0.5" aria-hidden="true"></i>
                        <div>
                            <span class="font-medium text-gray-900">Parking:</span>
                            <span class="text-gray-600"><?= h($beach['parking_details']) ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <?php if ($beach['description']): ?>
            <div class="prose max-w-none">
                <h2 class="text-xl font-bold text-gray-900 mb-3">About <?= h($beach['name']) ?></h2>
                <p class="text-gray-600 leading-relaxed"><?= nl2br(h($beach['description'])) ?></p>
            </div>
            <?php endif; ?>

            <!-- Features -->
            <?php if (!empty($beach['features'])): ?>
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-4">Beach Highlights</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($beach['features'] as $feature): ?>
                    <div class="bg-gray-50 p-4 rounded-xl">
                        <h3 class="font-semibold text-gray-900"><?= h($feature['title']) ?></h3>
                        <p class="text-gray-600 text-sm mt-1"><?= h($feature['description']) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tips -->
            <?php if (!empty($beach['tips'])): ?>
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-4">Visitor Tips</h2>
                <div class="space-y-3">
                    <?php foreach ($beach['tips'] as $tip): ?>
                    <div class="flex gap-3 p-3 bg-blue-50 rounded-lg">
                        <span class="text-blue-600 font-semibold shrink-0"><?= h($tip['category']) ?></span>
                        <span class="text-gray-700"><?= h($tip['tip']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Gallery -->
            <?php if (!empty($beach['gallery'])): ?>
            <div>
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i data-lucide="images" class="w-5 h-5 text-blue-600" aria-hidden="true"></i>
                    Photos
                </h2>
                <div class="gallery-grid">
                    <?php foreach ($beach['gallery'] as $idx => $image): ?>
                    <img src="<?= h($image) ?>"
                         alt="<?= h($beach['name']) ?> - Photo <?= $idx + 1 ?>"
                         class="rounded-lg cursor-pointer hover:opacity-90 transition-opacity gallery-image"
                         data-gallery-index="<?= $idx ?>"
                         onclick="openLightbox(<?= $idx ?>)"
                         loading="lazy">
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Reviews Section -->
            <div id="reviews" class="pt-4">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Visitor Reviews</h2>
                        <?php if ($avgUserRating): ?>
                        <div class="flex items-center gap-2 mt-1">
                            <div class="flex text-yellow-500">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span><?= $i <= round($avgUserRating) ? '★' : '☆' ?></span>
                                <?php endfor; ?>
                            </div>
                            <span class="font-semibold"><?= number_format($avgUserRating, 1) ?></span>
                            <span class="text-gray-500">(<?= $userReviewCount ?> review<?= $userReviewCount !== 1 ? 's' : '' ?>)</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if (isAuthenticated()): ?>
                    <button onclick="openReviewForm('<?= h($beach['id']) ?>', '<?= h(addslashes($beach['name'])) ?>')"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Write a Review
                    </button>
                    <?php else: ?>
                    <a href="/login.php?redirect=<?= urlencode('/beach/' . $beach['slug'] . '#reviews') ?>"
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors">
                        Sign in to Review
                    </a>
                    <?php endif; ?>
                </div>

                <?php if (!empty($reviews)): ?>
                <div class="space-y-4">
                    <?php foreach ($reviews as $review): ?>
                    <?php include __DIR__ . '/components/review-card.php'; ?>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-12 bg-gray-50 rounded-xl">
                    <div class="text-5xl mb-4">📝</div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">No reviews yet</h3>
                    <p class="text-gray-500 mb-4">Be the first to share your experience at <?= h($beach['name']) ?>!</p>
                    <?php if (isAuthenticated()): ?>
                    <button onclick="openReviewForm('<?= h($beach['id']) ?>', '<?= h(addslashes($beach['name'])) ?>')"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        Write the First Review
                    </button>
                    <?php else: ?>
                    <a href="/login.php?redirect=<?= urlencode('/beach/' . $beach['slug'] . '#reviews') ?>"
                       class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        Sign in to Write a Review
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Conditions Card -->
            <?php if ($beach['sargassum'] || $beach['surf'] || $beach['wind']): ?>
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold text-gray-900 mb-4">Current Conditions</h3>
                <div class="space-y-3">
                    <?php if ($beach['sargassum']): ?>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 inline-flex items-center gap-2">
                            <i data-lucide="leaf" class="w-4 h-4" aria-hidden="true"></i>
                            <span>Sargassum</span>
                        </span>
                        <span class="<?= getConditionClass($beach['sargassum'], 'sargassum') ?> px-3 py-1 rounded-lg text-sm">
                            <?= h(getConditionLabel('sargassum', $beach['sargassum'])) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <?php if ($beach['surf']): ?>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 inline-flex items-center gap-2">
                            <i data-lucide="waves" class="w-4 h-4" aria-hidden="true"></i>
                            <span>Surf</span>
                        </span>
                        <span class="<?= getConditionClass($beach['surf'], 'surf') ?> px-3 py-1 rounded-lg text-sm">
                            <?= h(getConditionLabel('surf', $beach['surf'])) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <?php if ($beach['wind']): ?>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 inline-flex items-center gap-2">
                            <i data-lucide="wind" class="w-4 h-4" aria-hidden="true"></i>
                            <span>Wind</span>
                        </span>
                        <span class="<?= getConditionClass($beach['wind'], 'wind') ?> px-3 py-1 rounded-lg text-sm">
                            <?= h(getConditionLabel('wind', $beach['wind'])) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Amenities Card -->
            <?php if (!empty($beach['amenities'])): ?>
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold text-gray-900 mb-4">Amenities</h3>
                <div class="space-y-2">
                    <?php foreach ($beach['amenities'] as $amenity): ?>
                    <div class="flex items-center gap-2 text-gray-600">
                        <i data-lucide="check" class="w-4 h-4 text-green-500" aria-hidden="true"></i>
                        <span><?= h(getAmenityLabel($amenity)) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Practical Info -->
            <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
                <h3 class="font-bold text-gray-900">Practical Information</h3>

                <?php if ($beach['parking_details']): ?>
                <div>
                    <h4 class="font-medium text-gray-900 text-sm inline-flex items-center gap-1.5">
                        <i data-lucide="car" class="w-4 h-4" aria-hidden="true"></i>
                        <span>Parking</span>
                    </h4>
                    <p class="text-gray-600 text-sm mt-1"><?= h($beach['parking_details']) ?></p>
                </div>
                <?php endif; ?>

                <?php if ($beach['safety_info']): ?>
                <div>
                    <h4 class="font-medium text-gray-900 text-sm inline-flex items-center gap-1.5">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-500" aria-hidden="true"></i>
                        <span>Safety</span>
                    </h4>
                    <p class="text-gray-600 text-sm mt-1"><?= h($beach['safety_info']) ?></p>
                </div>
                <?php endif; ?>

                <?php if ($beach['best_time']): ?>
                <div>
                    <h4 class="font-medium text-gray-900 text-sm inline-flex items-center gap-1.5">
                        <i data-lucide="clock" class="w-4 h-4" aria-hidden="true"></i>
                        <span>Best Time</span>
                    </h4>
                    <p class="text-gray-600 text-sm mt-1"><?= h($beach['best_time']) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Map Preview -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div id="beach-map" class="h-48"></div>
                <div class="p-4">
                    <p class="text-sm text-gray-600 mb-3 inline-flex items-center gap-1.5">
                        <i data-lucide="map-pin" class="w-4 h-4" aria-hidden="true"></i>
                        <span><?= h($beach['municipality']) ?>, Puerto Rico</span>
                    </p>
                    <a href="<?= h(getDirectionsUrl($beach)) ?>"
                       target="_blank"
                       class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg font-medium transition-colors">
                        Get Directions
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div id="share-modal" class="share-modal" onclick="closeShareModal()">
    <div class="share-modal-content" onclick="event.stopPropagation()">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Share Beach</h3>
            <button onclick="closeShareModal()" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div id="share-modal-body"></div>
    </div>
</div>

<script>
// Initialize small map for sidebar
document.addEventListener('DOMContentLoaded', () => {
    const mapContainer = document.getElementById('beach-map');
    if (mapContainer && typeof maplibregl !== 'undefined') {
        const map = new maplibregl.Map({
            container: 'beach-map',
            style: 'https://basemaps.cartocdn.com/gl/positron-gl-style/style.json',
            center: [<?= $beach['lng'] ?>, <?= $beach['lat'] ?>],
            zoom: 13,
            interactive: false
        });

        new maplibregl.Marker({ color: '#2563eb' })
            .setLngLat([<?= $beach['lng'] ?>, <?= $beach['lat'] ?>])
            .addTo(map);
    }
});
</script>

<?php if (!empty($beach['gallery'])): ?>
<!-- Gallery Lightbox -->
<div id="gallery-lightbox" class="lightbox-overlay" onclick="closeLightbox(event)" role="dialog" aria-modal="true" aria-label="Image gallery">
    <div class="lightbox-container" onclick="event.stopPropagation()">
        <!-- Close button -->
        <button onclick="closeLightbox()" class="lightbox-close" aria-label="Close gallery">
            <i data-lucide="x" class="w-6 h-6"></i>
        </button>

        <!-- Previous button -->
        <button onclick="navigateLightbox(-1)" class="lightbox-nav lightbox-prev" aria-label="Previous image">
            <i data-lucide="chevron-left" class="w-8 h-8"></i>
        </button>

        <!-- Image container -->
        <div class="lightbox-content">
            <img id="lightbox-image" src="" alt="" class="lightbox-image">
            <div id="lightbox-counter" class="lightbox-counter"></div>
        </div>

        <!-- Next button -->
        <button onclick="navigateLightbox(1)" class="lightbox-nav lightbox-next" aria-label="Next image">
            <i data-lucide="chevron-right" class="w-8 h-8"></i>
        </button>
    </div>
</div>

<script>
// Gallery Lightbox
const galleryImages = <?= json_encode($beach['gallery']) ?>;
let currentImageIndex = 0;

function openLightbox(index) {
    currentImageIndex = index;
    updateLightboxImage();
    document.getElementById('gallery-lightbox').classList.add('open');
    document.body.style.overflow = 'hidden';

    // Re-initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function closeLightbox(e) {
    if (e && e.target !== document.getElementById('gallery-lightbox')) return;
    document.getElementById('gallery-lightbox').classList.remove('open');
    document.body.style.overflow = '';
}

function navigateLightbox(direction) {
    currentImageIndex += direction;
    if (currentImageIndex >= galleryImages.length) currentImageIndex = 0;
    if (currentImageIndex < 0) currentImageIndex = galleryImages.length - 1;
    updateLightboxImage();
}

function updateLightboxImage() {
    const img = document.getElementById('lightbox-image');
    const counter = document.getElementById('lightbox-counter');
    img.src = galleryImages[currentImageIndex];
    img.alt = '<?= h($beach['name']) ?> - Photo ' + (currentImageIndex + 1);
    counter.textContent = (currentImageIndex + 1) + ' / ' + galleryImages.length;
}

// Keyboard navigation
document.addEventListener('keydown', (e) => {
    const lightbox = document.getElementById('gallery-lightbox');
    if (!lightbox || !lightbox.classList.contains('open')) return;

    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') navigateLightbox(-1);
    if (e.key === 'ArrowRight') navigateLightbox(1);
});

// Touch swipe support
let touchStartX = 0;
let touchEndX = 0;

document.getElementById('gallery-lightbox')?.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].screenX;
}, { passive: true });

document.getElementById('gallery-lightbox')?.addEventListener('touchend', (e) => {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
}, { passive: true });

function handleSwipe() {
    const swipeThreshold = 50;
    const diff = touchStartX - touchEndX;
    if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0) navigateLightbox(1); // Swipe left = next
        else navigateLightbox(-1); // Swipe right = prev
    }
}
</script>
<?php endif; ?>

<?php include __DIR__ . '/components/footer.php'; ?>
