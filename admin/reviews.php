<?php
/**
 * Admin - Review Moderation
 */

require_once __DIR__ . '/../inc/db.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../inc/session.php';
    session_start();
    require_once __DIR__ . '/../inc/admin.php';
    requireAdmin();

    $reviewId = $_POST['review_id'] ?? '';
    $action = $_POST['action'] ?? '';

    if ($reviewId && in_array($action, ['approve', 'reject', 'delete'])) {
        $db = getDb();

        if ($action === 'delete') {
            $db->exec("DELETE FROM beach_reviews WHERE id = '$reviewId'");
        } else {
            $status = $action === 'approve' ? 'published' : 'rejected';
            $stmt = $db->prepare("UPDATE beach_reviews SET status = :status WHERE id = :id");
            $stmt->bindValue(':status', $status, SQLITE3_TEXT);
            $stmt->bindValue(':id', $reviewId, SQLITE3_TEXT);
            $stmt->execute();
        }

        // If HTMX request, return empty response
        if (isset($_SERVER['HTTP_HX_REQUEST'])) {
            exit;
        }

        header('Location: /admin/reviews.php?updated=1');
        exit;
    }
}

$pageTitle = 'Reviews';
$pageSubtitle = 'Moderate user reviews';

include __DIR__ . '/components/header.php';

// Get filter
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = '1=1';
$params = [];

if ($status) {
    $where .= ' AND r.status = :status';
    $params[':status'] = $status;
}

