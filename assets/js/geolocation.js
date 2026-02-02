// Geolocation functions for Beach Finder

function requestUserLocation() {
    if (!navigator.geolocation) {
        alert("Geolocation is not supported by your browser");
        return;
    }

    // Desktop elements
    const locationBtn = document.getElementById("location-btn");
    const locationIcon = document.getElementById("location-icon");
    const locationText = document.getElementById("location-text");

    // Mobile elements (in filter bar and filter drawer)
    const mobileNearmeBtn = document.getElementById("mobile-nearme-btn");
    const mobileNearmeIcon = document.getElementById("mobile-nearme-icon");
    const mobileNearmeText = document.getElementById("mobile-nearme-text");
    const mobileLocationBtn = document.getElementById("mobile-location-btn");
    const mobileLocationIcon = document.getElementById("mobile-location-icon");
    const mobileLocationText = document.getElementById("mobile-location-text");

    // Update desktop UI - loading state
    if (locationIcon) locationIcon.textContent = "⏳";
    if (locationText) locationText.textContent = "Getting location...";
    if (locationBtn) locationBtn.disabled = true;

    // Update mobile UI - loading state
    if (mobileNearmeText) mobileNearmeText.textContent = "Finding...";
    if (mobileNearmeBtn) mobileNearmeBtn.disabled = true;
    if (mobileLocationText) mobileLocationText.textContent = "Getting location...";
    if (mobileLocationBtn) mobileLocationBtn.disabled = true;

    state.geolocationStatus = "requesting";

    navigator.geolocation.getCurrentPosition(
        // Success
        function(position) {
            state.userLocation = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };
            state.geolocationStatus = "granted";
            localStorage.setItem("userLocation", JSON.stringify(state.userLocation));

            // Re-enable buttons
            if (locationBtn) locationBtn.disabled = false;
            if (mobileNearmeBtn) mobileNearmeBtn.disabled = false;
            if (mobileLocationBtn) mobileLocationBtn.disabled = false;

            onLocationGranted();
            applyFilters();

            // Update map if in map view
            if (state.viewMode === "map" && typeof addUserMarker === "function") {
                addUserMarker();
            }
        },
        // Error
        function(error) {
            state.geolocationStatus = "denied";

            // Reset desktop UI
            if (locationIcon) locationIcon.textContent = "📍";
            if (locationText) locationText.textContent = "Location denied";
            if (locationBtn) {
                locationBtn.disabled = false;
                locationBtn.classList.add("border-red-300", "text-red-600");
            }

            // Reset mobile UI
            if (mobileNearmeText) mobileNearmeText.textContent = "Denied";
            if (mobileNearmeBtn) {
                mobileNearmeBtn.disabled = false;
                mobileNearmeBtn.classList.add("border-red-300", "text-red-400");
            }
            if (mobileLocationText) mobileLocationText.textContent = "Location denied";
            if (mobileLocationBtn) {
                mobileLocationBtn.disabled = false;
                mobileLocationBtn.classList.add("border-red-300", "text-red-400");
            }

            let message = "Unable to get your location.";
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    message = "Location access denied. Please enable location in your browser settings.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = "Location information unavailable.";
                    break;
                case error.TIMEOUT:
                    message = "Location request timed out. Please try again.";
                    break;
            }
            console.warn("Geolocation error:", error);

            // Show toast notification
            if (typeof showToast === "function") {
                showToast(message, "error", 4000);
            }

            // Reset UI after delay
            setTimeout(function() {
                if (locationText) locationText.textContent = "Use My Location";
                if (locationIcon) locationIcon.textContent = "📍";
                if (locationBtn) locationBtn.classList.remove("border-red-300", "text-red-600");

                if (mobileNearmeText) mobileNearmeText.textContent = "Near Me";
                if (mobileNearmeBtn) mobileNearmeBtn.classList.remove("border-red-300", "text-red-400");
                if (mobileLocationText) mobileLocationText.textContent = "Use My Location";
                if (mobileLocationBtn) mobileLocationBtn.classList.remove("border-red-300", "text-red-400");
            }, 3000);
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 300000
        }
    );
}

