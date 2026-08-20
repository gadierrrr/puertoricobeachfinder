/* Redesign v2 homepage — beach directory + island coast filter.
   Data: window.RD_BEACHES; config/i18n: window.RD_CFG (set by the template).
   tile() here MUST mirror $rdTile in templates/redesign/home.php — the first
   page of tiles is server-rendered for crawlability, JS re-renders on state
   changes. */
(function () {
  "use strict";
  var B = window.RD_BEACHES || [];
  var CFG = window.RD_CFG || {};
  var I = CFG.i18n || {};
  var grid = document.getElementById("rdGrid");
  if (!grid) return;
  var RN = CFG.regionNames || { north: "North Coast", metro: "Metro · San Juan", west: "Porta del Sol", south: "South Coast", east: "East · Fajardo", cays: "The Cays" };
  var CLUSTER_POS = {
    north: { x: 30, y: 16 },
    metro: { x: 70, y: 16 },
    west: { x: 18, y: 42 },
    east: { x: 72, y: 43 },
    south: { x: 31, y: 76 },
    cays: { x: 72, y: 76 }
  };
  var CLUSTER_POS_MOBILE = {
    north: { x: 30, y: 16 },
    metro: { x: 58, y: 16 },
    west: { x: 22, y: 43 },
    east: { x: 58, y: 43 },
    south: { x: 32, y: 76 },
    cays: { x: 58, y: 76 }
  };
  var URL_PREFIX = CFG.urlPrefix || "/beach/";
  var favs = (CFG.favs || []).slice();
  var PAGE_SIZE = 12;
  var FIRST_PAGE = 9;
  var st = { region: null, q: "", sort: "rating", shown: FIRST_PAGE, tags: [], near: null, mapActive: null };
  var esc = function (s) { return String(s == null ? "" : s).replace(/[&<>"]/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]; }); };
  var barVal = function (b, label) { for (var i = 0; i < b.bars.length; i++) { if (b.bars[i][0] === label) return b.bars[i][1]; } return 0; };
  var word = function (key, fallback) { return I[key] || fallback; };

  function list() {
    var a = B.filter(function (b) {
      if (st.region && b.rg !== st.region) return false;
      if (st.q && (b.n + " " + b.m).toLowerCase().indexOf(st.q) === -1) return false;
      if (st.tags.length) {
        var t = b.t || [];
        for (var i = 0; i < st.tags.length; i++) { if (t.indexOf(st.tags[i]) === -1) return false; }
      }
      return true;
    });
    var s = st.sort;
    a.sort(function (x, y) {
      if (st.near) return distanceMiles(x, st.near) - distanceMiles(y, st.near);
      if (s === "rating") return y.rt - x.rt;
      if (s === "calm") return barVal(y, "Calm water") - barVal(x, "Calm water");
      if (s === "crowd") return barVal(y, "Seclusion") - barVal(x, "Seclusion");
      return y.sc - x.sc;
    });
    return a;
  }

  function bar(l, v, c) { return '<div class="score"><span>' + l + '</span><div class="bar"><i class="' + c + '" style="width:' + v + '%"></i></div></div>'; }

  function tile(b, rank) {
    var url = URL_PREFIX + b.slug;
    // Honest signals only: real Google rating; the heuristic score stays
    // ranking-only. MUST mirror $rdTile() in templates/redesign/home.php.
    var waterWord = (I.water && I.water[b.water]) || b.water;
    var crowdWord = (I.crowd && I.crowd[b.crowd]) || b.crowd;
    var isFav = favs.indexOf(String(b.id)) > -1;
    var rt = b.rt ? b.rt.toFixed(1) : "";
    return '<div class="btile">' +
      '<a class="btile-link" href="' + url + '">' +
      '<div class="btile-photo" style="background-image:url(\'' + esc(b.img) + '\')"></div><div class="btile-grad"></div>' +
      '<div class="btile-rest"><div class="bt-top"><span class="bt-rank">' + rank + '</span>' +
      (rt ? '<span class="bt-score-mini">⭐ ' + rt + '</span>' : '') +
      '</div>' +
      '<div class="bt-name">' + esc(b.n) + '</div><div class="bt-muni">' + esc(b.m) + '</div>' +
      '<div class="bt-rest-stats"><span>🌊 ' + esc(waterWord) + '</span><span>👥 ' + esc(crowdWord) + '</span></div></div>' +
      '<div class="btile-hover"><div class="bt-hovtop"><span>' + (rt ? '⭐ ' + rt + ' Google' : esc(b.m)) + '</span></div>' +
      '<span class="bt-view">' + esc(word("viewBeach", "View beach →")) + '</span></div></a>' +
      '<button class="bt-fav' + (isFav ? " on" : "") + '" type="button" data-id="' + esc(b.id) + '" title="' + esc(word("save", "Save")) + '">' + (isFav ? "♥" : "♡") + '</button></div>';
  }

  function toggleFavorite(btn) {
    if (!CFG.authed) {
      if (typeof window.showSignupPrompt === "function") { window.showSignupPrompt("favorites"); }
      else { window.location.assign("/login"); }
      return;
    }
    var id = btn.getAttribute("data-id");
    var body = new URLSearchParams({ beach_id: id, csrf_token: CFG.csrf || "" });
    fetch("/api/toggle-favorite.php?format=json", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      credentials: "same-origin",
      body: body.toString()
    }).then(function (r) { return r.ok ? r.json() : null; }).then(function (data) {
      if (!data || data.success === false) return;
      var idx = favs.indexOf(String(id));
      var on = idx === -1;
      if (on) { favs.push(String(id)); } else { favs.splice(idx, 1); }
      document.querySelectorAll('.bt-fav[data-id="' + id + '"]').forEach(function (f) {
        f.classList.toggle("on", on); f.textContent = on ? "♥" : "♡";
      });
      if (on && typeof window.bfCelebrateBadges === "function") {
        window.bfCelebrateBadges(data.newly_earned_badges);
      }
      if (typeof window.showToast === "function") {
        window.showToast(on ? word("addedFavorite", "Added to favorites!") : word("removedFavorite", "Removed from favorites"), on ? "success" : "info", 2200);
      }
    }).catch(function () {});
  }

  // Project lat/lng through the SAME transform the island SVG uses
  // (inc/island_chart.php constants, viewBox 560x360), then account for the
  // xMidYMid-meet letterboxing of the SVG inside the canvas element.
  var CHART = { lonMin: -67.271350, latMax: 18.515757, kx: 0.949895, scale: 220.8437, x0: 70.0, y0: 131.3791, vw: 560, vh: 360 };
  var chartBox = null;
  function measureChart() {
    var el = document.querySelector(".mapcanvas");
    if (!el) { chartBox = null; return; }
    var cw = el.clientWidth, ch = el.clientHeight;
    if (!cw || !ch) { chartBox = null; return; }
    var sc = Math.min(cw / CHART.vw, ch / CHART.vh);
    chartBox = { cw: cw, ch: ch, sc: sc, ox: (cw - CHART.vw * sc) / 2, oy: (ch - CHART.vh * sc) / 2 };
  }
  function mapPoint(b) {
    var lat = Number(b.lat), lng = Number(b.lng);
    if (!lat || !lng || !chartBox) return null;
    var vx = CHART.x0 + (lng - CHART.lonMin) * CHART.scale * CHART.kx;
    var vy = CHART.y0 + (CHART.latMax - lat) * CHART.scale;
    var x = (chartBox.ox + vx * chartBox.sc) / chartBox.cw * 100;
    var y = (chartBox.oy + vy * chartBox.sc) / chartBox.ch * 100;
    if (x < 0 || x > 100 || y < 0 || y > 100) return null;
    return { x: Math.max(1.5, Math.min(98.5, x)), y: Math.max(3, Math.min(97, y)) };
  }

  function renderMap(a) {
    var pins = document.getElementById("rdMapPins");
    var listEl = document.getElementById("rdMapList");
    if (!pins || !listEl) return;
    measureChart();
    var shown = a.filter(mapPoint);
    var clusterMode = !st.region && !st.q && !st.near && shown.length > 80;
    if (clusterMode) {
      pins.innerHTML = renderMapClusters(shown);
      set("rdMapModeNote", word("mapClusterHint", "Showing coast groups. Pick a coast or search a beach to reveal individual points."));
    } else {
      pins.innerHTML = shown.slice(0, 64).map(function (b) {
        var p = mapPoint(b);
        var active = String(st.mapActive || "") === String(b.id);
        return '<button type="button" class="map-pin' + (active ? " is-active" : "") + '" style="left:' + p.x.toFixed(2) + '%;top:' + p.y.toFixed(2) + '%" data-id="' + esc(b.id) + '" aria-label="' + esc(b.n + ", " + b.m) + '"></button>';
      }).join("");
      set("rdMapModeNote", word("mapPinHint", "Showing individual beaches on the map."));
    }
    listEl.innerHTML = a.slice(0, 12).map(function (b, i) {
      var active = String(st.mapActive || "") === String(b.id);
      return '<a class="map-result' + (active ? " is-active" : "") + '" href="' + URL_PREFIX + esc(b.slug) + '" data-id="' + esc(b.id) + '">' +
        '<span class="map-result-rank">' + (i + 1) + '</span>' +
        '<span class="map-result-copy"><b>' + esc(b.n) + '</b><small>' + esc(b.m) + (b.rt ? " · ★ " + b.rt.toFixed(1) : "") + '</small></span></a>';
    }).join("") || '<div class="map-empty">' + esc(word("noMatch", "No beaches match — try another coast or search.")) + '</div>';
    set("rdMapCount", a.length.toLocaleString() + " " + (a.length === 1 ? word("beach", "beach") : word("beaches", "beaches")));
    set("rdMapSummary", st.region ? RN[st.region] : word("wholeIsland", "The whole island"));
  }

  function renderMapClusters(beaches) {
    var groups = {};
    beaches.forEach(function (b) {
      var rg = b.rg || "unknown";
      var p = mapPoint(b);
      if (!p || rg === "unknown") return;
      if (!groups[rg]) groups[rg] = { count: 0, x: 0, y: 0, best: 0 };
      groups[rg].count += 1;
      groups[rg].x += p.x;
      groups[rg].y += p.y;
      groups[rg].best = Math.max(groups[rg].best, Number(b.sc) || 0);
    });
    return Object.keys(groups).map(function (rg) {
      var g = groups[rg];
      var isMobileMap = window.matchMedia && window.matchMedia("(max-width: 640px)").matches;
      var pos = (isMobileMap ? CLUSTER_POS_MOBILE[rg] : CLUSTER_POS[rg]) || { x: g.x / g.count, y: g.y / g.count };
      return '<button type="button" class="map-cluster" style="left:' + pos.x.toFixed(2) + '%;top:' + pos.y.toFixed(2) + '%" data-region="' + esc(rg) + '" aria-label="' + esc((RN[rg] || rg) + ", " + g.count + " " + word("beaches", "beaches")) + '">' +
        '<b>' + g.count + '</b><span>' + esc(RN[rg] || rg) + '</span></button>';
    }).join("");
  }

  function setMapActive(id) {
    st.mapActive = id;
    document.querySelectorAll(".map-pin,.map-result").forEach(function (el) {
      el.classList.toggle("is-active", String(el.getAttribute("data-id")) === String(id));
    });
    var card = document.querySelector('.map-result[data-id="' + id + '"]');
    if (card) card.scrollIntoView({ block: "nearest" });
  }

  function draw() {
    var a = list();
    grid.innerHTML = a.slice(0, st.shown).map(function (b, i) { return tile(b, i + 1); }).join("") ||
      '<div style="grid-column:1/-1;padding:34px;color:var(--ink-60);font-family:var(--data)">' + esc(word("noMatch", "No beaches match — try another coast or search.")) + '</div>';
    renderMap(a);
    var rem = Math.max(0, a.length - st.shown);
    var beachesWord = a.length === 1 ? word("beach", "beach") : word("beaches", "beaches");
    set("rdDirCount", a.length + " " + beachesWord); set("rdCount", a.length.toLocaleString());
    set("rdRemain", rem);
    var more = document.getElementById("rdMore");
    if (more) {
      more.textContent = word("showMore", "Show 12 more");
      more.parentElement.style.display = rem ? "" : "none";
    }
    var note = document.getElementById("rdMoreNote");
    if (note) {
      var tpl = word("showRemaining", "%s of %s beaches left");
      note.textContent = tpl.replace("%s", rem.toLocaleString()).replace("%s", a.length.toLocaleString());
    }
  }
  function set(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; }
  function distanceMiles(b, p) {
    if (!b.lat || !b.lng || !p) return 9999;
    var R = 3958.8, toRad = Math.PI / 180;
    var dLat = (b.lat - p.lat) * toRad, dLng = (b.lng - p.lng) * toRad;
    var lat1 = p.lat * toRad, lat2 = b.lat * toRad;
    var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }
  function jumpToDirectory(focusSearch) {
    var d = document.getElementById("beaches");
    if (d) d.scrollIntoView({ behavior: "smooth", block: "start" });
    if (focusSearch && search) setTimeout(function () { search.focus({ preventScroll: true }); }, 350);
  }

  // one delegated listener so server-rendered AND js-rendered hearts both work
  grid.addEventListener("click", function (e) {
    var f = e.target.closest(".bt-fav");
    if (!f) return;
    e.preventDefault(); e.stopPropagation();
    toggleFavorite(f);
  });

  // controls
  var search = document.getElementById("rdSearch"), hero = document.getElementById("heroSearch"), mapSearch = document.getElementById("rdMapSearch");
  var heroSearchScrolled = false;
  function onSearch(v) { st.q = v.toLowerCase().trim(); st.near = null; st.shown = FIRST_PAGE; draw(); }
  if (search) search.addEventListener("input", function (e) { onSearch(e.target.value); });
  if (mapSearch) mapSearch.addEventListener("input", function (e) {
    if (search) search.value = e.target.value;
    if (hero) hero.value = e.target.value;
    onSearch(e.target.value);
  });
  if (hero) hero.addEventListener("input", function (e) {
    if (search) search.value = e.target.value;
    if (mapSearch) mapSearch.value = e.target.value;
    onSearch(e.target.value);
    if (!heroSearchScrolled && e.target.value.trim().length >= 2) {
      heroSearchScrolled = true;
      var d = document.getElementById("beaches"); if (d) d.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  });
  var heroForm = document.querySelector(".rd-home .hero-search");
  if (heroForm) heroForm.addEventListener("submit", function (e) {
    e.preventDefault();
    if (hero) onSearch(hero.value);
    jumpToDirectory(true);
  });
  document.querySelectorAll(".rd-home .suggestions button").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var q = btn.getAttribute("data-query") || "";
      var tag = btn.getAttribute("data-tag") || "";
      var sortValue = btn.getAttribute("data-sort") || "";
      st.near = null;
      if (q) {
        if (hero) hero.value = q;
        if (search) search.value = q;
        if (mapSearch) mapSearch.value = q;
        st.q = q.toLowerCase().trim();
      } else {
        if (hero) hero.value = "";
        if (search) search.value = "";
        if (mapSearch) mapSearch.value = "";
        st.q = "";
      }
      if (tag && st.tags.indexOf(tag) === -1) st.tags.push(tag);
      if (sortValue) {
        st.sort = sortValue;
        var sort = document.getElementById("rdSort");
        if (sort) sort.value = sortValue;
        var mapSort = document.getElementById("rdMapSort");
        if (mapSort) mapSort.value = sortValue;
      }
      document.querySelectorAll(".rd-home .chip[data-tag]").forEach(function (chip) {
        chip.setAttribute("aria-pressed", st.tags.indexOf(chip.getAttribute("data-tag")) > -1 ? "true" : "false");
      });
      st.shown = FIRST_PAGE;
      draw();
      jumpToDirectory(false);
    });
  });
  var nearMe = document.getElementById("heroNearMe");
  function setNearMeLabel(label) {
    if (!nearMe) return;
    var clean = String(label || "").replace(/^⌖\s*/, "");
    var text = nearMe.querySelector(".near-text");
    var icon = nearMe.querySelector(".near-icon");
    if (icon) icon.textContent = "⌖";
    if (text) text.textContent = clean;
    else nearMe.textContent = label;
    nearMe.setAttribute("aria-label", clean);
    nearMe.setAttribute("title", clean);
  }
  if (nearMe) nearMe.addEventListener("click", function () {
    if (!navigator.geolocation) return;
    nearMe.disabled = true;
    setNearMeLabel(word("locating", "⌖ Locating…"));
    navigator.geolocation.getCurrentPosition(function (pos) {
      st.near = { lat: pos.coords.latitude, lng: pos.coords.longitude };
      st.region = null; st.q = ""; st.tags = []; st.shown = FIRST_PAGE;
      if (hero) hero.value = "";
      if (search) search.value = "";
      if (mapSearch) mapSearch.value = "";
      document.querySelectorAll(".region").forEach(function (b) { b.setAttribute("aria-pressed", "false"); });
      document.querySelectorAll(".rd-home .chip[data-tag]").forEach(function (c) { c.setAttribute("aria-pressed", "false"); });
      set("rdDirTitle", word("closestBeaches", "Closest beaches"));
      set("rdDirSub", word("sortedByLocation", "Sorted by your location"));
      draw();
      nearMe.disabled = false;
      setNearMeLabel(word("nearMe", "⌖ Near me"));
      jumpToDirectory(false);
    }, function () {
      nearMe.disabled = false;
      setNearMeLabel(word("useMyLocation", "⌖ Use my location"));
    }, { enableHighAccuracy: false, timeout: 8000, maximumAge: 300000 });
  });
  var jump = document.querySelector(".rd-home .hero-jump");
  if (jump) jump.addEventListener("click", function () {
    setTimeout(function () { if (search) search.focus({ preventScroll: true }); }, 350);
  });
  function onSort(value) {
    st.near = null; st.sort = value;
    var sort = document.getElementById("rdSort");
    var mapSort = document.getElementById("rdMapSort");
    if (sort) sort.value = value;
    if (mapSort) mapSort.value = value;
    draw();
  }
  var sort = document.getElementById("rdSort"); if (sort) sort.addEventListener("change", function (e) { onSort(e.target.value); });
  var mapResizeTimer = null;
  window.addEventListener("resize", function () {
    if (!document.getElementById("rdMapPins")) return;
    clearTimeout(mapResizeTimer);
    mapResizeTimer = setTimeout(function () { renderMap(list()); }, 250);
  });
  var rdMapSort = document.getElementById("rdMapSort"); if (rdMapSort) rdMapSort.addEventListener("change", function (e) { onSort(e.target.value); });
  var more = document.getElementById("rdMore"); if (more) more.addEventListener("click", function () { st.shown += PAGE_SIZE; draw(); });

  // island coast filter
  var marker = document.getElementById("rdMarker");
  function setRegion(rg, pt, opts) {
    opts = opts || {};
    st.region = st.region === rg ? null : rg; st.near = null; st.shown = FIRST_PAGE;
    document.querySelectorAll(".region").forEach(function (b) { b.setAttribute("aria-pressed", b.dataset.region === st.region ? "true" : "false"); });
    document.querySelectorAll(".map-region").forEach(function (b) { b.classList.toggle("is-on", (b.getAttribute("data-map-region") || "") === (st.region || "")); });
    if (st.region && pt && marker) {
      document.getElementById("rdRing").setAttribute("cx", pt[0]); document.getElementById("rdRing").setAttribute("cy", pt[1]);
      document.getElementById("rdPin").setAttribute("cx", pt[0]); document.getElementById("rdPin").setAttribute("cy", pt[1]);
      marker.classList.add("on");
    } else if (marker) { marker.classList.remove("on"); }
    set("rdDirTitle", st.region ? RN[st.region] : word("findYourBeach", "Find your beach"));
    set("rdDirSub", st.region ? word("byCoast", "Filtered by coast") : word("wholeIsland", "The whole island"));
    var scope = document.getElementById("rdScope"); if (scope) scope.textContent = st.region ? "· " + RN[st.region] : word("manyMunicipios", "· many municipalities");
    draw();
    if (st.region && !opts.stayOnMap) { var d = document.getElementById("beaches"); if (d) d.scrollIntoView({ behavior: "smooth", block: "start" }); }
  }
  document.querySelectorAll(".region").forEach(function (b) {
    b.addEventListener("click", function () {
      var pt = (b.dataset.pt || "").split(",").map(Number);
      setRegion(b.dataset.region, pt);
    });
  });
  document.querySelectorAll(".map-region").forEach(function (b) {
    b.addEventListener("click", function () {
      var rg = b.getAttribute("data-map-region") || null;
      if (!rg) {
        st.region = null; st.near = null; st.shown = FIRST_PAGE;
        document.querySelectorAll(".region").forEach(function (r) { r.setAttribute("aria-pressed", "false"); });
        document.querySelectorAll(".map-region").forEach(function (r) { r.classList.toggle("is-on", !r.getAttribute("data-map-region")); });
        set("rdDirTitle", word("findYourBeach", "Find your beach"));
        set("rdDirSub", word("wholeIsland", "The whole island"));
        draw();
        return;
      }
      setRegion(rg, null, { stayOnMap: true });
    });
  });
  var mapPins = document.getElementById("rdMapPins");
  if (mapPins) mapPins.addEventListener("click", function (e) {
    var cluster = e.target.closest(".map-cluster");
    if (cluster) {
      setRegion(cluster.getAttribute("data-region"), null, { stayOnMap: true });
      return;
    }
    var pin = e.target.closest(".map-pin");
    if (!pin) return;
    setMapActive(pin.getAttribute("data-id"));
  });
  var mapResults = document.getElementById("rdMapList");
  if (mapResults) mapResults.addEventListener("mouseover", function (e) {
    var item = e.target.closest(".map-result");
    if (item) setMapActive(item.getAttribute("data-id"));
  });

  // hero condition chips — real tag filters over RD_BEACHES
  document.querySelectorAll(".rd-home .chip[data-tag]").forEach(function (c) {
    c.addEventListener("click", function () {
      var tag = c.getAttribute("data-tag");
      var idx = st.tags.indexOf(tag);
      if (idx === -1) { st.tags.push(tag); } else { st.tags.splice(idx, 1); }
      st.near = null;
      c.setAttribute("aria-pressed", idx === -1 ? "true" : "false");
      st.shown = FIRST_PAGE;
      draw();
      var d = document.getElementById("beaches"); if (d && idx === -1) d.scrollIntoView({ behavior: "smooth", block: "start" });
    });
  });

  function syncChatFab() {
    var heroBand = document.querySelector(".rd-home .hero-band");
    if (!heroBand || !window.matchMedia("(max-width: 640px)").matches) {
      document.body.classList.remove("rd-chat-muted");
      return;
    }
    document.body.classList.toggle("rd-chat-muted", window.scrollY < heroBand.offsetHeight - 80);
  }
  syncChatFab();
  window.addEventListener("scroll", syncChatFab, { passive: true });
  window.addEventListener("resize", syncChatFab);
  draw();
})();
