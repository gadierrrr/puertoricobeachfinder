<?php
// inc/helpers.php - Utility functions
//
// IMPORTANT: This file should be included with require_once to prevent
// duplicate function declarations. All display label functions live here.

// Include guard to prevent duplicate declarations
if (defined('HELPERS_PHP_INCLUDED')) {
    return;
}
define('HELPERS_PHP_INCLUDED', true);

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function currentUser() {
    if (!isset($_SESSION['user_id'])) return null;
    require_once __DIR__ . '/db.php';
    return queryOne('SELECT * FROM users WHERE id = :id', [':id' => $_SESSION['user_id']]);
}

function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

function requireAuth() {
    if (!isAuthenticated()) {
        redirect("/login.php?redirect=" . urlencode($_SERVER["REQUEST_URI"]));
    }

    // Check session timeout (30 minutes of inactivity)
    if (isset($_SESSION["LAST_ACTIVITY"]) && (time() - $_SESSION["LAST_ACTIVITY"] > 1800)) {
        session_unset();
        session_destroy();
        redirect("/login.php?timeout=1&redirect=" . urlencode($_SERVER["REQUEST_URI"]));
    }

    // Validate session fingerprint (prevent session hijacking)
    $currentFingerprint = hash("sha256", ($_SERVER["REMOTE_ADDR"] ?? "") . ($_SERVER["HTTP_USER_AGENT"] ?? ""));
    if (isset($_SESSION["SESSION_FINGERPRINT"]) && $_SESSION["SESSION_FINGERPRINT"] !== $currentFingerprint) {
        session_unset();
        session_destroy();
        redirect("/login.php?error=session_invalid");
    }

    // Update last activity time
    $_SESSION["LAST_ACTIVITY"] = time();
}

function slugify($str) {
    $str = strtolower(trim($str));
    $str = preg_replace('/[^a-z0-9-]/', '-', $str);
    return trim(preg_replace('/-+/', '-', $str), '-');
}

function isHtmx() {
    return isset($_SERVER['HTTP_HX_REQUEST']);
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function csrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken(32);
    }
    return $_SESSION['csrf_token'];
}

function validateCsrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">';
}

// Beach-specific helpers

function getBeachUrl($beach) {
    return '/beach/' . h($beach['slug']);
}

function getBeachDetailUrl($beach) {
    return '/beach.php?slug=' . urlencode($beach['slug']);
}

function getDirectionsUrl($beach) {
    $lat = $beach['lat'];
    $lng = $beach['lng'];
    $name = urlencode($beach['name']);
    return "https://www.google.com/maps/dir/?api=1&destination={$lat},{$lng}&destination_place_id={$name}";
}

function getShareText($beach) {
    return "Check out " . $beach['name'] . " in " . $beach['municipality'] . ", Puerto Rico!";
}

function getConditionClass($value, $type) {
    if (!$value) return 'bg-gray-100 text-gray-600';

    $classes = [
        'sargassum' => [
            'none' => 'bg-green-100 text-green-700',
            'light' => 'bg-yellow-100 text-yellow-700',
            'moderate' => 'bg-orange-100 text-orange-700',
            'heavy' => 'bg-red-100 text-red-700',
        ],
        'surf' => [
            'calm' => 'bg-blue-100 text-blue-700',
            'small' => 'bg-cyan-100 text-cyan-700',
            'medium' => 'bg-indigo-100 text-indigo-700',
            'large' => 'bg-purple-100 text-purple-700',
        ],
        'wind' => [
            'calm' => 'bg-teal-100 text-teal-700',
            'light' => 'bg-emerald-100 text-emerald-700',
            'moderate' => 'bg-amber-100 text-amber-700',
            'strong' => 'bg-rose-100 text-rose-700',
        ],
    ];

    return $classes[$type][$value] ?? 'bg-gray-100 text-gray-600';
}

function formatDistance($meters) {
    if ($meters < 1000) {
        return round($meters) . 'm';
    }
    return round($meters / 1000, 1) . 'km';
}

function timeAgo($datetime) {
    if (!$datetime) return 'Never';

    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}

function getThumbnailUrl($imagePath) {
    if (empty($imagePath)) {
        return '/assets/images/placeholder.jpg';
    }

    // Extract filename without extension
    $pathInfo = pathinfo($imagePath);
    $baseName = $pathInfo['filename'];

    // Build thumbnail path
    $thumbnailPath = '/images/thumbnails/' . $baseName . '.webp';
    $thumbnailFile = $_SERVER['DOCUMENT_ROOT'] . $thumbnailPath;

    // Return thumbnail if it exists, otherwise original
    if (file_exists($thumbnailFile)) {
        return $thumbnailPath;
    }

    return $imagePath;
}

