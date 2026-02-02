# Content Generation Pre-Flight Checklist

Complete this checklist before starting full batch generation.

## Environment Setup

- [ ] API key obtained from Anthropic
- [ ] API key exported: `export ANTHROPIC_API_KEY='your-key'`
- [ ] API key verified: `echo $ANTHROPIC_API_KEY` returns key
- [ ] Database exists: `ls -lh data/beach-finder.db`
- [ ] Database writable: `ls -ld data/` shows www-data permissions

## System Verification

- [ ] Test components: `php scripts/test-content-generator.php`
  - PromptBuilder creates prompts
  - ContentValidator validates sections
  - Generic phrase detection works
  - Database connected
  - Sample beach retrieved

- [ ] Test API connection: `php scripts/generate-beach-content.php --test-api`
  - Returns: `✓ API connection successful`

## Single Beach Testing

- [ ] Dry run test: `php scripts/generate-beach-content.php --beach-id=14122416-804d-47aa-9885-9ce3aecd8520 --dry-run`
  - Generates 6 sections
  - Word counts within ranges
  - No generic phrases in warnings
  - Quality score > 60
  - Content reads naturally

- [ ] Save test: `php scripts/generate-beach-content.php --beach-id=14122416-804d-47aa-9885-9ce3aecd8520`
  - Saves to database successfully
  - Verify: `sqlite3 data/beach-finder.db "SELECT COUNT(*) FROM beach_content_sections"`
  - Should show 6 sections

- [ ] Manual review of saved content
  - Open database: `sqlite3 data/beach-finder.db`
  - Check content: `SELECT section_type, heading, word_count FROM beach_content_sections WHERE beach_id = '14122416-804d-47aa-9885-9ce3aecd8520';`
  - Read sample: `SELECT content FROM beach_content_sections WHERE section_type = 'history' LIMIT 1;`
  - Content is beach-specific (not generic)
  - Grammar and spelling correct
  - Natural travel guide tone

## Database Backup

- [ ] Backup database: `cp data/beach-finder.db data/beach-finder.db.backup-$(date +%Y%m%d)`
- [ ] Verify backup: `ls -lh data/beach-finder.db.backup-*`
- [ ] Backup size matches original

## Pre-Generation Planning

- [ ] Estimated time understood: ~3-4 hours for 468 beaches
- [ ] Estimated cost understood: ~$15-20 USD
- [ ] Ready to monitor process
- [ ] Terminal session will remain open (use `screen` or `tmux` if SSH)

## Monitoring Setup

- [ ] Log monitoring ready: `tail -f scripts/content-generation.log`
- [ ] Checkpoint monitoring ready: `watch -n 5 cat scripts/content-generation-checkpoint.json`
- [ ] Database query ready for progress:
  ```sql
  SELECT COUNT(DISTINCT beach_id) as beaches,
         COUNT(*) as sections
  FROM beach_content_sections;
  ```

## Generation Strategy

Choose one:

### Option A: Cautious (Recommended for First Time)
- [ ] Phase 1: 10 beaches
  - `php scripts/generate-beach-content.php --batch-size=10`
  - Review output
  - Manually check 2-3 beaches
  - Verify quality

- [ ] Phase 2: 50 beaches
  - `php scripts/generate-beach-content.php --batch-size=50`
  - Monitor logs
  - Spot check 5 beaches

- [ ] Phase 3: Remaining beaches
  - `php scripts/generate-beach-content.php`
  - Full monitoring

### Option B: Confident (If Testing Looks Good)
- [ ] Full generation in one run
  - `php scripts/generate-beach-content.php`
  - Monitor throughout

## Post-Generation Checklist

After generation completes:

- [ ] Review final statistics
- [ ] Check for missing beaches:
  ```sql
  SELECT COUNT(*) FROM beaches WHERE id NOT IN
  (SELECT DISTINCT beach_id FROM beach_content_sections);
  ```
- [ ] Validate content: `php scripts/generate-beach-content.php --validate-only`
- [ ] Manual review of 10-20 random beaches
- [ ] Check quality scores in logs
- [ ] Regenerate any low-quality beaches
- [ ] Approve content: `php scripts/generate-beach-content.php --approve`

## Recovery Planning

If something goes wrong:

- [ ] Know how to stop process: `Ctrl+C`
- [ ] Know checkpoint location: `scripts/content-generation-checkpoint.json`
- [ ] Know how to resume: Just re-run same command
- [ ] Know how to regenerate single beach: `--beach-id=ID`
- [ ] Have backup to restore: `cp data/beach-finder.db.backup-YYYYMMDD data/beach-finder.db`

## Quality Assurance

- [ ] Understand validation criteria:
  - Word counts: 400-600 for history, 200-300 for others
  - No generic phrases
  - No duplicate sentences
  - Readability score > 60
  - Beach-specific content

- [ ] Know how to check quality:
  ```sql
  SELECT b.name, c.section_type, c.word_count
  FROM beach_content_sections c
  JOIN beaches b ON c.beach_id = b.id
  WHERE c.word_count < 150 OR c.word_count > 700;
  ```

## Final Verification

Before starting, verify all green:

- ✅ API key working
- ✅ Test generation successful
- ✅ Database backed up
- ✅ Monitoring ready
- ✅ Time allocated
- ✅ Cost approved
- ✅ Recovery plan understood

## Start Generation

If all checked above:

```bash
# Set API key (if not persistent)
export ANTHROPIC_API_KEY='your-key-here'

# Start generation
php scripts/generate-beach-content.php

# Or with custom batch size
php scripts/generate-beach-content.php --batch-size=50
```

## During Generation

Monitor these:

- [ ] Console output shows progress
- [ ] No error messages in output
- [ ] Success rate stays high (>95%)
- [ ] Checkpoint saves every 10 beaches
- [ ] Log file grows: `ls -lh scripts/content-generation.log`

## Completion

When finished:

- [ ] Review final summary report
- [ ] Success rate > 95%
- [ ] Total beaches = 468
- [ ] Total sections = 2,808 (468 × 6)
- [ ] No critical errors in log

## Sign-off

- **Tested by**: _______________
- **Date**: _______________
- **Test results**: ✅ Pass / ❌ Fail
- **Ready for production**: ✅ Yes / ❌ No
- **Notes**: _______________
