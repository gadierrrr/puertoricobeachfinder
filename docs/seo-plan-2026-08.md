# SEO update plan — August 2026

**Analysis date:** 2026-08-19
**Data window:** GSC `sc-domain:puertoricobeachfinder.com`, 2026-07-19 → 2026-08-16 (28d), plus calendar-month totals Feb–Aug 2026
**Supersedes:** the July 2026 "SEO 2x" plan (original plan file was pruned; its record survives in the session memory `seo-2x-initiative.md`)

---

## 1. Where the site actually is

### Authoritative totals (no-dimension GSC query)

| Window | Clicks | Impressions | CTR | Avg pos |
|---|---:|---:|---:|---:|
| 2026-07-19 → 08-16 (28d) | 5,755 | 450,305 | 1.28% | 7.8 |
| 2026-06-21 → 07-18 (prior 28d) | 6,216 | 456,733 | 1.36% | 7.7 |
| **Change** | **−7.4%** | **−1.4%** | **−0.08pp** | **−0.1** |

### Calendar-month trend

| Month | Clicks | Impressions | CTR | Avg pos | Clicks/day |
|---|---:|---:|---:|---:|---:|
| Feb 2026 | 163 | 21,965 | 0.74% | 9.3 | 5.8 |
| Mar | 1,727 | 169,835 | 1.02% | 8.1 | 55.7 |
| Apr | 1,817 | 185,552 | 0.98% | 8.2 | 60.6 |
| May | 2,689 | 267,779 | 1.00% | 9.1 | 86.7 |
| Jun | 4,918 | 417,754 | 1.18% | 7.9 | 163.9 |
| Jul | 7,463 | 532,271 | 1.40% | 7.6 | 240.7 |
| Aug 1–16 | 2,615 | 221,245 | 1.18% | 8.0 | 163.4 |

**Growth has stalled.** August's daily click rate (163.4) is statistically identical to June's (163.9) and 32% below July's peak (240.7). Impressions are flat, position held (7.6 → 8.0), and the click decline is a CTR decline (1.40% → 1.18%). Projected August close: **~5,065 clicks**.

The July plan's target was 10,000 clicks/mo by October. Current run-rate is **half that, with a flat trajectory**.

> **Caveat on seasonality:** the site launched Feb 2026, so there is **no year-over-year history** to confirm a seasonal explanation. The Jul→Aug drop coincides with the Puerto Rico school-year restart, which is a plausible demand explanation, but it is an assumption — not a measured fact. Section 6 proposes a metric that is robust to this.

---

## 2. What the July plan already delivered

Verified against prod (`beach-prod`, commit `2d1c04b`) and the prod DB:

| Item | Status |
|---|---|
| Phase-1 technical batch (hreflang, sitemap alternates, 301s, schema, LCP) | ✅ deployed `82ca439` |
| Guide CTR fixes (transportation/safety/best-time links + ES accents) | ✅ deployed `a655ec2`, `2d1c04b` |
| Cloudflare migration | ✅ completed 2026-08-16 |
| **"448 zero-prose beach pages" — the old plan's headline lever** | ✅ **done.** 434/434 published beaches have EN *and* ES descriptions; 432 ≥300 chars |
| **~26 duplicate-beach merge clusters** | ✅ **done.** 0 name-duplicate clusters remain |
| **735 missing ES `pages.*` keys** | ✅ **done.** EN and ES key sets are identical |
| Municipality page coverage | ✅ complete — 42 muni pages for the 42 municipalities that have beaches |

**Three of the old plan's four deferred "big levers" are already closed.** The plan below is built on what the data says is binding *now*, not on that backlog.

Still open from the old list: SEO title/description overrides (48/434 beaches), `local_tips` (0/434 populated), English head-term push, `/beaches-near-me`.

---

## 3. Diagnosis

### 3.1 Impressions are concentrated in the worst-converting template

By page template (top-1000 pages slice; shares are head-weighted and the slice sums ~6.7% above the property total, a normal GSC dimension artifact):

| Template | URLs | Clicks | Impressions | Share of impr | CTR | Avg pos |
|---|---:|---:|---:|---:|---:|---:|
| **Beach detail** | 811 | 4,093 | 363,706 | **75.7%** | **1.13%** | 7.9 |
| Municipality | 81 | 1,110 | 45,427 | 9.5% | **2.44%** | 6.8 |
| Guide | 32 | 149 | 27,019 | 5.6% | **0.55%** | 9.2 |
| Collection | 44 | 203 | 19,857 | 4.1% | 1.02% | 10.9 |
| Proximity | 6 | 128 | 19,771 | 4.1% | **0.65%** | 8.4 |
| Other / home | 26 | 120 | 4,814 | 1.0% | 2.49% | 10.6 |

