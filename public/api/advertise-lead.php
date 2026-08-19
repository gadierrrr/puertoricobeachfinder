<?php
/** Store a direct-advertising inquiry and notify the lead owner. */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/rate_limiter.php';
require_once APP_ROOT . '/inc/email.php';
require_once APP_ROOT . '/inc/advertising.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

$locale = trim((string) ($_POST['locale'] ?? 'en')) === 'es' ? 'es' : 'en';
$back = routeUrl('advertise', $locale);

function advertisingLeadRedirect(string $back, string $query): never
{
    header('Location: ' . $back . '?' . $query . '#inquiry', true, 303);
    exit;
}

// Honeypot submissions receive a synthetic success without persistence.
if (trim((string) ($_POST['company_website'] ?? '')) !== '') {
    advertisingLeadRedirect($back, 'sent=1');
}

$origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
if ($origin !== '') {
    $originHost = strtolower((string) (parse_url($origin, PHP_URL_HOST) ?? ''));
    $requestHost = strtolower((string) (parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST) ?? ''));
    if ($originHost === '' || $requestHost === '' || !hash_equals($requestHost, $originHost)) {
        advertisingLeadRedirect($back, 'error=origin');
    }
}

if (!advertisingValidateLeadFormToken(trim((string) ($_POST['form_token'] ?? '')))) {
    advertisingLeadRedirect($back, 'error=form');
}

$limiter = new RateLimiter(getDb());
$rate = $limiter->check(advertisingVisitorHash(), 'advertising_lead', 5, 60);
if (!($rate['allowed'] ?? false)) {
    advertisingLeadRedirect($back, 'error=rate');
}

$businessName = mb_substr(trim((string) ($_POST['business_name'] ?? '')), 0, 120);
$contactName = mb_substr(trim((string) ($_POST['contact_name'] ?? '')), 0, 120);
$email = mb_strtolower(mb_substr(trim((string) ($_POST['email'] ?? '')), 0, 200));
$phone = mb_substr(trim((string) ($_POST['phone'] ?? '')), 0, 30);
$websiteUrl = mb_substr(trim((string) ($_POST['website_url'] ?? '')), 0, 300);
$category = trim((string) ($_POST['category'] ?? 'services'));
$packageSlug = mb_substr(trim((string) ($_POST['package_slug'] ?? 'standard')), 0, 80);
$targetDetails = mb_substr(trim((string) ($_POST['target_details'] ?? '')), 0, 300);
$message = mb_substr(trim((string) ($_POST['message'] ?? '')), 0, 2000);
$sourcePage = sanitizeInternalRedirect(mb_substr(trim((string) ($_POST['source_page'] ?? $back)), 0, 300), $back);
$consentContact = isset($_POST['consent_contact']) && (string) $_POST['consent_contact'] === '1';

if ($businessName === '' || $contactName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$consentContact) {
    advertisingLeadRedirect($back, 'error=required');
}
if (!isset(ADVERTISING_ALLOWED_CATEGORIES[$category])) {
    advertisingLeadRedirect($back, 'error=category');
}
$package = advertisingPackageBySlug($packageSlug);
if (!$package || ($package['status'] ?? '') !== 'active') {
    advertisingLeadRedirect($back, 'error=package');
}

// Treat an identical inquiry inside 24 hours as the same lead.
$duplicate = queryOne(
    'SELECT id FROM ad_leads
     WHERE lower(email)=lower(:email) AND lower(business_name)=lower(:business)
       AND created_at >= datetime("now","-24 hours")
     ORDER BY created_at DESC LIMIT 1',
    [':email' => $email, ':business' => $businessName]
);
if ($duplicate) {
    advertisingLeadRedirect($back, 'sent=1&ref=' . rawurlencode(substr((string) $duplicate['id'], 0, 8)));
}

$owner = queryOne('SELECT id,email FROM users WHERE is_admin=1 ORDER BY created_at ASC LIMIT 1');
$leadId = uuid();
$stored = execute(
    'INSERT INTO ad_leads
        (id,business_name,contact_name,email,phone,website_url,category,package_slug,
         target_details,message,source_page,locale,consent_contact,status,owner_user_id,
         next_follow_up_at,created_at,updated_at)
     VALUES
        (:id,:business,:contact,:email,:phone,:website,:category,:package,:target,:message,
         :source,:locale,1,"new",:owner,datetime("now","+2 day"),CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)',
    [
        ':id' => $leadId, ':business' => $businessName, ':contact' => $contactName,
        ':email' => $email, ':phone' => $phone, ':website' => $websiteUrl,
        ':category' => $category, ':package' => $packageSlug, ':target' => $targetDetails,
        ':message' => $message, ':source' => $sourcePage, ':locale' => $locale,
        ':owner' => $owner['id'] ?? null,
    ]
);
if (!$stored) {
    advertisingLeadRedirect($back, 'error=server');
}

advertisingAudit('lead', $leadId, 'created', null, [
    'package_slug' => $packageSlug,
    'category' => $category,
    'source_page' => $sourcePage,
]);

try {
    $to = trim((string) ($owner['email'] ?? ''));
    if ($to !== '') {
        $adminUrl = rtrim((string) env('APP_URL', ''), '/') . '/admin/advertising?tab=leads';
        $html = '<h2>New advertising inquiry</h2>'
            . '<p><strong>Business:</strong> ' . h($businessName) . '</p>'
            . '<p><strong>Contact:</strong> ' . h($contactName) . ' &lt;' . h($email) . '&gt; ' . h($phone) . '</p>'
            . '<p><strong>Package:</strong> ' . h((string) $package['name_en']) . '</p>'
            . '<p><strong>Category:</strong> ' . h(advertisingCategoryLabel($category, 'en')) . '</p>'
            . '<p><strong>Target:</strong> ' . h($targetDetails) . '</p>'
            . '<p><strong>Message:</strong><br>' . nl2br(h($message)) . '</p>'
            . '<p><a href="' . h($adminUrl) . '">Open advertising leads</a></p>';
        sendEmail($to, 'Advertising inquiry: ' . $businessName, $html, ['category' => 'admin_notification']);
    }
} catch (Throwable $e) {
    error_log('advertise lead notification failed: ' . $e->getMessage());
}

advertisingLeadRedirect($back, 'sent=1&ref=' . rawurlencode(substr($leadId, 0, 8)));
