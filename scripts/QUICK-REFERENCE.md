# Content Generation Quick Reference

Fast reference for common tasks.

## Prerequisites

```bash
# Set API key
export ANTHROPIC_API_KEY='your-key-here'

# Verify
echo $ANTHROPIC_API_KEY
```

## Common Commands

### Testing

```bash
# Test system components
php scripts/test-content-generator.php

# Test API connection
php scripts/generate-beach-content.php --test-api

# Dry run (single beach)
php scripts/generate-beach-content.php \
  --beach-id=14122416-804d-47aa-9885-9ce3aecd8520 \
  --dry-run
```

### Generation

```bash
# Single beach
php scripts/generate-beach-content.php \
  --beach-id=14122416-804d-47aa-9885-9ce3aecd8520

# Small batch (10 beaches)
php scripts/generate-beach-content.php --batch-size=10

# Medium batch (50 beaches)
php scripts/generate-beach-content.php --batch-size=50

# Full generation (all beaches)
php scripts/generate-beach-content.php

# Resume from checkpoint
php scripts/generate-beach-content.php
# (automatically resumes if checkpoint exists)

# Start from specific beach
php scripts/generate-beach-content.php --start=BEACH-ID
```

### Validation

```bash
# Validate existing content
php scripts/generate-beach-content.php --validate-only
```

### Approval

```bash
# Auto-approve all generated content
php scripts/generate-beach-content.php --approve
```

## Monitoring

### Real-time Monitoring

```bash
# Watch logs
tail -f scripts/content-generation.log

# Watch checkpoint
watch -n 5 cat scripts/content-generation-checkpoint.json
```

### Database Queries

```sql
-- Check progress
SELECT COUNT(DISTINCT beach_id) as beaches,
       COUNT(*) as sections
FROM beach_content_sections;

-- Find missing beaches
SELECT COUNT(*) FROM beaches WHERE id NOT IN
  (SELECT DISTINCT beach_id FROM beach_content_sections);

-- Check word counts
SELECT section_type,
       AVG(word_count) as avg,
       MIN(word_count) as min,
       MAX(word_count) as max
FROM beach_content_sections
GROUP BY section_type;

-- Find incomplete beaches
SELECT b.name, COUNT(c.id) as sections
FROM beaches b
LEFT JOIN beach_content_sections c ON b.id = c.beach_id
GROUP BY b.id
HAVING sections != 6;

-- View sample content
SELECT b.name, c.heading, substr(c.content, 1, 100) as preview
FROM beach_content_sections c
JOIN beaches b ON c.beach_id = b.id
ORDER BY RANDOM()
LIMIT 5;
```

## File Locations

```
scripts/
├── generate-beach-content.php          # Main CLI
├── test-content-generator.php          # Test script
├── content-generation.log              # Log file
├── content-generation-checkpoint.json  # Checkpoint
└── content/
    ├── PromptBuilder.php
    ├── ContentGenerator.php
    ├── ContentValidator.php
    └── BatchProcessor.php
```

## Troubleshooting

### API Key Issues

```bash
# Check if set
echo $ANTHROPIC_API_KEY

# Set in current session
export ANTHROPIC_API_KEY='your-key'

# Persist in bash
echo 'export ANTHROPIC_API_KEY="your-key"' >> ~/.bashrc
source ~/.bashrc
```

### Resume from Checkpoint

```bash
# Just re-run same command
php scripts/generate-beach-content.php

# Clear checkpoint to restart
rm scripts/content-generation-checkpoint.json
```

### Regenerate Single Beach

```bash
# Replaces existing content
php scripts/generate-beach-content.php --beach-id=BEACH-ID
```

### Database Locked

```bash
# Check WAL mode
sqlite3 data/beach-finder.db "PRAGMA journal_mode;"

# Enable WAL mode
sqlite3 data/beach-finder.db "PRAGMA journal_mode=WAL;"
```

## Quality Checks

### Good Content Indicators

- ✅ Word counts: 400-600 (history), 200-300 (others)
- ✅ No generic phrases
- ✅ Beach-specific details
- ✅ Natural writing style
- ✅ Overall score > 60

