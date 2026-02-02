# Beach Content Generation System

A production-ready system for generating extended content (6 sections per beach) for 468 Puerto Rico beaches using Claude AI.

## Architecture

```
scripts/
├── generate-beach-content.php     # Main CLI orchestrator
└── content/
    ├── PromptBuilder.php          # Constructs context-rich prompts
    ├── ContentGenerator.php       # Claude API integration
    ├── ContentValidator.php       # Quality control engine
    └── BatchProcessor.php         # Batch management with checkpointing
```

## Quick Start

### 1. Set API Key

```bash
export ANTHROPIC_API_KEY='your-anthropic-api-key'
```

### 2. Test Connection

```bash
php scripts/generate-beach-content.php --test-api
```

### 3. Generate Content

```bash
# Single beach (testing)
php scripts/generate-beach-content.php --beach-id=14122416-804d-47aa-9885-9ce3aecd8520

# Dry run (no database save)
php scripts/generate-beach-content.php --beach-id=14122416-804d-47aa-9885-9ce3aecd8520 --dry-run

# Full batch processing
php scripts/generate-beach-content.php

# Custom batch size
php scripts/generate-beach-content.php --batch-size=25

# Start from specific beach
php scripts/generate-beach-content.php --start=abc123-beach-id
```

## Content Sections

Each beach gets 6 sections:

1. **History & Background** (400-600 words)
   - Historical context, cultural significance, naming origin

2. **Best Time to Visit** (200-300 words)
   - Seasonal patterns, weather, crowd levels

3. **Getting There** (200-300 words)
   - Directions, parking, accessibility

4. **What to Bring** (200-300 words)
   - Essential items, activity-specific gear

5. **Nearby Attractions** (200-300 words)
   - Local restaurants, shops, other beaches

6. **Local Tips** (200-300 words)
   - Insider knowledge, cultural etiquette

## Quality Standards

### Validation Checks

- **Word count**: Within specified ranges
- **Generic phrases**: Detects overused clichés (crystal clear waters, hidden gem, etc.)
- **Duplicate sentences**: Prevents content reuse across beaches
- **Readability**: Flesch Reading Ease score (60-100 target)
- **Uniqueness score**: Based on generic phrase count
- **Content quality**: Sentence variety, flow, beach-specific details

### Quality Scores

Each section receives:
- Word count score (0-100)
- Uniqueness score (0-100)
- Readability score (0-100)
- Overall score (average of above)

### Acceptance Criteria

- Overall score ≥ 60
- No generic phrases
- No duplicate sentences
- Word counts within ranges
- Beach-specific content (not generic)

## Features

### 1. Rate Limiting

- 2-second delay between API calls
- Prevents API rate limit errors
- Configurable via `ContentGenerator::setMinDelay()`

### 2. Checkpointing

- Saves progress every 10 beaches
- Resume from last checkpoint on failure
- Checkpoint file: `scripts/content-generation-checkpoint.json`

### 3. Error Recovery

- Continues processing on single beach failure
- Logs all errors with beach context
- Generates detailed error report

### 4. Progress Tracking

- Real-time console output
- Log file: `scripts/content-generation.log`
- Summary report on completion

### 5. Batch Processing

- Default batch size: 50 beaches
- Configurable via `--batch-size` flag
- 2-second pause between batches

## CLI Options

```
--beach-id=ID         Generate for single beach (testing)
--start=ID            Resume from specific beach ID
--batch-size=N        Process N beaches per batch (default: 50)
--validate-only       Validate existing content only
--dry-run             Generate but don't save to database
--approve             Auto-approve and publish all content
--test-api            Test API connection and exit
--help                Show help message
```

## Usage Examples

### Testing with Single Beach

```bash
# Test generation for one beach
php scripts/generate-beach-content.php --beach-id=14122416-804d-47aa-9885-9ce3aecd8520

# Dry run (see output without saving)
php scripts/generate-beach-content.php --beach-id=abc123 --dry-run
```

### Batch Processing

```bash
# Process all beaches (default batch size: 50)
php scripts/generate-beach-content.php

# Custom batch size
php scripts/generate-beach-content.php --batch-size=25

# Resume from specific beach
php scripts/generate-beach-content.php --start=xyz789
```

### Validation

```bash
# Validate existing content
php scripts/generate-beach-content.php --validate-only
```

### Approval

```bash
# Generate and auto-approve
php scripts/generate-beach-content.php --approve
```

## Database Schema

Content saved to `beach_content_sections` table:

```sql
CREATE TABLE beach_content_sections (
    id TEXT PRIMARY KEY,
    beach_id TEXT NOT NULL,
    section_type TEXT NOT NULL,
    heading TEXT,
    content TEXT NOT NULL,
    word_count INTEGER DEFAULT 0,
    display_order INTEGER DEFAULT 0,
    status TEXT DEFAULT 'draft',
    generated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    approved_at TEXT,
    FOREIGN KEY (beach_id) REFERENCES beaches(id)
);
```

### Content Status Flow

1. **draft** - Initially generated content
2. **published** - Approved content (via `--approve` flag)

## Prompt Strategy

### Context Provided

