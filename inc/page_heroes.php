<?php
/**
 * Managed page header / hero images.
 *
 * Admins can publish a default image for a page family and optionally override
 * any individual public URL. Individual beach profiles are deliberately
 * excluded; their photography remains owned by the beach record.
 */

if (defined('PAGE_HEROES_INCLUDED')) {
    return;
}
define('PAGE_HEROES_INCLUDED', true);

require_once __DIR__ . '/settings.php';

function pageHeroFamilies(): array
{
    return [
        'home' => [
            'label' => 'Homepage',
            'description' => 'The main beach finder and map-view introduction.',
            'example' => '/',
        ],
        'listings' => [
            'label' => 'Beach directories',
            'description' => 'Best-of, activity, municipality, nearby, tag and explore pages.',
            'example' => '/best-beaches',
        ],
        'guides' => [
            'label' => 'Guides',
            'description' => 'The guide directory and every guide article header.',
            'example' => '/guides/',
        ],
        'quiz' => [
            'label' => 'Beach quiz',
            'description' => 'The quiz introduction before a visitor answers questions.',
            'example' => '/quiz',
        ],
        'quiz-results' => [
            'label' => 'Quiz results',
            'description' => 'Shareable “Your Beach Matches” result pages.',
            'example' => '/quiz-results',
        ],
        'compare' => [
            'label' => 'Compare',
            'description' => 'The beach comparison workspace header.',
            'example' => '/compare',
        ],
        'account' => [
            'label' => 'Accounts & saved items',
            'description' => 'Sign in, profile, favorites, lists and onboarding.',
            'example' => '/profile',
        ],
        'legal' => [
            'label' => 'Legal & system pages',
            'description' => 'Terms, privacy, unsubscribe, offline and error pages.',
            'example' => '/privacy',
        ],
        'general' => [
            'label' => 'Other public pages',
            'description' => 'Fallback for public pages that do not match another family.',
            'example' => '/advertise',
        ],
    ];
}

function pageHeroDefaults(): array
{
    return ['families' => [], 'pages' => []];
}

function pageHeroNormalizePath(?string $path): string
{
    if ($path === null || $path === '') {
        $path = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    }
    $parsed = parse_url($path, PHP_URL_PATH);
    $path = is_string($parsed) && $parsed !== '' ? $parsed : '/';
    $path = '/' . ltrim($path, '/');
    if ($path !== '/') {
        $path = rtrim($path, '/');
    }
    return $path;
}

function pageHeroIsBeachProfilePath(string $path): bool
{
    return (bool) preg_match('#^/(?:es/)?(?:beach|playa)/[a-z0-9-]+$#i', pageHeroNormalizePath($path));
}

function pageHeroFamilyForPath(?string $path = null): ?string
{
    $path = pageHeroNormalizePath($path);
    if (pageHeroIsBeachProfilePath($path)) {
        return null;
    }

    if ($path === '/' || $path === '/es') {
        return 'home';
    }
    if (preg_match('#^/(?:es/)?(?:quiz-results|resultados-quiz-playa)$#', $path)) {
        return 'quiz-results';
    }
    if (preg_match('#^/(?:es/)?(?:quiz|quiz-playa)$#', $path)) {
        return 'quiz';
    }
    if (preg_match('#^/(?:es/)?(?:compare|comparar-playas)$#', $path)) {
        return 'compare';
    }
    if (preg_match('#^/(?:es/)?(?:guides|guias)(?:/|$)#', $path)) {
        return 'guides';
    }
    if (preg_match('#^/(?:es/)?(?:best-|mejores-|hidden-|playas-escondidas|beaches(?:-|/)|playas(?:-|/)|municipality|explore|explorar)#', $path)) {
        return 'listings';
    }
    if (preg_match('#^/(?:es/)?(?:login|iniciar-sesion|verify|verificar|onboarding|bienvenida|profile|perfil|favorites|favoritos|lists?|lista|logout|cerrar-sesion)(?:/|$)#', $path)) {
        return 'account';
    }
    if (preg_match('#^/(?:es/)?(?:terms|terminos|privacy|privacidad|unsubscribe|offline|sin-conexion|errors)(?:/|$)#', $path)) {
        return 'legal';
    }
    return 'general';
}

function sanitizePageHeroEntry(array $entry): ?array
{
    $image = trim((string) ($entry['image'] ?? ''));
    if (!preg_match('#^/uploads/admin/page-heroes/[a-z0-9._-]+\.webp$#i', $image)) {
        return null;
    }

    $positions = ['center center', 'center top', 'center bottom', 'left center', 'right center'];
    $position = (string) ($entry['position'] ?? 'center center');
    if (!in_array($position, $positions, true)) {
        $position = 'center center';
    }

    return [
        'image' => $image,
        'position' => $position,
        'overlay' => max(0, min(80, (int) ($entry['overlay'] ?? 46))),
    ];
}

function sanitizePageHeroSettings(array $settings): array
{
    $out = pageHeroDefaults();
    $families = pageHeroFamilies();

    foreach (($settings['families'] ?? []) as $key => $entry) {
        if (!isset($families[$key]) || !is_array($entry)) {
            continue;
        }
        $clean = sanitizePageHeroEntry($entry);
        if ($clean !== null) {
            $out['families'][$key] = $clean;
        }
    }

    foreach (($settings['pages'] ?? []) as $path => $entry) {
        $path = pageHeroNormalizePath((string) $path);
        if (pageHeroIsBeachProfilePath($path)
            || !preg_match('#^/[a-zA-Z0-9/_-]*$#', $path)
            || !is_array($entry)) {
            continue;
        }
        $clean = sanitizePageHeroEntry($entry);
        if ($clean !== null) {
            $out['pages'][$path] = $clean;
        }
    }

    ksort($out['pages']);
    return $out;
}

