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
  -x "${PLUGIN_DIR}/.editorconfig" \
  -x "${PLUGIN_DIR}/.agents/*" \
  -x "${PLUGIN_DIR}/.codex/*" \
  -x "${PLUGIN_DIR}/phpcs.xml" \
  -x "${PLUGIN_DIR}/phpcs.xml.dist" \
  -x "${PLUGIN_DIR}/phpunit.xml.dist" \
  -x "${PLUGIN_DIR}/composer.json" \
  -x "${PLUGIN_DIR}/composer.lock" \
  -x "${PLUGIN_DIR}/package.json" \
  -x "${PLUGIN_DIR}/package-lock.json" \
  -x "${PLUGIN_DIR}/node_modules/*" \
  -x "${PLUGIN_DIR}/vendor/*" \
  -x "${PLUGIN_DIR}/tests/*" \
  -x "${PLUGIN_DIR}/specs/*" \
  -x "${PLUGIN_DIR}/docs/*" \
  -x "${PLUGIN_DIR}/tools/*" \
  -x "${PLUGIN_DIR}/src/*" \
  -x "${PLUGIN_DIR}/public/*" \
  -x "${PLUGIN_DIR}/README.md" \
  -x "${PLUGIN_DIR}/.DS_Store" \
  -x "${PLUGIN_DIR}/build-zip.sh" \
  -x "${PLUGIN_DIR}/*.log" \
  -x "${PLUGIN_DIR}/*.zip"

echo ""
echo "ZIP created:"
echo "${ZIP_PATH}"

echo ""
echo "Checking excluded files..."
unzip -l "${ZIP_PATH}" | grep -E "(README\.md|\.git|\.github|phpcs\.xml|phpunit\.xml|specs/|tests/|docs/|tools/|src/|node_modules|vendor/|package\.json|composer\.json|build-zip\.sh|maintenance-mode-studio/public/)" && {
  echo ""
  echo "Warning: excluded files still found in ZIP."
  exit 1
} || {
  echo "Good. Excluded files were not found."
}

echo ""
echo "Done."