$reviews = query("
    SELECT r.*, u.name as user_name, u.email as user_email, b.name as beach_name, b.slug as beach_slug
    FROM beach_reviews r
    LEFT JOIN users u ON r.user_id = u.id
    LEFT JOIN beaches b ON r.beach_id = b.id
    WHERE $where
    ORDER BY r.created_at DESC
    LIMIT $limit OFFSET $offset
", $params);

$total = queryOne("SELECT COUNT(*) as count FROM beach_reviews r WHERE $where", $params)['count'] ?? 0;
$totalPages = ceil($total / $limit);

// Stats
$pendingCount = queryOne("SELECT COUNT(*) as count FROM beach_reviews WHERE status = 'pending'")['count'] ?? 0;
$publishedCount = queryOne("SELECT COUNT(*) as count FROM beach_reviews WHERE status = 'published'")['count'] ?? 0;
$rejectedCount = queryOne("SELECT COUNT(*) as count FROM beach_reviews WHERE status = 'rejected'")['count'] ?? 0;
?>

<!-- Status Tabs -->
<div class="bg-white rounded-xl shadow-sm mb-6">
    <div class="flex border-b border-gray-200">
        <a href="/admin/reviews.php"
           class="px-6 py-4 font-medium <?= !$status ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-500 hover:text-gray-700' ?>">
            All (<?= $total ?>)
        </a>
        <a href="/admin/reviews.php?status=pending"
           class="px-6 py-4 font-medium <?= $status === 'pending' ? 'text-yellow-600 border-b-2 border-yellow-600' : 'text-gray-500 hover:text-gray-700' ?>">
            Pending (<?= $pendingCount ?>)
        </a>
        <a href="/admin/reviews.php?status=published"
           class="px-6 py-4 font-medium <?= $status === 'published' ? 'text-green-600 border-b-2 border-green-600' : 'text-gray-500 hover:text-gray-700' ?>">
            Published (<?= $publishedCount ?>)
        </a>
        <a href="/admin/reviews.php?status=rejected"
           class="px-6 py-4 font-medium <?= $status === 'rejected' ? 'text-red-600 border-b-2 border-red-600' : 'text-gray-500 hover:text-gray-700' ?>">
            Rejected (<?= $rejectedCount ?>)
        </a>
    </div>
</div>

<?php if (isset($_GET['updated'])): ?>
<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">Review updated successfully!</div>
<?php endif; ?>

<?php if (empty($reviews)): ?>
<div class="bg-white rounded-xl shadow-sm p-12 text-center">
    <div class="text-4xl mb-4">⭐</div>
    <h2 class="text-xl font-semibold text-gray-900 mb-2">No reviews found</h2>
    <p class="text-gray-500">
        <?= $status === 'pending' ? 'No pending reviews to moderate.' : 'No reviews match your filter.' ?>
    </p>
</div>
<?php else: ?>

<!-- Reviews List -->
<div class="space-y-4">
    <?php foreach ($reviews as $review): ?>
    <div class="bg-white rounded-xl shadow-sm p-6" id="review-<?= h($review['id']) ?>">
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
                <!-- Header -->
                <div class="flex items-center gap-3 mb-3">
                    <div class="flex items-center gap-1">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="<?= $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-200' ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <span class="text-sm text-gray-500">•</span>
                    <a href="/beach/<?= h($review['beach_slug']) ?>" target="_blank" class="text-blue-600 hover:text-blue-700 font-medium">
                        <?= h($review['beach_name']) ?>
                    </a>
                </div>

                <!-- Title -->
                <?php if ($review['title']): ?>
                <h3 class="font-semibold text-gray-900 mb-2"><?= h($review['title']) ?></h3>
                <?php endif; ?>

                <!-- Review Text -->
                <p class="text-gray-600 mb-4"><?= nl2br(h($review['review_text'])) ?></p>

                <!-- Meta -->
                <div class="flex items-center gap-4 text-sm text-gray-500">
                    <span><?= h($review['user_name'] ?? $review['user_email'] ?? 'Anonymous') ?></span>
                    <span>•</span>
                    <span><?= timeAgo($review['created_at']) ?></span>
                    <?php if ($review['visit_date']): ?>
                    <span>•</span>
                    <span>Visited: <?= date('M Y', strtotime($review['visit_date'])) ?></span>
                    <?php endif; ?>
                    <?php if ($review['visit_type']): ?>
                    <span>•</span>
                    <span><?= ucfirst($review['visit_type']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Status Badge & Actions -->
            <div class="text-right">
                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full mb-4
                    <?php
                    switch ($review['status']) {
                        case 'published': echo 'bg-green-100 text-green-700'; break;
                        case 'rejected': echo 'bg-red-100 text-red-700'; break;
                        default: echo 'bg-yellow-100 text-yellow-700';
                    }
                    ?>">
                    <?= ucfirst($review['status'] ?? 'pending') ?>
                </span>

                <div class="flex flex-col gap-2">
                    <?php if ($review['status'] !== 'published'): ?>
                    <form method="POST" class="inline"
                          hx-post="/admin/reviews.php"
                          hx-target="#review-<?= h($review['id']) ?>"
                          hx-swap="outerHTML">
                        <input type="hidden" name="review_id" value="<?= h($review['id']) ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="w-full bg-green-100 hover:bg-green-200 text-green-700 px-3 py-1 rounded text-sm font-medium">
                            Approve
                        </button>
                    </form>
                    <?php endif; ?>

                    <?php if ($review['status'] !== 'rejected'): ?>
                    <form method="POST" class="inline"
                          hx-post="/admin/reviews.php"
                          hx-target="#review-<?= h($review['id']) ?>"
                          hx-swap="outerHTML">
                        <input type="hidden" name="review_id" value="<?= h($review['id']) ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="w-full bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1 rounded text-sm font-medium">
                            Reject
                        </button>
                    </form>
                    <?php endif; ?>

                    <form method="POST" class="inline"
                          hx-post="/admin/reviews.php"
                          hx-target="#review-<?= h($review['id']) ?>"
                          hx-swap="outerHTML"
                          hx-confirm="Are you sure you want to delete this review?">
                        <input type="hidden" name="review_id" value="<?= h($review['id']) ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="w-full text-gray-400 hover:text-red-600 px-3 py-1 text-sm">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($totalPages > 1): ?>
<div class="mt-6 flex items-center justify-between">
    <p class="text-sm text-gray-500">Showing <?= $offset + 1 ?>-<?= min($offset + $limit, $total) ?> of <?= $total ?> reviews</p>
    <div class="flex gap-2">
        <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 ?>&status=<?= urlencode($status) ?>"
           class="px-4 py-2 bg-white border rounded-lg hover:bg-gray-50">Previous</a>
        <?php endif; ?>
        <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 ?>&status=<?= urlencode($status) ?>"
           class="px-4 py-2 bg-white border rounded-lg hover:bg-gray-50">Next</a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<?php include __DIR__ . '/components/footer.php'; ?>
