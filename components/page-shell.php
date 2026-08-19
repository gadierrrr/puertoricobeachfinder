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
    if (!empty($redesignLayout) && $pageTheme === 'guide') {
        $bodyClasses = trim(($bodyClasses ?? '') . ' rd-guide-page');
    }
    include __DIR__ . '/header.php';
    return;
}

if ($pageShellMode === 'end') {
    if ($pageTheme === 'guide' && empty($guideSponsorRendered)) {
        require_once APP_ROOT . '/inc/advertising.php';

        $guideSponsorPath = '';
        if (!empty($guideUrl) && is_string($guideUrl)) {
            $guideSponsorPath = (string) (parse_url($guideUrl, PHP_URL_PATH) ?: '');
        }
        if ($guideSponsorPath === '') {
            $guideSponsorPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
        }
        if (function_exists('localizePathAndQuery')) {
            $guideSponsorPath = localizePathAndQuery($guideSponsorPath, '', 'en');
        }

        $guideSponsorSlug = '';
        if (preg_match('#^/guides/([a-z0-9-]+)$#', $guideSponsorPath, $guideSponsorMatch)) {
            $guideSponsorSlug = $guideSponsorMatch[1];
        } elseif (isset($slug) && is_scalar($slug)) {
            $guideSponsorSlug = trim((string) $slug);
        }

        if ($guideSponsorSlug !== '') {
            $guideSponsorLocale = isset($lang) && $lang === 'es' ? 'es' : 'en';
            $guideSponsorHtml = advertisingRenderSlot(
                'guide.inline-sponsor',
                'guide',
                $guideSponsorSlug,
                $guideSponsorLocale
            );
            if ($guideSponsorHtml !== '') {
                echo '<div class="wrap guide-sponsor-fallback">' . $guideSponsorHtml . '</div>';
            }
        }
    }
    include __DIR__ . '/footer.php';
    return;
}

throw new InvalidArgumentException('Invalid page shell mode: ' . (string) $pageShellMode);
