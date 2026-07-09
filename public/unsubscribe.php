<?php
/**
 * One-click unsubscribe from the weekly digest (HMAC-token verified; no login needed).
 * Turns off user_preferences.weekly_digest for the matching user AND suppresses the
 * contact in the email pipeline (belt-and-suspenders / CAN-SPAM).
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/email.php';

$email = trim((string) ($_GET['e'] ?? ''));
$token = trim((string) ($_GET['t'] ?? ''));

// When the signed link is opened, process it then 302 to a CLEAN url (no email in the
// query) BEFORE any analytics-instrumented page renders — so the recipient's email is
// never captured by GA/PostHog / session replay.
if ($email !== '' || $token !== '') {
    $ok = false;
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && emailVerifyUnsubscribeToken($email, $token)) {
        $ok = true;
        $u = queryOne('SELECT id FROM users WHERE email = :e', [':e' => $email]);
        if ($u) {
            execute(
                'UPDATE user_preferences SET weekly_digest = 0, updated_at = datetime("now") WHERE user_id = :id',
                [':id' => $u['id']]
            );
            if (getDB()->changes() === 0) {
                @execute(
                    'INSERT OR IGNORE INTO user_preferences (user_id, weekly_digest, notifications_enabled, updated_at) VALUES (:id, 0, 1, datetime("now"))',
                    [':id' => $u['id']]
                );
            }
        }
        emailUpsertContactState($email, ['unsubscribed' => true, 'suppressed_reason' => 'user_unsub']);
    }
    header('Location: /unsubscribe?ok=' . ($ok ? '1' : '0'), true, 302);
    exit;
}

$ok = (($_GET['ok'] ?? '') === '1');

$pageTitle = 'Unsubscribe';
$skipMapCSS = true;
$robotsOverride = 'noindex, nofollow';
$redesignLayout = useRedesign();
$bodyClasses = trim(($bodyClasses ?? '') . ' rd-legal');
include APP_ROOT . '/components/header.php';
?>
<div class="max-w-md mx-auto px-4 py-20 text-center managed-page-hero page-heading-hero"<?= pageHeroAttributes('legal') ?>>
    <?php if ($ok): ?>
    <div class="text-6xl mb-4">✅</div>
    <h1 class="text-2xl font-bold text-warm-900 mb-2">You're unsubscribed</h1>
    <p class="text-warm-500 mb-6">You won't receive the weekly beach digest anymore. You can re-enable it anytime from your profile.</p>
    <?php else: ?>
    <div class="text-6xl mb-4">⚠️</div>
    <h1 class="text-2xl font-bold text-warm-900 mb-2">Invalid link</h1>
    <p class="text-warm-500 mb-6">This unsubscribe link is invalid or has expired.</p>
    <?php endif; ?>
    <a href="/" class="inline-block bg-sunset-400 hover:bg-sunset-300 text-ocean-900 px-6 py-3 rounded-lg font-medium">Back to beaches</a>
</div>
<?php include APP_ROOT . '/components/footer.php'; ?>
