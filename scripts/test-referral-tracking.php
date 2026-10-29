#!/usr/bin/env php
<?php
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/inc/referrals.php';
if (appEnv() !== 'dev') throw new RuntimeException('Use a disposable development database.');
function trackingCheck(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
$db = getDb();
$db->exec('BEGIN');
try {
    $campaign = queryOne('SELECT c.*, p.slug AS provider_slug FROM referral_campaigns c JOIN referral_providers p ON p.id=c.provider_id WHERE c.status="active" AND p.status="active" AND p.slug="viator" LIMIT 1');
    trackingCheck((bool) $campaign, 'Seeded active campaign required');
    $count = static fn(): int => (int) $db->querySingle('SELECT COUNT(*) FROM referral_clicks');
    $before = $count();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
    $_GET = [];
    foreach ([['REQUEST_METHOD'=>'HEAD'], ['HTTP_USER_AGENT'=>'Googlebot'], ['HTTP_SEC_PURPOSE'=>'prefetch;prerender'], ['HTTP_PURPOSE'=>'prefetch']] as $case) {
        $saved = $_SERVER;
        $_SERVER = array_merge($_SERVER, $case);
        $result = referralResolveRedirect($campaign['slug']);
        trackingCheck($result['ok'] && $result['click_id'] === '', 'Automated request must still resolve without a click');
        trackingCheck($count() === $before, 'Automated request polluted click ledger');
        $_SERVER = $saved;
    }
    $_GET['bf_analytics_probe'] = '1';
    trackingCheck(referralResolveRedirect($campaign['slug'])['click_id'] === '', 'Probe counted as visitor');
    $_GET = [];
    $result = referralResolveRedirect($campaign['slug'], ['page_type'=>'guide','page_slug'=>'bioluminescent-bays']);
    trackingCheck($result['ok'] && $result['click_id'] !== '' && $count() === $before + 1, 'Real navigation must write one click');
    execute('UPDATE referral_campaigns SET target_url="" WHERE id=:id', [':id'=>$campaign['id']]);
    $result = referralResolveRedirect($campaign['slug']);
    trackingCheck(!$result['ok'] && $count() === $before + 1, 'Missing destination must not write a click');
    execute('UPDATE referral_campaigns SET target_url=:url WHERE id=:id', [':url'=>$campaign['target_url'], ':id'=>$campaign['id']]);
    $exactUrl = 'https://www.viator.com/tours/x/x/d903-99999P1?pid=test&campaign=api%20campaign&mcid=api';
    execute('INSERT INTO viator_products(product_code,locale,status) VALUES("99999P1","en","ACTIVE")');
    execute('INSERT OR REPLACE INTO viator_product_links(campaign_id,product_code,locale,campaign_value,product_url)
        VALUES(:campaign,"99999P1","en","api campaign",:url)', [':campaign'=>$campaign['id'], ':url'=>$exactUrl]);
    $result = referralResolveRedirect($campaign['slug'], ['product_code'=>'99999P1','locale'=>'en']);
    trackingCheck($result['ok'] && $result['target_url']===$exactUrl && $result['attribution_mode']==='api_product_url', 'API attribution URL must remain byte-for-byte intact: ' . json_encode($result));
    trackingCheck($count()===$before+2, 'API redirect must write exactly one local click');
    echo "Referral request filtering checks passed.\n";
} finally {
    $db->exec('ROLLBACK');
}
