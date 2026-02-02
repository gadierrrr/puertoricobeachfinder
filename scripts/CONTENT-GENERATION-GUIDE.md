# Beach Content Generation Guide

Complete guide for generating extended content for all 468 Puerto Rico beaches.

## System Overview

The content generation system creates 6 unique sections for each beach:

1. **History & Background** (400-600 words)
2. **Best Time to Visit** (200-300 words)
3. **Getting There** (200-300 words)
4. **What to Bring** (200-300 words)
5. **Nearby Attractions** (200-300 words)
6. **Local Tips** (200-300 words)

**Total per beach**: ~1,800-2,400 words
**Total for 468 beaches**: ~840,000-1,120,000 words

## Quick Start

### Step 1: Set Up API Key

```bash
export ANTHROPIC_API_KEY='your-anthropic-api-key-here'
```

To persist across sessions, add to `~/.bashrc` or `~/.zshrc`:

```bash
echo 'export ANTHROPIC_API_KEY="your-key-here"' >> ~/.bashrc
source ~/.bashrc
```

### Step 2: Verify System

```bash
# Test all components (no API calls)
php scripts/test-content-generator.php

# Test API connection
php scripts/generate-beach-content.php --test-api
```

Expected output:
```
✓ API connection successful
ℹ Model: claude-3-5-sonnet-20241022
```

### Step 3: Test with Single Beach

```bash
# Dry run (generate but don't save)
php scripts/generate-beach-content.php \
  --beach-id=14122416-804d-47aa-9885-9ce3aecd8520 \
  --dry-run
```

Review the output for:
- Content quality
- Word counts (within ranges)
- No generic phrases
- Beach-specific details

### Step 4: Generate for One Beach (Save)

```bash
# Generate and save to database
php scripts/generate-beach-content.php \
  --beach-id=14122416-804d-47aa-9885-9ce3aecd8520
```

Check database:
```bash
sqlite3 data/beach-finder.db "
SELECT section_type, heading, word_count, status
FROM beach_content_sections
WHERE beach_id = '14122416-804d-47aa-9885-9ce3aecd8520'
ORDER BY display_order;
"
```

### Step 5: Full Batch Processing

```bash
# Start full generation (with confirmation prompt)
php scripts/generate-beach-content.php
```

Or with custom settings:
```bash
# Smaller batches for better monitoring
php scripts/generate-beach-content.php --batch-size=25
```

## Production Workflow

### Recommended Approach

Generate content in phases to monitor quality:

#### Phase 1: Small Test Batch (10 beaches)

```bash
php scripts/generate-beach-content.php --batch-size=10
```

Review:
- Check quality scores in output
- Manually review 2-3 beaches in database
- Verify no generic phrases
- Ensure beach-specific content

#### Phase 2: Medium Batch (50 beaches)

```bash
php scripts/generate-beach-content.php --batch-size=50
```

Monitor:
- Watch log file: `tail -f scripts/content-generation.log`
- Check for errors in real-time
- Verify checkpoint saves

#### Phase 3: Full Generation (All remaining)

```bash
# Continue from where Phase 2 left off
php scripts/generate-beach-content.php
```

The system automatically resumes from the last checkpoint.

#### Phase 4: Quality Review

```bash
# Validate all generated content
php scripts/generate-beach-content.php --validate-only
```

Find low-quality content:
```sql
SELECT
    b.name,
    b.municipality,
    COUNT(c.id) as sections
FROM beaches b
LEFT JOIN beach_content_sections c ON b.id = c.beach_id
GROUP BY b.id
HAVING sections != 6
ORDER BY sections;
```

#### Phase 5: Approval

```bash
# Approve all content (set status='published')
php scripts/generate-beach-content.php --approve
```

Or approve selectively in database:
```sql
UPDATE beach_content_sections
SET status = 'published',
    approved_at = datetime('now')
WHERE beach_id IN (
    -- List of beach IDs with good content
    SELECT beach_id
    FROM beach_content_sections
    GROUP BY beach_id
    HAVING COUNT(*) = 6
);
```

## Monitoring & Progress Tracking

### Real-time Monitoring

```bash
# Terminal 1: Run generation
php scripts/generate-beach-content.php

# Terminal 2: Watch logs
tail -f scripts/content-generation.log

# Terminal 3: Watch checkpoint
watch -n 5 'cat scripts/content-generation-checkpoint.json'
```

### Check Progress

```bash
# Count generated sections
sqlite3 data/beach-finder.db "
SELECT
    status,
    COUNT(DISTINCT beach_id) as beaches,
    COUNT(*) as sections
FROM beach_content_sections
GROUP BY status;
"
```

### Expected Output

```
status      beaches  sections
----------  -------  --------
draft       150      900
```

## Error Recovery

### Scenario 1: Process Crashes

The system automatically saves checkpoints every 10 beaches.

**To resume:**
```bash
# Just run the command again
php scripts/generate-beach-content.php
```

It will resume from the last checkpoint.

### Scenario 2: API Rate Limiting

The system has built-in 2-second delays. If you still hit rate limits:

