<?php
/**
 * Shared nav behavior (dropdowns, mobile menu, language switch).
 * Included by components/nav.php (classic) and components/redesign/nav.php —
 * both use the same element IDs and data-action hooks, and only one nav
 * renders per page, so the bindings are identical.
 */
?>
<script <?= cspNonceAttr() ?>>
function closeMobileMenu() {
    const menu = document.getElementById('mobile-menu');
    const button = document.getElementById('mobile-menu-button');
    if (!menu || !button) return;
    menu.classList.add('hidden');
    button.setAttribute('aria-expanded', 'false');
}

function closeBeachesDropdown() {
    const menu = document.getElementById('beaches-dropdown-menu');
    const button = document.querySelector('#beaches-dropdown button');
    if (menu) {
        menu.classList.add('hidden');
    }
    if (button) {
        button.setAttribute('aria-expanded', 'false');
    }
}

function closeLangDropdown() {
    const menu = document.getElementById('lang-dropdown-menu');
    const button = document.querySelector('#lang-dropdown button');
    if (menu) {
        menu.classList.add('hidden');
    }
    if (button) {
        button.setAttribute('aria-expanded', 'false');
    }
}

function toggleMobileMenu() {
    const menu = document.getElementById('mobile-menu');
    const button = document.getElementById('mobile-menu-button');
    if (!menu || !button) return;
    const isOpen = !menu.classList.contains('hidden');
    menu.classList.toggle('hidden');
    button.setAttribute('aria-expanded', (!isOpen).toString());
}

function toggleBeachesDropdown() {
    const menu = document.getElementById('beaches-dropdown-menu');
    const button = document.querySelector('#beaches-dropdown button');
    if (!menu || !button) return;
    const isOpen = !menu.classList.contains('hidden');
    menu.classList.toggle('hidden');
    button.setAttribute('aria-expanded', (!isOpen).toString());
    closeLangDropdown();
}

function toggleLangDropdown() {
    const menu = document.getElementById('lang-dropdown-menu');
    const button = document.querySelector('#lang-dropdown button');
    if (!menu || !button) return;
    const isOpen = !menu.classList.contains('hidden');
    menu.classList.toggle('hidden');
    button.setAttribute('aria-expanded', (!isOpen).toString());
    closeBeachesDropdown();
}

function setLanguage(lang, targetUrl) {
    const currentLang = (document.documentElement.lang || 'en').substring(0, 2);
    if (typeof window.bfTrack === 'function') {
        window.bfTrack('language_switch', { from: currentLang, to: lang, page: window.location.pathname });
    }
    const redirectUrl = targetUrl || window.location.pathname + window.location.search;
    const body = new URLSearchParams({
        lang: lang,
        redirect: redirectUrl
    });

    fetch('/api/set-language.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        credentials: 'same-origin',
        body: body.toString()
    })
        .then((response) => response.ok ? response.json() : null)
        .then((data) => {
            const nextUrl = data && data.redirect_url ? data.redirect_url : redirectUrl;
            window.location.assign(nextUrl);
        })
        .catch(() => {
            window.location.assign(redirectUrl);
        });
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('#beaches-dropdown')) {
        closeBeachesDropdown();
    }
    if (!e.target.closest('#lang-dropdown')) {
        closeLangDropdown();
    }
    if (!e.target.closest('#mobile-menu') && !e.target.closest('#mobile-menu-button')) {
        closeMobileMenu();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeBeachesDropdown();
        closeLangDropdown();
        closeMobileMenu();
    }
});
</script>