// ========================================
// Score Badge Helpers (Nomads-style)
// ========================================

/**
 * Get descriptive label for a rating score
 */
function getScoreLabel($rating) {
    if ($rating === null) return '';
    if ($rating >= 4.8) return 'Exceptional';
    if ($rating >= 4.5) return 'Excellent';
    if ($rating >= 4.0) return 'Very Good';
    if ($rating >= 3.5) return 'Good';
    if ($rating >= 3.0) return 'Average';
    return 'Below Avg';
}

/**
 * Get CSS class for score badge based on rating
 */
function getScoreBadgeClass($rating) {
    if ($rating === null) return 'score-average';
    if ($rating >= 4.5) return 'score-exceptional';
    if ($rating >= 4.0) return 'score-excellent';
    if ($rating >= 3.5) return 'score-good';
    return 'score-average';
}

/**
 * Format distance for display with smart units
 */
function formatDistanceDisplay($meters) {
    if ($meters === null) return null;
    if ($meters < 1000) {
        return round($meters) . 'm';
    } elseif ($meters < 10000) {
        return number_format($meters / 1000, 1) . 'km';
    }
    return round($meters / 1000) . 'km';
}

// ========================================
// Conditions Meter Helpers
// ========================================

/**
 * Get percentage for surf condition meter
 */
function getSurfPercentage($surf) {
    $map = ['calm' => 20, 'small' => 40, 'medium' => 70, 'large' => 100];
    return $map[$surf] ?? 0;
}

/**
 * Get percentage for sargassum condition meter
 */
function getSargassumPercentage($sargassum) {
    $map = ['none' => 5, 'light' => 33, 'moderate' => 66, 'heavy' => 100];
    return $map[$sargassum] ?? 0;
}

/**
 * Get percentage for wind condition meter
 */
function getWindPercentage($wind) {
    $map = ['calm' => 20, 'light' => 40, 'moderate' => 70, 'strong' => 100];
    return $map[$wind] ?? 0;
}

/**
 * Get human-readable label for condition value
 */
function getConditionLabel($type, $value) {
    if (!$value) return 'Unknown';
    return ucfirst($value);
}

// ========================================
// Tag Helpers
// ========================================

/**
 * Get emoji for a tag
 */
function getTagEmoji($tag) {
    $emojis = [
        'calm-waters' => '🌊',
        'surfing' => '🏄',
        'snorkeling' => '🤿',
        'family-friendly' => '👨‍👩‍👧‍👦',
        'accessible' => '♿',
        'secluded' => '🏝️',
        'popular' => '⭐',
        'scenic' => '📸',
        'swimming' => '🏊',
        'diving' => '🐠',
        'fishing' => '🎣',
        'camping' => '⛺',
    ];
    return $emojis[$tag] ?? '🏖️';
}

/**
 * Get display label for a tag
 */
function getTagLabel($tag) {
    $labels = [
        'calm-waters' => 'Calm Waters',
        'surfing' => 'Surfing',
        'snorkeling' => 'Snorkeling',
        'family-friendly' => 'Family Friendly',
        'accessible' => 'Accessible',
        'secluded' => 'Secluded',
        'popular' => 'Popular',
        'scenic' => 'Scenic',
        'swimming' => 'Swimming',
        'diving' => 'Diving',
        'fishing' => 'Fishing',
        'camping' => 'Camping',
    ];
    return $labels[$tag] ?? ucwords(str_replace('-', ' ', $tag));
}

/**
 * Get display label for an amenity
 */
function getAmenityLabel($amenity) {
    $labels = [
        'restrooms' => 'Restrooms',
        'showers' => 'Showers',
        'lifeguard' => 'Lifeguard',
        'parking' => 'Parking',
        'food' => 'Food & Drinks',
        'equipment-rental' => 'Equipment Rental',
        'accessibility' => 'Wheelchair Accessible',
        'picnic-areas' => 'Picnic Areas',
        'shade-structures' => 'Shade/Umbrellas',
        'water-sports' => 'Water Sports',
    ];
    return $labels[$amenity] ?? ucwords(str_replace('-', ' ', $amenity));
}

// ========================================
// Beach Badge Helpers
// ========================================

/**
 * Get auto-generated badges for a beach based on its attributes
 */