```bash
# Increase delay by using smaller batches
php scripts/generate-beach-content.php --batch-size=10
```

Or modify delay in code (temporary):
```php
// In BatchProcessor.php, add after line 33:
$generator->setMinDelay(5); // 5 seconds between calls
```

### Scenario 3: Low Quality Content

If a beach gets low-quality content (generic phrases, wrong word count):

```bash
# Regenerate single beach
php scripts/generate-beach-content.php \
  --beach-id=BEACH-ID-HERE
```

This will replace the existing content.

### Scenario 4: Clear All and Restart

```sql
-- Delete all generated content
DELETE FROM beach_content_sections;

-- Remove checkpoint
rm scripts/content-generation-checkpoint.json

-- Restart from beginning
php scripts/generate-beach-content.php
```

## Quality Assurance

### Automated Checks

The system automatically validates:
- ✓ Word counts within ranges
- ✓ No generic phrases (crystal clear waters, hidden gem, etc.)
- ✓ No duplicate sentences across beaches
- ✓ Readability scores (Flesch Reading Ease)
- ✓ Beach name mentioned in content
- ✓ Sentence variety (no repetitive structures)

### Manual Review Checklist

For each beach, verify:

1. **Accuracy**
   - Municipality is correct
   - Geographic details make sense
   - Tag-appropriate content (e.g., surfing details for surf beaches)

2. **Uniqueness**
   - Not generic descriptions
   - Specific to this exact beach
   - Different from other beaches in same area

3. **Completeness**
   - All 6 sections present
   - Word counts reasonable
   - No truncated sentences

4. **Tone**
   - Travel guide style (not marketing)
   - Helpful and informative
   - Natural language flow

### SQL Queries for Review

**Find beaches missing sections:**
```sql
SELECT
    b.name,
    b.municipality,
    COUNT(c.id) as section_count
FROM beaches b
LEFT JOIN beach_content_sections c ON b.id = c.beach_id
GROUP BY b.id
HAVING section_count < 6
ORDER BY section_count;
```

**Find very short sections:**
```sql
SELECT
    b.name,
    c.section_type,
    c.word_count
FROM beach_content_sections c
JOIN beaches b ON c.beach_id = b.id
WHERE c.word_count < 150
ORDER BY c.word_count;
```

**Find very long sections:**
```sql
SELECT
    b.name,
    c.section_type,
    c.word_count
FROM beach_content_sections c
JOIN beaches b ON c.beach_id = b.id
WHERE c.word_count > 700
ORDER BY c.word_count DESC;
```

**Sample random content for review:**
```sql
SELECT
    b.name,
    c.section_type,
    c.heading,
    substr(c.content, 1, 200) || '...' as preview
FROM beach_content_sections c
JOIN beaches b ON c.beach_id = b.id
ORDER BY RANDOM()
LIMIT 10;
```

## Performance & Cost

### Time Estimates

- **Single beach**: 5-10 seconds
- **10 beaches**: ~2 minutes
- **50 beaches**: ~8-12 minutes
- **468 beaches**: ~3-4 hours

### API Cost Estimates

- **Model**: Claude 3.5 Sonnet
- **Input tokens**: ~2,500 per beach
- **Output tokens**: ~1,500 per beach
- **Total per beach**: ~4,000 tokens
- **Total for 468 beaches**: ~1.87M tokens

**Estimated cost**: $15-20 USD (check current Anthropic pricing)

Pricing as of Feb 2026:
- Input: $3 per million tokens
- Output: $15 per million tokens

Calculation:
```
Input:  468 × 2,500 × $3 / 1M = $3.51
Output: 468 × 1,500 × $15 / 1M = $10.53
Total: ~$14.04
```

### Tips to Minimize Cost

1. **Test with dry runs** before full generation
2. **Use small batches** for initial quality testing
3. **Don't regenerate** unless quality issues
4. **Monitor progress** to catch errors early

## Advanced Usage

### Custom Batch Processing

Process specific municipalities:

```bash
# Get beach IDs for a municipality
sqlite3 data/beach-finder.db "
SELECT id FROM beaches
WHERE municipality = 'Rincon'
ORDER BY id;
" > rincon_beaches.txt

# Process first beach as start point
FIRST_ID=$(head -n 1 rincon_beaches.txt)
php scripts/generate-beach-content.php --start=$FIRST_ID --batch-size=20
```

### Parallel Processing (Advanced)

For faster generation, run multiple processes on different beach ranges:

```bash
# Terminal 1: Beaches 1-150
php scripts/generate-beach-content.php --batch-size=150 &

# Terminal 2: Beaches 151-300 (wait for first to create checkpoint)
sleep 30
BEACH_151_ID="..." # Get from database
php scripts/generate-beach-content.php --start=$BEACH_151_ID --batch-size=150 &

# etc.
```

**Warning**: This requires careful coordination and may hit API rate limits.

### Custom Model Selection

To use a different model, modify `ContentGenerator.php`:

```php
private $model = 'claude-opus-4-5-20251101'; // Use Opus for higher quality
```

Or edit after initialization in `generate-beach-content.php`:

