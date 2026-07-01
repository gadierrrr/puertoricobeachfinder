<?php
/**
 * Beach Detail: All Modal Dialogs + Inline Scripts
 * Share modal, gallery lightbox, report modal, checkin modal,
 * review form modal, photo upload modal, photo lightbox.
 * Includes associated JavaScript for each modal.
 *
 * Expects: $beach, $lang, $reviews, $avgUserRating, $userReviewCount
 */
?>
<!-- Share Modal -->
<div id="share-modal" class="share-modal" role="dialog" aria-modal="true" aria-labelledby="share-modal-title" data-action="closeShareModal">
    <div class="share-modal-content" data-action-stop data-action="noop" data-on="click">
        <div class="flex justify-between items-center mb-4">
            <h3 id="share-modal-title" class="text-lg font-semibold"><?= h(__('beach.share_beach')) ?></h3>
            <button data-action="closeShareModal" class="text-gray-400 hover:text-gray-600" aria-label="Close share dialog">
                <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
            </button>
        </div>
        <div id="share-modal-body"></div>
    </div>
</div>

<script <?= cspNonceAttr() ?>>
// Initialize small map for sidebar.
//
// Two race conditions must both be handled or the map renders broken on mobile:
//
// 1. maplibre-gl.css is loaded via <link rel="preload" as="style"> and only
//    flipped to rel="stylesheet" once the preload finishes. If we init the map
//    before that flip happens, none of maplibre's positioning CSS applies —
//    the marker and attribution fall into normal document flow below the
//    canvas, spilling outside the container.
// 2. The sidebar is far below the fold on mobile. iOS Safari can measure the
//    container as 0-height at init, and MapLibre's internal auto-resize
//    doesn't always fire, leaving the canvas mis-sized.
//
// So we wait for BOTH (a) maplibre-gl.css applied and (b) container has
// non-zero dimensions, then instantiate. Extra resize() calls on scroll /
// IntersectionObserver act as belt-and-braces for late layout shifts.
(function () {
    function maplibreCssReady() {
        const links = document.querySelectorAll('link[href*="maplibre-gl"][href*=".css"]');
        if (links.length === 0) return true; // no CSS link at all — nothing to wait for
        for (let i = 0; i < links.length; i++) {
            const l = links[i];
            if (l.rel === 'stylesheet') return true;
            // preload finished parsing — .sheet is populated as soon as CSSOM is built
            if (l.sheet) return true;
        }
        return false;
    }

    function createMap() {
        const mapContainer = document.getElementById('beach-map');
        if (!mapContainer) return;

        const lng = <?= json_encode((float) $beach['lng']) ?>;
        const lat = <?= json_encode((float) $beach['lat']) ?>;

        const map = new maplibregl.Map({
            container: 'beach-map',
            style: 'https://basemaps.cartocdn.com/gl/positron-gl-style/style.json',
            center: [lng, lat],
            zoom: 13,
            interactive: false
        });

        new maplibregl.Marker({ color: '#2563eb' })
            .setLngLat([lng, lat])
            .addTo(map);

        function doResize() {
            try { map.resize(); } catch (e) {}
        }

        if (typeof IntersectionObserver !== 'undefined') {
            const io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) {
                        doResize();
                        io.disconnect();
                    }
                });
            }, { threshold: 0.1 });
            io.observe(mapContainer);
        }

        window.addEventListener('scroll', doResize, { passive: true, once: true });
        setTimeout(doResize, 1500);
        setTimeout(doResize, 3500);
    }

    function tryInit(attempts) {
        if (typeof maplibregl === 'undefined' || !maplibreCssReady()) {
            if (attempts > 100) return; // give up after ~10s
            setTimeout(function () { tryInit(attempts + 1); }, 100);
            return;
        }
        const mapContainer = document.getElementById('beach-map');
        if (!mapContainer) return;
        // Wait until the container has measurable height — avoids iOS Safari
        // init-with-zero-height state.
        if (mapContainer.offsetHeight === 0) {
            if (attempts > 100) { createMap(); return; } // give up waiting, init anyway
            setTimeout(function () { tryInit(attempts + 1); }, 100);
            return;
        }
        createMap();
    }

    function start() { tryInit(0); }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
</script>

<script <?= cspNonceAttr() ?>>
async function toggleStickyFavorite() {
    const btn = document.getElementById('sticky-favorite-btn');
    const icon = document.getElementById('sticky-favorite-icon');
    if (!btn || !icon) return;

    <?php if (!isAuthenticated()): ?>
    if (typeof showSignupPrompt === 'function') {
        showSignupPrompt('favorites', '/beach/<?= h($beach['slug']) ?>');
    } else {
        window.location.href = '/login?redirect=' + encodeURIComponent('/beach/<?= h($beach['slug']) ?>');
    }
    return;
    <?php endif; ?>

    if (btn.dataset.loading === '1') return;
    btn.dataset.loading = '1';

    try {
        const body = new URLSearchParams();
        body.set('beach_id', <?= json_encode($beach['id']) ?>);
        body.set('csrf_token', <?= json_encode(csrfToken()) ?>);
        const res = await fetch('/api/toggle-favorite.php?format=json', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        });
        const payload = await res.json();
        if (!res.ok || !payload.success) throw new Error(payload.error || 'Failed');

        const isFav = payload.is_favorite === true;
        btn.setAttribute('aria-pressed', isFav ? 'true' : 'false');
        btn.setAttribute('aria-label', isFav ? 'Remove from favorites' : 'Add to favorites');
        icon.textContent = isFav ? '❤️' : '🤍';
        if (typeof showToast === 'function') {
            showToast(isFav ? '<?= __('beach.saved_toast') ?>' : '<?= __('beach.removed_toast') ?>', isFav ? 'success' : 'info', 2500);
        }
        if (typeof window.bfTrack === 'function') {
            window.bfTrack(isFav ? 'favorite_add' : 'favorite_remove', { source: 'beach_page_sticky', beach_slug: <?= json_encode($beach['slug']) ?> });
        }
        // Celebrate any achievement badges earned by saving (no reload here).
        if (isFav && typeof window.bfCelebrateBadges === 'function') {
            window.bfCelebrateBadges(payload.newly_earned_badges);
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('<?= __('beach.favorite_error') ?>', 'error', 3500);
    } finally {
        delete btn.dataset.loading;
    }
}
</script>

