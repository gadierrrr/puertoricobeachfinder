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
  var URL_PREFIX = CFG.urlPrefix || "/beach/";
  var favs = (CFG.favs || []).slice();
  var st = { region: null, q: "", sort: "score", shown: 9, tags: [] };
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
    var barsHtml = bar(word("overall", "⭐ Overall"), b.sc, b.sc >= 67 ? "g" : b.sc >= 40 ? "a" : "r");
    var barLabels = I.barLabels || { "Calm water": "🌊 Calm", "Snorkeling": "🤿 Snorkel", "Seclusion": "🌾 Quiet", "Family": "👨‍👩‍👧 Family", "Facilities": "🚻 Facilities" };
    for (var i = 0; i < b.bars.length; i++) { barsHtml += bar(barLabels[b.bars[i][0]] || b.bars[i][0], b.bars[i][1], b.bars[i][2]); }
    var waterWord = (I.water && I.water[b.water]) || b.water;
    var crowdWord = (I.crowd && I.crowd[b.crowd]) || b.crowd;
    var isFav = favs.indexOf(String(b.id)) > -1;
    return '<a class="btile" href="' + url + '">' +
      '<div class="btile-photo" style="background-image:url(\'' + esc(b.img) + '\')"></div><div class="btile-grad"></div>' +
      '<div class="btile-rest"><div class="bt-top"><span class="bt-rank">' + rank + '</span>' +
      '<span class="bt-water">🌊 ' + esc(waterWord) + '</span></div>' +
      '<div class="bt-name">' + esc(b.n) + '</div><div class="bt-muni">' + esc(b.m) + '</div>' +
      '<div class="bt-rest-stats"><span>👥 ' + esc(crowdWord) + '</span><span>🏄 ' + esc(b.surf) + '</span>' + (b.rt ? '<span>⭐ ' + b.rt.toFixed(1) + '</span>' : '') + '</div></div>' +
      '<div class="btile-hover"><div class="bt-hovtop"><button class="bt-fav' + (isFav ? " on" : "") + '" type="button" data-id="' + esc(b.id) + '" title="' + esc(word("save", "Save")) + '">' + (isFav ? "♥" : "♡") + '</button><span>Beach Score ' + b.sc + '</span></div>' +
      '<div class="scores">' + barsHtml + '</div>' +
      '<span class="bt-view" style="margin-top:8px;text-align:center;display:block">' + esc(word("viewBeach", "View beach →")) + '</span></div></a>';
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
    }).catch(function () {});
  }

  function draw() {
    var a = list();
    grid.innerHTML = a.slice(0, st.shown).map(function (b, i) { return tile(b, i + 1); }).join("") ||
      '<div style="grid-column:1/-1;padding:34px;color:var(--ink-60);font-family:var(--data)">' + esc(word("noMatch", "No beaches match — try another coast or search.")) + '</div>';
    var rem = Math.max(0, a.length - st.shown);
    var beachesWord = word("beaches", "beaches");
    set("rdDirCount", a.length + " " + beachesWord); set("rdCount", a.length.toLocaleString());
    set("rdRemain", rem);
    var more = document.getElementById("rdMore"); if (more) more.parentElement.style.display = rem ? "" : "none";
  }
  function set(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; }

  // one delegated listener so server-rendered AND js-rendered hearts both work
  grid.addEventListener("click", function (e) {
    var f = e.target.closest(".bt-fav");
    if (!f) return;
    e.preventDefault(); e.stopPropagation();
    toggleFavorite(f);
  });

  // controls
  var search = document.getElementById("rdSearch"), hero = document.getElementById("heroSearch");
  function onSearch(v) { st.q = v.toLowerCase().trim(); st.shown = 9; draw(); }
  if (search) search.addEventListener("input", function (e) { onSearch(e.target.value); });
  if (hero) hero.addEventListener("input", function (e) { if (search) search.value = e.target.value; onSearch(e.target.value); });
  var sort = document.getElementById("rdSort"); if (sort) sort.addEventListener("change", function (e) { st.sort = e.target.value; draw(); });
  var more = document.getElementById("rdMore"); if (more) more.addEventListener("click", function () { st.shown += 6; draw(); });

  // island coast filter
  var marker = document.getElementById("rdMarker");
  function setRegion(rg, pt) {
    st.region = st.region === rg ? null : rg; st.shown = 9;
    document.querySelectorAll(".region").forEach(function (b) { b.setAttribute("aria-pressed", b.dataset.region === st.region ? "true" : "false"); });
    if (st.region && pt && marker) {
      document.getElementById("rdRing").setAttribute("cx", pt[0]); document.getElementById("rdRing").setAttribute("cy", pt[1]);
      document.getElementById("rdPin").setAttribute("cx", pt[0]); document.getElementById("rdPin").setAttribute("cy", pt[1]);
      marker.classList.add("on");
    } else if (marker) { marker.classList.remove("on"); }
    set("rdDirTitle", st.region ? RN[st.region] : word("findYourBeach", "Find your beach"));
    set("rdDirSub", st.region ? word("byCoast", "Filtered by coast") : word("wholeIsland", "The whole island"));
    var scope = document.getElementById("rdScope"); if (scope) scope.textContent = st.region ? "· " + RN[st.region] : word("manyMunicipios", "· many municipios");
    draw();
    if (st.region) { var d = document.getElementById("beaches"); if (d) d.scrollIntoView({ behavior: "smooth", block: "start" }); }
  }
  document.querySelectorAll(".region").forEach(function (b) {
    b.addEventListener("click", function () {
      var pt = (b.dataset.pt || "").split(",").map(Number);
      setRegion(b.dataset.region, pt);
    });
  });

  // hero condition chips — real tag filters over RD_BEACHES
  document.querySelectorAll(".rd-home .chip[data-tag]").forEach(function (c) {
    c.addEventListener("click", function () {
      var tag = c.getAttribute("data-tag");
      var idx = st.tags.indexOf(tag);
      if (idx === -1) { st.tags.push(tag); } else { st.tags.splice(idx, 1); }
      c.setAttribute("aria-pressed", idx === -1 ? "true" : "false");
      st.shown = 9;
      draw();
      var d = document.getElementById("beaches"); if (d && idx === -1) d.scrollIntoView({ behavior: "smooth", block: "start" });
    });
  });
})();
