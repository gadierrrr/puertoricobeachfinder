<?php
/**
 * Beach Detail: Inline Scripts
 * Weather widget loader, section nav IntersectionObserver.
 *
 * Expects: $beach (via window.BeachDetail)
 */
?>
(function() {
    const container = document.getElementById('weather-widget-container');
    if (!container) return;
    const lat = container.dataset.lat;
    const lng = container.dataset.lng;
    if (!lat || !lng) return;

    // Fetch rendered weather widget HTML
    fetch('/api/weather-widget.php?lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng) + '&size=sidebar')
        .then(r => r.ok ? r.text() : Promise.reject())
        .then(html => { container.innerHTML = html; })
        .catch(() => { container.innerHTML = '<div class="text-sm text-gray-400">Weather unavailable</div>'; });

    // Fetch weather JSON for sticky bar
    fetch('/api/weather.php?lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng))
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(data => {
            if (!data.success || !data.data) return;
            const w = data.data;
            const iconEl = document.getElementById('sticky-weather-icon');
            const tempEl = document.getElementById('sticky-weather-temp');
            const verdictEl = document.getElementById('sticky-weather-verdict');
            if (iconEl && w.current) iconEl.textContent = w.current.icon || '🌤️';
            if (tempEl && w.current) tempEl.textContent = w.current.temperature ? Math.round(w.current.temperature) + '°F' : '--';
            if (verdictEl && w.recommendation) verdictEl.textContent = w.recommendation.verdict || 'Check weather';
            // Populate mobile weather strip
            var sv = document.getElementById('weather-strip-verdict');
            if (sv && w.recommendation) sv.textContent = w.recommendation.verdict || 'Weather';
            var sd = document.getElementById('weather-strip-desc');
            if (sd && w.current) sd.textContent = (w.current.description || '') + ' • ' + (w.current.location || '');
            var st = document.getElementById('weather-strip-temp');
            if (st && w.current && w.current.temperature) st.textContent = Math.round(w.current.temperature) + '°';
            var sw = document.getElementById('weather-strip-wind');
            if (sw && w.current) sw.textContent = (w.current.wind_speed ? Math.round(w.current.wind_speed) + 'mph' : '--');
            var su = document.getElementById('weather-strip-uv');
            if (su && w.current) {
                var uvi = w.current.uv_index || 0;
                su.textContent = uvi <= 2 ? 'Low' : uvi <= 5 ? 'Moderate' : uvi <= 7 ? 'High' : 'Very High';
                su.className = 'text-xs font-medium ' + (uvi <= 2 ? 'text-green-400' : uvi <= 5 ? 'text-yellow-400' : 'text-red-400');
            }
            var sh = document.getElementById('weather-strip-humidity');
            if (sh && w.current) sh.textContent = (w.current.humidity || '--') + '%';
        })
        .catch(() => {
            const verdictEl = document.getElementById('sticky-weather-verdict');
            if (verdictEl) verdictEl.textContent = 'Check weather';
        });
})();
</script>


<!-- Section Nav Active State -->
<script <?= cspNonceAttr() ?>>
(function(){
    var nav = document.querySelector(".beach-section-nav");
    if (!nav) return;
    var links = nav.querySelectorAll(".beach-nav-link");
    var sections = document.querySelectorAll("[id^='section-']");

    links.forEach(function(link){
        link.addEventListener("click", function(e){
            e.preventDefault();
            var target = document.querySelector(link.getAttribute("href"));
            if (target) target.scrollIntoView({behavior: "smooth"});
        });
    });

    if (typeof IntersectionObserver !== "undefined") {
        var observer = new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if (entry.isIntersecting) {
                    links.forEach(function(l){ l.classList.remove("active"); });
                    var active = nav.querySelector('a[data-section="' + entry.target.id + '"]');
                    if (active) active.classList.add("active");
                }
            });
        }, {rootMargin: "-120px 0px -60% 0px"});
        sections.forEach(function(s){ observer.observe(s); });
    }
})();

// Collapsible section toggle (CSP-safe via data-action)
function toggleSection(el) {
    var section = el.closest('.section-collapsible');
    if (!section) return;
    section.classList.toggle('expanded');
    var isExpanded = section.classList.contains('expanded');
    if (typeof lucide !== 'undefined') lucide.createIcons();
    section.querySelectorAll('.read-more-text').forEach(function(el) {
        el.textContent = isExpanded ? '<?= h($lang === "es" ? "Mostrar menos" : "Show less") ?>' : '<?= h($lang === "es" ? "Leer más" : "Read more") ?>';
    });
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

// Sticky bar auto-hide on scroll down
(function() {
    var bar = document.querySelector('.beach-sticky-bar');
    if (!bar) return;
    var lastScroll = 0, ticking = false;
    window.addEventListener('scroll', function() {
        if (!ticking) {
            requestAnimationFrame(function() {
                var curr = window.scrollY;
                bar.classList.toggle('hidden-bar', curr > lastScroll && curr > 200);
                lastScroll = curr;
                ticking = false;
            });
            ticking = true;
        }
    });
})();

</script>