<?php if (!empty($beach['gallery'])): ?>
<!-- Gallery Lightbox -->
<div id="gallery-lightbox" class="lightbox-overlay" data-action="closeLightbox" data-action-args='["__event__"]' role="dialog" aria-modal="true" aria-label="Image gallery">
    <div class="lightbox-container" data-action-stop data-action="noop" data-on="click">
        <!-- Close button -->
        <button data-action="closeLightbox" class="lightbox-close" aria-label="Close gallery">
            <i data-lucide="x" class="w-6 h-6"></i>
        </button>

        <!-- Previous button -->
        <button data-action="navigateLightbox" data-action-args='[-1]' class="lightbox-nav lightbox-prev" aria-label="Previous image">
            <i data-lucide="chevron-left" class="w-8 h-8"></i>
        </button>

        <!-- Image container -->
        <div class="lightbox-content">
            <img id="lightbox-image" src="" alt="" class="lightbox-image">
            <div id="lightbox-counter" class="lightbox-counter"></div>
        </div>

        <!-- Next button -->
        <button data-action="navigateLightbox" data-action-args='[1]' class="lightbox-nav lightbox-next" aria-label="Next image">
            <i data-lucide="chevron-right" class="w-8 h-8"></i>
        </button>
    </div>
</div>

<script <?= cspNonceAttr() ?>>
// Gallery Lightbox
const galleryImages = <?= json_encode($beach['gallery']) ?>;
let currentImageIndex = 0;

function openLightbox(index) {
    currentImageIndex = index;
    updateLightboxImage();
    document.getElementById('gallery-lightbox').classList.add('open');
    document.body.style.overflow = 'hidden';

    // Re-initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function closeLightbox(e) {
    if (e && e.target !== document.getElementById('gallery-lightbox')) return;
    document.getElementById('gallery-lightbox').classList.remove('open');
    document.body.style.overflow = '';
}

function navigateLightbox(direction) {
    currentImageIndex += direction;
    if (currentImageIndex >= galleryImages.length) currentImageIndex = 0;
    if (currentImageIndex < 0) currentImageIndex = galleryImages.length - 1;
    updateLightboxImage();
}

function updateLightboxImage() {
    const img = document.getElementById('lightbox-image');
    const counter = document.getElementById('lightbox-counter');
    img.src = galleryImages[currentImageIndex];
    img.alt = '<?= h($beach['name']) ?> - <?= h(__('beach.photo_label')) ?> ' + (currentImageIndex + 1);
    counter.textContent = (currentImageIndex + 1) + ' / ' + galleryImages.length;
}

// Keyboard navigation
document.addEventListener('keydown', (e) => {
    const lightbox = document.getElementById('gallery-lightbox');
    if (!lightbox || !lightbox.classList.contains('open')) return;

    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') navigateLightbox(-1);
    if (e.key === 'ArrowRight') navigateLightbox(1);
});

// Touch swipe support
let touchStartX = 0;
let touchEndX = 0;

document.getElementById('gallery-lightbox')?.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].screenX;
}, { passive: true });

document.getElementById('gallery-lightbox')?.addEventListener('touchend', (e) => {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
}, { passive: true });

function handleSwipe() {
    const swipeThreshold = 50;
    const diff = touchStartX - touchEndX;
    if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0) navigateLightbox(1); // Swipe left = next
        else navigateLightbox(-1); // Swipe right = prev
    }
}
</script>
<?php endif; ?>

