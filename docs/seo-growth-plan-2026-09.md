# Puerto Rico Beach Finder — search growth plan

Reviewed September 23, 2026, using the live Search Console property `sc-domain:puertoricobeachfinder.com`, current public HTTP responses, and repository source.

The opportunity is to turn existing visibility into visits and become the resource people trust for actually choosing and reaching a beach. Start with pages already earning impressions, strengthen their practical detail in English and Spanish, and connect them into useful town and trip-planning journeys.

## Current baseline

Search Console → Performance → Web → Compare last 28 days to previous period. Property totals below are rounded values displayed by the UI; percentage changes are approximate. They are not sums of the query table. The three-month overview covers June 22–September 21 and reports 15,892 clicks, about 1.28M impressions, 1.2% CTR, and position 7.9.

| Metric | Last 28 days | Previous 28 days | Change |
|---|---:|---:|---:|
| Clicks | ~2,920 | ~4,670 | −37% |
| Impressions | ~290,000 | ~387,000 | −25% |
| CTR | 1.0% | 1.2% | −0.2 percentage points |
| Average position | 8.2 | 7.9 | 0.3 worse |

Both visibility and click-through weakened. These aggregates cannot establish whether demand, ranking changes, result presentation, or site changes caused the decline. Seasonality is a hypothesis, not a measured explanation. Some losses involve intentionally held listings: Ojo de Agua was unpublished for identity verification on September 14. Do not republish uncertain information to recover clicks.

One highly visible query, `combate beach pr google suggest autocomplete`, has 2,315 impressions and zero clicks; another includes `google search trends keywords`. These are unusual research-like queries. Inspect the query mix before interpreting all impressions as traveler demand or rewriting the Combate page around those terms.

## Indexing: distinguish useful pages from URL variants

The September 20 indexing report shows 16,087 excluded URLs and 1,874 indexed URLs across **all known URLs**. Exclusions break down as follows:

| Reason | URLs |
|---|---:|
| Noindex | 7,816 |
| Robots blocked | 4,589 |
| Alternate with proper canonical | 2,706 |
| Redirect | 660 |
| Crawled, currently not indexed | 275 |
| 404 | 27 |
| Server error | 13 |
| Different canonical chosen by Google | 1 |

These totals are not a backlog of pages to force into Google. The first ten examples of the separate **811 indexed-but-blocked URLs** are `/go` outbound tour-tracking links. The first ten server-error examples are login/OAuth URLs. Samples do not establish the composition of every affected URL.

The **submitted sitemap** view is substantially healthier: about **1,060 indexed**, **26 excluded for noindex**, and zero currently reported submitted URLs in the crawled/discovered-not-indexed categories. The 26 noindex examples were last crawled in March or April.

We checked all 26 current public pages: all returned 200, explicit index/follow, self-canonicals, no noindex headers, permission in robots.txt, and inclusion in the current 1,082-URL sitemap. See [machine-readable HTTP evidence](reports/search-index-check-2026-09-23.json). These browser-user-agent checks confirm current signals, not Google's index or verified Googlebot access.

**Action completed:** requested validation in Search Console. The UI confirms **“Validation started — Started: 9/23/26”** for the 26 submitted noindex URLs. Validation is pending; indexing is not guaranteed.

## First implementation batch

Prepared locally; **not deployed**. Existing unrelated working-tree edits were preserved.

- Related beach-guide links now use canonical localized routes. Family and scenic beaches use the real taxonomy keys, while older aliases still work.
- Municipality activity links preserve the visitor's English/Spanish route in both layouts.
- Six recommendation queries across five static guides exclude unpublished beach records. Snorkeling applies its limit after publication filtering; family planning uses the correct `lifeguard` amenity key. This covers query-backed recommendations and map IDs, not literal CMS HTML or hardcoded editorial mentions.
- Robots rules cover confirmed filters in any parameter position, including encoded array filters. Conflicting public-page Allow rules were removed; clean pages and the directory rendering API remain crawlable.
- Unfiltered pagination is allowed. This intentionally removes the former blanket first-parameter page block and avoids newly blocking collection pagination when longer Allow rules disappear. Pagination with filters is blocked. Invalid-page HTTP status and pagination canonicals require a separate fix.

Validation: 1,269 crawl-policy checks, six SQLite in-memory recommendation-query tests, PHP syntax checks, and locale routing checks passed. No database migrations are part of this batch.

## Priority pages and concrete editorial briefs

Values are from the live 28-day comparison table. Page-level metrics are not additive property totals. The proposed work is an editorial brief; it does not assert that any unverified practical detail is already known.

