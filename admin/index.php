<?php
/**
 * Admin Dashboard
 */

$pageTitle = 'Dashboard';
$pageSubtitle = 'Overview of your Beach Finder site';

include __DIR__ . '/components/header.php';

$stats = getAdminStats();
$recentActivity = getRecentActivity(10);
?>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Beaches -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Beaches</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?= number_format($stats['total_beaches']) ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <span class="text-2xl">🏖️</span>
            </div>
        </div>
        <div class="mt-4 flex items-center gap-4 text-sm">
            <span class="text-green-600"><?= $stats['published_beaches'] ?> published</span>
            <span class="text-gray-400"><?= $stats['draft_beaches'] ?> drafts</span>
        </div>
    </div>

    <!-- Total Users -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Registered Users</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?= number_format($stats['total_users']) ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <span class="text-2xl">👥</span>
            </div>
        </div>
        <a href="/admin/users.php" class="mt-4 text-sm text-blue-600 hover:text-blue-700 inline-block">
            Manage users →
        </a>
    </div>

    <!-- Reviews -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">User Reviews</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?= number_format($stats['total_reviews']) ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                <span class="text-2xl">⭐</span>
            </div>
        </div>
        <?php if ($stats['pending_reviews'] > 0): ?>
        <a href="/admin/reviews.php?status=pending" class="mt-4 text-sm text-yellow-600 hover:text-yellow-700 inline-block">
            <?= $stats['pending_reviews'] ?> pending review →
        </a>
        <?php else: ?>
        <p class="mt-4 text-sm text-gray-400">All reviews moderated</p>
        <?php endif; ?>
    </div>

    <!-- Favorites -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Saved Favorites</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?= number_format($stats['total_favorites']) ?></p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                <span class="text-2xl">❤️</span>
            </div>
        </div>
        <p class="mt-4 text-sm text-gray-400">Total user favorites</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Activity -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="font-semibold text-gray-900">Recent Activity</h2>
        </div>
        <div class="divide-y divide-gray-100">
            <?php if (empty($recentActivity)): ?>
            <div class="p-6 text-center text-gray-500">
                No recent activity
            </div>
            <?php else: ?>
            <?php foreach ($recentActivity as $activity): ?>
            <a href="<?= h($activity['link']) ?>" class="flex items-center gap-4 px-6 py-4 hover:bg-gray-50">
                <div class="w-10 h-10 rounded-full flex items-center justify-center <?= $activity['type'] === 'review' ? 'bg-yellow-100' : 'bg-green-100' ?>">
                    <?= $activity['type'] === 'review' ? '⭐' : '👤' ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-900 truncate"><?= h($activity['message']) ?></p>
                    <p class="text-xs text-gray-500"><?= timeAgo($activity['time']) ?></p>
                </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="font-semibold text-gray-900">Quick Actions</h2>
        </div>
        <div class="p-6 space-y-4">
            <a href="/admin/beaches.php?action=new"
               class="flex items-center gap-4 p-4 rounded-lg border-2 border-dashed border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition-colors">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900">Add New Beach</p>
                    <p class="text-sm text-gray-500">Create a new beach listing</p>
                </div>
            </a>

            <a href="/admin/reviews.php?status=pending"
               class="flex items-center gap-4 p-4 rounded-lg border-2 border-dashed border-gray-200 hover:border-yellow-300 hover:bg-yellow-50 transition-colors">
                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900">Moderate Reviews</p>
                    <p class="text-sm text-gray-500">Approve or reject pending reviews</p>
                </div>
            </a>

            <a href="/sitemap.xml" target="_blank"
               class="flex items-center gap-4 p-4 rounded-lg border-2 border-dashed border-gray-200 hover:border-green-300 hover:bg-green-50 transition-colors">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900">View Sitemap</p>
                    <p class="text-sm text-gray-500">Check SEO sitemap</p>
                </div>
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>