<!-- Report Outdated Info Modal -->
<div id="report-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="report-modal-title" data-action="closeReportModal">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full" data-action-stop data-action="noop" data-on="click">
        <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h2 id="report-modal-title" class="text-lg font-semibold text-gray-900"><?= __('beach.report_modal_title') ?></h2>
            <button data-action="closeReportModal" class="text-gray-400 hover:text-gray-600 p-1" aria-label="Close">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form id="report-form" class="p-6 space-y-4" data-action="submitReport" data-action-args='["__event__"]' data-on="submit">
            <input type="hidden" name="beach_id" id="report-beach-id">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">

            <p class="text-sm text-gray-600">
                <?= __('beach.report_help_text', ['name' => '<strong id="report-beach-name">' . h($beach['name']) . '</strong>']) ?>
            </p>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?= __('beach.report_what_updating') ?></label>
                <div class="space-y-2">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="issues[]" value="conditions" class="rounded border-gray-300">
                        <span class="text-sm text-gray-700"><?= __('beach.report_opt_conditions') ?></span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="issues[]" value="amenities" class="rounded border-gray-300">
                        <span class="text-sm text-gray-700"><?= __('beach.report_opt_amenities') ?></span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="issues[]" value="access" class="rounded border-gray-300">
                        <span class="text-sm text-gray-700"><?= __('beach.report_opt_access') ?></span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="issues[]" value="safety" class="rounded border-gray-300">
                        <span class="text-sm text-gray-700"><?= __('beach.report_opt_safety') ?></span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="issues[]" value="other" class="rounded border-gray-300">
                        <span class="text-sm text-gray-700"><?= __('beach.report_opt_other') ?></span>
                    </label>
                </div>
            </div>

            <div>
                <label for="report-details" class="block text-sm font-medium text-gray-700 mb-1">
                    <?= __('beach.report_details') ?> <span class="text-gray-400"><?= __('beach.optional') ?></span>
                </label>
                <textarea name="details" id="report-details" rows="3" maxlength="500"
                          placeholder="<?= h(__('beach.report_placeholder')) ?>"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none text-sm"></textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" id="report-submit-btn"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg font-medium transition-colors text-sm">
                    <?= __('beach.report_submit') ?>
                </button>
                <button type="button" data-action="closeReportModal"
                        class="px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors text-sm">
                    <?= __('beach.report_cancel') ?>
                </button>
            </div>

            <div id="report-message" class="hidden text-sm px-4 py-3 rounded-lg"></div>
        </form>
    </div>
</div>

<script <?= cspNonceAttr() ?>>
function openReportModal(beachId, beachName) {
    document.getElementById('report-beach-id').value = beachId;
    document.getElementById('report-beach-name').textContent = beachName || 'this beach';
    document.getElementById('report-modal').classList.remove('hidden');
    document.getElementById('report-modal').classList.add('flex');
    document.body.style.overflow = 'hidden';

    // Reset form
    document.getElementById('report-form').reset();
    document.getElementById('report-message').classList.add('hidden');

    // Re-init Lucide icons
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeReportModal() {
    document.getElementById('report-modal').classList.add('hidden');
    document.getElementById('report-modal').classList.remove('flex');
    document.body.style.overflow = '';
}

async function submitReport(event) {
    event.preventDefault();

    const form = document.getElementById('report-form');
    const submitBtn = document.getElementById('report-submit-btn');
    const messageDiv = document.getElementById('report-message');

    // Check if at least one issue is selected
    const checkboxes = form.querySelectorAll('input[name="issues[]"]:checked');
    if (checkboxes.length === 0) {
        messageDiv.textContent = '<?= __('beach.report_select_issue') ?>';
        messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
        messageDiv.classList.remove('hidden');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = '<?= __('beach.report_submitting') ?>';
    messageDiv.classList.add('hidden');

    // For now, just show success (you can implement the API endpoint later)
    setTimeout(() => {
        messageDiv.textContent = '<?= __('beach.report_success') ?>';
        messageDiv.className = 'bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg';
        messageDiv.classList.remove('hidden');

        // Show toast and close after delay
        if (typeof showToast === 'function') {
            showToast('<?= __('beach.report_toast_success') ?>', 'success', 3000);
        }

        setTimeout(() => {
            closeReportModal();
            submitBtn.disabled = false;
            submitBtn.textContent = '<?= __('beach.report_submit') ?>';
        }, 1500);
    }, 500);
}

// Close on escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeReportModal();
});
</script>