Three-quarters of the site's search visibility sits on the template that converts at less than half the rate of the municipality template.

### 3.2 The binding constraint is position, and the cliff is at the top-5 line

CTR by position band, against rough industry medians for informational SERPs:

**Beach detail pages**

| Band | Clicks | Impressions | CTR | Benchmark | vs bench |
|---|---:|---:|---:|---:|---:|
| pos 3–5 | 783 | 20,827 | 3.76% | 7.0% | 0.54× |
| **pos 5–8** | 2,233 | **176,333** | **1.27%** | 3.5% | 0.36× |
| **pos 8–11** | 958 | **149,956** | **0.64%** | 2.0% | 0.32× |
| pos 11–16 | 78 | 12,656 | 0.62% | 1.2% | 0.51× |

**Municipality pages**

| Band | Clicks | Impressions | CTR | Benchmark | vs bench |
|---|---:|---:|---:|---:|---:|
| pos 3–5 | 484 | 7,922 | **6.11%** | 7.0% | **0.87×** |
| pos 5–8 | 546 | 29,605 | 1.84% | 3.5% | 0.53× |
| pos 8–11 | 66 | 6,110 | 1.08% | 2.0% | 0.54× |

Two things fall out:

1. **There is a ~3× CTR cliff crossing out of the top-5.** Beach pages: 3.76% → 1.27%. Municipality pages: 6.11% → 1.84%. On a mobile SERP (81% of impressions) carrying a Maps pack for place queries, position 6+ is far below the fold.
2. **When the site ranks top-5 on list-intent queries it performs normally** (muni pos 3–5 = 0.87× benchmark). The site is not bad at winning clicks; it is bad at being in the top 5.

**326,289 beach-page impressions — 72% of all site impressions — sit in the pos 5–11 dead zone converting at 0.32–0.36× benchmark.**

### 3.3 Hypotheses tested and rejected

Two plausible explanations were tested against data and **do not hold**. Recording them so they don't get re-litigated:

- **"Beach pages have weak titles/metas."** Rejected. The pages *with* hand-written `seo_title`/`seo_description` overrides have the *worst* CTR (`ojo-de-agua-beach` 0.87%, `montones-beach` 0.61%) while `balneario-de-dorado` — a **default** title — gets 6.75%. The overrides were applied to high-impression/low-rank pages, so the correlation is backwards. The copy on those overrides is good; position is what differs (3.6 vs 9.2).
- **"434 near-duplicate template pages are thin-content capped."** Rejected. Proper visible-text extraction across four beach pages shows **52–57% of each page's text is unique** (~1,300–1,550 unique words/page) against only 825 words of shared boilerplate. Beach-page content is healthy.

Also tested: **impression targeting on beach pages is fine** — only 5% of beach-page impressions in the top-2,000 query/page pairs are off-topic, and the on-topic head converts at 2.47%. The scatter problem is real but it is **specific to guides** (see 3.4).

### 3.4 Two templates are genuinely broken

- **Guides** — 27,019 impressions produce **149 clicks (0.55%)**, 0.12–0.28× benchmark. **95% of guide impressions are off-topic** (query shares no token with the page subject). This is the scattered-broad-match pattern diagnosed in July, and the July fix (adding inbound links from beach pages) did not resolve it. `/guides/beach-safety-tips`: 10,220 impr → 21 clicks (0.21%). `/guides/bioluminescent-bays`: 2,911 impr → 4 clicks (0.14%) at pos 15.9.
- **Proximity** — 19,771 impressions → **128 clicks (0.65%)**, 0.22–0.28× benchmark. `/beaches-near-san-juan` alone is 12,289 impr → 54 clicks (0.44%) at pos 9.2. Meanwhile the queries exist and are large: "beaches near me" 2,737 impr @ pos 7.4, "beach near me" 2,477 @ 7.4, "playas cerca de mi" 1,078 @ 5.6. There is still **no `/beaches-near-me` page**.

### 3.5 English underperforms Spanish on every single template

| Template | EN CTR | EN pos | ES CTR | ES pos |
|---|---:|---:|---:|---:|
| Beach detail | 0.93% | 8.0 | 1.30% | 7.8 |
| Municipality | 1.47% | 8.9 | **2.74%** | **6.2** |
| Collection | 0.78% | 11.3 | 2.20% | 8.8 |
| Guide | 0.51% | 9.4 | 0.97% | 7.2 |
| Proximity | 0.50% | 8.8 | 1.12% | 6.9 |

