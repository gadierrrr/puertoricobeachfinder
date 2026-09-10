# Analytics integration (GA4)

Updated 2026-09-10. The filename is retained for existing links; Umami is no longer configured. `GA_MEASUREMENT_ID` enables the GA4 tag in the shared header. `window.bfTrack(name, props)` sends custom events to GA4 and also PostHog when present. Blocking analytics must never prevent site actions.

## Outcomes and supporting actions

| Event | Trigger | Interpretation |
| --- | --- | --- |
| `sign_up` | Successful verification creates a new email or Google user | New verified account, not a returning login |
| `generate_lead` | Advertising inquiry successfully stored | Inquiry, not a sale or revenue |
| `A3_directions_click` | Visitor opens beach directions | Intent to visit, not a confirmed arrival |
| `favorite_add` / `favorite_remove` | Successful favorite persistence | Saved/removed beach |
| `nearby_beaches_click` | Sticky mobile Nearby link | Navigation to alternatives |
| `visit_planner_click` | Practical planning links | Navigation to planning resources |

The first four positive events (`sign_up`, `generate_lead`, `A3_directions_click`, `favorite_add`) are marked as key events in GA property 543500092. Signups and inquiries have no default monetary value and count once per event. Existing unrelated key events were preserved. Old `S1_signup_from_quiz` / `S2_signup_from_checkin` settings remain historical; the URL-based implementation is removed because it counted returning users as signups.

`inc/analytics.php` queues verified signups/inquiries in the session. The next shared-header page consumes them; the client additionally deduplicates the event ID within the tab. Reloads, duplicate inquiries, failed verification and honeypot responses do not create another outcome. IDs are used locally for deduplication and are not analytics properties. Only method/source/package/category context is permitted. Delivery remains subject to consent, blockers and browser/network availability; this is not a replacement for the database as the outcome system of record.

Favorite HTMX responses expose `X-Beach-Favorite: added|removed`. Only successful responses count; tracking does not inspect heart emoji. Other fetch-based favorite paths keep their existing success tracking. Both full and minimal footers load the wrapper.

## Traffic quality

- Nonproduction pages, webdriver sessions and explicit `bf_analytics_probe` / `design` test URLs set `traffic_type=internal` and `debug_mode=true` before GA config.
- GA already has an **active** Internal Traffic exclusion for `traffic_type` exactly `internal`; verified September 10. This existing filter excludes matching incoming events. It does not repair historical reports.
- Saved comparison **Organic Search baseline** matches Session default channel group exactly `Organic Search`. Apply it when evaluating search recovery separately from the Direct traffic anomaly. Existing Mobile and Direct comparisons remain available.
- No country-wide or blanket Direct-traffic exclusion was created. Server logs and GA geography alone do not prove that every such visitor is a bot.
- The configured GA page location removes token, code, state, redirect, email and ref query parameters and URL fragments. Campaign parameters remain available for attribution.
- Auth and advertising pages have their own content groups. Landing-page visits are not conversions.

## Validation

```bash
node --test scripts/test-analytics-events.js
# Disposable dev database only; creates and removes temporary verification fixtures:
php scripts/test-analytics-outcomes.php
php scripts/check-analytics-ga.php --urls=http://127.0.0.1:8083/,http://127.0.0.1:8083/login
```

The local inquiry flow was also checked end to end: successful persistence produces one event, reload/duplicate/honeypot produce none. Test submissions must use a disposable local DB with no admin recipients or live email delivery.

The GA health endpoint and HTML checks verify configuration/tag presence, not guaranteed ingestion. Do not create fake production signups or inquiries to inflate key-event counts. Internal filtering can prevent labelled probes from appearing in normal reports.

For server-side traffic triage, `scripts/traffic-log-summary.py` reads nginx combined logs and prints aggregate counts without client addresses or query strings. Non-asset requests include API calls and are not equivalent to GA sessions.
