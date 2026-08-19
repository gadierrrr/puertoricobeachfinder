# Cloudflare (free tier) in front of beach-prod

Why: prod is a 2-core/2GB VPS with no CDN that swaps under load (2026-07-12 incident). Cloudflare's free tier caches static assets at the edge, terminates TLS close to visitors, and shields the origin — protecting Core Web Vitals as traffic grows. DNS-level change, no application code.

## Steps (needs the domain registrar / Cloudflare account — manual)

1. Add `puertoricobeachfinder.com` as a site in Cloudflare (Free plan). Let it import existing DNS records; verify `A`/`AAAA` for apex and `www` point at the VPS IP, both **Proxied** (orange cloud).
2. Switch the domain's nameservers at the registrar to the two Cloudflare NS hosts it assigns. Wait for activation (minutes to a few hours).
3. SSL/TLS → set mode to **Full (strict)** (the origin already serves valid Let's Encrypt certs). Do NOT use "Flexible" — it breaks canonical https detection.
4. Speed → enable **Brotli**. Leave Rocket Loader OFF (inline scripts use CSP nonces; Rocket Loader would break them).
5. Caching: default "standard" covers static assets — the nginx conf already sends `30d immutable`, which Cloudflare respects. Optionally add a Cache Rule: `/assets/*`, `/images/*`, `/uploads/*` → Cache Eligible, Edge TTL "respect origin". Default "standard" does **not** cache HTML; see [HTML edge caching](#html-edge-caching) below.
6. Purge cache after each deploy that changes CSS/JS (or rely on the `?v=` query-string versioning already used — bumping the version busts edge cache automatically, so purging is rarely needed).

## Origin changes (in this repo)

Restore real client IPs so logs, rate limiting, and analytics keep working. Add to the nginx server block (deploy/nginx/beach-finder.conf):

```nginx
    # Cloudflare real-IP restoration (IP ranges: https://www.cloudflare.com/ips/)
    include /etc/nginx/cloudflare-real-ip.conf;
    real_ip_header CF-Connecting-IP;
```

Generate `/etc/nginx/cloudflare-real-ip.conf` on the box (and re-run monthly via cron if desired):

```bash
{ curl -s https://www.cloudflare.com/ips-v4 ; echo; curl -s https://www.cloudflare.com/ips-v6; } \
  | sed 's/^/set_real_ip_from /; s/$/;/' | sudo tee /etc/nginx/cloudflare-real-ip.conf
sudo nginx -t && sudo systemctl reload nginx
```

Optionally firewall ports 80/443 to Cloudflare's IP ranges only, so crawlers can't bypass the CDN and hit the weak origin directly.

## HTML edge caching

Cloudflare's default cache covers static extensions only, so **HTML was served `cf-cache-status: DYNAMIC`** — every request, including every Googlebot crawl of all 1,088 sitemap URLs, reached PHP on the 2-core box (measured TTFB 1.3–2.1s, with spikes to 5–9s). Enabling HTML caching takes two halves; both must be in place.

### Origin half (shipped)

`inc/security_headers.php::pageIsEdgeCacheable()` decides per response. A response is edge-cacheable only when **all** hold:

- method is `GET`
- no `BEACH_FINDER_SESSION` cookie (anonymous — sessions only start when that cookie already exists, so anonymous visitors never have one and never receive one)
- `http_response_code()` is 200
- no `?ref=` (sets an invite cookie) and no `?rdedit=1` (admin preview)
- path is not under `/admin`, `/api/`, `/auth/`, `/login`, `/logout`, `/profile`, `/favorites`, `/lists`, `/list`, `/onboarding`, `/verify`, `/quiz-results`, `/go`, `/ad-out`, `/local-out`
- `resolveLocaleFromPath()` returns a locale — i.e. **the URL alone pins the language**. Without this, `getCurrentLanguage()` would fall back to the session/`lang` cookie and a cached copy could serve one visitor's language to everyone.

Cacheable responses send:

```
Cache-Control: public, max-age=0, s-maxage=300, must-revalidate
CDN-Cache-Control: public, s-maxage=300, stale-while-revalidate=86400
Vary: Accept-Encoding
```

Browsers still revalidate every navigation (`max-age=0`), so nobody sees a stale shell; the revalidation just terminates at the edge instead of at PHP. `CDN-Cache-Control` governs Cloudflare and takes precedence there. Everything else keeps `private, no-cache` with `Vary: Cookie, Accept-Language`.

### Cloudflare half (manual — dashboard)

Caching → Cache Rules → **Create rule**:

- **Name:** `HTML edge cache (anonymous)`
- **If:** Custom filter expression —
  ```
  (not http.cookie contains "BEACH_FINDER_SESSION")
  ```
- **Then:** Cache eligibility → **Eligible for cache**
- **Edge TTL:** *Use cache-control header if present, bypass cache if not*
- **Browser TTL:** *Respect origin TTL*

Leave Rocket Loader off (CSP nonces). Purge everything after a deploy that changes HTML.

### Tradeoff on record

The CSP is `'nonce-…' 'strict-dynamic'`, and the nonce is regenerated per response. A cached page therefore pins one nonce for the life of the cache entry, so visitors served the same cached copy share it. This was accepted deliberately when edge caching was enabled: exploiting it needs a separate injection vector, and a reflected-XSS URL is a different cache key so it would not be served from cache anyway. **Keep the TTL short.** If per-request nonces become a requirement, the options are a Cloudflare Worker that rewrites the nonce in both body and CSP header, or an origin nginx `fastcgi_cache` + `sub_filter` substitution.

## Verification

After enabling the Cache Rule:

```bash
# Anonymous HTML should go MISS -> HIT
for i in 1 2; do
  curl -sI https://www.puertoricobeachfinder.com/beach/montones-beach \
    | grep -iE "cf-cache-status|cache-control|age"
done

# A session cookie must always BYPASS (never serve another visitor a cached page)
curl -sI -H "Cookie: BEACH_FINDER_SESSION=test" \
  https://www.puertoricobeachfinder.com/beach/montones-beach | grep -i cf-cache-status
```

Expect `MISS` then `HIT` for the anonymous pair, and `BYPASS`/`DYNAMIC` for the cookie'd request. Re-measure TTFB (`curl -w '%{time_starttransfer}'`) — it should drop from ~1.5s to well under 200ms on a HIT.

- `curl -sI https://www.puertoricobeachfinder.com/assets/css/styles.css | grep -i cf-cache-status` → `HIT` on second request.
- `curl -sI https://www.puertoricobeachfinder.com/ | grep -i server` → `cloudflare`.
- Check GA4 still records distinct visitor IPs/geo (real-IP working) and the internal-traffic IP filter still matches.
- PageSpeed Insights before/after; CrUX field data ~28 days later.