function getBeachBadges($beach) {
    $badges = [];
    $tags = $beach['tags'] ?? [];
    $rating = $beach['google_rating'] ?? 0;
    $reviewCount = $beach['google_review_count'] ?? 0;

    // Top Rated: High rating with many reviews
    if ($rating >= 4.7 && $reviewCount >= 50) {
        $badges[] = 'top-rated';
    }

    // Family Pick: Family-friendly + calm waters
    if (in_array('family-friendly', $tags) && in_array('calm-waters', $tags)) {
        $badges[] = 'family-pick';
    }

    // Hidden Gem: Secluded + high rating + low review count
    if (in_array('secluded', $tags) && $rating >= 4.5 && $reviewCount < 100) {
        $badges[] = 'hidden-gem';
    }

    // Surfer's Favorite: Has surfing tag
    if (in_array('surfing', $tags) && $rating >= 4.0) {
        $badges[] = 'surfer-fave';
    }

    // Instagram Worthy: Scenic with good rating
    if (in_array('scenic', $tags) && $rating >= 4.3) {
        $badges[] = 'instagram-worthy';
    }

    // Return max 2 badges
    return array_slice($badges, 0, 2);
}

/**
 * Get badge display info
 */
function getBadgeInfo($badgeKey) {
    $badges = [
        'top-rated' => ['emoji' => '🏆', 'label' => 'Top Rated', 'color' => 'gold'],
        'family-pick' => ['emoji' => '👨‍👩‍👧', 'label' => 'Family Pick', 'color' => 'purple'],
        'hidden-gem' => ['emoji' => '💎', 'label' => 'Hidden Gem', 'color' => 'blue'],
        'surfer-fave' => ['emoji' => '🏄', 'label' => "Surfer's Fave", 'color' => 'cyan'],
        'instagram-worthy' => ['emoji' => '📸', 'label' => 'Insta-Worthy', 'color' => 'pink'],
        'local-secret' => ['emoji' => '🤫', 'label' => 'Local Secret', 'color' => 'green'],
    ];
    return $badges[$badgeKey] ?? null;
}

/**
 * Render a beach badge as HTML
 */
function renderBeachBadge($badgeKey) {
    $badge = getBadgeInfo($badgeKey);
    if (!$badge) return '';

    $colorClass = 'beach-badge-' . $badge['color'];

    return '<span class="beach-badge ' . $colorClass . '">'
         . '<span>' . $badge['emoji'] . '</span>'
         . '<span>' . h($badge['label']) . '</span>'
         . '</span>';
}

// ========================================
// Parking Difficulty Helpers
// ========================================

/**
 * Get parking difficulty label
 */
function getParkingDifficultyLabel($difficulty) {
    $labels = [
        'easy' => 'Easy Parking',
        'moderate' => 'Moderate',
        'difficult' => 'Difficult',
        'very-difficult' => 'Very Difficult'
    ];
    return $labels[$difficulty] ?? ucwords(str_replace('-', ' ', $difficulty ?? ''));
}

/**
 * Get parking difficulty description
 */
function getParkingDifficultyDescription($difficulty) {
    $descriptions = [
        'easy' => 'Plenty of parking available, rarely fills up',
        'moderate' => 'Usually find parking, may fill on weekends',
        'difficult' => 'Limited spots, arrive early on busy days',
        'very-difficult' => 'Very limited parking, consider alternate transport'
    ];
    return $descriptions[$difficulty] ?? '';
}

/**
 * Get parking difficulty CSS classes
 */
function getParkingDifficultyClass($difficulty) {
    $classes = [
        'easy' => 'bg-green-100 text-green-700',
        'moderate' => 'bg-yellow-100 text-yellow-700',
        'difficult' => 'bg-orange-100 text-orange-700',
        'very-difficult' => 'bg-red-100 text-red-700'
    ];
    return $classes[$difficulty] ?? 'bg-gray-100 text-gray-600';
}

/**
 * Get parking difficulty icon
 */
function getParkingDifficultyIcon($difficulty) {
    $icons = [
        'easy' => 'circle-check',
        'moderate' => 'circle-minus',
        'difficult' => 'circle-alert',
        'very-difficult' => 'circle-x'
    ];
    return $icons[$difficulty] ?? 'car';
}

// ========================================
// Sunrise/Sunset Helpers
// ========================================

/**
 * Calculate sunrise and sunset times for a given location and date
 * Uses standard solar calculations
 */
function getSunTimes($lat, $lng, $date = null) {
    $date = $date ?? date('Y-m-d');
    $timestamp = strtotime($date);

    // Puerto Rico timezone (AST - Atlantic Standard Time, no DST)
    $timezone = new DateTimeZone('America/Puerto_Rico');

    $sunrise = date_sunrise($timestamp, SUNFUNCS_RET_TIMESTAMP, $lat, $lng, 90.833333, -4);
    $sunset = date_sunset($timestamp, SUNFUNCS_RET_TIMESTAMP, $lat, $lng, 90.833333, -4);

    if ($sunrise === false || $sunset === false) {
        return null;
    }

    $sunriseDateTime = new DateTime('@' . $sunrise);
    $sunriseDateTime->setTimezone($timezone);

    $sunsetDateTime = new DateTime('@' . $sunset);
    $sunsetDateTime->setTimezone($timezone);

    return [
        'sunrise' => $sunriseDateTime->format('g:i A'),
        'sunset' => $sunsetDateTime->format('g:i A'),
        'sunrise_timestamp' => $sunrise,
        'sunset_timestamp' => $sunset
    ];
}

