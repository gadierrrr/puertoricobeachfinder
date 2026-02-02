#!/bin/bash
# Build CSS from partials
# Usage: ./scripts/build-css.sh

set -e

CSS_DIR="assets/css"
PARTIALS_DIR="$CSS_DIR/partials"
OUTPUT_FILE="$CSS_DIR/styles.css"

# Change to project root
cd "$(dirname "$0")/.."

echo "Building CSS from partials..."

# Concatenate partials in correct order
cat "$PARTIALS_DIR/_variables.css" \
    "$PARTIALS_DIR/_base.css" \
    "$PARTIALS_DIR/_loading.css" \
    "$PARTIALS_DIR/_cards.css" \
    "$PARTIALS_DIR/_filters.css" \
    "$PARTIALS_DIR/_conditions.css" \
    "$PARTIALS_DIR/_map.css" \
    "$PARTIALS_DIR/_drawers.css" \
    "$PARTIALS_DIR/_modals.css" \
    "$PARTIALS_DIR/_layout.css" \
    "$PARTIALS_DIR/_forms.css" \
    "$PARTIALS_DIR/_accessibility.css" \
    "$PARTIALS_DIR/_dark-mode.css" \
    "$PARTIALS_DIR/_responsive.css" \
    "$PARTIALS_DIR/_print.css" > "$OUTPUT_FILE"

# Get file size
SIZE=$(wc -c < "$OUTPUT_FILE" | tr -d ' ')
LINES=$(wc -l < "$OUTPUT_FILE" | tr -d ' ')

echo "✓ Built $OUTPUT_FILE ($LINES lines, $SIZE bytes)"
