<?php
/** Admin — direct advertising sales, fulfillment, inventory, and reporting. */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/session.php';
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/admin.php';
require_once APP_ROOT . '/inc/advertising.php';
require_once APP_ROOT . '/inc/image-optimizer.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
requireAdmin();

$tabs = ['dashboard', 'leads', 'advertisers', 'campaigns', 'creatives', 'inventory', 'reports', 'policy'];
$tab = trim((string) ($_GET['tab'] ?? 'dashboard'));
if (!in_array($tab, $tabs, true)) {
    $tab = 'dashboard';
}

function adAdminRedirect(string $tab, string $status = 'saved'): never
{
    redirect('/admin/advertising?tab=' . rawurlencode($tab) . '&' . rawurlencode($status) . '=1');
}

function adAdminString(string $key, int $limit = 500): string
{
    return mb_substr(trim((string) ($_POST[$key] ?? '')), 0, $limit);
}

function adAdminDateTime(string $key): ?string
{
    $value = str_replace('T', ' ', adAdminString($key, 40));
    if ($value === '') {
        return null;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}(?: \d{2}:\d{2}(?::\d{2})?)?$/', $value)) {
        return null;
    }
    return $value;
}

function adAdminUploadCreativeImage(string $seed, string $existing = ''): string
{
    if (!isset($_FILES['image']) || ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $existing;
    }
    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK || (int) $_FILES['image']['size'] > 10 * 1024 * 1024) {
        throw new RuntimeException('Image upload failed or exceeded 10 MB.');
    }
    $tmp = (string) $_FILES['image']['tmp_name'];
    $signature = detectImageSignature($tmp);
    $mime = (string) ($signature['mime'] ?? '');
    if (!$signature || !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        throw new RuntimeException('Use a JPEG, PNG, or WebP image.');
    }
    $image = loadImage($tmp, $mime);
    if (!$image) {
        throw new RuntimeException('The uploaded image could not be read.');
    }
    $image = autoRotateImage($image, $tmp, $mime);
    $width = imagesx($image);
    $height = imagesy($image);
    if ($width > 1200) {
        $newHeight = max(1, (int) round($height * 1200 / $width));
        $resized = imagecreatetruecolor(1200, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, 1200, $newHeight, $width, $height);
        imagedestroy($image);
        $image = $resized;
    }
    $dir = APP_ROOT . '/uploads/admin/advertising';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        imagedestroy($image);
        throw new RuntimeException('Advertising upload directory is unavailable.');
    }
    $filename = slugify($seed ?: 'creative') . '-' . date('Ymd') . '-' . substr(bin2hex(random_bytes(6)), 0, 10) . '.webp';
    if (!imagewebp($image, $dir . '/' . $filename, 84)) {
        imagedestroy($image);
        throw new RuntimeException('The optimized image could not be saved.');
    }
    imagedestroy($image);
    return '/uploads/admin/advertising/' . $filename;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf((string) ($_POST['csrf_token'] ?? ''))) {
        adAdminRedirect($tab, 'csrf_error');
    }
    $action = adAdminString('action', 60);

    if ($action === 'set_enabled') {
        setSetting('advertising_enabled', isset($_POST['enabled']) ? '1' : '0');
        advertisingAudit('setting', 'advertising_enabled', 'updated', null, ['enabled' => isset($_POST['enabled'])]);
        adAdminRedirect('dashboard');
    }

    if ($action === 'update_lead') {
        $id = adAdminString('id', 80);
        $before = queryOne('SELECT * FROM ad_leads WHERE id=:id', [':id' => $id]);
        $status = adAdminString('status', 40);
        if (!$before || !in_array($status, ADVERTISING_LEAD_STATUSES, true)) {
            adAdminRedirect('leads', 'invalid');
        }
        $followUp = adAdminString('next_follow_up_at', 40) ?: null;
        execute(
            'UPDATE ad_leads SET status=:status,next_follow_up_at=:follow,lost_reason=:reason,
             last_contacted_at=CASE WHEN :status IN ("qualified","proposal_sent") THEN CURRENT_TIMESTAMP ELSE last_contacted_at END,
             updated_at=CURRENT_TIMESTAMP WHERE id=:id',
            [':status' => $status, ':follow' => $followUp, ':reason' => adAdminString('lost_reason', 300), ':id' => $id]
        );
        advertisingAudit('lead', $id, 'updated', $before, ['status' => $status, 'next_follow_up_at' => $followUp]);
        adAdminRedirect('leads');
    }

    if ($action === 'convert_lead') {
        $id = adAdminString('id', 80);
        $lead = queryOne('SELECT * FROM ad_leads WHERE id=:id', [':id' => $id]);
        if (!$lead || in_array((string) $lead['status'], ['spam', 'lost'], true)) {
            adAdminRedirect('leads', 'invalid');
        }
        $advertiserId = trim((string) ($lead['advertiser_id'] ?? ''));
        if ($advertiserId === '') {
            $advertiserId = uuid();
            execute(
                'INSERT INTO advertisers
                    (id,business_name,contact_name,contact_email,contact_phone,website_url,category,status,created_at,updated_at)
                 VALUES (:id,:business,:contact,:email,:phone,:website,:category,"prospect",CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',
                [
                    ':id' => $advertiserId, ':business' => $lead['business_name'], ':contact' => $lead['contact_name'],
                    ':email' => $lead['email'], ':phone' => $lead['phone'], ':website' => $lead['website_url'],
                    ':category' => $lead['category'],
                ]
            );
        }
        execute('UPDATE ad_leads SET advertiser_id=:advertiser,status="won",updated_at=CURRENT_TIMESTAMP WHERE id=:id', [':advertiser' => $advertiserId, ':id' => $id]);
        advertisingAudit('lead', $id, 'converted', $lead, ['advertiser_id' => $advertiserId]);
        redirect('/admin/advertising?tab=advertisers&edit=' . rawurlencode($advertiserId) . '&converted=1');
    }

    if ($action === 'save_advertiser') {
        $id = adAdminString('id', 80) ?: uuid();
        $before = queryOne('SELECT * FROM advertisers WHERE id=:id', [':id' => $id]);
        $businessName = adAdminString('business_name', 120);
        $status = adAdminString('status', 30);
        $category = adAdminString('category', 40);
        if ($businessName === '' || !in_array($status, ['prospect', 'active', 'paused', 'archived'], true) || !isset(ADVERTISING_ALLOWED_CATEGORIES[$category])) {
            adAdminRedirect('advertisers', 'invalid');
        }
        execute(
            'INSERT INTO advertisers
                (id,business_name,legal_name,contact_name,contact_email,contact_phone,billing_email,website_url,category,status,notes,created_at,updated_at)
             VALUES (:id,:business,:legal,:contact,:email,:phone,:billing,:website,:category,:status,:notes,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)
             ON CONFLICT(id) DO UPDATE SET business_name=excluded.business_name,legal_name=excluded.legal_name,
                contact_name=excluded.contact_name,contact_email=excluded.contact_email,contact_phone=excluded.contact_phone,
                billing_email=excluded.billing_email,website_url=excluded.website_url,category=excluded.category,
                status=excluded.status,notes=excluded.notes,updated_at=CURRENT_TIMESTAMP',
            [
                ':id' => $id, ':business' => $businessName, ':legal' => adAdminString('legal_name', 160),
                ':contact' => adAdminString('contact_name', 120), ':email' => adAdminString('contact_email', 200),
                ':phone' => adAdminString('contact_phone', 30), ':billing' => adAdminString('billing_email', 200),
                ':website' => adAdminString('website_url', 500), ':category' => $category,
                ':status' => $status, ':notes' => adAdminString('notes', 2000),
            ]
        );
        advertisingAudit('advertiser', $id, $before ? 'updated' : 'created', $before ?: null, ['business_name' => $businessName, 'status' => $status]);
        adAdminRedirect('advertisers');
    }

    if ($action === 'save_campaign') {
        $id = adAdminString('id', 80) ?: uuid();
        $before = queryOne('SELECT * FROM ad_campaigns WHERE id=:id', [':id' => $id]);
        $advertiserId = adAdminString('advertiser_id', 80);
        $packageId = adAdminString('package_id', 80);
        $name = adAdminString('name', 160);
        $status = adAdminString('status', 40);
        $starts = adAdminDateTime('starts_at');
        $ends = adAdminDateTime('ends_at');
        $billingStatus = adAdminString('billing_status', 30) ?: 'unbilled';
        $advertiser = queryOne('SELECT id FROM advertisers WHERE id=:id', [':id' => $advertiserId]);
        $package = queryOne('SELECT * FROM ad_packages WHERE id=:id', [':id' => $packageId]);
        if (!$advertiser || !$package || $name === '' || !in_array($status, ADVERTISING_CAMPAIGN_STATUSES, true)
            || !in_array($billingStatus, ['unbilled', 'invoiced', 'paid', 'overdue', 'waived'], true)
            || ($starts !== null && $ends !== null && $ends < $starts)) {
            adAdminRedirect('campaigns', 'invalid');
        }
        $amount = max(0, (int) round(((float) ($_POST['contracted_amount'] ?? 0)) * 100));
        if ($amount === 0) {
            $amount = (int) $package['price_cents'];
        }
        $approved = in_array($status, ['scheduled', 'active'], true);
        execute(
            'INSERT INTO ad_campaigns
                (id,advertiser_id,package_id,name,objective,status,starts_at,ends_at,contracted_amount_cents,
                 currency,billing_status,approved_by,approved_at,notes,created_at,updated_at)
             VALUES (:id,:advertiser,:package,:name,:objective,:status,:starts,:ends,:amount,"USD",:billing,
                     :approver,:approved_at,:notes,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)
             ON CONFLICT(id) DO UPDATE SET advertiser_id=excluded.advertiser_id,package_id=excluded.package_id,
                 name=excluded.name,objective=excluded.objective,status=excluded.status,starts_at=excluded.starts_at,
                 ends_at=excluded.ends_at,contracted_amount_cents=excluded.contracted_amount_cents,
                 billing_status=excluded.billing_status,approved_by=excluded.approved_by,
                 approved_at=excluded.approved_at,notes=excluded.notes,updated_at=CURRENT_TIMESTAMP',
            [
                ':id' => $id, ':advertiser' => $advertiserId, ':package' => $packageId, ':name' => $name,
                ':objective' => adAdminString('objective', 500), ':status' => $status,
                ':starts' => $starts, ':ends' => $ends,
                ':amount' => $amount, ':billing' => $billingStatus,
                ':approver' => $approved ? ($_SESSION['user_id'] ?? null) : null,
                ':approved_at' => $approved ? gmdate('Y-m-d H:i:s') : null,
                ':notes' => adAdminString('notes', 2000),
            ]
        );
        advertisingAudit('campaign', $id, $before ? 'updated' : 'created', $before ?: null, ['status' => $status, 'package_id' => $packageId]);
        adAdminRedirect('campaigns');
    }

    if ($action === 'save_creative') {
        $id = adAdminString('id', 80) ?: uuid();
        $before = queryOne('SELECT * FROM ad_creatives WHERE id=:id', [':id' => $id]);
        $advertiserId = adAdminString('advertiser_id', 80);
        $name = adAdminString('name', 160);
        $headlineEn = adAdminString('headline_en', 160);
        $status = adAdminString('status', 40);
        if (!queryOne('SELECT id FROM advertisers WHERE id=:id', [':id' => $advertiserId]) || $name === '' || $headlineEn === '' || !in_array($status, ADVERTISING_CREATIVE_STATUSES, true)) {
            adAdminRedirect('creatives', 'invalid');
        }
        try {
            $imageUrl = adAdminUploadCreativeImage($name, (string) ($before['image_url'] ?? ''));
        } catch (Throwable $e) {
            error_log('creative upload failed: ' . $e->getMessage());
            adAdminRedirect('creatives', 'upload_error');
        }
        $approved = $status === 'approved';
        execute(
            'INSERT INTO ad_creatives
                (id,advertiser_id,name,creative_type,headline_en,headline_es,body_en,body_es,image_url,
                 alt_en,alt_es,destination_url,action_label_en,action_label_es,phone,whatsapp,instagram,
                 status,approved_by,approved_at,created_at,updated_at)
             VALUES (:id,:advertiser,:name,:type,:hen,:hes,:ben,:bes,:image,:aen,:aes,:url,:laen,:laes,
                     :phone,:whatsapp,:instagram,:status,:approver,:approved_at,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)
             ON CONFLICT(id) DO UPDATE SET advertiser_id=excluded.advertiser_id,name=excluded.name,
                 creative_type=excluded.creative_type,headline_en=excluded.headline_en,headline_es=excluded.headline_es,
                 body_en=excluded.body_en,body_es=excluded.body_es,image_url=excluded.image_url,
                 alt_en=excluded.alt_en,alt_es=excluded.alt_es,destination_url=excluded.destination_url,
                 action_label_en=excluded.action_label_en,action_label_es=excluded.action_label_es,
                 phone=excluded.phone,whatsapp=excluded.whatsapp,instagram=excluded.instagram,status=excluded.status,
                 approved_by=excluded.approved_by,approved_at=excluded.approved_at,updated_at=CURRENT_TIMESTAMP',
            [
                ':id' => $id, ':advertiser' => $advertiserId, ':name' => $name,
                ':type' => adAdminString('creative_type', 40) ?: 'listing_card',
                ':hen' => $headlineEn, ':hes' => adAdminString('headline_es', 160),
                ':ben' => adAdminString('body_en', 600), ':bes' => adAdminString('body_es', 600),
                ':image' => $imageUrl, ':aen' => adAdminString('alt_en', 200), ':aes' => adAdminString('alt_es', 200),
                ':url' => adAdminString('destination_url', 500), ':laen' => adAdminString('action_label_en', 60),
                ':laes' => adAdminString('action_label_es', 60), ':phone' => adAdminString('phone', 30),
                ':whatsapp' => adAdminString('whatsapp', 30), ':instagram' => ltrim(adAdminString('instagram', 60), '@'),
                ':status' => $status, ':approver' => $approved ? ($_SESSION['user_id'] ?? null) : null,
                ':approved_at' => $approved ? gmdate('Y-m-d H:i:s') : null,
            ]
        );
        advertisingAudit('creative', $id, $before ? 'updated' : 'created', $before ?: null, ['status' => $status, 'headline_en' => $headlineEn]);
        adAdminRedirect('creatives');
    }

    if ($action === 'save_assignment') {
        $id = adAdminString('id', 120) ?: uuid();
        $before = queryOne('SELECT * FROM ad_assignments WHERE id=:id', [':id' => $id]);
        $campaignId = adAdminString('campaign_id', 80);
        $creativeId = adAdminString('creative_id', 80);
        $slotId = adAdminString('slot_id', 80);
        $targetType = adAdminString('target_type', 40);
        $targetKey = adAdminString('target_key', 300);
        $locale = adAdminString('locale', 10);
        $status = adAdminString('status', 30);
        $starts = adAdminDateTime('starts_at');
        $ends = adAdminDateTime('ends_at');
        $validTargetTypes = ['global', 'beach', 'guide', 'collection', 'municipality', 'region', 'category'];
        if ($targetType === 'global') {
            $targetKey = '*';
        }
        $campaign = queryOne('SELECT id,advertiser_id FROM ad_campaigns WHERE id=:id', [':id' => $campaignId]);
        $creative = queryOne('SELECT id,advertiser_id,status FROM ad_creatives WHERE id=:id', [':id' => $creativeId]);
        $slot = queryOne('SELECT id FROM ad_slots WHERE id=:id', [':id' => $slotId]);
        if (!$campaign || !$creative || !$slot
            || !hash_equals((string) $campaign['advertiser_id'], (string) $creative['advertiser_id'])
            || ($status === 'active' && $creative['status'] !== 'approved')
            || !in_array($targetType, $validTargetTypes, true) || $targetKey === ''
            || !in_array($locale, ['all', 'en', 'es'], true) || !in_array($status, ADVERTISING_ASSIGNMENT_STATUSES, true)
            || ($starts !== null && $ends !== null && $ends < $starts)) {
            adAdminRedirect('inventory', 'invalid');
        }
        $conflict = advertisingAssignmentConflict($slotId, $targetType, $targetKey, $locale, $starts, $ends, $id);
        if ($conflict) {
            adAdminRedirect('inventory', 'conflict');
        }
        execute(
            'INSERT INTO ad_assignments
                (id,campaign_id,creative_id,slot_id,target_type,target_key,locale,priority,display_order,
                 status,starts_at,ends_at,created_at,updated_at)
             VALUES (:id,:campaign,:creative,:slot,:type,:key,:locale,:priority,:display,:status,:starts,:ends,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)
             ON CONFLICT(id) DO UPDATE SET campaign_id=excluded.campaign_id,creative_id=excluded.creative_id,
                 slot_id=excluded.slot_id,target_type=excluded.target_type,target_key=excluded.target_key,
                 locale=excluded.locale,priority=excluded.priority,display_order=excluded.display_order,
                 status=excluded.status,starts_at=excluded.starts_at,ends_at=excluded.ends_at,updated_at=CURRENT_TIMESTAMP',
            [
                ':id' => $id, ':campaign' => $campaignId, ':creative' => $creativeId, ':slot' => $slotId,
                ':type' => $targetType, ':key' => $targetKey, ':locale' => $locale,
                ':priority' => max(0, (int) ($_POST['priority'] ?? 100)),
                ':display' => max(0, (int) ($_POST['display_order'] ?? 0)), ':status' => $status,
                ':starts' => $starts, ':ends' => $ends,
            ]
        );
        advertisingAudit('assignment', $id, $before ? 'updated' : 'created', $before ?: null, ['status' => $status, 'target_type' => $targetType, 'target_key' => $targetKey]);
        adAdminRedirect('inventory');
    }

    if ($action === 'record_conversion') {
        $campaignId = adAdminString('campaign_id', 80);
        if (!queryOne('SELECT id FROM ad_campaigns WHERE id=:id', [':id' => $campaignId])) {
            adAdminRedirect('reports', 'invalid');
        }
        $id = uuid();
        execute(
            'INSERT INTO ad_conversions
                (id,campaign_id,external_id,source,status,value_cents,currency,occurred_at,notes)
             VALUES (:id,:campaign,:external,"manual","confirmed",:value,"USD",:occurred,:notes)',
            [
                ':id' => $id, ':campaign' => $campaignId, ':external' => adAdminString('external_id', 120) ?: null,
                ':value' => max(0, (int) round(((float) ($_POST['value'] ?? 0)) * 100)),
                ':occurred' => adAdminDateTime('occurred_at') ?: gmdate('Y-m-d H:i:s'),
                ':notes' => adAdminString('notes', 1000),
            ]
        );
        advertisingAudit('conversion', $id, 'created', null, ['campaign_id' => $campaignId]);
        adAdminRedirect('reports');
    }

    if ($action === 'record_incident') {
        $campaignId = adAdminString('campaign_id', 80);
        $startedAt = adAdminDateTime('started_at');
        $endedAt = adAdminDateTime('ended_at');
        if (!queryOne('SELECT id FROM ad_campaigns WHERE id=:id', [':id' => $campaignId]) || $startedAt === null
            || ($endedAt !== null && $endedAt < $startedAt)) {
            adAdminRedirect('reports', 'invalid');
        }
        $id = uuid();
        execute(
            'INSERT INTO ad_delivery_incidents
                (id,campaign_id,assignment_id,started_at,ended_at,cause,missed_days,resolution,credit_cents,notes,created_by)
             VALUES (:id,:campaign,:assignment,:started,:ended,:cause,:days,:resolution,:credit,:notes,:actor)',
            [
                ':id' => $id, ':campaign' => $campaignId, ':assignment' => adAdminString('assignment_id', 100) ?: null,
                ':started' => $startedAt, ':ended' => $endedAt,
                ':cause' => in_array(adAdminString('cause', 30), ['site', 'advertiser', 'force_majeure'], true) ? adAdminString('cause', 30) : 'site',
                ':days' => max(0, (float) ($_POST['missed_days'] ?? 0)),
                ':resolution' => in_array(adAdminString('resolution', 30), ['extension', 'credit', 'refund', 'none'], true) ? adAdminString('resolution', 30) : 'extension',
                ':credit' => max(0, (int) round(((float) ($_POST['credit'] ?? 0)) * 100)),
                ':notes' => adAdminString('notes', 1000), ':actor' => $_SESSION['user_id'] ?? null,
            ]
        );
        advertisingAudit('delivery_incident', $id, 'created', null, ['campaign_id' => $campaignId]);
        adAdminRedirect('reports');
    }

    adAdminRedirect($tab, 'invalid');
}