| Page | Clicks now / prior | Current impressions | CTR | Position | Next improvement |
|---|---:|---:|---:|---:|---|
| `/beaches-near-san-juan` | 16 / 42 | 6,718 | 0.2% | 9.6 | Compare city beaches by starting area, access, facilities and transport; distinguish beachgoing from swimming conditions. Link to the corresponding detail pages and airport guide. |
| `/guides/best-time-visit-puerto-rico-beaches` | 36 / 53 | 4,816 | 0.7% | 5.9 | Answer seasonal trip-planning questions directly, cite primary climate/conditions sources, explain coast/activity differences, and link to practical beach choices. |
| `/beach/playa-de-luquillo` | 11 / 15 | 3,381 | 0.3% | 11.5 | Clarify the beach's identity and named sections; verify entrance and parking details, operator hours/fees, bathrooms and walking access. Avoid conflating nearby places. |
| `/beaches-near-san-juan-airport` | 12 / 23 | 2,773 | 0.4% | 6.8 | Build a useful short-stop comparison: transport assumptions, luggage constraints, facilities and return timing. Verify access and avoid presenting traffic-sensitive drive times as guarantees. |
| `/es/playa/balneario-de-dorado` | 133 / 274 | 2,664 | 5.0% | 4.9 | Protect a proven Spanish landing page. Review lost queries and practical information; connect to the Dorado directory and nearby alternatives. Prior position was 3.5. |
| `/es/playas-en-salinas` | 99 / 143 | 1,142 | 8.7% | 5.8 | Add specific comparisons of access, beach type and facilities; explain which options fit which outing and link to verified detail pages. |

Also investigate `/beach/mar-chiquita-village` (3 clicks / 3,394 impressions / position 9.0) for identity and intent; `/guides/bioluminescent-bays` (7 / 2,777 / 15.4) needs its own planning intent review. Do not redirect, merge or noindex pages just because aggregate CTR is low.

## What makes this the definitive resource

For each priority beach, publish an evidence-backed practical record: correct identity and aliases; entrance and parking pins; walking approach and surface; dated fees/hours with source; bathrooms/showers and accessibility specifics; current-condition source links; original credited photos; nearby alternatives; and a visible correction route. Distinguish an online source review from an on-site visit. Unknown details should remain explicitly unknown.

Use one reviewed fact source across beach pages, guides, lists, FAQs and schema. Existing contradictions to resolve include the Ojo de Agua family-list hours despite its identity hold, blanket lifeguard statements in the kid-friendly guide, and Fajardo ferry-departure copy that conflicts with Ceiba guidance elsewhere. Verify before changing claims. Audit stored CMS content as well as static fallbacks.

Give each page type a clear job: exhaustive town directory, editorial shortlist explaining tradeoffs, specific beach visit record, and trip-planning guide. Check query/page overlap between `/beaches/swimming` and `/best-swimming-beaches` (and corresponding family pages) before consolidation. Strengthen reciprocal links among genuinely related pages in the same language.

## 90-day execution and measurement

| Phase | Deliverable | Acceptance criteria |
|---|---|---|
| Days 1–14 | Release the tested fixes; follow the 26-page validation; complete the six briefs above | Production spot checks pass; canonical URLs stay accessible; held listings remain excluded; dated factual evidence and language parity on updated pages |
| Days 15–45 | Extend practical verification to a 20-beach priority cohort; improve town/guide comparisons | Every changed operational claim has a source/review date or a clear unknown; entrance/parking distinctions and useful alternatives are present; CMS/static copies agree |
| Days 46–90 | Expand winning content patterns; prepare original photo/accessibility/local-access resources and relevant partner outreach | Work is selected from cohort results; original evidence is credited; outreach destinations and messages are separately approved before sending |

Track weekly, compare completed equal 28-day windows, and annotate release dates. Use dimension-free property totals, then separate English/Spanish and device/country cohorts. For the named pages, track clicks, impressions, CTR and query-level position together; keep an unchanged comparison cohort to help distinguish site changes from broad demand shifts. Also measure directions clicks, saved beaches and useful guide-to-beach navigation once GA4 event definitions are verified.

The first business milestone is recovering the prior approximately **4,670 clicks per 28 days**. At unchanged 290,000 impressions, a hypothetical 1.5% CTR yields 4,350 clicks; reaching 4,670 would require about 1.61%. This is arithmetic, not a forecast or a recommended universal CTR benchmark. Recovery needs better relevance and visibility, not only metadata edits. Revisit targets after the first completed 28-day cohort readout.

No recurring automation, outreach, production deployment or new content claims were published during this review.

## Remaining engineering backlog

Normalize Spanish guide aliases and mixed-language tag aliases; correct invalid municipality routes that redirect home; fix accented-town redirects; return 404 for impossible pagination; and align pagination canonicals with intended indexing. Review `/go` deindexing separately: robots blocking prevents Google from reading noindex, so changing it requires a deliberate crawl-versus-removal strategy. Review structured-data eligibility before promising rich-result stars.

The August plan is historical context: edge caching and the near-me page already shipped. Its claimed review-star upside and broad-match classification should not be reused as established current findings.

## Sources

- [Search Console performance comparison](https://search.google.com/search-console/performance/search-analytics?resource_id=sc-domain%3Apuertoricobeachfinder.com&num_of_days=28&compare_date=PREV&metrics=CLICKS%2CIMPRESSIONS%2CCTR%2CPOSITION)
- [Submitted noindex validation](https://search.google.com/search-console/index/drilldown?resource_id=sc-domain%3Apuertoricobeachfinder.com&item_key=CAIYCCAC)
- [Google: faceted navigation](https://developers.google.com/crawling/docs/faceted-navigation) — unnecessary filter combinations can waste crawling resources; preserve access to useful public content.
- [Google: noindex](https://developers.google.com/search/docs/crawling-indexing/block-indexing) — a crawler must access a URL to see its noindex instruction.
- [Google: helpful, reliable content](https://developers.google.com/search/docs/fundamentals/creating-helpful-content) — prioritize original utility, transparent sourcing and demonstrable experience.