```php
$generator = new ContentGenerator($apiKey);
$generator->setModel('claude-opus-4-5-20251101');
```

## Troubleshooting

### Problem: "ANTHROPIC_API_KEY environment variable not set"

**Solution:**
```bash
export ANTHROPIC_API_KEY='your-key-here'

# Verify
echo $ANTHROPIC_API_KEY
```

### Problem: "API connection failed"

**Solutions:**
1. Check API key is valid
2. Test connection: `php scripts/generate-beach-content.php --test-api`
3. Check internet connection
4. Verify no firewall blocking api.anthropic.com

### Problem: "Failed to parse JSON response"

**Cause**: Claude sometimes returns markdown-wrapped JSON

**Solution**: Already handled in code. If still occurs:
1. Check error log for exact response
2. May indicate model instruction following issue
3. Try regenerating that specific beach

### Problem: Database locked errors

**Solutions:**
```bash
# Check WAL mode is enabled
sqlite3 data/beach-finder.db "PRAGMA journal_mode;"

# Should return: wal

# If not, enable it:
sqlite3 data/beach-finder.db "PRAGMA journal_mode=WAL;"
```

### Problem: Very slow generation

**Causes**:
- API latency (normal)
- Rate limiting delays (intentional)
- Network issues

**Solutions**:
- Use smaller batch sizes for better progress visibility
- Monitor network connection
- Check Anthropic API status page

## Post-Generation Tasks

After all content is generated:

### 1. Database Cleanup

```sql
-- Remove any duplicates (shouldn't happen, but check)
DELETE FROM beach_content_sections
WHERE id NOT IN (
    SELECT MIN(id)
    FROM beach_content_sections
    GROUP BY beach_id, section_type
);
```

### 2. Statistics

```sql
-- Overall statistics
SELECT
    COUNT(DISTINCT beach_id) as beaches_with_content,
    COUNT(*) as total_sections,
    AVG(word_count) as avg_words_per_section,
    SUM(word_count) as total_words
FROM beach_content_sections;

-- By section type
SELECT
    section_type,
    COUNT(*) as count,
    AVG(word_count) as avg_words,
    MIN(word_count) as min_words,
    MAX(word_count) as max_words
FROM beach_content_sections
GROUP BY section_type
ORDER BY display_order;
```

### 3. Export Sample for Review

```bash
# Export sample content to review file
sqlite3 data/beach-finder.db "
SELECT
    b.name || ' - ' || b.municipality as beach,
    c.heading,
    c.content,
    '---' as separator
FROM beach_content_sections c
JOIN beaches b ON c.beach_id = b.id
WHERE b.id IN (
    SELECT id FROM beaches ORDER BY RANDOM() LIMIT 3
)
ORDER BY b.name, c.display_order;
" > sample-content-review.txt
```

### 4. Approve Content

```bash
# After review, approve all
php scripts/generate-beach-content.php --approve
```

Or selectively:
```sql
UPDATE beach_content_sections
SET status = 'published', approved_at = datetime('now')
WHERE beach_id = 'specific-beach-id';
```

## Integration with Website

After generation, update `beach.php` to display content:

```php
// Get extended content sections
$sections = query(
    "SELECT section_type, heading, content
     FROM beach_content_sections
     WHERE beach_id = ? AND status = 'published'
     ORDER BY display_order",
    [$beach['id']]
);

// Display sections
foreach ($sections as $section) {
    echo '<section class="content-section">';
    echo '<h2>' . h($section['heading']) . '</h2>';
    echo '<div class="prose">' . nl2br(h($section['content'])) . '</div>';
    echo '</section>';
}
```

## Maintenance

### Regenerate Single Beach

```bash
php scripts/generate-beach-content.php --beach-id=BEACH-ID
```

This replaces existing content for that beach.

### Batch Regeneration

```sql
-- Mark beaches for regeneration by deleting their content
DELETE FROM beach_content_sections
WHERE beach_id IN ('id1', 'id2', 'id3');
```

Then run generation to fill gaps:
```bash
php scripts/generate-beach-content.php
```

### Archive Old Versions

Before regenerating, optionally archive:

```sql
-- Create archive table
CREATE TABLE beach_content_sections_archive AS
SELECT *, datetime('now') as archived_at
FROM beach_content_sections;

-- Then regenerate
```

## Support

For issues:

1. **Check logs**: `scripts/content-generation.log`
2. **Check checkpoint**: `scripts/content-generation-checkpoint.json`
3. **Run tests**: `php scripts/test-content-generator.php`
4. **Test single beach**: Use `--dry-run` flag
5. **Validate existing**: Use `--validate-only` flag

## Summary Checklist

Before full generation:
- [ ] API key is set and tested
- [ ] Test script runs successfully
- [ ] Single beach test works (dry run)
- [ ] Database is backed up
- [ ] Ready to monitor for ~4 hours

During generation:
- [ ] Monitor logs for errors
- [ ] Check quality of first few beaches
- [ ] Verify checkpoint saves working

After generation:
- [ ] Run validation on all content
- [ ] Review statistics
- [ ] Sample manual review
- [ ] Approve content
- [ ] Update website integration
