<?php
/**
 * Advertise lead capture — stores the lead and notifies the site owner.
 *
 * Anonymous endpoint: protected by honeypot + IP rate limiting (no CSRF,
 * since leads come from businesses without sessions).
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/rate_limiter.php';
require_once APP_ROOT . '/inc/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$locale = trim((string) ($_POST['locale'] ?? 'en')) === 'es' ? 'es' : 'en';
$back = '/advertise';

// Honeypot: real users never fill this field.
if (trim((string) ($_POST['company_website'] ?? '')) !== '') {
    // Pretend success so bots don't adapt.
    header('Location: ' . $back . '?sent=1', true, 303);
    exit;
}

$ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
$limiter = new RateLimiter(getDb());
$rate = $limiter->check('advertise:' . $ip, 'advertise_lead', 5, 60);
if (!($rate['allowed'] ?? false)) {
    header('Location: ' . $back . '?error=1', true, 303);
    exit;
}

$businessName = mb_substr(trim((string) ($_POST['business_name'] ?? '')), 0, 120);
$contactName = mb_substr(trim((string) ($_POST['contact_name'] ?? '')), 0, 120);
$email = mb_substr(trim((string) ($_POST['email'] ?? '')), 0, 200);
$phone = mb_substr(trim((string) ($_POST['phone'] ?? '')), 0, 30);
$message = mb_substr(trim((string) ($_POST['message'] ?? '')), 0, 2000);
$beachesInterest = mb_substr(trim((string) ($_POST['beaches_interest'] ?? '')), 0, 300);
$sourcePage = mb_substr(trim((string) ($_POST['source_page'] ?? '')), 0, 300);

if ($businessName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $back . '?error=1#form', true, 303);
    exit;
}

execute(
    'INSERT INTO local_listing_leads
        (id, business_name, contact_name, email, phone, message, beaches_interest, source_page, locale, status)
     VALUES
        (:id, :business_name, :contact_name, :email, :phone, :message, :beaches, :source, :locale, "new")',
    [
        ':id' => uuid(),
        ':business_name' => $businessName,
        ':contact_name' => $contactName,
        ':email' => $email,
        ':phone' => $phone,
        ':message' => $message,
        ':beaches' => $beachesInterest,
        ':source' => $sourcePage,
        ':locale' => $locale,
    ]
);

// Notify the site owner (best-effort — the lead is already stored).
try {
    $adminEmail = queryOne('SELECT email FROM users WHERE is_admin = 1 ORDER BY created_at ASC LIMIT 1');
    $to = trim((string) ($adminEmail['email'] ?? ''));
    if ($to !== '') {
        $html = '<h2>New advertise lead</h2>'
            . '<p><strong>Business:</strong> ' . h($businessName) . '</p>'
            . '<p><strong>Contact:</strong> ' . h($contactName) . ' &lt;' . h($email) . '&gt; ' . h($phone) . '</p>'
            . '<p><strong>Beaches:</strong> ' . h($beachesInterest) . '</p>'
            . '<p><strong>Message:</strong><br>' . nl2br(h($message)) . '</p>'
            . '<p><strong>Source:</strong> ' . h($sourcePage) . ' (' . h($locale) . ')</p>'
            . '<p><a href="' . h(env('APP_URL', '')) . '/admin/listings?tab=leads">Open in admin</a></p>';
        sendEmail($to, 'New advertise lead: ' . $businessName, $html, ['category' => 'admin_notification']);
    }
} catch (Throwable $e) {
    error_log('advertise-lead notify failed: ' . $e->getMessage());
}

header('Location: ' . $back . '?sent=1', true, 303);
exit;
