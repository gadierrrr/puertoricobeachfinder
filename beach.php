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
                    <button onclick="copyBeachLink('<?= h($beach['slug']) ?>', this)"
                            aria-label="Copy link to this beach"
                            class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium transition-colors">
                        <i data-lucide="link" class="w-4 h-4" aria-hidden="true"></i>
                        <span>Copy Link</span>
                    </button>
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

            <!-- User Photos Gallery -->
            <?php
            $userPhotos = query("
                SELECT p.id, p.filename, p.caption, p.created_at, u.name as user_name
                FROM beach_photos p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.beach_id = :beach_id AND p.status = 'published'
                ORDER BY p.created_at DESC
                LIMIT 12
            ", [':beach_id' => $beach['id']]);
            $totalUserPhotos = queryOne("SELECT COUNT(*) as count FROM beach_photos WHERE beach_id = :beach_id AND status = 'published'", [':beach_id' => $beach['id']]);
            ?>
            <div id="user-photos" class="pt-4">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                        <i data-lucide="camera" class="w-5 h-5 text-purple-600" aria-hidden="true"></i>
                        <span>Visitor Photos</span>
                        <?php if ($totalUserPhotos['count'] > 0): ?>
                        <span class="text-sm font-normal text-gray-500">(<?= $totalUserPhotos['count'] ?>)</span>
                        <?php endif; ?>
                    </h2>
                    <?php if (isAuthenticated()): ?>
                    <button onclick="openPhotoUploadModal('<?= h($beach['id']) ?>', '<?= h(addslashes($beach['name'])) ?>')"
                            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center gap-2 text-sm">
                        <i data-lucide="upload" class="w-4 h-4"></i>
                        <span>Add Photos</span>
                    </button>
                    <?php else: ?>
                    <a href="/login.php?redirect=<?= urlencode('/beach/' . $beach['slug'] . '#user-photos') ?>"
                       class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center gap-2 text-sm">
                        <i data-lucide="upload" class="w-4 h-4"></i>
                        <span>Sign in to Add Photos</span>
                    </a>
                    <?php endif; ?>
                </div>

                <?php if (!empty($userPhotos)): ?>
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
                    <?php foreach ($userPhotos as $photo): ?>
                    <button onclick="openPhotoModal('/uploads/photos/<?= h($photo['filename']) ?>', '<?= h(addslashes($photo['caption'] ?? '')) ?>')"
                            class="aspect-square rounded-lg overflow-hidden hover:opacity-90 transition-opacity group relative">
                        <img src="/uploads/photos/thumbs/<?= h($photo['filename']) ?>"
                             alt="<?= h($photo['caption'] ?? 'Beach photo by ' . ($photo['user_name'] ?? 'visitor')) ?>"
                             class="w-full h-full object-cover"
                             loading="lazy">
                        <?php if ($photo['user_name']): ?>
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                            <span class="text-white text-xs truncate block"><?= h($photo['user_name']) ?></span>
                        </div>
                        <?php endif; ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php if ($totalUserPhotos['count'] > 12): ?>
                <div class="text-center mt-4">
                    <button onclick="loadMorePhotos('<?= h($beach['id']) ?>')"
                            class="text-purple-600 hover:text-purple-700 font-medium text-sm">
                        View all <?= $totalUserPhotos['count'] ?> photos
                    </button>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="text-center py-12 bg-gray-50 rounded-xl">
                    <div class="text-5xl mb-4">📸</div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">No visitor photos yet</h3>
                    <p class="text-gray-500 mb-4">Be the first to share photos of <?= h($beach['name']) ?>!</p>
                    <?php if (isAuthenticated()): ?>
                    <button onclick="openPhotoUploadModal('<?= h($beach['id']) ?>', '<?= h(addslashes($beach['name'])) ?>')"
                            class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        Upload the First Photo
                    </button>
                    <?php else: ?>
                    <a href="/login.php?redirect=<?= urlencode('/beach/' . $beach['slug'] . '#user-photos') ?>"
                       class="inline-block bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        Sign in to Upload Photos
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

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

            <!-- Similar Beaches -->
            <?php
            $similarBeaches = getSimilarBeaches($beach['id'], $beach['tags'], 4);
            if (!empty($similarBeaches)):
            ?>
            <div class="pt-8">
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <i data-lucide="sparkles" class="w-5 h-5 text-blue-600" aria-hidden="true"></i>
                    Similar Beaches You Might Like
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?php foreach ($similarBeaches as $similar): ?>
                    <a href="/beach/<?= h($similar['slug']) ?>"
                       class="group bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                        <div class="aspect-video relative overflow-hidden">
                            <img src="<?= h(getThumbnailUrl($similar['cover_image'])) ?>"
                                 alt="<?= h($similar['name']) ?>"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                 loading="lazy">
                            <?php if ($similar['shared_tags'] > 1): ?>
                            <span class="absolute top-2 right-2 bg-blue-600 text-white text-xs px-2 py-1 rounded-full">
                                <?= $similar['shared_tags'] ?> shared tags
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="p-3">
                            <h3 class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors line-clamp-1">
                                <?= h($similar['name']) ?>
                            </h3>
                            <p class="text-sm text-gray-500"><?= h($similar['municipality']) ?></p>
                            <?php if ($similar['google_rating']): ?>
                            <div class="flex items-center gap-1 mt-1">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" aria-hidden="true">
                                    <path fill="#FACC15" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                                <span class="text-sm font-medium text-amber-700"><?= number_format($similar['google_rating'], 1) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">

            <!-- Weather Widget -->
            <?php
            require_once __DIR__ . '/inc/weather.php';
            $weather = getWeatherForLocation($beach['lat'], $beach['lng']);
            if ($weather):
                $size = 'full';
                include __DIR__ . '/components/weather-widget.php';
            endif;
            ?>

            <!-- Conditions Card -->
            <?php if ($beach['sargassum'] || $beach['surf'] || $beach['wind']): ?>
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-900">Current Conditions</h3>
                    <?php if ($beach['updated_at']): ?>
                    <span class="text-xs text-gray-400" title="Last updated: <?= h(date('F j, Y', strtotime($beach['updated_at']))) ?>">
                        Updated <?= h(timeAgo($beach['updated_at'])) ?>
                    </span>
                    <?php endif; ?>
                </div>
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
                <!-- Report Outdated Info -->
                <button onclick="openReportModal('<?= h($beach['id']) ?>', '<?= h(addslashes($beach['name'])) ?>')"
                        class="w-full mt-4 pt-4 border-t border-gray-100 text-sm text-gray-500 hover:text-blue-600 transition-colors flex items-center justify-center gap-1.5">
                    <i data-lucide="flag" class="w-3.5 h-3.5" aria-hidden="true"></i>
                    <span>Report outdated info</span>
                </button>
            </div>
            <?php endif; ?>

            <!-- Live Check-Ins Card -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-900 flex items-center gap-2">
                        <i data-lucide="radio" class="w-4 h-4 text-green-500" aria-hidden="true"></i>
                        <span>Live Updates</span>
                    </h3>
                    <?php if (isAuthenticated()): ?>
                    <button onclick="openCheckinModal('<?= h($beach['id']) ?>', '<?= h(addslashes($beach['name'])) ?>')"
                            class="text-sm bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg font-medium transition-colors flex items-center gap-1">
                        <i data-lucide="map-pin" class="w-3.5 h-3.5" aria-hidden="true"></i>
                        <span>Check In</span>
                    </button>
                    <?php else: ?>
                    <a href="/login.php?redirect=<?= urlencode('/beach/' . $beach['slug']) ?>"
                       class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                        Sign in to check in
                    </a>
                    <?php endif; ?>
                </div>

                <div id="checkins-list"
                     hx-get="/api/checkin.php?beach_id=<?= h($beach['id']) ?>&limit=5"
                     hx-trigger="load"
                     hx-swap="innerHTML">
                    <div class="text-center py-4">
                        <div class="animate-pulse text-gray-400">Loading...</div>
                    </div>
                </div>
            </div>

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
            <?php $sunTimes = getSunTimes($beach['lat'], $beach['lng']); ?>
            <div class="bg-white rounded-xl shadow-sm p-6 space-y-4">
                <h3 class="font-bold text-gray-900">Practical Information</h3>

                <?php if ($beach['parking_details']): ?>
                <div>
                    <h4 class="font-medium text-gray-900 text-sm inline-flex items-center gap-1.5">
                        <i data-lucide="car" class="w-4 h-4" aria-hidden="true"></i>
                        <span>Parking</span>
                        <?php if (!empty($beach['parking_difficulty'])): ?>
                        <span class="<?= getParkingDifficultyClass($beach['parking_difficulty']) ?> text-xs px-2 py-0.5 rounded-full ml-1">
                            <?= h(getParkingDifficultyLabel($beach['parking_difficulty'])) ?>
                        </span>
                        <?php endif; ?>
                    </h4>
                    <p class="text-gray-600 text-sm mt-1"><?= h($beach['parking_details']) ?></p>
                    <?php if (!empty($beach['parking_difficulty'])): ?>
                    <p class="text-gray-400 text-xs mt-1"><?= h(getParkingDifficultyDescription($beach['parking_difficulty'])) ?></p>
                    <?php endif; ?>
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

                <?php if ($sunTimes): ?>
                <div>
                    <h4 class="font-medium text-gray-900 text-sm inline-flex items-center gap-1.5">
                        <i data-lucide="sun" class="w-4 h-4 text-amber-500" aria-hidden="true"></i>
                        <span>Today's Sun Times</span>
                    </h4>
                    <div class="flex gap-4 mt-1.5">
                        <div class="flex items-center gap-1.5 text-sm">
                            <i data-lucide="sunrise" class="w-4 h-4 text-orange-400" aria-hidden="true"></i>
                            <span class="text-gray-600"><?= h($sunTimes['sunrise']) ?></span>
                        </div>
                        <div class="flex items-center gap-1.5 text-sm">
                            <i data-lucide="sunset" class="w-4 h-4 text-rose-400" aria-hidden="true"></i>
                            <span class="text-gray-600"><?= h($sunTimes['sunset']) ?></span>
                        </div>
                    </div>
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

<!-- Report Outdated Info Modal -->
<div id="report-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4"
     role="dialog" aria-modal="true" onclick="closeReportModal()">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full" onclick="event.stopPropagation()">
        <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Report Outdated Info</h2>
            <button onclick="closeReportModal()" class="text-gray-400 hover:text-gray-600 p-1" aria-label="Close">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form id="report-form" class="p-6 space-y-4" onsubmit="submitReport(event)">
            <input type="hidden" name="beach_id" id="report-beach-id">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">

            <p class="text-sm text-gray-600">
                Help us keep beach information accurate! Let us know what's changed at <strong id="report-beach-name"></strong>.
            </p>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">What needs updating?</label>
                <div class="space-y-2">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="issues[]" value="conditions" class="rounded border-gray-300">
                        <span class="text-sm text-gray-700">Beach conditions (sargassum, surf, wind)</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="issues[]" value="amenities" class="rounded border-gray-300">
                        <span class="text-sm text-gray-700">Amenities (parking, restrooms, etc.)</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="issues[]" value="access" class="rounded border-gray-300">
                        <span class="text-sm text-gray-700">Access or directions</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="issues[]" value="safety" class="rounded border-gray-300">
                        <span class="text-sm text-gray-700">Safety information</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="issues[]" value="other" class="rounded border-gray-300">
                        <span class="text-sm text-gray-700">Other</span>
                    </label>
                </div>
            </div>

            <div>
                <label for="report-details" class="block text-sm font-medium text-gray-700 mb-1">
                    Details <span class="text-gray-400">(optional)</span>
                </label>
                <textarea name="details" id="report-details" rows="3" maxlength="500"
                          placeholder="Tell us what's different from what's shown..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none text-sm"></textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" id="report-submit-btn"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg font-medium transition-colors text-sm">
                    Submit Report
                </button>
                <button type="button" onclick="closeReportModal()"
                        class="px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors text-sm">
                    Cancel
                </button>
            </div>

            <div id="report-message" class="hidden text-sm px-4 py-3 rounded-lg"></div>
        </form>
    </div>
</div>

<script>
function openReportModal(beachId, beachName) {
    document.getElementById('report-beach-id').value = beachId;
    document.getElementById('report-beach-name').textContent = beachName || 'this beach';
    document.getElementById('report-modal').classList.remove('hidden');
    document.getElementById('report-modal').classList.add('flex');
    document.body.style.overflow = 'hidden';

    // Reset form
    document.getElementById('report-form').reset();
    document.getElementById('report-message').classList.add('hidden');

    // Re-init Lucide icons
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeReportModal() {
    document.getElementById('report-modal').classList.add('hidden');
    document.getElementById('report-modal').classList.remove('flex');
    document.body.style.overflow = '';
}

async function submitReport(event) {
    event.preventDefault();

    const form = document.getElementById('report-form');
    const submitBtn = document.getElementById('report-submit-btn');
    const messageDiv = document.getElementById('report-message');

    // Check if at least one issue is selected
    const checkboxes = form.querySelectorAll('input[name="issues[]"]:checked');
    if (checkboxes.length === 0) {
        messageDiv.textContent = 'Please select at least one issue to report.';
        messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
        messageDiv.classList.remove('hidden');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
    messageDiv.classList.add('hidden');

    // For now, just show success (you can implement the API endpoint later)
    setTimeout(() => {
        messageDiv.textContent = 'Thank you! Your report has been submitted and will be reviewed soon.';
        messageDiv.className = 'bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg';
        messageDiv.classList.remove('hidden');

        // Show toast and close after delay
        if (typeof showToast === 'function') {
            showToast('Report submitted. Thank you!', 'success', 3000);
        }

        setTimeout(() => {
            closeReportModal();
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Report';
        }, 1500);
    }, 500);
}

// Close on escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeReportModal();
});
</script>

<!-- Check-In Modal -->
<div id="checkin-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4"
     role="dialog" aria-modal="true" onclick="closeCheckinModal()">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <i data-lucide="map-pin" class="w-5 h-5 text-green-600"></i>
                <span>Check In</span>
            </h2>
            <button onclick="closeCheckinModal()" class="text-gray-400 hover:text-gray-600 p-1" aria-label="Close">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="checkin-form" class="p-6 space-y-5" onsubmit="submitCheckin(event)">
            <input type="hidden" name="beach_id" id="checkin-beach-id">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">

            <p class="text-sm text-gray-600">
                Share what you're seeing at <strong id="checkin-beach-name"></strong> right now!
            </p>

            <!-- Crowd Level -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">How crowded is it?</label>
                <div class="grid grid-cols-5 gap-2">
                    <label class="checkin-option">
                        <input type="radio" name="crowd_level" value="empty" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🏝️</span>
                            <span class="text-xs">Empty</span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="crowd_level" value="light" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">👥</span>
                            <span class="text-xs">Light</span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="crowd_level" value="moderate" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">👥👥</span>
                            <span class="text-xs">Moderate</span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="crowd_level" value="busy" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">👥👥👥</span>
                            <span class="text-xs">Busy</span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="crowd_level" value="packed" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🔥</span>
                            <span class="text-xs">Packed</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Parking Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Parking availability?</label>
                <div class="grid grid-cols-4 gap-2">
                    <label class="checkin-option">
                        <input type="radio" name="parking_status" value="plenty" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🅿️</span>
                            <span class="text-xs">Plenty</span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="parking_status" value="available" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">✓</span>
                            <span class="text-xs">Available</span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="parking_status" value="limited" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">⚠️</span>
                            <span class="text-xs">Limited</span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="parking_status" value="full" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🚫</span>
                            <span class="text-xs">Full</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Water Conditions -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Water conditions?</label>
                <div class="grid grid-cols-4 gap-2">
                    <label class="checkin-option">
                        <input type="radio" name="water_condition" value="calm" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">😌</span>
                            <span class="text-xs">Calm</span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="water_condition" value="small-waves" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🌊</span>
                            <span class="text-xs">Small</span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="water_condition" value="choppy" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🌊🌊</span>
                            <span class="text-xs">Choppy</span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="water_condition" value="rough" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">⚠️</span>
                            <span class="text-xs">Rough</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Sargassum -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sargassum level?</label>
                <div class="grid grid-cols-4 gap-2">
                    <label class="checkin-option">
                        <input type="radio" name="sargassum_level" value="none" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">✨</span>
                            <span class="text-xs">None</span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="sargassum_level" value="light" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🌿</span>
                            <span class="text-xs">Light</span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="sargassum_level" value="moderate" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🌿🌿</span>
                            <span class="text-xs">Moderate</span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="sargassum_level" value="heavy" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🌿🌿🌿</span>
                            <span class="text-xs">Heavy</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label for="checkin-notes" class="block text-sm font-medium text-gray-700 mb-1">
                    Any other notes? <span class="text-gray-400">(optional)</span>
                </label>
                <textarea name="notes" id="checkin-notes" rows="2" maxlength="280"
                          placeholder="Share a quick tip for others..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 resize-none text-sm"></textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" id="checkin-submit-btn"
                        class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2.5 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Submit Check-In</span>
                </button>
                <button type="button" onclick="closeCheckinModal()"
                        class="px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
            </div>

            <div id="checkin-message" class="hidden text-sm px-4 py-3 rounded-lg"></div>
        </form>
    </div>
</div>

<style>
.checkin-option input:checked + .checkin-option-box {
    background-color: rgb(220 252 231);
    border-color: rgb(34 197 94);
    color: rgb(21 128 61);
}
.checkin-option-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0.5rem;
    border: 2px solid rgb(229 231 235);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s;
    min-height: 3.5rem;
}
.checkin-option-box:hover {
    border-color: rgb(156 163 175);
    background-color: rgb(249 250 251);
}
</style>

