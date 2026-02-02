# Content Generation System Architecture

Complete technical documentation for the beach content generation system.

## System Overview

A production-ready, scalable content generation system that creates unique, high-quality extended content for 468 Puerto Rico beaches using Claude AI.

### Key Features

- **Batch Processing**: Handles 468 beaches efficiently with configurable batch sizes
- **Quality Assurance**: Multi-level validation ensuring content meets standards
- **Error Recovery**: Automatic checkpointing and resume capability
- **Rate Limiting**: Built-in delays to respect API limits
- **Progress Tracking**: Real-time monitoring and comprehensive logging
- **Content Validation**: Automated quality checks for uniqueness and readability

## Architecture Components

```
┌─────────────────────────────────────────────────────────────┐
│                  generate-beach-content.php                  │
│              (CLI Orchestrator & User Interface)             │
└─────────────────────┬───────────────────────────────────────┘
                      │
         ┌────────────┼────────────┐
         │            │            │
         ▼            ▼            ▼
┌────────────┐ ┌──────────┐ ┌──────────────┐
│  Prompt    │ │ Content  │ │   Content    │
│  Builder   │ │Generator │ │  Validator   │
└────────────┘ └──────────┘ └──────────────┘
         │            │            │
         └────────────┼────────────┘
                      │
                      ▼
              ┌───────────────┐
              │    Batch      │
              │   Processor   │
              └───────┬───────┘
                      │
         ┌────────────┼────────────┐
         ▼            ▼            ▼
    ┌────────┐  ┌─────────┐  ┌─────────┐
    │Database│  │  Logs   │  │Checkpoint│
    └────────┘  └─────────┘  └─────────┘
```

## Component Specifications

### 1. PromptBuilder

**File**: `scripts/content/PromptBuilder.php`

**Responsibilities**:
- Construct context-rich prompts for Claude AI
- Include beach metadata (name, municipality, coordinates, tags, amenities)
- Apply variation strategies to prevent repetitive content
- Define section requirements and word count targets

**Key Methods**:
```php
buildPrompt(array $beachData, array $tags, array $amenities): string
  - Builds complete prompt for all 6 sections
  - Returns: Formatted prompt string

getSectionConfigs(): array
  - Returns section definitions with word count ranges
  - Static method for accessing config

buildTagEmphasis(array $tags): string
  - Creates tag-specific content guidance
  - Private helper method

buildVariationGuidance(array $beach): string
  - Generates variation instructions
  - Handles island vs urban beaches differently
```

**Section Configurations**:
```php
const SECTION_CONFIGS = [
    'history' => [
        'heading' => 'History & Background',
        'min_words' => 400,
        'max_words' => 600,
        'description' => 'Historical background, cultural significance, naming origin'
    ],
    'best_time' => [
        'heading' => 'Best Time to Visit',
        'min_words' => 200,
        'max_words' => 300,
        'description' => 'Seasonal patterns, weather considerations, crowd levels'
    ],
    // ... 4 more sections
];
```

**Municipality Context**:
- Provides geographic and cultural context per municipality
- Examples: "Rincon - Surfing capital, west coast sunset views"
- Used to generate location-specific content

**Variation Strategies**:
1. **Tag-driven emphasis**: Surfing beaches get surf condition details
2. **Municipality patterns**: Island beaches mention ferry logistics
3. **Structural variation**: Mixed sentence lengths and openings
4. **Generic phrase avoidance**: Explicit instructions to avoid clichés

### 2. ContentGenerator

**File**: `scripts/content/ContentGenerator.php`

**Responsibilities**:
- Interface with Claude API
- Handle rate limiting
- Parse and validate API responses
- Manage API connection testing

**Key Methods**:
```php
generateBeachContent(array $beachData, array $tags, array $amenities): array
  - Main generation method
  - Returns: Array of 6 sections with content

callClaudeAPI(string $prompt): string
  - Makes HTTP POST to Anthropic API
  - Handles authentication and errors
  - Returns: Raw API response text

parseResponse(string $response): array
  - Parses JSON response
  - Validates structure
  - Enriches with word counts
  - Returns: Structured sections array

testConnection(): bool
  - Tests API connectivity
  - Returns: true if API key valid

enforceRateLimit(): void
  - Ensures minimum delay between requests
  - Uses microtime for precision
```

