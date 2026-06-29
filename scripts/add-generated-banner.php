#!/usr/bin/env php
<?php
/**
 * Prepend a "generated file" banner to a built asset (idempotent).
 *
 * Used by the npm build scripts so compiled outputs (tailwind.min.css, *.min.js)
 * advertise that they should not be hand-edited. build-css.sh writes its own
 * banner directly.
 *
 *   php scripts/add-generated-banner.php <file> <css|js>
 */

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$file = $argv[1] ?? null;
$type = $argv[2] ?? 'css';

if (!$file || !is_file($file)) {
    fwrite(STDERR, "usage: php scripts/add-generated-banner.php <file> <css|js>\n");
    exit(1);
}

$marker  = 'GENERATED FILE — DO NOT EDIT';
$content = file_get_contents($file);

if (strpos($content, $marker) !== false) {
    exit(0); // already banded; idempotent
}

$banner = "/* {$marker}. Edit the source then run `npm run build`. */\n";
file_put_contents($file, $banner . $content);
echo "✓ banner added to {$file}\n";
