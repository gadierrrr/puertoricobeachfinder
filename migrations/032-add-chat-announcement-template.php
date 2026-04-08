<?php
/**
 * Migration 032: Add chat feature announcement email template
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: Chat announcement email template\n";

$db = getDb();

$templateId = 'tpl_' . bin2hex(random_bytes(8));
$variables = json_encode([
    ['name' => 'name', 'description' => 'User\'s display name'],
    ['name' => 'app_name', 'description' => 'Application name (Puerto Rico Beach Finder)'],
    ['name' => 'app_url', 'description' => 'Application URL'],
]);

$html = <<<'HTML'
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
                            <div style="font-size: 48px; margin-bottom: 12px;">💬</div>
                            <h1 style="margin: 0; color: #0f172a; font-size: 26px; font-weight: bold;">
                                New: Beach Community Chat
                            </h1>
                            <p style="margin: 10px 0 0; color: #0f172a; opacity: 0.8; font-size: 15px;">
                                Connect with fellow beach lovers in real time
                            </p>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="margin: 0 0 20px; color: #e2e8f0; font-size: 16px; line-height: 1.6;">
                                Hey {{name}}! 👋
                            </p>

                            <p style="margin: 0 0 20px; color: #94a3b8; font-size: 16px; line-height: 1.6;">
                                We just launched a brand new chat feature on {{app_name}}! Now you can ask questions, share tips, and get real-time updates from other beachgoers — right from any beach page.
                            </p>

                            <p style="margin: 0 0 25px; color: #94a3b8; font-size: 16px; line-height: 1.6;">
                                Look for the blue chat bubble in the bottom-right corner of the site. Here's what you can do:
                            </p>

                            <!-- Features Grid -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 0 0 30px;">
                                <tr>
                                    <td width="50%" style="padding: 6px;">
                                        <div style="background-color: #334155; border-radius: 12px; padding: 20px; text-align: center;">
                                            <div style="font-size: 28px; margin-bottom: 8px;">🌐</div>
                                            <div style="color: #e2e8f0; font-weight: 600; font-size: 14px;">General Chat</div>
                                            <div style="color: #64748b; font-size: 12px; margin-top: 5px;">Talk about anything PR beaches</div>
                                        </div>
                                    </td>
                                    <td width="50%" style="padding: 6px;">
                                        <div style="background-color: #334155; border-radius: 12px; padding: 20px; text-align: center;">
                                            <div style="font-size: 28px; margin-bottom: 8px;">🏖️</div>
                                            <div style="color: #e2e8f0; font-weight: 600; font-size: 14px;">Beach Discussions</div>
                                            <div style="color: #64748b; font-size: 12px; margin-top: 5px;">Each beach has its own chat</div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="50%" style="padding: 6px;">
                                        <div style="background-color: #334155; border-radius: 12px; padding: 20px; text-align: center;">
                                            <div style="font-size: 28px; margin-bottom: 8px;">⚡</div>
                                            <div style="color: #e2e8f0; font-weight: 600; font-size: 14px;">Real-time Updates</div>
                                            <div style="color: #64748b; font-size: 12px; margin-top: 5px;">Conditions, crowds, tips</div>
                                        </div>
                                    </td>
                                    <td width="50%" style="padding: 6px;">
                                        <div style="background-color: #334155; border-radius: 12px; padding: 20px; text-align: center;">
                                            <div style="font-size: 28px; margin-bottom: 8px;">🛡️</div>
                                            <div style="color: #e2e8f0; font-weight: 600; font-size: 14px;">Safe & Moderated</div>
                                            <div style="color: #64748b; font-size: 12px; margin-top: 5px;">AI-powered moderation</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding: 10px 0 20px;">
                                        <a href="{{app_url}}" style="display: inline-block; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #0f172a; text-decoration: none; padding: 16px 40px; border-radius: 12px; font-weight: 600; font-size: 16px;">
                                            Try the Chat Now
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Tip -->
                            <div style="background-color: #334155; border-radius: 12px; padding: 20px; margin-top: 10px;">
                                <p style="margin: 0 0 8px; color: #fbbf24; font-weight: 600; font-size: 14px;">
                                    💡 How to get started
                                </p>
                                <p style="margin: 0; color: #94a3b8; font-size: 14px; line-height: 1.6;">
                                    Visit any beach page and click the blue chat bubble in the bottom-right corner. Ask a question like "Is the parking lot open?" or share a tip for other visitors. Your first message is all it takes!
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 30px; border-top: 1px solid #334155; text-align: center;">
                            <p style="margin: 0 0 10px; color: #64748b; font-size: 14px;">
                                See you in the chat! 🏖️
                            </p>
                            <p style="margin: 0; color: #475569; font-size: 12px;">
                                <a href="{{app_url}}" style="color: #fbbf24; text-decoration: none;">{{app_name}}</a>
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

$stmt = $db->prepare("
    INSERT OR IGNORE INTO email_templates (id, slug, name, subject, html_body, description, variables)
    VALUES (:id, :slug, :name, :subject, :html_body, :description, :variables)
");
$stmt->bindValue(':id', $templateId, SQLITE3_TEXT);
$stmt->bindValue(':slug', 'chat-announcement', SQLITE3_TEXT);
$stmt->bindValue(':name', 'Chat Feature Announcement', SQLITE3_TEXT);
$stmt->bindValue(':subject', 'New: Chat with fellow beach lovers on {{app_name}}! 💬', SQLITE3_TEXT);
$stmt->bindValue(':html_body', $html, SQLITE3_TEXT);
$stmt->bindValue(':description', 'Announce the new chat feature to existing users', SQLITE3_TEXT);
$stmt->bindValue(':variables', $variables, SQLITE3_TEXT);
$stmt->execute();

echo "✓ Added chat-announcement email template\n";
echo "Done.\n";
