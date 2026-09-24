<?php
/** Verified outcomes are queued by the server, never inferred from query strings. */
function queueAnalyticsOutcome(string $name, array $props = []): void
{
    if (!in_array($name, ['sign_up', 'generate_lead'], true)) {
        throw new InvalidArgumentException('Unsupported analytics outcome');
    }
    require_once __DIR__ . '/session.php';
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    // Only low-cardinality context; no email, name, lead ID, token or message.
    $props = array_intersect_key($props, array_flip(['method', 'source', 'package_slug', 'category']));
    $_SESSION['analytics_outcomes'][] = [
        'name' => $name, 'props' => $props, 'id' => bin2hex(random_bytes(16)),
    ];
}

function takeAnalyticsOutcomes(): array
{
    $events = $_SESSION['analytics_outcomes'] ?? [];
    unset($_SESSION['analytics_outcomes']);
    return is_array($events) ? $events : [];
}
