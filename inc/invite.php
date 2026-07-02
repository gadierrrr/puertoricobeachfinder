<?php
/**
 * User-to-user invite / referral loop (Sprint 4 item 17).
 * SEPARATE from the affiliate system in inc/referrals.php / migration 023.
 *
 * Flow: a visitor lands on /?ref=CODE -> code captured to session -> they sign up
 * (Google OAuth or magic link, same session) -> the new account is attributed to the
 * referrer (write-once) -> when the referred user completes onboarding, the referral
 * is marked completed and the referrer is (re)awarded achievement badges.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function inviteValidateCode(string $code): string {
    return preg_match('/^[A-Za-z0-9]{4,16}$/', $code) ? $code : '';
}

/**
 * Return the user's referral code, generating a unique one lazily if absent.
 */
function inviteEnsureCode(string $userId): string {
    $u = queryOne('SELECT referral_code FROM users WHERE id = :id', [':id' => $userId]);
    if ($u && !empty($u['referral_code'])) {
        return (string) $u['referral_code'];
    }
    for ($i = 0; $i < 10; $i++) {
        $code = substr(strtoupper(bin2hex(random_bytes(5))), 0, 7);
        if (queryOne('SELECT id FROM users WHERE referral_code = :c', [':c' => $code])) {
            continue;
        }
        execute(
            'UPDATE users SET referral_code = :c WHERE id = :id AND (referral_code IS NULL OR referral_code = "")',
            [':c' => $code, ':id' => $userId]
        );
        $again = queryOne('SELECT referral_code FROM users WHERE id = :id', [':id' => $userId]);
        if ($again && !empty($again['referral_code'])) {
            return (string) $again['referral_code'];
        }
    }
    return '';
}

/**
 * Capture a ?ref=CODE from the current request into a cookie (guests only). A cookie
 * (not the session) is used so attribution survives the OAuth redirect / magic-link
 * click in the same browser even on pages that don't start a session (e.g. the homepage).
 */
function inviteCaptureRefFromRequest(): void {
    if (empty($_GET['ref'])) {
        return;
    }
    $code = inviteValidateCode(trim((string) $_GET['ref']));
    if ($code === '') {
        return;
    }
    // Skip only if a session is actually active AND the user is signed in. On the sessionless
    // homepage this is a no-op, and that's fine: attribution is gated to genuinely-new signups
    // (inviteAttribute is only called in the new-user branches, with a self-referral guard), so
    // a stray cookie on a logged-in user's browser is never acted on.
    if (session_status() === PHP_SESSION_ACTIVE && function_exists('isAuthenticated') && isAuthenticated()) {
        return;
    }
    if (!headers_sent()) {
        $secure = function_exists('getRequestScheme')
            ? getRequestScheme() === 'https'
            : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        setcookie('bf_ref', $code, [
            'expires' => time() + 30 * 24 * 60 * 60,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    $_COOKIE['bf_ref'] = $code;
}

function inviteClearRefCookie(): void {
    if (!headers_sent()) {
        setcookie('bf_ref', '', ['expires' => time() - 3600, 'path' => '/']);
    }
    unset($_COOKIE['bf_ref']);
}

/**
 * Resolve the referrer behind the current request's bf_ref cookie, used to decide
 * whether to show a guest the "you were invited" signup prompt.
 *
 * Returns:
 *   - null   : no ref cookie, or the code is invalid / matches no real user
 *              (caller shows nothing referral-specific)
 *   - ''     : a valid referrer that has no display name (show a generic invite prompt)
 *   - 'Ana'  : the referrer's first name (show a personalized invite prompt)
 */
function inviteReferrerName(): ?string {
    $code = inviteValidateCode((string) ($_COOKIE['bf_ref'] ?? ''));
    if ($code === '') {
        return null;
    }
    $referrer = queryOne('SELECT name FROM users WHERE referral_code = :c', [':c' => $code]);
    if (!$referrer) {
        return null;
    }
    $name = trim((string) ($referrer['name'] ?? ''));
    if ($name === '') {
        return '';
    }
    $parts = preg_split('/\s+/', $name) ?: [$name];
    return $parts[0] !== '' ? $parts[0] : '';
}

/**
 * Attribute a newly-created user to a referrer (write-once). Uses the bf_ref cookie
 * unless an explicit code is passed. No-ops on self-referral or unknown code.
 */
function inviteAttribute(string $newUserId, ?string $code = null): void {
    $code = inviteValidateCode((string) ($code ?: ($_COOKIE['bf_ref'] ?? '')));
    if ($code === '' || $newUserId === '') {
        return;
    }
    $referrer = queryOne('SELECT id FROM users WHERE referral_code = :c', [':c' => $code]);
    if (!$referrer || $referrer['id'] === $newUserId) {
        inviteClearRefCookie();
        return;
    }
    // Write-once referred_by.
    execute(
        'UPDATE users SET referred_by = :r WHERE id = :id AND (referred_by IS NULL OR referred_by = "")',
        [':r' => $referrer['id'], ':id' => $newUserId]
    );
    // Record the (pending) referral edge; UNIQUE(referred_user_id) makes it idempotent.
    @execute(
        'INSERT OR IGNORE INTO user_referrals (id, referrer_user_id, referred_user_id, code, ip_hash, ua_hash, status, created_at)
         VALUES (:id, :rr, :ru, :c, :ip, :ua, "pending", datetime("now"))',
        [
            ':id' => uuid(),
            ':rr' => $referrer['id'],
            ':ru' => $newUserId,
            ':c' => $code,
            ':ip' => hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            ':ua' => hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''),
        ]
    );
    inviteClearRefCookie();
}

/**
 * Mark a referral completed (fires once) when the referred user does a meaningful
 * action (onboarding). Re-awards the referrer's achievement badges.
 */
function inviteMarkCompleted(string $userId): void {
    $ref = queryOne('SELECT id, referrer_user_id, status FROM user_referrals WHERE referred_user_id = :id', [':id' => $userId]);
    if (!$ref || $ref['status'] === 'completed') {
        return;
    }
    execute('UPDATE user_referrals SET status = "completed", rewarded_at = datetime("now") WHERE id = :id', [':id' => $ref['id']]);
    if (function_exists('awardAchievements')) {
        awardAchievements($ref['referrer_user_id']);
    }
}
