#!/usr/bin/env bash

set -euo pipefail

PLUGIN_SLUG="maneuvrez-maintenance-studio"
VERSION="${1:-1.0.2}"
SVN_ROOT="${SVN_ROOT:-/home/hurairah/work/wordpress/svn/maneuvrez-maintenance-studio}"
SOURCE_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TRUNK_DIR="${SVN_ROOT}/trunk"
TAG_DIR="${SVN_ROOT}/tags/${VERSION}"
ASSETS_DIR="${SVN_ROOT}/assets"

RUNTIME_PATHS=(
  "admin"
  "assets"
  "includes"
  "languages"
  "templates"
  "LICENSE"
  "maneuvrez-maintenance-studio.php"
  "readme.txt"
  "uninstall.php"
)

LISTING_ASSETS=(
  "banner-772x250.png"
  "banner-1544x500.png"
  "icon-128x128.png"
  "icon-256x256.png"
  "icon.svg"
)

die() {
  echo "Error: $*" >&2
  exit 1
}

copy_runtime_files() {
  local destination="$1"

  mkdir -p "${destination}"

  for path in "${RUNTIME_PATHS[@]}"; do
    [[ -e "${SOURCE_ROOT}/${path}" ]] || die "Missing runtime source path: ${path}"

    if [[ -e "${destination}/${path}" ]]; then
      svn delete --force "${destination}/${path}" >/dev/null 2>&1 || rm -rf "${destination:?}/${path}"
    fi

    cp -R "${SOURCE_ROOT}/${path}" "${destination}/${path}"
  done

  svn add --force "${destination}" >/dev/null
}

copy_listing_assets() {
  mkdir -p "${ASSETS_DIR}"

  for filename in "${LISTING_ASSETS[@]}"; do
    if [[ -f "${SOURCE_ROOT}/media/${filename}" ]]; then
      cp "${SOURCE_ROOT}/media/${filename}" "${ASSETS_DIR}/${filename}"
      svn add --force "${ASSETS_DIR}/${filename}" >/dev/null
    else
      echo "Warning: media/${filename} not found; leaving SVN asset unchanged."
    fi
  done
}

[[ -d "${SVN_ROOT}/.svn" ]] || die "SVN checkout not found at ${SVN_ROOT}"
[[ -d "${TRUNK_DIR}" ]] || die "SVN trunk directory not found at ${TRUNK_DIR}"
[[ -d "${SVN_ROOT}/tags" ]] || die "SVN tags directory not found at ${SVN_ROOT}/tags"
command -v svn >/dev/null 2>&1 || die "svn command is not available"

if [[ -e "${TAG_DIR}" ]]; then
  die "Tag ${VERSION} already exists at ${TAG_DIR}. Remove it manually if you need to recreate it."
fi

echo "Preparing WordPress.org SVN release ${VERSION}"
echo "Source: ${SOURCE_ROOT}"
echo "SVN:    ${SVN_ROOT}"
echo ""

echo "Updating SVN checkout..."
svn update "${SVN_ROOT}"

echo ""
echo "Copying runtime files into trunk..."
copy_runtime_files "${TRUNK_DIR}"

echo ""
echo "Copying listing assets from media/ when available..."
copy_listing_assets

echo ""
echo "Creating tag ${VERSION} from prepared trunk..."
mkdir -p "${TAG_DIR}"
copy_runtime_files "${TAG_DIR}"

echo ""
echo "Prepared. Next commands:"
echo "  cd ${SVN_ROOT}"
echo "  svn status"
echo "  svn diff"
echo "  svn commit -m \"Release ${VERSION}\""