**Configuration**:
```php
private $apiKey;              // Anthropic API key
private $model = 'claude-3-5-sonnet-20241022';
private $maxTokens = 4000;    // Max response tokens
private $minDelay = 2;        // Seconds between requests
```

**Rate Limiting Algorithm**:
```php
if ($this->lastRequestTime > 0) {
    $elapsed = microtime(true) - $this->lastRequestTime;
    $remaining = $this->minDelay - $elapsed;
    if ($remaining > 0) {
        usleep((int)($remaining * 1000000));
    }
}
$this->lastRequestTime = microtime(true);
```

**Error Handling**:
- cURL errors: Throws exception with error message
- HTTP errors: Parses error JSON, throws with API message
- JSON parse errors: Throws with parse error and response preview

### 3. ContentValidator

**File**: `scripts/content/ContentValidator.php`

**Responsibilities**:
- Validate word counts against requirements
- Detect generic/cliché phrases
- Check for duplicate sentences across beaches
- Calculate readability scores
- Assess content quality

**Key Methods**:
```php
validateSection(array $section, string $beachName): array
  - Validates single section
  - Returns: Validation result with scores and warnings

validateBeach(array $sections, string $beachName): array
  - Validates all 6 sections for a beach
  - Returns: Comprehensive validation report

detectGenericPhrases(string $content): array
  - Finds overused phrases
  - Returns: List of found generic phrases

calculateReadability(string $content): int
  - Computes Flesch Reading Ease score
  - Returns: Score 0-100 (60-100 is good)

detectDuplicateSentences(string $content, string $sectionType): array
  - Checks for sentence reuse
  - Uses cache to track seen sentences
  - Returns: List of duplicate sentences
```

**Validation Criteria**:

1. **Word Count Scoring**:
   ```php
   if ($actual < $min) {
       score = ($actual / $min) * 100
   } elseif ($actual > $max) {
       penalty = min(50, overage / 10)
       score = max(50, 100 - penalty)
   } else {
       score = 100  // Perfect
   }
   ```

2. **Uniqueness Scoring**:
   ```php
   score = 100 - (genericPhraseCount * 20)
   // Each generic phrase: -20 points
   ```

3. **Readability Scoring**:
   ```php
   // Flesch Reading Ease formula
   score = 206.835 - (1.015 * avgSentenceLength) - (84.6 * avgSyllablesPerWord)
   // Normalized to 0-100
   ```

4. **Overall Section Score**:
   ```php
   overall = (wordCountScore + uniquenessScore + readabilityScore) / 3
   ```

**Quality Checks**:
- Short sentences (< 5 words): Flags if > 2
- Repetitive sentence starts: Flags if same word starts 3+ sentences
- "The beach" overuse: Warns if used 5+ times
- Beach name mention: Warns if beach name not in content

**Generic Phrases List**:
```php
const GENERIC_PHRASES = [
    'crystal clear waters',
    'hidden gem',
    'paradise',
    'pristine',
    'breathtaking',
    'slice of heaven',
    'tropical paradise',
    // ... 20+ total phrases
];
```

### 4. BatchProcessor

**File**: `scripts/content/BatchProcessor.php`

**Responsibilities**:
- Manage batch processing workflow
- Save/load checkpoints for resume capability
- Coordinate between Generator and Validator
- Database operations (save sections)
- Progress logging and reporting

**Key Methods**:
```php
processBatches(array $options): array
  - Main batch processing loop
  - Returns: Statistics array

processBeach(array $beach, bool $dryRun, bool $validateOnly): array
  - Process single beach
  - Returns: Success/failure with scores

getBeachesToProcess(?string $startBeachId, ?array $checkpoint): array
  - Query beaches from database
  - Handles resume from checkpoint
  - Returns: Array of beach records

saveSections(string $beachId, array $sections): void
  - Delete existing draft sections
  - Insert new sections
  - Sets display_order

saveCheckpoint(array $data): void
  - Writes checkpoint JSON file
  - Includes last beach ID and timestamp

loadCheckpoint(): ?array
  - Loads checkpoint if exists
  - Returns: Checkpoint data or null

log(string $message, string $level): void
  - Writes to log file and console
  - Includes timestamp and level
```

