#!/usr/bin/env bash
# bin/release.sh — build a correctly-structured theme ZIP and publish a GitHub release.
#
# Usage: bash bin/release.sh
#
# Requires: GITHUB_TOKEN env var  (or stored in macOS Keychain as
#           internet password for github.com with account s-k-l-e-n-k-a)
#
# What it does:
#   1. Reads the version from style.css
#   2. Creates skate.zip with `skate/` as the top-level folder (WordPress requirement)
#   3. Creates a GitHub release via API and uploads skate.zip as the release asset
#   4. Updates update-metadata.json with the new version + asset download URL
#   5. Commits and pushes the metadata update

set -euo pipefail

REPO="s-k-l-e-n-k-a/skatewp"
THEME_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$THEME_DIR"

# ── GitHub token ───────────────────────────────────────────────────────────────
if [ -z "${GITHUB_TOKEN:-}" ]; then
    GITHUB_TOKEN=$(security find-internet-password -s github.com -a s-k-l-e-n-k-a -w 2>/dev/null || true)
fi
if [ -z "${GITHUB_TOKEN:-}" ]; then
    echo "✖ GITHUB_TOKEN not set. Export it or store it in Keychain:"
    echo "  security add-internet-password -s github.com -a s-k-l-e-n-k-a -w <token>"
    exit 1
fi

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

echo "   ZIP built ($(du -sh "$ZIPPATH" | cut -f1))"

# ── Create GitHub release ─────────────────────────────────────────────────────
RELEASE_RESP=$(curl -sf -X POST \
    -H "Authorization: Bearer ${GITHUB_TOKEN}" \
    -H "Accept: application/vnd.github+json" \
    "https://api.github.com/repos/${REPO}/releases" \
    -d "{\"tag_name\":\"${TAG}\",\"name\":\"${TAG}\",\"body\":\"Release ${TAG}\"}")

UPLOAD_URL=$(echo "$RELEASE_RESP" | python3 -c "import sys,json; print(json.load(sys.stdin)['upload_url'])" | sed 's/{?name,label}//')

# ── Upload asset ──────────────────────────────────────────────────────────────
curl -sf -X POST \
    -H "Authorization: Bearer ${GITHUB_TOKEN}" \
    -H "Content-Type: application/zip" \
    "${UPLOAD_URL}?name=${ZIPNAME}" \
    --data-binary "@${ZIPPATH}" > /dev/null

ASSET_URL="https://github.com/${REPO}/releases/download/${TAG}/${ZIPNAME}"
echo "   Asset: ${ASSET_URL}"

# ── Update update-metadata.json ───────────────────────────────────────────────
cat > update-metadata.json <<EOF
{
  "version": "${VERSION}",
  "download_url": "${ASSET_URL}"
}
EOF

# ── Commit + push ──────────────────────────────────────────────────────────────
git add update-metadata.json
git commit -m "release: ${TAG}"
git push origin main

echo "✓ Done — ${TAG} live on GitHub"

rm -rf "${TMPDIR}"
