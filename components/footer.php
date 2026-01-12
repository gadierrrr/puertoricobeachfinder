    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-12 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- About -->
                <div>
                    <h3 class="text-white font-semibold text-lg mb-4 flex items-center gap-2">
                        <i data-lucide="umbrella" class="w-5 h-5 text-blue-400"></i>
                        <span>Beach Finder</span>
                    </h3>
                    <p class="text-sm">
                        Discover the perfect beach in Puerto Rico. Explore 230+ beaches with detailed information,
                        conditions, and amenities.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-white font-semibold text-lg mb-4">Quick Links</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/" class="hover:text-white transition-colors">Explore Beaches</a></li>
                        <li><a href="/?tags[]=family-friendly" class="hover:text-white transition-colors">Family-Friendly Beaches</a></li>
                        <li><a href="/?tags[]=surfing" class="hover:text-white transition-colors">Surfing Spots</a></li>
                        <li><a href="/?tags[]=snorkeling" class="hover:text-white transition-colors">Snorkeling Beaches</a></li>
                    </ul>
                </div>

                <!-- Popular Regions -->
                <div>
                    <h3 class="text-white font-semibold text-lg mb-4">Popular Regions</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/?municipality=San+Juan" class="hover:text-white transition-colors">San Juan</a></li>
                        <li><a href="/?municipality=Rincon" class="hover:text-white transition-colors">Rincon</a></li>
                        <li><a href="/?municipality=Fajardo" class="hover:text-white transition-colors">Fajardo</a></li>
                        <li><a href="/?municipality=Vieques" class="hover:text-white transition-colors">Vieques</a></li>
                        <li><a href="/?municipality=Culebra" class="hover:text-white transition-colors">Culebra</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm">
                <p>&copy; <?= date('Y') ?> Puerto Rico Beach Finder. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- MapLibre GL JS (defer for non-blocking load) -->
    <script defer src="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.js"></script>

    <!-- App JavaScript (defer for non-blocking load) -->
    <script defer src="/assets/js/app.js"></script>
    <script defer src="/assets/js/geolocation.js"></script>
    <script defer src="/assets/js/filters.js"></script>
    <script defer src="/assets/js/share.js"></script>

    <!-- Initialize Lucide Icons -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
    // Re-initialize after HTMX swaps
    document.body.addEventListener('htmx:afterSwap', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
    </script>

    <!-- PWA Service Worker & Install Prompt -->
    <script>
    // Register service worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', async () => {
            try {
                const registration = await navigator.serviceWorker.register('/sw.js');
                console.log('SW registered:', registration.scope);

                // Check for updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            // New version available
                            if (confirm('A new version is available! Reload to update?')) {
                                newWorker.postMessage('skipWaiting');
                                window.location.reload();
                            }
                        }
                    });
                });
            } catch (error) {
                console.error('SW registration failed:', error);
            }
        });
    }

    // PWA Install Prompt
    let deferredPrompt;
    const installBanner = document.getElementById('pwa-install-banner');

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;

        // Show install banner if not dismissed before
        if (!localStorage.getItem('pwa-install-dismissed') && installBanner) {
            installBanner.classList.remove('hidden');
        }
    });

    function installPWA() {
        if (!deferredPrompt) return;

        deferredPrompt.prompt();
        deferredPrompt.userChoice.then((result) => {
            if (result.outcome === 'accepted') {
                console.log('PWA installed');
            }
            deferredPrompt = null;
            if (installBanner) installBanner.classList.add('hidden');
        });
    }

    function dismissInstall() {
        localStorage.setItem('pwa-install-dismissed', 'true');
        if (installBanner) installBanner.classList.add('hidden');
    }

    // Detect if running as PWA
    if (window.matchMedia('(display-mode: standalone)').matches) {
        document.body.classList.add('pwa-standalone');
    }
    </script>

    <!-- PWA Install Banner (hidden by default) -->
    <div id="pwa-install-banner" class="hidden fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-96 bg-white rounded-xl shadow-xl border border-gray-200 p-4 z-50">
        <div class="flex items-start gap-3">
            <img src="/assets/icons/icon-72x72.png" alt="" class="w-12 h-12 rounded-lg">
            <div class="flex-1">
                <h3 class="font-semibold text-gray-900">Install Beach Finder</h3>
                <p class="text-sm text-gray-600 mt-1">Add to your home screen for quick access and offline features.</p>
                <div class="flex gap-2 mt-3">
                    <button onclick="installPWA()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Install
                    </button>
                    <button onclick="dismissInstall()" class="text-gray-500 hover:text-gray-700 px-4 py-2 text-sm font-medium">
                        Not now
                    </button>
                </div>
            </div>
            <button onclick="dismissInstall()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Review Form Modal -->
    <div id="review-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4"
         role="dialog" aria-modal="true" aria-labelledby="review-modal-title" onclick="closeReviewForm()">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <h2 id="review-modal-title" class="text-xl font-semibold text-gray-900">Write a Review</h2>
                <button onclick="closeReviewForm()" class="text-gray-400 hover:text-gray-600 p-1" aria-label="Close review form">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <form id="review-form" class="p-6 space-y-5" onsubmit="submitReview(event)">
                <input type="hidden" name="beach_id" id="review-beach-id">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">

                <!-- Beach Name (display only) -->
                <div>
                    <div class="text-sm text-gray-500 mb-1">Reviewing</div>
                    <div id="review-beach-name" class="font-semibold text-gray-900"></div>
                </div>

                <!-- Star Rating -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Your Rating <span class="text-red-500">*</span></label>
                    <div class="flex gap-1" id="star-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" class="star-btn text-3xl text-gray-300 hover:text-yellow-400 transition-colors" data-rating="<?= $i ?>" onclick="setRating(<?= $i ?>)">★</button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="review-rating" value="0" required>
                    <p class="text-red-500 text-sm mt-1 hidden" id="rating-error">Please select a rating</p>
                </div>

                <!-- Title -->
                <div>
                    <label for="review-title" class="block text-sm font-medium text-gray-700 mb-1">Title (optional)</label>
                    <input type="text" name="title" id="review-title" maxlength="100" placeholder="Summarize your experience"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Review Text -->
                <div>
                    <label for="review-text" class="block text-sm font-medium text-gray-700 mb-1">Your Review</label>
                    <textarea name="review_text" id="review-text" rows="4" maxlength="2000" placeholder="Share your experience at this beach..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                    <div class="text-xs text-gray-400 text-right mt-1"><span id="char-count">0</span>/2000</div>
                </div>

                <!-- Visit Details -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="review-visit-date" class="block text-sm font-medium text-gray-700 mb-1">When did you visit?</label>
                        <input type="month" name="visit_date" id="review-visit-date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label for="review-visit-type" class="block text-sm font-medium text-gray-700 mb-1">Trip type</label>
                        <select name="visit_type" id="review-visit-type"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select...</option>
                            <option value="solo">Solo</option>
                            <option value="couple">Couple</option>
                            <option value="family">Family</option>
                            <option value="friends">Friends</option>
                            <option value="group">Group</option>
                        </select>
                    </div>
                </div>

                <!-- Would Recommend -->
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="would_recommend" id="review-recommend" checked
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="review-recommend" class="text-sm text-gray-700">I would recommend this beach</label>
                </div>

                <!-- Submit -->
                <div class="flex gap-3 pt-2">
                    <button type="submit" id="review-submit-btn"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-medium transition-colors">
                        Submit Review
                    </button>
                    <button type="button" onclick="closeReviewForm()"
                            class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                </div>

                <!-- Error Message -->
                <div id="review-error" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm"></div>
                <!-- Success Message -->
                <div id="review-success" class="hidden bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm"></div>
            </form>
        </div>
    </div>

    <!-- Review Form JavaScript -->
    <script>
    let currentReviewBeachId = null;

    function openReviewForm(beachId, beachName) {
        currentReviewBeachId = beachId;
        document.getElementById('review-beach-id').value = beachId;
        document.getElementById('review-beach-name').textContent = beachName || 'this beach';
        document.getElementById('review-modal').classList.remove('hidden');
        document.getElementById('review-modal').classList.add('flex');
        document.body.style.overflow = 'hidden';

        // Reset form
        document.getElementById('review-form').reset();
        document.getElementById('review-rating').value = '0';
        document.querySelectorAll('.star-btn').forEach(btn => btn.classList.remove('text-yellow-400'));
        document.querySelectorAll('.star-btn').forEach(btn => btn.classList.add('text-gray-300'));
        document.getElementById('review-error').classList.add('hidden');
        document.getElementById('review-success').classList.add('hidden');
        document.getElementById('char-count').textContent = '0';
    }

    function closeReviewForm() {
        document.getElementById('review-modal').classList.add('hidden');
        document.getElementById('review-modal').classList.remove('flex');
        document.body.style.overflow = '';
    }

    function setRating(rating) {
        document.getElementById('review-rating').value = rating;
        document.getElementById('rating-error').classList.add('hidden');

        document.querySelectorAll('.star-btn').forEach((btn, index) => {
            if (index < rating) {
                btn.classList.add('text-yellow-400');
                btn.classList.remove('text-gray-300');
            } else {
                btn.classList.remove('text-yellow-400');
                btn.classList.add('text-gray-300');
            }
        });
    }

    // Character counter
    document.getElementById('review-text')?.addEventListener('input', function() {
        document.getElementById('char-count').textContent = this.value.length;
    });

    async function submitReview(event) {
        event.preventDefault();

        const form = document.getElementById('review-form');
        const submitBtn = document.getElementById('review-submit-btn');
        const errorDiv = document.getElementById('review-error');
        const successDiv = document.getElementById('review-success');

        // Validate rating
        const rating = document.getElementById('review-rating').value;
        if (!rating || rating === '0') {
            document.getElementById('rating-error').classList.remove('hidden');
            return;
        }

        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';
        errorDiv.classList.add('hidden');
        successDiv.classList.add('hidden');

        try {
            const formData = new FormData(form);
            const response = await fetch('/api/submit-review.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                successDiv.textContent = data.message || 'Review submitted successfully!';
                successDiv.classList.remove('hidden');

                // Close modal after delay and reload
                setTimeout(() => {
                    closeReviewForm();
                    window.location.reload();
                }, 1500);
            } else {
                errorDiv.textContent = data.error || 'Failed to submit review';
                errorDiv.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Review';
            }
        } catch (error) {
            console.error('Review submission error:', error);
            errorDiv.textContent = 'Network error. Please try again.';
            errorDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Review';
        }
    }

    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeReviewForm();
        }
    });
    </script>

    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
