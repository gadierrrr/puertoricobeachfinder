# Growth Roadmap — Referral Prompt Fix Report

**Date:** 2026-07-02
**Theme:** Make the user-to-user referral loop actually convert — surface a prompt for referred visitors
**Status:** ✅ Shipped to production
**Branch:** `feat/referral-prompt-fix` → `main`
**Deploy:** `git pull origin main` on prod (`/var/www/beach-finder`) — no migration, no asset rebuild

---

## The bug

Opening a referral link (`https://www.puertoricobeachfinder.com/?ref=CODE`) captured the code into
the `bf_ref` cookie but **nothing ever read that cookie to prompt the visitor to register**, so the
referral silently leaked — the visitor just saw the normal homepage. Capture was also **homepage-only**,
and the one generic registration popup was referral-blind, delayed 30s, desktop-only, and self-suppressed
for 30 days.

Two independent systems share the word "referral" — don't confuse them:
- **Affiliate** (`inc/referrals.php`, migration `023`) — Expedia outbound links. Unrelated.
- **User-to-user invite loop** (`inc/invite.php`, migration `039`) — the `?ref=CODE` system. This fix.

## What shipped

| # | Item | Files |
|---|------|-------|
| 1 | **Referrer lookup helper** — `inviteReferrerName()` validates the `bf_ref` cookie against a real `users.referral_code` and returns the referrer's first name (or `null` for invalid/unknown codes, so fake codes show nothing). | `inc/invite.php` |
| 2 | **Site-wide capture** — moved `inviteCaptureRefFromRequest()` into the shared header so `?ref=` is captured on **any** landing page (beach pages, guides, collections), not just `/`. | `components/header.php` |
| 3 | **Referral-aware prompt** — the welcome popup now, for a valid referral, shows **immediately on any page**, with **personalized copy** ("Ana invited you!"), its **own dismissal key** (`referral_popup_dismissed`) so the generic 30-day suppression can't hide it, and fires `referral_prompt_shown` / `referral_cta_click`. | `components/footer.php` |
| 4 | **i18n** — English + Spanish strings `welcome_referral_title[_named]`, `welcome_referral_subtitle`. | `inc/lang/en.php`, `inc/lang/es.php` |
| 5 | **GA4 routing** — `bfTrack()` now sends to `gtag('event', ...)`, back-filling **all** custom events into GA4 (they previously went only to the now-disabled Umami) and delivering the new referral events. | `public/assets/js/analytics.js` |
| 6 | **Cache-bust** — bumped `analytics.js?v=2.0 → v2.1` so returning visitors fetch the new wrapper (caught during live-browser testing; without it, cached JS misses the GA4 routing). | `components/footer.php` |

Attribution back-end (`inviteAttribute()` on signup, `user_referrals` ledger, badge rewards) was already
correct and unchanged — the only missing piece was the visitor-facing prompt.

## Verification

- **Local end-to-end** (dev server, user `s4user` / code `04434B0`): valid code → personalized popup + `bf_ref` cookie; invalid/absent code → generic popup; inner pages (`/beach/…`, `/best-family-beaches`) → referral popup (site-wide capture); Spanish `/es?ref=…` → translated popup. PHP lint + JS syntax clean, no runtime warnings.
- **Live Chrome browser** (with a test GA4 id): popup appears immediately with "S4 invited you!"; `referral_prompt_shown` lands in GA4 `dataLayer` on load; real "Continue with Google" click fires `referral_cta_click` (`referrer:"S4"`); `bfTrack → gtag('event', ...)` confirmed via spy; "Maybe Later" dismissal → suppressed on reload; Spanish "¡S4 te invitó!" verified.
- **Not automatable:** completing a real Google OAuth signup (and thus the final `referred_by` / `user_referrals` write) needs live Google credentials; that path is code-verified and unchanged.

## Follow-ups (not in this ship)

- **Double-sided incentive** — the invitee currently gets no reward; only the referrer earns a badge on completion. Add an invitee-side unlock to strengthen the conversion reason.
- **Umami cleanup** — remove dead Umami plumbing (false "analytics unavailable" health probe/console warnings, CSP hosts) now that GA4 is primary; migrate `check-analytics-umami.php` / `/api/health/analytics.php` probes to GA4.
- **Prompt frequency** — the referral popup re-shows on each new page until dismissed. Tune to homepage + beach pages only if it reads as naggy.
