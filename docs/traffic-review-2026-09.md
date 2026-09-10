# September 2026 traffic review and fixes

## Baseline

Search Console, Aug 12–Sep 8 vs Jul 15–Aug 11: 3,540 vs 6,068 clicks (−41.7%); 336,191 vs 449,349 impressions (−25.2%); CTR 1.1% vs 1.4%; average position 8.3 vs 7.6. Mobile clicks were 3,079 vs 5,437. This is a real search decline, with both fewer impressions and weaker click-through; these aggregate numbers do not establish a single cause.

GA4, Aug 13–Sep 9: 11,538 sessions, 10,524 active users, 5,201 engaged sessions, 45.08% engagement and 21 seconds average engagement per session. Direct: 6,719 sessions, 23.01%, 6 seconds. Organic Search: 4,111 sessions, 81.76%, 45 seconds. AI Assistant: 265 sessions, 79.25%, 35 seconds. No key events were reported in that period. These windows differ by one day from the Search Console comparison.

The Spanish login page accounted for 2,292 landing sessions; English login 1,074 and advertising 745. GA highlighted a Singapore Direct spike. Two September 9–10 server logs contained 26,040 non-asset requests, including 8,485 auth/login/advertising requests and declared crawler agents. These are requests, not sessions, and do not prove all Direct or Singapore traffic is automated.

Search Console's Sep 3 indexing report: 3,109 indexed; 10,787 not indexed. Most exclusions were expected noindex, robots, alternate canonical or redirects. The actionable pattern was 2,049 indexed despite robots blocking; all 10 sampled examples were Google OAuth initiation URLs with redirect parameters. Smaller categories included 12 not found and 92 crawled but not indexed. All ten sampled crawled-but-unindexed URLs were Spanish sign-in variants, appropriately excluded. The 404 examples exposed seven mixed-language Spanish landing URLs and one historical Poza alias pointing at a retired record; these receive direct redirects. Two other beach records are deliberately unpublished, while `/contact` and the tracking subdomain have no public content to restore.

## Implementation

- Verified signups and persisted inquiries now emit GA events; directions and favorites are measurable key events. Removed URL-inferred signup tracking and repaired the favorite response signal. See the analytics runbook for exact semantics and limitations.
- Preserved the existing active internal-traffic filter and added matching explicit test labels. Saved an Organic Search-only comparison. No historical data deletion or geographic blocking.
- Historical OAuth initiation GET URLs show a noindex login page. Narrow robots allowances let Google read that directive; the callback remains blocked. A CSRF-protected POST starts Google sign-in. New public links point to stable sign-in pages.
- Refined titles/descriptions on nine existing English/Spanish page pairs: San Juan, airport, hidden beaches, Salinas, Mayagüez, Dorado, Luquillo, safety and best time to visit.
- Added access-planning information and relevant nearby/parking/safety links to seven landing-page pairs. Replaced unsupported airport timing/price promises with bilingual planning FAQs and official SJU links. Corrected unverified Dorado/Luquillo hours and swimming guarantees in the database through migration 058.
- Improved mobile beach actions: visible Save text, Nearby link and beach context on tracked actions. Avoided presenting family suitability as a swimming guarantee.
- Added permanent redirects from legacy Spanish tag/proximity paths to their translated equivalents, plus migration 059 to repair the oldest Poza alias. Deliberately unpublished records remain excluded.
- Corrected the scheduled GSC fetch window from 29 inclusive days to the documented 28 days (end −3, start −30).

Search queries showed that San Juan and airport pages received many broad “beaches near me” impressions, while Luquillo ranked around position 10 for its principal beach query. The changes clarify page intent and connect broad nearby searches with the actual nearby finder. Spanish Dorado/Salinas pages already attract meaningful clicks; Mayagüez and hidden beaches had gains, supporting focused improvements instead of mass-producing municipality pages.

## Evaluation

Changes are a repair and a test, not proof of ranking recovery. Compare equal 28-day windows after deployment using Organic Search, with separate mobile and Spanish views. Review clicks, impressions, CTR and position for the nine page pairs and their relevant queries, plus directions/favorites and verified outcomes. New key-event settings do not backfill the baseline.

Google must recrawl old auth URLs before indexed counts fall. Do not request indexing for noindex login URLs, remove legitimate content wholesale, or treat every excluded URL as an error. Avoid further title churn before enough post-change data is available.

References: [Google title guidance](https://developers.google.com/search/docs/appearance/title-link), [indexing report definitions](https://support.google.com/webmasters/answer/7440203), [SJU flight information](https://aeropuertosju.com/vuelos/), [SJU services](https://aeropuertosju.com/servicios/).