**Checkpoint Format**:
```json
{
  "last_beach_id": "abc-123-def-456",
  "last_beach_name": "Survival Beach",
  "processed": 150,
  "timestamp": "2026-02-01 14:30:00"
}
```

**Statistics Format**:
```php
[
    'total' => 468,           // Total beaches to process
    'processed' => 150,       // Number processed
    'succeeded' => 145,       // Successful generations
    'failed' => 5,            // Failed generations
    'skipped' => 0,           // Skipped beaches
    'errors' => [             // Array of error details
        [
            'beach_id' => 'abc-123',
            'beach_name' => 'Test Beach',
            'error' => 'Validation failed: ...'
        ]
    ],
    'start_time' => 1234567890,
    'end_time' => 1234567890,
    'duration' => 3600        // Seconds
]
```

**Save Interval**: Checkpoint saved every 10 beaches

**Batch Flow**:
```
1. Load checkpoint (if exists)
2. Get beaches to process (resume from checkpoint)
3. Split into batches of N beaches
4. For each batch:
   a. For each beach:
      - Get tags/amenities
      - Generate content (or validate)
      - Validate generated content
      - Save to database (unless dry run)
      - Update statistics
      - Save checkpoint every 10 beaches
   b. Sleep 2 seconds between batches
5. Generate final report
6. Clear checkpoint if successful
```

### 5. Main CLI Script

**File**: `scripts/generate-beach-content.php`

**Responsibilities**:
- Command-line interface
- Argument parsing
- User interaction (confirmation prompts)
- Component initialization
- Error handling and reporting

**CLI Arguments**:
```
--beach-id=ID         Single beach mode
--start=ID            Start from specific beach
--batch-size=N        Beaches per batch (default: 50)
--validate-only       Only validate, don't generate
--dry-run             Generate but don't save
--approve             Set status='published' after generation
--test-api            Test API connection and exit
--help                Show help message
```

**Execution Flow**:
```
1. Parse command-line arguments
2. Show help if requested
3. Check for ANTHROPIC_API_KEY
4. Initialize components:
   - ContentGenerator
   - ContentValidator
   - BatchProcessor
5. Test API if requested
6. Configure batch processor
7. Prepare process options
8. Show configuration summary
9. Confirm with user (for full batches)
10. Process beaches
11. Show final report
12. Approve if requested
13. Exit with status code
```

**Color Output**:
```php
function colorize($text, $color): string
  - Adds ANSI color codes
  - Colors: red, green, yellow, blue

function success($msg): void  // Green ✓
function error($msg): void    // Red ✗
function warning($msg): void  // Yellow ⚠
function info($msg): void     // Blue ℹ
```

## Data Flow

### Generation Flow

```
1. User runs CLI command
   ↓
2. CLI initializes components with API key
   ↓
3. BatchProcessor queries beaches from database
   ↓
4. For each beach:
   a. Get tags/amenities from database
   b. PromptBuilder creates context-rich prompt
   c. ContentGenerator calls Claude API
   d. API returns JSON with 6 sections
   e. ContentGenerator parses JSON
   f. ContentValidator validates all sections
   g. BatchProcessor saves to database (status='draft')
   h. Update statistics
   i. Save checkpoint every 10 beaches
   ↓
5. Generate final report
   ↓
6. Optional: Approve (set status='published')
```

### Database Schema

```sql
CREATE TABLE beach_content_sections (
    id TEXT PRIMARY KEY,                    -- UUID v4
    beach_id TEXT NOT NULL,                 -- FK to beaches(id)
    section_type TEXT NOT NULL,             -- history, best_time, etc.
    heading TEXT,                           -- Display heading
    content TEXT NOT NULL,                  -- Actual content
    word_count INTEGER DEFAULT 0,           -- Word count
    metadata TEXT,                          -- JSON metadata (unused currently)
    display_order INTEGER DEFAULT 0,        -- Order for display
    status TEXT DEFAULT 'draft',            -- draft or published
    generated_at TEXT DEFAULT CURRENT_TIMESTAMP,
    approved_at TEXT,                       -- When approved
    approved_by TEXT,                       -- Who approved (unused)
    version INTEGER DEFAULT 1,              -- Version number
    FOREIGN KEY (beach_id) REFERENCES beaches(id) ON DELETE CASCADE,
    UNIQUE(beach_id, section_type, version)
);
```

