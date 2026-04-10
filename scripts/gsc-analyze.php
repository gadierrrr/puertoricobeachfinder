<?php
/**
 * Reads data/gsc-reports/*.json and prints actionable findings.
 */
$dir = __DIR__ . '/../data/gsc-reports';

function load(string $f): array {
    $j = json_decode(file_get_contents($f), true);
    return $j['rows'] ?? [];
}

$queries     = load("$dir/by-query.json");
$pages       = load("$dir/by-page.json");
$queryPages  = load("$dir/by-query-page.json");
$devices     = load("$dir/by-device.json");
$countries   = load("$dir/by-country.json");

// 1. Totals
$totC = $totI = 0; $posSum = 0; $posN = 0;
foreach ($queries as $r) {
    $totC += $r['clicks'];
    $totI += $r['impressions'];
    $posSum += $r['position'] * $r['impressions'];
    $posN += $r['impressions'];
}
$avgPos = $posN ? $posSum / $posN : 0;
$ctr    = $totI ? $totC / $totI * 100 : 0;
echo "=== TOTALS (last 28 days, query-level cap 1000) ===\n";
echo "Clicks:       $totC\n";
echo "Impressions:  $totI\n";
printf("CTR:          %.2f%%\n", $ctr);
printf("Avg position: %.1f\n",  $avgPos);
echo "\n";

// 2. Devices
echo "=== DEVICE SPLIT ===\n";
printf("%-10s %10s %15s %8s %8s\n", 'Device', 'Clicks', 'Impressions', 'CTR%', 'Pos');
foreach ($devices as $r) {
    printf("%-10s %10d %15d %7.2f%% %8.1f\n",
        $r['keys'][0], $r['clicks'], $r['impressions'], $r['ctr']*100, $r['position']);
}
echo "\n";

// 3. Top countries
echo "=== TOP 10 COUNTRIES ===\n";
$countriesSorted = $countries;
usort($countriesSorted, fn($a,$b) => $b['impressions'] <=> $a['impressions']);
printf("%-6s %10s %15s %8s %8s\n", 'Country','Clicks','Impressions','CTR%','Pos');
foreach (array_slice($countriesSorted,0,10) as $r) {
    printf("%-6s %10d %15d %7.2f%% %8.1f\n",
        $r['keys'][0], $r['clicks'], $r['impressions'], $r['ctr']*100, $r['position']);
}
echo "\n";

// 4. High-impression, low-CTR queries (needs better title/meta or snippet)
echo "=== HIGH IMPRESSIONS, LOW CTR (impressions>=300, CTR<2%, pos<=15) ===\n";
$lowCtr = array_filter($queries, fn($r) => $r['impressions'] >= 300 && $r['ctr'] < 0.02 && $r['position'] <= 15);
usort($lowCtr, fn($a,$b) => $b['impressions'] <=> $a['impressions']);
printf("%-50s %10s %10s %8s %8s\n", 'Query','Impr','Clicks','CTR%','Pos');
foreach (array_slice($lowCtr,0,25) as $r) {
    printf("%-50s %10d %10d %7.2f%% %8.1f\n",
        substr($r['keys'][0],0,49), $r['impressions'], $r['clicks'], $r['ctr']*100, $r['position']);
}
echo "\n";

// 5. Striking distance: position 5-20 with traffic potential
echo "=== STRIKING DISTANCE QUERIES (pos 5-20, impr>=100) ===\n";
$strike = array_filter($queries, fn($r) => $r['position'] >= 5 && $r['position'] <= 20 && $r['impressions'] >= 100);
usort($strike, fn($a,$b) => $b['impressions'] <=> $a['impressions']);
printf("%-50s %10s %10s %8s %8s\n", 'Query','Impr','Clicks','CTR%','Pos');
foreach (array_slice($strike,0,25) as $r) {
    printf("%-50s %10d %10d %7.2f%% %8.1f\n",
        substr($r['keys'][0],0,49), $r['impressions'], $r['clicks'], $r['ctr']*100, $r['position']);
}
echo "\n";

