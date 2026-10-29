#!/usr/bin/env php
<?php
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/inc/referral_reporting.php';
if (appEnv() !== 'dev') throw new RuntimeException('Use a disposable development database.');
function reportCheck(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
$id = uuid();
$campaignId = uuid();
$file = tempnam(sys_get_temp_dir(), 'referral-csv-');
execute('INSERT INTO referral_providers(id,slug,name,status) VALUES(:id,:slug,"Test","active")', [':id'=>$id,':slug'=>'test-'.$id]);
execute('INSERT INTO referral_campaigns(id,provider_id,slug,name,status,link_type) VALUES(:id,:provider,"test-campaign","Test","active","tour_product")', [':id'=>$campaignId,':provider'=>$id]);
try {
    // Real Viator export headers, synthetic values. API and links share a campaign.
    file_put_contents($file, "Campaign Name,Source Type,Page Views,Sessions,Unique Visitors,Bookings,Session Conversion Rate,Visitor Conversion Rate,Gross Booking Value (USD),Gross Commission (USD)\ntest-campaign,api,30,12,10,2,0.1,0.2,100,8\ntest-campaign,links,8,5,4,1,0.2,0.25,50,4\n");
    $rejected = false;
    try { referralImportCampaignCsv($id,$file); } catch (RuntimeException $e) { $rejected = str_contains($e->getMessage(),'no dates'); }
    reportCheck($rejected, 'Undated exports must require the selected reporting period');
    $period = ['start'=>'2026-09-28','end'=>'2026-10-27'];
    for ($n=0;$n<2;$n++) {
        $result = referralImportCampaignCsv($id,$file,$period);
        reportCheck($result['summary']['upserted']===2 && $result['summary']['errors']===0, 'Original CSV format must import');
    }
    $rows = query('SELECT * FROM referral_campaign_reports WHERE provider_id=:id ORDER BY source_type', [':id'=>$id]);
    reportCheck(count($rows)===2, 'Repeat imports must not duplicate or overwrite API/link rows');
    reportCheck((int)$rows[0]['visitors']===10 && (int)$rows[1]['visitors']===4, 'Unique visitors must win over sessions');
    reportCheck((float)$rows[0]['commission_value']===8.0 && (float)$rows[1]['booking_value']===50.0, 'Currency-suffixed amounts must be retained');
    reportCheck($rows[0]['period_start']===$period['start'] && $rows[0]['period_end']===$period['end'], 'Period must not become a single day');
    referralImportCampaignCsv($id,$file,['start'=>'2026-10-01','end'=>'2026-10-28']);
    reportCheck((int)getDb()->querySingle('SELECT COUNT(*) FROM referral_campaign_reports WHERE provider_id="'.$id.'"')===4, 'Overlapping exports must stay distinct snapshots');
    $funnel = referralViatorFunnelReport($period['start'], $period['end'], 'test-'.$id);
    reportCheck(count($funnel)===1 && (int)$funnel[0]['bookings']===3 && (int)$funnel[0]['visitors']===14, 'Funnel must not double-count overlapping exports');
    file_put_contents($file, "date,campaign,visitors,gross bookings,gross booking value,gross commission,currency\n2026-09-01,test-campaign,5,1,70,5.6,EUR\n");
    referralImportCampaignCsv($id,$file);
    $daily = queryOne('SELECT * FROM referral_campaign_reports WHERE provider_id=:id AND period_start="2026-09-01"', [':id'=>$id]);
    reportCheck((int)$daily['bookings']===1 && $daily['currency']==='EUR' && $daily['period_start']===$daily['period_end'], 'Normalized daily exports must remain supported');
    file_put_contents($file, "external_conversion_id,campaign_slug,booking_value,commission_value,currency,booked_at\ntest-booking,test-campaign,70,5.6,USD,2026-09-01\n");
    referralImportCampaignCsv($id,$file);
    referralImportCampaignCsv($id,$file);
    $conversions = query('SELECT * FROM referral_conversions WHERE provider_id=:id', [':id'=>$id]);
    reportCheck(count($conversions)===1 && (float)$conversions[0]['commission_value']===5.6, 'Individual conversions must remain separate and idempotent');
    echo "Referral CSV format, periods, source types, currency and repeat-import checks passed.\n";
} finally {
    execute('DELETE FROM referral_providers WHERE id=:id', [':id'=>$id]);
    unlink($file);
}
