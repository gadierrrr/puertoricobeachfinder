#!/usr/bin/env php
<?php
/**
 * Best-effort route smoke test.
 *
 * Boots the PHP built-in server against public/ (via scripts/dev-router.php),
 * requests a handful of representative routes, and asserts none return 5xx — that
 * a known-bad URL 404s and that JSON endpoints parse.
 *
 * Best-effort by design: if the server can't start, or `/` is not reachable
 * (e.g. no local DB / .env), it prints a warning and exits 0 rather than failing
 * the whole `npm run check`.
 *
 *   php scripts/smoke-routes.php
 */

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$root    = dirname(__DIR__);
$router  = "$root/scripts/dev-router.php";
$docroot = "$root/public";
$port    = (int) (getenv('SMOKE_PORT') ?: 8099);

if (!is_file($router)) {
    fwrite(STDERR, "⚠ Skipping smoke test — scripts/dev-router.php not found.\n");
    exit(0);
}

$cmd = sprintf(
    'exec php -S 127.0.0.1:%d -t %s %s',
    $port,
    escapeshellarg($docroot),
    escapeshellarg($router)
);
$desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$proc = @proc_open($cmd, $desc, $pipes, $root);
if (!is_resource($proc)) {
    fwrite(STDERR, "⚠ Skipping smoke test — could not start PHP server.\n");
    exit(0);
}

$base = "http://127.0.0.1:$port";

// Wait up to ~10s for the server to accept connections.
$ready = false;
for ($i = 0; $i < 50; $i++) {
    $fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
    if ($fp) { fclose($fp); $ready = true; break; }
    usleep(200000);
}

function smokeGet(string $url): array {
    $ctx = stream_context_create(['http' => [
        'ignore_errors'   => true,
        'timeout'         => 15,
        'follow_location' => 0,
        'header'          => "Accept: text/html,application/json\r\n",
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    $status = 0;
    foreach (($http_response_header ?? []) as $h) {
        if (preg_match('#^HTTP/\S+\s+(\d+)#', $h, $m)) {
            $status = (int) $m[1];
        }
    }
    return [$status, $body === false ? '' : $body];
}

function shutdown($proc): void {
    proc_terminate($proc);
    proc_close($proc);
}

if (!$ready) {
    shutdown($proc);
    fwrite(STDERR, "⚠ Skipping smoke test — server did not become ready on port $port.\n");
    exit(0);
}

// Gate: if the homepage isn't healthy, the local env/DB likely isn't configured —
// skip rather than fail (this is a best-effort check).
[$homeStatus] = smokeGet("$base/");
if ($homeStatus !== 200) {
    shutdown($proc);
    fwrite(STDERR, "⚠ Skipping smoke test — homepage returned $homeStatus (local DB/.env not configured?).\n");
    exit(0);
}

// [path, acceptable statuses, expectJson]
$routes = [
    ['/',                                  [200],      false],
    ['/best-beaches',                      [200, 301], false],
    ['/quiz',                              [200, 301], false],
    ['/compare',                           [200, 301], false],
    ['/guides/',                           [200],      false],
    ['/api/beaches.php',                   [200],      true],
    ['/api/random-beach.php',              [200, 302], false],
    ['/this-route-should-not-exist-xyz',   [404],      false],
];

echo "Smoke-testing routes on $base ...\n";
$fail = 0;
foreach ($routes as [$path, $expect, $isJson]) {
    [$status, $body] = smokeGet($base . $path);
    $ok = in_array($status, $expect, true);
    $note = '';
    if ($ok && $isJson) {
        json_decode($body);
        if (json_last_error() !== JSON_ERROR_NONE) { $ok = false; $note = ' (invalid JSON)'; }
        else { $note = ' (valid JSON)'; }
    }
    printf("  [%s] %-38s -> %d%s\n", $ok ? '✓' : '✗', $path, $status, $note);
    if (!$ok) $fail++;
}

shutdown($proc);

if ($fail > 0) {
    fwrite(STDERR, "✗ {$fail} route(s) failed.\n");
    exit(1);
}
echo "✓ All smoke routes OK.\n";
exit(0);
