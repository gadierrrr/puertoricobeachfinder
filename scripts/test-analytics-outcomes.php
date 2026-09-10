#!/usr/bin/env php
<?php
require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/inc/analytics.php';
require_once APP_ROOT . '/inc/auth.php';
require_once APP_ROOT . '/inc/session.php';
if (appEnv() !== 'dev') {
    fwrite(STDERR, "Use a disposable development database.\n");
    exit(1);
}
session_start();
function outcomeCheck(bool $value, string $message): void {
    if (!$value) throw new RuntimeException($message);
}
$email = 'outcome-' . bin2hex(random_bytes(6)) . '@example.test';
try {
    outcomeCheck(takeAnalyticsOutcomes() === [], 'Empty queue must not emit events');
    queueAnalyticsOutcome('generate_lead', ['source'=>'advertise', 'email'=>'private@example.test', 'message'=>'private']);
    $event = takeAnalyticsOutcomes()[0];
    outcomeCheck($event['props'] === ['source'=>'advertise'], 'Private properties must be stripped');
    outcomeCheck(takeAnalyticsOutcomes() === [], 'Queue must be consumed once');
    $bad = verifyMagicLink('invalid-token');
    outcomeCheck(!$bad['success'] && takeAnalyticsOutcomes() === [], 'Invalid token must not produce signup');
    foreach ([true, false] as $firstVisit) {
        $token = bin2hex(random_bytes(16));
        execute('INSERT INTO magic_links (id,email,token,expires_at) VALUES (:id,:email,:token,datetime("now","+5 minutes"))',
            [':id'=>uuid(), ':email'=>$email, ':token'=>hash('sha256',$token)]);
        $result = verifyMagicLink($token);
        outcomeCheck($result['success'], 'Valid magic link must sign in');
        $events = takeAnalyticsOutcomes();
        outcomeCheck(count($events) === ($firstVisit ? 1 : 0), 'Only a newly created verified user is a signup');
        if ($firstVisit) outcomeCheck($events[0]['name'] === 'sign_up' && $events[0]['props']['method'] === 'email', 'Signup method missing');
        outcomeCheck(!verifyMagicLink($token)['success'] && takeAnalyticsOutcomes() === [], 'Replayed token must not emit another signup');
    }
    echo "Analytics outcome checks passed.\n";
} finally {
    execute('DELETE FROM magic_links WHERE email=:email', [':email'=>$email]);
    execute('DELETE FROM users WHERE email=:email', [':email'=>$email]);
}
