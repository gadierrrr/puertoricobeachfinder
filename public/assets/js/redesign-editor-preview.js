/**
 * Homepage design editor — iframe preview side.
 *
 * Loaded by templates/redesign/home.php only when ?rdedit=1 and the viewer is
 * an admin. The parent page (/admin/homepage-design) holds the canonical
 * design state and posts it down; this script applies it live and reports
 * sticker interactions (drag / inline text edit / selection) back up.
 *
 * Protocol (same-origin postMessage):
 *   parent → iframe: {rd:'apply', design, fontStack, fontWeight}
 *                    {rd:'select', idx}
 *   iframe → parent: {rd:'ready'}
 *                    {rd:'select', idx}
 *                    {rd:'stickerPatch', idx, patch:{x,y}|{text}}
 */
(function () {
  'use strict';

  var ORIGIN = window.location.origin;
  var band = document.querySelector('.rd-home .hero-band');
  var layer = document.getElementById('rdStickerLayer');
  if (!band || !layer) { return; }

  band.classList.add('rd-editing');

  var LIGHT = ['#F7E14C', '#E9A81F', '#F5EFDF'];
  var STAR = "<svg viewBox='0 0 100 100'><path d='M50 0 L59 41 L100 50 L59 59 L50 100 L41 59 L0 50 L41 41 Z'/></svg>";
  var SWASH = "<svg viewBox='0 0 190 32' preserveAspectRatio='none'><path d='M7 20 C45 30 75 6 105 15 S160 30 183 12'/></svg>";

  var stickers = [];   // live design.stickers reference
  var els = [];        // sticker DOM nodes, index-aligned
  var selIdx = -1;
  var drag = null;

  function inkFor(color) {
    var c = String(color || '').toUpperCase();
    if (LIGHT.indexOf(c) !== -1) { return '#D2352A'; }
    return c === '#EE3640' ? '#F8E14A' : '#FFFFFF';
  }

  function luminance(hex) {
    return (0.299 * parseInt(hex.slice(1, 3), 16)
          + 0.587 * parseInt(hex.slice(3, 5), 16)
          + 0.114 * parseInt(hex.slice(5, 7), 16)) / 255;
  }

  function post(msg) { window.parent.postMessage(msg, ORIGIN); }

  // ---- background / font / texture -------------------------------------
  function ensureLayer(cls, before) {
    var el = band.querySelector('.' + cls);
    if (!el) {
      el = document.createElement('div');
      el.className = cls;
      band.insertBefore(el, before);
    }
    return el;
  }

  function applyDesign(design, fontStack, fontWeight) {
    var rd = document.querySelector('.rd');
    if (fontStack && rd) {
      rd.style.setProperty('--disp', fontStack);
      document.querySelectorAll('.rd-home .headline, .rd-home .dir-head h2').forEach(function (el) {
        el.style.fontWeight = fontWeight || 400;
      });
    }

    var grain = band.querySelector('.hero-grain');
    if (grain) { grain.style.opacity = design.texture / 100; }

    var bg = ensureLayer('hero-bg', band.firstChild);
    var scrim = ensureLayer('hero-scrim', bg.nextSibling);
    if (design.bg_mode === 'photo' && design.bg_photo) {
      band.style.background = '';
      band.classList.remove('dark-hero');
      bg.style.display = '';
      scrim.style.display = '';
      bg.style.backgroundImage = 'url("' + design.bg_photo + '")';
      bg.style.opacity = design.photo_opacity / 100;
      scrim.style.background = 'rgba(9,22,32,' + design.darken / 100 + ')';
    } else {
      bg.style.display = 'none';
      scrim.style.display = 'none';
      if (design.bg_color === 'default') {
        band.style.background = '';
        band.classList.remove('dark-hero');
      } else {
        band.style.background = design.bg_color;
        band.classList.toggle('dark-hero', luminance(design.bg_color) > 0.62);
      }
    }

    renderStickers(design.stickers || []);
  }

  // ---- stickers ---------------------------------------------------------
  function renderStickers(list) {
    stickers = list;
    layer.innerHTML = '';
    els = [];
    list.forEach(function (s, i) { els.push(makeEl(s, i)); });
    highlight();
  }

  function makeEl(s, idx) {
    var el = document.createElement('div');
    el.className = 'sticker sticker-' + s.type;
    el.dataset.idx = idx;
    el.style.left = s.x + '%';
    el.style.top = s.y + '%';
    el.style.setProperty('--rot', s.rot + 'deg');
    el.style.setProperty('--sc', s.sc);
    el.style.setProperty('--stk', s.color);
    el.style.setProperty('--stkt', inkFor(s.color));
    if (s.type === 'star') { el.innerHTML = STAR; }
    else if (s.type === 'swash') { el.innerHTML = SWASH; }
    else {
      var t = document.createElement('div');
      t.className = 'st-text';
      t.textContent = s.text || '';
      el.appendChild(t);
    }
    wire(el, idx);
    layer.appendChild(el);
    return el;
  }

  function highlight() {
    els.forEach(function (el, i) { el.classList.toggle('selected', i === selIdx); });
  }

  function editing(el) {
    var t = el.querySelector('.st-text');
    return t && t.getAttribute('contenteditable') === 'true';
  }

  function wire(el, idx) {
    el.addEventListener('pointerdown', function (e) {
      if (editing(el)) { return; }
      e.preventDefault();
      selIdx = idx;
      highlight();
      post({ rd: 'select', idx: idx });
      var br = band.getBoundingClientRect();
      var sr = el.getBoundingClientRect();
      drag = { el: el, idx: idx, br: br, ox: e.clientX - (sr.left + sr.width / 2), oy: e.clientY - (sr.top + sr.height / 2) };
      el.classList.add('dragging');
      try { el.setPointerCapture(e.pointerId); } catch (_) {}
    });
    el.addEventListener('pointermove', function (e) {
      if (!drag || drag.el !== el) { return; }
      var s = stickers[drag.idx];
      s.x = Math.max(-4, Math.min(104, (e.clientX - drag.ox - drag.br.left) / drag.br.width * 100));
      s.y = Math.max(-8, Math.min(112, (e.clientY - drag.oy - drag.br.top) / drag.br.height * 100));
      el.style.left = s.x + '%';
      el.style.top = s.y + '%';
    });
    el.addEventListener('pointerup', function () {
      if (!drag || drag.el !== el) { return; }
      el.classList.remove('dragging');
      var s = stickers[drag.idx];
      post({ rd: 'stickerPatch', idx: drag.idx, patch: { x: s.x, y: s.y } });
      drag = null;
    });
    el.addEventListener('dblclick', function () {
      var t = el.querySelector('.st-text');
      if (!t) { return; }
      t.setAttribute('contenteditable', 'true');
      t.focus();
      var range = document.createRange();
      range.selectNodeContents(t);
      var sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(range);
    });
    el.addEventListener('blur', function () {
      var t = el.querySelector('.st-text');
      if (!t || t.getAttribute('contenteditable') !== 'true') { return; }
      t.removeAttribute('contenteditable');
      stickers[idx].text = t.textContent.slice(0, 60);
      post({ rd: 'stickerPatch', idx: idx, patch: { text: stickers[idx].text } });
    }, true);
  }

  layer.addEventListener('pointerdown', function (e) {
    if (e.target === layer) {
      selIdx = -1;
      highlight();
      post({ rd: 'select', idx: -1 });
    }
  });

  // ---- messages from parent ---------------------------------------------
  window.addEventListener('message', function (e) {
    if (e.origin !== ORIGIN || !e.data || !e.data.rd) { return; }
    if (e.data.rd === 'apply') {
      applyDesign(e.data.design, e.data.fontStack, e.data.fontWeight);
    } else if (e.data.rd === 'select') {
      selIdx = e.data.idx;
      highlight();
    }
  });

  post({ rd: 'ready' });
})();
