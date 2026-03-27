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
</script>

