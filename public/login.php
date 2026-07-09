<?php
/**
 * Login Page - Google OAuth + Magic Link Authentication
 * Redesigned with split layout, social proof, and feature showcase
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
session_start();
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/google-oauth.php';
require_once APP_ROOT . '/inc/i18n.php';

$redirectUrl = sanitizeInternalRedirect($_GET['redirect'] ?? '/');

// If already logged in, redirect
if (isAuthenticated()) {
    redirectInternal($redirectUrl);
}

$error = '';
$success = '';
$accountDeleted = isset($_GET['account_deleted']) && $_GET['account_deleted'] === '1';
// Magic-link (email) sign-in is offered as a second channel alongside Google OAuth.
// ?method=email reveals the email form.
$showMagicLinkForm = isset($_GET['method']) && $_GET['method'] === 'email';
$devHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$devLoginEnabled = appEnv() === 'dev'
    && appDebug()
    && (
        str_starts_with($devHost, 'localhost')
        || str_starts_with($devHost, '127.0.0.1')
        || str_starts_with($devHost, '[::1]')
    );

// Handle OAuth error codes from callback
if (isset($_GET['error'])) {
    $errorMessages = [
        'google_denied' => __('login.error_google_denied'),
        'no_code' => __('login.error_no_code'),
        'invalid_state' => __('login.error_invalid_state'),
        'token_failed' => __('login.error_token_failed'),
        'userinfo_failed' => __('login.error_userinfo_failed'),
        'email_not_verified' => __('login.error_email_not_verified'),
        'user_creation_failed' => __('login.error_user_creation_failed'),
        'google_not_configured' => __('login.error_google_not_configured'),
    ];
    $error = $errorMessages[$_GET['error']] ?? __('login.error_generic');
}

// Handle session timeout
if (isset($_GET['timeout'])) {
    $error = __('login.error_timeout');
}

// Handle form submission (local dev login or magic link)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once APP_ROOT . '/inc/auth.php';

    // Validate CSRF
    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        $error = __('login.error_csrf');
    } elseif (($_POST['auth_action'] ?? '') === 'dev_login') {
        if (!$devLoginEnabled) {
            $error = __('login.error_generic');
        } else {
            $testEmail = 'local-test@puertoricobeachfinder.test';
            $testName = 'Local Test User';
            $user = queryOne('SELECT * FROM users WHERE email = :email', [':email' => $testEmail]);

            if (!$user) {
                execute(
                    'INSERT OR IGNORE INTO users (id, email, name, created_at, updated_at)
                     VALUES (:id, :email, :name, datetime("now"), datetime("now"))',
                    [
                        ':id' => uuid(),
                        ':email' => $testEmail,
                        ':name' => $testName,
                    ]
                );
                $user = queryOne('SELECT * FROM users WHERE email = :email', [':email' => $testEmail]);
            }

            if (!$user) {
                $error = __('login.error_generic');
            } else {
                loginUser($user);
                redirectInternal(sanitizeInternalRedirect($_POST['redirect'] ?? '/'));
            }
        }
    } elseif (trim($_POST['website'] ?? '') !== '') {
        // Honeypot: a real user never fills the hidden "website" field. Silently
        // pretend success (don't send) so bots can't tell they were caught.
        $success = 'Check your email for the login link!';
    } else {
        $email = trim($_POST['email'] ?? '');
        $postRedirect = sanitizeInternalRedirect($_POST['redirect'] ?? '/');
        $result = sendMagicLink($email, $postRedirect !== '/' ? $postRedirect : '');

        if ($result['success']) {
            $success = $result['message'];
        } else {
            $error = $result['error'];
        }
    }
}

$googleEnabled = isGoogleOAuthEnabled();

// If Google OAuth is unavailable, default to the email/magic-link form so users still have
// a working way to sign in (otherwise the email channel is only linked from the Google branch).
if (!$googleEnabled && !$showMagicLinkForm) {
    $showMagicLinkForm = true;
}

// Cache social proof stats (5 min cache) to avoid DB queries on every page load
$cacheFile = APP_ROOT . '/data/cache/login-stats.json';
$cacheMaxAge = 300; // 5 minutes

$stats = null;
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheMaxAge) {
    $stats = json_decode(file_get_contents($cacheFile), true);
}

if (!$stats) {
    // Query stats and cache them
    $stats = [
        'userCount' => queryOne('SELECT COUNT(*) as count FROM users', [])['count'] ?? 0,
        'photoCount' => queryOne('SELECT COUNT(*) as count FROM beach_photos WHERE status = "published"', [])['count'] ?? 0,
        'reviewCount' => queryOne('SELECT COUNT(*) as count FROM beach_reviews WHERE status = "published"', [])['count'] ?? 0,
        'checkinCount' => queryOne('SELECT COUNT(*) as count FROM beach_checkins', [])['count'] ?? 0,
        'featuredBeach' => queryOne(
            'SELECT name, municipality, cover_image FROM beaches
             WHERE cover_image IS NOT NULL AND google_rating >= 4.5
             ORDER BY google_review_count DESC LIMIT 1',
            []
        )
    ];
    // Write cache (create dir if needed)
    @mkdir(dirname($cacheFile), 0755, true);
    @file_put_contents($cacheFile, json_encode($stats));
}

$userCountDisplay = $stats['userCount'] > 100 ? number_format($stats['userCount']) : '500';
$photoCount = $stats['photoCount'];
$reviewCount = $stats['reviewCount'];
$checkinCount = $stats['checkinCount'];
$featuredBeach = $stats['featuredBeach'];

$pageTitle = __('login.title');
$skipMapCSS = true; // Auth pages don't need map
$redesignLayout = useRedesign();
$bodyClasses = trim(($bodyClasses ?? '') . ' rd-auth');
include APP_ROOT . '/components/header.php';
$loginManagedHero = pageHeroResolve('account');
?>

<div class="min-h-screen flex pt-16">
    <!-- Left Panel - Hero Image & Value Props (Hidden on mobile) -->
    <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden">
        <!-- Background Image -->
        <div class="absolute inset-0">
            <?php if ($loginManagedHero !== null): ?>
            <img src="<?= h($loginManagedHero['image']) ?>"
                 alt="Puerto Rico coast"
                 class="w-full h-full object-cover"
                 style="object-position:<?= h($loginManagedHero['position']) ?>">
            <?php elseif ($featuredBeach && $featuredBeach['cover_image']): ?>
            <img src="<?= h(getBeachImageUrl($featuredBeach, 'large')) ?>"
                 data-fallback-src="/images/beaches/placeholder-beach.webp"
                 alt="Beautiful Puerto Rico beach"
                 class="w-full h-full object-cover">
            <?php else: ?>
            <img src="/images/beaches/jobos-beach-isabela-18513-67085.jpg"
                 alt="Jobos Beach, Puerto Rico"
                 class="w-full h-full object-cover">
            <?php endif; ?>
            <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/60 to-black/40"<?= $loginManagedHero !== null ? ' style="opacity:' . h((string) ($loginManagedHero['overlay'] / 100)) . '"' : '' ?>></div>
        </div>

        <!-- Content Overlay -->
        <div class="relative z-10 flex flex-col justify-center p-12 xl:p-16">
            <!-- Tagline -->
            <div class="mb-8">
                <h2 class="text-4xl xl:text-5xl font-bold text-white leading-tight mb-4">
                    <?= h(__('login.hero_title_1')) ?><br>
                    <span class="text-sunset-400"><?= h(__('login.hero_title_2')) ?></span><br>
                    <?= h(__('login.hero_title_3')) ?>
                </h2>
                <p class="text-lg text-white/70 max-w-md">
                    <?= h(__('login.hero_subtitle', ['count' => $userCountDisplay])) ?>
                </p>
            </div>

            <!-- Benefit-Focused Feature List -->
            <div class="space-y-4 mb-10">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-sunset-400/20 flex items-center justify-center flex-shrink-0">
                        <span class="text-2xl">🌤️</span>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold"><?= h(__('login.feature_conditions_title')) ?></h3>
                        <p class="text-white/60 text-sm"><?= h(__('login.feature_conditions_desc')) ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-sunset-400/20 flex items-center justify-center flex-shrink-0">
                        <span class="text-2xl">❤️</span>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold"><?= h(__('login.feature_favorites_title')) ?></h3>
                        <p class="text-white/60 text-sm"><?= h(__('login.feature_favorites_desc')) ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-sunset-400/20 flex items-center justify-center flex-shrink-0">
                        <span class="text-2xl">🏆</span>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold"><?= h(__('login.feature_badges_title')) ?></h3>
                        <p class="text-white/60 text-sm"><?= h(__('login.feature_badges_desc')) ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-sunset-400/20 flex items-center justify-center flex-shrink-0">
                        <span class="text-2xl">👥</span>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold"><?= h(__('login.feature_community_title')) ?></h3>
                        <p class="text-white/60 text-sm"><?= h(__('login.feature_community_desc')) ?></p>
                    </div>
                </div>
            </div>

            <!-- Community Stats -->
            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-warm-200 max-w-md">
                <p class="text-white/60 text-sm mb-4"><?= h(__('login.community_shared')) ?></p>
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-sunset-400"><?= number_format(max($photoCount, 500)) ?>+</div>
                        <div class="text-white/50 text-xs"><?= h(__('login.stat_photos')) ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-sunset-400"><?= number_format(max($reviewCount, 200)) ?>+</div>
                        <div class="text-white/50 text-xs"><?= h(__('login.stat_reviews')) ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-sunset-400"><?= number_format(max($checkinCount, 1000)) ?>+</div>
                        <div class="text-white/50 text-xs"><?= h(__('login.stat_checkins')) ?></div>
                    </div>
                </div>
                <div class="pt-4 border-t border-warm-200">
                    <div class="flex gap-1 mb-2">
                        <span class="text-sunset-400">★</span>
                        <span class="text-sunset-400">★</span>
                        <span class="text-sunset-400">★</span>
                        <span class="text-sunset-400">★</span>
                        <span class="text-sunset-400">★</span>
                    </div>
                    <p class="text-white/90 italic text-sm">
                        <?= h(__('login.testimonial')) ?>
                    </p>
                    <p class="text-white/50 text-xs mt-2"><?= h(__('login.testimonial_author')) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Panel - Sign In Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-8 lg:p-12 bg-white">
        <div class="w-full max-w-md">
            <!-- Social Proof Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-2 bg-sunset-400/10 border border-sunset-400/20 rounded-full px-4 py-2 mb-6">
                    <div class="flex -space-x-2">
                        <div class="w-6 h-6 rounded-full bg-blue-500 border-2 border-white flex items-center justify-center text-[10px] text-white font-bold">J</div>
                        <div class="w-6 h-6 rounded-full bg-green-500 border-2 border-white flex items-center justify-center text-[10px] text-white font-bold">M</div>
                        <div class="w-6 h-6 rounded-full bg-purple-500 border-2 border-white flex items-center justify-center text-[10px] text-white font-bold">C</div>
                        <div class="w-6 h-6 rounded-full bg-orange-500 border-2 border-white flex items-center justify-center text-[10px] text-white font-bold">+</div>
                    </div>
                    <span class="text-sm text-sunset-400 font-medium"><?= h(__('login.join_explorers', ['count' => $userCountDisplay])) ?></span>
                </div>

                <h1 class="text-3xl sm:text-4xl font-bold text-warm-900 mb-2">
                    <?= $showMagicLinkForm ? h(__('login.sign_in_email')) : h(__('login.start_exploring')) ?>
                </h1>
                <p class="text-warm-500">
                    <?= $showMagicLinkForm ? h(__('login.magic_link_info')) : h(__('login.free_forever')) ?>
                </p>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg mb-6 flex items-start gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                <span><?= h($error) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($accountDeleted): ?>
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg mb-6">
                <div class="flex items-start gap-3">
                    <i data-lucide="shield-check" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                    <div>
                        <p class="font-medium"><?= h(__('login.account_deleted_title')) ?></p>
                        <p class="text-sm mt-1 text-green-400/80"><?= h(__('login.account_deleted_message')) ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg mb-6">
                <div class="flex items-start gap-3">
                    <i data-lucide="mail-check" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                    <div>
                        <p class="font-medium"><?= h(__('login.check_email')) ?></p>
                        <p class="text-sm mt-1 text-green-400/80"><?= h($success) ?></p>
                    </div>
                </div>
            </div>
            <?php else: ?>

            <div class="space-y-6">
                <?php if (!$showMagicLinkForm): ?>
                    <?php if ($googleEnabled): ?>
                    <!-- Google Sign In Button -->
                    <a href="/auth/google/<?= $redirectUrl !== '/' ? '?redirect=' . urlencode($redirectUrl) : '' ?>"
                       class="w-full flex items-center justify-center gap-3 bg-white hover:bg-gray-50 text-gray-700 py-3.5 px-4 rounded-xl font-medium transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <span><?= h(__('login.continue_google')) ?></span>
                    </a>

                    <!-- Trust Signal -->
                    <div class="flex items-center justify-center gap-2 text-sm text-warm-500">
                        <i data-lucide="shield-check" class="w-4 h-4 text-green-500 a11y-success-text"></i>
                        <span><?= h(__('login.trust_signal')) ?></span>
                    </div>

                    <!-- Magic Link (email) option — second sign-in channel -->
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-warm-200"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-white text-warm-500"><?= h(__('login.or')) ?></span>
                        </div>
                    </div>
                    <a href="?method=email<?= $redirectUrl !== '/' ? '&redirect=' . urlencode($redirectUrl) : '' ?>"
                       class="w-full flex items-center justify-center gap-3 bg-warm-50 hover:bg-warm-100 border border-warm-200 text-warm-900 py-3.5 px-4 rounded-xl font-medium transition-all">
                        <i data-lucide="mail" class="w-5 h-5"></i>
                        <span><?= h(__('login.continue_email')) ?></span>
                    </a>
                    <?php else: ?>
                    <div class="text-center py-8 text-warm-500">
                        <i data-lucide="alert-triangle" class="w-12 h-12 mx-auto mb-4 text-yellow-500/50"></i>
                        <p><?= h(__('login.google_unavailable')) ?></p>
                        <p class="text-sm mt-2 text-warm-500"><?= h(__('login.google_config_note')) ?></p>
                    </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- Magic Link Form -->
                    <form method="POST" action="" class="space-y-4">
                        <?= csrfField() ?>
                        <input type="hidden" name="redirect" value="<?= h($redirectUrl) ?>">
                        <!-- Honeypot: must stay empty; positioned offscreen for real users -->
                        <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true"
                               class="hidden" value="">

                        <div>
                            <label for="email" class="block text-sm font-medium text-warm-600 mb-2"><?= h(__('login.email_label')) ?></label>
                            <div class="relative">
                                <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-warm-500"></i>
                                <input type="email"
                                       id="email"
                                       name="email"
                                       required
                                       placeholder="<?= h(__('login.email_placeholder')) ?>"
                                       class="w-full pl-12 pr-4 py-3.5 bg-warm-50 border border-warm-200 rounded-xl text-warm-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-ocean-400 focus:border-transparent">
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full bg-sunset-400 hover:bg-sunset-300 text-ocean-900 py-3.5 px-4 rounded-xl font-semibold transition-all hover:-translate-y-0.5 hover:shadow-lg">
                            <?= h(__('login.send_magic_link')) ?>
                        </button>

                        <p class="text-center text-sm text-warm-500">
                            <?= h(__('login.magic_link_note')) ?>
                        </p>
                    </form>

                    <?php if ($googleEnabled): ?>
                    <!-- Back to Google option -->
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-warm-200"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-white text-warm-500"><?= h(__('login.or')) ?></span>
                        </div>
                    </div>

                    <a href="?<?= $redirectUrl !== '/' ? 'redirect=' . urlencode($redirectUrl) : '' ?>"
                       class="w-full flex items-center justify-center gap-3 bg-warm-50 hover:bg-warm-100 border border-warm-200 text-warm-900 py-3.5 px-4 rounded-xl font-medium transition-all">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <span><?= h(__('login.continue_google_instead')) ?></span>
                    </a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($devLoginEnabled): ?>
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-warm-200"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-white text-warm-500"><?= h(__('login.dev_login_separator')) ?></span>
                        </div>
                    </div>

                    <form method="POST" action="" class="space-y-3">
                        <?= csrfField() ?>
                        <input type="hidden" name="auth_action" value="dev_login">
                        <input type="hidden" name="redirect" value="<?= h($redirectUrl) ?>">
                        <button type="submit"
                                class="w-full flex items-center justify-center gap-3 bg-ocean-500 hover:bg-ocean-600 text-white py-3.5 px-4 rounded-xl font-semibold transition-all hover:-translate-y-0.5 hover:shadow-lg">
                            <i data-lucide="flask-conical" class="w-5 h-5"></i>
                            <span><?= h(__('login.dev_login_button')) ?></span>
                        </button>
                        <p class="text-center text-xs text-warm-500"><?= h(__('login.dev_login_note')) ?></p>
                    </form>
                <?php endif; ?>
            </div>

            <?php endif; ?>

            <!-- Mobile Feature Cards (visible only on mobile) -->
            <div class="lg:hidden mt-8 pt-8 border-t border-warm-200">
                <p class="text-center text-sm text-warm-500 mb-4"><?= h(__('login.join_who', ['count' => $userCountDisplay])) ?></p>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-warm-50 rounded-xl p-4 text-center border border-warm-200">
                        <span class="text-2xl mb-2 block">🌤️</span>
                        <p class="text-warm-900 text-sm font-medium"><?= h(__('login.mobile_conditions')) ?></p>
                        <p class="text-warm-500 text-xs mt-1"><?= h(__('login.mobile_conditions_desc')) ?></p>
                    </div>
                    <div class="bg-warm-50 rounded-xl p-4 text-center border border-warm-200">
                        <span class="text-2xl mb-2 block">❤️</span>
                        <p class="text-warm-900 text-sm font-medium"><?= h(__('login.mobile_favorites')) ?></p>
                        <p class="text-warm-500 text-xs mt-1"><?= h(__('login.mobile_favorites_desc')) ?></p>
                    </div>
                    <div class="bg-warm-50 rounded-xl p-4 text-center border border-warm-200">
                        <span class="text-2xl mb-2 block">🏆</span>
                        <p class="text-warm-900 text-sm font-medium"><?= h(__('login.mobile_badges')) ?></p>
                        <p class="text-warm-500 text-xs mt-1"><?= h(__('login.mobile_badges_desc')) ?></p>
                    </div>
                    <div class="bg-warm-50 rounded-xl p-4 text-center border border-warm-200">
                        <span class="text-2xl mb-2 block">👥</span>
                        <p class="text-warm-900 text-sm font-medium"><?= h(__('login.mobile_help')) ?></p>
                        <p class="text-warm-500 text-xs mt-1"><?= h(__('login.mobile_help_desc')) ?></p>
                    </div>
                </div>
            </div>

            <!-- Footer Links -->
            <div class="mt-8 pt-6 border-t border-warm-200 text-center space-y-4">
                <p class="text-xs text-warm-500">
                    <?= h(__('login.terms_agree')) ?>
                    <a href="/terms" class="text-sunset-400 hover:underline"><?= h(__('login.terms_link')) ?></a>
                    <?= h(__('login.terms_and')) ?>
                    <a href="/privacy" class="text-sunset-400 hover:underline"><?= h(__('login.privacy_link')) ?></a>
                </p>

                <a href="/" class="inline-flex items-center gap-2 text-sunset-400 hover:text-sunset-400 text-sm transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span><?= h(__('login.back_to_exploring')) ?></span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php include APP_ROOT . '/components/footer-minimal.php'; ?>
