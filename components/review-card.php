<?php
/**
 * Review Card Component
 *
 * @param array $review - Review data
 */

$review = $review ?? [];

$reviewId = $review['id'] ?? '';
$rating = $review['rating'] ?? 0;
$title = $review['title'] ?? '';
$reviewText = $review['review_text'] ?? '';
$visitDate = $review['visit_date'] ?? '';
$visitType = $review['visit_type'] ?? '';
$helpfulCount = $review['helpful_count'] ?? 0;
$createdAt = $review['created_at'] ?? '';
$userName = $review['user_name'] ?? 'Anonymous';
$userInitial = strtoupper(substr($userName, 0, 1));

// Format date
$dateFormatted = $createdAt ? date('M j, Y', strtotime($createdAt)) : '';
$visitDateFormatted = $visitDate ? date('F Y', strtotime($visitDate)) : '';

// Visit type labels
$visitTypeLabels = [
    'solo' => '👤 Solo',
    'couple' => '💑 Couple',
    'family' => '👨‍👩‍👧 Family',
    'friends' => '👥 Friends',
    'group' => '👥 Group'
];
$visitTypeLabel = $visitTypeLabels[$visitType] ?? '';
?>

<div class="review-card bg-white border border-gray-100 rounded-lg p-4" data-review-id="<?= h($reviewId) ?>">
    <!-- Header -->
    <div class="flex items-start gap-3 mb-3">
        <!-- Avatar -->
        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-semibold">
            <?= h($userInitial) ?>
        </div>

        <div class="flex-1 min-w-0">
            <!-- User name and date -->
            <div class="flex items-center gap-2 flex-wrap">
                <span class="font-medium text-gray-900"><?= h($userName) ?></span>
                <?php if ($dateFormatted): ?>
                <span class="text-gray-400 text-sm">•</span>
                <span class="text-gray-500 text-sm"><?= $dateFormatted ?></span>
                <?php endif; ?>
            </div>

            <!-- Rating stars -->
            <div class="flex items-center gap-1 mt-0.5">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="<?= $i <= $rating ? 'text-yellow-400' : 'text-gray-300' ?> text-sm">★</span>
                <?php endfor; ?>
                <?php if ($visitTypeLabel): ?>
                <span class="text-gray-300 mx-1">•</span>
                <span class="text-gray-500 text-xs"><?= $visitTypeLabel ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Title -->
    <?php if ($title): ?>
    <h4 class="font-semibold text-gray-900 mb-2"><?= h($title) ?></h4>
    <?php endif; ?>

    <!-- Review text -->
    <?php if ($reviewText): ?>
    <p class="text-gray-600 text-sm leading-relaxed mb-3"><?= nl2br(h($reviewText)) ?></p>
    <?php endif; ?>

    <!-- Visit info -->
    <?php if ($visitDateFormatted): ?>
    <p class="text-gray-500 text-xs mb-3">
        Visited in <?= $visitDateFormatted ?>
    </p>
    <?php endif; ?>

    <!-- Footer -->
    <div class="flex items-center justify-between pt-3 border-t border-gray-100">
        <!-- Helpful button -->
        <button class="helpful-btn flex items-center gap-1.5 text-gray-500 hover:text-blue-600 text-sm transition-colors"
                hx-post="/api/reviews/helpful.php"
                hx-vals='{"review_id": "<?= h($reviewId) ?>"}'
                hx-target="this"
                hx-swap="outerHTML">
            <span>👍</span>
            <span>Helpful</span>
            <?php if ($helpfulCount > 0): ?>
            <span class="text-gray-400">(<?= $helpfulCount ?>)</span>
            <?php endif; ?>
        </button>

        <!-- Report -->
        <button class="text-gray-400 hover:text-gray-600 text-xs transition-colors"
                onclick="reportReview('<?= h($reviewId) ?>')">
            Report
        </button>
    </div>
</div>
