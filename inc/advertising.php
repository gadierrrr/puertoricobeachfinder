<?php
/**
 * Direct advertising: package catalog, campaign eligibility, rendering,
 * signed measurement tokens, first-party metrics, and inventory conflicts.
 */

if (defined('ADVERTISING_PHP_INCLUDED')) {
    return;
}
define('ADVERTISING_PHP_INCLUDED', true);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/settings.php';

const ADVERTISING_ALLOWED_CATEGORIES = [
    'food' => ['en' => 'Food & drink', 'es' => 'Comida y bebida', 'icon' => '🍽️'],
    'tours' => ['en' => 'Tours & rentals', 'es' => 'Tours y alquileres', 'icon' => '🚤'],
    'surf' => ['en' => 'Surf & watersports', 'es' => 'Surf y deportes acuáticos', 'icon' => '🏄'],
    'shop' => ['en' => 'Shops', 'es' => 'Tiendas', 'icon' => '🛍️'],
    'lodging' => ['en' => 'Stays', 'es' => 'Hospedaje', 'icon' => '🏨'],
    'transport' => ['en' => 'Transportation', 'es' => 'Transportación', 'icon' => '🚐'],
    'wellness' => ['en' => 'Wellness', 'es' => 'Bienestar', 'icon' => '🌿'],
    'services' => ['en' => 'Local services', 'es' => 'Servicios locales', 'icon' => '📍'],
];

const ADVERTISING_CAMPAIGN_STATUSES = ['draft', 'pending_approval', 'scheduled', 'active', 'paused', 'ended', 'cancelled'];
const ADVERTISING_CREATIVE_STATUSES = ['draft', 'pending_review', 'approved', 'rejected', 'archived'];
const ADVERTISING_ASSIGNMENT_STATUSES = ['draft', 'active', 'paused', 'ended'];
const ADVERTISING_LEAD_STATUSES = ['new', 'qualified', 'proposal_sent', 'won', 'lost', 'spam'];

function advertisingEnabled(): bool
{
    return getSetting('advertising_enabled', '1') === '1';
}

function advertisingPackages(bool $activeOnly = true): array
{
    try {
        $sql = 'SELECT * FROM ad_packages';
        if ($activeOnly) {
            $sql .= ' WHERE status = "active"';
        }
        $sql .= ' ORDER BY display_order, price_cents';
        $rows = query($sql);
        return is_array($rows) ? $rows : [];
    } catch (Throwable $e) {
        return [];
    }
}

