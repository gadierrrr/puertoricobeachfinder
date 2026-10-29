const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const source = fs.readFileSync(require('node:path').join(__dirname, '../public/assets/js/analytics.js'), 'utf8');

function boot(events = [], storage = new Map(), enabled = true, path = '/', options = {}) {
  const handlers = {}, bodyHandlers = {}, captured = [];
  const observers = [], beacons = [], scrollHandlers = [];
  const store = { getItem: k => storage.get(k), setItem: (k, v) => storage.set(k, v) };
  const body = { addEventListener: (n, f) => (bodyHandlers[n] ||= []).push(f), closest: () => null, getAttribute: () => '' };
  const document = { cookie: 'BF_ANON_ID=local', readyState: options.readyState || 'loading', documentElement: { lang: 'en', scrollHeight: 2000 }, body,
    addEventListener: (n, f) => (handlers[n] ||= []).push(f), querySelector: () => options.guide ? {} : null,
    querySelectorAll: () => options.nodes || [], getElementById: () => null };
  const window = { BF_OUTCOME_EVENTS: events, BF_CONFIG: { appEnv: 'dev' }, BeachFinderMeta: { authenticated: 1 },
    location: { href: 'http://localhost' + path, pathname: path.split('?')[0], search: path.includes('?') ? '?' + path.split('?')[1] : '', protocol: 'http:', origin: 'http://localhost' },
    BF_ANALYTICS_INTERNAL: !!options.internal, innerHeight: 1000, scrollY: 500,
    addEventListener: (n, f) => { if (n === 'scroll') scrollHandlers.push(f); }, history: { replaceState() {} }, setTimeout() {} };
  function IntersectionObserver(callback, config) {
    observers.push({callback, config}); this.observe = () => {}; this.unobserve = () => {};
  }
  if (options.observe) window.IntersectionObserver = IntersectionObserver;
  if (options.posthogThrows) window.posthog = {register() {throw Error('blocked');}};
  if (enabled) window.gtag = (...args) => captured.push(args);
  vm.runInNewContext(source, { window, document, sessionStorage: store, localStorage: store, URL, URLSearchParams,
    IntersectionObserver, Blob, setTimeout: f => {f(); return 1;}, navigator: {sendBeacon: (url, body) => {beacons.push({url,body}); return true;}}, console });
  (handlers.DOMContentLoaded || []).forEach(f => f());
  return { captured, bodyHandlers, handlers, observers, beacons, scrollHandlers };
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

function tourLink() {
  const attrs = {'data-bf-track':'referral-click', 'data-bf-referral-campaign':'vieques-tour',
    'data-bf-referral-product':'225672P1', 'data-bf-referral-placement':'guide_tours',
    'data-bf-referral-page-slug':'bioluminescent-bays', 'data-bf-referral-locale':'es',
    'data-bf-source':'beach_detail', href:'/go?c=vieques-tour'};
  const node = {closest: s => s === '[data-bf-track]' || s === '[data-bf-referral-campaign]' || s === '[data-bf-beach-id]' ? node : null,
    getAttribute: k => attrs[k] || '', setAttribute: (k,v) => {attrs[k]=v;}};
  return node;
}

test('referrals track normal and middle clicks once, never right-click, with unambiguous attribution', () => {
  const app = boot([], new Map(), true, '/', {readyState:'complete', posthogThrows:true});
  const link = tourLink();
  for (const [type, button] of [['click',0],['auxclick',1],['auxclick',2]]) {
    app.handlers[type].forEach(f => f({type,button,target:link}));
  }
  assert.deepEqual(app.captured.map(e=>e[1]), ['R2_referral_click','R2_referral_click']);
  const props=app.captured[0][2];
  assert.equal(props.referral_campaign,'vieques-tour');
  assert.equal(props.ui_source,'beach_detail');
  assert.equal(props.product_code,'225672P1');
  assert.equal(props.locale,'es');
  assert.equal(props.source,undefined);
  assert.equal(props.campaign,undefined);
});

test('referral impressions require 25 percent visibility and deduplicate HTMX rediscovery', () => {
  const link = tourLink();
  const app=boot([], new Map(), true, '/', {observe:true,nodes:[link]});
  const observer=app.observers[0];
  observer.callback([{target:link,isIntersecting:true,intersectionRatio:0.01}]);
  assert.equal(app.captured.length,0);
  observer.callback([{target:link,isIntersecting:true,intersectionRatio:0.25}]);
  app.bodyHandlers['htmx:afterSwap'].forEach(f=>f({detail:{}}));
  observer.callback([{target:link,isIntersecting:true,intersectionRatio:1}]);
  assert.equal(app.captured.length,1);
  assert.equal(app.beacons.filter(b=>b.url.includes('/referrals/')).length,1);
});

test('internal probes do not write referral impressions or count redirect visits', () => {
  const link = tourLink();
  const app=boot([], new Map(), true, '/', {internal:true,observe:true,nodes:[link]});
  app.observers[0].callback([{target:link,isIntersecting:true,intersectionRatio:1}]);
  app.handlers.click.forEach(f=>f({type:'click',button:0,target:link}));
  assert.equal(app.beacons.length,0);
  assert.match(link.getAttribute('href'), /bf_analytics_probe=1/);
});

test('guide scroll events initialize on the served guide article', () => {
  const app=boot([], new Map(), true, '/guides/bioluminescent-bays', {guide:true});
  app.scrollHandlers.forEach(f=>f());
  assert.deepEqual(app.captured.map(e=>e[2].depth), [25,50]);
});
