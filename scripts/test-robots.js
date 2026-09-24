#!/usr/bin/env node
/**
 * Crawl-policy regression checks; no application bootstrap, DB, or network.
 * Usage: node scripts/test-robots.js [path/to/robots.txt]
 *
 * The matcher covers this site's single wildcard user-agent group, ASCII
 * paths, * wildcards, terminal $, longest rule, and Allow winning ties.
 * It is not a general-purpose robots parser. Matching/precedence references:
 * https://developers.google.com/crawling/docs/robots-txt/robots-txt-spec
 * https://developers.google.com/crawling/docs/faceted-navigation
 */
const fs = require('node:fs');
const path = require('node:path');

function parseRules(source) {
  const agents = [];
  const rules = [];
  for (const rawLine of source.split(/\r?\n/)) {
    const line = rawLine.split('#', 1)[0].trim();
    const separator = line.indexOf(':');
    if (separator < 0) continue;
    const field = line.slice(0, separator).trim().toLowerCase();
    const value = line.slice(separator + 1).trim();
    if (field === 'user-agent') agents.push(value);
    if ((field === 'allow' || field === 'disallow') && value) {
      const anchored = value.endsWith('$');
      const pattern = anchored ? value.slice(0, -1) : value;
      const escaped = pattern.split('*')
        .map(part => part.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'))
        .join('.*');
      rules.push({
        allow: field === 'allow',
        value,
        specificity: Buffer.byteLength(value),
        regex: new RegExp('^' + escaped + (anchored ? '$' : '')),
      });
    }
  }
  if (agents.length !== 1 || agents[0] !== '*') {
    throw new Error('Extend this test matcher before introducing multiple user-agent groups.');
  }
  return rules;
}

function decision(rules, requestPath) {
  // Fragments are never sent in an HTTP request; query strings do participate.
  const candidate = requestPath.split('#', 1)[0];
  let winner = null;
  for (const rule of rules) {
    if (!rule.regex.test(candidate)) continue;
    if (!winner || rule.specificity > winner.specificity
      || (rule.specificity === winner.specificity && rule.allow)) {
      winner = rule;
    }
  }
  return { allowed: winner ? winner.allow : true, rule: winner?.value ?? '(default)' };
}

const failures = [];
let checks = 0;
function expect(rules, requestPath, allowed, label = '') {
  checks++;
  const actual = decision(rules, requestPath);
  if (actual.allowed !== allowed) {
    failures.push(`${label ? label + ': ' : ''}${requestPath}: expected ${allowed ? 'ALLOW' : 'BLOCK'}, got ${actual.allowed ? 'ALLOW' : 'BLOCK'} via ${actual.rule}`);
  }
}

// Google's documented examples independently anchor matcher semantics.
for (const [directives, requestPath, allowed] of [
  ['Allow: /p\nDisallow: /', '/page', true],
  ['Allow: /folder\nDisallow: /folder', '/folder/page', true],
  ['Allow: /page\nDisallow: /*.htm', '/page.htm', false],
  ['Allow: /page\nDisallow: /*.ph', '/page.php5', true],
  ['Allow: /$\nDisallow: /', '/', true],
  ['Allow: /$\nDisallow: /', '/page.htm', false],
]) {
  expect(parseRules('User-agent: *\n' + directives), requestPath, allowed, 'Google precedence example');
}

const robotsPath = process.argv[2] || path.join(__dirname, '../public/robots.txt');
const rules = parseRules(fs.readFileSync(robotsPath, 'utf8'));
const landingPaths = [
  '/', '/es', '/best-beaches', '/es/mejores-playas',
  '/best-snorkeling-beaches', '/best-surfing-beaches', '/best-family-beaches',
  '/beaches-near-san-juan', '/es/playas-cerca-de-san-juan',
  '/beaches-in-san-juan', '/es/playas-en-san-juan',
  '/beaches/swimming', '/es/playas/natacion',
];
const facetQueries = [
  'municipality=San+Juan', 'sort=rating', 'q=Flamenco', 'view=map',
  'collection=best-beaches', 'include_all=1', 'activity=snorkeling',
  'has_lifeguard=1', 'limit=30', 'design=classic',
  'tags=swimming', 'tags[]=swimming', 'tags[0]=swimming',
  'tags%5B%5D=swimming', 'tags%5B0%5D=swimming', 'tags%5b0%5d=swimming',
  'amenities=parking', 'amenities[]=parking', 'amenities[0]=parking',
  'amenities%5B%5D=parking', 'amenities%5B0%5D=parking', 'amenities%5b0%5d=parking',
];
for (const landingPath of landingPaths) {
  expect(rules, landingPath, true, 'clean landing');
  // Pagination alone must not become blocked when redundant public Allows
  // disappear. Filters must still block a URL even when page is first.
  expect(rules, landingPath + '?page=2', true, 'unfiltered pagination');
  expect(rules, landingPath + '?utm_source=test&page=2', true, 'pagination after tracking');
  for (const query of facetQueries) {
    expect(rules, landingPath + '?' + query, false, 'first facet');
    expect(rules, landingPath + '?utm_source=test&' + query, false, 'later facet');
    expect(rules, landingPath + '?page=2&' + query, false, 'facet after pagination');
    expect(rules, landingPath + '?' + query + '&page=2', false, 'pagination after facet');
  }
}

// Real collection links, rendering dependencies, redirects, and private areas.
for (const [requestPath, allowed] of [
  ['/best-beaches?collection=best-beaches&include_all=0&view=list&sort=rating&page=1&limit=15', false],
  ['/es/mejores-playas?utm_source=test&tags%5B0%5D=snorkeling&municipality=San+Juan', false],
  ['/beach/flamenco-beach', true],
  ['/es/playa/flamenco-beach', true],
  ['/guides/beach-safety-tips', true],
  ['/es/guias/consejos-seguridad-playa', true],
  ['/beaches-near-me', true],
  ['/es/playas-cerca-de-mi', true],
  ['/beach/flamenco-beach?utm_source=test', true],
  ['/best-beaches?otherq=Flamenco', true],
  ['/best-beaches?return=%2F%3Fq%3DFlamenco', true],
  ['/assets/css/styles.css?v=5.4', true],
  ['/assets/js/app.min.js?v=1', true],
  ['/images/beaches/flamenco-beach-culebra.webp', true],
  ['/sitemap.xml', true], ['/llms.txt', true], ['/llms-full.txt', true], ['/feed.xml', true],
  ['/auth/google', true], ['/auth/google/', true],
  ['/auth/google?return=%2Fbest-beaches', true],
  ['/auth/google/?return=%2Fbest-beaches', true],
  ['/auth/google/index.php?return=%2Fbest-beaches', true],
  ['/auth/google/callback', false], ['/auth/google/callback.php', false],
  ['/admin/beaches', false], ['/api/admin/beaches.php', false],
  ['/api/reviews/list.php', false], ['/inc/helpers.php', false],
  ['/beach.php?slug=flamenco-beach', false], ['/best-beaches.php', false],
  ['/go?c=viator&url=example', false], ['/ad-out?id=1', false], ['/local-out?id=1', false],
  ['/api/beaches.php', true], ['/api/beaches.php?page=2', true],
]) expect(rules, requestPath, allowed);

for (const query of facetQueries) {
  expect(rules, '/api/beaches.php?' + query, true, 'rendering API first facet');
  expect(rules, '/api/beaches.php?format=html&' + query, true, 'rendering API later facet');
}

if (failures.length) {
  console.error(`FAIL: ${failures.length} of ${checks} robots checks failed.`);
  console.error(failures.slice(0, 20).join('\n'));
  if (failures.length > 20) console.error(`... ${failures.length - 20} more failures`);
  process.exit(1);
}
console.log(`PASS: ${checks} robots checks (canonical routes, facets, pagination, rendering, private paths, and Google precedence).`);
