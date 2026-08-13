# Documentation index

This directory separates maintained operating guidance from historical analysis. When documentation conflicts with code, use the current code and `.env.example`, then correct the documentation in the same change.

## Start here

- `../README.md` — setup, environment groups, validation, deployment, and health checks
- `../AGENTS.md` — repository layout, routing conventions, and contributor guardrails
- `codebase-map.md` — application subsystems and common change playbooks
- `design-system.md` — public page shell, tokens, CSS ownership, and enforced design rules

`../CLAUDE.md` is a compatibility entry point for tools that discover that filename. `AGENTS.md` is the authoritative repository instruction file.

## Maintained runbooks

- `analytics-umami.md` — GA4-first client tracking with optional Umami/PostHog delivery
- `email-resend.md` — Resend configuration, webhooks, rotation, and health checks
- `viator-api.md` — Viator placements (curated, auto-matched, browse), catalog sync, attribution, and reporting
- `google-key-rotation.md` — Google Maps key rotation
- `secret-history-cleanup.md` — post-rotation history cleanup
- `nginx-municipality-rewrites.md` — municipality route handling in Nginx
- `self-hosted-fonts-setup.md` — local font assets and CSP-safe loading

## Content and schema references

- `../public/guides/README.md` — live and CMS-backed guide behavior
- `../scripts/CONTENT-GENERATION-GUIDE.md` — content-generation workflow
- `../scripts/GENERATION-CHECKLIST.md` — generation QA checklist
- `../scripts/QUICK-REFERENCE.md` — generation commands
- `../scripts/SYSTEM-ARCHITECTURE.md` — generation subsystem architecture
- `schema-quick-reference.md` and `schema-improvements.md` — structured-data reference material

## Historical snapshots

The following files describe audits or implementation phases at a point in time. They are useful context, but they are not the current operating contract:

- `SEO-Improvements-Summary.md`
- `chat-system-analysis.md`
- `user-experience-analysis.md`
- `reports/`

Historical documents can contain pre-`public/` paths, completed recommendations, old branch names, or superseded counts. Add new point-in-time reports under `docs/reports/` and label them with their date and status.

## Documentation maintenance rules

Update documentation in the same change when you modify:

- environment variables or health endpoints — update `.env.example`, `README.md`, and the relevant runbook
- routing, entrypoints, or directory ownership — update `AGENTS.md` and `codebase-map.md`
- shared UI or CSS ownership — update `design-system.md`
- deploy, migration, backup, analytics, email, or referral operations — update the corresponding runbook
- generated content tooling — update the documentation under `scripts/`

Do not document secrets, production credentials, private user data, or database contents. Keep runtime output in ignored directories such as `logs/`, `audit-results/`, `test-results/`, and `backups/`.
