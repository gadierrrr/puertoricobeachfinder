<?php
declare(strict_types=1);

$publicRoot = realpath(__DIR__ . '/../public');
if ($publicRoot === false) {
    http_response_code(500);
    echo "public/ docroot not found\n";
    return true;
}

// Ensure public entrypoints can reliably load ../bootstrap.php via DOCUMENT_ROOT.
$_SERVER['DOCUMENT_ROOT'] = $publicRoot;

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$uriPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';
$uriPath = rawurldecode($uriPath);

if ($uriPath === '' || $uriPath[0] !== '/') {
    $uriPath = '/' . ltrim($uriPath, '/');
}

$requestedPath = $publicRoot . $uriPath;
$queryString = $_SERVER['QUERY_STRING'] ?? '';

// Canonical redirects: .php -> extensionless public URLs.
if (preg_match('~^/(best-beaches|best-beaches-san-juan|best-snorkeling-beaches|best-surfing-beaches|best-family-beaches|beaches-near-san-juan|beaches-near-san-juan-airport|hidden-beaches-puerto-rico)\.php$~', $uriPath, $matches)) {
    $target = '/' . $matches[1];
    if ($queryString !== '') {
        $target .= '?' . $queryString;
    }
    header('Location: ' . $target, true, 301);
    return true;
}

if (preg_match('~^/guides/([a-z0-9-]+)\.php$~', $uriPath, $matches)) {
    $target = '/guides/' . $matches[1];
    if ($queryString !== '') {
        $target .= '?' . $queryString;
    }
    header('Location: ' . $target, true, 301);
    return true;
}

if ($uriPath === '/guides/index.php') {
    $target = '/guides/';
    if ($queryString !== '') {
        $target .= '?' . $queryString;
    }
    header('Location: ' . $target, true, 301);
    return true;
}

// Serve existing static files directly.
if (is_file($requestedPath)) {
    return false;
}

// Serve directory indexes when present (ex: /guides/ -> /guides/index.php).
if (is_dir($requestedPath)) {
    $indexPhp = rtrim($requestedPath, '/') . '/index.php';
    if (is_file($indexPhp)) {
        require $indexPhp;
        return true;
    }
}

// Nginx-equivalent rewrites used by this project.
if ($uriPath === '/sitemap.xml') {
    require $publicRoot . '/sitemap.php';
    return true;
}

if (preg_match('~^/beach/([a-z0-9-]+)$~', $uriPath, $matches)) {
    $_GET['slug'] = $matches[1];
    require $publicRoot . '/beach.php';
    return true;
}

if (preg_match('~^/beaches-in-([a-z-]+)$~', $uriPath, $matches)) {
    $_GET['m'] = $matches[1];
    require $publicRoot . '/municipality.php';
    return true;
}

// Extensionless editorial URLs (try /path.php when /path is requested).
if (strpos(basename($uriPath), '.') === false) {
    $candidate = $publicRoot . $uriPath . '.php';
    if (is_file($candidate)) {
        require $candidate;
        return true;
    }
}

// Fallback to homepage.
require $publicRoot . '/index.php';
return true;
