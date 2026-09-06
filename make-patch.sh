#!/usr/bin/env bash

# Exit on error
set -e

# ==============================================================================
# Edusfera Lightweight Patch Generator (for slow internet connection)
#
# Usage:
#   ./make-patch.sh              -> Package all uncommitted/modified & new files
#   ./make-patch.sh HEAD~1       -> Package all files changed in last commit
#   ./make-patch.sh <commit-hash> -> Package all files changed since commit
# ==============================================================================

REF="${1:-working_tree}"
OUTPUT_ZIP="edusfera-patch.zip"

echo "⚡ Generating lightweight incremental patch archive..."

# Remove previous patch zip if it exists
rm -f "$OUTPUT_ZIP"

# Temporary directory for bundling patch
TMP_DIR=$(mktemp -d 2>/dev/null || mktemp -d -t 'patch')
trap 'rm -rf "$TMP_DIR"' EXIT

FILES_LIST="$TMP_DIR/patch_files.txt"
touch "$FILES_LIST"

if [ "$REF" = "working_tree" ]; then
    echo "🔍 Collecting modified, added, and untracked files from working tree..."
    git status --porcelain | grep -v 'edusfera-patch.zip' | grep -v 'edusfera-release.zip' | awk '{if ($1 != "D" && $2 != "D") print $2}' >> "$FILES_LIST"
else
    echo "🔍 Collecting files changed since $REF..."
    git diff --name-only --diff-filter=ACMRT "$REF" >> "$FILES_LIST"
fi

# Always include public/build if compiled assets exist
if [ -d "public/build" ]; then
    find public/build -type f >> "$FILES_LIST"
fi

# Deduplicate file list
sort -u "$FILES_LIST" -o "$FILES_LIST"

COUNT=0
while IFS= read -r FILE; do
    # Strip quotes if git output quoted filenames
    FILE=$(echo "$FILE" | sed -e 's/^"//' -e 's/"$//')

    if [ -f "$FILE" ] && [[ "$FILE" != node_modules/* ]] && [[ "$FILE" != .git/* ]] && [[ "$FILE" != *.zip ]] && [[ "$FILE" != *.docx ]] && [[ "$FILE" != *.code-workspace ]] && [[ "$FILE" != .codegraph/* ]] && [[ "$FILE" != .gemini/* ]] && [[ "$FILE" != .agents/* ]] && [[ "$FILE" != "Документы на платформу/"* ]] && [[ "$FILE" != docs/business_plan_* ]]; then
        TARGET_FILE_DIR="$TMP_DIR/$(dirname "$FILE")"
        mkdir -p "$TARGET_FILE_DIR"
        cp "$FILE" "$TMP_DIR/$FILE"
        COUNT=$((COUNT + 1))
        echo "  + $FILE"
    fi
done < "$FILES_LIST"

if [ "$COUNT" -eq 0 ]; then
    echo "⚠️ No modified or new files to package into patch!"
    exit 0
fi

# Create zip archive
(cd "$TMP_DIR" && zip -r "$OLDPWD/$OUTPUT_ZIP" .) > /dev/null

SIZE=$(ls -lh "$OUTPUT_ZIP" | awk '{print $5}')

echo ""
echo "=============================================================================="
echo "✅ Incremental Patch Created Successfully!"
echo "📦 File: $OUTPUT_ZIP"
echo "📊 Size: $SIZE (only $COUNT files instead of full 118MB release!)"
echo "=============================================================================="
echo ""
echo "🚀 Instructions for Uploading on Slow Internet:"
echo "1. Upload '$OUTPUT_ZIP' ($SIZE) to hosting root folder via File Manager."
echo "2. Unzip '$OUTPUT_ZIP' overwriting existing files."
echo "3. Run 'php artisan config:clear && php artisan cache:clear' (or deploy.sh)."
echo "=============================================================================="
