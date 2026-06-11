#!/usr/bin/env bash

set -euo pipefail

PLUGIN_DIR="$(basename "$PWD")"
ZIP_NAME="${PLUGIN_DIR}.zip"
PARENT_DIR="$(dirname "$PWD")"
ZIP_PATH="${PARENT_DIR}/${ZIP_NAME}"

echo "Building WordPress plugin ZIP..."
echo "Plugin folder: ${PLUGIN_DIR}"
echo "Output ZIP: ${ZIP_PATH}"

rm -f "${ZIP_PATH}"

cd "${PARENT_DIR}"

zip -r "${ZIP_NAME}" "${PLUGIN_DIR}" \
  -x "${PLUGIN_DIR}/.git/*" \
  -x "${PLUGIN_DIR}/.github/*" \
  -x "${PLUGIN_DIR}/.gitignore" \
  -x "${PLUGIN_DIR}/.distignore" \
  -x "${PLUGIN_DIR}/.agents/*" \
  -x "${PLUGIN_DIR}/.codex/*" \
  -x "${PLUGIN_DIR}/phpcs.xml" \
  -x "${PLUGIN_DIR}/phpcs.xml.dist" \
  -x "${PLUGIN_DIR}/composer.json" \
  -x "${PLUGIN_DIR}/composer.lock" \
  -x "${PLUGIN_DIR}/package.json" \
  -x "${PLUGIN_DIR}/package-lock.json" \
  -x "${PLUGIN_DIR}/node_modules/*" \
  -x "${PLUGIN_DIR}/vendor/bin/*" \
  -x "${PLUGIN_DIR}/tests/*" \
  -x "${PLUGIN_DIR}/specs/*" \
  -x "${PLUGIN_DIR}/docs/*" \
  -x "${PLUGIN_DIR}/.DS_Store" \
  -x "${PLUGIN_DIR}/build-zip.sh"

echo ""
echo "ZIP created:"
echo "${ZIP_PATH}"

echo ""
echo "Checking excluded files..."
unzip -l "${ZIP_PATH}" | grep -E "(\.git|\.github|phpcs\.xml|specs/|tests/|node_modules|package\.json|composer\.json|build-zip\.sh)" && {
  echo ""
  echo "Warning: excluded files still found in ZIP."
  exit 1
} || {
  echo "Good. Excluded files were not found."
}

echo ""
echo "Done."