<!-- Check-In Modal -->
<div id="checkin-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="checkin-modal-title" data-action="closeCheckinModal">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto" data-action-stop data-action="noop" data-on="click">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h2 id="checkin-modal-title" class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <i data-lucide="map-pin" class="w-5 h-5 text-green-600" aria-hidden="true"></i>
                <span><?= __('beach.checkin_modal_title') ?></span>
            </h2>
            <button data-action="closeCheckinModal" class="text-gray-400 hover:text-gray-600 p-1" aria-label="Close">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="checkin-form" class="p-6 space-y-5" data-action="submitCheckin" data-action-args='["__event__"]' data-on="submit">
            <input type="hidden" name="beach_id" id="checkin-beach-id">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="website" value="" autocomplete="off">

            <p class="text-sm text-gray-600">
                <?= __('beach.checkin_share_text', ['name' => '<strong id="checkin-beach-name">' . h($beach['name']) . '</strong>']) ?>
            </p>

            <!-- Crowd Level -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?= __('beach.checkin_crowded') ?></label>
                <div class="grid grid-cols-5 gap-2">
                    <label class="checkin-option">
                        <input type="radio" name="crowd_level" value="empty" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🏝️</span>
                            <span class="text-xs"><?= __('beach.checkin_empty') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="crowd_level" value="light" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">👥</span>
                            <span class="text-xs"><?= __('beach.checkin_light') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="crowd_level" value="moderate" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">👥👥</span>
                            <span class="text-xs"><?= __('beach.checkin_moderate') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="crowd_level" value="busy" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">👥👥👥</span>
                            <span class="text-xs"><?= __('beach.checkin_busy') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="crowd_level" value="packed" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🔥</span>
                            <span class="text-xs"><?= __('beach.checkin_packed') ?></span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Parking Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?= __('beach.checkin_parking') ?></label>
                <div class="grid grid-cols-4 gap-2">
                    <label class="checkin-option">
                        <input type="radio" name="parking_status" value="plenty" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🅿️</span>
                            <span class="text-xs"><?= __('beach.checkin_plenty') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="parking_status" value="available" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">✓</span>
                            <span class="text-xs"><?= __('beach.checkin_available') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="parking_status" value="limited" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">⚠️</span>
                            <span class="text-xs"><?= __('beach.checkin_limited') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="parking_status" value="full" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🚫</span>
                            <span class="text-xs"><?= __('beach.checkin_full') ?></span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Water Conditions -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?= __('beach.checkin_water') ?></label>
                <div class="grid grid-cols-4 gap-2">
                    <label class="checkin-option">
                        <input type="radio" name="water_condition" value="calm" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">😌</span>
                            <span class="text-xs"><?= __('beach.checkin_calm') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="water_condition" value="small-waves" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🌊</span>
                            <span class="text-xs"><?= __('beach.checkin_small_waves') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="water_condition" value="choppy" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🌊🌊</span>
                            <span class="text-xs"><?= __('beach.checkin_choppy') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="water_condition" value="rough" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">⚠️</span>
                            <span class="text-xs"><?= __('beach.checkin_rough') ?></span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Sargassum -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?= __('beach.checkin_sargassum') ?></label>
                <div class="grid grid-cols-4 gap-2">
                    <label class="checkin-option">
                        <input type="radio" name="sargassum_level" value="none" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">✨</span>
                            <span class="text-xs"><?= __('beach.checkin_none') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="sargassum_level" value="light" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🌿</span>
                            <span class="text-xs"><?= __('beach.checkin_light') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="sargassum_level" value="moderate" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🌿🌿</span>
                            <span class="text-xs"><?= __('beach.checkin_moderate') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="sargassum_level" value="heavy" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🌿🌿🌿</span>
                            <span class="text-xs"><?= __('beach.checkin_heavy') ?></span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label for="checkin-notes" class="block text-sm font-medium text-gray-700 mb-1">
                    <?= __('beach.checkin_notes') ?> <span class="text-gray-400"><?= __('beach.optional') ?></span>
                </label>
                <textarea name="notes" id="checkin-notes" rows="2" maxlength="280"
                          placeholder="<?= h(__('beach.checkin_notes_placeholder')) ?>"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 resize-none text-sm"></textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" id="checkin-submit-btn"
                        class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2.5 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span><?= __('beach.checkin_submit') ?></span>
                </button>
                <button type="button" data-action="closeCheckinModal"
                        class="px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                    <?= __('beach.checkin_cancel') ?>
                </button>
            </div>

            <div id="checkin-message" class="hidden text-sm px-4 py-3 rounded-lg"></div>
        </form>
    </div>
</div>