$editId = trim((string) ($_GET['edit'] ?? ''));
$packages = advertisingPackages(false);
$advertisers = query('SELECT * FROM advertisers ORDER BY business_name') ?: [];
$campaigns = query('SELECT c.*,a.business_name,p.name_en AS package_name,p.slug AS package_slug FROM ad_campaigns c INNER JOIN advertisers a ON a.id=c.advertiser_id INNER JOIN ad_packages p ON p.id=c.package_id ORDER BY c.created_at DESC') ?: [];
$creatives = query('SELECT cr.*,a.business_name FROM ad_creatives cr INNER JOIN advertisers a ON a.id=cr.advertiser_id ORDER BY cr.created_at DESC') ?: [];
$slots = query('SELECT * FROM ad_slots ORDER BY display_order') ?: [];
$assignments = query('SELECT aa.*,s.slot_key,s.exclusive,c.name AS campaign_name,cr.name AS creative_name FROM ad_assignments aa INNER JOIN ad_slots s ON s.id=aa.slot_id INNER JOIN ad_campaigns c ON c.id=aa.campaign_id INNER JOIN ad_creatives cr ON cr.id=aa.creative_id ORDER BY aa.created_at DESC') ?: [];
$leads = query('SELECT l.*,u.email AS owner_email,p.name_en AS package_name FROM ad_leads l LEFT JOIN users u ON u.id=l.owner_user_id LEFT JOIN ad_packages p ON p.slug=l.package_slug ORDER BY l.created_at DESC LIMIT 300') ?: [];
$reports = query('SELECT c.id,c.name,a.business_name,p.name_en AS package_name,c.status,c.contracted_amount_cents,c.billing_status, COALESCE(SUM(CASE WHEN m.event_type="impression" THEN m.valid_events ELSE 0 END),0) impressions, COALESCE(SUM(CASE WHEN m.event_type="click" THEN m.valid_events ELSE 0 END),0) clicks, (SELECT COUNT(*) FROM ad_conversions cv WHERE cv.campaign_id=c.id AND cv.status="confirmed") conversions FROM ad_campaigns c INNER JOIN advertisers a ON a.id=c.advertiser_id INNER JOIN ad_packages p ON p.id=c.package_id LEFT JOIN ad_metrics_daily m ON m.campaign_id=c.id GROUP BY c.id ORDER BY c.created_at DESC') ?: [];

