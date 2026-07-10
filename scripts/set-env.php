#!/usr/bin/env php
<?php
/**
 * Set one approved environment value from STDIN without echoing the value.
 * Intended for deployment-time secret injection from a secure pipe.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once __DIR__ . '/../bootstrap.php';

$allowed = [
    'VIATOR_PID',
    'VIATOR_API_KEY',
    'VIATOR_API_BASE_URL',
    'VIATOR_API_ENABLED',
    'VIATOR_SYNC_TTL_HOURS',
];

$key = trim((string) ($argv[1] ?? ''));
if (!in_array($key, $allowed, true)) {
    fwrite(STDERR, "Unsupported environment key.\n");
    exit(2);
}

$value = trim((string) stream_get_contents(STDIN));
if ($value === '' || str_contains($value, "\n") || str_contains($value, "\r")) {
    fwrite(STDERR, "Environment value must be one non-empty line.\n");
    exit(3);
}

$envPath = APP_ROOT . '/.env';
if (!is_file($envPath)) {
    fwrite(STDERR, ".env does not exist.\n");
    exit(4);
}

$contents = file_get_contents($envPath);
if (!is_string($contents)) {
    fwrite(STDERR, "Unable to read .env.\n");
    exit(5);
}
$originalStat = stat($envPath);
if (!is_array($originalStat)) {
    fwrite(STDERR, "Unable to inspect .env permissions.\n");
    exit(6);
}

$encoded = $key . '=' . $value;
$pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
if (preg_match($pattern, $contents) === 1) {
    $next = preg_replace($pattern, $encoded, $contents, 1);
} else {
    $next = rtrim($contents) . "\n" . $encoded . "\n";
}

if (!is_string($next)) {
    fwrite(STDERR, "Unable to update .env content.\n");
    exit(7);
}

$temp = $envPath . '.tmp.' . bin2hex(random_bytes(4));
if (file_put_contents($temp, $next, LOCK_EX) === false) {
    fwrite(STDERR, "Unable to write temporary environment file.\n");
    exit(8);
}
@chmod($temp, ((int) $originalStat['mode']) & 0777);
@chown($temp, (int) $originalStat['uid']);
@chgrp($temp, (int) $originalStat['gid']);
if (!rename($temp, $envPath)) {
    @unlink($temp);
    fwrite(STDERR, "Unable to replace .env.\n");
    exit(9);
}

echo $key . " updated.\n";
