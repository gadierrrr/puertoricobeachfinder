/**
 * Beach Finder analytics wrapper (Umami-compatible).
 *
 * Goals:
 * - Never break UX when analytics is blocked/disabled.
 * - Provide a single bfTrack() API for the app.
 * - Provide delegated click/form tracking for key funnel events.
 */

(function () {
  "use strict";
  var umamiUnavailableWarned = false;
  var clientProbeSent = false;

  function getMeta() {
    return window.BeachFinderMeta || { authenticated: 0 };
  }

  function getConfig() {
    return window.BF_CONFIG || {};
  }

  function isProdRuntime() {
    return getConfig().appEnv === "prod";
  }

  function postClientProbe(eventName, umamiAvailable) {
    if (clientProbeSent) return;
    clientProbeSent = true;

    var payload = {
      event_name: String(eventName || "unknown"),
      path: window.location.pathname,
      umami_available: !!umamiAvailable,
    };

    try {
      if (navigator.sendBeacon) {
        var blob = new Blob([JSON.stringify(payload)], { type: "application/json" });
        navigator.sendBeacon("/api/health/analytics.php?client_probe=1", blob);
        return;
      }
    } catch (e) {}

    try {
      fetch("/api/health/analytics.php?client_probe=1", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
        keepalive: true,
        credentials: "same-origin",
      }).catch(function () {});
    } catch (e) {}
  }

  function warnUmamiUnavailable(eventName) {
    if (!isProdRuntime() || umamiUnavailableWarned) return;
    umamiUnavailableWarned = true;
    postClientProbe(eventName, false);

    try {
      if (typeof console !== "undefined" && typeof console.warn === "function") {
        console.warn("[analytics] bfTrack called but window.umami is unavailable", {
          event_name: eventName || "",
          path: window.location.pathname,
        });
      }
    } catch (e) {}
  }

  function safeJsonParse(value, fallback) {
    try {
      return JSON.parse(value);
    } catch (e) {
      return fallback;
    }
  }

  function toBool01(value) {
    return value ? 1 : 0;
  }

  function setInteracted() {
    try {
      sessionStorage.setItem("bf_interacted", "1");
    } catch (e) {}
  }

  function hasInteracted() {
    try {
      return sessionStorage.getItem("bf_interacted") === "1";
    } catch (e) {
      return false;
    }
  }

  function uuidLike() {
    if (window.crypto && typeof window.crypto.randomUUID === "function") {
      return window.crypto.randomUUID();
    }
    // RFC4122-ish fallback (not perfect, but stable enough for anon id).
    const bytes = new Uint8Array(16);
    if (window.crypto && typeof window.crypto.getRandomValues === "function") {
      window.crypto.getRandomValues(bytes);
    } else {
      for (let i = 0; i < bytes.length; i++) bytes[i] = Math.floor(Math.random() * 256);
    }
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = Array.from(bytes).map((b) => b.toString(16).padStart(2, "0")).join("");
    return (
      hex.slice(0, 8) +
      "-" +
      hex.slice(8, 12) +
      "-" +
      hex.slice(12, 16) +
      "-" +
      hex.slice(16, 20) +
      "-" +
      hex.slice(20)
    );
  }

  function getCookie(name) {
    const cookies = document.cookie ? document.cookie.split(";") : [];
    for (let i = 0; i < cookies.length; i++) {
      const part = cookies[i].trim();
      if (part.startsWith(name + "=")) {
        return decodeURIComponent(part.slice(name.length + 1));
      }
    }
    return "";
  }

  function setCookie(name, value, maxAgeSeconds) {
    const secure = window.location.protocol === "https:" ? "; Secure" : "";
    document.cookie =
      name +
      "=" +
      encodeURIComponent(value) +
      "; Path=/" +
      "; Max-Age=" +
      String(maxAgeSeconds) +
      "; SameSite=Lax" +
      secure;
  }

  function ensureAnonId() {
    const existing = getCookie("BF_ANON_ID");
    if (existing) return existing;
    const next = uuidLike();
    // 180 days
    setCookie("BF_ANON_ID", next, 180 * 24 * 60 * 60);
    return next;
  }

  function baseProps(extra) {
    const meta = getMeta();
    const anonId = ensureAnonId();
    var locale = "en";
    try { locale = (document.documentElement.lang || "en").substring(0, 2); } catch (e) {}
    return Object.assign(
      {
        auth: toBool01(meta.authenticated === 1 || meta.authenticated === "1"),
        anon_id: anonId,
        locale: locale,
      },
      extra || {}
    );
  }

  /**
   * Public tracking API.
   * Uses Umami when present; otherwise no-op.
   */
  window.bfTrack = function bfTrack(eventName, props) {
    try {
      if (!eventName) return;
      setInteracted();
      const payload = baseProps(props);
      // Primary analytics: Google Analytics 4 (gtag). Loaded site-wide in the header
      // when GA_MEASUREMENT_ID is configured; guarded so it no-ops when absent.
      if (typeof window.gtag === "function") {
        window.gtag("event", eventName, payload);
      }
      if (window.umami && typeof window.umami.track === "function") {
        window.umami.track(eventName, payload);
      } else {
        warnUmamiUnavailable(eventName);
      }
      // Dual-send to PostHog for funnels, session replay, and cohort analysis
      if (window.posthog && typeof window.posthog.capture === "function") {
        window.posthog.capture(eventName, payload);
      }
    } catch (e) {
      // Never throw from analytics.
    }
  };

  function trackSignupAttribution() {
    const meta = getMeta();
    const authed = meta && (meta.authenticated === 1 || meta.authenticated === "1");
    if (!authed) return;

    const url = new URL(window.location.href);
    const src = (url.searchParams.get("src") || "").trim().toLowerCase();
    if (!src) return;

    if (src === "quiz") {
      window.bfTrack("S1_signup_from_quiz", { source: "quiz" });
    } else if (src === "checkin") {
      window.bfTrack("S2_signup_from_checkin", { source: "checkin" });
    } else {
      return;
    }

    url.searchParams.delete("src");
    window.history.replaceState({}, "", url.toString());
  }

  function beachPropsFromEl(el) {
    const container = el.closest("[data-bf-beach-id]") || el;
    const beachId = container.getAttribute("data-bf-beach-id") || "";
    const beachSlug = container.getAttribute("data-bf-beach-slug") || "";
    const municipality = container.getAttribute("data-bf-municipality") || "";
    const source = container.getAttribute("data-bf-source") || "";
    const props = {};
    if (beachId) props.beach_id = beachId;
    if (beachSlug) props.beach_slug = beachSlug;
    if (municipality) props.municipality = municipality;
    if (source) props.source = source;
    return props;
  }

  function referralPropsFromEl(el) {
    const container = el.closest("[data-bf-referral-campaign]") || el;
    const props = {};

    const provider = container.getAttribute("data-bf-referral-provider") || "";
    const campaign = container.getAttribute("data-bf-referral-campaign") || "";
    const placement = container.getAttribute("data-bf-referral-placement") || "";
    const pageType = container.getAttribute("data-bf-referral-page-type") || "";
    const pageSlug = container.getAttribute("data-bf-referral-page-slug") || "";
    const locale = container.getAttribute("data-bf-referral-locale") || "";
    const block = container.getAttribute("data-bf-referral-block") || "";

    if (provider) props.provider = provider;
    if (campaign) props.campaign = campaign;
    if (placement) props.placement = placement;
    if (pageType) props.page_type = pageType;
    if (pageSlug) props.page_slug = pageSlug;
    if (locale) props.locale = locale;
    if (block) props.block = block;
    return props;
  }

  function initDelegatedClickTracking() {
    document.addEventListener("click", function (event) {
      const target = event.target && event.target.closest ? event.target.closest("[data-bf-track]") : null;
      if (!target) return;

      const kind = (target.getAttribute("data-bf-track") || "").trim();
      const props = beachPropsFromEl(target);

      if (kind === "directions") {
        window.bfTrack("A3_directions_click", props);
        return;
      }

      if (kind === "share") {
        window.bfTrack("share_click", props);
        return;
      }

      if (kind === "details") {
        window.bfTrack("A1_list_to_detail_click", props);
        return;
      }

      if (kind === "referral-click") {
        const referralProps = referralPropsFromEl(target);
        window.bfTrack("R2_referral_click", Object.assign({}, props, referralProps));
        return;
      }
    });
  }

  function initReferralImpressionTracking() {
    if (typeof window.IntersectionObserver !== "function") return;

    const seen = new WeakSet();
    const observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          const el = entry.target;
          if (seen.has(el)) return;
          seen.add(el);
          observer.unobserve(el);

          const props = referralPropsFromEl(el);
          window.bfTrack("R1_referral_impression", props);
        });
      },
      { threshold: 0.25 }
    );

    function observeCurrentNodes() {
      const nodes = document.querySelectorAll('[data-bf-track="referral-impression"]');
      nodes.forEach(function (node) {
        if (seen.has(node)) return;
        observer.observe(node);
      });
    }

    observeCurrentNodes();
    document.body.addEventListener("htmx:afterSwap", observeCurrentNodes);
  }

  function initHtmxDrawerTracking() {
    document.body.addEventListener("htmx:afterSwap", function (event) {
      const target = event.detail && event.detail.target;
      if (!target || target.id !== "drawer-content-inner") return;

      // beach drawer markup includes these attributes after our patch.
      const beachEl = target.querySelector("[data-bf-beach-id]");
      const props = beachEl ? beachPropsFromEl(beachEl) : {};
      window.bfTrack("A1_list_to_detail_click", Object.assign({ source: props.source || "drawer" }, props));
    });
  }

  function initFavoriteTrackingFromHtmx() {
    document.body.addEventListener("htmx:afterRequest", function (event) {
      const path = event.detail && event.detail.pathInfo && event.detail.pathInfo.requestPath;
      if (!path || String(path).indexOf("toggle-favorite") === -1) return;
      const resp = event.detail && event.detail.xhr && event.detail.xhr.response;
      if (typeof resp !== "string") return;

      // Heuristic: the response HTML includes either ❤️ or 🤍.
      if (resp.indexOf("❤️") !== -1) {
        window.bfTrack("favorite_add", { source: "htmx" });
      } else if (resp.indexOf("🤍") !== -1) {
        window.bfTrack("favorite_remove", { source: "htmx" });
      }
    });
  }

  function initSendListForms() {
    document.addEventListener("submit", async function (event) {
      const form = event.target;
      if (!form || !form.matches || !form.matches('form[data-bf-form="send-list"]')) return;

      event.preventDefault();

      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) submitBtn.disabled = true;

      try {
        const res = await fetch("/api/send-list.php", {
          method: "POST",
          body: new FormData(form),
        });
        const payload = await res.json();
        if (!res.ok || !payload.success) {
          throw new Error(payload.error || "Unable to send list.");
        }

        if (typeof window.showToast === "function") {
          window.showToast((window.BF_STRINGS || {}).email_subscribed || "Sent! Check your inbox for the list.", "success", 3500);
        }

        const contextType = form.querySelector('input[name="context_type"]')?.value || "";
        const contextKey = form.querySelector('input[name="context_key"]')?.value || "";
        window.bfTrack("L2_list_sent", { context_type: contextType, context_key: contextKey });
        form.reset();
      } catch (e) {
        if (typeof window.showToast === "function") {
          window.showToast((window.BF_STRINGS || {}).email_error || "Could not send the list. Please try again.", "error", 4000);
        }
      } finally {
        if (submitBtn) submitBtn.disabled = false;
      }
    });
  }

  function initWelcomePopupSuppression() {
    // If user already interacted (or analytics fired), suppress timed welcome popup.
    if (!hasInteracted()) return;
    try {
      localStorage.setItem("welcome_popup_dismissed", String(Date.now()));
    } catch (e) {}
  }

  function initSyntheticProbe() {
    var url;
    try {
      url = new URL(window.location.href);
    } catch (e) {
      return;
    }

    if (url.searchParams.get("bf_analytics_probe") !== "1") return;

    window.setTimeout(function () {
      var umamiAvailable = !!(window.umami && typeof window.umami.track === "function");
      postClientProbe("health_analytics_probe", umamiAvailable);
      window.bfTrack("health_analytics_probe", {
        source: "synthetic_probe",
        path: window.location.pathname,
      });
    }, 1200);
  }

  /* ===== Guide Scroll Depth Tracking ===== */
  function initGuideScrollDepth() {
    // Only run on guide pages
    var guideContent = document.querySelector(".guide-content, article.guide");
    if (!guideContent) return;
    // Also check URL
    var path = window.location.pathname;
    if (path.indexOf("/guides/") === -1 && path.indexOf("/es/guias/") === -1) return;

    var milestones = [25, 50, 75, 100];
    var fired = {};

    function getScrollPercent() {
      var docHeight = document.documentElement.scrollHeight - window.innerHeight;
      if (docHeight <= 0) return 100;
      return Math.round((window.scrollY / docHeight) * 100);
    }

    var guideSlug = path.split("/").pop() || "unknown";

    function checkMilestones() {
      var pct = getScrollPercent();
      for (var i = 0; i < milestones.length; i++) {
        var m = milestones[i];
        if (pct >= m && !fired[m]) {
          fired[m] = true;
          window.bfTrack("guide_scroll_depth", { depth: m, guide_slug: guideSlug });
        }
      }
    }

    var scrollTimer = null;
    window.addEventListener("scroll", function () {
      if (scrollTimer) return;
      scrollTimer = setTimeout(function () {
        scrollTimer = null;
        checkMilestones();
      }, 300);
    }, { passive: true });
  }

  /* ===== Guide CTA Click Tracking ===== */
  function initGuideCTATracking() {
    var path = window.location.pathname;
    if (path.indexOf("/guides/") === -1 && path.indexOf("/es/guias/") === -1) return;
    var guideSlug = path.split("/").pop() || "unknown";

    document.addEventListener("click", function (event) {
      var link = event.target.closest ? event.target.closest("a[href]") : null;
      if (!link) return;
      var href = link.getAttribute("href") || "";
      // Track clicks to beach pages from within guide content
      if (href.indexOf("/beach/") !== -1 || href.indexOf("/playa/") !== -1) {
        var beachSlug = href.split("/").pop() || "";
        window.bfTrack("guide_cta_click", { guide_slug: guideSlug, beach_slug: beachSlug, href: href });
      }
    });
  }

  /* ===== Outbound Link Click Tracking ===== */
  function initOutboundLinkTracking() {
    document.addEventListener("click", function (event) {
      var link = event.target.closest ? event.target.closest("a[href]") : null;
      if (!link) return;
      var href = link.getAttribute("href") || "";
      // Only track external links (not same origin, not anchors, not mailto/tel)
      if (!href || href.charAt(0) === "#" || href.charAt(0) === "/" || href.indexOf("mailto:") === 0 || href.indexOf("tel:") === 0) return;
      try {
        var url = new URL(href, window.location.origin);
        if (url.hostname === window.location.hostname) return;
        // Skip Google Maps directions (already tracked as A3)
        if (url.hostname.indexOf("google.com") !== -1 && href.indexOf("/maps/dir") !== -1) return;
        window.bfTrack("outbound_click", { url: url.hostname + url.pathname, domain: url.hostname });
      } catch (e) {}
    });
  }

  /* ===== Drawer Engagement Tracking ===== */
  function initDrawerEngagementTracking() {
    var drawerOpenTime = 0;
    var drawerBeachSlug = "";

    // Track when drawer opens (time starts)
    document.body.addEventListener("htmx:afterSwap", function (event) {
      var target = event.detail && event.detail.target;
      if (!target || target.id !== "drawer-content-inner") return;
      drawerOpenTime = Date.now();
      var beachEl = target.querySelector("[data-bf-beach-slug]");
      drawerBeachSlug = beachEl ? (beachEl.getAttribute("data-bf-beach-slug") || "") : "";
    });

    // Track gallery interaction in drawer
    document.addEventListener("click", function (event) {
      var target = event.target.closest ? event.target : null;
      if (!target) return;
      var drawer = target.closest("#beach-drawer, #drawer-content-inner");
      if (!drawer) return;
      // Gallery prev/next or thumbnail clicks
      var galleryBtn = target.closest("[data-gallery-action], .gallery-nav, .gallery-thumb, .swiper-button-next, .swiper-button-prev, [data-slide]");
      if (galleryBtn) {
        window.bfTrack("drawer_gallery_interact", { beach_slug: drawerBeachSlug });
      }
    });

    // Track drawer close and time spent
    var drawerEl = document.getElementById("beach-drawer");
    if (drawerEl) {
      var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
          if (mutation.attributeName !== "class") return;
          var wasOpen = mutation.oldValue && mutation.oldValue.indexOf("open") !== -1;
          var isOpen = drawerEl.classList.contains("open");
          if (wasOpen && !isOpen && drawerOpenTime > 0) {
            var duration = Math.round((Date.now() - drawerOpenTime) / 1000);
            window.bfTrack("drawer_close", { beach_slug: drawerBeachSlug, time_spent_seconds: duration });
            drawerOpenTime = 0;
          }
        });
      });
      observer.observe(drawerEl, { attributes: true, attributeOldValue: true });
    }
  }

  /* ===== Geolocation Usage Tracking ===== */
  function initGeolocationTracking() {
    document.addEventListener("click", function (event) {
      var btn = event.target.closest ? event.target.closest("#location-btn, #mobile-nearme-btn, #mobile-location-btn") : null;
      if (!btn) return;
      window.bfTrack("geolocation_requested", { source: btn.id });
    });
  }

  /* ===== Compare Page View Tracking ===== */
  function initCompareTracking() {
    if (window.location.pathname.indexOf("/compare") === -1) return;
    var params = new URLSearchParams(window.location.search);
    var beaches = params.get("beaches") || "";
    if (!beaches) return;
    var slugs = beaches.split(",").filter(Boolean);
    window.bfTrack("compare_page_view", { beach_count: slugs.length, beaches: slugs.join(",") });
  }

  /* ===== PostHog Page Context ===== */
  function initPostHogPageContext() {
    if (!window.posthog || typeof window.posthog.register === "function" === false) return;

    var path = window.location.pathname;
    var pageType = "other";
    var pageProps = {};

    if (path === "/" || path === "/es") {
      pageType = "homepage";
    } else if (path.indexOf("/beach/") === 0 || path.indexOf("/playa/") === 0) {
      pageType = "beach_detail";
      pageProps.beach_slug = path.split("/").pop() || "";
    } else if (path.indexOf("/beaches-in-") === 0 || path.indexOf("/playas-en-") === 0) {
      pageType = "municipality";
      pageProps.municipality = path.replace(/^\/(beaches-in-|playas-en-)/, "");
    } else if (path.indexOf("/beaches-near-") === 0 || path.indexOf("/playas-cerca-") === 0) {
      pageType = "proximity";
    } else if (path.indexOf("/beaches/") === 0 || path.indexOf("/playas/") === 0) {
      pageType = "tag_landing";
      pageProps.tag = path.split("/").pop() || "";
    } else if (path.indexOf("/best-") === 0 || path.indexOf("/mejores-") === 0) {
      pageType = "collection";
    } else if (path.indexOf("/guides/") === 0 || path.indexOf("/es/guias/") === 0) {
      pageType = "guide";
      pageProps.guide_slug = path.split("/").pop() || "";
    } else if (path.indexOf("/quiz") === 0) {
      pageType = "quiz";
    } else if (path.indexOf("/compare") === 0) {
      pageType = "compare";
    } else if (path.indexOf("/favorites") === 0) {
      pageType = "favorites";
    } else if (path.indexOf("/profile") === 0) {
      pageType = "profile";
    }

    pageProps.page_type = pageType;

    // Register as super properties so they attach to all subsequent events
    window.posthog.register(pageProps);

    // Set first-seen page type as a person property
    if (pageType === "beach_detail" && pageProps.beach_slug) {
      window.posthog.setPersonPropertiesForFlags({ last_beach_viewed: pageProps.beach_slug });
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    ensureAnonId();
    initPostHogPageContext();
    trackSignupAttribution();
    initDelegatedClickTracking();
    initReferralImpressionTracking();
    initHtmxDrawerTracking();
    initFavoriteTrackingFromHtmx();
    initSendListForms();
    initWelcomePopupSuppression();
    initSyntheticProbe();
    initGuideScrollDepth();
    initGuideCTATracking();
    initOutboundLinkTracking();
    initDrawerEngagementTracking();
    initGeolocationTracking();
    initCompareTracking();
  });
})();
