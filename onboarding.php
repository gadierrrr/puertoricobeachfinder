<?php
/**
 * Onboarding Page - Collect user preferences after signup
 * Shown once to new users to personalize their experience
 */

require_once __DIR__ . '/inc/session.php';
session_start();
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/constants.php';

// Require authentication
requireAuth();

$user = currentUser();

// If already onboarded, redirect to profile or home
if (!empty($user['onboarding_completed'])) {
    redirect($_GET['redirect'] ?? '/');
}

// Handle skip request
if (isset($_GET['skip']) || isset($_COOKIE['skip_onboarding'])) {
    execute(
        'UPDATE users SET onboarding_completed = 1 WHERE id = :id',
        [':id' => $user['id']]
    );
    redirect($_GET['redirect'] ?? '/');
}

$pageTitle = 'Welcome to Beach Finder';
$pageDescription = 'Tell us what you love at the beach to get personalized recommendations.';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $activities = $_POST['activities'] ?? [];
        $vibe = $_POST['vibe'] ?? 'relaxing';

        // Save preferences
        execute(
            'INSERT OR REPLACE INTO user_preferences (user_id, preferred_activities, preferred_vibe, updated_at)
             VALUES (:user_id, :activities, :vibe, datetime("now"))',
            [
                ':user_id' => $user['id'],
                ':activities' => json_encode($activities),
                ':vibe' => $vibe
            ]
        );

        // Mark onboarding as completed
        execute(
            'UPDATE users SET onboarding_completed = 1 WHERE id = :id',
            [':id' => $user['id']]
        );

        // Redirect to home with welcome message
        $_SESSION['show_welcome'] = true;
        redirect($_GET['redirect'] ?? '/');
    }
}

// Activity options
$activityOptions = [
    ['id' => 'swimming', 'icon' => '🏊', 'label' => 'Swimming', 'desc' => 'Calm, clear waters'],
    ['id' => 'snorkeling', 'icon' => '🤿', 'label' => 'Snorkeling', 'desc' => 'Coral reefs & marine life'],
    ['id' => 'surfing', 'icon' => '🏄', 'label' => 'Surfing', 'desc' => 'Waves & water sports'],
    ['id' => 'relaxing', 'icon' => '🏖️', 'label' => 'Relaxing', 'desc' => 'Sunbathing & lounging'],
    ['id' => 'family', 'icon' => '👨‍👩‍👧‍👦', 'label' => 'Family', 'desc' => 'Kid-friendly spots'],
    ['id' => 'photography', 'icon' => '📸', 'label' => 'Photography', 'desc' => 'Scenic & Instagram-worthy'],
    ['id' => 'hiking', 'icon' => '🥾', 'label' => 'Hiking', 'desc' => 'Trails & hidden coves'],
    ['id' => 'secluded', 'icon' => '🏝️', 'label' => 'Secluded', 'desc' => 'Off the beaten path'],
];

// Vibe options
$vibeOptions = [
    ['id' => 'relaxing', 'icon' => '😌', 'label' => 'Relaxing', 'desc' => 'Peaceful & calm'],
    ['id' => 'adventurous', 'icon' => '🤸', 'label' => 'Adventurous', 'desc' => 'Active & exciting'],
    ['id' => 'family', 'icon' => '👨‍👩‍👧', 'label' => 'Family-Oriented', 'desc' => 'Safe & fun for kids'],
    ['id' => 'romantic', 'icon' => '💑', 'label' => 'Romantic', 'desc' => 'Perfect for couples'],
];

include __DIR__ . '/components/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4 py-16 pt-24">
    <div class="w-full max-w-2xl">
        <!-- Welcome Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-brand-yellow/20 mb-6">
                <span class="text-4xl">👋</span>
            </div>
            <h1 class="text-3xl sm:text-4xl font-bold text-white mb-3">
                Welcome, <?= h($user['name'] ?? 'Beach Explorer') ?>!
            </h1>
            <p class="text-gray-400 text-lg">
                Let's personalize your beach experience
            </p>
        </div>

        <form method="POST" action="" class="space-y-8">
            <?= csrfField() ?>

            <!-- Step 1: Activities -->
            <div class="bg-brand-darker/50 backdrop-blur-md rounded-xl border border-white/10 p-6">
                <h2 class="text-xl font-semibold text-white mb-2 flex items-center gap-2">
                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-brand-yellow text-brand-darker text-sm font-bold">1</span>
                    What do you love at the beach?
                </h2>
                <p class="text-gray-400 text-sm mb-6">Select all that apply - we'll find beaches that match</p>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <?php foreach ($activityOptions as $activity): ?>
                    <label class="activity-option cursor-pointer">
                        <input type="checkbox"
                               name="activities[]"
                               value="<?= h($activity['id']) ?>"
                               class="sr-only peer">
                        <div class="p-4 rounded-xl border-2 border-white/10 bg-white/5 text-center transition-all
                                    hover:border-brand-yellow/50 hover:bg-brand-yellow/5
                                    peer-checked:border-brand-yellow peer-checked:bg-brand-yellow/10">
                            <span class="text-3xl block mb-2"><?= $activity['icon'] ?></span>
                            <span class="text-white font-medium text-sm block"><?= h($activity['label']) ?></span>
                            <span class="text-gray-500 text-xs"><?= h($activity['desc']) ?></span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Step 2: Vibe -->
            <div class="bg-brand-darker/50 backdrop-blur-md rounded-xl border border-white/10 p-6">
                <h2 class="text-xl font-semibold text-white mb-2 flex items-center gap-2">
                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-brand-yellow text-brand-darker text-sm font-bold">2</span>
                    What's your beach vibe?
                </h2>
                <p class="text-gray-400 text-sm mb-6">Choose the one that best describes you</p>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <?php foreach ($vibeOptions as $index => $vibe): ?>
                    <label class="vibe-option cursor-pointer">
                        <input type="radio"
                               name="vibe"
                               value="<?= h($vibe['id']) ?>"
                               class="sr-only peer"
                               <?= $index === 0 ? 'checked' : '' ?>>
                        <div class="p-4 rounded-xl border-2 border-white/10 bg-white/5 text-center transition-all
                                    hover:border-brand-yellow/50 hover:bg-brand-yellow/5
                                    peer-checked:border-brand-yellow peer-checked:bg-brand-yellow/10">
                            <span class="text-3xl block mb-2"><?= $vibe['icon'] ?></span>
                            <span class="text-white font-medium text-sm block"><?= h($vibe['label']) ?></span>
                            <span class="text-gray-500 text-xs"><?= h($vibe['desc']) ?></span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <a href="/onboarding.php?skip=1<?= isset($_GET['redirect']) ? '&redirect=' . urlencode($_GET['redirect']) : '' ?>"
                   class="text-gray-500 hover:text-gray-300 text-sm transition-colors order-2 sm:order-1">
                    Skip for now
                </a>
                <button type="submit"
                        class="w-full sm:w-auto bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-8 py-3 rounded-xl font-semibold transition-all hover:-translate-y-0.5 hover:shadow-lg order-1 sm:order-2">
                    Find My Perfect Beaches
                </button>
            </div>
        </form>

        <!-- Progress indicator -->
        <div class="mt-8 flex items-center justify-center gap-2">
            <div class="w-3 h-3 rounded-full bg-brand-yellow"></div>
            <div class="w-12 h-1 rounded-full bg-brand-yellow"></div>
            <div class="w-3 h-3 rounded-full bg-white/20"></div>
        </div>
        <p class="text-center text-gray-500 text-xs mt-2">
            You're almost there! Just one more step.
        </p>
    </div>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>
