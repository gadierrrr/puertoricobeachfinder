<?php
/**
 * Redesign v2 site navigation. Included by header.php when $redesignLayout is
 * set (mutually exclusive with components/nav.php). Carries the same internal
 * link set as the classic nav — tag pages, guides, quiz, map, auth, language
 * switcher — so the redesign never sheds nav link equity. Uses the same
 * element IDs / data-action hooks as the classic nav; behavior comes from
 * components/nav-scripts.php.
 */
$appName = $appName ?? ($_ENV['APP_NAME'] ?? 'Beach Finder');
$currentLang = $currentLang ?? getCurrentLanguage();
$user = $user ?? currentUser();
$localizedHome = routeUrl('home', $currentLang);
$localizedQuiz = routeUrl('quiz', $currentLang);
$localizedProfile = routeUrl('profile', $currentLang);
$localizedLogout = routeUrl('logout', $currentLang);
$localizedLogin = routeUrl('login', $currentLang);
$langSwitchEnUrl = getLocalizedUrlForCurrentRequest('en');
$langSwitchEsUrl = getLocalizedUrlForCurrentRequest('es');

$navMapHref = $navMapHref ?? null;
if (!is_string($navMapHref) || $navMapHref === '') {
    $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $currentPath = (string)(parse_url($requestUri, PHP_URL_PATH) ?? '/');
    if ($currentPath === '') {
        $currentPath = $localizedHome;
    }
    $queryParams = $_GET;
    $queryParams['view'] = 'map';
    $queryString = http_build_query($queryParams);
    $navMapHref = $currentPath . ($queryString !== '' ? '?' . $queryString : '?view=map');
}

$rdNavTags = [
    'surfing' => '🏄‍♂️',
    'snorkeling' => '🤿',
    'family-friendly' => '👨‍👩‍👧',
    'secluded' => '🌴',
    'swimming' => '🏊',
];
?>

<!-- Skip Links for Accessibility -->
<a href="#main-content" class="skip-link sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:bg-cyan-500 focus:text-white focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-lg focus:outline-none">
    <?= h(__('nav.skip_main')) ?>
</a>
<a href="#beach-grid" class="skip-link sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-48 focus:z-50 focus:bg-cyan-500 focus:text-white focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-lg focus:outline-none">
    <?= h(__('nav.skip_beaches')) ?>
</a>