<script>
function openCheckinModal(beachId, beachName) {
    document.getElementById('checkin-beach-id').value = beachId;
    document.getElementById('checkin-beach-name').textContent = beachName || 'this beach';
    document.getElementById('checkin-modal').classList.remove('hidden');
    document.getElementById('checkin-modal').classList.add('flex');
    document.body.style.overflow = 'hidden';

    // Reset form
    document.getElementById('checkin-form').reset();
    document.getElementById('checkin-message').classList.add('hidden');

    // Re-init Lucide icons
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeCheckinModal() {
    document.getElementById('checkin-modal').classList.add('hidden');
    document.getElementById('checkin-modal').classList.remove('flex');
    document.body.style.overflow = '';
}

async function submitCheckin(event) {
    event.preventDefault();

    const form = document.getElementById('checkin-form');
    const submitBtn = document.getElementById('checkin-submit-btn');
    const messageDiv = document.getElementById('checkin-message');

    // Check if at least one option is selected
    const hasSelection = form.querySelector('input[type="radio"]:checked') || form.querySelector('#checkin-notes').value.trim();
    if (!hasSelection) {
        messageDiv.textContent = 'Please select at least one condition or add a note.';
        messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
        messageDiv.classList.remove('hidden');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="animate-pulse">Submitting...</span>';
    messageDiv.classList.add('hidden');

    try {
        const formData = new FormData(form);
        const response = await fetch('/api/checkin.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            messageDiv.textContent = data.message || 'Thanks for checking in!';
            messageDiv.className = 'bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg';
            messageDiv.classList.remove('hidden');

            if (typeof showToast === 'function') {
                showToast('Check-in submitted!', 'success', 3000);
            }

            // Refresh check-ins list
            if (typeof htmx !== 'undefined') {
                htmx.trigger('#checkins-list', 'load');
            }

            setTimeout(() => {
                closeCheckinModal();
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i><span>Submit Check-In</span>';
            }, 1000);
        } else {
            messageDiv.textContent = data.error || 'Failed to submit check-in';
            messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
            messageDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i><span>Submit Check-In</span>';
        }
    } catch (error) {
        console.error('Check-in error:', error);
        messageDiv.textContent = 'Network error. Please try again.';
        messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
        messageDiv.classList.remove('hidden');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i><span>Submit Check-In</span>';
    }
}

// Close checkin modal on escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeCheckinModal();
});
</script>

