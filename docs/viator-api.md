# Viator affiliate integration

The site monetizes beach and guide pages with Viator tours in three placement
tiers, all rendered from a local SQLite cache. API calls are CLI-only;
checkout always happens on Viator.

1. **Curated products** — eight editorially verified beach/product matches
   (`beach_referral_placements`, anchor `tours_curated`) plus hand-picked
   guide placements (`guide_tour_placements`). Editorial copy lives in
   `toursCuratedOfferMeta()` and is overridden by live API content when the
   cache is warm.
2. **Auto-matched products** — the catalog sync sweeps `/products/search` per
   Puerto Rico destination and scores products against every published beach
   (`viator_beach_products`). Matched products fill the remaining product
   slots on beach pages. Matching requires beach-name or activity-tag
   relevance; geography alone never qualifies.
3. **Regional browse** — one destination campaign per region
   (municipality > region > global) remains the always-available fallback.

## Configuration

Set these outside the web root:

```dotenv
VIATOR_PID=P00000000
VIATOR_API_KEY=replace-with-partner-api-key
VIATOR_API_BASE_URL=https://api.viator.com/partner
VIATOR_API_ENABLED=1
VIATOR_SYNC_TTL_HOURS=24
```

Use `https://api.sandbox.viator.com/partner` only for testing. Never expose
the API key to browser JavaScript or commit it.

## Endpoints used (Basic-access affiliate tier)

- `GET /products/{code}` + `GET /availability/schedules/{code}` — curated set
- `GET /destinations` — Puerto Rico destination subtree → `viator_destinations`
- `GET /products/tags` — tag taxonomy → `viator_tags`
- `POST /products/search` — top products per destination (EN + ES) →
  `viator_products` with `source = catalog_search`

All requests pass `campaign-value` so the `productUrl` returned by the API
carries attribution. Curated syncs use the product campaign slug; catalog
sweeps use the regional browse campaign slug for the destination, which is
also the campaign the click is logged under.

## Sync scripts

```bash
# Hourly: refresh the curated set (EN + ES)
php scripts/sync-viator-products.php

# Nightly: taxonomy + destination sweeps + beach auto-matching
php scripts/sync-viator-catalog.php

# Recompute matches from the existing cache without touching the API
php scripts/sync-viator-catalog.php --match-only
```

```cron
17 * * * *  cd /var/www/beach-finder && php scripts/sync-viator-products.php >> logs/viator-sync.log 2>&1
41 3 * * *  cd /var/www/beach-finder && php scripts/sync-viator-catalog.php >> logs/viator-catalog.log 2>&1
```

Both scripts record runs in `viator_sync_runs` (catalog runs carry
`"kind":"catalog"` in `summary_json`). The API client retries HTTP 429/5xx
with backoff and honors `Retry-After`; a 401 aborts the run immediately so a
bad key cannot burn the rate budget. Catalog products absent from every sweep
for 7+ days are marked `STALE` and drop out of matching. An `INACTIVE`
curated product is removed from exact placements and the regional browse
campaign is shown instead.

## Initial activation

```bash
php scripts/migrate.php
php scripts/sync-viator-products.php
php scripts/sync-viator-catalog.php
php scripts/test-viator-api.php
php scripts/test-viator-curation.php
php scripts/test-viator-matching.php
curl -sS https://www.puertoricobeachfinder.com/api/health/viator.php
```

Until the first successful sync, curated cards fall back to editorial content
and manual affiliate links, auto-matched slots stay empty, and browse cards
work as plain campaign links — nothing breaks without the API.

## Auto-matching rules

`viatorRebuildBeachMatches()` scores every ACTIVE cached product against
every published beach:

- +60 beach-name phrase in product title / +45 distinctive single token
- +25 product destination matches the beach's municipality destination
  (`viator_municipality_destinations`, seeded + name-matched from taxonomy)
- +20 municipality name in product title
- +10 per beach tag ↔ product keyword/tag hit (snorkeling, surfing, diving,
  fishing, kayaking), capped at 30
- +5 for ≥4.5 rating with ≥50 reviews

Threshold 35, top 2 per beach, and at least one name or tag reason is
required — destination-only matches (e.g. a San Juan food tour) are rejected.
To suppress a bad match permanently, set its `viator_beach_products.status`
to `blocked`; rebuilds preserve blocked rows and never resurface them.

## Placements and rendering

- Beach pages (`renderToursSection`): up to 2 product cards (curated first,
  then auto-matched) + 1 regional browse card. Auto cards use the regional
  campaign as their click bucket with `match_type=auto_product`.
- Guide pages (`renderGuideToursSection` via `components/guide/tours.php`):
  driven by `guide_tour_placements`, `match_type=curated_guide`, context
  `page_type=guide`. Add or reorder placements in that table; no code change
  needed.

## Attribution and reporting

- API `productUrl` values are stored and used exactly as returned by Viator.
- `/go` resolution order for Viator product clicks: campaign-scoped link
  (`viator_product_links`) → product-level URL (`viator_products.product_url`)
  → manual campaign URL + `pid`/`mcid`/`campaign` params. The local click ID
  is never appended to API-generated URLs.
- Viewport impressions land in `referral_impressions` with `match_type`
  (`curated_beach`, `curated_guide`, `auto_product`, `regional_browse`) and
  `api_hydrated`; clicks remain in `referral_clicks`.
- Export campaign performance from Viator Performance Trends and upload it at
  `/admin/referrals?tab=revenue`. Common header variants are normalized.
  Aggregate imports should include:

```text
date,campaign,visitors,bookings,gross booking value,gross commission,currency,product code
```

Unknown campaign values are imported for reconciliation but flagged in the
import job.

## Health and alerting

`GET /api/health/viator.php` returns 503 until the curated cache is warm and
fresh, and reports catalog stats (destinations, search products, matched
beaches, guide placements, last catalog run). Point an uptime check at it so
a failing key or stalled cron pages someone instead of silently reverting the
site to editorial fallbacks.

## Measurement gate

Expansion is validated by data, not assumption: the referral dashboard
compares `curated_*` and `auto_product` placements against `regional_browse`
using CTR, Viator visitor conversion, commission, and commission per 1,000
impressions. Keep a placement tier only where it meaningfully outperforms the
browse fallback on a sample of at least 1,000 impressions or eight weeks of
data; prune auto matches (`blocked`) or curated placements that do not.
