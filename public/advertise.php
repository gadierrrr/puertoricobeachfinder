<?php
/** Public advertising media kit and lead form. */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/advertising.php';

$lang = getCurrentLanguage() === 'es' ? 'es' : 'en';
$isEs = $lang === 'es';
$packages = advertisingPackages(true);
$packageSlugs = array_column($packages, 'slug');
$selectedPackage = trim((string) ($_GET['package'] ?? 'standard'));
if (!in_array($selectedPackage, $packageSlugs, true)) {
    $selectedPackage = $packageSlugs[0] ?? 'standard';
}

$beachSlug = mb_substr(trim((string) ($_GET['beach'] ?? '')), 0, 120);
$beachName = '';
if ($beachSlug !== '') {
    $row = queryOne('SELECT name FROM beaches WHERE slug=:slug AND publish_status="published"', [':slug' => $beachSlug]);
    $beachName = trim((string) ($row['name'] ?? ''));
}

$coverage = queryOne(
    'SELECT COUNT(*) AS beaches,COUNT(DISTINCT municipality) AS municipalities
     FROM beaches WHERE publish_status="published" AND (location_type="beach" OR location_type IS NULL)'
) ?: ['beaches' => 0, 'municipalities' => 0];

$sent = isset($_GET['sent']);
$errorCode = trim((string) ($_GET['error'] ?? ''));
$leadRef = mb_substr(trim((string) ($_GET['ref'] ?? '')), 0, 36);
$formToken = advertisingLeadFormToken();
$sourcePage = routeUrl('advertise', $lang);
if ($beachSlug !== '') {
    $sourcePage .= '?beach=' . rawurlencode($beachSlug);
}

$pageTitle = $isEs
    ? 'Anuncia tu negocio | Puerto Rico Beach Finder'
    : 'Advertise Your Business | Puerto Rico Beach Finder';
$pageTitleNoBrandSuffix = true;
$pageDescription = $isEs
    ? 'Espacios pagados, bilingües y contextuales para negocios que sirven a quienes visitan las playas de Puerto Rico.'
    : 'Contextual, bilingual paid placements for businesses serving people visiting Puerto Rico beaches.';
$redesignLayout = true;
$bodyClasses = trim(($bodyClasses ?? '') . ' rd-advertise-page rd-chat-muted');

include APP_ROOT . '/components/header.php';
include APP_ROOT . '/templates/redesign/advertise.php';
include APP_ROOT . '/components/footer.php';
