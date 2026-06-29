# Codebase map

> **Superseded — see [`CLAUDE.md`](../CLAUDE.md).**
> The directory map, bootstrap/entrypoint conventions, web entrypoints, and the common
> change playbooks that used to live here are now maintained in the canonical guide, plus
> a full **Documentation Index** linking the generated references
> (`docs/database-schema.md`, `docs/api-manifest.md`, `components/CATALOG.md`,
> `docs/helpers-index.md`).

Quick links:

- Architecture, conventions, commands, playbooks → [`CLAUDE.md`](../CLAUDE.md)
- Setup, env vars, deploy flow, health checks → [`README.md`](../README.md)
- Agent guardrails → [`AGENTS.md`](../AGENTS.md)

## Deployment notes

- Nginx `root` should be `/var/www/beach-finder/public`.
- `uploads/` is served via `alias /var/www/beach-finder/uploads/;` at URL `/uploads/` with PHP disabled.
- DB lives under `data/` (path configured via `DB_PATH` in `.env`).
