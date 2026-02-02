# Self-Hosted Fonts Setup Guide

## Why Self-Host Fonts?

Self-hosting fonts eliminates:
- External DNS lookup to fonts.googleapis.com
- Additional HTTPS connection to fonts.gstatic.com
- Render-blocking requests that delay First Contentful Paint (FCP)
- Third-party tracking concerns

**Performance Impact:** Can improve LCP by 200-500ms

## Step 1: Download Fonts

### Option A: Using google-webfonts-helper

1. Visit https://gwfh.mranftl.com/fonts
2. Search for "Inter" and "Playfair Display"
3. Select character sets: latin, latin-ext
4. Download the files

### Option B: Manual Download (Current Fonts Used)

**Inter (Weights: 400, 500, 600, 700, 800):**
```bash
cd /var/www/beach-finder/assets/fonts

# Download Inter variable font (recommended - single file for all weights)
wget -O inter-variable.woff2 https://fonts.gstatic.com/s/inter/v13/UcCO3FwrK3iLTeHuS_fvQtMwCp50KnMw2boKoduKmMEVuLyfAZJhiI2B.woff2

# OR download individual weights (if variable font not supported)
wget -O inter-400.woff2 https://fonts.gstatic.com/s/inter/v13/UcCO3FwrK3iLTeHuS_fvQtMwCp50KnMw2boKoduKmMEVuLyeMZJhiI2B.woff2
wget -O inter-500.woff2 https://fonts.gstatic.com/s/inter/v13/UcCO3FwrK3iLTeHuS_fvQtMwCp50KnMw2boKoduKmMEVuI6fMZJhiI2B.woff2
wget -O inter-600.woff2 https://fonts.gstatic.com/s/inter/v13/UcCO3FwrK3iLTeHuS_fvQtMwCp50KnMw2boKoduKmMEVuGKYMZJhiI2B.woff2
wget -O inter-700.woff2 https://fonts.gstatic.com/s/inter/v13/UcCO3FwrK3iLTeHuS_fvQtMwCp50KnMw2boKoduKmMEVuDyfMZJhiI2B.woff2
wget -O inter-800.woff2 https://fonts.gstatic.com/s/inter/v13/UcCO3FwrK3iLTeHuS_fvQtMwCp50KnMw2boKoduKmMEVuDyfMZJhiI2B.woff2
```

**Playfair Display Italic (Weights: 400, 500, 600, 700):**
```bash
# Download Playfair Display Italic
wget -O playfair-400-italic.woff2 https://fonts.gstatic.com/s/playfairdisplay/v36/nuFkD-vYSZviVYUb_rj3ij__anPXDTnCjmHKM4nYO7KN_qiTbtbK-F2rA0s.woff2
wget -O playfair-500-italic.woff2 https://fonts.gstatic.com/s/playfairdisplay/v36/nuFkD-vYSZviVYUb_rj3ij__anPXDTnCjmHKM4nYO7KN_pqTbtbK-F2rA0s.woff2
wget -O playfair-600-italic.woff2 https://fonts.gstatic.com/s/playfairdisplay/v36/nuFkD-vYSZviVYUb_rj3ij__anPXDTnCjmHKM4nYO7KN_nSUbtbK-F2rA0s.woff2
wget -O playfair-700-italic.woff2 https://fonts.gstatic.com/s/playfairdisplay/v36/nuFkD-vYSZviVYUb_rj3ij__anPXDTnCjmHKM4nYO7KN_k2UbtbK-F2rA0s.woff2
```

## Step 2: Create Font Face CSS

Create `/var/www/beach-finder/assets/css/fonts.css`:

```css
/* Inter Variable Font (Recommended) */
@font-face {
  font-family: 'Inter';
  font-style: normal;
  font-weight: 100 900;
  font-display: swap;
  src: url('/assets/fonts/inter-variable.woff2') format('woff2-variations');
  unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}

/* OR Individual Weights (if variable font not supported) */
@font-face {
  font-family: 'Inter';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url('/assets/fonts/inter-400.woff2') format('woff2');
}

@font-face {
  font-family: 'Inter';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: url('/assets/fonts/inter-500.woff2') format('woff2');
}

@font-face {
  font-family: 'Inter';
  font-style: normal;
  font-weight: 600;
  font-display: swap;
  src: url('/assets/fonts/inter-600.woff2') format('woff2');
}

@font-face {
  font-family: 'Inter';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url('/assets/fonts/inter-700.woff2') format('woff2');
}

@font-face {
  font-family: 'Inter';
  font-style: normal;
  font-weight: 800;
  font-display: swap;
  src: url('/assets/fonts/inter-800.woff2') format('woff2');
}

/* Playfair Display Italic */
@font-face {
  font-family: 'Playfair Display';
  font-style: italic;
  font-weight: 400;
  font-display: swap;
  src: url('/assets/fonts/playfair-400-italic.woff2') format('woff2');
}

@font-face {
  font-family: 'Playfair Display';
  font-style: italic;
  font-weight: 500;
  font-display: swap;
  src: url('/assets/fonts/playfair-500-italic.woff2') format('woff2');
}

@font-face {
  font-family: 'Playfair Display';
  font-style: italic;
  font-weight: 600;
  font-display: swap;
  src: url('/assets/fonts/playfair-600-italic.woff2') format('woff2');
}

@font-face {
  font-family: 'Playfair Display';
  font-style: italic;
  font-weight: 700;
  font-display: swap;
  src: url('/assets/fonts/playfair-700-italic.woff2') format('woff2');
}
```

## Step 3: Update Header Component

In `/var/www/beach-finder/components/header.php`, replace:

```html
<!-- OLD: Google Fonts async load -->
<link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@1,400;1,500;1,600;1,700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@1,400;1,500;1,600;1,700&display=swap" rel="stylesheet"></noscript>
```

With:

```html
<!-- Self-hosted fonts with preload -->
<link rel="preload" href="/assets/fonts/inter-variable.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/playfair-400-italic.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/assets/css/fonts.css">
```

## Step 4: Remove DNS Prefetch (No Longer Needed)

Remove these lines from header.php:

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
```

## Step 5: Update Tailwind Config (If Needed)

In `/var/www/beach-finder/tailwind.config.js`, verify font families reference the same names:

```javascript
fontFamily: {
  'sans': ['Inter', 'system-ui', 'sans-serif'],
  'serif': ['Playfair Display', 'Georgia', 'serif'],
}
```

## Step 6: Test & Verify

1. **Clear browser cache**
2. **Test fonts load:**
   - Open DevTools Network tab
   - Reload page
   - Should see `/assets/fonts/*.woff2` loading
   - Should NOT see `fonts.googleapis.com` or `fonts.gstatic.com`

3. **Check font rendering:**
   - Text should look identical to before
   - No FOUT (Flash of Unstyled Text) thanks to `font-display: swap`

4. **Measure performance:**
   - Run Lighthouse audit
   - LCP should improve by 200-500ms
   - "Eliminate render-blocking resources" warning should be gone for fonts

## Performance Benefits

**Before (Google Fonts):**
- DNS lookup: ~20-50ms
- Connection: ~50-100ms
- Download: ~100-200ms
- **Total: ~170-350ms added to LCP**

**After (Self-Hosted):**
- Same origin as HTML (no DNS/connection)
- Can use preload for critical fonts
- **Total: ~50-100ms (70% faster!)**

## Browser Support

- WOFF2 supported in all modern browsers (Chrome, Firefox, Safari, Edge)
- Font-display: swap supported in 95%+ browsers
- Variable fonts supported in 93%+ browsers (fallback to individual weights works fine)

## File Sizes

- Inter Variable: ~100KB (all weights)
- Playfair Display Italic (4 weights): ~80KB total
- **Total: ~180KB vs ~150KB from Google** (slightly larger but faster delivery)

## Maintenance

Fonts are static files - no updates needed unless you:
- Add new font weights
- Change font families
- Want to update to newer font versions (rare)
