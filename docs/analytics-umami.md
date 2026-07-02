# Analytics Integration (GA4 + client wrapper)

> **Update (2026-07):** Google Analytics 4 (gtag) is now the primary analytics sink. The
> `window.bfTrack()` wrapper routes every custom event to GA4 via `gtag('event', ...)`. The
> legacy Umami path documented below is retained but disabled in production (`UMAMI_ENABLED=0`);
> PostHog is also dual-sent when present. Enable GA4 by setting `GA_MEASUREMENT_ID` (see below).

This repo ships a thin client wrapper (`public/assets/js/analytics.js`) that is safe when
analytics is disabled or blocked, and forwards events to whichever sinks are loaded.


## Configuration

**GA4 (primary):**

- `GA_MEASUREMENT_ID=G-XXXXXXXXXX` — enables the gtag.js tag (injected in `components/header.php`)
  and activates the `bfTrack() → gtag('event', ...)` routing. Leave empty to disable.

**Umami (legacy, optional):** set these in `.env` (see `.env.example`):

- `UMAMI_ENABLED=1`
- `UMAMI_SCRIPT_URL=https://cloud.umami.is/script.js`
- `UMAMI_WEBSITE_ID=...`
- `UMAMI_DOMAINS=puertoricobeachfinder.com,www.puertoricobeachfinder.com` (optional)

The script tag is injected in `components/header.php` only when `UMAMI_ENABLED=1` and `UMAMI_WEBSITE_ID` is non-empty.
`inc/security_headers.php` also extends CSP allowlists using `UMAMI_SCRIPT_URL` host when Umami is enabled.

## Client wrapper

- `public/assets/js/analytics.js` defines `window.bfTrack(eventName, props)`.
- Events are routed to **GA4** via `window.gtag('event', eventName, props)` whenever the gtag tag is loaded (primary sink).
- If Umami is available, events are also forwarded via `window.umami.track(eventName, props)` (legacy path).
- If PostHog is present, events are dual-sent via `window.posthog.capture(eventName, props)`.
- A persistent anonymous id cookie `BF_ANON_ID` is created (180 days) and included in event props, plus `authenticated` and `user_id` when available.
- In `prod`, `bfTrack()` logs a one-time console warning when Umami is unavailable.
- Add `?bf_analytics_probe=1` to any page URL to fire `health_analytics_probe` and send a client probe beacon to `/api/health/analytics.php`.

## Funnel event map (minimal schema)

Activation:

- `A1_list_to_detail_click`: fired when the beach drawer swaps in (HTMX) after a list "Details" click.
- `A2_quiz_complete`: fired after quiz match results are returned/rendered.
- `A3_directions_click`: fired from directions links marked with `data-bf-track="directions"`.

Lead capture:

- `L1_results_sent`: fired when quiz results are sent (email/SMS/WhatsApp flow).
- `L2_list_sent`: fired when a list page capture form is submitted.

Signup attribution:

- `S1_signup_from_quiz`: fired on first authenticated page view when URL contains `?src=quiz`.
- `S2_signup_from_checkin`: fired on first authenticated page view when URL contains `?src=checkin`.

UGC:

- `U1_checkin_submitted`: fired after a check-in is successfully submitted.

Referral (user-to-user invite loop — see `inc/invite.php`):

- `referral_prompt_shown`: fired when a referred guest (arrived via `/?ref=CODE`, `bf_ref` cookie set) is shown the invite-aware signup popup in `components/footer.php`. Param: `referrer` (referrer's first name).
- `referral_cta_click`: fired when that guest clicks the popup's "Continue with Google" CTA. Param: `referrer`.

Other utility events (implementation-specific):

- `share_click`, `share_copy_link` from `public/assets/js/share.js`
- `favorite_add`, `favorite_remove` (favorites toggles)

## Implementation references

- Umami script injection: `components/header.php`
- Dynamic CSP host allowlist: `inc/security_headers.php`
- Global user meta for analytics: `components/footer.php`
- Tracking wrapper + delegated listeners: `public/assets/js/analytics.js`
- Share tracking: `public/assets/js/share.js`
- Quiz results landing + tokenized page: `public/quiz-results.php`
- Analytics health endpoint: `public/api/health/analytics.php`
- CI/deploy tag check: `scripts/check-analytics-umami.php`
- Synthetic browser smoke script: `scripts/synthetic-analytics-probe.sh`

## Operational checks

> These probes target the **legacy Umami** tag. With Umami disabled in prod they will report
> `umami_tag_present: false` — that is expected, not an outage. GA4 delivery is verified in the
> GA4 DebugView / Realtime reports instead. (Migrating these probes to GA4 is a tracked follow-up.)

Configuration + page probe:

```bash
curl -sS "https://www.puertoricobeachfinder.com/api/health/analytics.php?page_probe=1&network_probe=1"
```

Expected in production:
- `ok: true`
- `checks.config.enabled: true`
- `checks.page_probe.umami_tag_present: true`
- `checks.page_probe.umami_website_id_present: true`

Rendered HTML guardrail:

```bash
php scripts/check-analytics-umami.php \
  --urls=https://www.puertoricobeachfinder.com/,https://www.puertoricobeachfinder.com/best-beaches \
  --expect-script-host=cloud.umami.is
```

Synthetic browser probe (headless):

```bash
scripts/synthetic-analytics-probe.sh https://www.puertoricobeachfinder.com
```

This loads a page with `?bf_analytics_probe=1`, then verifies `/api/health/analytics.php?page_probe=1` reports a fresh client probe and Umami availability.

## Notes

- Tokenized quiz results pages (`/quiz-results?token=...`) are `noindex` to avoid indexing user-specific pages.
- The canonical `/quiz-results` route exists as a landing URL and is included in `public/sitemap.php`.