- Beach name, municipality, coordinates
- Tags (surfing, snorkeling, family-friendly, etc.)
- Amenities (restrooms, parking, lifeguard, etc.)
- Municipality-specific context (geographic region, cultural notes)

### Variation Techniques

1. **Tag-driven emphasis**: Surfing beaches get surf condition details, family beaches get safety info
2. **Municipality patterns**: Island beaches include ferry logistics, urban beaches mention public transport
3. **Structural variation**: Varied sentence lengths, opening strategies, paragraph structures
4. **Avoiding generics**: Explicit instructions to avoid clichés and marketing language

### Content Guidelines

- Natural travel guide tone (not marketing)
- Specific to exact beach (not generic)
- Factually accurate for Puerto Rico
- Practical information for travelers
- Varied sentence structure
- No duplicate content across beaches

## Error Handling

### Common Issues

**API Connection Failure**
```bash
# Test connection first
php scripts/generate-beach-content.php --test-api
```

**JSON Parse Errors**
- Validator catches malformed JSON
- Error logged with beach context
- Processing continues with next beach

**Validation Failures**
- Content regenerated with warnings
- Saved as 'draft' for manual review
- Errors listed in final report

**Rate Limiting**
- 2-second automatic delay
- Increase with `setMinDelay()` if needed

## Performance

### Estimated Times

- Single beach: ~5-10 seconds
- 50-beach batch: ~8-12 minutes
- Full 468 beaches: ~3-4 hours (with rate limiting)

### Optimization Tips

1. **Increase batch size** for faster processing (but monitor API usage)
2. **Process during off-peak** to reduce API latency
3. **Use checkpointing** for long-running batches
4. **Monitor logs** in real-time: `tail -f scripts/content-generation.log`

## Quality Assurance

### Manual Review Process

1. Generate content in draft status
2. Run validation: `--validate-only`
3. Review low-scoring beaches manually
4. Regenerate problem beaches
5. Approve when satisfied: `--approve`

### Batch Review

```bash
# Check content in database
sqlite3 data/beach-finder.db

SELECT
    b.name,
    COUNT(c.id) as section_count,
    AVG(c.word_count) as avg_words,
    c.status
FROM beaches b
LEFT JOIN beach_content_sections c ON b.id = c.beach_id
GROUP BY b.id
HAVING section_count < 6;  -- Find incomplete beaches
```

## Monitoring

### Real-time Progress

```bash
# Watch log file
tail -f scripts/content-generation.log

# Monitor checkpoint
watch -n 5 cat scripts/content-generation-checkpoint.json
```

### Statistics Query

```sql
-- Content generation statistics
SELECT
    status,
    COUNT(*) as count,
    COUNT(DISTINCT beach_id) as beaches,
    AVG(word_count) as avg_words,
    MIN(word_count) as min_words,
    MAX(word_count) as max_words
FROM beach_content_sections
GROUP BY status;
```

## Troubleshooting

### Issue: API Key Invalid

```bash
# Verify environment variable
echo $ANTHROPIC_API_KEY

# Test connection
php scripts/generate-beach-content.php --test-api
```

### Issue: Checkpoint Won't Resume

```bash
# Check checkpoint file
cat scripts/content-generation-checkpoint.json

# Clear checkpoint to restart
rm scripts/content-generation-checkpoint.json
```

### Issue: Low Quality Scores

- Review prompts in `PromptBuilder.php`
- Adjust word count ranges
- Add municipality-specific context
- Refine generic phrase detection

### Issue: Database Locked

```bash
# Check for long-running processes
ps aux | grep generate-beach-content

# Ensure WAL mode is enabled
sqlite3 data/beach-finder.db "PRAGMA journal_mode;"
```

## Extending the System

### Add New Section Types

1. Update `PromptBuilder::SECTION_CONFIGS`
2. Add section to prompt instructions
3. Update database if needed
4. Adjust validation rules

### Custom Validation Rules

1. Edit `ContentValidator::validateSection()`
2. Add new quality checks
3. Update scoring algorithm
4. Test with `--validate-only`

### Different AI Models

```php
$generator = new ContentGenerator($apiKey);
$generator->setModel('claude-opus-4-5-20251101');  // Use Opus
```

## Production Deployment

### Pre-deployment Checklist

- [ ] Test with single beach
- [ ] Validate API key works
- [ ] Check database permissions
- [ ] Test checkpoint/resume
- [ ] Review quality scores
- [ ] Set up monitoring

### Deployment Steps

1. Set API key in environment
2. Test connection: `--test-api`
3. Run small batch: `--batch-size=10`
4. Review quality
5. Full generation if satisfied
6. Approve content: `--approve`

## Cost Estimation

### API Usage

- Model: Claude 3.5 Sonnet
- ~4,000 tokens per beach (input + output)
- 468 beaches = ~1.9M tokens total
- Estimated cost: ~$15-20 (check current pricing)

### Tips to Reduce Costs

1. Use `--dry-run` for testing
2. Test prompts on single beaches
3. Monitor token usage in logs
4. Use checkpointing to avoid re-generation

## Support

For issues or questions:

1. Check logs: `scripts/content-generation.log`
2. Review checkpoint: `scripts/content-generation-checkpoint.json`
3. Test single beach with `--dry-run`
4. Validate existing content with `--validate-only`
