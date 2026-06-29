#!/usr/bin/env php
<?php
/**
 * Detect duplicate global function declarations in the shared include layer.
 *
 * Two shared files that each declare the same top-level function cause a fatal
 * "Cannot redeclare function" (HTTP 500) when both are included in one request —
 * the #1 ERROR 500 cause called out in CLAUDE.md.
 *
 * Scans inc/ and components/ — the files that are require_once'd together on
 * nearly every request. Uses the PHP tokenizer, so only real top-level PHP
 * functions count (class methods, closures, and JS inside inline <script> blocks
 * are ignored). Standalone page/API entrypoints under public/ are intentionally
 * out of scope: they are never included into one another.
 *
 * Exits 1 if any name is declared in two or more shared files.
 *
 *   php scripts/check-duplicate-functions.php
 */

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$root = dirname(__DIR__);
$dirs = ['inc', 'components'];

/** Return names of top-level (non-method, non-closure) PHP functions in a file. */
function cdf_globalFunctions(string $path): array {
    $tokens = token_get_all(file_get_contents($path));
    $n = count($tokens);
    $depth = 0;
    $names = [];
    for ($i = 0; $i < $n; $i++) {
        $t = $tokens[$i];
        if ($t === '{') { $depth++; continue; }
        if ($t === '}') { $depth--; continue; }
        if (is_array($t) && ($t[0] === T_CURLY_OPEN || $t[0] === T_DOLLAR_OPEN_CURLY_BRACES)) { $depth++; continue; }
        if (is_array($t) && $t[0] === T_FUNCTION) {
            $j = $i + 1;
            while ($j < $n && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
            if (isset($tokens[$j]) && $tokens[$j] === '&') { // return-by-reference
                $j++;
                while ($j < $n && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
            }
            if ($depth === 0 && isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                $names[] = $tokens[$j][1];
            }
        }
    }
    return $names;
}

$map = [];
foreach ($dirs as $d) {
    $base = "$root/$d";
    if (!is_dir($base)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!$f->isFile() || $f->getExtension() !== 'php') continue;
        $rel = ltrim(str_replace($root, '', $f->getPathname()), '/');
        foreach (cdf_globalFunctions($f->getPathname()) as $name) {
            $map[$name][$rel] = true;
        }
    }
}

$dupes = [];
foreach ($map as $name => $files) {
    if (count($files) > 1) {
        $dupes[$name] = array_keys($files);
    }
}

if ($dupes) {
    fwrite(STDERR, "✗ Duplicate global function declarations found in the shared include layer:\n");
    foreach ($dupes as $name => $files) {
        fwrite(STDERR, "  - {$name}() declared in:\n");
        foreach ($files as $file) {
            fwrite(STDERR, "      {$file}\n");
        }
    }
    fwrite(STDERR, "\nThese cause 'Cannot redeclare function' (HTTP 500) when co-loaded. Rename or consolidate.\n");
    exit(1);
}

echo "✓ No duplicate global functions in inc/ + components/ (" . count($map) . " unique).\n";
exit(0);
