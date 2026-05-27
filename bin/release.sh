#!/usr/bin/env bash
# bin/release.sh — build a correctly-structured theme ZIP and publish a GitHub release.
#
# Usage: bash bin/release.sh
#
# What it does:
#   1. Reads the version from style.css
#   2. Creates skate.zip with `skate/` as the top-level folder (WordPress requirement)
#   3. Creates a GitHub release (tag must not exist yet) and uploads skate.zip as asset
#   4. Updates update-metadata.json with the new version + asset download URL
#   5. Commits and pushes the metadata update

set -euo pipefail

THEME_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$THEME_DIR"

# ── Read version ───────────────────────────────────────────────────────────────
VERSION=$(grep -m1 'Version:' style.css | sed 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')
TAG="v${VERSION}"
ZIPNAME="skate.zip"
TMPDIR=$(mktemp -d)
ZIPPATH="${TMPDIR}/${ZIPNAME}"

echo "→ Releasing ${TAG}"

# ── Build ZIP with skate/ as top-level dir ────────────────────────────────────
cd "${THEME_DIR}/.."
zip -r "${ZIPPATH}" skate/ \
    --exclude "skate/.git/*" \
    --exclude "skate/.gitignore" \
    --exclude "skate/node_modules/*" \
    --exclude "skate/bin/*" \
    --exclude "skate/mockups/*" \
    --exclude "skate/CLAUDE.md" \
    --exclude "skate/.DS_Store" \
    --exclude "skate/**/.DS_Store" \
    > /dev/null
cd "${THEME_DIR}"

echo "   ZIP: ${ZIPPATH}"

# ── Create GitHub release + upload asset ──────────────────────────────────────
gh release create "${TAG}" "${ZIPPATH}" \
    --title "${TAG}" \
    --notes "Release ${TAG}" \
    --repo s-k-l-e-n-k-a/skatewp

ASSET_URL="https://github.com/s-k-l-e-n-k-a/skatewp/releases/download/${TAG}/${ZIPNAME}"

# ── Update update-metadata.json ───────────────────────────────────────────────
cat > update-metadata.json <<EOF
{
  "version": "${VERSION}",
  "download_url": "${ASSET_URL}"
}
EOF

echo "   update-metadata.json → ${ASSET_URL}"

# ── Commit + push metadata ─────────────────────────────────────────────────────
git add update-metadata.json
git commit -m "release: update download_url to ${TAG} release asset"
git push origin main

echo "✓ Done — ${TAG} live on GitHub"

# ── Cleanup ───────────────────────────────────────────────────────────────────
rm -rf "${TMPDIR}"
