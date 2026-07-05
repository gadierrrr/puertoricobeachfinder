/**
 * Homepage design editor — admin parent side (/admin/homepage-design).
 *
 * Holds the canonical design state, renders the control panels (font picker,
 * background, texture, stickers), drives the live homepage preview iframe
 * (see redesign-editor-preview.js for the message protocol) and saves to
 * /api/admin/homepage-design.php.
 */
(function () {
  'use strict';

  var cfg = JSON.parse(document.getElementById('hpConfig').textContent);
  var state = cfg.design;
  var selIdx = -1;
  var dirty = false;

  var iframe = document.getElementById('hpPreview');
  var csrf = document.querySelector('meta[name="csrf-token"]').content;
  var $ = function (id) { return document.getElementById(id); };

  var STICKER_COLORS = ['#F7E14C', '#E9A81F', '#EE3640', '#F5EFDF', '#45A3D9'];
  var DEFAULT_TEXT = { note: '¡Playa!', circle: 'nuevo', banner: '¡Vamos pa’ la playa!' };
  var DEFAULT_COLOR = { star: '#EE3640', swash: '#EE3640', banner: '#E9A81F', note: '#F7E14C', circle: '#F7E14C' };

  // ---- preview messaging --------------------------------------------------
  function send(msg) {
    if (iframe.contentWindow) { iframe.contentWindow.postMessage(msg, window.location.origin); }
  }
  function apply() {
    var f = cfg.fonts[state.font] || cfg.fonts['alfa-slab-one'];
    send({ rd: 'apply', design: state, fontStack: f.stack, fontWeight: f.weight });
  }
  window.addEventListener('message', function (e) {
    if (e.origin !== window.location.origin || !e.data || !e.data.rd) { return; }
    if (e.data.rd === 'ready') { apply(); send({ rd: 'select', idx: selIdx }); }
    if (e.data.rd === 'select') { selIdx = e.data.idx; renderSelPanel(); }
    if (e.data.rd === 'stickerPatch' && state.stickers[e.data.idx]) {
      Object.assign(state.stickers[e.data.idx], e.data.patch);
      setDirty(true);
      if (e.data.idx === selIdx) { renderSelPanel(); }
    }
  });

  function setDirty(v) {
    dirty = v;
    $('hpSave').disabled = !dirty;
    $('hpStatus').textContent = dirty ? 'Unsaved changes' : '';
  }
  function change() { setDirty(true); apply(); }

  // ---- font picker ----------------------------------------------------------
  function renderFonts() {
    var wrap = $('hpFontList');
    wrap.innerHTML = '';
    var lastGroup = '';
    Object.keys(cfg.fonts).forEach(function (slug) {
      var f = cfg.fonts[slug];
      if (f.group !== lastGroup) {
        lastGroup = f.group;
        var g = document.createElement('div');
        g.className = 'text-[10px] font-bold uppercase tracking-widest text-amber-500 mt-3 mb-1';
        g.textContent = f.group;
        wrap.appendChild(g);
      }
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'hp-font-btn' + (slug === state.font ? ' active' : '');
      b.dataset.slug = slug;
      b.innerHTML = '<span class="fn" style="font-family:' + f.stack.replace(/"/g, '&quot;') + ';font-weight:' + f.weight + '">' + f.family + '</span><span class="fc">' + f.note + '</span>';
      b.addEventListener('click', function () {
        state.font = slug;
        wrap.querySelectorAll('.hp-font-btn').forEach(function (x) { x.classList.remove('active'); });
        b.classList.add('active');
        change();
      });
      wrap.appendChild(b);
    });
  }

  // ---- background -----------------------------------------------------------
  function renderBgMode() {
    $('hpModeColor').classList.toggle('active', state.bg_mode === 'color');
    $('hpModePhoto').classList.toggle('active', state.bg_mode === 'photo');
    $('hpColorRow').style.display = state.bg_mode === 'color' ? '' : 'none';
    $('hpPhotoRow').style.display = state.bg_mode === 'photo' ? '' : 'none';
  }

  function renderSwatches() {
    var row = $('hpSwatches');
    row.innerHTML = '';
    cfg.presets.forEach(function (p) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'hp-sw' + (state.bg_color === p.v && state.bg_mode === 'color' ? ' active' : '');
      b.style.background = p.sw;
      b.title = p.n;
      b.addEventListener('click', function () {
        state.bg_mode = 'color';
        state.bg_color = p.v;
        renderBgMode(); renderSwatches();
        change();
      });
      row.appendChild(b);
    });
  }

  function renderThumbs() {
    var grid = $('hpThumbs');
    grid.innerHTML = '';
    cfg.photos.forEach(function (url) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'hp-thumb' + (state.bg_photo === url ? ' active' : '');
      b.style.backgroundImage = 'url("' + url + '")';
      b.title = url.split('/').pop();
      b.addEventListener('click', function () {
        state.bg_mode = 'photo';
        state.bg_photo = url;
        renderThumbs();
        change();
      });
      grid.appendChild(b);
    });
  }

  $('hpModeColor').addEventListener('click', function () {
    state.bg_mode = 'color';
    renderBgMode(); change();
  });
  $('hpModePhoto').addEventListener('click', function () {
    if (!state.bg_photo && cfg.photos.length) { state.bg_photo = cfg.photos[0]; }
    if (state.bg_photo) { state.bg_mode = 'photo'; }
    renderBgMode(); renderThumbs(); change();
  });
  $('hpCustomColor').addEventListener('input', function () {
    state.bg_mode = 'color';
    state.bg_color = this.value;
    renderSwatches(); change();
  });
  $('hpOpacity').addEventListener('input', function () { state.photo_opacity = +this.value; change(); });
  $('hpDarken').addEventListener('input', function () { state.darken = +this.value; change(); });
  $('hpTexture').addEventListener('input', function () { state.texture = +this.value; change(); });

  // ---- photo upload ---------------------------------------------------------
  $('hpUploadBtn').addEventListener('click', function () { $('hpUploadInput').click(); });
  $('hpUploadInput').addEventListener('change', function () {
    var file = this.files[0];
    if (!file) { return; }
    var fd = new FormData();
    fd.append('action', 'upload-bg');
    fd.append('csrf_token', csrf);
    fd.append('photo', file);
    $('hpUploadBtn').textContent = 'Uploading…';
    fetch('/api/admin/homepage-design.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.error) { throw new Error(data.error); }
        cfg.photos.unshift(data.url);
        state.bg_mode = 'photo';
        state.bg_photo = data.url;
        renderBgMode(); renderThumbs(); change();
      })
      .catch(function (err) { alert('Upload failed: ' + err.message); })
      .finally(function () {
        $('hpUploadBtn').textContent = 'Upload photo';
        $('hpUploadInput').value = '';
      });
  });

  // ---- stickers -------------------------------------------------------------
  document.querySelectorAll('[data-add-sticker]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var type = btn.dataset.addSticker;
      if (state.stickers.length >= 12) { return; }
      state.stickers.push({
        type: type,
        x: 50 + (state.stickers.length % 5) * 3,
        y: 44 + (state.stickers.length % 5) * 3,
        rot: type === 'star' ? -8 : -4,
        sc: 1,
        color: DEFAULT_COLOR[type],
        text: DEFAULT_TEXT[type] || ''
      });
      selIdx = state.stickers.length - 1;
      change();
      send({ rd: 'select', idx: selIdx });
      renderSelPanel();
    });
  });
  $('hpClearStickers').addEventListener('click', function () {
    state.stickers = [];
    selIdx = -1;
    change();
    renderSelPanel();
  });

  function renderSelPanel() {
    var panel = $('hpSelPanel');
    var s = state.stickers[selIdx];
    if (!s) {
      panel.innerHTML = '<p class="text-xs text-gray-400">Click a sticker in the preview to edit it — drag to move, double-click text to type.</p>';
      return;
    }
    panel.innerHTML = '';
    var head = document.createElement('div');
    head.className = 'text-[10px] font-bold uppercase tracking-widest text-amber-500 mb-1';
    head.textContent = 'Editing · ' + s.type;
    panel.appendChild(head);

    if (s.type !== 'star' && s.type !== 'swash') {
      var txt = document.createElement('input');
      txt.type = 'text';
      txt.maxLength = 60;
      txt.value = s.text;
      txt.className = 'w-full border rounded-md px-2 py-1.5 text-sm mb-2';
      txt.addEventListener('input', function () { s.text = txt.value; change(); });
      panel.appendChild(txt);
    }

    var colors = document.createElement('div');
    colors.className = 'flex gap-2 mb-2';
    STICKER_COLORS.forEach(function (c) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'hp-sw' + (s.color === c ? ' active' : '');
      b.style.background = c;
      b.addEventListener('click', function () { s.color = c; change(); renderSelPanel(); });
      colors.appendChild(b);
    });
    panel.appendChild(colors);

    panel.appendChild(slider('Size', 40, 250, Math.round(s.sc * 100), function (v) { s.sc = v / 100; change(); }));
    panel.appendChild(slider('Rotate', -45, 45, Math.round(s.rot), function (v) { s.rot = v; change(); }));

    var del = document.createElement('button');
    del.type = 'button';
    del.className = 'mt-2 text-xs font-semibold text-red-600 hover:text-red-700';
    del.textContent = 'Delete sticker';
    del.addEventListener('click', function () {
      state.stickers.splice(selIdx, 1);
      selIdx = -1;
      change();
      renderSelPanel();
    });
    panel.appendChild(del);
  }

  function slider(label, min, max, value, oninput) {
    var wrap = document.createElement('label');
    wrap.className = 'block text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2';
    wrap.textContent = label;
    var r = document.createElement('input');
    r.type = 'range';
    r.min = min; r.max = max; r.value = value;
    r.className = 'w-full block mt-1';
    r.addEventListener('input', function () { oninput(+r.value); });
    wrap.appendChild(r);
    return wrap;
  }

  // ---- save / reset -----------------------------------------------------------
  $('hpSave').addEventListener('click', function () {
    var fd = new FormData();
    fd.append('action', 'save');
    fd.append('csrf_token', csrf);
    fd.append('design', JSON.stringify(state));
    $('hpSave').textContent = 'Saving…';
    fetch('/api/admin/homepage-design.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.error) { throw new Error(data.error); }
        state = data.design; // server-sanitized copy
        setDirty(false);
        $('hpStatus').textContent = 'Saved ✓';
        apply();
      })
      .catch(function (err) { $('hpStatus').textContent = 'Save failed: ' + err.message; })
      .finally(function () { $('hpSave').textContent = 'Save & publish'; });
  });

  $('hpReset').addEventListener('click', function () {
    if (!window.confirm('Reset to the default design (sky gradient, Alfa Slab One, no stickers)? You still need to Save to publish it.')) { return; }
    state = JSON.parse(JSON.stringify(cfg.defaults));
    selIdx = -1;
    renderFonts(); renderBgMode(); renderSwatches(); renderThumbs(); renderSelPanel(); syncSliders();
    change();
  });

  function syncSliders() {
    $('hpOpacity').value = state.photo_opacity;
    $('hpDarken').value = state.darken;
    $('hpTexture').value = state.texture;
    $('hpCustomColor').value = /^#/.test(state.bg_color) ? state.bg_color : '#45A3D9';
  }

  // ---- init -------------------------------------------------------------------
  renderFonts();
  renderBgMode();
  renderSwatches();
  renderThumbs();
  renderSelPanel();
  syncSliders();
  setDirty(false);
})();