function advertisingPackageBySlug(string $slug): ?array
{
    try {
        return queryOne('SELECT * FROM ad_packages WHERE slug = :slug LIMIT 1', [':slug' => $slug]) ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function advertisingCategoryLabel(string $category, string $locale): string
{
    $entry = ADVERTISING_ALLOWED_CATEGORIES[$category] ?? ADVERTISING_ALLOWED_CATEGORIES['services'];
    return $entry[$locale === 'es' ? 'es' : 'en'];
}

function advertisingSecret(): string
{
    $secret = trim((string) env('APP_SECRET', ''));
    if ($secret !== '') {
        return hash_hmac('sha256', 'advertising-v1', $secret);
    }
    return hash('sha256', 'advertising-v1|' . (string) env('APP_URL', '') . '|' . (string) env('RESEND_API_KEY', ''));
}

function advertisingBase64UrlEncode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function advertisingBase64UrlDecode(string $value): string|false
{
    $value = strtr($value, '-_', '+/');
    $padding = strlen($value) % 4;
    if ($padding !== 0) {
        $value .= str_repeat('=', 4 - $padding);
    }
    return base64_decode($value, true);
}

function advertisingSignPayload(array $payload): string
{
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (!is_string($json)) {
        return '';
    }
    $body = advertisingBase64UrlEncode($json);
    $signature = advertisingBase64UrlEncode(hash_hmac('sha256', $body, advertisingSecret(), true));
    return $body . '.' . $signature;
}

function advertisingVerifyPayload(string $token): ?array
{
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
        return null;
    }
    $expected = advertisingBase64UrlEncode(hash_hmac('sha256', $parts[0], advertisingSecret(), true));
    if (!hash_equals($expected, $parts[1])) {
        return null;
    }
    $json = advertisingBase64UrlDecode($parts[0]);
    $payload = is_string($json) ? json_decode($json, true) : null;
    if (!is_array($payload)) {
        return null;
    }
    $expires = (int) ($payload['e'] ?? 0);
    if ($expires < time() || $expires > time() + 86400 * 8) {
        return null;
    }
    return $payload;
}

function advertisingLeadFormToken(): string
{
    return advertisingSignPayload([
        'purpose' => 'lead',
        'r' => time(),
        'e' => time() + 14400,
        'n' => bin2hex(random_bytes(8)),
    ]);
}

function advertisingValidateLeadFormToken(string $token): bool
{
    $payload = advertisingVerifyPayload($token);
    if (!$payload || ($payload['purpose'] ?? '') !== 'lead') {
        return false;
    }
    $rendered = (int) ($payload['r'] ?? 0);
    return $rendered > 0 && time() - $rendered >= 3 && time() - $rendered <= 14400;
}

function advertisingAssignmentCurrentlyActive(array $row): bool
{
    if (!advertisingEnabled()) {
        return false;
    }
    if (!in_array((string) ($row['campaign_status'] ?? ''), ['active', 'scheduled'], true)) {
        return false;
    }
    if (($row['creative_status'] ?? '') !== 'approved' || ($row['assignment_status'] ?? '') !== 'active') {
        return false;
    }
    $now = gmdate('Y-m-d H:i:s');
    foreach (['campaign_starts_at', 'assignment_starts_at'] as $key) {
        $value = trim((string) ($row[$key] ?? ''));
        if ($value !== '' && $now < $value) {
            return false;
        }
    }
    foreach (['campaign_ends_at', 'assignment_ends_at'] as $key) {
        $value = trim((string) ($row[$key] ?? ''));
        if ($value !== '' && $now > $value) {
            return false;
        }
    }
    return true;
}

function advertisingAssignmentMatchesTarget(array $row, string $pageType, string $pageKey, array $context): bool
{
    $targetType = (string) ($row['target_type'] ?? '');
    $targetKey = (string) ($row['target_key'] ?? '');
    if ($targetType === 'global') {
        return true;
    }
    if ($targetType === $pageType && hash_equals($targetKey, $pageKey)) {
        return true;
    }
    foreach (['municipality', 'region', 'category'] as $contextKey) {
        if ($targetType === $contextKey && isset($context[$contextKey]) && hash_equals($targetKey, (string) $context[$contextKey])) {
            return true;
        }
    }
    return false;
}

function advertisingAssignmentsForSlot(string $slotKey, string $pageType, string $pageKey, string $locale, array $context = []): array
{
    if (!advertisingEnabled()) {
        return [];
    }
    try {
        $rows = query(
            'SELECT a.id AS assignment_id,a.target_type,a.target_key,a.locale AS assignment_locale,
                    a.priority,a.display_order,a.status AS assignment_status,
                    a.starts_at AS assignment_starts_at,a.ends_at AS assignment_ends_at,
                    s.slot_key,s.max_items,s.exclusive,s.disclosure_en,s.disclosure_es,
                    c.id AS campaign_id,c.name AS campaign_name,c.status AS campaign_status,
                    c.starts_at AS campaign_starts_at,c.ends_at AS campaign_ends_at,
                    cr.id AS creative_id,cr.creative_type,cr.headline_en,cr.headline_es,
                    cr.body_en,cr.body_es,cr.image_url,cr.alt_en,cr.alt_es,
                    cr.destination_url,cr.action_label_en,cr.action_label_es,
                    cr.phone,cr.whatsapp,cr.instagram,cr.status AS creative_status,
                    adv.id AS advertiser_id,adv.business_name,adv.category
             FROM ad_assignments a
             INNER JOIN ad_slots s ON s.id=a.slot_id
             INNER JOIN ad_campaigns c ON c.id=a.campaign_id
             INNER JOIN ad_creatives cr ON cr.id=a.creative_id
             INNER JOIN advertisers adv ON adv.id=c.advertiser_id
             WHERE s.slot_key=:slot AND s.page_type=:page_type AND s.status="active"
               AND a.status="active" AND (a.locale="all" OR a.locale=:locale)
             ORDER BY a.priority ASC,a.display_order ASC,a.created_at ASC',
            [':slot' => $slotKey, ':page_type' => $pageType, ':locale' => $locale === 'es' ? 'es' : 'en']
        );
    } catch (Throwable $e) {
        return [];
    }
    if (!is_array($rows)) {
        return [];
    }
    $eligible = array_values(array_filter($rows, static function (array $row) use ($pageType, $pageKey, $context): bool {
        return advertisingAssignmentCurrentlyActive($row)
            && advertisingAssignmentMatchesTarget($row, $pageType, $pageKey, $context);
    }));
    if ($eligible === []) {
        return [];
    }
    $limit = max(1, min(2, (int) ($eligible[0]['max_items'] ?? 1)));
    if (!empty($eligible[0]['exclusive'])) {
        $limit = 1;
    }
    return array_slice($eligible, 0, $limit);
}

function advertisingEventToken(array $assignment, string $pageType, string $pageKey, string $locale, string $action = '', ?string $nonce = null): string
{
    return advertisingSignPayload([
        'purpose' => 'event',
        'a' => (string) $assignment['assignment_id'],
        'p' => $pageType,
        'k' => $pageKey,
        'l' => $locale === 'es' ? 'es' : 'en',
        'x' => $action,
        'n' => $nonce ?: bin2hex(random_bytes(8)),
        'e' => time() + 86400 * 7,
    ]);
}

function advertisingActionTarget(array $creative, string $action): string
{
    if ($action === 'website') {
        $url = trim((string) ($creative['destination_url'] ?? ''));
        return preg_match('~^https?://~i', $url) ? $url : '';
    }
    if ($action === 'instagram') {
        $handle = ltrim(trim((string) ($creative['instagram'] ?? '')), '@');
        return preg_match('/^[A-Za-z0-9._]{1,30}$/', $handle) ? 'https://www.instagram.com/' . $handle . '/' : '';
    }
    if ($action === 'whatsapp') {
        $number = preg_replace('/[^0-9]/', '', (string) ($creative['whatsapp'] ?? ''));
        return $number !== '' ? 'https://wa.me/' . $number : '';
    }
    if ($action === 'call') {
        $number = preg_replace('/[^0-9+]/', '', (string) ($creative['phone'] ?? ''));
        return $number !== '' ? 'tel:' . $number : '';
    }
    return '';
}

function advertisingActions(array $creative, string $locale, int $limit = 2): array
{
    $actions = [];
    if (advertisingActionTarget($creative, 'website') !== '') {
        $custom = trim((string) ($creative[$locale === 'es' ? 'action_label_es' : 'action_label_en'] ?? ''));
        $actions[] = ['website', $custom !== '' ? $custom : ($locale === 'es' ? 'Visitar sitio' : 'Visit website')];
    }
    if (advertisingActionTarget($creative, 'whatsapp') !== '') {
        $actions[] = ['whatsapp', 'WhatsApp'];
    }
    if (advertisingActionTarget($creative, 'call') !== '') {
        $actions[] = ['call', $locale === 'es' ? 'Llamar' : 'Call'];
    }
    if (advertisingActionTarget($creative, 'instagram') !== '') {
        $actions[] = ['instagram', 'Instagram'];
    }
    return array_slice($actions, 0, $limit);
}

function advertisingRenderSlot(string $slotKey, string $pageType, string $pageKey, string $locale = 'en', array $context = []): string
{
    $locale = $locale === 'es' ? 'es' : 'en';
    $assignments = advertisingAssignmentsForSlot($slotKey, $pageType, $pageKey, $locale, $context);
    if ($assignments === []) {
        return '';
    }
    $disclosure = $locale === 'es' ? (string) $assignments[0]['disclosure_es'] : (string) $assignments[0]['disclosure_en'];
    $cards = '';
    foreach ($assignments as $assignment) {
        $headline = trim((string) ($locale === 'es' ? ($assignment['headline_es'] ?: $assignment['headline_en']) : $assignment['headline_en']));
        $body = trim((string) ($locale === 'es' ? ($assignment['body_es'] ?: $assignment['body_en']) : $assignment['body_en']));
        $alt = trim((string) ($locale === 'es' ? ($assignment['alt_es'] ?: $assignment['alt_en']) : $assignment['alt_en']));
        $nonce = bin2hex(random_bytes(8));
        $impressionToken = advertisingEventToken($assignment, $pageType, $pageKey, $locale, '', $nonce);
        $actions = advertisingActions($assignment, $locale, $pageType === 'beach' ? 2 : 1);
        $links = '';
        foreach ($actions as [$action, $label]) {
            $clickToken = advertisingEventToken($assignment, $pageType, $pageKey, $locale, $action, $nonce);
            $links .= '<a class="ad-card__action" href="/ad-out?t=' . rawurlencode($clickToken) . '" rel="sponsored nofollow noopener"'
                . ($action === 'call' ? '' : ' target="_blank"')
                . ' data-bf-track="ad-click" data-ad-campaign-id="' . h($assignment['campaign_id']) . '"'
                . ' data-ad-creative-id="' . h($assignment['creative_id']) . '" data-ad-slot="' . h($slotKey) . '"'
                . ' data-ad-action="' . h($action) . '">' . h($label) . '</a>';
        }
        $image = trim((string) ($assignment['image_url'] ?? ''));
        $icon = ADVERTISING_ALLOWED_CATEGORIES[$assignment['category'] ?? 'services']['icon'] ?? '📍';
        $cards .= '<article class="ad-card ad-card--' . h($pageType) . '" data-bf-track="ad-impression"'
            . ' data-ad-impression-token="' . h($impressionToken) . '" data-ad-campaign-id="' . h($assignment['campaign_id']) . '"'
            . ' data-ad-creative-id="' . h($assignment['creative_id']) . '" data-ad-slot="' . h($slotKey) . '">'
            . ($image !== ''
                ? '<img class="ad-card__image" src="' . h($image) . '" alt="' . h($alt !== '' ? $alt : $headline) . '" width="320" height="220" loading="lazy">'
                : '<div class="ad-card__image ad-card__image--fallback" aria-hidden="true">' . $icon . '</div>')
            . '<div class="ad-card__body">'
            . ($pageType === 'beach' ? '<div class="ad-card__label">' . h($disclosure) . '</div>' : '')
            . '<h3>' . h($headline) . '</h3>'
            . ($body !== '' ? '<p>' . h($body) . '</p>' : '')
            . ($links !== '' ? '<div class="ad-card__actions">' . $links . '</div>' : '')
            . '</div></article>';
    }

    if ($pageType === 'beach') {
        $title = $locale === 'es' ? 'Negocios cercanos' : 'Nearby businesses';
        return '<section id="local" class="block ad-unit ad-unit--beach" aria-label="' . h($title) . '">'
            . '<div class="ad-unit__heading"><h2 class="h2">' . h($title) . '</h2><span>' . h($disclosure) . '</span></div>'
            . '<div class="ad-unit__grid">' . $cards . '</div></section>';
    }

    $title = $pageType === 'guide'
        ? ($locale === 'es' ? 'Patrocinador de esta guía' : 'Guide sponsor')
        : ($locale === 'es' ? 'Patrocinador de esta colección' : 'Collection sponsor');
    $sponsorId = $pageType === 'guide' ? 'guide-sponsor' : 'collection-sponsor';
    return '<aside id="' . h($sponsorId) . '" class="ad-unit ad-unit--sponsor ad-unit--' . h($pageType) . '" aria-label="' . h($title) . '">'
        . '<div class="ad-unit__heading"><strong>' . h($title) . '</strong><span>' . h($disclosure) . '</span></div>'
        . $cards . '</aside>';
}

function advertisingAssignmentById(string $assignmentId): ?array
{
    try {
        $row = queryOne(
            'SELECT a.id AS assignment_id,a.status AS assignment_status,
                    a.starts_at AS assignment_starts_at,a.ends_at AS assignment_ends_at,
                    s.slot_key,c.id AS campaign_id,c.status AS campaign_status,
                    c.starts_at AS campaign_starts_at,c.ends_at AS campaign_ends_at,
                    cr.id AS creative_id,cr.status AS creative_status,cr.destination_url,
                    cr.phone,cr.whatsapp,cr.instagram
             FROM ad_assignments a
             INNER JOIN ad_slots s ON s.id=a.slot_id
             INNER JOIN ad_campaigns c ON c.id=a.campaign_id
             INNER JOIN ad_creatives cr ON cr.id=a.creative_id
             WHERE a.id=:id LIMIT 1',
            [':id' => $assignmentId]
        );
        return is_array($row) ? $row : null;
    } catch (Throwable $e) {
        return null;
    }
}

function advertisingVisitorHash(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    return hash_hmac('sha256', gmdate('Y-m-d') . '|' . $ip . '|' . $ua, advertisingSecret());
}

function advertisingRecordEvent(array $payload, string $eventType): array
{
    if (($payload['purpose'] ?? '') !== 'event' || !in_array($eventType, ['impression', 'click'], true)) {
        return ['ok' => false, 'status' => 400];
    }
    $assignment = advertisingAssignmentById((string) ($payload['a'] ?? ''));
    if (!$assignment || !advertisingAssignmentCurrentlyActive($assignment)) {
        return ['ok' => false, 'status' => 404];
    }
    $action = $eventType === 'click' ? trim((string) ($payload['x'] ?? '')) : '';
    if ($eventType === 'click' && !in_array($action, ['website', 'instagram', 'call', 'whatsapp'], true)) {
        return ['ok' => false, 'status' => 400];
    }
    $pageType = mb_substr(trim((string) ($payload['p'] ?? '')), 0, 40);
    $pageKey = mb_substr(trim((string) ($payload['k'] ?? '')), 0, 300);
    $locale = ($payload['l'] ?? '') === 'es' ? 'es' : 'en';
    $nonce = mb_substr(trim((string) ($payload['n'] ?? '')), 0, 64);
    if ($pageType === '' || $pageKey === '' || $nonce === '') {
        return ['ok' => false, 'status' => 400];
    }
    // The nonce alone is NOT enough to scope a duplicate. It is minted per render
    // (advertisingRenderSlot), and beach/guide/collection pages are edge-cached for
    // anonymous visitors (inc/security_headers.php), so one cached copy — and therefore
    // one nonce — is served to every visitor for the life of the cache entry. Keying
    // only on it would collapse every visitor's click into a single billable event.
    // The visitor hash is a daily-rotating HMAC of IP+UA, so including it dedupes per
    // visitor per day, which is the intent, and stays correct under caching.
    $visitorHash = advertisingVisitorHash();
    $dedupeKey = hash_hmac('sha256', implode('|', [$eventType, $assignment['assignment_id'], $pageType, $pageKey, $nonce, $action, $visitorHash]), advertisingSecret());
    $id = uuid();
    $inserted = execute(
        'INSERT OR IGNORE INTO ad_events
            (id,event_type,assignment_id,campaign_id,creative_id,slot_key,page_type,page_key,
             locale,action,visitor_hash,dedupe_key,is_valid,occurred_at)
         VALUES (:id,:event_type,:assignment_id,:campaign_id,:creative_id,:slot_key,:page_type,
                 :page_key,:locale,:action,:visitor_hash,:dedupe_key,1,CURRENT_TIMESTAMP)',
        [
            ':id' => $id, ':event_type' => $eventType,
            ':assignment_id' => $assignment['assignment_id'], ':campaign_id' => $assignment['campaign_id'],
            ':creative_id' => $assignment['creative_id'], ':slot_key' => $assignment['slot_key'],
            ':page_type' => $pageType, ':page_key' => $pageKey, ':locale' => $locale,
            ':action' => $action, ':visitor_hash' => $visitorHash, ':dedupe_key' => $dedupeKey,
        ]
    );
    if (!$inserted || getDb()->changes() === 0) {
        return ['ok' => true, 'duplicate' => true, 'event_id' => null, 'assignment' => $assignment];
    }
    $date = (new DateTimeImmutable('now', new DateTimeZone('America/Puerto_Rico')))->format('Y-m-d');
    execute(
        'INSERT INTO ad_metrics_daily
            (metric_date,assignment_id,campaign_id,creative_id,slot_key,page_type,page_key,
             locale,event_type,action,valid_events,invalid_events,updated_at)
         VALUES (:date,:assignment_id,:campaign_id,:creative_id,:slot_key,:page_type,:page_key,
                 :locale,:event_type,:action,1,0,CURRENT_TIMESTAMP)
         ON CONFLICT(metric_date,assignment_id,page_key,locale,event_type,action)
         DO UPDATE SET valid_events=valid_events+1,updated_at=CURRENT_TIMESTAMP',
        [
            ':date' => $date, ':assignment_id' => $assignment['assignment_id'],
            ':campaign_id' => $assignment['campaign_id'], ':creative_id' => $assignment['creative_id'],
            ':slot_key' => $assignment['slot_key'], ':page_type' => $pageType, ':page_key' => $pageKey,
            ':locale' => $locale, ':event_type' => $eventType, ':action' => $action,
        ]
    );
    return ['ok' => true, 'duplicate' => false, 'event_id' => $id, 'assignment' => $assignment];
}

/**
 * Could two targets ever select the same page?
 *
 * Mirrors advertisingAssignmentMatchesTarget(). Deliberately conservative: when
 * two different non-global target types are involved we cannot prove they are
 * disjoint without resolving every page (a municipality target and a beach
 * target collide on any beach in that municipality), so we report an overlap.
 * On an exclusive slot a false positive costs an admin one extra decision; a
 * false negative sells the same exclusive inventory twice.
 */
function advertisingTargetsOverlap(string $typeA, string $keyA, string $typeB, string $keyB): bool
{
    if ($typeA === 'global' || $typeB === 'global') {
        return true;
    }
    if ($typeA === $typeB) {
        return hash_equals($keyA, $keyB);
    }
    return true;
}

function advertisingAssignmentConflict(string $slotId, string $targetType, string $targetKey, string $locale, ?string $startsAt, ?string $endsAt, string $excludeId = ''): ?array
{
    try {
        $slot = queryOne('SELECT exclusive FROM ad_slots WHERE id=:id', [':id' => $slotId]);
        if (!$slot || empty($slot['exclusive'])) {
            return null;
        }
        // Do NOT filter on target_type/target_key in SQL. Two assignments can
        // collide on an exclusive slot without sharing a target tuple —
        // advertisingAssignmentMatchesTarget() treats target_type="global" as
        // matching every page, so a global assignment silently coexisted with a
        // guide-scoped one. Both then matched the same page, the exclusive slot
        // capped rendering at one, and whichever lost on priority was billed for
        // an exclusive placement that never appeared. Pull every candidate on the
        // slot and decide overlap in PHP instead.
        //
        // "paused" is included because a paused assignment can be resumed, which
        // would resurrect the same collision.
        $rows = query(
            'SELECT a.id,a.target_type,a.target_key,c.name FROM ad_assignments a
             INNER JOIN ad_campaigns c ON c.id=a.campaign_id
             WHERE a.slot_id=:slot
               AND (a.locale="all" OR :locale="all" OR a.locale=:locale)
               AND a.status IN ("active","draft","paused") AND a.id<>:exclude',
            [':slot' => $slotId, ':locale' => $locale, ':exclude' => $excludeId]
        );
    } catch (Throwable $e) {
        return null;
    }
    foreach ($rows ?: [] as $row) {
        if (!advertisingTargetsOverlap($targetType, $targetKey, (string) $row['target_type'], (string) $row['target_key'])) {
            continue;
        }
        $existing = queryOne('SELECT starts_at,ends_at FROM ad_assignments WHERE id=:id', [':id' => $row['id']]);
        $existingStart = trim((string) ($existing['starts_at'] ?? '')) ?: '0000-01-01 00:00:00';
        $existingEnd = trim((string) ($existing['ends_at'] ?? '')) ?: '9999-12-31 23:59:59';
        $newStart = $startsAt ?: '0000-01-01 00:00:00';
        $newEnd = $endsAt ?: '9999-12-31 23:59:59';
        if ($newStart <= $existingEnd && $newEnd >= $existingStart) {
            return $row;
        }
    }
    return null;
}

function advertisingAudit(string $entityType, string $entityId, string $action, ?array $before = null, ?array $after = null): void
{
    try {
        execute(
            'INSERT INTO ad_audit_log (id,actor_user_id,entity_type,entity_id,action,before_json,after_json)
             VALUES (:id,:actor,:type,:entity,:action,:before,:after)',
            [
                ':id' => uuid(), ':actor' => $_SESSION['user_id'] ?? null, ':type' => $entityType,
                ':entity' => $entityId, ':action' => $action,
                ':before' => $before ? json_encode($before, JSON_UNESCAPED_SLASHES) : null,
                ':after' => $after ? json_encode($after, JSON_UNESCAPED_SLASHES) : null,
            ]
        );
    } catch (Throwable $e) {
        error_log('advertising audit failed: ' . $e->getMessage());
    }
}
