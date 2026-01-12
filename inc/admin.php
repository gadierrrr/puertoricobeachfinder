<?php
/**
 * Admin Helper Functions
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

/**
 * Check if current user is an admin
 */
function isAdmin(): bool {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    $user = queryOne(
        'SELECT is_admin FROM users WHERE id = :id',
        [':id' => $_SESSION['user_id']]
    );

    return $user && $user['is_admin'] == 1;
}

/**
 * Require admin access - redirect if not admin
 */
function requireAdmin(): void {
    if (!isAuthenticated()) {
        redirect('/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }

    if (!isAdmin()) {
        http_response_code(403);
        include __DIR__ . '/../admin/403.php';
        exit;
    }
}

/**
 * Get admin stats for dashboard
 */
function getAdminStats(): array {
    return [
        'total_beaches' => queryOne('SELECT COUNT(*) as count FROM beaches')['count'] ?? 0,
        'published_beaches' => queryOne('SELECT COUNT(*) as count FROM beaches WHERE publish_status = "published"')['count'] ?? 0,
        'draft_beaches' => queryOne('SELECT COUNT(*) as count FROM beaches WHERE publish_status = "draft"')['count'] ?? 0,
        'total_users' => queryOne('SELECT COUNT(*) as count FROM users')['count'] ?? 0,
        'total_reviews' => queryOne('SELECT COUNT(*) as count FROM beach_reviews')['count'] ?? 0,
        'pending_reviews' => queryOne('SELECT COUNT(*) as count FROM beach_reviews WHERE status = "pending"')['count'] ?? 0,
        'total_favorites' => queryOne('SELECT COUNT(*) as count FROM user_favorites')['count'] ?? 0,
    ];
}

/**
 * Get recent activity for admin dashboard
 */
function getRecentActivity(int $limit = 10): array {
    $activities = [];

    // Recent reviews
    $reviews = query("
        SELECT 'review' as type, r.id, r.title, r.created_at, u.name as user_name, b.name as beach_name
        FROM beach_reviews r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN beaches b ON r.beach_id = b.id
        ORDER BY r.created_at DESC
        LIMIT :limit
    ", [':limit' => $limit]);

    foreach ($reviews as $review) {
        $activities[] = [
            'type' => 'review',
            'message' => ($review['user_name'] ?? 'Anonymous') . ' reviewed ' . $review['beach_name'],
            'time' => $review['created_at'],
            'link' => '/admin/reviews.php?id=' . $review['id']
        ];
    }

    // Recent users
    $users = query("
        SELECT 'user' as type, id, name, email, created_at
        FROM users
        ORDER BY created_at DESC
        LIMIT :limit
    ", [':limit' => $limit]);

    foreach ($users as $user) {
        $activities[] = [
            'type' => 'user',
            'message' => 'New user: ' . ($user['name'] ?? $user['email']),
            'time' => $user['created_at'],
            'link' => '/admin/users.php?id=' . $user['id']
        ];
    }

    // Sort by time
    usort($activities, function($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });

    return array_slice($activities, 0, $limit);
}

/**
 * Generate UUID
 */
function adminGenerateUuid(): string {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
