# Practical information corrections — deployed September 14, 2026

Implemented the corrections for the 20 listings in the accompanying audit. The content is an online source review, not an on-site inspection. Unconfirmed current fees, staffing, access and conditions are explicitly qualified.

## Published changes

- Replaced unsupported descriptions, parking, safety and planning advice with bilingual guidance and dated source links.
- Archived previous content before replacing it; older generated sections are drafts.
- Removed unsupported free-parking, lifeguard, accessibility and calm-water/family-safety claims from the reviewed records where applicable.
- Aligned visible FAQs and structured data with the reviewed guidance; removed blanket free/public-access schema assertions.
- Removed unsupported tour booking prompts for Cayo Matías and Muelle de Azúcar.
- Preserved existing production interface updates.

Sixteen listings remain published. Four are temporarily drafts until their identity and location can be established: Balneario Isla Verde, Ojo de Agua, Las Picúas and Coco Beach Río Grande. No replacement coordinates or redirects were invented.

## Verification

- Production deployment completed successfully at commit `cb9feec`.
- Migration: 20 updated, four held, no missing records; no migrations pending.
- All 40 English/Spanish routes checked at the origin: 32 published routes returned 200 with dated source sections; eight held routes returned 404.
- Public Carolina page also returned 200 with the updated source section.
- Regression tests passed for backups, repeat-run preservation, transactional rollback, bilingual FAQ/schema parity, locale routes and page heroes.
- PHP syntax, frontend build, design checks and beach image audit passed.

## Recovery

Production database backup: `/var/www/beach-finder/backups/db/beach-finder.db.backup-20260914_173730.sqlite`. Restore smoke test and integrity check passed. Per-listing original data is also retained in `practical_information_backups`.

## Remaining factual verification

The four held listings need confirmed identities and access locations before republication. Current operating details and physical conditions require operator or on-site confirmation where the pages say so. These uncertainties have been disclosed rather than presented as verified facts.
