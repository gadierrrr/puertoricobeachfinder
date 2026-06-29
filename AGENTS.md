# AGENTS.md

> **`CLAUDE.md` is the canonical guide.** It holds the architecture, directory map,
> bootstrap conventions, commands, change playbooks, and the full documentation index.
> This file is a short, tool-agnostic orientation plus the agent guardrails. When the two
> ever disagree, CLAUDE.md wins.

## Orientation (30 seconds)

PHP + SQLite site, **no framework**, procedural style. The production web server serves
**only** the `public/` directory. Frontend is Tailwind + custom CSS partials + vanilla JS;
many `public/api/` endpoints return HTML for `HX-Request` and JSON otherwise. Everything
outside `public/` is server-side code, source assets, runtime data, or CLI-only utilities.

For where things live, how to bootstrap an entrypoint, the build/run/verify commands, and
common change playbooks, see **`CLAUDE.md`**.

## Agent guardrails

- Never put secrets into `public/` (or any committed file). CI runs secret scanning.
- Treat `data/`, `uploads/`, `logs/`, and `backups/` as runtime or sensitive areas, not
  normal source directories.
- Edit CSS in `public/assets/css/partials/`, then rebuild with `npm run build:css`
  (`public/assets/css/styles.css`, `tailwind.min.css`, and `*.min.js` are generated).
- `public/assets/` is the served asset tree; the root-level `assets/` is source/reference.
- Prefer existing shared components before creating new inline UI patterns.
- Before writing a new utility, check `docs/helpers-index.md` — and remember every global
  function name must be unique across `inc/` + `components/` (duplicates cause ERROR 500).
- If you touch guides, account for both CMS-backed rendering and legacy static fallbacks.
- If you touch localized routes or translated copy, inspect `inc/i18n.php`,
  `inc/locale_routes.php`, and `inc/lang/`.
- Don't build on the inactive Discovery redesign (see "Inactive / Work-in-Progress Areas"
  in CLAUDE.md) — those files are unshipped and unwired.
- **Run `npm run check` before reporting a change done.**
- `public/llms-full.txt` can be large; only open it if you specifically need it.

## Further reading

- `CLAUDE.md` — canonical architecture, commands, and documentation index
- `README.md` — setup, env vars, deploy flow, health checks
- `docs/analytics-umami.md`, `docs/email-resend.md` — analytics & email runbooks
- `scripts/SYSTEM-ARCHITECTURE.md` — content/generation system deep-dive