<script <?= cspNonceAttr() ?>>
function openCheckinModal(beachId, beachName) {
    document.getElementById('checkin-beach-id').value = beachId;
    document.getElementById('checkin-beach-name').textContent = beachName || 'this beach';
    document.getElementById('checkin-modal').classList.remove('hidden');
    document.getElementById('checkin-modal').classList.add('flex');
    document.body.style.overflow = 'hidden';

    // Reset form
    document.getElementById('checkin-form').reset();
    document.getElementById('checkin-message').classList.add('hidden');

    // Re-init Lucide icons
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeCheckinModal() {
    document.getElementById('checkin-modal').classList.add('hidden');
    document.getElementById('checkin-modal').classList.remove('flex');
    document.body.style.overflow = '';
}

// Bridge from the post-check-in nudge to the review modal for the same beach.
function openReviewFromCheckin() {
    const beachId = document.getElementById('checkin-beach-id')?.value || '';
    const beachName = document.getElementById('checkin-beach-name')?.textContent || '';
    closeCheckinModal();
    if (typeof openReviewForm === 'function') {
        openReviewForm(beachId, beachName);
    }
}

async function submitCheckin(event) {
    event.preventDefault();

    const form = document.getElementById('checkin-form');
    const submitBtn = document.getElementById('checkin-submit-btn');
    const messageDiv = document.getElementById('checkin-message');

    // Check if at least one option is selected
    const hasSelection = form.querySelector('input[type="radio"]:checked') || form.querySelector('#checkin-notes').value.trim();
    if (!hasSelection) {
        messageDiv.textContent = '<?= __('beach.checkin_select_condition') ?>';
        messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
        messageDiv.classList.remove('hidden');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="animate-pulse"><?= __('beach.checkin_submitting') ?></span>';
    messageDiv.classList.add('hidden');

    try {
        const formData = new FormData(form);
        const response = await fetch('/api/checkin.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            messageDiv.textContent = data.message || '<?= __('beach.checkin_success') ?>';
            messageDiv.className = 'bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg';
            messageDiv.classList.remove('hidden');

            if (typeof showToast === 'function') {
                showToast('<?= __('beach.checkin_toast_success') ?>', 'success', 3000);
            }

            if (typeof window.bfTrack === 'function') {
                window.bfTrack('U1_checkin_submitted', { source: 'beach_page', beach_slug: <?= json_encode($beach['slug']) ?> });
            }

            // Explorer level-up celebration (levels are recomputed server-side on each check-in)
            if (data.leveled_up) {
                const levelMsg = <?= json_encode($lang === 'es' ? '¡Subiste de nivel! Ahora eres ' : 'Level up! You\'re now a ') ?>
                    + (data.level_icon || '🏆') + ' ' + (data.level_label || '');
                if (typeof showToast === 'function') {
                    showToast(levelMsg, 'success', 6000);
                }
                if (typeof window.bfTrack === 'function') {
                    window.bfTrack('U1_explorer_level_up', { level: data.explorer_level || '' });
                }
            }

            // Newly-earned achievement badges (staggered so they don't collide with the level-up toast)
            if (Array.isArray(data.newly_earned_badges) && data.newly_earned_badges.length && typeof showToast === 'function') {
                const badgePrefix = <?= json_encode($lang === 'es' ? 'Insignia desbloqueada: ' : 'Badge unlocked: ') ?>;
                data.newly_earned_badges.forEach((b, i) => {
                    setTimeout(() => {
                        showToast((b.icon || '🏅') + ' ' + badgePrefix + (b.label || ''), 'success', 5000);
                    }, (data.leveled_up ? 1200 : 300) + i * 1100);
                });
                if (typeof window.bfTrack === 'function') {
                    window.bfTrack('U3_badge_earned', { count: data.newly_earned_badges.length });
                }
            }

            // Refresh check-ins list
            if (typeof htmx !== 'undefined') {
                htmx.trigger('#checkins-list', 'load');
            }

            // Post-check-in review nudge: for authed users who haven't reviewed THIS beach,
            // offer a one-tap review instead of auto-closing (frequency-capped per beach).
            <?php
                $checkinCanReview = false;
                if (function_exists('isAuthenticated') && isAuthenticated() && !empty($_SESSION['user_id'])) {
                    // Check across ALL statuses (published/pending/hidden) so we don't nudge a
                    // user who already reviewed and would just be rejected by createReview().
                    $checkinCanReview = !queryOne(
                        'SELECT 1 AS x FROM beach_reviews WHERE beach_id = :b AND user_id = :u LIMIT 1',
                        [':b' => $beach['id'], ':u' => (string)$_SESSION['user_id']]
                    );
                }
            ?>
            const bfCheckinCanReview = <?= $checkinCanReview ? 'true' : 'false' ?>;
            const bfCheckinBeachId = document.getElementById('checkin-beach-id')?.value || '';
            let bfShowReviewNudge = false;
            try {
                bfShowReviewNudge = bfCheckinCanReview && !data.requires_signup && bfCheckinBeachId
                    && !localStorage.getItem('bf_review_nudge_' + bfCheckinBeachId);
            } catch (e) {}

            if (bfShowReviewNudge) {
                try { localStorage.setItem('bf_review_nudge_' + bfCheckinBeachId, '1'); } catch (e) {}
                messageDiv.innerHTML = '<div class="mb-2"><?= h(__('beach.checkin_review_nudge')) ?></div>'
                    + '<button type="button" data-action="openReviewFromCheckin" class="inline-flex items-center gap-1 bg-sunset-400 hover:bg-sunset-300 text-ocean-900 px-3 py-1.5 rounded-lg text-sm font-semibold"><?= h(__('beach.checkin_review_nudge_cta')) ?></button>';
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i><span><?= __('beach.checkin_submit') ?></span>';
            } else {
                setTimeout(() => {
                    closeCheckinModal();
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i><span><?= __('beach.checkin_submit') ?></span>';
                }, 1000);
            }

            // Reward gate for guests: contribute -> signup.
            if (data.requires_signup && typeof showSignupPrompt === 'function') {
                showSignupPrompt('favorites', '/beach/<?= h($beach['slug']) ?>?src=checkin');
            }
        } else {
            messageDiv.textContent = data.error || '<?= __('beach.checkin_error') ?>';
            messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
            messageDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i><span><?= __('beach.checkin_submit') ?></span>';
        }
    } catch (error) {
        console.error('Check-in error:', error);
        messageDiv.textContent = '<?= __('beach.checkin_network_error') ?>';
        messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
        messageDiv.classList.remove('hidden');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i><span><?= __('beach.checkin_submit') ?></span>';
    }
}

// Close checkin modal on escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeCheckinModal();
});
</script>