function onLocationGranted() {
    // Desktop elements
    const locationBtn = document.getElementById("location-btn");
    const locationIcon = document.getElementById("location-icon");
    const locationText = document.getElementById("location-text");
    const distanceContainer = document.getElementById("distance-filter-container");
    const sortDistanceOption = document.getElementById("sort-distance-option");

    // Mobile elements
    const mobileNearmeBtn = document.getElementById("mobile-nearme-btn");
    const mobileNearmeText = document.getElementById("mobile-nearme-text");
    const mobileLocationBtn = document.getElementById("mobile-location-btn");
    const mobileLocationText = document.getElementById("mobile-location-text");
    const mobileDistanceContainer = document.getElementById("mobile-distance-container");
    const mobileSortDistanceOption = document.getElementById("mobile-sort-distance-option");

    // Update desktop UI
    if (locationBtn) {
        locationBtn.classList.add("bg-green-50", "border-green-300", "text-green-700");
        locationBtn.classList.remove("border-stone-300");
    }
    if (locationIcon) locationIcon.textContent = "✓";
    if (locationText) locationText.textContent = "Location enabled";
    if (distanceContainer) distanceContainer.classList.remove("hidden");
    if (sortDistanceOption) sortDistanceOption.disabled = false;

    // Update mobile near me button
    if (mobileNearmeBtn) {
        mobileNearmeBtn.classList.add("bg-green-500/20", "border-green-500/50", "text-green-300");
        mobileNearmeBtn.classList.remove("text-white/80");
    }
    if (mobileNearmeText) mobileNearmeText.textContent = "Near Me ✓";

    // Update mobile filter drawer location button
    if (mobileLocationBtn) {
        mobileLocationBtn.classList.add("bg-green-500/20", "border-green-500/50", "text-green-300");
        mobileLocationBtn.classList.remove("text-white/80");
    }
    if (mobileLocationText) mobileLocationText.textContent = "Location enabled";
    if (mobileDistanceContainer) mobileDistanceContainer.classList.remove("hidden");
    if (mobileSortDistanceOption) mobileSortDistanceOption.disabled = false;

    // Update distance badges on beach cards
    updateDistanceBadges();

    // Show toast
    if (typeof showToast === "function") {
        showToast("Location enabled! Beaches sorted by distance.", "success", 3000);
    }
}

function calculateDistance(lat1, lng1, lat2, lng2) {
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
              Math.sin(dLng / 2) * Math.sin(dLng / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return 6371000 * c; // Distance in meters
}

function toRad(deg) {
    return deg * (Math.PI / 180);
}

function formatDistance(meters) {
    if (meters < 1000) {
        return Math.round(meters) + "m";
    }
    return (meters / 1000).toFixed(1) + "km";
}

function sortByDistance(beaches) {
    if (!state.userLocation) return beaches;

    return beaches.map(function(beach) {
        return {
            ...beach,
            _distance: calculateDistance(
                state.userLocation.lat,
                state.userLocation.lng,
                parseFloat(beach.lat),
                parseFloat(beach.lng)
            )
        };
    }).sort(function(a, b) {
        return a._distance - b._distance;
    });
}

function filterByDistance(beaches, maxKm) {
    if (!state.userLocation) return beaches;

    const maxMeters = maxKm * 1000;
    return beaches.filter(function(beach) {
        const dist = calculateDistance(
            state.userLocation.lat,
            state.userLocation.lng,
            parseFloat(beach.lat),
            parseFloat(beach.lng)
        );
        return dist <= maxMeters;
    });
}

function updateDistanceBadges() {
    if (!state.userLocation) return;

    document.querySelectorAll(".beach-card").forEach(function(card) {
        const lat = parseFloat(card.dataset.lat);
        const lng = parseFloat(card.dataset.lng);

        if (lat && lng) {
            const distance = calculateDistance(
                state.userLocation.lat,
                state.userLocation.lng,
                lat,
                lng
            );

            let badge = card.querySelector(".distance-badge");
            if (!badge) {
                badge = document.createElement("div");
                badge.className = "distance-badge absolute top-3 right-3 bg-brand-yellow text-brand-darker text-xs font-semibold px-2 py-1 rounded-full shadow";
                const cardImageContainer = card.querySelector(".relative");
                if (cardImageContainer) {
                    cardImageContainer.appendChild(badge);
                }
            }
            badge.textContent = formatDistance(distance);
        }
    });
}
