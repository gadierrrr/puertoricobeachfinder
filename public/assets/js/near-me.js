// Geolocation enhancement for /beaches-near-me and /es/playas-cerca-de-mi.
//
// The page is fully useful without this file — it ships a municipality
// directory, city proximity links and FAQs server-side, which is what Googlebot
// indexes. This only adds the literal "near me" answer for visitors who opt in.
//
// The trigger button is rendered hidden and revealed here, so a no-JS visitor
// never sees a button that cannot work.
(function () {
  "use strict";

  var root = document.getElementById("near-me");
  if (!root) return;

  var btn = document.getElementById("near-me-btn");
  var statusEl = document.getElementById("near-me-status");
  var resultsEl = document.getElementById("near-me-results");
  if (!btn || !statusEl || !resultsEl) return;

  var isEs = root.getAttribute("data-lang") === "es";
  var heading = root.getAttribute("data-heading") || "";

  var T = isEs
    ? {
        locating: "Buscando tu ubicación…",
        loading: "Ordenando playas por distancia…",
        denied: "No compartiste tu ubicación. Explora por municipio más abajo.",
        unsupported: "Tu navegador no permite compartir ubicación. Explora por municipio más abajo.",
        failed: "No pudimos cargar las playas. Intenta de nuevo.",
        away: "a",
        retry: "Actualizar mi ubicación",
      }
    : {
        locating: "Finding your location…",
        loading: "Sorting beaches by distance…",
        denied: "Location wasn't shared. Browse by municipality below instead.",
        unsupported: "Your browser can't share location. Browse by municipality below instead.",
        failed: "We couldn't load the beaches. Please try again.",
        away: "away",
        retry: "Update my location",
      };

  if (!navigator.geolocation) {
    statusEl.textContent = T.unsupported;
    return;
  }

  btn.hidden = false;

  function haversineKm(lat1, lng1, lat2, lng2) {
    var R = 6371;
    var dLat = ((lat2 - lat1) * Math.PI) / 180;
    var dLng = ((lng2 - lng1) * Math.PI) / 180;
    var a =
      Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos((lat1 * Math.PI) / 180) *
        Math.cos((lat2 * Math.PI) / 180) *
        Math.sin(dLng / 2) *
        Math.sin(dLng / 2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }

  function beachUrl(slug) {
    return (isEs ? "/es/playa/" : "/beach/") + encodeURIComponent(slug);
  }

  function render(beaches) {
    resultsEl.textContent = "";

    var h2 = document.createElement("h2");
    h2.className = "rd-nearme__heading";
    h2.textContent = heading;
    resultsEl.appendChild(h2);

    var ul = document.createElement("ul");
    ul.className = "rd-nearme__list";

    beaches.forEach(function (b) {
      var li = document.createElement("li");
      li.className = "rd-nearme__item";

      var a = document.createElement("a");
      a.className = "rd-nearme__link";
      a.href = beachUrl(b.slug);
      a.textContent = b.name;

      var meta = document.createElement("span");
      meta.className = "rd-nearme__meta";
      var km = b.distance_km;
      var mi = km * 0.621371;
      meta.textContent =
        b.municipality + " · " + km.toFixed(1) + " km / " + mi.toFixed(1) + " mi " + T.away;

      li.appendChild(a);
      li.appendChild(meta);
      ul.appendChild(li);
    });

    resultsEl.appendChild(ul);
    btn.textContent = T.retry;

    if (typeof window.bfTrack === "function") {
      window.bfTrack("N1_near_me_results", { count: beaches.length });
    }
  }

  function locate() {
    statusEl.textContent = T.locating;
    btn.disabled = true;

    navigator.geolocation.getCurrentPosition(
      function (pos) {
        statusEl.textContent = T.loading;
        var lat = pos.coords.latitude;
        var lng = pos.coords.longitude;

        // Reuses the existing 24h-cacheable map endpoint rather than adding a
        // new one; distance is computed client-side so the response stays cacheable.
        fetch("/api/beaches-map.php", { credentials: "same-origin" })
          .then(function (r) {
            if (!r.ok) throw new Error("http " + r.status);
            return r.json();
          })
          .then(function (data) {
            var list = (data && data.beaches) || [];
            var scored = [];
            list.forEach(function (b) {
              if (typeof b.lat !== "number" || typeof b.lng !== "number") return;
              b.distance_km = haversineKm(lat, lng, b.lat, b.lng);
              scored.push(b);
            });
            scored.sort(function (x, y) {
              return x.distance_km - y.distance_km;
            });
            if (!scored.length) {
              statusEl.textContent = T.failed;
              btn.disabled = false;
              return;
            }
            statusEl.textContent = "";
            render(scored.slice(0, 20));
            btn.disabled = false;
          })
          .catch(function () {
            statusEl.textContent = T.failed;
            btn.disabled = false;
          });

        if (typeof window.bfTrack === "function") {
          window.bfTrack("geolocation_granted", { source: "near_me_page" });
        }
      },
      function () {
        statusEl.textContent = T.denied;
        btn.disabled = false;
        if (typeof window.bfTrack === "function") {
          window.bfTrack("geolocation_denied", { source: "near_me_page" });
        }
      },
      { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 }
    );
  }

  btn.addEventListener("click", locate);
})();
