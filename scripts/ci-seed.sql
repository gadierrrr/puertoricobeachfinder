-- Minimal CI seed data: one beach + one municipality so pages render
INSERT OR IGNORE INTO beaches (id, slug, name, municipality, lat, lng, cover_image, publish_status, published_at, location_type)
VALUES ("ci-test-beach-1", "ci-test-beach-san-juan", "CI Test Beach", "san-juan", 18.4655, -66.1057, "/images/placeholder.jpg", "published", "2026-01-01", "beach");
