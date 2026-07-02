/* Redesign v2 homepage — beach directory + island coast filter. Data: window.RD_BEACHES. */
(function () {
  "use strict";
  var B = window.RD_BEACHES || [];
  var grid = document.getElementById("rdGrid");
  if (!grid) return;
  var RN = { north: "North Coast", metro: "Metro · San Juan", west: "Porta del Sol", south: "South Coast", east: "East · Fajardo", cays: "The Cays" };
  var st = { region: null, q: "", sort: "score", shown: 9 };
  var esc = function (s) { return String(s == null ? "" : s).replace(/[&<>"]/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]; }); };
  var barVal = function (b, label) { for (var i = 0; i < b.bars.length; i++) { if (b.bars[i][0] === label) return b.bars[i][1]; } return 0; };

  function list() {
    var a = B.filter(function (b) {
      return (!st.region || b.rg === st.region) && (!st.q || (b.n + " " + b.m).toLowerCase().indexOf(st.q) > -1);
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
    var url = "/beach/" + b.slug;
    var barsHtml = bar("⭐ Overall", b.sc, b.sc >= 67 ? "g" : b.sc >= 40 ? "a" : "r");
    var icons = { "Calm water": "🌊 Calm", "Snorkeling": "🤿 Snorkel", "Seclusion": "🌾 Quiet", "Family": "👨‍👩‍👧 Family", "Facilities": "🚻 Facilities" };
    for (var i = 0; i < b.bars.length; i++) { barsHtml += bar(icons[b.bars[i][0]] || b.bars[i][0], b.bars[i][1], b.bars[i][2]); }
    return '<a class="btile" href="' + url + '">' +
      '<div class="btile-photo" style="background-image:url(\'' + esc(b.img) + '\')"></div><div class="btile-grad"></div>' +
      '<div class="btile-rest"><div class="bt-top"><span class="bt-rank">' + rank + '</span>' +
      '<span class="bt-water">🌊 ' + esc(b.water) + '</span></div>' +
      '<div class="bt-name">' + esc(b.n) + '</div><div class="bt-muni">' + esc(b.m) + '</div>' +
      '<div class="bt-rest-stats"><span>👥 ' + esc(b.crowd) + '</span><span>🏄 ' + esc(b.surf) + '</span>' + (b.rt ? '<span>⭐ ' + b.rt.toFixed(1) + '</span>' : '') + '</div></div>' +
      '<div class="btile-hover"><div class="bt-hovtop"><button class="bt-fav" type="button" title="Save">♡</button><span>Beach Score ' + b.sc + '</span></div>' +
      '<div class="scores">' + barsHtml + '</div>' +
      '<span class="bt-view" style="margin-top:8px;text-align:center;display:block">View beach →</span></div></a>';
  }

  function draw() {
    var a = list();
    grid.innerHTML = a.slice(0, st.shown).map(function (b, i) { return tile(b, i + 1); }).join("") ||
      '<div style="grid-column:1/-1;padding:34px;color:var(--ink-60);font-family:var(--data)">No beaches match — try another coast or search.</div>';
    var rem = Math.max(0, a.length - st.shown);
    set("rdDirCount", a.length + " beaches"); set("rdCount", a.length.toLocaleString());
    set("rdRemain", rem);
    var more = document.getElementById("rdMore"); if (more) more.parentElement.style.display = rem ? "" : "none";
    grid.querySelectorAll(".bt-fav").forEach(function (f) {
      f.addEventListener("click", function (e) { e.preventDefault(); e.stopPropagation(); var on = f.classList.toggle("on"); f.textContent = on ? "♥" : "♡"; });
    });
  }
  function set(id, v) { var el = document.getElementById(id); if (el) el.textContent = v; }

  // controls
  var search = document.getElementById("rdSearch"), hero = document.getElementById("heroSearch");
  function onSearch(v) { st.q = v.toLowerCase().trim(); st.shown = 9; draw(); }
  if (search) search.addEventListener("input", function (e) { onSearch(e.target.value); });
  if (hero) hero.addEventListener("input", function (e) { if (search) search.value = e.target.value; onSearch(e.target.value); });
  var sort = document.getElementById("rdSort"); if (sort) sort.addEventListener("change", function (e) { st.sort = e.target.value; draw(); });
  var more = document.getElementById("rdMore"); if (more) more.addEventListener("click", function () { st.shown += 6; draw(); });
  document.querySelectorAll(".seg [data-view]").forEach(function (v) {
    v.addEventListener("click", function () {
      document.querySelectorAll(".seg [data-view]").forEach(function (x) { x.classList.remove("on"); });
      v.classList.add("on");
    });
  });

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
    set("rdDirTitle", st.region ? RN[st.region] : "Find your beach");
    set("rdDirSub", st.region ? "Filtered by coast" : "The whole island");
    var scope = document.getElementById("rdScope"); if (scope) scope.textContent = st.region ? "· " + RN[st.region] : "· many municipios";
    draw();
    if (st.region) { var d = document.getElementById("beachdir"); if (d) d.scrollIntoView({ behavior: "smooth", block: "start" }); }
  }
  document.querySelectorAll(".region").forEach(function (b) {
    b.addEventListener("click", function () {
      var pt = (b.dataset.pt || "").split(",").map(Number);
      setRegion(b.dataset.region, pt);
    });
  });

  // hero condition chips (visual toggle for now)
  document.querySelectorAll(".rd-home .chip").forEach(function (c) {
    c.addEventListener("click", function () { c.setAttribute("aria-pressed", c.getAttribute("aria-pressed") === "true" ? "false" : "true"); });
  });

  draw();
})();
