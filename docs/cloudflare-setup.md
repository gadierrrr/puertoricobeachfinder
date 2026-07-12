# Cloudflare (free tier) in front of beach-prod

Why: prod is a 2-core/2GB VPS with no CDN that swaps under load (2026-07-12 incident). Cloudflare's free tier caches static assets at the edge, terminates TLS close to visitors, and shields the origin — protecting Core Web Vitals as traffic grows. DNS-level change, no application code.

## Steps (needs the domain registrar / Cloudflare account — manual)

1. Add `puertoricobeachfinder.com` as a site in Cloudflare (Free plan). Let it import existing DNS records; verify `A`/`AAAA` for apex and `www` point at the VPS IP, both **Proxied** (orange cloud).
2. Switch the domain's nameservers at the registrar to the two Cloudflare NS hosts it assigns. Wait for activation (minutes to a few hours).
3. SSL/TLS → set mode to **Full (strict)** (the origin already serves valid Let's Encrypt certs). Do NOT use "Flexible" — it breaks canonical https detection.
4. Speed → enable **Brotli**. Leave Rocket Loader OFF (inline scripts use CSP nonces; Rocket Loader would break them).
5. Caching: default "standard" is fine. The nginx conf already sends `30d immutable` for static assets, which Cloudflare respects. Optionally add a Cache Rule: `/assets/*`, `/images/*`, `/uploads/*` → Cache Eligible, Edge TTL "respect origin".
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

## Verification

- `curl -sI https://www.puertoricobeachfinder.com/assets/css/styles.css | grep -i cf-cache-status` → `HIT` on second request.
- `curl -sI https://www.puertoricobeachfinder.com/ | grep -i server` → `cloudflare`.
- Check GA4 still records distinct visitor IPs/geo (real-IP working) and the internal-traffic IP filter still matches.
- PageSpeed Insights before/after; CrUX field data ~28 days later.
