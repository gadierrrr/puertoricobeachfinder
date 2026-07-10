# Viator affiliate product hydration

The site keeps its eight editorial beach/product matches and uses Viator's
Basic Affiliate API only for changing product facts and exact attributed URLs.
Checkout remains on Viator.

## Configuration

Set these outside the web root:

```dotenv
VIATOR_PID=P00000000
VIATOR_API_KEY=replace-with-partner-api-key
VIATOR_API_BASE_URL=https://api.viator.com/partner
VIATOR_API_ENABLED=1
VIATOR_SYNC_TTL_HOURS=24
```

Use `https://api.sandbox.viator.com/partner` only for testing. Never expose the
API key to browser JavaScript or commit it.

## Initial activation

```bash
php scripts/migrate.php
php scripts/sync-viator-products.php
php scripts/test-viator-api.php
php scripts/test-viator-curation.php
curl -sS https://www.puertoricobeachfinder.com/api/health/viator.php
```

The sync requests English and Spanish product content for all eight curated
campaigns. Product cards fall back to their existing editorial content and
manual affiliate links until the first successful sync.

## Scheduling

An hourly refresh keeps the small curated set current and also lets a newly
issued Viator key recover automatically after its activation delay:

```cron
17 * * * * cd /var/www/beach-finder && php scripts/sync-viator-products.php >> logs/viator-sync.log 2>&1
```

The script records every run in `viator_sync_runs`. An inactive API product is
removed from exact placements and the existing regional browse campaign is
shown instead.

## Attribution and reporting

- API calls set `campaign-value` to the existing referral campaign slug.
- `productUrl` is stored and used exactly as returned by Viator.
- `/go` continues to record the local click but does not append the local click
  ID to an API-generated Viator URL.
- Viewport impressions are recorded in `referral_impressions`; clicks remain in
  `referral_clicks`.
- Export campaign performance from Viator Performance Trends and upload it at
  `/admin/referrals?tab=revenue`. Common header variants are normalized.
- The referral dashboard compares exact beach products against regional browse
  fallbacks using CTR, Viator visitor conversion rate, commission, and
  commission per 1,000 impressions.

For aggregate imports, include these concepts as columns (names are flexible):

```text
date,campaign,visitors,bookings,gross booking value,gross commission,currency,product code
```

Unknown campaign values are imported for reconciliation but flagged in the
import job. They do not count toward exact-vs-regional proof until mapped to a
known referral campaign.

## Decision rule

Do not expand beyond the initial eight products until exact placements have a
meaningful sample and outperform regional fallbacks. Use at least 1,000 exact
impressions or eight weeks of data, and require a positive commission RPM that
meets the site's internal payback threshold.