### Warning Signs

- ⚠️ Very short sections (< 150 words)
- ⚠️ Generic phrases detected
- ⚠️ Repetitive sentence structures
- ⚠️ Low quality scores (< 60)

### Manual Review Query

```sql
-- Get random sample for review
SELECT
    b.name,
    c.section_type,
    c.word_count,
    c.content
FROM beach_content_sections c
JOIN beaches b ON c.beach_id = b.id
WHERE c.section_type = 'history'
ORDER BY RANDOM()
LIMIT 3;
```

## Backup & Recovery

### Backup Database

```bash
cp data/beach-finder.db \
   data/beach-finder.db.backup-$(date +%Y%m%d)
```

### Restore Backup

```bash
cp data/beach-finder.db.backup-YYYYMMDD \
   data/beach-finder.db
```

### Clear All Content

```sql
DELETE FROM beach_content_sections;
```

```bash
rm scripts/content-generation-checkpoint.json
```

## Status Workflow

```
draft → published
  ↓        ↑
  └────────┘
   (approve)
```

### Change Status

```bash
# Approve all via CLI
php scripts/generate-beach-content.php --approve

# Or in SQL
UPDATE beach_content_sections
SET status = 'published',
    approved_at = datetime('now')
WHERE status = 'draft';
```

## Performance

### Time Estimates

- Single beach: 5-10 seconds
- 10 beaches: ~2 minutes
- 50 beaches: ~8-12 minutes
- 468 beaches: ~3-4 hours

### Cost Estimates

- Per beach: ~$0.03-0.04 USD
- 468 beaches: ~$15-20 USD

## Exit Codes

```
0 = Success
1 = Failure (errors occurred)
```

## Help

```bash
# Show help
php scripts/generate-beach-content.php --help
```

## Documentation

- `CONTENT-GENERATION-GUIDE.md` - Complete usage guide
- `GENERATION-CHECKLIST.md` - Pre-flight checklist
- `SYSTEM-ARCHITECTURE.md` - Technical documentation
- `content/README.md` - Component details

## Support Commands

```bash
# Check PHP version
php -v

# Check database file
ls -lh data/beach-finder.db

# Check log file size
ls -lh scripts/content-generation.log

# Count beaches in database
sqlite3 data/beach-finder.db "SELECT COUNT(*) FROM beaches;"

# Check API connectivity
curl -I https://api.anthropic.com
```

## Section Types

1. `history` - History & Background (400-600 words)
2. `best_time` - Best Time to Visit (200-300 words)
3. `getting_there` - Getting There (200-300 words)
4. `what_to_bring` - What to Bring (200-300 words)
5. `nearby` - Nearby Attractions (200-300 words)
6. `local_tips` - Local Tips (200-300 words)

## Quality Scores

- **Word count**: 0-100 (based on range compliance)
- **Uniqueness**: 0-100 (100 - generic_phrases * 20)
- **Readability**: 0-100 (Flesch Reading Ease)
- **Overall**: Average of above three

Target: Overall score ≥ 60

## Common Flags

```
--beach-id=ID      Single beach mode
--start=ID         Start from beach ID
--batch-size=N     Beaches per batch
--validate-only    Only validate
--dry-run          Don't save
--approve          Auto-approve
--test-api         Test connection
--help             Show help
```

## Emergency Stop

```
Ctrl+C             Stop generation
                   (checkpoint saved every 10 beaches)
```

## Production Checklist

- [ ] API key set
- [ ] Database backed up
- [ ] Test generation successful
- [ ] Monitoring ready
- [ ] Time allocated (~4 hours)

## Post-Generation

```sql
-- Verify completion
SELECT
    COUNT(DISTINCT beach_id) as beaches,
    COUNT(*) as sections
FROM beach_content_sections;

-- Expected: beaches=468, sections=2808
```

```bash
# Approve all
php scripts/generate-beach-content.php --approve
```

---

**Quick Start**:
```bash
export ANTHROPIC_API_KEY='key' && \
php scripts/test-content-generator.php && \
php scripts/generate-beach-content.php --test-api && \
php scripts/generate-beach-content.php --batch-size=10
```
