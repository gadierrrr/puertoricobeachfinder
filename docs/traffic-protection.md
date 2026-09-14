# Targeted traffic protection — September 14, 2026

The Singapore cohort has recurring near-zero-engagement bursts. Origin logs also
show login crawling and scanners, but these are not yet proven to be the same
clients. No country-wide block is enabled.

## Installed origin protection

`deploy/nginx/traffic-protection-http.conf` defines a shared per-visitor IP bucket
for `/login`, `/es/iniciar-sesion`, and `/auth/google/` (including their PHP forms).
The server snippet allows 30 requests/minute, burst 20, then returns HTTP 429.
This is a leaky bucket, not a fixed minute counter. It applies to GET/HEAD/POST;
existing application limits on magic-link submissions still apply. Original URL
matching survives locale and PHP rewrites. Query strings do not create new buckets.
Canonical redirects may execute before limiting; requests that reach login PHP
are limited. Callback routes, other pages, APIs, and assets have empty keys and
are exempt. There is no spoofable User-Agent exemption.

Cloudflare real-IP restoration already trusts only its configured network ranges.
The new JSON traffic log trusts country/Ray headers only when the connection peer
is in those same ranges. Unknown countries are recorded as `unknown`. The log
omits query strings, referrers, cookies and authorization headers, and uses the
existing daily Nginx log rotation (14 rotations). It is server-private and contains
visitor IPs; share aggregates, not raw log files.

Install as root on beach-prod with `python3 scripts/install-traffic-protection.py`.
The installer preserves existing routes/TLS, backs up all modified files, checks
`nginx -t`, reloads gracefully, and restores the previous configuration on failure.
Re-run after changing trusted Cloudflare ranges to regenerate the geo include.
This is an explicit infrastructure install; the normal PHP deploy does not run it.

Production installation backup: `/etc/nginx/backups/traffic-20260914T131405Z`.
To disable the protection, remove the single traffic-protection include from the
canonical server, run `nginx -t`, and reload Nginx. To fully restore the initial
state, restore backup `0` to the site configuration and remove the three newly
installed files (http snippet, server snippet and geo ranges), test, then reload.

## Verification

Live local-origin test: all three login URLs initially returned 200; a subsequent
35-request mixed-route burst returned 19 HTTP 200 and 16 HTTP 429. After exhausting
that bucket, homepage, beach, robots and search API returned 200 and the OAuth
callback returned 302. Spoofed Cloudflare country/client headers on the direct
connection were ignored. Trusted Cloudflare requests recorded a country. Query
strings were absent from the new log. These probes used one local IP, not visitor
addresses, and HEAD requests to avoid loading browser analytics.

## Remaining edge work

The Cloudflare dashboard requires sign-in. Correlate country/network/path/time
with the GA Singapore spikes before enabling a network- or country-specific rule.
Use managed challenges for confirmed abusive browser traffic and preserve verified
search crawlers; do not challenge OAuth callbacks or APIs. Bot-score rules depend
on the account plan. If no corresponding edge traffic exists, investigate GA-only
spam instead; origin limits cannot stop fabricated analytics events.

After 48 hours, compare country/path/rate-limit aggregates against GA, inspect
false positives, and verify legitimate organic engagement and sign-in/directions
events. Lower overall user counts alone are not proof of success.