**Indexes**:
- `idx_beach_content_beach_id` on `beach_id`
- `idx_beach_content_type` on `section_type`
- `idx_beach_content_status` on `status`
- `idx_beach_content_beach_type` on `(beach_id, section_type)`

### File System

```
scripts/
├── generate-beach-content.php              # Main CLI script
├── test-content-generator.php              # Test script
├── content-generation.log                  # Log file (generated)
├── content-generation-checkpoint.json      # Checkpoint (generated)
├── CONTENT-GENERATION-GUIDE.md            # User guide
├── GENERATION-CHECKLIST.md                # Pre-flight checklist
├── SYSTEM-ARCHITECTURE.md                 # This file
└── content/
    ├── PromptBuilder.php
    ├── ContentGenerator.php
    ├── ContentValidator.php
    ├── BatchProcessor.php
    └── README.md
```

## Performance Characteristics

### Time Complexity

- **Single beach**: O(1) - ~5-10 seconds
- **N beaches**: O(N) - Linear with rate limiting
- **Full 468 beaches**: ~3-4 hours

### Space Complexity

- **Memory**: O(1) - Processes one beach at a time
- **Database**: O(N × 6) - 6 sections per beach
- **Logs**: O(N) - One log entry per beach

### API Usage

- **Requests per beach**: 1
- **Tokens per beach**: ~4,000 (2,500 input + 1,500 output)
- **Total tokens (468 beaches)**: ~1.87M
- **Rate**: 0.5 requests/second (with 2s delay)

### Database Operations

Per beach:
- 1 SELECT (get beach data)
- 1 SELECT (get tags)
- 1 SELECT (get amenities)
- 1 DELETE (remove old draft sections)
- 6 INSERT (save new sections)

Total: 9 queries per beach

## Error Handling Strategy

### Levels of Error Handling

1. **API Level** (ContentGenerator)
   - cURL errors → Exception
   - HTTP errors → Exception with API message
   - JSON parse errors → Exception

2. **Validation Level** (ContentValidator)
   - Word count issues → Warnings
   - Generic phrases → Warnings
   - Missing sections → Errors

3. **Batch Level** (BatchProcessor)
   - Single beach failure → Log and continue
   - Database errors → Log and continue
   - Checkpoint save → Silent fail (non-critical)

4. **CLI Level** (Main script)
   - Fatal errors → Exit with code 1
   - Validation failures → Continue but exit 1
   - Success → Exit with code 0

### Recovery Mechanisms

1. **Automatic Checkpoint**
   - Saves every 10 beaches
   - Includes last beach ID
   - Resume by re-running same command

2. **Graceful Degradation**
   - Continue on single beach failure
   - Collect errors for final report
   - Don't stop entire batch

3. **Retry Strategy**
   - Manual: Re-run with `--beach-id`
   - Automatic: Rate limit enforced, no retries

## Configuration

### Constants

**PromptBuilder**:
```php
SECTION_CONFIGS        // Section definitions
GENERIC_PHRASES_TO_AVOID  // Phrases to avoid
MUNICIPALITY_CONTEXT   // Location context
```

**ContentValidator**:
```php
GENERIC_PHRASES       // Detection list (30+ phrases)
```

**ContentGenerator**:
```php
$model = 'claude-3-5-sonnet-20241022'
$maxTokens = 4000
$minDelay = 2  // seconds
```

**BatchProcessor**:
```php
$batchSize = 50
$saveInterval = 10  // beaches between checkpoints
```

### Environment Variables

```bash
ANTHROPIC_API_KEY     # Required: API key for Claude
```

## Testing

### Test Script

**File**: `scripts/test-content-generator.php`

**Tests**:
1. PromptBuilder creates valid prompts
2. Section configs are correct
3. ContentValidator validates sections
4. Generic phrase detection works
5. Database connection works
6. Sample beach data retrieved