By country: PR 4,386 clicks / 279,690 impr (1.57%) vs **US 1,152 clicks / 121,143 impr (0.95%)**. The US is 27% of impressions and 20% of clicks. English is a genuine second market being served at roughly 60% of the Spanish conversion rate.

### 3.6 Infrastructure: Cloudflare is caching zero HTML

```
cf-cache-status: DYNAMIC
cache-control: private, no-cache, max-age=0, must-revalidate
vary: Cookie, Accept-Language
```

The site moved behind Cloudflare on 2026-08-16 and gets **no HTML edge caching at all**. Every request — including every Googlebot crawl of all 1,088 sitemap URLs — hits PHP on a 2-core/2GB VPS.

Measured TTFB (steady state, repeated): **1.3–2.1s**, with observed spikes to **4.9s and 8.9s**. Page weight 190–307KB of HTML. Origin is running `pm.max_children = 25` on 1,967MB RAM with opcache on.

This is slow enough to suppress crawl rate and Core Web Vitals across the whole property, and it is the cheapest thing on this list to fix.

---

## 4. The plan

Ordered by (upside × confidence) ÷ effort. All click figures are per 28 days against the 5,755-click baseline.

### P0 — Edge-cache anonymous HTML ✅ **SHIPPED 2026-08-19**

Deployed as commit `d58ab06` on `origin/main`, plus a Cloudflare Cache Rule. See `cloudflare-setup.md` → *HTML edge caching* for the full contract.

**Measured result — TTFB 1.3–2.1s → ~0.11s on a cache HIT** (a ~13× improvement), on the template holding 76% of impressions:

| Path | MISS → HIT | TTFB |
|---|---|---:|
| `/beach/hobie-beach` | HIT → HIT | 0.124s |
| `/es/playa/ojo-de-agua-beach` | MISS → HIT | 0.122s |
| `/beaches-in-isabela` | MISS → HIT | 0.117s |
| `/es/playas-en-salinas` | MISS → HIT | 0.112s |
| `/guides/beach-safety-tips` | MISS → HIT | 0.115s |
| `/` | MISS → HIT | 0.104s |

Verified safe: a `BEACH_FINDER_SESSION` cookie always yields `private, no-cache` + `cf-cache-status: DYNAMIC` (logged-in pages are never cached); cached EN URLs serve `lang="en-US"` and ES URLs `lang="es-PR"` with no cross-contamination, including when a `lang=es` cookie is sent to an English URL.

**Gotcha worth remembering:** the zone's default Browser Cache TTL (4h, free plan) silently overrode the origin's `max-age=0`, so responses went out as `max-age=14400` — which would have let a visitor's browser serve the logged-out page for four hours after they signed in. Fixed by adding **Browser TTL = Respect origin TTL** to the Cache Rule. Always check the emitted `cache-control`, not just `cf-cache-status`.

**Upside:** removes the crawl-rate ceiling and the 5–9s spike tail; improves LCP sitewide. **Does not directly add clicks** — it unblocks everything else.

### P1 — Rich-result stars on the top 100 beaches *(weeks, high confidence)*

**Zero beaches currently qualify for `AggregateRating`** — 1 beach has any first-party review, 0 have the ≥3 the schema gate requires. The schema code is already written and gated; it has simply never had data.

Impression concentration makes this tractable:

| Top N beaches | Share of beach impressions |
|---|---:|
| 25 | 30.5% |
| 50 | 45.8% |
| **100** | **64.5%** |
| 150 | 76.4% |

Seeding ~3 reviews each on the **top 100 beaches** (~300 reviews) covers 64.5% of beach impressions ≈ **49% of all site impressions**.

- Prioritise by GSC impressions, not by traffic.
- Solicit through the existing review UI: on-page prompt after check-in/photo upload, plus the email list.
- Do **not** map `google_rating` (333 beaches have one) into `AggregateRating` — using third-party ratings as first-party markup is a structured-data policy violation and risks a manual action.

**Upside:** stars typically lift CTR 15–30% at unchanged position. On 4,093 beach clicks: **+614 to +1,228 clicks/28d**. This is the largest lever that does *not* require ranking gains.

### P2 — Fix the two broken templates *(weeks, high confidence)*