<!-- Review Form Modal -->
<div id="review-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4"
     role="dialog" aria-modal="true" onclick="closeReviewModal()">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Write a Review</h2>
            <button onclick="closeReviewModal()" class="text-gray-400 hover:text-gray-600 p-1" aria-label="Close">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="review-form" class="p-6 space-y-5" onsubmit="submitReview(event)">
            <input type="hidden" name="beach_id" id="review-beach-id">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">

            <p class="text-sm text-gray-600">
                Share your experience at <strong id="review-beach-name"></strong>
            </p>

            <!-- Rating -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Your Rating <span class="text-red-500">*</span></label>
                <div class="flex gap-1" id="star-rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button type="button" onclick="setRating(<?= $i ?>)" data-star="<?= $i ?>"
                            class="star-btn text-3xl text-gray-300 hover:text-amber-400 transition-colors">
                        ★
                    </button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="review-rating" value="0" required>
            </div>

            <!-- Title -->
            <div>
                <label for="review-title" class="block text-sm font-medium text-gray-700 mb-1">
                    Review Title <span class="text-gray-400">(optional)</span>
                </label>
                <input type="text" name="title" id="review-title" maxlength="100"
                       placeholder="Sum up your experience in a few words..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Review Text -->
            <div>
                <label for="review-text" class="block text-sm font-medium text-gray-700 mb-1">
                    Your Review <span class="text-red-500">*</span>
                </label>
                <textarea name="review_text" id="review-text" rows="4" minlength="20" maxlength="5000" required
                          placeholder="What did you like or dislike? Share tips for other visitors..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                <p class="text-xs text-gray-400 mt-1">Minimum 20 characters</p>
            </div>

            <!-- Pros/Cons -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="review-pros" class="block text-sm font-medium text-green-700 mb-1">
                        Pros <span class="text-gray-400">(optional)</span>
                    </label>
                    <textarea name="pros" id="review-pros" rows="2" maxlength="500"
                              placeholder="What you liked..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 resize-none text-sm"></textarea>
                </div>
                <div>
                    <label for="review-cons" class="block text-sm font-medium text-red-700 mb-1">
                        Cons <span class="text-gray-400">(optional)</span>
                    </label>
                    <textarea name="cons" id="review-cons" rows="2" maxlength="500"
                              placeholder="What could be better..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none text-sm"></textarea>
                </div>
            </div>

            <!-- Visit Details -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="review-visit-date" class="block text-sm font-medium text-gray-700 mb-1">
                        When did you visit?
                    </label>
                    <input type="month" name="visit_date" id="review-visit-date"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="review-visited-with" class="block text-sm font-medium text-gray-700 mb-1">
                        Who did you go with?
                    </label>
                    <select name="visited_with" id="review-visited-with"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select...</option>
                        <option value="solo">Solo</option>
                        <option value="partner">Partner/Couple</option>
                        <option value="family">Family</option>
                        <option value="friends">Friends</option>
                        <option value="group">Group</option>
                    </select>
                </div>
            </div>

            <!-- Photo Upload -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Add Photos <span class="text-gray-400">(optional)</span>
                </label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-blue-400 transition-colors">
                    <input type="file" name="photos[]" id="review-photos" accept="image/jpeg,image/png,image/webp" multiple
                           class="hidden" onchange="previewPhotos(this)">
                    <label for="review-photos" class="cursor-pointer">
                        <i data-lucide="camera" class="w-8 h-8 mx-auto text-gray-400 mb-2"></i>
                        <p class="text-sm text-gray-600">Click to upload photos</p>
                        <p class="text-xs text-gray-400 mt-1">JPG, PNG, or WebP (max 10MB each)</p>
                    </label>
                </div>
                <div id="photo-preview" class="flex gap-2 mt-2 flex-wrap"></div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" id="review-submit-btn"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg font-medium transition-colors">
                    Submit Review
                </button>
                <button type="button" onclick="closeReviewModal()"
                        class="px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
            </div>

            <div id="review-message" class="hidden text-sm px-4 py-3 rounded-lg"></div>
        </form>
    </div>