/**
 * Get "Best For" summary based on beach tags
 */
function getBestForSummary($tags, $maxItems = 3) {
    if (empty($tags)) return [];

    // Priority order for "Best For" display
    $priority = [
        'family-friendly' => 'Families',
        'surfing' => 'Surfing',
        'snorkeling' => 'Snorkeling',
        'diving' => 'Diving',
        'swimming' => 'Swimming',
        'calm-waters' => 'Relaxing',
        'scenic' => 'Photography',
        'secluded' => 'Quiet Escape',
        'fishing' => 'Fishing',
        'camping' => 'Camping'
    ];

    $bestFor = [];
    foreach ($priority as $tag => $label) {
        if (in_array($tag, $tags)) {
            $bestFor[] = $label;
            if (count($bestFor) >= $maxItems) break;
        }
    }

    return $bestFor;
}

// ========================================
// Similar Beaches Helper
// ========================================

/**
 * Find beaches similar to a given beach based on shared tags
 * @param string $beachId The beach to find similar beaches for
 * @param array $beachTags Tags of the current beach
 * @param int $limit Maximum number of similar beaches to return
 * @return array Array of similar beaches with similarity scores
 */
function getSimilarBeaches($beachId, $beachTags, $limit = 4) {
    if (empty($beachTags)) return [];

    require_once __DIR__ . '/db.php';

    // Build query to find beaches with overlapping tags
    $placeholders = [];
    $params = [':beach_id' => $beachId];
    foreach ($beachTags as $i => $tag) {
        $placeholders[] = ':tag' . $i;
        $params[':tag' . $i] = $tag;
    }

    $sql = "
        SELECT
            b.id, b.slug, b.name, b.municipality, b.cover_image,
            b.google_rating, b.google_review_count,
            COUNT(DISTINCT bt.tag) as shared_tags
        FROM beaches b
        INNER JOIN beach_tags bt ON b.id = bt.beach_id
        WHERE b.id != :beach_id
          AND b.publish_status = 'published'
          AND bt.tag IN (" . implode(',', $placeholders) . ")
        GROUP BY b.id
        ORDER BY shared_tags DESC, b.google_rating DESC
        LIMIT " . intval($limit);

    $similarBeaches = query($sql, $params);

    // Attach tags to similar beaches
    if (!empty($similarBeaches)) {
        attachBeachMetadata($similarBeaches);
    }

    return $similarBeaches;
}

/**
 * Get trending beaches based on recent favorites and views
 * @param int $limit Maximum number of beaches to return
 * @param int $days Number of days to look back
 * @return array Array of trending beaches
 */
function getTrendingBeaches($limit = 6, $days = 7) {
    require_once __DIR__ . '/db.php';

    $sql = "
        SELECT
            b.*,
            COUNT(DISTINCT uf.id) as recent_favorites
        FROM beaches b
        LEFT JOIN user_favorites uf ON b.id = uf.beach_id
            AND uf.created_at >= datetime('now', '-' || :days || ' days')
        WHERE b.publish_status = 'published'
        GROUP BY b.id
        ORDER BY recent_favorites DESC, b.google_rating DESC
        LIMIT :limit
    ";

    $beaches = query($sql, [':days' => $days, ':limit' => $limit]);

    if (!empty($beaches)) {
        attachBeachMetadata($beaches);
    }

    return $beaches;
}

/**
 * Get hidden gem beaches (high rating, low review count, secluded tag)
 * @param int $limit Maximum number of beaches to return
 * @return array Array of hidden gem beaches
 */
function getHiddenGems($limit = 6) {
    require_once __DIR__ . '/db.php';

    $sql = "
        SELECT b.*
        FROM beaches b
        LEFT JOIN beach_tags bt ON b.id = bt.beach_id AND bt.tag = 'secluded'
        WHERE b.publish_status = 'published'
          AND b.google_rating >= 4.3
          AND (b.google_review_count < 100 OR bt.tag IS NOT NULL)
        ORDER BY
            CASE WHEN bt.tag IS NOT NULL THEN 1 ELSE 2 END,
            b.google_rating DESC
        LIMIT :limit
    ";

    $beaches = query($sql, [':limit' => $limit]);

    if (!empty($beaches)) {
        attachBeachMetadata($beaches);
    }

    return $beaches;
}
