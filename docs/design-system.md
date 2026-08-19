# Design System (Homepage-First)

This project uses a homepage-first visual system for all public pages.

## Source of truth

- Design tokens reference: `public/assets/design/tokens.json`
- CSS variables used by the site: `public/assets/css/partials/_variables.css`
- Tailwind token module: `public/assets/design/tailwind.tokens.cjs`

Token generation is manual; there is no `build:tokens` script. When updating `tokens.json`, make the corresponding change to `_variables.css` and `tailwind.tokens.cjs`.

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

- `_collections.css` — collection explorer and `.collection-legacy-content` scoped legacy remaps only
- `_dark-mode.css` — generic dark-theme behavior; avoid utility remaps for collection content
- `_accessibility.css` — focus, motion, and semantic accessibility helpers only
- `_print.css` — print-only rules
- `_beach.css` — beach page screen styles, excluding print behavior

## Banned patterns on public pages

- Inline `<style>` blocks
- Direct `/assets/css/*.css` links outside the shared shell/header
- Deprecated hero classes: `.hero-gradient`, `.hero-gradient-purple`
- New blue, green, or teal utility accents for non-semantic styling
- Global utility selector overrides such as `.bg-*`, `.text-*`, or `.border-*` in partial CSS
- `a:not([class])` selectors outside explicit content scopes
- Mid-rule CSS partial files; every partial must be self-contained

## Enforcement

Run the same design-system check used by CI:

```bash
npm run check:design
```

CI runs this check for every push and pull request.

## Exceptions

Use `config/design-lint-allowlist.json` only for time-boxed migration exceptions. Every exception must be explicit and should be removed when its page is migrated.