function getPageHeroSettings(): array
{
    $raw = getSetting('page_hero_images');
    if (!$raw) {
        return pageHeroDefaults();
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? sanitizePageHeroSettings($decoded) : pageHeroDefaults();
}

function setPageHeroSettings(array $settings): array
{
    $settings = sanitizePageHeroSettings($settings);
    setSetting('page_hero_images', json_encode($settings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    return $settings;
}

function pageHeroResolve(?string $family = null, ?string $path = null): ?array
{
    $path = pageHeroNormalizePath($path);
    if (pageHeroIsBeachProfilePath($path)) {
        return null;
    }

    $settings = getPageHeroSettings();
    if (isset($settings['pages'][$path])) {
        return $settings['pages'][$path] + ['scope' => 'page', 'key' => $path];
    }

    $family = $family ?: pageHeroFamilyForPath($path);
    if ($family !== null && isset($settings['families'][$family])) {
        return $settings['families'][$family] + ['scope' => 'family', 'key' => $family];
    }
    return null;
}

function pageHeroAttributes(?string $family = null, ?string $path = null): string
{
    $entry = pageHeroResolve($family, $path);
    if ($entry === null) {
        return '';
    }
    $esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    $style = '--managed-hero-image:url(' . $entry['image'] . ');'
        . '--managed-hero-position:' . $entry['position'] . ';'
        . '--managed-hero-overlay:' . number_format(((int) $entry['overlay']) / 100, 2, '.', '') . ';';
    return ' data-managed-page-hero="' . $esc((string) $entry['key']) . '" style="' . $esc($style) . '"';
}

function pageHeroSetEntry(string $scope, string $key, array $entry): array
{
    $settings = getPageHeroSettings();
    $clean = sanitizePageHeroEntry($entry);
    if ($clean === null) {
        throw new InvalidArgumentException('Invalid page hero image.');
    }

    if ($scope === 'family') {
        if (!isset(pageHeroFamilies()[$key])) {
            throw new InvalidArgumentException('Unknown page family.');
        }
        $settings['families'][$key] = $clean;
    } elseif ($scope === 'page') {
        $key = pageHeroNormalizePath($key);
        if (pageHeroIsBeachProfilePath($key)) {
            throw new InvalidArgumentException('Beach profile headers are managed from their beach record.');
        }
        if (!preg_match('#^/[a-zA-Z0-9/_-]*$#', $key)) {
            throw new InvalidArgumentException('Enter a valid public URL path.');
        }
        $settings['pages'][$key] = $clean;
    } else {
        throw new InvalidArgumentException('Invalid page hero scope.');
    }

    return setPageHeroSettings($settings);
}

function pageHeroDeleteEntry(string $scope, string $key): array
{
    $settings = getPageHeroSettings();
    if ($scope === 'family') {
        unset($settings['families'][$key]);
    } elseif ($scope === 'page') {
        unset($settings['pages'][pageHeroNormalizePath($key)]);
    }
    return setPageHeroSettings($settings);
}

function pageHeroUploadDirectory(): string
{
    return APP_ROOT . '/uploads/admin/page-heroes';
}

function savePageHeroUpload(array $file): string
{
    require_once __DIR__ . '/image-optimizer.php';

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Choose a photo to upload.');
    }
    if ((int) ($file['size'] ?? 0) > 15 * 1024 * 1024) {
        throw new RuntimeException('The photo is larger than 15 MB.');
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if (PHP_SAPI !== 'cli' && !is_uploaded_file($tmp)) {
        throw new RuntimeException('The upload could not be verified.');
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? (string) finfo_file($finfo, $tmp) : '';
    if ($finfo) {
        finfo_close($finfo);
    }
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        throw new RuntimeException('Use a JPEG, PNG or WebP photo.');
    }

    $dimensions = @getimagesize($tmp);
    $sourceWidth = (int) ($dimensions[0] ?? 0);
    $sourceHeight = (int) ($dimensions[1] ?? 0);
    if ($sourceWidth <= 0 || $sourceHeight <= 0 || ($sourceWidth * $sourceHeight) > 40000000) {
        throw new RuntimeException('The photo dimensions are invalid or too large.');
    }

    $image = loadImage($tmp, $mime);
    if (!$image) {
        throw new RuntimeException('The uploaded photo could not be read.');
    }
    $image = autoRotateImage($image, $tmp, $mime);
    $width = imagesx($image);
    $height = imagesy($image);
    if ($width < 800 || $height < 300) {
        imagedestroy($image);
        throw new RuntimeException('Use an image at least 800 × 300 pixels.');
    }

    $maxWidth = 2400;
    if ($width > $maxWidth) {
        $newHeight = (int) round($height * $maxWidth / $width);
        $resized = imagecreatetruecolor($maxWidth, $newHeight);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
        imagedestroy($image);
        $image = $resized;
    }

    $dir = pageHeroUploadDirectory();
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        imagedestroy($image);
        throw new RuntimeException('The hero upload directory could not be created.');
    }
    $filename = 'hero-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(5)) . '.webp';
    $target = $dir . '/' . $filename;
    if (!imagewebp($image, $target, 84)) {
        imagedestroy($image);
        throw new RuntimeException('The hero image could not be saved.');
    }
    imagedestroy($image);
    return '/uploads/admin/page-heroes/' . $filename;
}
