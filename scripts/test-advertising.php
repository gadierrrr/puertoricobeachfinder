#!/usr/bin/env php
<?php
/** Advertising platform integration tests. Run against a disposable DB. */

require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/advertising.php';

$checks = 0;
$failures = [];
$ids = [];

function adTest(bool $condition, string $message): void
{
    global $checks, $failures;
    $checks++;
    if (!$condition) {
        $failures[] = $message;
    }
}

function adTestRemember(string $table, string $id): void
{
    global $ids;
    $ids[$table][] = $id;
}

function adTestCleanup(): void
{
    global $ids;
    foreach (['ad_events', 'ad_metrics_daily', 'ad_assignments', 'ad_creatives', 'ad_campaigns', 'advertisers'] as $table) {
        foreach (array_reverse($ids[$table] ?? []) as $id) {
            $column = $table === 'ad_metrics_daily' ? 'assignment_id' : 'id';
            execute("DELETE FROM {$table} WHERE {$column}=:id", [':id' => $id]);
        }
    }
}

try {
    setSetting('advertising_enabled', '1');
    $packages = advertisingPackages();
    adTest(count($packages) === 5, 'Expected five active packages');
    $bySlug = array_column($packages, null, 'slug');
    adTest((int) ($bySlug['standard']['price_cents'] ?? 0) === 4900, 'Standard price should be $49');
    adTest((int) ($bySlug['sponsored-guide']['minimum_term_months'] ?? 0) === 3, 'Guide package should have a three-month minimum');
    adTest(!empty($bySlug['collection-sponsor']['exclusive']), 'Collection sponsor should be exclusive');

    $beach = queryOne('SELECT slug FROM beaches WHERE publish_status="published" LIMIT 1');
    adTest(is_array($beach) && !empty($beach['slug']), 'Expected a published beach');
    $beachSlug = (string) ($beach['slug'] ?? '');

    $advertiserId = uuid();
    execute('INSERT INTO advertisers (id,business_name,category,status) VALUES (:id,"QA Mango Hut","food","active")', [':id' => $advertiserId]);
    adTestRemember('advertisers', $advertiserId);

    $campaignId = uuid();
    execute(
        'INSERT INTO ad_campaigns
            (id,advertiser_id,package_id,name,status,starts_at,ends_at,contracted_amount_cents,billing_status,approved_at)
         VALUES (:id,:advertiser,"adpkg-standard","QA campaign","active",datetime("now","-1 day"),datetime("now","+7 day"),4900,"paid",CURRENT_TIMESTAMP)',
        [':id' => $campaignId, ':advertiser' => $advertiserId]
    );
    adTestRemember('ad_campaigns', $campaignId);

    $creativeId = uuid();
    execute(
        'INSERT INTO ad_creatives
            (id,advertiser_id,name,creative_type,headline_en,headline_es,body_en,body_es,
             destination_url,whatsapp,status,approved_at)
         VALUES (:id,:advertiser,"QA card","listing_card","Mango Hut","Casa de Mango",
                 "Cold juice near the beach.","Jugo frío cerca de la playa.",
                 "https://example.com/mango","17875550123","approved",CURRENT_TIMESTAMP)',
        [':id' => $creativeId, ':advertiser' => $advertiserId]
    );
    adTestRemember('ad_creatives', $creativeId);

    $assignmentId = uuid();
    execute(
        'INSERT INTO ad_assignments
            (id,campaign_id,creative_id,slot_id,target_type,target_key,locale,status,starts_at,ends_at)
         VALUES (:id,:campaign,:creative,"adslot-beach-local","beach",:key,"all","active",datetime("now","-1 day"),datetime("now","+7 day"))',
        [':id' => $assignmentId, ':campaign' => $campaignId, ':creative' => $creativeId, ':key' => $beachSlug]
    );
    adTestRemember('ad_assignments', $assignmentId);
    adTestRemember('ad_metrics_daily', $assignmentId);

    $html = advertisingRenderSlot('beach.local-partners', 'beach', $beachSlug, 'en');
    adTest(str_contains($html, 'Paid advertisement'), 'English unit should disclose paid advertising');
    adTest(str_contains($html, 'Nearby businesses'), 'English beach unit should use neutral heading');
    adTest(substr_count($html, 'class="ad-card ') === 1, 'Expected one rendered ad card');
    adTest(str_contains($html, 'rel="sponsored nofollow noopener"'), 'Paid link should use sponsored rel');

    preg_match('/data-ad-impression-token="([^"]+)"/', $html, $impressionMatch);
    $impressionToken = html_entity_decode((string) ($impressionMatch[1] ?? ''), ENT_QUOTES, 'UTF-8');
    $impressionPayload = advertisingVerifyPayload($impressionToken);
    adTest(is_array($impressionPayload), 'Impression token should verify');
    $firstImpression = advertisingRecordEvent((array) $impressionPayload, 'impression');
    $secondImpression = advertisingRecordEvent((array) $impressionPayload, 'impression');
    adTest(($firstImpression['ok'] ?? false) && !($firstImpression['duplicate'] ?? true), 'First impression should be stored');
    adTest(($secondImpression['ok'] ?? false) && ($secondImpression['duplicate'] ?? false), 'Second impression should deduplicate');
    if (!empty($firstImpression['event_id'])) {
        adTestRemember('ad_events', (string) $firstImpression['event_id']);
    }

    preg_match('/href="\/ad-out\?t=([^"]+)"/', $html, $clickMatch);
    $clickToken = rawurldecode(html_entity_decode((string) ($clickMatch[1] ?? ''), ENT_QUOTES, 'UTF-8'));
    $clickPayload = advertisingVerifyPayload($clickToken);
    adTest(is_array($clickPayload) && ($clickPayload['x'] ?? '') === 'website', 'Click token should carry a stored action');
    $click = advertisingRecordEvent((array) $clickPayload, 'click');
    adTest(($click['ok'] ?? false) && !($click['duplicate'] ?? true), 'Click should be stored');
    if (!empty($click['event_id'])) {
        adTestRemember('ad_events', (string) $click['event_id']);
    }
    adTest(advertisingActionTarget((array) ($click['assignment'] ?? []), 'website') === 'https://example.com/mango', 'Click target should come from the stored creative');

    $metrics = queryOne('SELECT SUM(valid_events) n FROM ad_metrics_daily WHERE assignment_id=:id', [':id' => $assignmentId]);
    adTest((int) ($metrics['n'] ?? 0) === 2, 'Daily metrics should contain one impression and one click');

    $spanish = advertisingRenderSlot('beach.local-partners', 'beach', $beachSlug, 'es');
    adTest(str_contains($spanish, 'Anuncio pagado'), 'Spanish unit should disclose paid advertising');
    adTest(str_contains($spanish, 'Casa de Mango'), 'Spanish creative should be selected');

    $guideAssignmentId = uuid();
    execute(
        'INSERT INTO ad_assignments
            (id,campaign_id,creative_id,slot_id,target_type,target_key,locale,status,starts_at,ends_at)
         VALUES (:id,:campaign,:creative,"adslot-guide-inline","guide","qa-guide","all","active",datetime("now","-1 day"),datetime("now","+7 day"))',
        [':id' => $guideAssignmentId, ':campaign' => $campaignId, ':creative' => $creativeId]
    );
    adTestRemember('ad_assignments', $guideAssignmentId);
    $conflict = advertisingAssignmentConflict('adslot-guide-inline', 'guide', 'qa-guide', 'all', gmdate('Y-m-d H:i:s'), gmdate('Y-m-d H:i:s', time() + 86400), 'new-id');
    adTest(is_array($conflict), 'Exclusive guide overlap should be rejected');

    setSetting('advertising_enabled', '0');
    adTest(advertisingRenderSlot('beach.local-partners', 'beach', $beachSlug, 'en') === '', 'Kill switch should hide placements');
    setSetting('advertising_enabled', '1');
} catch (Throwable $e) {
    $failures[] = 'Unexpected exception: ' . $e->getMessage();
} finally {
    adTestCleanup();
}

if ($failures) {
    fwrite(STDERR, "Advertising tests failed ({$checks} checks):\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo "Advertising tests passed ({$checks} checks).\n";