$summary = [
    'new_leads' => count(array_filter($leads, static fn($row) => $row['status'] === 'new')),
    'overdue_leads' => count(array_filter($leads, static fn($row) => in_array($row['status'], ['new','qualified','proposal_sent'], true) && !empty($row['next_follow_up_at']) && $row['next_follow_up_at'] < gmdate('Y-m-d H:i:s'))),
    'active_campaigns' => count(array_filter($campaigns, static fn($row) => in_array($row['status'], ['active','scheduled'], true))),
    'active_assignments' => count(array_filter($assignments, static fn($row) => $row['status'] === 'active')),
    'impressions' => array_sum(array_map(static fn($row) => (int) $row['impressions'], $reports)),
    'clicks' => array_sum(array_map(static fn($row) => (int) $row['clicks'], $reports)),
];

$editAdvertiser = $tab === 'advertisers' && $editId !== '' ? queryOne('SELECT * FROM advertisers WHERE id=:id', [':id' => $editId]) : null;
$editCampaign = $tab === 'campaigns' && $editId !== '' ? queryOne('SELECT * FROM ad_campaigns WHERE id=:id', [':id' => $editId]) : null;
$editCreative = $tab === 'creatives' && $editId !== '' ? queryOne('SELECT * FROM ad_creatives WHERE id=:id', [':id' => $editId]) : null;
$editAssignment = $tab === 'inventory' && $editId !== '' ? queryOne('SELECT * FROM ad_assignments WHERE id=:id', [':id' => $editId]) : null;