**Usage**:
```bash
php scripts/test-content-generator.php
```

### Manual Testing

```bash
# Test API connection
php scripts/generate-beach-content.php --test-api

# Test single beach (dry run)
php scripts/generate-beach-content.php --beach-id=ID --dry-run

# Test single beach (save)
php scripts/generate-beach-content.php --beach-id=ID

# Validate existing content
php scripts/generate-beach-content.php --validate-only
```

## Monitoring & Observability

### Logging

**Log File**: `scripts/content-generation.log`

**Format**:
```
[2026-02-01 14:30:00] [INFO] Starting batch processing: 468 beaches
[2026-02-01 14:30:05] [INFO] ✓ Survival Beach - Score: 85
[2026-02-01 14:30:10] [ERROR] ✗ Test Beach - Validation failed: ...
```

**Levels**:
- INFO: Normal progress
- ERROR: Failures

### Checkpoint

**File**: `scripts/content-generation-checkpoint.json`

**Purpose**: Resume capability after interruption

**Contents**:
- Last processed beach ID
- Last processed beach name
- Count of processed beaches
- Timestamp

### Progress Queries

```sql
-- Check progress
SELECT COUNT(DISTINCT beach_id) as beaches_done,
       COUNT(*) as sections_done
FROM beach_content_sections;

-- Find missing beaches
SELECT COUNT(*) FROM beaches WHERE id NOT IN
  (SELECT DISTINCT beach_id FROM beach_content_sections);

-- Quality statistics
SELECT
    AVG(word_count) as avg_words,
    MIN(word_count) as min_words,
    MAX(word_count) as max_words
FROM beach_content_sections
GROUP BY section_type;
```

## Security Considerations

### API Key Protection

- Stored in environment variable (not in code)
- Not logged or printed
- Not stored in database
- Not in version control

### Database Safety

- Uses parameterized queries (prevents SQL injection)
- Validates beach IDs before queries
- Atomic transactions (DELETE + INSERT)

### Input Validation

- Beach data from trusted database
- No user-generated content in prompts
- API responses parsed and validated

## Extensibility

### Adding New Section Types

1. Update `PromptBuilder::SECTION_CONFIGS`
2. Add to prompt instructions
3. Update validation logic if needed
4. Adjust display_order

### Using Different Models

```php
$generator->setModel('claude-opus-4-5-20251101');
```

### Custom Validation Rules

Extend `ContentValidator`:
```php
private function customValidation($content) {
    // Add custom logic
}
```

### Integration Points

1. **Website Integration**: Query published sections in `beach.php`
2. **API Endpoints**: Expose sections via JSON API
3. **Search Integration**: Index section content
4. **Export**: Generate PDF guides from sections

## Maintenance

### Regenerating Content

Single beach:
```bash
php scripts/generate-beach-content.php --beach-id=ID
```

Batch regeneration:
```sql
DELETE FROM beach_content_sections WHERE beach_id IN (...);
```
Then run generation.

### Clearing Checkpoint

```bash
rm scripts/content-generation-checkpoint.json
```

### Log Rotation

```bash
mv scripts/content-generation.log \
   scripts/content-generation.log.$(date +%Y%m%d)
```

## Performance Tuning

### Faster Generation

1. Increase batch size: `--batch-size=100`
2. Reduce delay: `$generator->setMinDelay(1)`
3. Use faster model (if available)

**Warning**: May hit API rate limits

### Quality Over Speed

1. Smaller batches for better monitoring
2. Increase delay for stability
3. Use higher-quality model (Opus)

## Cost Optimization

### Reduce Costs

1. Test with `--dry-run` first
2. Validate prompts on single beach
3. Use checkpointing (avoid re-generation)
4. Monitor token usage

### Token Efficiency

- Concise prompts (but sufficient context)
- Clear instructions (reduce retries)
- Efficient JSON structure

## Conclusion

This system provides a robust, scalable solution for generating high-quality beach content at scale. Key strengths:

- **Reliability**: Checkpointing and error recovery
- **Quality**: Multi-level validation
- **Efficiency**: Batch processing with rate limiting
- **Maintainability**: Modular architecture
- **Observability**: Comprehensive logging

Ready for production use with proper API key and database setup.