**Guides** (27,019 impr → 149 clicks). The July fix treated this as a linking problem; the data says it is a *targeting* problem — 95% off-topic impressions. Per guide, decide one of:
- **Re-target** to the intent actually being matched, where that intent has volume;
- **Consolidate** thin guides into the beach/muni pages that already rank;
- **De-index** the ones that earn broad-match noise and no clicks (`bioluminescent-bays` at 0.14% CTR / pos 15.9 is the clearest candidate), redirecting equity to the relevant collection page.

Reaching a 2.0% CTR on this pool = **+392 clicks/28d**.

**Proximity** (19,771 impr → 128 clicks). Build the `/beaches-near-me` page (still unbuilt from the July list) with real geolocation, and rework `/beaches-near-san-juan` — 12,289 impressions at 0.44% is the single worst-converting high-volume URL on the site. Reaching muni-level 2.44% = **+354 clicks/28d**.

### P3 — English market push *(weeks–months, moderate confidence)*

Closing the EN↔ES gap is worth **+751 clicks/28d** if EN reached the PR CTR of 1.57% on its existing 121,143 US impressions — without a single new ranking.

- EN municipality pages sit at pos 8.9 vs ES 6.2 on identical underlying content: the ES versions are simply better optimised. Port what works.
- EN collections at pos 11.3 are the weakest surface on the site.
- Target US-intent head terms ("best beaches in puerto rico" was 3,108 impr at pos 16 with 0 clicks in the July baseline).

### P4 — Move beach pages from pos 5–8 into pos 3–5 *(months, lower confidence)*

The biggest theoretical prize and the slowest: 176,333 impressions sit in the pos 5–8 band at 1.27% against 3.76% one band up. Moving **25%** of them = **+1,098 clicks/28d**.

Content is already done, so the remaining levers are authority and signals:
- Tier internal linking toward the top-150 beaches by impression (currently every beach page carries a flat 56–59 internal links).
- Populate `local_tips` — empty on all 434 beaches — as genuinely differentiated, non-templated content.
- Freshness signals (conditions/sargassum recency) on the top-100 set.
- Off-site authority. This is the real constraint and it is not a code change.

Treat P4 as a program, not a sprint. Do not let it block P0–P3.

### Deliberately dropped

- **SEO title/description overrides for the remaining 386 beaches.** Section 3.3 shows no evidence they help; the 48 that exist do not outperform defaults. Revisit only if P4 moves pages into the top-5 where snippet copy starts to matter.
- **More municipality pages.** Coverage is already complete at 42/42.

---

## 5. Projected impact

| Lever | Clicks/28d | Confidence |
|---|---:|---|
| P1 stars (top 100 beaches) | +614 to +1,228 | High |
| P2 guides | +392 | High |
| P2 proximity | +354 | High |
| P3 English | +751 | Moderate |
| P4 beach rankings (25% of band) | +1,098 | Low–moderate |
| **Total** | **+3,209 to +3,823** | |

Against the 5,755 baseline that is **+56% to +66%**, landing at roughly **9,000–9,600 clicks/28d**.

**Honest read:** that reaches the neighbourhood of the old 10k/mo goal, but it depends on P4 — the slowest, least certain lever — for a third of the gain. P0–P3 alone deliver roughly **+2,100 clicks/28d (+37%)**, which is real and much more bankable. The October deadline from the July plan should be treated as retired.

---

## 6. Measurement

**Replace the raw monthly-clicks goal.** With no YoY history, a raw click target cannot distinguish a real gain from a seasonal swing, and August already demonstrated the failure mode.

Track instead, weekly, via the existing `scripts/gsc-snapshot.php` cron (Mondays 06:00 on beach-prod):

1. **CTR by template × position band** — the table in §3.2. This is the actual health metric and it is seasonality-neutral. Beach pos 5–8 CTR is the headline number.
2. **Share of beach impressions in pos ≤5** — the P4 tracker.
3. **Count of beaches with ≥3 reviews** — the P1 tracker; today it is 0.
4. **Guide off-topic impression share** — the P2 tracker; today 95%.
5. Clicks and impressions, reported as clicks/day and always compared to **June 2026 (163.9/day)**, never to the July peak.

---

## 7. Deployment note

Local `main` is currently **4 commits ahead of `origin/main`** (`ad41e3b` Cloudflare nginx mirror, `bced494` docs restructure, `7fe1db5` direct advertising platform, `ccd32c6` catch-up migrations). Prod runs `origin/main` = `2d1c04b`, so **none of those four are live**.

Any work from this plan needs that divergence resolved first — the advertising-platform commit in particular is a substantial unshipped change that should not ride along unnoticed with an SEO deploy.

Standard flow per project convention: push to `origin/main`, `ssh beach-prod`, `git pull && ./deploy.sh`.
