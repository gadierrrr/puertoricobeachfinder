<?php
/**
 * Web Push foundation (Sprint 4 item 16, Phase 1).
 *
 * The RECEIVE side is already done: public/sw.js has `push` + `notificationclick`
 * handlers. This file adds subscription storage + config. The SEND side is a documented
 * STUB: encrypted Web Push (VAPID ES256 JWT + aes128gcm/ECDH) needs a crypto library
 * (e.g. minishlink/web-push via Composer), which this Composer-free project doesn't have.
 * Do NOT hand-roll the crypto. See pushSend() below.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/env.php';

function pushVapidPublicKey(): string {
    return (string) env('VAPID_PUBLIC_KEY', '');
}

function pushIsConfigured(): bool {
    return pushVapidPublicKey() !== '';
}

/**
 * Upsert a browser PushSubscription: $sub = { endpoint, keys: { p256dh, auth } }.
 */
function pushStoreSubscription(?string $userId, array $sub): bool {
    $endpoint = (string) ($sub['endpoint'] ?? '');
    $p256dh   = (string) ($sub['keys']['p256dh'] ?? '');
    $auth     = (string) ($sub['keys']['auth'] ?? '');
    if ($endpoint === '' || $p256dh === '' || $auth === '') {
        return false;
    }
    $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 400);

    if (queryOne('SELECT id FROM push_subscriptions WHERE endpoint = :e', [':e' => $endpoint])) {
        execute(
            'UPDATE push_subscriptions SET user_id = :u, p256dh = :p, auth = :a, ua = :ua, last_used_at = datetime("now") WHERE endpoint = :e',
            [':u' => $userId, ':p' => $p256dh, ':a' => $auth, ':ua' => $ua, ':e' => $endpoint]
        );
    } else {
        execute(
            'INSERT INTO push_subscriptions (id, user_id, endpoint, p256dh, auth, ua, created_at) VALUES (:id, :u, :e, :p, :a, :ua, datetime("now"))',
            [':id' => uuid(), ':u' => $userId, ':e' => $endpoint, ':p' => $p256dh, ':a' => $auth, ':ua' => $ua]
        );
    }
    return true;
}

function pushDeleteSubscription(string $endpoint): void {
    execute('DELETE FROM push_subscriptions WHERE endpoint = :e', [':e' => $endpoint]);
}

/**
 * STUB — send one encrypted Web Push message. Returns false (not implemented).
 *
 * To enable: add a Web Push library (minishlink/web-push via Composer), read the VAPID
 * keypair from env (VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY), build a WebPush client, and
 * send $payload (JSON: {title, body, url}) to $subscription. On a 404/410 response,
 * call pushDeleteSubscription($subscription['endpoint']) to prune the dead endpoint.
 * Then implement scripts/send-condition-push.php + a prod cron.
 */
function pushSend(array $subscription, array $payload): bool {
    return false;
}