<!-- Review Form Modal -->
<div id="review-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="review-modal-title" data-action="closeReviewModal">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto" data-action-stop data-action="noop" data-on="click">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h2 id="review-modal-title" class="text-lg font-semibold text-gray-900"><?= __('beach.review_modal_title') ?></h2>
            <button data-action="closeReviewModal" class="text-gray-400 hover:text-gray-600 p-1" aria-label="Close">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="review-form" class="p-6 space-y-5" data-action="submitReview" data-action-args='["__event__"]' data-on="submit">
            <input type="hidden" name="beach_id" id="review-beach-id">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">

            <p class="text-sm text-gray-600">
                <?= __('beach.review_share_experience', ['name' => '<strong id="review-beach-name">' . h($beach['name']) . '</strong>']) ?>
            </p>

            <!-- Rating -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?= __('beach.review_your_rating') ?> <span class="text-red-500 a11y-error-text">*</span></label>
                <div class="flex gap-1" id="star-rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button type="button" data-action="setRating" data-action-args='[<?= $i ?>]' data-star="<?= $i ?>"
                            class="star-btn text-3xl text-gray-300 hover:text-amber-400 transition-colors">
                        ★
                    </button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="review-rating" value="0" required>
            </div>

            <!-- Title -->
            <div>
                <label for="review-title" class="block text-sm font-medium text-gray-700 mb-1">
                    <?= __('beach.review_title_label') ?> <span class="text-gray-400"><?= __('beach.optional') ?></span>
                </label>
                <input type="text" name="title" id="review-title" maxlength="100"
                       placeholder="<?= h(__('beach.review_title_placeholder')) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Review Text -->
            <div>
                <label for="review-text" class="block text-sm font-medium text-gray-700 mb-1">
                    <?= __('beach.review_your_review') ?> <span class="text-red-500 a11y-error-text">*</span>
                </label>
                <textarea name="review_text" id="review-text" rows="4" minlength="20" maxlength="5000" required
                          placeholder="<?= h(__('beach.review_body_placeholder')) ?>"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                <p class="text-xs text-gray-400 mt-1"><?= __('beach.review_min_chars') ?></p>
            </div>

            <!-- Pros/Cons -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="review-pros" class="block text-sm font-medium text-green-700 mb-1">
                        <?= __('reviews.pros') ?> <span class="text-gray-400"><?= __('beach.optional') ?></span>
                    </label>
                    <textarea name="pros" id="review-pros" rows="2" maxlength="500"
                              placeholder="<?= h(__('reviews.pros_placeholder')) ?>"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 resize-none text-sm"></textarea>
                </div>
                <div>
                    <label for="review-cons" class="block text-sm font-medium text-red-700 mb-1">
                        <?= __('reviews.cons') ?> <span class="text-gray-400"><?= __('beach.optional') ?></span>
                    </label>
                    <textarea name="cons" id="review-cons" rows="2" maxlength="500"
                              placeholder="<?= h(__('reviews.cons_placeholder')) ?>"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none text-sm"></textarea>
                </div>
            </div>

            <!-- Visit Details -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="review-visit-date" class="block text-sm font-medium text-gray-700 mb-1">
                        <?= __('beach.review_when_visit') ?>
                    </label>
                    <input type="month" name="visit_date" id="review-visit-date"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="review-visited-with" class="block text-sm font-medium text-gray-700 mb-1">
                        <?= __('beach.review_who_with') ?>
                    </label>
                    <select name="visited_with" id="review-visited-with"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value=""><?= __('beach.review_select') ?></option>
                        <option value="solo"><?= __('beach.review_solo') ?></option>
                        <option value="partner"><?= __('beach.review_partner') ?></option>
                        <option value="family"><?= __('beach.review_family') ?></option>
                        <option value="friends"><?= __('beach.review_friends') ?></option>
                        <option value="group"><?= __('beach.review_group') ?></option>
                    </select>
                </div>
            </div>

            <!-- Photo Upload -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <?= __('beach.review_add_photos') ?> <span class="text-gray-400"><?= __('beach.optional') ?></span>
                </label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-blue-400 transition-colors">
                    <input type="file" name="photos[]" id="review-photos" accept="image/jpeg,image/png,image/webp" multiple
                           class="hidden" data-action="previewPhotos" data-action-args='["__this__"]' data-on="change">
                    <label for="review-photos" class="cursor-pointer">
                        <i data-lucide="camera" class="w-8 h-8 mx-auto text-gray-400 mb-2"></i>
                        <p class="text-sm text-gray-600"><?= __('beach.review_click_upload') ?></p>
                        <p class="text-xs text-gray-400 mt-1"><?= __('beach.review_file_types') ?></p>
                    </label>
                </div>
                <div id="photo-preview" class="flex gap-2 mt-2 flex-wrap"></div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" id="review-submit-btn"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg font-medium transition-colors">
                    <?= __('beach.review_submit') ?>
                </button>
                <button type="button" data-action="closeReviewModal"
                        class="px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                    <?= __('beach.review_cancel') ?>
                </button>
            </div>

            <div id="review-message" class="hidden text-sm px-4 py-3 rounded-lg"></div>
        </form>
    </div>
</div>

<!-- Photo Upload Modal (standalone) -->
<div id="photo-upload-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="photo-upload-modal-title" data-action="closePhotoUploadModal">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full" data-action-stop data-action="noop" data-on="click">
        <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h2 id="photo-upload-modal-title" class="text-lg font-semibold text-gray-900"><?= __('beach.upload_modal_title') ?></h2>
            <button data-action="closePhotoUploadModal" class="text-gray-400 hover:text-gray-600 p-1" aria-label="Close">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="photo-upload-form" class="p-6 space-y-4" data-action="submitPhotoUpload" data-action-args='["__event__"]' data-on="submit">
            <input type="hidden" name="beach_id" id="upload-beach-id">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">

            <p class="text-sm text-gray-600">
                <?= __('beach.upload_share_photos', ['name' => '<strong id="upload-beach-name">' . h($beach['name']) . '</strong>']) ?>
            </p>

            <div>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-purple-400 transition-colors">
                    <input type="file" name="photo" id="upload-photo" accept="image/jpeg,image/png,image/webp" required
                           class="hidden" data-action="previewUploadPhoto" data-action-args='["__this__"]' data-on="change">
                    <label for="upload-photo" class="cursor-pointer">
                        <i data-lucide="image-plus" class="w-10 h-10 mx-auto text-gray-400 mb-2"></i>
                        <p class="text-sm text-gray-600"><?= __('beach.upload_click_select') ?></p>
                        <p class="text-xs text-gray-400 mt-1"><?= __('beach.upload_file_types') ?></p>
                    </label>
                </div>
                <div id="upload-preview" class="mt-3 hidden">
                    <img id="upload-preview-img" src="" alt="Preview" class="max-h-48 mx-auto rounded-lg">
                </div>
            </div>

            <div>
                <label for="upload-caption" class="block text-sm font-medium text-gray-700 mb-1">
                    Caption <span class="text-gray-400">(optional)</span>
                </label>
                <input type="text" name="caption" id="upload-caption" maxlength="200"
                       placeholder="Add a caption to your photo..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" id="upload-submit-btn"
                        class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-lg font-medium transition-colors">
                    Upload Photo
                </button>
                <button type="button" data-action="closePhotoUploadModal"
                        class="px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
            </div>

            <div id="upload-message" class="hidden text-sm px-4 py-3 rounded-lg"></div>
        </form>
    </div>
