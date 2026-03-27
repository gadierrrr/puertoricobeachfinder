# Beach Finder Design System

Quick reference for all semantic CSS classes. Use these instead of repeating Tailwind utility chains.

## Component Classes (defined in _cards.css)

| Class | Purpose | Example |
|-------|---------|---------|
| `.card-glass` | Translucent dark card container | Quick facts, sidebar cards |
| `.card-glass--interactive` | Add hover border effect to card-glass | Clickable quick fact cards |
| `.btn-glass` | Translucent action button | Share, nav buttons |
| `.btn-primary` | Yellow accent CTA button | Main call-to-action |
| `.text-shadow-hero` | Text shadow for image overlays | Beach card titles |
| `.beach-card` | Beach card with hover lift | Beach grid cards |
| `.beach-detail-card` | Dark card on beach detail page | Sidebar sections |
| `.quick-fact-card` | Fact card with icon box | Quick facts grid |
| `.score-badge` | Score display (exceptional/excellent/good/average) | Card overlay |
| `.beach-badge` | Auto-generated label pill (gold/purple/blue/cyan/green/pink) | Card badges |
| `.prose-brand` | Rich text on dark backgrounds | Beach descriptions, collection intros |

## Color System (defined in _variables.css)

- **Primary** (blue): `--color-primary` (#3b82f6) — interactive elements, links
- **Secondary** (green): `--color-secondary` (#10b981) — guide pages, success states
- **Accent** (yellow): `--color-accent` (#fde047) — highlights, CTAs
- **Brand dark**: `--color-bg-primary` (#1a2c32), `--color-bg-secondary` (#132024)

## Dark Mode Strategy

- Selector: `[data-theme="dark"]` (set in header.php based on page variant)
- All overrides in `_dark-mode.css` — never scatter dark rules across partials
- Guide pages: light-mode Tailwind classes remapped via scoped overrides

## CSS Architecture

17 partials in `public/assets/css/partials/` → bundled to `styles.css`
Tailwind in `tailwind-input.css` → compiled to `tailwind.min.css`
Build: `npm run build` (or `npm run build:css` / `npm run build:tailwind`)

## Breakpoints

| Name | Value | Usage |
|------|-------|-------|
| Narrow | max-width: 400px | Compact sticky bar, stacked cards |
| Mobile | max-width: 640px | Mobile-first layouts, reduced padding |
| Tablet | max-width: 768px | Tablet form controls |
| Desktop | min-width: 1024px | Grid layouts, side-by-side columns |
