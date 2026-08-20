#!/usr/bin/env bash
# ============================================================
# Shrinks the show and group photos to web size.
#
# The sliders display the images at around 300 px wide, so 600 px on retina.
# The lightbox serves the same file and is therefore the only place where
# more resolution becomes visible at all — 1600 px covers that with room to
# spare. There is currently ~14 MB here; afterwards it should be under 2 MB.
#
# What is measured is the LONG edge, not the width. That is the difference
# between "does something" and "does nothing": the show photos are portrait.
# The largest is 1536 x 2048 — 1536 px wide, so below any width limit of
# 1600, and still twice as tall as needed. A rule on the width would leave
# untouched exactly the files this script exists for.
#
#   bash tools/optimize-images.sh            # report only, change nothing
#   bash tools/optimize-images.sh --apply    # actually overwrite
#
# --apply overwrites the originals. Commit first, and a
# `git checkout -- public/images/` is still the way back.
# ============================================================

set -euo pipefail

MAX_EDGE=1600
QUALITY=82

APPLY=0
[[ "${1:-}" == "--apply" ]] && APPLY=1

cd "$(dirname "$0")/.."

# --- Find a tool: ImageMagick, otherwise Pillow --------------------------
if command -v magick >/dev/null 2>&1; then
  # "1600x1600>" means: fit into a square of 1600, and only shrink. That
  # limits the long edge, whether landscape or portrait.
  resize() { magick "$1" -auto-orient -resize "${MAX_EDGE}x${MAX_EDGE}>" -strip -quality "$QUALITY" "$1"; }
  TOOL="ImageMagick (magick)"
elif command -v convert >/dev/null 2>&1; then
  resize() { convert "$1" -auto-orient -resize "${MAX_EDGE}x${MAX_EDGE}>" -strip -quality "$QUALITY" "$1"; }
  TOOL="ImageMagick (convert)"
elif python3 -c "import PIL" >/dev/null 2>&1; then
  resize() {
    python3 - "$1" "$MAX_EDGE" "$QUALITY" <<'PY'
import sys
from PIL import Image, ImageOps
path, max_edge, quality = sys.argv[1], int(sys.argv[2]), int(sys.argv[3])
img = ImageOps.exif_transpose(Image.open(path)).convert("RGB")
# contain() fits into the square and never enlarges - the same rule as
# "1600x1600>" in ImageMagick, so correct for portrait photos too.
if max(img.size) > max_edge:
    img = ImageOps.contain(img, (max_edge, max_edge), Image.LANCZOS)
img.save(path, "JPEG", quality=quality, optimize=True, progressive=True)
PY
  }
  TOOL="Pillow"
else
  echo "No image tool found."
  echo "  macOS:  brew install imagemagick"
  echo "  Debian: sudo apt install imagemagick"
  echo "  or:     pip install Pillow"
  exit 1
fi

echo "Tool:      $TOOL"
echo "Long edge: at most ${MAX_EDGE}px, quality ${QUALITY}"
[[ $APPLY -eq 0 ]] && echo "Mode:      dry run (nothing is written) — run with --apply"
echo

before=$(du -sk public/images | cut -f1)

while IFS= read -r -d '' file; do
  kb_before=$(( $(stat -f%z "$file" 2>/dev/null || stat -c%s "$file") / 1024 ))

  if [[ $APPLY -eq 1 ]]; then
    resize "$file"
    kb_after=$(( $(stat -f%z "$file" 2>/dev/null || stat -c%s "$file") / 1024 ))
    printf '%6d KB → %5d KB  %s\n' "$kb_before" "$kb_after" "$file"
  else
    printf '%6d KB  %s\n' "$kb_before" "$file"
  fi
done < <(find public/images -name '*.jpg' -print0 | sort -z)

echo
if [[ $APPLY -eq 1 ]]; then
  after=$(du -sk public/images | cut -f1)
  echo "public/images/: ${before} KB → ${after} KB"
  echo
  echo "Check the result in a browser, the lightbox especially. Then commit —"
  echo "or roll back with 'git checkout -- public/images/'."
else
  echo "public/images/ currently: ${before} KB"
  echo "To apply:  bash tools/optimize-images.sh --apply"
fi
