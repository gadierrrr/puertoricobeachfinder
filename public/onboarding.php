<?php
/**
 * Onboarding Page - Collect user preferences after signup
 * Shown once to new users to personalize their experience
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
session_start();
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/island_chart.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/i18n.php';

// Require authentication
requireAuth();

$user = currentUser();
$redirectUrl = sanitizeInternalRedirect($_GET['redirect'] ?? '/');

// If already onboarded, redirect to profile or home
if (!empty($user['onboarding_completed'])) {
    redirectInternal($redirectUrl);
}

// Handle skip request
if (isset($_GET['skip']) || isset($_COOKIE['skip_onboarding'])) {
    execute(
        'UPDATE users SET onboarding_completed = 1 WHERE id = :id',
        [':id' => $user['id']]
    );
    redirectInternal($redirectUrl);
}

$pageTitle = __('onboarding.title');
$pageDescription = __('onboarding.description');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $error = __('onboarding.invalid_request');
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

        // Referral reward: onboarding is the "meaningful action" that completes a referral
        // and (re)awards the referrer's achievement badges.
        require_once APP_ROOT . '/inc/invite.php';
        inviteMarkCompleted($user['id']);

        // Redirect to home with welcome message
        $_SESSION['show_welcome'] = true;
        redirectInternal($redirectUrl);
    }
}

// Activity options
$activityOptions = [
    ['id' => 'swimming', 'icon' => '🏊', 'label' => __('onboarding.act_swimming'), 'desc' => __('onboarding.act_swimming_desc')],
    ['id' => 'snorkeling', 'icon' => '🤿', 'label' => __('onboarding.act_snorkeling'), 'desc' => __('onboarding.act_snorkeling_desc')],
    ['id' => 'surfing', 'icon' => '🏄', 'label' => __('onboarding.act_surfing'), 'desc' => __('onboarding.act_surfing_desc')],
    ['id' => 'relaxing', 'icon' => '🏖️', 'label' => __('onboarding.act_relaxing'), 'desc' => __('onboarding.act_relaxing_desc')],
    ['id' => 'family', 'icon' => '👨‍👩‍👧‍👦', 'label' => __('onboarding.act_family'), 'desc' => __('onboarding.act_family_desc')],
    ['id' => 'photography', 'icon' => '📸', 'label' => __('onboarding.act_photography'), 'desc' => __('onboarding.act_photography_desc')],
    ['id' => 'hiking', 'icon' => '🥾', 'label' => __('onboarding.act_hiking'), 'desc' => __('onboarding.act_hiking_desc')],
    ['id' => 'secluded', 'icon' => '🏝️', 'label' => __('onboarding.act_secluded'), 'desc' => __('onboarding.act_secluded_desc')],
];

// Vibe options
$vibeOptions = [
    ['id' => 'relaxing', 'icon' => '😌', 'label' => __('onboarding.vibe_relaxing'), 'desc' => __('onboarding.vibe_relaxing_desc')],
    ['id' => 'adventurous', 'icon' => '🤸', 'label' => __('onboarding.vibe_adventurous'), 'desc' => __('onboarding.vibe_adventurous_desc')],
    ['id' => 'family', 'icon' => '👨‍👩‍👧', 'label' => __('onboarding.vibe_family'), 'desc' => __('onboarding.vibe_family_desc')],
    ['id' => 'romantic', 'icon' => '💑', 'label' => __('onboarding.vibe_romantic'), 'desc' => __('onboarding.vibe_romantic_desc')],
];

$redesignLayout = useRedesign();
$bodyClasses = trim(($bodyClasses ?? '') . ' rd-auth rd-onboarding');
include APP_ROOT . '/components/header.php';
?>

<div class="onboarding-shell">
    <div class="onboarding-hero">
        <div>
            <p class="onboarding-kicker"><?= h(__('onboarding.setup_label')) ?></p>
            <h1>
                <?= h(__('onboarding.welcome', ['name' => $user['name'] ?? __('profile.beach_explorer')])) ?>
            </h1>
            <p class="onboarding-lede">
                <?= h(__('onboarding.personalize')) ?>
            </p>
        </div>

        <div class="onboarding-progress" aria-label="<?= h(__('onboarding.almost_there')) ?>">
            <span class="is-active"><?= h(__('onboarding.progress_activities')) ?></span>
            <i></i>
            <span><?= h(__('onboarding.progress_results')) ?></span>
        </div>
    </div>

    <div class="onboarding-layout">
        <form method="POST" action="" class="onboarding-form">
            <?= csrfField() ?>

            <?php if (!empty($error)): ?>
            <div class="onboarding-alert" role="alert">
                <i data-lucide="alert-circle" aria-hidden="true"></i>
                <span><?= h($error) ?></span>
            </div>
            <?php endif; ?>

            <section class="onboarding-section" aria-labelledby="onboarding-activities-title">
                <div class="onboarding-section-head">
                    <span class="onboarding-step">1</span>
                    <div>
                        <h2 id="onboarding-activities-title"><?= h(__('onboarding.step1_title')) ?></h2>
                        <p><?= h(__('onboarding.step1_subtitle')) ?></p>
                    </div>
                </div>

                <div class="onboarding-options onboarding-options-activities">
                    <?php foreach ($activityOptions as $activity): ?>
                    <label class="onboarding-choice activity-option">
                        <input type="checkbox"
                               name="activities[]"
                               value="<?= h($activity['id']) ?>"
                               class="sr-only">
                        <div class="onboarding-choice-card">
                            <span class="onboarding-choice-icon" aria-hidden="true"><?= $activity['icon'] ?></span>
                            <span class="onboarding-choice-title"><?= h($activity['label']) ?></span>
                            <span class="onboarding-choice-desc"><?= h($activity['desc']) ?></span>
                            <span class="onboarding-choice-check" aria-hidden="true">
                                <i data-lucide="check"></i>
                            </span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="onboarding-section" aria-labelledby="onboarding-vibe-title">
                <div class="onboarding-section-head">
                    <span class="onboarding-step">2</span>
                    <div>
                        <h2 id="onboarding-vibe-title"><?= h(__('onboarding.step2_title')) ?></h2>
                        <p><?= h(__('onboarding.step2_subtitle')) ?></p>
                    </div>
                </div>

                <div class="onboarding-options onboarding-options-vibes">
                    <?php foreach ($vibeOptions as $index => $vibe): ?>
                    <label class="onboarding-choice vibe-option">
                        <input type="radio"
                               name="vibe"
                               value="<?= h($vibe['id']) ?>"
                               class="sr-only"
                               <?= $index === 0 ? 'checked' : '' ?>>
                        <div class="onboarding-choice-card">
                            <span class="onboarding-choice-icon" aria-hidden="true"><?= $vibe['icon'] ?></span>
                            <span class="onboarding-choice-title"><?= h($vibe['label']) ?></span>
                            <span class="onboarding-choice-desc"><?= h($vibe['desc']) ?></span>
                            <span class="onboarding-choice-check" aria-hidden="true">
                                <i data-lucide="check"></i>
                            </span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="onboarding-actions">
                <a href="/onboarding?skip=1<?= $redirectUrl !== '/' ? '&redirect=' . urlencode($redirectUrl) : '' ?>">
                    <?= h(__('onboarding.skip')) ?>
                </a>
                <button type="submit"
                        class="onboarding-submit">
                    <?= h(__('onboarding.submit')) ?>
                    <i data-lucide="arrow-right" aria-hidden="true"></i>
                </button>
            </div>
        </form>

        <aside class="onboarding-aside" aria-labelledby="onboarding-aside-title">
            <div class="onboarding-aside-map" aria-hidden="true">
                <svg class="onboarding-mini-map" viewBox="0 0 560 360" focusable="false">
                    <defs>
                        <linearGradient id="onboardingSea" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="#E8F6FC"/>
                            <stop offset="1" stop-color="#BFDFF3"/>
                        </linearGradient>
                        <linearGradient id="onboardingSand" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="#F0C452"/>
                            <stop offset="1" stop-color="#D6A129"/>
                        </linearGradient>
                    </defs>
                    <rect class="mini-map-sea" x="20" y="32" width="520" height="296" rx="24"/>
                    <path class="mini-map-grid" d="M164 56V318M382 56V318M44 122H516M44 250H516"/>
                    <text class="mini-map-coord" x="158" y="146">67 W</text>
                    <text class="mini-map-coord" x="376" y="146">66 W</text>
                    <path class="mini-map-contour contour-far"
                          transform="translate(247.5,201.7) scale(1.08) translate(-247.5,-201.7)"
                          d="<?= ISLAND_CHART_CONTOUR_D ?>"/>
                    <path class="mini-map-contour contour-near"
                          transform="translate(247.5,201.7) scale(1.025) translate(-247.5,-201.7)"
                          d="<?= ISLAND_CHART_CONTOUR_D ?>"/>
                    <path class="mini-map-island" d="<?= ISLAND_CHART_ISLAND_D ?>"/>
                    <path class="mini-map-cay" d="<?= ISLAND_CHART_CUL_D ?>"/>
                    <path class="mini-map-cay" d="<?= ISLAND_CHART_VIE_D ?>"/>
                    <g class="mini-map-marker" transform="translate(198 132)">
                        <circle r="13"/>
                        <text y="4">N</text>
                    </g>
                    <g class="mini-map-marker" transform="translate(90 202)">
                        <circle r="13"/>
                        <text y="4">W</text>
                    </g>
                    <g class="mini-map-marker" transform="translate(398 196)">
                        <circle r="13"/>
                        <text y="4">E</text>
                    </g>
                    <g class="mini-map-marker" transform="translate(276 268)">
                        <circle r="13"/>
                        <text y="4">S</text>
                    </g>
                </svg>
            </div>
            <p class="onboarding-aside-kicker"><?= h(__('onboarding.aside_kicker')) ?></p>
            <h2 id="onboarding-aside-title"><?= h(__('onboarding.aside_title')) ?></h2>
            <p><?= h(__('onboarding.aside_body')) ?></p>
            <ul>
                <li><i data-lucide="sparkles" aria-hidden="true"></i><?= h(__('onboarding.aside_item_1')) ?></li>
                <li><i data-lucide="map" aria-hidden="true"></i><?= h(__('onboarding.aside_item_2')) ?></li>
                <li><i data-lucide="sliders-horizontal" aria-hidden="true"></i><?= h(__('onboarding.aside_item_3')) ?></li>
            </ul>
        </aside>
    </div>
</div>

<?php
// The welcome modal (in footer.php) fires for any not-yet-welcomed user; suppress it
// here so it never renders on top of the onboarding form itself.
$bfSuppressWelcome = true;
include APP_ROOT . '/components/footer.php';
?>
