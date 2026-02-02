<?php
// inc/email.php - Resend email integration

// Include guard
if (defined('EMAIL_PHP_INCLUDED')) {
    return;
}
define('EMAIL_PHP_INCLUDED', true);

require_once __DIR__ . '/db.php';

/**
 * Get an email template by slug
 *
 * @param string $slug Template slug (e.g., 'welcome', 'magic-link')
 * @return array|null Template data or null if not found/inactive
 */
function getEmailTemplate($slug) {
    return queryOne(
        'SELECT * FROM email_templates WHERE slug = :slug AND is_active = 1',
        [':slug' => $slug]
    );
}

/**
 * Render an email template with variables
 *
 * @param string $template The template string (subject or body)
 * @param array $variables Key-value pairs to replace
 * @return string Rendered template
 */
function renderEmailTemplate($template, $variables) {
    foreach ($variables as $key => $value) {
        $template = str_replace('{{' . $key . '}}', $value ?? '', $template);
    }
    return $template;
}

/**
 * Send an email using a database template
 *
 * @param string $slug Template slug
 * @param string $to Recipient email
 * @param array $variables Template variables
 * @return bool Success status
 */
function sendTemplateEmail($slug, $to, $variables = []) {
    $template = getEmailTemplate($slug);

    if (!$template) {
        error_log("Email template not found or inactive: {$slug}");
        return false;
    }

    // Add default variables
    $appUrl = $_ENV['APP_URL'] ?? 'https://puertoricobeachfinder.com';
    $appName = $_ENV['APP_NAME'] ?? 'Puerto Rico Beach Finder';

    $variables = array_merge([
        'app_url' => $appUrl,
        'app_name' => $appName,
    ], $variables);

    $subject = renderEmailTemplate($template['subject'], $variables);
    $html = renderEmailTemplate($template['html_body'], $variables);

    return sendEmail($to, $subject, $html);
}

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

/**
 * Send welcome email to new users
 *
 * @param string $email User's email address
 * @param string $name User's display name
 * @param array $preferences Optional user preferences from onboarding
 * @return bool Success status
 */
