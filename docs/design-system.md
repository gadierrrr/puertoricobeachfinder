# Design System (Homepage-First)

This project uses a homepage-first visual system for all public pages.

## Source of truth

- Tokens source: `public/assets/design/tokens.json`
- Generated CSS variables: `public/assets/css/partials/_variables.css`
- Generated Tailwind tokens: `public/assets/design/tailwind.tokens.cjs`

Do not edit generated files directly. Run:

```bash
npm run build:tokens
```

## Public page shell contract

Use `components/page-shell.php` with:

- `$pageTitle`
- `$pageDescription`
- `$extraHead`
- `$pageTheme` (`home`, `collection`, `guide`, `light`)
- `$skipMapCSS`
- `$skipMapScripts`
- `$skipAppScripts`

Usage pattern:

```php
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

$pageShellMode = 'start';
include APP_ROOT . '/components/page-shell.php';
?>

<!-- page content -->

<?php
$pageShellMode = 'end';
include APP_ROOT . '/components/page-shell.php';
?>
```

## UI primitives

Use these shared primitives from `public/assets/css/partials/_layout.css`:

- `.ui-hero`
- `.ui-surface`
- `.ui-card`
- `.ui-btn-primary`
- `.ui-btn-secondary`
- `.ui-chip`

## Partial ownership boundaries

- `_collections.css`: collection page explorer + `.collection-legacy-content` scoped legacy remaps only
- `_dark-mode.css`: generic dark-theme behavior (avoid utility remaps for collection content)
- `_accessibility.css`: focus, motion, and semantic accessibility helpers only
- `_print.css`: print-only rules
- `_beach.css`: beach page screen styles (non-print)

## Banned patterns on public pages

- Inline `<style>` blocks
- Direct `/assets/css/*.css` links outside shared shell/header
- Deprecated hero classes: `.hero-gradient`, `.hero-gradient-purple`
- New blue/green/teal utility accents for non-semantic styling
- Global utility selector overrides (`.bg-*`, `.text-*`, `.border-*`) in partial CSS
- `a:not([class])` selectors outside explicit content scopes
- Mid-rule CSS partial files (all partials must be self-contained)

## Enforcement

Run checks locally:

```bash
npm run check:design
```

`check:design` is required in CI.

## Exceptions (temporary only)

Use `config/design-lint-allowlist.json` for time-boxed migration exceptions.
Every exception should be explicit and removed after the page is migrated.
