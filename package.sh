#!/usr/bin/env bash
# ==============================================================================
# Packaging script for mod_knowledgebattle (Moodle Marketplace ready)
# ==============================================================================
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_NAME="knowledgebattle"
DIST_ZIP="${SCRIPT_DIR}/${PLUGIN_NAME}.zip"
TEMP_BUILD_DIR="$(mktemp -d -t kb_package_XXXXXX)"

echo "=== Packaging ${PLUGIN_NAME} for Moodle Marketplace ==="

# Step 1: Pre-flight checks
echo "[1/4] Running PHP syntax validation (php -l)..."
find "${SCRIPT_DIR}" -name "*.php" -not -path "*/knowledgebattle-docs/*" -exec php -l {} + > /dev/null
echo "  -> All PHP files passed syntax check."

# Step 2: AMD build verification
echo "[2/4] Verifying AMD minified assets..."
if [ ! -f "${SCRIPT_DIR}/amd/build/battle.min.js" ] || [ ! -f "${SCRIPT_DIR}/amd/build/question_manager.min.js" ]; then
    echo "  -> Building missing AMD minified files using terser..."
    npx --yes terser "${SCRIPT_DIR}/amd/src/battle.js" -c -m --source-map "url=battle.min.js.map" -o "${SCRIPT_DIR}/amd/build/battle.min.js"
    npx --yes terser "${SCRIPT_DIR}/amd/src/question_manager.js" -c -m --source-map "url=question_manager.min.js.map" -o "${SCRIPT_DIR}/amd/build/question_manager.min.js"
fi
echo "  -> AMD build assets verified."

# Step 3: Clean copy into standard Moodle root directory
echo "[3/4] Assembling clean plugin structure under '${PLUGIN_NAME}/'..."
TARGET_DIR="${TEMP_BUILD_DIR}/${PLUGIN_NAME}"
mkdir -p "${TARGET_DIR}"

# Use rsync or cp with exclude patterns
if command -v rsync > /dev/null 2>&1; then
    rsync -a \
        --exclude=".git" \
        --exclude=".gitignore" \
        --exclude=".gitattributes" \
        --exclude="knowledgebattle-docs" \
        --exclude="package.sh" \
        --exclude="*.zip" \
        --exclude="*.tar.gz" \
        --exclude=".DS_Store" \
        --exclude="Thumbs.db" \
        --exclude=".idea" \
        --exclude=".vscode" \
        --exclude="node_modules" \
        "${SCRIPT_DIR}/" "${TARGET_DIR}/"
else
    cp -R "${SCRIPT_DIR}/." "${TARGET_DIR}/"
    rm -rf "${TARGET_DIR}/.git" \
           "${TARGET_DIR}/.gitignore" \
           "${TARGET_DIR}/.gitattributes" \
           "${TARGET_DIR}/knowledgebattle-docs" \
           "${TARGET_DIR}/package.sh" \
           "${TARGET_DIR}/"*.zip \
           "${TARGET_DIR}/.DS_Store" \
           "${TARGET_DIR}/.idea" \
           "${TARGET_DIR}/.vscode" 2>/dev/null || true
fi

# Step 4: Create distribution ZIP
echo "[4/4] Generating ${PLUGIN_NAME}.zip..."
rm -f "${DIST_ZIP}"
(
    cd "${TEMP_BUILD_DIR}"
    zip -r -q "${DIST_ZIP}" "${PLUGIN_NAME}"
)

# Cleanup temporary folder
rm -rf "${TEMP_BUILD_DIR}"

ZIP_SIZE=$(du -h "${DIST_ZIP}" | cut -f1)
FILE_COUNT=$(unzip -l "${DIST_ZIP}" | tail -n 1 | awk '{print $2}')

echo "=========================================================="
echo " Packaging completed successfully!"
echo " Output archive: ${DIST_ZIP}"
echo " Archive size:   ${ZIP_SIZE}"
echo " Files included: ${FILE_COUNT}"
echo " Root directory: ${PLUGIN_NAME}/"
echo "=========================================================="
