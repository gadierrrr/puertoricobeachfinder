# Schema Markup Quick Reference

## Beach Detail Pages

```json
{
  "schemas": 5,
  "types": [
    "Beach",           // Primary entity with rating, image, geo
    "TouristAttraction", // Secondary entity, no duplicate rating
    "BreadcrumbList",  // Home → Municipality → Beach
    "FAQPage",         // 6 dynamic questions
    "WebPage"          // Page metadata
  ]
}
```

**ImageObject:** ✅ Used in Beach and TouristAttraction
**AggregateRating:** ✅ Only in Beach schema (no duplicates)
**Rating Source:** User reviews (>10) OR Google ratings

## Editorial Pages

```json
{
  "schemas": 4,
  "types": [
    "Article",        // With ImageObject for featured image
    "CollectionPage", // Curated beach collection
    "WebSite",        // Site-level metadata
    "FAQPage"         // 8 editorial questions
  ]
}
```

**ImageObject:** ✅ Used in Article
**Collections:** Top 20 beaches included as ListItem

## Guide Pages

```json
{
  "schemas": "3-4",
  "types": [
    "Article",         // With ImageObject
    "HowTo",           // Optional: for instructional guides
    "FAQPage",         // 5-8 guide questions
    "BreadcrumbList"   // Home → Guides → Guide Name
  ]
}
```

**ImageObject:** ✅ Used in Article
**HowTo Steps:** 6 steps with position and text

## Image Implementation

### All Schemas Use ImageObject

```json
{
  "@type": "ImageObject",
  "url": "https://...",
  "width": 1200,
  "height": 800,
  "caption": "Optional caption"
}
```

**NOT this (old way):**
```json
{
  "image": "https://..."  // ❌ String URLs no longer used
}
```

## Rating Implementation

### Beach Schema Only

```json
{
  "@type": "Beach",
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": 4.5,
    "reviewCount": 1234,
    "bestRating": 5,
    "worstRating": 1
  }
}
```

**Priority:**
1. User ratings (if >10 reviews)
2. Google ratings (fallback)
3. None (if no data)

**TouristAttraction:** ❌ No aggregateRating (prevents duplication)

## Validation Checklist

- [ ] Each page has expected number of schemas
- [ ] No duplicate AggregateRating
- [ ] All images use ImageObject
- [ ] All images have width/height
- [ ] Breadcrumbs have correct positions
- [ ] FAQs have Question/Answer structure
- [ ] HowTo steps have positions
- [ ] All URLs are absolute
- [ ] JSON-LD syntax is valid

## Testing URLs

**Validate with:**
- Google Rich Results Test: https://search.google.com/test/rich-results
- Schema.org Validator: https://validator.schema.org/
- Custom scripts: `scripts/validate-schema.php`

**Test These Pages:**
1. Any beach detail page
2. Best beaches editorial page
3. Any guide page with HowTo

## Common Schema Properties

### All Beach-related Schemas
- `name` - Required
- `description` - Recommended
- `url` - Absolute URL
- `geo` - GeoCoordinates (lat/lng)
- `address` - PostalAddress (locality, region, country)
- `image` - ImageObject with dimensions
- `isAccessibleForFree` - true
- `publicAccess` - true

### All Article Schemas
- `headline` - Required
- `description` - Required
- `author` - Organization
- `publisher` - Organization with logo
- `datePublished` - ISO date
- `dateModified` - ISO date
- `image` - ImageObject with dimensions

## Helper Functions

```php
// Generate Beach schema
beachSchema($beach, $reviews = null)

// Generate TouristAttraction schema (no rating)
touristAttractionSchema($beach)

// Generate Article schema (with ImageObject)
articleSchema($title, $description, $url, $image = null, $datePublished = null)

// Generate ImageObject
imageObjectSchema($imageUrl, $caption = null)

// Get intelligent rating (user or Google)
getRatingSchema($beach)

// Generate FAQPage
faqPageSchema($faqs)

// Generate BreadcrumbList
breadcrumbSchema($items)

// Generate HowTo
howToSchema($name, $description, $steps)
```

## Files

**Schema Generation:** `components/seo-schemas.php`
**Validation Scripts:** `scripts/validate-schema.php`
**Documentation:** `docs/schema-improvements.md`

---

*Quick Reference v1.0 - 2026-02-02*