$pageTitle = 'Advertising';
$pageSubtitle = 'Sales, creative approval, exclusive inventory, delivery, and reporting';
include __DIR__ . '/components/header.php';
?>

<?php if (isset($_GET['saved'])): ?><div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">Saved.</div><?php endif; ?>
<?php if (isset($_GET['invalid'])): ?><div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">The request was invalid. Check required fields and statuses.</div><?php endif; ?>
<?php if (isset($_GET['conflict'])): ?><div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">That exclusive guide or collection inventory overlaps an existing assignment.</div><?php endif; ?>
<?php if (isset($_GET['upload_error'])): ?><div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">The creative image could not be processed.</div><?php endif; ?>
<?php if (isset($_GET['csrf_error'])): ?><div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">Session expired. Reload and try again.</div><?php endif; ?>

<div class="mb-6 flex flex-wrap gap-1 border-b border-gray-200">
  <?php foreach ($tabs as $tabKey): ?>
  <a href="/admin/advertising?tab=<?= h($tabKey) ?>" class="px-3 py-2 text-sm font-semibold <?= $tab === $tabKey ? 'border-b-2 border-blue-600 text-blue-700' : 'text-gray-500 hover:text-gray-800' ?>"><?= h(ucfirst($tabKey)) ?><?= $tabKey === 'leads' && $summary['new_leads'] ? ' (' . $summary['new_leads'] . ')' : '' ?></a>
  <?php endforeach; ?>
</div>

<?php if ($tab === 'dashboard'): ?>
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6 mb-6">
  <?php foreach ([['New leads',$summary['new_leads'],'blue'],['Overdue SLA',$summary['overdue_leads'],'red'],['Live campaigns',$summary['active_campaigns'],'green'],['Live placements',$summary['active_assignments'],'cyan'],['Valid impressions',$summary['impressions'],'purple'],['Valid clicks',$summary['clicks'],'amber']] as [$label,$value,$color]): ?>
  <div class="rounded-xl bg-white p-4 shadow-sm border border-gray-100"><p class="text-xs uppercase tracking-wide text-gray-500"><?= h($label) ?></p><p class="mt-1 text-2xl font-bold text-gray-900"><?= number_format((int) $value) ?></p></div>
  <?php endforeach; ?>
</div>
<div class="grid gap-6 lg:grid-cols-2">
  <div class="rounded-xl border border-gray-200 bg-white p-5"><h2 class="font-bold text-gray-900">Platform status</h2><p class="mt-1 text-sm text-gray-500">The kill switch hides all direct placements and rejects new billable delivery without deleting campaigns.</p><form method="post" class="mt-4 flex items-center gap-3"><input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="action" value="set_enabled"><label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="enabled" value="1" <?= advertisingEnabled() ? 'checked' : '' ?>> Advertising enabled</label><button class="rounded bg-gray-900 px-3 py-2 text-xs font-bold text-white">Save</button></form></div>
  <div class="rounded-xl border border-gray-200 bg-white p-5"><h2 class="font-bold text-gray-900">Operating commitments</h2><ul class="mt-3 space-y-2 text-sm text-gray-600"><li>• Lead owner responds within two business days.</li><li>• Beach inventory: one block, maximum two paid cards.</li><li>• Guide and collection inventory: one exclusive sponsor.</li><li>• Site-caused missed days extend one-for-one.</li><li>• More than 20% monthly downtime permits pro-rata credit/refund.</li></ul></div>
</div>

