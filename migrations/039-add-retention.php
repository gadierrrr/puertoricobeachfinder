<?php
/**
 * Migration 039: Retention features (Sprint 4)
 *  - Seeds the 'weekly-digest' email template (item 15).
 *  - Creates push_subscriptions for web push (item 16, Phase 1 capture).
 *  - Adds user-to-user referral schema (item 17): users.referral_code / referred_by
 *    + user_referrals table. (Separate from the affiliate system in migration 023.)
 * Idempotent — safe to re-run.
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: retention features\n";

$db = getDb();

// ---------------------------------------------------------------------------
// 1) weekly-digest email template
// ---------------------------------------------------------------------------
$digestVars = json_encode([
    ['name' => 'name', 'description' => 'Recipient first name'],
    ['name' => 'intro', 'description' => 'Intro line'],
    ['name' => 'items_html', 'description' => 'Beach list as HTML <li> elements (raw)'],
    ['name' => 'app_url', 'description' => 'Application URL'],
    ['name' => 'app_name', 'description' => 'Application name'],
    ['name' => 'unsubscribe_url', 'description' => 'One-click unsubscribe link'],
]);

$digestHtml = <<<'HTML'
<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#0f172a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
  <div style="max-width:640px;margin:0 auto;padding:24px;">
    <div style="background:#1e293b;border-radius:16px;overflow:hidden;border:1px solid #334155;">
      <div style="padding:20px 24px;background:linear-gradient(135deg,#fb923c 0%,#f97316 100%);color:#0c3d42;">
        <h1 style="margin:0;font-size:20px;">Good beach days ahead, {{name}} 🏖️</h1>
        <p style="margin:8px 0 0;opacity:.85;">{{intro}}</p>
      </div>
      <div style="padding:22px 24px;color:#e2e8f0;">
        <ul style="margin:0;padding-left:18px;line-height:1.5;list-style:none;">{{items_html}}</ul>
        <p style="margin:20px 0 0;color:#64748b;font-size:12px;">
          Sent by <a href="{{app_url}}" style="color:#fb923c;text-decoration:none;">{{app_name}}</a>.
          <br><a href="{{unsubscribe_url}}" style="color:#64748b;text-decoration:underline;">Unsubscribe from the weekly digest</a>.
        </p>
      </div>
    </div>
  </div>
</body>
</html>
HTML;

$insTpl = $db->prepare("
    INSERT OR IGNORE INTO email_templates (id, slug, name, subject, html_body, description, variables)
    VALUES (:id, :slug, :name, :subject, :html_body, :description, :variables)
");
$insTpl->bindValue(':id', 'tpl_' . bin2hex(random_bytes(8)), SQLITE3_TEXT);
$insTpl->bindValue(':slug', 'weekly-digest', SQLITE3_TEXT);
$insTpl->bindValue(':name', 'Weekly Digest', SQLITE3_TEXT);
$insTpl->bindValue(':subject', 'Good beach days near your favorites', SQLITE3_TEXT);
$insTpl->bindValue(':html_body', $digestHtml, SQLITE3_TEXT);
$insTpl->bindValue(':description', 'Weekly "good conditions near your favorites" digest', SQLITE3_TEXT);
$insTpl->bindValue(':variables', $digestVars, SQLITE3_TEXT);
$insTpl->execute();
echo "  ✓ weekly-digest template seeded\n";

// ---------------------------------------------------------------------------
// 2) push_subscriptions (web push capture)
// ---------------------------------------------------------------------------
$db->exec("CREATE TABLE IF NOT EXISTS push_subscriptions (
    id TEXT PRIMARY KEY,
    user_id TEXT,
    endpoint TEXT NOT NULL UNIQUE,
    p256dh TEXT NOT NULL,
    auth TEXT NOT NULL,
    ua TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_used_at TEXT
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_push_subs_user ON push_subscriptions(user_id)");
echo "  ✓ push_subscriptions table\n";

// ---------------------------------------------------------------------------
// 3) user-to-user referral schema
// ---------------------------------------------------------------------------
$cols = [];
$res = $db->query('PRAGMA table_info(users)');
while ($res && ($c = $res->fetchArray(SQLITE3_ASSOC))) {
    $cols[$c['name']] = true;
}
if (!isset($cols['referral_code'])) {
    $db->exec('ALTER TABLE users ADD COLUMN referral_code TEXT');
    echo "  ✓ users.referral_code\n";
} else {
    echo "  - users.referral_code already exists\n";
}
// Always ensure the unique index (idempotent) so an interrupted prior run can't leave
// the column without its uniqueness guarantee.
$db->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_users_referral_code ON users(referral_code)');
if (!isset($cols['referred_by'])) {
    $db->exec('ALTER TABLE users ADD COLUMN referred_by TEXT');
    echo "  ✓ users.referred_by\n";
} else {
    echo "  - users.referred_by already exists\n";
}

$db->exec("CREATE TABLE IF NOT EXISTS user_referrals (
    id TEXT PRIMARY KEY,
    referrer_user_id TEXT NOT NULL,
    referred_user_id TEXT NOT NULL UNIQUE,
    code TEXT,
    ip_hash TEXT,
    ua_hash TEXT,
    status TEXT NOT NULL DEFAULT 'pending',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    rewarded_at TEXT
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_user_referrals_referrer ON user_referrals(referrer_user_id)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_user_referrals_code ON user_referrals(code)");
echo "  ✓ user_referrals table\n";

echo "Retention migration complete.\n";
