# Beach Finder design system

The maintained design-system contract lives in `docs/design-system.md`.

Use that document for tokens, page-shell conventions, CSS partial ownership, banned patterns, linting, and temporary exceptions. This root file remains only as a compatibility pointer for tools and links that expect `DESIGN-SYSTEM.md`.

Run the enforced design checks with:

```bash
npm run check:design
```

Edit source CSS under `public/assets/css/partials/`, then rebuild generated assets with `npm run build:css` or `npm run build`.