<?php elseif ($tab === 'leads'): ?>
<div class="overflow-x-auto rounded-xl border border-gray-200 bg-white"><table class="w-full text-sm"><thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-4 py-3">Received / SLA</th><th class="px-4 py-3">Business</th><th class="px-4 py-3">Placement</th><th class="px-4 py-3">Target</th><th class="px-4 py-3">Owner</th><th class="px-4 py-3">Pipeline</th></tr></thead><tbody class="divide-y divide-gray-100">
<?php if (!$leads): ?><tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No advertising inquiries yet.</td></tr><?php endif; ?>
<?php foreach ($leads as $lead): $overdue = in_array($lead['status'], ['new','qualified','proposal_sent'], true) && !empty($lead['next_follow_up_at']) && $lead['next_follow_up_at'] < gmdate('Y-m-d H:i:s'); ?>
<tr class="<?= $overdue ? 'bg-red-50/60' : ($lead['status'] === 'new' ? 'bg-blue-50/40' : '') ?>"><td class="px-4 py-3 whitespace-nowrap"><div><?= h(substr((string) $lead['created_at'],0,16)) ?></div><div class="text-xs <?= $overdue ? 'font-bold text-red-700' : 'text-gray-500' ?>"><?= $overdue ? 'SLA overdue' : h($lead['next_follow_up_at'] ? 'Follow up ' . substr($lead['next_follow_up_at'],0,10) : 'No follow-up set') ?></div></td><td class="px-4 py-3"><strong><?= h($lead['business_name']) ?></strong><div><?= h($lead['contact_name']) ?> · <a class="text-blue-600" href="mailto:<?= h($lead['email']) ?>"><?= h($lead['email']) ?></a></div><div class="text-xs text-gray-500"><?= h($lead['phone']) ?> <?= h($lead['website_url']) ?></div><p class="mt-1 max-w-sm text-xs text-gray-600"><?= h($lead['message']) ?></p></td><td class="px-4 py-3"><?= h($lead['package_name'] ?: $lead['package_slug']) ?><div class="text-xs text-gray-500"><?= h(advertisingCategoryLabel((string) $lead['category'], 'en')) ?></div></td><td class="px-4 py-3 max-w-xs"><?= h($lead['target_details']) ?></td><td class="px-4 py-3 text-xs"><?= h($lead['owner_email'] ?: 'Unassigned') ?></td><td class="px-4 py-3"><form method="post" class="grid gap-2 min-w-44"><input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="action" value="update_lead"><input type="hidden" name="id" value="<?= h($lead['id']) ?>"><select name="status" class="rounded border-gray-300 text-xs"><?php foreach (ADVERTISING_LEAD_STATUSES as $status): ?><option value="<?= h($status) ?>" <?= $lead['status'] === $status ? 'selected' : '' ?>><?= h(ucwords(str_replace('_',' ',$status))) ?></option><?php endforeach; ?></select><input type="date" name="next_follow_up_at" value="<?= h(substr((string) $lead['next_follow_up_at'],0,10)) ?>" class="rounded border-gray-300 text-xs"><input name="lost_reason" value="<?= h($lead['lost_reason']) ?>" placeholder="Loss/spam reason" class="rounded border-gray-300 text-xs"><button class="rounded bg-gray-800 px-2 py-1.5 text-xs font-bold text-white">Update</button></form><?php if (empty($lead['advertiser_id']) && !in_array($lead['status'], ['lost','spam'], true)): ?><form method="post" class="mt-2"><input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="action" value="convert_lead"><input type="hidden" name="id" value="<?= h($lead['id']) ?>"><button class="text-xs font-bold text-blue-700 hover:underline">Convert to advertiser →</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>

<?php elseif ($tab === 'advertisers'): $a = $editAdvertiser ?: []; ?>
<div class="grid gap-6 xl:grid-cols-[420px_1fr]"><form method="post" class="rounded-xl border border-gray-200 bg-white p-5 space-y-3"><h2 class="font-bold text-gray-900"><?= $editAdvertiser ? 'Edit advertiser' : 'New advertiser' ?></h2><input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="action" value="save_advertiser"><input type="hidden" name="id" value="<?= h($a['id'] ?? '') ?>"><?php foreach ([['business_name','Business name *'],['legal_name','Legal name'],['contact_name','Contact name'],['contact_email','Contact email'],['contact_phone','Contact phone'],['billing_email','Billing email'],['website_url','Website URL']] as [$name,$label]): ?><label class="block text-xs font-semibold text-gray-600"><?= h($label) ?><input name="<?= h($name) ?>" value="<?= h($a[$name] ?? '') ?>" <?= $name === 'business_name' ? 'required' : '' ?> class="mt-1 w-full rounded border-gray-300 text-sm"></label><?php endforeach; ?><div class="grid grid-cols-2 gap-3"><label class="text-xs font-semibold text-gray-600">Category<select name="category" class="mt-1 w-full rounded border-gray-300 text-sm"><?php foreach (ADVERTISING_ALLOWED_CATEGORIES as $key=>$category): ?><option value="<?= h($key) ?>" <?= ($a['category'] ?? 'services') === $key ? 'selected' : '' ?>><?= h($category['en']) ?></option><?php endforeach; ?></select></label><label class="text-xs font-semibold text-gray-600">Status<select name="status" class="mt-1 w-full rounded border-gray-300 text-sm"><?php foreach (['prospect','active','paused','archived'] as $status): ?><option <?= ($a['status'] ?? 'prospect') === $status ? 'selected' : '' ?>><?= h($status) ?></option><?php endforeach; ?></select></label></div><label class="block text-xs font-semibold text-gray-600">Internal notes<textarea name="notes" rows="3" class="mt-1 w-full rounded border-gray-300 text-sm"><?= h($a['notes'] ?? '') ?></textarea></label><button class="w-full rounded bg-blue-600 px-4 py-2 text-sm font-bold text-white">Save advertiser</button></form><div class="overflow-x-auto rounded-xl border border-gray-200 bg-white"><table class="w-full text-sm"><thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Business</th><th class="px-4 py-3">Contact</th><th class="px-4 py-3">Category</th><th class="px-4 py-3">Status</th><th></th></tr></thead><tbody class="divide-y"><?php foreach ($advertisers as $row): ?><tr><td class="px-4 py-3 font-semibold"><?= h($row['business_name']) ?></td><td class="px-4 py-3"><?= h($row['contact_name']) ?><div class="text-xs text-gray-500"><?= h($row['contact_email']) ?></div></td><td class="px-4 py-3"><?= h(advertisingCategoryLabel($row['category'],'en')) ?></td><td class="px-4 py-3"><?= h($row['status']) ?></td><td class="px-4 py-3 text-right"><a class="text-blue-600 font-semibold" href="?tab=advertisers&edit=<?= h($row['id']) ?>">Edit</a></td></tr><?php endforeach; ?></tbody></table></div></div>