function sendWelcomeEmail($email, $name, $preferences = []) {
    // Personalized recommendations based on preferences
    $activityText = '';
    if (!empty($preferences['activities'])) {
        $activities = json_decode($preferences['activities'], true) ?: [];
        if (!empty($activities)) {
            $activityLabels = [
                'swimming' => 'swimming spots',
                'snorkeling' => 'snorkeling paradises',
                'surfing' => 'surf breaks',
                'relaxing' => 'relaxing getaways',
                'family' => 'family-friendly beaches',
                'photography' => 'Instagram-worthy views',
                'hiking' => 'hidden coves',
                'secluded' => 'secluded escapes'
            ];
            $matched = array_intersect_key($activityLabels, array_flip($activities));
            if (!empty($matched)) {
                $activityText = '<p style="margin: 0 0 20px; color: #fbbf24; font-size: 16px; line-height: 1.6;">Based on your preferences, we\'ll help you find the best ' . implode(', ', array_slice($matched, 0, 3)) . ' across Puerto Rico.</p>';
            }
        }
    }

    // Try to use database template first
    $sent = sendTemplateEmail('welcome', $email, [
        'name' => $name,
        'email' => $email,
        'activity_text' => $activityText
    ]);

    if ($sent) {
        return true;
    }

    // Fallback to hardcoded template if database template fails
    $appUrl = $_ENV['APP_URL'] ?? 'https://puertoricobeachfinder.com';
    $appName = $_ENV['APP_NAME'] ?? 'Puerto Rico Beach Finder';

    $subject = "Welcome to {$appName}! 🏖️";

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #0f172a;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #0f172a; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="100%" style="max-width: 600px; background-color: #1e293b; border-radius: 16px; overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #0f172a; font-size: 28px; font-weight: bold;">
                                Welcome to {$appName}!
                            </h1>
                            <p style="margin: 10px 0 0; color: #0f172a; opacity: 0.8; font-size: 16px;">
                                Your beach adventure starts now
                            </p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="margin: 0 0 20px; color: #e2e8f0; font-size: 16px; line-height: 1.6;">
                                Hey {$name}! 👋
                            </p>

                            <p style="margin: 0 0 20px; color: #94a3b8; font-size: 16px; line-height: 1.6;">
                                Thanks for joining our community of beach lovers exploring Puerto Rico's 230+ beaches!
                            </p>

                            {$activityText}

                            <!-- Features Grid -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                <tr>
                                    <td width="50%" style="padding: 10px;">
                                        <div style="background-color: #334155; border-radius: 12px; padding: 20px; text-align: center;">
                                            <div style="font-size: 32px; margin-bottom: 10px;">🌤️</div>
                                            <div style="color: #e2e8f0; font-weight: 600; font-size: 14px;">Real-time Weather</div>
                                            <div style="color: #64748b; font-size: 12px; margin-top: 5px;">Know before you go</div>
                                        </div>
                                    </td>
                                    <td width="50%" style="padding: 10px;">
                                        <div style="background-color: #334155; border-radius: 12px; padding: 20px; text-align: center;">
                                            <div style="font-size: 32px; margin-bottom: 10px;">❤️</div>
                                            <div style="color: #e2e8f0; font-weight: 600; font-size: 14px;">Save Favorites</div>
                                            <div style="color: #64748b; font-size: 12px; margin-top: 5px;">Build your bucket list</div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="50%" style="padding: 10px;">
                                        <div style="background-color: #334155; border-radius: 12px; padding: 20px; text-align: center;">
                                            <div style="font-size: 32px; margin-bottom: 10px;">🏆</div>
                                            <div style="color: #e2e8f0; font-weight: 600; font-size: 14px;">Earn Badges</div>
                                            <div style="color: #64748b; font-size: 12px; margin-top: 5px;">Track your journey</div>
                                        </div>
                                    </td>
                                    <td width="50%" style="padding: 10px;">
                                        <div style="background-color: #334155; border-radius: 12px; padding: 20px; text-align: center;">
                                            <div style="font-size: 32px; margin-bottom: 10px;">📸</div>
                                            <div style="color: #e2e8f0; font-weight: 600; font-size: 14px;">Share Photos</div>
                                            <div style="color: #64748b; font-size: 12px; margin-top: 5px;">Help others discover</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="{$appUrl}" style="display: inline-block; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #0f172a; text-decoration: none; padding: 16px 40px; border-radius: 12px; font-weight: 600; font-size: 16px;">
                                            Start Exploring Beaches
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Tips -->
                            <div style="background-color: #334155; border-radius: 12px; padding: 20px; margin-top: 20px;">
                                <p style="margin: 0 0 10px; color: #fbbf24; font-weight: 600; font-size: 14px;">
                                    💡 Pro tip
                                </p>
                                <p style="margin: 0; color: #94a3b8; font-size: 14px; line-height: 1.6;">
                                    Check in at beaches you visit to help others know current crowd levels and conditions. You'll also earn progress toward your Explorer badges!
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px; border-top: 1px solid #334155; text-align: center;">
                            <p style="margin: 0 0 10px; color: #64748b; font-size: 14px;">
                                Happy exploring! 🏖️
                            </p>
                            <p style="margin: 0; color: #475569; font-size: 12px;">
                                <a href="{$appUrl}" style="color: #fbbf24; text-decoration: none;">{$appName}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

    // Add activity text if we have preferences
    if (!empty($activityText)) {
        $activityText = '<p style="margin: 0 0 20px; color: #fbbf24; font-size: 16px; line-height: 1.6;">' . $activityText . '</p>';
        $html = str_replace('{$activityText}', $activityText, $html);
    } else {
        $html = str_replace('{$activityText}', '', $html);
    }

    return sendEmail($email, $subject, $html);
}
