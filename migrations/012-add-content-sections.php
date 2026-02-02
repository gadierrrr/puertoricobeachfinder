<?php
/**
 * Migration: Add beach_content_sections table
 * Run once to update the database schema
 */

require_once __DIR__ . '/../inc/db.php';

echo "Starting migration: Beach Content Sections table\n";

try {
    $db = getDb();

    // ============================================================
    // BEACH CONTENT SECTIONS TABLE
    // ============================================================
    $db->exec("
        CREATE TABLE IF NOT EXISTS beach_content_sections (
            id TEXT PRIMARY KEY,
            beach_id TEXT NOT NULL,
            section_type TEXT NOT NULL,
            heading TEXT,
            content TEXT NOT NULL,
            word_count INTEGER DEFAULT 0,
            metadata TEXT,
            display_order INTEGER DEFAULT 0,
            status TEXT DEFAULT 'draft',
            generated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            approved_at TEXT,
            approved_by TEXT,
            version INTEGER DEFAULT 1,
            FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE,
            UNIQUE(beach_id, section_type, version)
        )
    ");
    echo "✓ Created beach_content_sections table\n";

    // Indexes for content sections
    $db->exec("CREATE INDEX IF NOT EXISTS idx_beach_content_beach_id ON beach_content_sections(beach_id)");
    echo "✓ Created index on beach_id\n";

    $db->exec("CREATE INDEX IF NOT EXISTS idx_beach_content_type ON beach_content_sections(section_type)");
    echo "✓ Created index on section_type\n";

    $db->exec("CREATE INDEX IF NOT EXISTS idx_beach_content_status ON beach_content_sections(status)");
    echo "✓ Created index on status\n";

    $db->exec("CREATE INDEX IF NOT EXISTS idx_beach_content_beach_type ON beach_content_sections(beach_id, section_type)");
    echo "✓ Created composite index on beach_id and section_type\n";

    echo "\n✅ Migration completed successfully!\n";
    echo "\nTable structure:\n";
    echo "  - id: UUID primary key\n";
    echo "  - beach_id: Foreign key to beaches table\n";
    echo "  - section_type: Type of content (overview, history, activities, tips, etc.)\n";
    echo "  - heading: Optional section heading\n";
    echo "  - content: Main content text\n";
    echo "  - word_count: Calculated word count\n";
    echo "  - metadata: JSON for additional data\n";
    echo "  - display_order: Sort order for sections\n";
    echo "  - status: draft, published, archived\n";
    echo "  - generated_at: Creation timestamp\n";
    echo "  - approved_at: Approval timestamp\n";
    echo "  - approved_by: User ID who approved\n";
    echo "  - version: Version number for content revisions\n";

} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