<?php elseif ($tab === 'campaigns'): $c = $editCampaign ?: []; ?>
<div class="grid gap-6 xl:grid-cols-[440px_1fr]"><form method="post" class="rounded-xl border border-gray-200 bg-white p-5 space-y-3"><h2 class="font-bold"><?= $editCampaign ? 'Edit campaign' : 'New campaign' ?></h2><input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="action" value="save_campaign"><input type="hidden" name="id" value="<?= h($c['id'] ?? '') ?>"><label class="block text-xs font-semibold text-gray-600">Advertiser *<select name="advertiser_id" required class="mt-1 w-full rounded border-gray-300 text-sm"><option value="">Choose…</option><?php foreach ($advertisers as $row): ?><option value="<?= h($row['id']) ?>" <?= ($c['advertiser_id'] ?? '') === $row['id'] ? 'selected' : '' ?>><?= h($row['business_name']) ?></option><?php endforeach; ?></select></label><label class="block text-xs font-semibold text-gray-600">Package *<select name="package_id" required class="mt-1 w-full rounded border-gray-300 text-sm"><?php foreach ($packages as $package): ?><option value="<?= h($package['id']) ?>" <?= ($c['package_id'] ?? '') === $package['id'] ? 'selected' : '' ?>><?= h($package['name_en']) ?> — $<?= number_format($package['price_cents']/100,0) ?>/mo · <?= (int) $package['minimum_term_months'] ?> mo min</option><?php endforeach; ?></select></label><label class="block text-xs font-semibold text-gray-600">Campaign name *<input name="name" required value="<?= h($c['name'] ?? '') ?>" class="mt-1 w-full rounded border-gray-300 text-sm"></label><label class="block text-xs font-semibold text-gray-600">Objective<input name="objective" value="<?= h($c['objective'] ?? '') ?>" class="mt-1 w-full rounded border-gray-300 text-sm"></label><div class="grid grid-cols-2 gap-3"><label class="text-xs font-semibold text-gray-600">Starts<input type="datetime-local" name="starts_at" value="<?= h(isset($c['starts_at']) ? str_replace(' ','T',substr($c['starts_at'],0,16)) : '') ?>" class="mt-1 w-full rounded border-gray-300 text-sm"></label><label class="text-xs font-semibold text-gray-600">Ends<input type="datetime-local" name="ends_at" value="<?= h(isset($c['ends_at']) ? str_replace(' ','T',substr($c['ends_at'],0,16)) : '') ?>" class="mt-1 w-full rounded border-gray-300 text-sm"></label></div><div class="grid grid-cols-2 gap-3"><label class="text-xs font-semibold text-gray-600">Contracted USD<input type="number" step="0.01" min="0" name="contracted_amount" value="<?= h(isset($c['contracted_amount_cents']) ? number_format($c['contracted_amount_cents']/100,2,'.','') : '') ?>" class="mt-1 w-full rounded border-gray-300 text-sm"></label><label class="text-xs font-semibold text-gray-600">Billing<select name="billing_status" class="mt-1 w-full rounded border-gray-300 text-sm"><?php foreach (['unbilled','invoiced','paid','overdue','waived'] as $status): ?><option <?= ($c['billing_status'] ?? 'unbilled') === $status ? 'selected' : '' ?>><?= h($status) ?></option><?php endforeach; ?></select></label></div><label class="block text-xs font-semibold text-gray-600">Status<select name="status" class="mt-1 w-full rounded border-gray-300 text-sm"><?php foreach (ADVERTISING_CAMPAIGN_STATUSES as $status): ?><option value="<?= h($status) ?>" <?= ($c['status'] ?? 'draft') === $status ? 'selected' : '' ?>><?= h(ucwords(str_replace('_',' ',$status))) ?></option><?php endforeach; ?></select></label><label class="block text-xs font-semibold text-gray-600">Notes<textarea name="notes" rows="3" class="mt-1 w-full rounded border-gray-300 text-sm"><?= h($c['notes'] ?? '') ?></textarea></label><button class="w-full rounded bg-blue-600 px-4 py-2 text-sm font-bold text-white">Save campaign</button></form><div class="overflow-x-auto rounded-xl border border-gray-200 bg-white"><table class="w-full text-sm"><thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Campaign</th><th class="px-4 py-3">Package</th><th class="px-4 py-3">Window</th><th class="px-4 py-3">Billing</th><th></th></tr></thead><tbody class="divide-y"><?php foreach ($campaigns as $row): ?><tr><td class="px-4 py-3"><strong><?= h($row['name']) ?></strong><div class="text-xs text-gray-500"><?= h($row['business_name']) ?> · <?= h($row['status']) ?></div></td><td class="px-4 py-3"><?= h($row['package_name']) ?></td><td class="px-4 py-3 text-xs"><?= h(($row['starts_at'] ?: '—') . ' → ' . ($row['ends_at'] ?: '—')) ?></td><td class="px-4 py-3"><?= h($row['billing_status']) ?><div class="text-xs">$<?= number_format($row['contracted_amount_cents']/100,2) ?></div></td><td class="px-4 py-3 text-right"><a class="font-semibold text-blue-600" href="?tab=campaigns&edit=<?= h($row['id']) ?>">Edit</a></td></tr><?php endforeach; ?></tbody></table></div></div>

<?php elseif ($tab === 'creatives'): $cr = $editCreative ?: []; ?>
<div class="grid gap-6 xl:grid-cols-[480px_1fr]"><form method="post" enctype="multipart/form-data" class="rounded-xl border border-gray-200 bg-white p-5 space-y-3"><h2 class="font-bold"><?= $editCreative ? 'Edit creative' : 'New creative' ?></h2><input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="action" value="save_creative"><input type="hidden" name="id" value="<?= h($cr['id'] ?? '') ?>"><label class="block text-xs font-semibold text-gray-600">Advertiser *<select name="advertiser_id" required class="mt-1 w-full rounded border-gray-300 text-sm"><option value="">Choose…</option><?php foreach ($advertisers as $row): ?><option value="<?= h($row['id']) ?>" <?= ($cr['advertiser_id'] ?? '') === $row['id'] ? 'selected' : '' ?>><?= h($row['business_name']) ?></option><?php endforeach; ?></select></label><div class="grid grid-cols-2 gap-3"><label class="text-xs font-semibold text-gray-600">Internal name *<input name="name" required value="<?= h($cr['name'] ?? '') ?>" class="mt-1 w-full rounded border-gray-300 text-sm"></label><label class="text-xs font-semibold text-gray-600">Type<select name="creative_type" class="mt-1 w-full rounded border-gray-300 text-sm"><?php foreach (['listing_card','sponsor_card','sponsor_strip'] as $type): ?><option <?= ($cr['creative_type'] ?? 'listing_card') === $type ? 'selected' : '' ?>><?= h($type) ?></option><?php endforeach; ?></select></label></div><?php foreach ([['headline_en','Headline EN *'],['headline_es','Headline ES'],['body_en','Body EN'],['body_es','Body ES'],['alt_en','Image alt EN'],['alt_es','Image alt ES'],['destination_url','Website URL'],['action_label_en','Button label EN'],['action_label_es','Button label ES'],['phone','Phone'],['whatsapp','WhatsApp digits'],['instagram','Instagram handle']] as [$name,$label]): ?><label class="block text-xs font-semibold text-gray-600"><?= h($label) ?><?php if (str_starts_with($name,'body_')): ?><textarea name="<?= h($name) ?>" rows="2" class="mt-1 w-full rounded border-gray-300 text-sm"><?= h($cr[$name] ?? '') ?></textarea><?php else: ?><input name="<?= h($name) ?>" value="<?= h($cr[$name] ?? '') ?>" <?= $name === 'headline_en' ? 'required' : '' ?> class="mt-1 w-full rounded border-gray-300 text-sm"><?php endif; ?></label><?php endforeach; ?><label class="block text-xs font-semibold text-gray-600">Creative image (JPEG/PNG/WebP, 10 MB max)<input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="mt-1 w-full text-sm"><?php if (!empty($cr['image_url'])): ?><img src="<?= h($cr['image_url']) ?>" alt="" class="mt-2 h-24 w-36 rounded object-cover"><?php endif; ?></label><label class="block text-xs font-semibold text-gray-600">Approval status<select name="status" class="mt-1 w-full rounded border-gray-300 text-sm"><?php foreach (ADVERTISING_CREATIVE_STATUSES as $status): ?><option value="<?= h($status) ?>" <?= ($cr['status'] ?? 'draft') === $status ? 'selected' : '' ?>><?= h(ucwords(str_replace('_',' ',$status))) ?></option><?php endforeach; ?></select></label><button class="w-full rounded bg-blue-600 px-4 py-2 text-sm font-bold text-white">Save creative</button></form><div class="grid content-start gap-3 sm:grid-cols-2"><?php foreach ($creatives as $row): ?><article class="rounded-xl border border-gray-200 bg-white p-4"><?php if ($row['image_url']): ?><img src="<?= h($row['image_url']) ?>" alt="" class="mb-3 h-32 w-full rounded-lg object-cover"><?php endif; ?><p class="text-xs font-semibold uppercase text-red-600">Paid advertisement preview</p><h3 class="mt-1 font-bold"><?= h($row['headline_en']) ?></h3><p class="mt-1 text-sm text-gray-600"><?= h($row['body_en']) ?></p><div class="mt-3 flex justify-between text-xs"><span><?= h($row['business_name']) ?> · <?= h($row['status']) ?></span><a class="font-bold text-blue-600" href="?tab=creatives&edit=<?= h($row['id']) ?>">Edit</a></div></article><?php endforeach; ?></div></div>

