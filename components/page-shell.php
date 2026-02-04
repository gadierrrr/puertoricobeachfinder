<?php
/**
 * Shared public page shell.
 *
 * Usage:
 *   $pageShellMode = 'start';
 *   include __DIR__ . '/components/page-shell.php';
 *   ...page content...
 *   $pageShellMode = 'end';
 *   include __DIR__ . '/components/page-shell.php';
 */

$pageShellMode = $pageShellMode ?? 'start';
$pageTheme = $pageTheme ?? 'home';

if (!isset($bodyVariant)) {
    $bodyVariant = $pageTheme === 'light' ? 'collection-light' : 'collection-dark';
}

if ($pageShellMode === 'start') {
    include __DIR__ . '/header.php';
    return;
}

if ($pageShellMode === 'end') {
    include __DIR__ . '/footer.php';
    return;
}

throw new InvalidArgumentException('Invalid page shell mode: ' . (string) $pageShellMode);