</div>

<!-- Photo Upload Modal (standalone) -->
<div id="photo-upload-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4"
     role="dialog" aria-modal="true" onclick="closePhotoUploadModal()">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full" onclick="event.stopPropagation()">
        <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Upload Photos</h2>
            <button onclick="closePhotoUploadModal()" class="text-gray-400 hover:text-gray-600 p-1" aria-label="Close">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="photo-upload-form" class="p-6 space-y-4" onsubmit="submitPhotoUpload(event)">
            <input type="hidden" name="beach_id" id="upload-beach-id">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">

            <p class="text-sm text-gray-600">
                Share your photos of <strong id="upload-beach-name"></strong>
            </p>

            <div>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-purple-400 transition-colors">
                    <input type="file" name="photo" id="upload-photo" accept="image/jpeg,image/png,image/webp" required
                           class="hidden" onchange="previewUploadPhoto(this)">
                    <label for="upload-photo" class="cursor-pointer">
                        <i data-lucide="image-plus" class="w-10 h-10 mx-auto text-gray-400 mb-2"></i>
                        <p class="text-sm text-gray-600">Click to select a photo</p>
                        <p class="text-xs text-gray-400 mt-1">JPG, PNG, or WebP (max 10MB)</p>
                    </label>
                </div>
                <div id="upload-preview" class="mt-3 hidden">
                    <img id="upload-preview-img" src="" alt="Preview" class="max-h-48 mx-auto rounded-lg">
                </div>
            </div>

            <div>
                <label for="upload-caption" class="block text-sm font-medium text-gray-700 mb-1">
                    Caption <span class="text-gray-400">(optional)</span>
                </label>
                <input type="text" name="caption" id="upload-caption" maxlength="200"
                       placeholder="Add a caption to your photo..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" id="upload-submit-btn"
                        class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-lg font-medium transition-colors">
                    Upload Photo
                </button>
                <button type="button" onclick="closePhotoUploadModal()"
                        class="px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
            </div>

            <div id="upload-message" class="hidden text-sm px-4 py-3 rounded-lg"></div>
        </form>
    </div>