<?php elseif ($tab === 'inventory'): $as = $editAssignment ?: []; ?>
<div class="grid gap-6 xl:grid-cols-[440px_1fr]"><form method="post" class="rounded-xl border border-gray-200 bg-white p-5 space-y-3"><h2 class="font-bold"><?= $editAssignment ? 'Edit placement' : 'Assign inventory' ?></h2><input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="action" value="save_assignment"><input type="hidden" name="id" value="<?= h($as['id'] ?? '') ?>"><label class="block text-xs font-semibold text-gray-600">Campaign *<select name="campaign_id" required class="mt-1 w-full rounded border-gray-300 text-sm"><option value="">Choose…</option><?php foreach ($campaigns as $row): ?><option value="<?= h($row['id']) ?>" <?= ($as['campaign_id'] ?? '') === $row['id'] ? 'selected' : '' ?>><?= h($row['business_name'] . ' — ' . $row['name']) ?></option><?php endforeach; ?></select></label><label class="block text-xs font-semibold text-gray-600">Approved creative *<select name="creative_id" required class="mt-1 w-full rounded border-gray-300 text-sm"><option value="">Choose…</option><?php foreach ($creatives as $row): ?><option value="<?= h($row['id']) ?>" <?= ($as['creative_id'] ?? '') === $row['id'] ? 'selected' : '' ?>><?= h($row['business_name'] . ' — ' . $row['name'] . ' (' . $row['status'] . ')') ?></option><?php endforeach; ?></select></label><label class="block text-xs font-semibold text-gray-600">Slot *<select name="slot_id" required class="mt-1 w-full rounded border-gray-300 text-sm"><?php foreach ($slots as $slot): ?><option value="<?= h($slot['id']) ?>" <?= ($as['slot_id'] ?? '') === $slot['id'] ? 'selected' : '' ?>><?= h($slot['slot_key']) ?><?= $slot['exclusive'] ? ' · exclusive' : '' ?> · max <?= (int) $slot['max_items'] ?></option><?php endforeach; ?></select></label><div class="grid grid-cols-2 gap-3"><label class="text-xs font-semibold text-gray-600">Target type<select name="target_type" class="mt-1 w-full rounded border-gray-300 text-sm"><?php foreach (['beach','guide','collection','municipality','region','category','global'] as $type): ?><option <?= ($as['target_type'] ?? 'beach') === $type ? 'selected' : '' ?>><?= h($type) ?></option><?php endforeach; ?></select></label><label class="text-xs font-semibold text-gray-600">Target key<input name="target_key" required value="<?= h($as['target_key'] ?? '') ?>" placeholder="beach slug, guide slug, or /path" class="mt-1 w-full rounded border-gray-300 text-sm"></label></div><div class="grid grid-cols-3 gap-3"><label class="text-xs font-semibold text-gray-600">Locale<select name="locale" class="mt-1 w-full rounded border-gray-300 text-sm"><?php foreach (['all','en','es'] as $loc): ?><option <?= ($as['locale'] ?? 'all') === $loc ? 'selected' : '' ?>><?= h($loc) ?></option><?php endforeach; ?></select></label><label class="text-xs font-semibold text-gray-600">Priority<input type="number" name="priority" min="0" value="<?= h($as['priority'] ?? 100) ?>" class="mt-1 w-full rounded border-gray-300 text-sm"></label><label class="text-xs font-semibold text-gray-600">Order<input type="number" name="display_order" min="0" value="<?= h($as['display_order'] ?? 0) ?>" class="mt-1 w-full rounded border-gray-300 text-sm"></label></div><div class="grid grid-cols-2 gap-3"><label class="text-xs font-semibold text-gray-600">Starts<input type="datetime-local" name="starts_at" value="<?= h(isset($as['starts_at']) ? str_replace(' ','T',substr($as['starts_at'],0,16)) : '') ?>" class="mt-1 w-full rounded border-gray-300 text-sm"></label><label class="text-xs font-semibold text-gray-600">Ends<input type="datetime-local" name="ends_at" value="<?= h(isset($as['ends_at']) ? str_replace(' ','T',substr($as['ends_at'],0,16)) : '') ?>" class="mt-1 w-full rounded border-gray-300 text-sm"></label></div><label class="block text-xs font-semibold text-gray-600">Status<select name="status" class="mt-1 w-full rounded border-gray-300 text-sm"><?php foreach (ADVERTISING_ASSIGNMENT_STATUSES as $status): ?><option <?= ($as['status'] ?? 'draft') === $status ? 'selected' : '' ?>><?= h($status) ?></option><?php endforeach; ?></select></label><button class="w-full rounded bg-blue-600 px-4 py-2 text-sm font-bold text-white">Save placement</button><p class="text-xs text-gray-500">Guide and collection slots reject overlapping dates for the same target. Collection keys use the localized path, e.g. <code>/beaches/swimming</code>.</p></form><div class="overflow-x-auto rounded-xl border border-gray-200 bg-white"><table class="w-full text-sm"><thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Slot</th><th class="px-4 py-3">Campaign / creative</th><th class="px-4 py-3">Target</th><th class="px-4 py-3">Window</th><th></th></tr></thead><tbody class="divide-y"><?php foreach ($assignments as $row): ?><tr><td class="px-4 py-3"><strong><?= h($row['slot_key']) ?></strong><div class="text-xs text-gray-500"><?= h($row['status']) ?><?= $row['exclusive'] ? ' · exclusive' : '' ?></div></td><td class="px-4 py-3"><?= h($row['campaign_name']) ?><div class="text-xs text-gray-500"><?= h($row['creative_name']) ?></div></td><td class="px-4 py-3"><?= h($row['target_type'] . ':' . $row['target_key']) ?><div class="text-xs text-gray-500"><?= h($row['locale']) ?></div></td><td class="px-4 py-3 text-xs"><?= h(($row['starts_at'] ?: '—') . ' → ' . ($row['ends_at'] ?: '—')) ?></td><td class="px-4 py-3 text-right"><a class="font-semibold text-blue-600" href="?tab=inventory&edit=<?= h($row['id']) ?>">Edit</a></td></tr><?php endforeach; ?></tbody></table></div></div>

