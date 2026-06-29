<?php
/**
 * ⚠️ INACTIVE / WIP — unshipped redesign, not wired into any live page.
 *    Do not build on without reviving. See docs/mobile-homepage-bug-report.md.
 *
 * Mobile bottom tab bar (Phase 3 of discovery redesign).
 *
 * Shown only on narrow viewports (<1024px) via the .mobile-tabbar class.
 * Four tabs: Map, List, Saved, Chat. Chat tab toggles the existing
 * components/chat/panel.php FAB.
 *
 * No new translation keys are introduced here — all labels reuse
 * existing keys (aria.list_view, aria.map_view, common.saved, chat.panel_title).
 */

$currentLang = $currentLang ?? (function_exists('getCurrentLanguage') ? getCurrentLanguage() : 'en');
$localizedHome = $localizedHome ?? (function_exists('routeUrl') ? routeUrl('home', $currentLang) : '/');
$profileUrl = function_exists('routeUrl') ? routeUrl('profile', $currentLang) : '/profile';
$loginUrl = function_exists('routeUrl') ? routeUrl('login', $currentLang) : '/login';
$savedUrl = function_exists('isAuthenticated') && isAuthenticated() ? $profileUrl . '#favorites' : $loginUrl;

$currentPath = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$isHome = ($currentPath === $localizedHome || $currentPath === '/' || $currentPath === '/es');
$currentView = $_GET['view'] ?? 'list';

$tabs = [
    [
        'id'    => 'map',
        'href'  => $localizedHome . '?view=map',
        'icon'  => 'map',
        'label' => __('aria.map_view'),
        'active'=> $isHome && $currentView === 'map',
        'data_action' => $isHome ? 'setMobileView' : null,
        'data_action_args' => $isHome ? '["map"]' : null,
    ],
    [
        'id'    => 'list',
        'href'  => $localizedHome,
        'icon'  => 'list',
        'label' => __('aria.list_view'),
        'active'=> $isHome && $currentView === 'list',
        'data_action' => $isHome ? 'setMobileView' : null,
        'data_action_args' => $isHome ? '["list"]' : null,
    ],
    [
        'id'    => 'saved',
        'href'  => $savedUrl,
        'icon'  => 'heart',
        'label' => __('common.saved') !== 'common.saved' ? __('common.saved') : 'Saved',
        'active'=> str_contains($currentPath, 'favorites') || str_contains($currentPath, 'profile'),
    ],
    [
        'id'      => 'chat',
        'href'    => '#',
        'icon'    => 'message-circle',
        'label'   => __('common.chat') !== 'common.chat' ? __('common.chat') : 'Chat',
        'data_action' => 'toggleChatPanel',
        'active'  => false,
    ],
];
?>
<nav class="mobile-tabbar" aria-label="Primary mobile navigation">
    <?php foreach ($tabs as $tab): ?>
        <?php if (!empty($tab['data_action'])): ?>
        <button type="button"
                class="mobile-tabbar__tab<?= $tab['active'] ? ' is-active' : '' ?>"
                data-action="<?= h($tab['data_action']) ?>"
                <?php if (!empty($tab['data_action_args'])): ?>data-action-args='<?= h($tab['data_action_args']) ?>'<?php endif; ?>
                data-tab-id="<?= h($tab['id']) ?>"
                aria-label="<?= h($tab['label']) ?>"
                aria-current="<?= $tab['active'] ? 'page' : 'false' ?>">
            <i data-lucide="<?= h($tab['icon']) ?>" aria-hidden="true"></i>
            <span><?= h($tab['label']) ?></span>
        </button>
        <?php else: ?>
        <a href="<?= h($tab['href']) ?>"
           class="mobile-tabbar__tab<?= $tab['active'] ? ' is-active' : '' ?>"
           data-tab-id="<?= h($tab['id']) ?>"
           aria-label="<?= h($tab['label']) ?>"
           aria-current="<?= $tab['active'] ? 'page' : 'false' ?>">
            <i data-lucide="<?= h($tab['icon']) ?>" aria-hidden="true"></i>
            <span><?= h($tab['label']) ?></span>
        </a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