</div>

<!-- Photo Lightbox Modal -->
<div id="photo-lightbox" class="fixed inset-0 bg-black/90 z-50 hidden items-center justify-center"
     role="dialog" aria-modal="true" data-action="closePhotoModal">
    <button data-action="closePhotoModal" class="absolute top-4 right-4 text-white/70 hover:text-white p-2" aria-label="Close">
        <i data-lucide="x" class="w-8 h-8"></i>
    </button>
    <div class="max-w-5xl max-h-[90vh] p-4" data-action-stop data-action="noop" data-on="click">
        <img id="photo-lightbox-img" src="" alt="" class="max-w-full max-h-[85vh] object-contain rounded-lg">
        <p id="photo-lightbox-caption" class="text-white/80 text-center mt-3 text-sm"></p>
    </div>
</div>

<script <?= cspNonceAttr() ?>>
// Star rating
function setRating(rating) {
    document.getElementById('review-rating').value = rating;
    const stars = document.querySelectorAll('#star-rating .star-btn');
    stars.forEach((star, idx) => {
        if (idx < rating) {
            star.classList.remove('text-gray-300');
            star.classList.add('text-amber-400');
        } else {
            star.classList.remove('text-amber-400');
            star.classList.add('text-gray-300');
        }
    });
}

// Review modal
function openReviewForm(beachId, beachName) {
    document.getElementById('review-beach-id').value = beachId;
    document.getElementById('review-beach-name').textContent = beachName || 'this beach';
    document.getElementById('review-modal').classList.remove('hidden');
    document.getElementById('review-modal').classList.add('flex');
    document.body.style.overflow = 'hidden';

    // Reset form
    document.getElementById('review-form').reset();
    document.getElementById('review-rating').value = '0';
    document.querySelectorAll('#star-rating .star-btn').forEach(s => {
        s.classList.remove('text-amber-400');
        s.classList.add('text-gray-300');
    });
    document.getElementById('photo-preview').innerHTML = '';
    document.getElementById('review-message').classList.add('hidden');

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeReviewModal() {
    document.getElementById('review-modal').classList.add('hidden');
    document.getElementById('review-modal').classList.remove('flex');
    document.body.style.overflow = '';
}

function previewPhotos(input) {
    const preview = document.getElementById('photo-preview');
    preview.innerHTML = '';

    if (input.files) {
        Array.from(input.files).slice(0, 5).forEach((file, idx) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.className = 'relative w-16 h-16 rounded-lg overflow-hidden';
                div.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-cover" alt="Preview">
                    <button type="button" data-action="removePhoto" data-action-args='[${idx}]' class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-5 h-5 text-xs">×</button>
                `;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }
}

async function submitReview(event) {
    event.preventDefault();

    const form = document.getElementById('review-form');
    const submitBtn = document.getElementById('review-submit-btn');
    const messageDiv = document.getElementById('review-message');

    const rating = document.getElementById('review-rating').value;
    if (!rating || rating === '0') {
        messageDiv.textContent = 'Please select a rating.';
        messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
        messageDiv.classList.remove('hidden');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
    messageDiv.classList.add('hidden');

    try {
        const formData = new FormData(form);
        const response = await fetch('/api/reviews.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            messageDiv.textContent = data.message || 'Review submitted!';
            messageDiv.className = 'bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg';
            messageDiv.classList.remove('hidden');

            if (typeof showToast === 'function') {
                showToast('Review submitted!', 'success', 3000);
            }
            // Queue any earned badges to celebrate after the reload below.
            if (typeof window.bfQueueBadgeToasts === 'function') {
                window.bfQueueBadgeToasts(data.newly_earned_badges);
            }

            setTimeout(() => {
                closeReviewModal();
                location.reload();
            }, 1500);
        } else {
            messageDiv.textContent = data.error || 'Failed to submit review';
            messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
            messageDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Review';
        }
    } catch (error) {
        console.error('Review error:', error);
        messageDiv.textContent = 'Network error. Please try again.';
        messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
        messageDiv.classList.remove('hidden');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Review';
    }
}

// Photo upload modal
function openPhotoUploadModal(beachId, beachName) {
    document.getElementById('upload-beach-id').value = beachId;
    document.getElementById('upload-beach-name').textContent = beachName || 'this beach';
    document.getElementById('photo-upload-modal').classList.remove('hidden');
    document.getElementById('photo-upload-modal').classList.add('flex');
    document.body.style.overflow = 'hidden';

    document.getElementById('photo-upload-form').reset();
    document.getElementById('upload-preview').classList.add('hidden');
    document.getElementById('upload-message').classList.add('hidden');

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closePhotoUploadModal() {
    document.getElementById('photo-upload-modal').classList.add('hidden');
    document.getElementById('photo-upload-modal').classList.remove('flex');
    document.body.style.overflow = '';
}

function previewUploadPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('upload-preview-img').src = e.target.result;
            document.getElementById('upload-preview').classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

async function submitPhotoUpload(event) {
    event.preventDefault();

    const form = document.getElementById('photo-upload-form');
    const submitBtn = document.getElementById('upload-submit-btn');
    const messageDiv = document.getElementById('upload-message');

    submitBtn.disabled = true;
    submitBtn.textContent = 'Uploading...';
    messageDiv.classList.add('hidden');

    try {
        const formData = new FormData(form);
        const response = await fetch('/api/photos.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            messageDiv.textContent = data.message || 'Photo uploaded!';
            messageDiv.className = 'bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg';
            messageDiv.classList.remove('hidden');

            if (typeof showToast === 'function') {
                showToast('Photo uploaded!', 'success', 3000);
            }
            // Queue any earned badges to celebrate after the reload below.
            if (typeof window.bfQueueBadgeToasts === 'function') {
                window.bfQueueBadgeToasts(data.newly_earned_badges);
            }

            setTimeout(() => {
                closePhotoUploadModal();
                location.reload();
            }, 1500);
        } else {
            messageDiv.textContent = data.error || 'Failed to upload photo';
            messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
            messageDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Upload Photo';
        }
    } catch (error) {
        console.error('Upload error:', error);
        messageDiv.textContent = 'Network error. Please try again.';
        messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
        messageDiv.classList.remove('hidden');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Upload Photo';
    }
}

// Photo lightbox
function openPhotoModal(url, caption) {
    document.getElementById('photo-lightbox-img').src = url;
    document.getElementById('photo-lightbox-img').alt = caption || '';
    document.getElementById('photo-lightbox-caption').textContent = caption || '';
    document.getElementById('photo-lightbox').classList.remove('hidden');
    document.getElementById('photo-lightbox').classList.add('flex');
    document.body.style.overflow = 'hidden';

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closePhotoModal() {
    document.getElementById('photo-lightbox').classList.add('hidden');
    document.getElementById('photo-lightbox').classList.remove('flex');
    document.body.style.overflow = '';
}

// Review voting
async function voteReview(reviewId, btn) {
    <?php if (!isAuthenticated()): ?>
    window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname + '#reviews');
    return;
    <?php endif; ?>

    try {
        const formData = new FormData();
        formData.append('action', 'vote');
        formData.append('review_id', reviewId);
        formData.append('csrf_token', '<?= h(csrfToken()) ?>');

        const response = await fetch('/api/reviews.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            const isVoted = btn.dataset.voted === 'true';
            btn.dataset.voted = isVoted ? 'false' : 'true';

            if (isVoted) {
                btn.classList.remove('text-blue-600');
                btn.classList.add('text-gray-500');
            } else {
                btn.classList.remove('text-gray-500');
                btn.classList.add('text-blue-600');
            }

            // Update count
            let countEl = btn.querySelector('.helpful-count');
            if (countEl) {
                const count = parseInt(countEl.textContent) + (isVoted ? -1 : 1);
                if (count > 0) {
                    countEl.textContent = count;
                } else {
                    countEl.remove();
                }
            } else if (!isVoted) {
                const span = document.createElement('span');
                span.className = 'helpful-count text-xs bg-gray-100 px-1.5 py-0.5 rounded-full';
                span.textContent = '1';
                btn.appendChild(span);
            }
        }
    } catch (error) {
        console.error('Vote error:', error);
    }
}

// Delete review
async function deleteReview(reviewId) {
    if (!confirm('Are you sure you want to delete your review? This cannot be undone.')) return;

    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('review_id', reviewId);
        formData.append('csrf_token', '<?= h(csrfToken()) ?>');

        const response = await fetch('/api/reviews.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            if (typeof showToast === 'function') {
                showToast('Review deleted', 'success', 3000);
            }
            location.reload();
        } else {
            alert(data.error || 'Failed to delete review');
        }
    } catch (error) {
        console.error('Delete error:', error);
        alert('Network error. Please try again.');
    }
}

// Share review
function shareReview(reviewId) {
    const url = window.location.origin + window.location.pathname + '#review-' + reviewId;
    if (navigator.share) {
        navigator.share({ url: url });
    } else if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            if (typeof showToast === 'function') {
                showToast('Link copied!', 'success', 2000);
            }
        });
    }
}

// Report review
function reportReview(reviewId) {
    alert('Report functionality coming soon. For now, please contact us at support@puertoricobeachfinder.com');
}

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeReviewModal();
        closePhotoUploadModal();
        closePhotoModal();
    }
});
</script>

<script <?= cspNonceAttr() ?>>
// Load weather data client-side (avoids blocking TTFB with external API call)
