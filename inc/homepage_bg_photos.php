<?php
/**
 * Hero background photo storage for the homepage design editor.
 * Files live in uploads/admin/homepage/ (served by nginx at
 * /uploads/admin/homepage/…), uploaded via /api/admin/homepage-design.php.
 */

if (defined('HOMEPAGE_BG_PHOTOS_INCLUDED')) {
    return;
}
define('HOMEPAGE_BG_PHOTOS_INCLUDED', true);

function homepageBgPhotoDir(): string {
    return APP_ROOT . '/uploads/admin/homepage';
}

/**
 * @return string[] public URLs, newest first
 */
function listHomepageBgPhotos(): array {
    $dir = homepageBgPhotoDir();
    if (!is_dir($dir)) {
        return [];
    }
    $files = glob($dir . '/*.webp') ?: [];
    usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
    return array_map(fn($f) => '/uploads/admin/homepage/' . basename($f), $files);
}