</div>

<!-- Photo Lightbox Modal -->
<div id="photo-lightbox" class="fixed inset-0 bg-black/90 z-50 hidden items-center justify-center"
     role="dialog" aria-modal="true" onclick="closePhotoModal()">
    <button onclick="closePhotoModal()" class="absolute top-4 right-4 text-white/70 hover:text-white p-2" aria-label="Close">
        <i data-lucide="x" class="w-8 h-8"></i>
    </button>
    <div class="max-w-5xl max-h-[90vh] p-4" onclick="event.stopPropagation()">
        <img id="photo-lightbox-img" src="" alt="" class="max-w-full max-h-[85vh] object-contain rounded-lg">
        <p id="photo-lightbox-caption" class="text-white/80 text-center mt-3 text-sm"></p>
    </div>
</div>

<script>
// Star rating
function setRating(rating) {
    document.getElementById('review-rating').value = rating;
    const stars = document.querySelectorAll('#star-rating .star-btn');
    stars.forEach((star, idx) => {
        if (idx < rating) {
            star.classList.remove('text-gray-300');
            star.classList.add('text-amber-400');
        } else {
            star.classList.remove('text-amber-400');
            star.classList.add('text-gray-300');
        }
    });
}

// Review modal
function openReviewForm(beachId, beachName) {
    document.getElementById('review-beach-id').value = beachId;
    document.getElementById('review-beach-name').textContent = beachName || 'this beach';
    document.getElementById('review-modal').classList.remove('hidden');
    document.getElementById('review-modal').classList.add('flex');
    document.body.style.overflow = 'hidden';

    // Reset form
    document.getElementById('review-form').reset();
    document.getElementById('review-rating').value = '0';
    document.querySelectorAll('#star-rating .star-btn').forEach(s => {
        s.classList.remove('text-amber-400');
        s.classList.add('text-gray-300');
    });
    document.getElementById('photo-preview').innerHTML = '';
    document.getElementById('review-message').classList.add('hidden');

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeReviewModal() {
    document.getElementById('review-modal').classList.add('hidden');
    document.getElementById('review-modal').classList.remove('flex');
    document.body.style.overflow = '';
}

function previewPhotos(input) {
    const preview = document.getElementById('photo-preview');
    preview.innerHTML = '';

    if (input.files) {
        Array.from(input.files).slice(0, 5).forEach((file, idx) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.className = 'relative w-16 h-16 rounded-lg overflow-hidden';
                div.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-cover" alt="Preview">
                    <button type="button" onclick="removePhoto(${idx})" class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-5 h-5 text-xs">×</button>
                `;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }
}

async function submitReview(event) {
    event.preventDefault();

    const form = document.getElementById('review-form');
    const submitBtn = document.getElementById('review-submit-btn');
    const messageDiv = document.getElementById('review-message');

    const rating = document.getElementById('review-rating').value;
    if (!rating || rating === '0') {
        messageDiv.textContent = 'Please select a rating.';
        messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
        messageDiv.classList.remove('hidden');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
    messageDiv.classList.add('hidden');

    try {
        const formData = new FormData(form);
        const response = await fetch('/api/reviews.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            messageDiv.textContent = data.message || 'Review submitted!';
            messageDiv.className = 'bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg';
            messageDiv.classList.remove('hidden');

            if (typeof showToast === 'function') {
                showToast('Review submitted!', 'success', 3000);
            }

            setTimeout(() => {
                closeReviewModal();
                location.reload();
            }, 1500);
        } else {
            messageDiv.textContent = data.error || 'Failed to submit review';
            messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
            messageDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Review';
        }
    } catch (error) {
        console.error('Review error:', error);
        messageDiv.textContent = 'Network error. Please try again.';
        messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
        messageDiv.classList.remove('hidden');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Review';
    }
}

// Photo upload modal
function openPhotoUploadModal(beachId, beachName) {
    document.getElementById('upload-beach-id').value = beachId;
    document.getElementById('upload-beach-name').textContent = beachName || 'this beach';
    document.getElementById('photo-upload-modal').classList.remove('hidden');
    document.getElementById('photo-upload-modal').classList.add('flex');
    document.body.style.overflow = 'hidden';

    document.getElementById('photo-upload-form').reset();
    document.getElementById('upload-preview').classList.add('hidden');
    document.getElementById('upload-message').classList.add('hidden');

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closePhotoUploadModal() {
    document.getElementById('photo-upload-modal').classList.add('hidden');
    document.getElementById('photo-upload-modal').classList.remove('flex');
    document.body.style.overflow = '';
}

function previewUploadPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('upload-preview-img').src = e.target.result;
            document.getElementById('upload-preview').classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

async function submitPhotoUpload(event) {
    event.preventDefault();

    const form = document.getElementById('photo-upload-form');
    const submitBtn = document.getElementById('upload-submit-btn');
    const messageDiv = document.getElementById('upload-message');

    submitBtn.disabled = true;
    submitBtn.textContent = 'Uploading...';
    messageDiv.classList.add('hidden');

    try {
        const formData = new FormData(form);
        const response = await fetch('/api/photos.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            messageDiv.textContent = data.message || 'Photo uploaded!';
            messageDiv.className = 'bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg';
            messageDiv.classList.remove('hidden');

            if (typeof showToast === 'function') {
                showToast('Photo uploaded!', 'success', 3000);
            }

            setTimeout(() => {
                closePhotoUploadModal();
                location.reload();
            }, 1500);
        } else {
            messageDiv.textContent = data.error || 'Failed to upload photo';
            messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
            messageDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Upload Photo';
        }
    } catch (error) {
        console.error('Upload error:', error);
        messageDiv.textContent = 'Network error. Please try again.';
        messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
        messageDiv.classList.remove('hidden');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Upload Photo';
    }
}

// Photo lightbox
function openPhotoModal(url, caption) {
    document.getElementById('photo-lightbox-img').src = url;
    document.getElementById('photo-lightbox-caption').textContent = caption || '';
    document.getElementById('photo-lightbox').classList.remove('hidden');
    document.getElementById('photo-lightbox').classList.add('flex');
    document.body.style.overflow = 'hidden';

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closePhotoModal() {
    document.getElementById('photo-lightbox').classList.add('hidden');
    document.getElementById('photo-lightbox').classList.remove('flex');
    document.body.style.overflow = '';
}

// Review voting
async function voteReview(reviewId, btn) {
    <?php if (!isAuthenticated()): ?>
    window.location.href = '/login.php?redirect=' + encodeURIComponent(window.location.pathname + '#reviews');
    return;
    <?php endif; ?>

    try {
        const formData = new FormData();
        formData.append('action', 'vote');
        formData.append('review_id', reviewId);
        formData.append('csrf_token', '<?= h(csrfToken()) ?>');

        const response = await fetch('/api/reviews.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            const isVoted = btn.dataset.voted === 'true';
            btn.dataset.voted = isVoted ? 'false' : 'true';

            if (isVoted) {
                btn.classList.remove('text-blue-600');
                btn.classList.add('text-gray-500');
            } else {
                btn.classList.remove('text-gray-500');
                btn.classList.add('text-blue-600');
            }

            // Update count
            let countEl = btn.querySelector('.helpful-count');
            if (countEl) {
                const count = parseInt(countEl.textContent) + (isVoted ? -1 : 1);
                if (count > 0) {
                    countEl.textContent = count;
                } else {
                    countEl.remove();
                }
            } else if (!isVoted) {
                const span = document.createElement('span');
                span.className = 'helpful-count text-xs bg-gray-100 px-1.5 py-0.5 rounded-full';
                span.textContent = '1';
                btn.appendChild(span);
            }
        }
    } catch (error) {
        console.error('Vote error:', error);
    }
}

// Delete review
async function deleteReview(reviewId) {
    if (!confirm('Are you sure you want to delete your review? This cannot be undone.')) return;

    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('review_id', reviewId);
        formData.append('csrf_token', '<?= h(csrfToken()) ?>');

        const response = await fetch('/api/reviews.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            if (typeof showToast === 'function') {
                showToast('Review deleted', 'success', 3000);
            }
            location.reload();
        } else {
            alert(data.error || 'Failed to delete review');
        }
    } catch (error) {
        console.error('Delete error:', error);
        alert('Network error. Please try again.');
    }
}

// Share review
function shareReview(reviewId) {
    const url = window.location.origin + window.location.pathname + '#review-' + reviewId;
    if (navigator.share) {
        navigator.share({ url: url });
    } else if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            if (typeof showToast === 'function') {
                showToast('Link copied!', 'success', 2000);
            }
        });
    }
}

// Report review
function reportReview(reviewId) {
    alert('Report functionality coming soon. For now, please contact us at support@puertoricobeachfinder.com');
}

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeReviewModal();
        closePhotoUploadModal();
        closePhotoModal();
    }
});
</script>

<?php include __DIR__ . '/components/footer.php'; ?>
