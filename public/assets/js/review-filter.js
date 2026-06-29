/**
 * ⚠️ INACTIVE / WIP — part of the unshipped redesign; not loaded by any live page.
 *    Do not build on without reviving. See docs/mobile-homepage-bug-report.md.
 *
 * Review visit-type filter
 *
 * Toggles visibility of review cards inside #reviews-list based on their
 * data-visit-type. The server still renders all reviews so the filter is
 * zero-latency and no extra fetch. An aria-live region announces the count.
 */
(function () {
    'use strict';

    function init() {
        var bar = document.querySelector('.reviews-visit-filter');
        var list = document.getElementById('reviews-list');
        if (!bar || !list) return;

        var buttons = bar.querySelectorAll('[data-review-filter]');
        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = btn.getAttribute('data-review-filter');

                buttons.forEach(function (b) {
                    var active = b === btn;
                    b.setAttribute('aria-pressed', active ? 'true' : 'false');
                    b.classList.toggle('chip-review--active', active);
                    b.classList.toggle('bg-sunset-400', active);
                    b.classList.toggle('text-ocean-900', active);
                    b.classList.toggle('bg-warm-100', !active);
                    b.classList.toggle('text-warm-700', !active);
                });

                var shown = 0;
                list.querySelectorAll('[data-visit-type]').forEach(function (node) {
                    var t = node.getAttribute('data-visit-type') || '';
                    var show = target === 'all' || t === target;
                    node.style.display = show ? '' : 'none';
                    if (show) shown++;
                });

                if (typeof window.bfTrack === 'function') {
                    window.bfTrack('reviews_filter', { visit_type: target, count: shown });
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
