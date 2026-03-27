-- Minimal CI seed data: one beach so pages can render
INSERT OR IGNORE INTO beaches (id, slug, name, municipality, lat, lng, cover_image, publish_status, published_at)
VALUES ("ci-test-1", "ci-test-beach", "CI Test Beach", "san-juan", 18.4655, -66.1057, "/images/placeholder.jpg", "published", "2026-01-01");
