const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const source = fs.readFileSync(require('node:path').join(__dirname, '../public/assets/js/analytics.js'), 'utf8');

function boot(events = [], storage = new Map(), enabled = true, path = '/') {
  const handlers = {}, bodyHandlers = {}, captured = [];
  const store = { getItem: k => storage.get(k), setItem: (k, v) => storage.set(k, v) };
  const body = { addEventListener: (n, f) => (bodyHandlers[n] ||= []).push(f), closest: () => null, getAttribute: () => '' };
  const document = { cookie: 'BF_ANON_ID=local', documentElement: { lang: 'en' }, body,
    addEventListener: (n, f) => (handlers[n] ||= []).push(f), querySelector: () => null, getElementById: () => null };
  const window = { BF_OUTCOME_EVENTS: events, BF_CONFIG: { appEnv: 'dev' }, BeachFinderMeta: { authenticated: 1 },
    location: { href: 'http://localhost' + path, pathname: path.split('?')[0], search: path.includes('?') ? '?' + path.split('?')[1] : '', protocol: 'http:', origin: 'http://localhost' },
    addEventListener() {}, history: { replaceState() {} }, setTimeout() {} };
  if (enabled) window.gtag = (...args) => captured.push(args);
  vm.runInNewContext(source, { window, document, sessionStorage: store, localStorage: store, URL, URLSearchParams, navigator: {}, console });
  handlers.DOMContentLoaded.forEach(f => f());
  return { captured, bodyHandlers, handlers };
}

test('verified outcomes fire once across reloads and reject unrecognized events', () => {
  const events = [{id:'a', name:'sign_up', props:{method:'google'}}, {id:'b', name:'generate_lead', props:{source:'advertise'}}, {id:'c', name:'page_view'}];
  const storage = new Map();
  const first = boot(events, storage);
  assert.deepEqual(first.captured.map(e => e[1]), ['sign_up', 'generate_lead']);
  assert.equal(first.captured[0][2].method, 'google');
  assert.equal(boot(events, storage).captured.length, 0);
});

test('URLs and existing authentication cannot manufacture signups or leads', () => {
  assert.equal(boot([], new Map(), true, '/?src=quiz&sent=1').captured.length, 0);
});

test('favorite results use successful server response, independent of heart glyph', () => {
  const app = boot();
  const listeners = app.bodyHandlers['htmx:afterRequest'];
  function response(successful, result) {
    listeners.forEach(f => f({detail:{successful, pathInfo:{requestPath:'/api/toggle-favorite.php?variant=redesign'},
      xhr:{response:'<button>♥</button>', getResponseHeader: () => result}}}));
  }
  response(false, 'added'); response(true, null);
  assert.equal(app.captured.length, 0);
  response(true, 'added'); response(true, 'removed');
  assert.deepEqual(app.captured.map(e => e[1]), ['favorite_add', 'favorite_remove']);
});

test('blocked analytics never breaks rendering', () => {
  assert.doesNotThrow(() => boot([{id:'a',name:'sign_up'}], new Map(), false));
});
