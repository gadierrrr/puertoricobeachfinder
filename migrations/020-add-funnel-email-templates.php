<?php
/**
 * Migration: Add funnel email templates (list-send, quiz-results)
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: Funnel email templates\n";

$db = getDb();

$insert = $db->prepare("
    INSERT OR IGNORE INTO email_templates (id, slug, name, subject, html_body, description, variables)
    VALUES (:id, :slug, :name, :subject, :html_body, :description, :variables)
");

// list-send
$listVars = json_encode([
    ['name' => 'title', 'description' => 'Email title'],
    ['name' => 'subtitle', 'description' => 'Email subtitle'],
    ['name' => 'page_url', 'description' => 'Deep link back to the list'],
    ['name' => 'items_html', 'description' => 'List items as HTML <li> elements'],
    ['name' => 'app_name', 'description' => 'Application name'],
    ['name' => 'app_url', 'description' => 'Application URL'],
]);

$listHtml = <<<'HTML'
<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#0f172a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
  <div style="max-width:640px;margin:0 auto;padding:24px;">
    <div style="background:#1e293b;border-radius:16px;overflow:hidden;border:1px solid #334155;">
      <div style="padding:20px 24px;background:linear-gradient(135deg,#fbbf24 0%,#f59e0b 100%);color:#0f172a;">
        <h1 style="margin:0;font-size:20px;">{{title}}</h1>
        <p style="margin:8px 0 0;opacity:.85;">{{subtitle}}</p>
      </div>
      <div style="padding:22px 24px;color:#e2e8f0;">
        <p style="margin:0 0 14px;color:#94a3b8;">Open this page again anytime:</p>
        <p style="margin:0 0 18px;"><a href="{{page_url}}" style="color:#fbbf24;text-decoration:none;">{{page_url}}</a></p>
        <ol style="margin:0;padding-left:18px;line-height:1.4;">{{items_html}}</ol>
        <p style="margin:18px 0 0;color:#64748b;font-size:12px;">Sent by <a href="{{app_url}}" style="color:#fbbf24;text-decoration:none;">{{app_name}}</a>.</p>
      </div>
    </div>
  </div>
</body>
</html>
HTML;

$insert->bindValue(':id', 'tpl_' . bin2hex(random_bytes(8)), SQLITE3_TEXT);
$insert->bindValue(':slug', 'list-send', SQLITE3_TEXT);
$insert->bindValue(':name', 'Send List', SQLITE3_TEXT);
$insert->bindValue(':subject', '{{title}}', SQLITE3_TEXT);
$insert->bindValue(':html_body', $listHtml, SQLITE3_TEXT);
$insert->bindValue(':description', 'Sent when a visitor requests a list of beaches by email', SQLITE3_TEXT);
$insert->bindValue(':variables', $listVars, SQLITE3_TEXT);
$insert->execute();
echo "✓ Added list-send template\n";

// quiz-results
$quizVars = json_encode([
    ['name' => 'results_url', 'description' => 'Shareable quiz results link'],
    ['name' => 'items_html', 'description' => 'Result items as HTML <li> elements'],
    ['name' => 'app_name', 'description' => 'Application name'],
    ['name' => 'app_url', 'description' => 'Application URL'],
]);

$quizHtml = <<<'HTML'
<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#0f172a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
  <div style="max-width:640px;margin:0 auto;padding:24px;">
    <div style="background:#1e293b;border-radius:16px;overflow:hidden;border:1px solid #334155;">
      <div style="padding:20px 24px;background:linear-gradient(135deg,#fbbf24 0%,#f59e0b 100%);color:#0f172a;">
        <h1 style="margin:0;font-size:20px;">Your Puerto Rico Beach Matches</h1>
        <p style="margin:8px 0 0;opacity:.85;">Open the full list here:</p>
      </div>
      <div style="padding:22px 24px;color:#e2e8f0;">
        <p style="margin:0 0 18px;"><a href="{{results_url}}" style="color:#fbbf24;text-decoration:none;">{{results_url}}</a></p>
        <ol style="margin:0;padding-left:18px;line-height:1.4;">{{items_html}}</ol>
        <p style="margin:18px 0 0;color:#64748b;font-size:12px;">Sent by <a href="{{app_url}}" style="color:#fbbf24;text-decoration:none;">{{app_name}}</a>.</p>
      </div>
    </div>
  </div>
</body>
</html>
HTML;

$insert = $db->prepare("
    INSERT OR IGNORE INTO email_templates (id, slug, name, subject, html_body, description, variables)
    VALUES (:id, :slug, :name, :subject, :html_body, :description, :variables)
");
$insert->bindValue(':id', 'tpl_' . bin2hex(random_bytes(8)), SQLITE3_TEXT);
$insert->bindValue(':slug', 'quiz-results', SQLITE3_TEXT);
$insert->bindValue(':name', 'Quiz Results', SQLITE3_TEXT);
$insert->bindValue(':subject', 'Your Puerto Rico Beach Matches', SQLITE3_TEXT);
$insert->bindValue(':html_body', $quizHtml, SQLITE3_TEXT);
$insert->bindValue(':description', 'Sent when a visitor emails their quiz results', SQLITE3_TEXT);
$insert->bindValue(':variables', $quizVars, SQLITE3_TEXT);
$insert->execute();
echo "✓ Added quiz-results template\n";

echo "✅ Migration completed: Funnel email templates\n";