<nav id="main-nav" class="rd rd-topnav" role="navigation" aria-label="<?= h(__('nav.main_navigation')) ?>">
    <div class="wrap bar">
        <a href="<?= h($localizedHome) ?>" class="brand" aria-label="<?= h($appName) ?> - <?= h(__('nav.home')) ?>">
            <svg class="sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="4.2"/><path d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M19.1 4.9l-1.8 1.8M6.7 17.3l-1.8 1.8" stroke-linecap="round"/></svg>
            <span><?= h($appName) ?></span>
        </a>

        <!-- Center Navigation (Desktop) -->
        <div class="links" role="menubar">
            <div class="dd" id="beaches-dropdown">
                <button type="button"
                        data-action="toggleBeachesDropdown"
                        role="menuitem"
                        aria-expanded="false"
                        aria-haspopup="true">
                    <span><?= h(__('nav.beaches')) ?></span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <div id="beaches-dropdown-menu" class="hidden menu">
                    <div class="menu-label"><?= h(__('nav.find_by_activity')) ?></div>
                    <?php foreach ($rdNavTags as $tag => $emoji): ?>
                    <a href="<?= h(getLocalizedTagPageUrl($tag, $currentLang)) ?>"><span class="ic"><?= $emoji ?></span><span><?= h(__('tags.' . $tag)) ?></span></a>
                    <?php endforeach; ?>
                    <a class="all" href="<?= h($localizedHome) ?>#beaches"><?= h(__('nav.view_all_beaches')) ?> →</a>
                </div>
            </div>
            <a href="<?= h(routeUrl('guides_index', $currentLang)) ?>" role="menuitem"><?= h(__('nav.guides')) ?></a>
            <a href="<?= h($localizedQuiz) ?>" role="menuitem"><?= h(__('nav.quiz')) ?></a>
            <a href="<?= h($navMapHref) ?>" role="menuitem" data-context-map-link><?= h(__('nav.map')) ?></a>
        </div>

        <!-- Right Side - Language & Auth -->
        <div class="side">
            <div class="dd" id="lang-dropdown">
                <button type="button"
                        class="lang"
                        data-action="toggleLangDropdown"
                        aria-label="<?= h(__('nav.language')) ?>"
                        aria-expanded="false"
                        aria-haspopup="true">
                    <span><?= getLanguageFlag($currentLang) ?></span>
                    <span><?= strtoupper($currentLang) ?></span>
                </button>
                <div id="lang-dropdown-menu" class="hidden menu right">
                    <button type="button" data-target-url="<?= h($langSwitchEnUrl) ?>" data-action="setLanguage" data-action-args='["en","__this__"]' class="<?= $currentLang === 'en' ? 'on' : '' ?>">
                        <span class="ic">🇺🇸</span> <?= h(__('nav.language_english')) ?>
                    </button>
                    <button type="button" data-target-url="<?= h($langSwitchEsUrl) ?>" data-action="setLanguage" data-action-args='["es","__this__"]' class="<?= $currentLang === 'es' ? 'on' : '' ?>">
                        <span class="ic">🇵🇷</span> <?= h(__('nav.language_spanish')) ?>
                    </button>
                </div>
            </div>

            <?php if ($user): ?>
                <?php
                require_once APP_ROOT . '/inc/chat.php';
                $_navAvatar = chatUserDisplayInfo($user);
                ?>
                <a class="iconlink" href="<?= h($localizedProfile) ?>?tab=favorites" aria-label="<?= h($currentLang === 'es' ? 'Favoritas' : 'Favorites') ?>" title="<?= h($currentLang === 'es' ? 'Favoritas' : 'Favorites') ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                </a>
                <a class="iconlink" href="/lists" aria-label="<?= h($currentLang === 'es' ? 'Mis listas' : 'My Lists') ?>" title="<?= h($currentLang === 'es' ? 'Mis listas' : 'My Lists') ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" stroke-linecap="round"/></svg>
                </a>
                <a href="<?= h($localizedProfile) ?>" class="avatar <?= h($_navAvatar['color']) ?>" aria-label="<?= h(__('nav.my_profile')) ?>"><?= h($_navAvatar['initials']) ?></a>
                <a href="<?= h($localizedLogout) ?>" class="quiet"><?= h(__('nav.logout')) ?></a>
            <?php else: ?>
                <a href="<?= h($localizedLogin) ?>" class="signin"><?= h(__('nav.sign_in')) ?></a>
            <?php endif; ?>
        </div>

        <!-- Mobile menu button -->
        <button type="button"
                id="mobile-menu-button"
                class="burger"
                data-action="toggleMobileMenu"
                aria-expanded="false"
                aria-controls="mobile-menu"
                aria-label="<?= h(__('nav.open_main_menu')) ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/></svg>
        </button>
    </div>

    <!-- Mobile menu -->
    <div id="mobile-menu" class="hidden mobile" role="menu" aria-labelledby="mobile-menu-button">
        <div class="menu-label"><?= h(__('nav.find_beaches')) ?></div>
        <?php foreach ($rdNavTags as $tag => $emoji): ?>
        <a href="<?= h(getLocalizedTagPageUrl($tag, $currentLang)) ?>" role="menuitem"><span class="ic"><?= $emoji ?></span><span><?= h(__('tags.' . $tag)) ?></span></a>
        <?php endforeach; ?>
        <a class="all" href="<?= h($localizedHome) ?>#beaches" role="menuitem"><?= h(__('nav.view_all_beaches')) ?> →</a>

        <div class="menu-label"><?= h(__('nav.tools')) ?></div>
        <a href="<?= h(routeUrl('guides_index', $currentLang)) ?>" role="menuitem"><span class="ic">📖</span><span><?= h(__('nav.guides')) ?></span></a>
        <a href="<?= h($localizedQuiz) ?>" role="menuitem"><span class="ic">✨</span><span><?= h(__('nav.find_my_beach_quiz')) ?></span></a>
        <a href="<?= h($navMapHref) ?>" role="menuitem" data-context-map-link><span class="ic">🗺️</span><span><?= h(__('nav.map_view')) ?></span></a>

        <?php if ($user): ?>
        <div class="menu-label"><?= h(__('footer.your_account')) ?></div>
        <a href="<?= h($localizedProfile) ?>" role="menuitem"><span class="ic">👤</span><span><?= h(__('nav.my_profile')) ?></span></a>
        <a href="<?= h($localizedProfile) ?>?tab=favorites" role="menuitem"><span class="ic">❤️</span><span><?= h(__('nav.favorites')) ?></span></a>
        <a href="/lists" role="menuitem"><span class="ic">📋</span><span><?= h($currentLang === 'es' ? 'Mis listas' : 'My Lists') ?></span></a>
        <a href="<?= h($localizedLogout) ?>" class="logout" role="menuitem"><?= h(__('nav.logout')) ?></a>
        <?php else: ?>
        <a href="<?= h($localizedLogin) ?>" class="signin-block" role="menuitem"><?= h(__('nav.sign_in')) ?></a>
        <?php endif; ?>

        <div class="menu-label"><?= h(__('nav.language')) ?></div>
        <div class="langrow">
            <button type="button" data-target-url="<?= h($langSwitchEnUrl) ?>" data-action="setLanguage" data-action-args='["en","__this__"]' class="<?= $currentLang === 'en' ? 'on' : '' ?>">🇺🇸 <?= h(__('nav.language_english')) ?></button>
            <button type="button" data-target-url="<?= h($langSwitchEsUrl) ?>" data-action="setLanguage" data-action-args='["es","__this__"]' class="<?= $currentLang === 'es' ? 'on' : '' ?>">🇵🇷 <?= h(__('nav.language_spanish')) ?></button>
        </div>
    </div>
</nav>

<?php include __DIR__ . '/../nav-scripts.php'; ?>