// 6. Top pages
echo "=== TOP 20 PAGES BY CLICKS ===\n";
$pagesSorted = $pages;
usort($pagesSorted, fn($a,$b) => $b['clicks'] <=> $a['clicks']);
printf("%-70s %8s %10s %8s %8s\n", 'Page','Clicks','Impr','CTR%','Pos');
foreach (array_slice($pagesSorted,0,20) as $r) {
    $url = str_replace('https://www.puertoricobeachfinder.com', '', $r['keys'][0]);
    printf("%-70s %8d %10d %7.2f%% %8.1f\n",
        substr($url,0,69), $r['clicks'], $r['impressions'], $r['ctr']*100, $r['position']);
}
echo "\n";

// 7. Pages with high impressions, low CTR
echo "=== PAGES: HIGH IMPRESSIONS, LOW CTR (impr>=500, CTR<1.5%) ===\n";
$pageLowCtr = array_filter($pages, fn($r) => $r['impressions'] >= 500 && $r['ctr'] < 0.015);
usort($pageLowCtr, fn($a,$b) => $b['impressions'] <=> $a['impressions']);
printf("%-70s %8s %10s %8s %8s\n", 'Page','Clicks','Impr','CTR%','Pos');
foreach (array_slice($pageLowCtr,0,20) as $r) {
    $url = str_replace('https://www.puertoricobeachfinder.com', '', $r['keys'][0]);
    printf("%-70s %8d %10d %7.2f%% %8.1f\n",
        substr($url,0,69), $r['clicks'], $r['impressions'], $r['ctr']*100, $r['position']);
}
echo "\n";

// 8. Pages ranked but getting zero clicks
echo "=== ZERO-CLICK PAGES (impr>=200, clicks=0) ===\n";
$zero = array_filter($pages, fn($r) => $r['impressions'] >= 200 && $r['clicks'] == 0);
usort($zero, fn($a,$b) => $b['impressions'] <=> $a['impressions']);
printf("%-70s %10s %8s\n", 'Page','Impr','Pos');
foreach (array_slice($zero,0,20) as $r) {
    $url = str_replace('https://www.puertoricobeachfinder.com', '', $r['keys'][0]);
    printf("%-70s %10d %8.1f\n",
        substr($url,0,69), $r['impressions'], $r['position']);
}
echo "\n";

// 9. Branded vs non-branded
$brand = ['puerto rico beach finder','puertoricobeachfinder','beach finder pr','prbeachfinder'];
$bC = $bI = 0; $nC = $nI = 0;
foreach ($queries as $r) {
    $isBrand = false;
    foreach ($brand as $b) if (stripos($r['keys'][0], $b) !== false) { $isBrand = true; break; }
    if ($isBrand) { $bC += $r['clicks']; $bI += $r['impressions']; }
    else          { $nC += $r['clicks']; $nI += $r['impressions']; }
}
echo "=== BRANDED VS NON-BRANDED (within top-1000 query sample) ===\n";
printf("Branded:     %5d clicks / %7d impr (CTR %.1f%%)\n", $bC, $bI, $bI ? $bC/$bI*100 : 0);
printf("Non-branded: %5d clicks / %7d impr (CTR %.1f%%)\n", $nC, $nI, $nI ? $nC/$nI*100 : 0);
echo "\n";

// 10. Top winning queries (already getting clicks)
echo "=== TOP 20 QUERIES BY CLICKS ===\n";
$qSorted = $queries;
usort($qSorted, fn($a,$b) => $b['clicks'] <=> $a['clicks']);
printf("%-50s %10s %10s %8s %8s\n", 'Query','Clicks','Impr','CTR%','Pos');
foreach (array_slice($qSorted,0,20) as $r) {
    printf("%-50s %10d %10d %7.2f%% %8.1f\n",
        substr($r['keys'][0],0,49), $r['clicks'], $r['impressions'], $r['ctr']*100, $r['position']);
}
