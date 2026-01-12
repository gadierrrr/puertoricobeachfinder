<?php
// inc/email.php - Resend email integration

function sendEmail($to, $subject, $html) {
    $apiKey = $_ENV['RESEND_API_KEY'] ?? '';

    if (empty($apiKey)) {
        error_log("RESEND_API_KEY not configured");
        return false;
    }

    // Extract domain from APP_URL for "from" address
    $appUrl = $_ENV['APP_URL'] ?? 'localhost';
    $appName = $_ENV['APP_NAME'] ?? 'Beach Finder';
    $domain = parse_url($appUrl, PHP_URL_HOST) ?? 'localhost';

    $data = json_encode([
        'from' => $appName . ' <noreply@' . $domain . '>',
        'to' => [$to],
        'subject' => $subject,
        'html' => $html
    ]);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("Resend API error (HTTP {$httpCode}): {$response}");
        return false;
    }

    return true;
}
