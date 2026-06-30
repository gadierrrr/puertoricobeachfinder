/**
 * Beach Lists UI — create/edit/delete lists, add/remove beaches, share.
 * Talks to /api/lists.php. CSP-compliant: all handlers are global functions
 * invoked via the data-action delegation system (csp-bindings.js).
 */
(function () {
  'use strict';

  function cfg() { return window.BF_LISTS || {}; }
  function strings() { return cfg().strings || {}; }
  function csrf() { return (window.BeachFinder && window.BeachFinder.csrfToken) || ''; }

  function toast(msg, type) {
    if (typeof window.showToast === 'function') {
      window.showToast(msg, type || 'info', 3000);
    }
  }

  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function api(action, params) {
    var body = new URLSearchParams();
    body.set('action', action);
    body.set('csrf_token', csrf());
    Object.keys(params || {}).forEach(function (k) {
      if (params[k] !== undefined && params[k] !== null) body.set(k, params[k]);
    });
    return fetch('/api/lists.php?action=' + encodeURIComponent(action), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString()
    }).then(function (r) {
      return r.json().then(function (d) { return { ok: r.ok, data: d }; });
    });
  }

  // ---------- Create / Edit modal ----------
  function modalEl() { return document.getElementById('list-modal'); }

  function openModal(title) {
    var m = modalEl();
    if (!m) return;
    var t = document.getElementById('list-modal-title');
    if (t && title) t.textContent = title;
    m.classList.remove('hidden');
    m.classList.add('flex');
    var nameEl = document.getElementById('list-form-name');
    if (nameEl) nameEl.focus();
  }

  window.openCreateListModal = function () {
    var form = document.getElementById('list-form');
    if (form) form.reset();
    var idEl = document.getElementById('list-form-id');
    if (idEl) idEl.value = '';
    var err = document.getElementById('list-form-error');
    if (err) err.classList.add('hidden');
    openModal(strings().newList || 'New list');
  };

  window.openEditListModal = function (id, name, description, isPublic) {
    var idEl = document.getElementById('list-form-id');
    if (idEl) idEl.value = id;
    var nameEl = document.getElementById('list-form-name');
    if (nameEl) nameEl.value = name || '';
    var descEl = document.getElementById('list-form-desc');
    if (descEl) descEl.value = description || '';
    var pubEl = document.getElementById('list-form-public');
    if (pubEl) pubEl.checked = !!isPublic;
    var err = document.getElementById('list-form-error');
    if (err) err.classList.add('hidden');
    openModal(strings().editList || 'Edit list');
  };

  window.closeListModal = function () {
    var m = modalEl();
    if (!m) return;
    m.classList.add('hidden');
    m.classList.remove('flex');
  };

  window.submitListForm = function (event) {
    if (event) event.preventDefault();
    var id = (document.getElementById('list-form-id') || {}).value || '';
    var name = (document.getElementById('list-form-name') || {}).value || '';
    var description = (document.getElementById('list-form-desc') || {}).value || '';
    var isPublic = (document.getElementById('list-form-public') || {}).checked ? '1' : '0';
    var err = document.getElementById('list-form-error');
    var submitBtn = document.getElementById('list-form-submit');

    if (!name.trim()) {
      if (err) { err.textContent = 'Name is required'; err.classList.remove('hidden'); }
      return;
    }
    if (submitBtn) submitBtn.disabled = true;

    var action = id ? 'update' : 'create';
    var params = { name: name, description: description, is_public: isPublic };
    if (id) params.list_id = id;

    api(action, params).then(function (res) {
      if (res.ok && res.data && res.data.success) {
        window.location.reload();
      } else {
        if (err) { err.textContent = (res.data && res.data.error) || strings().genericError; err.classList.remove('hidden'); }
        if (submitBtn) submitBtn.disabled = false;
      }
    }).catch(function () {
      if (err) { err.textContent = strings().genericError; err.classList.remove('hidden'); }
      if (submitBtn) submitBtn.disabled = false;
    });
  };

  // ---------- Delete (confirm via data-action-confirm) ----------
  window.deleteList = function (id) {
    api('delete', { list_id: id }).then(function (res) {
      if (res.ok && res.data && res.data.success) {
        window.location.reload();
      } else {
        toast((res.data && res.data.error) || strings().genericError, 'error');
      }
    }).catch(function () { toast(strings().genericError, 'error'); });
  };

  // ---------- Share ----------
  window.shareList = function (slug) {
    var url = (cfg().origin || window.location.origin) + (cfg().listBasePath || '/list') + '?slug=' + encodeURIComponent(slug);
    if (navigator.share) {
      navigator.share({ url: url }).catch(function () {});
      return;
    }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(url).then(function () {
        toast(strings().linkCopied || 'Link copied!', 'success');
      }).catch(function () { toast(url, 'info'); });
    } else {
      toast(url, 'info');
    }
  };

  // ---------- Remove beach (list.php owner) ----------
  window.removeBeachFromList = function (beachId) {
    api('remove-beach', { list_id: cfg().currentListId, beach_id: beachId }).then(function (res) {
      if (res.ok && res.data && res.data.success) {
        window.location.reload();
      } else {
        toast((res.data && res.data.error) || strings().genericError, 'error');
      }
    }).catch(function () { toast(strings().genericError, 'error'); });
  };

  // ---------- Add beach (list.php owner) autocomplete ----------
  function initAddBeach() {
    var input = document.getElementById('list-add-search');
    var results = document.getElementById('list-add-results');
    if (!input || !results) return;

    var candidates = cfg().addCandidates || [];
    var addLabel = strings().add || 'Add';
    var noMatches = strings().noMatches || 'No matches';

    function render(items) {
      if (!items.length) {
        results.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">' + escapeHtml(noMatches) + '</div>';
        results.classList.remove('hidden');
        return;
      }
      results.innerHTML = items.map(function (b) {
        var muni = b.municipality ? ' <span class="text-gray-400">· ' + escapeHtml(b.municipality) + '</span>' : '';
        return '<button type="button" class="w-full text-left px-3 py-2 hover:bg-gray-50 flex items-center justify-between gap-2" data-beach-id="' + escapeHtml(String(b.id)) + '">'
          + '<span class="text-sm text-gray-800">' + escapeHtml(b.name) + muni + '</span>'
          + '<span class="text-xs font-medium text-sunset-500 shrink-0">' + escapeHtml(addLabel) + '</span>'
          + '</button>';
      }).join('');
      results.classList.remove('hidden');
    }

    input.addEventListener('input', function () {
      var q = input.value.trim().toLowerCase();
      if (q.length < 2) { results.classList.add('hidden'); results.innerHTML = ''; return; }
      var matches = candidates.filter(function (b) {
        return (b.name && b.name.toLowerCase().indexOf(q) !== -1) ||
               (b.municipality && b.municipality.toLowerCase().indexOf(q) !== -1);
      }).slice(0, 8);
      render(matches);
    });

    results.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-beach-id]');
      if (!btn) return;
      var beachId = btn.getAttribute('data-beach-id');
      btn.disabled = true;
      api('add-beach', { list_id: cfg().currentListId, beach_id: beachId }).then(function (res) {
        if (res.ok && res.data && res.data.success) {
          window.location.reload();
        } else {
          toast((res.data && res.data.error) || strings().genericError, 'error');
          btn.disabled = false;
        }
      }).catch(function () { toast(strings().genericError, 'error'); btn.disabled = false; });
    });

    document.addEventListener('click', function (e) {
      if (!results.contains(e.target) && e.target !== input) {
        results.classList.add('hidden');
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAddBeach);
  } else {
    initAddBeach();
  }
})();