<?php elseif ($tab === 'reports'): ?>
<div class="overflow-x-auto rounded-xl border border-gray-200 bg-white mb-6"><table class="w-full text-sm"><thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-4 py-3">Campaign</th><th class="px-4 py-3">Impressions</th><th class="px-4 py-3">Clicks</th><th class="px-4 py-3">CTR</th><th class="px-4 py-3">Conversions</th><th class="px-4 py-3">Contract</th></tr></thead><tbody class="divide-y"><?php foreach ($reports as $row): $ctr = (int) $row['impressions'] > 0 ? ((int) $row['clicks'] / (int) $row['impressions']) * 100 : 0; ?><tr><td class="px-4 py-3"><strong><?= h($row['name']) ?></strong><div class="text-xs text-gray-500"><?= h($row['business_name'] . ' · ' . $row['package_name'] . ' · ' . $row['status']) ?></div></td><td class="px-4 py-3"><?= number_format((int) $row['impressions']) ?></td><td class="px-4 py-3"><?= number_format((int) $row['clicks']) ?></td><td class="px-4 py-3"><?= number_format($ctr,2) ?>%</td><td class="px-4 py-3"><?= number_format((int) $row['conversions']) ?></td><td class="px-4 py-3">$<?= number_format($row['contracted_amount_cents']/100,2) ?><div class="text-xs text-gray-500"><?= h($row['billing_status']) ?></div></td></tr><?php endforeach; ?></tbody></table></div>
<div class="grid gap-6 lg:grid-cols-2"><form method="post" class="rounded-xl border border-gray-200 bg-white p-5 space-y-3"><h2 class="font-bold">Record advertiser-reported conversion</h2><input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="action" value="record_conversion"><select name="campaign_id" required class="w-full rounded border-gray-300 text-sm"><option value="">Campaign…</option><?php foreach ($campaigns as $row): ?><option value="<?= h($row['id']) ?>"><?= h($row['business_name'] . ' — ' . $row['name']) ?></option><?php endforeach; ?></select><div class="grid grid-cols-2 gap-3"><input name="external_id" placeholder="External/order ID" class="rounded border-gray-300 text-sm"><input type="number" step="0.01" min="0" name="value" placeholder="Value USD" class="rounded border-gray-300 text-sm"></div><input type="datetime-local" name="occurred_at" class="w-full rounded border-gray-300 text-sm"><textarea name="notes" placeholder="Notes" class="w-full rounded border-gray-300 text-sm"></textarea><button class="rounded bg-blue-600 px-4 py-2 text-sm font-bold text-white">Record conversion</button></form><form method="post" class="rounded-xl border border-gray-200 bg-white p-5 space-y-3"><h2 class="font-bold">Record delivery incident / make-good</h2><input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>"><input type="hidden" name="action" value="record_incident"><select name="campaign_id" required class="w-full rounded border-gray-300 text-sm"><option value="">Campaign…</option><?php foreach ($campaigns as $row): ?><option value="<?= h($row['id']) ?>"><?= h($row['business_name'] . ' — ' . $row['name']) ?></option><?php endforeach; ?></select><div class="grid grid-cols-2 gap-3"><input type="datetime-local" name="started_at" required class="rounded border-gray-300 text-sm"><input type="datetime-local" name="ended_at" class="rounded border-gray-300 text-sm"></div><div class="grid grid-cols-3 gap-3"><select name="cause" class="rounded border-gray-300 text-sm"><option value="site">Site-caused</option><option value="advertiser">Advertiser-caused</option><option value="force_majeure">Force majeure</option></select><input type="number" step="0.25" min="0" name="missed_days" placeholder="Missed days" class="rounded border-gray-300 text-sm"><select name="resolution" class="rounded border-gray-300 text-sm"><option value="extension">Extension</option><option value="credit">Credit</option><option value="refund">Refund</option><option value="none">None</option></select></div><input type="number" step="0.01" min="0" name="credit" placeholder="Credit/refund USD" class="w-full rounded border-gray-300 text-sm"><textarea name="notes" placeholder="Resolution notes" class="w-full rounded border-gray-300 text-sm"></textarea><button class="rounded bg-gray-900 px-4 py-2 text-sm font-bold text-white">Record incident</button></form></div>

<?php else: ?>
<div class="grid gap-6 lg:grid-cols-2"><section class="rounded-xl border border-gray-200 bg-white p-6"><h2 class="text-lg font-bold">Categories</h2><h3 class="mt-4 text-sm font-bold text-red-700">Prohibited</h3><ul class="mt-2 list-disc pl-5 text-sm text-gray-600 space-y-1"><li>Illegal goods or services</li><li>Adult or sexual content</li><li>Tobacco, nicotine, cannabis, weapons, gambling, or political advertising</li><li>Deceptive, discriminatory, unsafe, or environmentally harmful claims</li><li>Unlicensed operators or false certifications</li></ul><h3 class="mt-5 text-sm font-bold text-amber-700">Manual restricted review</h3><ul class="mt-2 list-disc pl-5 text-sm text-gray-600 space-y-1"><li>Alcohol-serving businesses</li><li>Health, medical, wellness, or financial claims</li><li>Real estate and long-term accommodation</li><li>Marine, transport, and adventure operators with safety implications</li></ul></section><section class="rounded-xl border border-gray-200 bg-white p-6"><h2 class="text-lg font-bold">Privacy and retention</h2><dl class="mt-4 grid grid-cols-[160px_1fr] gap-y-3 text-sm"><dt class="font-semibold">Lead PII</dt><dd class="text-gray-600">24 months after last activity</dd><dt class="font-semibold">Raw ad events</dt><dd class="text-gray-600">90 days; daily rotating HMAC visitor hash</dd><dt class="font-semibold">Daily aggregates</dt><dd class="text-gray-600">25 months</dd><dt class="font-semibold">Contracts/invoices</dt><dd class="text-gray-600">Seven years, subject to accountant/legal confirmation</dd><dt class="font-semibold">Consent</dt><dd class="text-gray-600">Inquiry contact only; no bundled newsletter consent; no advertiser pixels</dd><dt class="font-semibold">Public metrics</dt><dd class="text-gray-600">Verified coverage only</dd><dt class="font-semibold">Advertiser reports</dt><dd class="text-gray-600">Viewable impressions, clicks, CTR, action, placement, and dates; no visitor identities</dd></dl></section></div>
<?php endif; ?>

<?php include __DIR__ . '/components/footer.php'; ?>
