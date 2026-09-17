#!/usr/bin/env bash
# ============================================================
# Scales the lighthouse mark down into the three small icons.
#
# The source is images/logo/lighthouse.png, the 512 px master that `make
# mark` cuts out of the logo. It used to be images/logo/logo.jpg itself —
# the whole lockup, cropped square from the centre — and that was wrong
# three times over: 2240 x 1260 px and 258 KB loaded to draw a 52 px circle,
# landscape where a home-screen icon has to be square, and at favicon size
# an illegible smudge, because the centre of the lockup is the middle of the
# STURMFREI wordmark and its letters end up about four pixels tall. The
# lighthouse is the one element that still reads at 32 px, and it does not
# repeat the wordmark that stands right next to it in the header bar.
#
# Three files are produced, all plain scales of the square master:
#
#   images/logo/favicon.png           32 px   browser tab
#   images/logo/apple-touch-icon.png 180 px   home screen on iOS
#   images/logo/logo-mark.png        128 px   the mark in the header bar
#
#   bash tools/make-icons.sh
#
# This step alone is enough for the icons: the master is committed, and
# scaling it works with sips, ImageMagick or Pillow, whichever is there.
# Only `make mark`, which cuts the master out of the logo, needs Pillow.
#
# While the three are missing it stays with the logo: asset_or() in
# lib/paths.php only takes the small version if it exists, and `make check`
# points it out. So nothing breaks if this script never runs — it just means
# a quarter of a megabyte too much on every page, and the smudge in the tab.
#
# After the run: commit the three files, they belong in the repo like
# everything under public/.
# ============================================================

set -euo pipefail

cd "$(dirname "$0")/.."

SOURCE="public/images/logo/lighthouse.png"
DIR="public/images/logo"

[[ -f "$SOURCE" ]] || {
  echo "There is no $SOURCE — the mark the icons are scaled from."
  echo "Cut it out of the logo first:  make mark   (needs Pillow)"
  exit 1
}

# --- Find a tool ---------------------------------------------------------
#
# sips comes first because it is already on every Mac and this project is
# driven from a Mac — no brew install for three files.
#
# sips cannot crop from the centre and scale in one step, hence two: crop
# square first, then bring it to size. On the square master the crop is a
# no-op — it stays because it costs nothing and keeps the script honest if
# SOURCE is ever a file that is not square again.

if command -v magick >/dev/null 2>&1 || command -v convert >/dev/null 2>&1; then
  IM=$(command -v magick || command -v convert)
  TOOL="ImageMagick"
  square() { # $1 = target, $2 = edge length
    "$IM" "$SOURCE" -auto-orient -gravity center -resize "$2x$2^" \
      -extent "$2x$2" -strip "$1"
  }
elif command -v sips >/dev/null 2>&1; then
  TOOL="sips (macOS)"
  square() {
    local target="$1" edge="$2"
    local tmp="${target%.*}.tmp.jpg"
    local short

    # The source's short edge — that is what becomes the square.
    short=$(sips -g pixelHeight -g pixelWidth "$SOURCE" \
      | awk '/pixel(Height|Width)/ {print $2}' | sort -n | head -1)

    cp "$SOURCE" "$tmp"
    sips -c "$short" "$short" "$tmp" >/dev/null  # square, from the centre
    sips -z "$edge" "$edge" "$tmp" >/dev/null    # to size

    # Into the target format only at the very end: sips reads the content,
    # not the extension, and would otherwise write a JPEG into a file
    # named .png.
    if [[ "$target" == *.png ]]; then
      sips -s format png "$tmp" --out "$target" >/dev/null
      rm -f "$tmp"
    else
      mv "$tmp" "$target"
    fi
  }
elif python3 -c "import PIL" >/dev/null 2>&1; then
  TOOL="Pillow"
  square() {
    python3 - "$SOURCE" "$1" "$2" <<'PY'
import sys
from PIL import Image, ImageOps
src, dest, edge = sys.argv[1], sys.argv[2], int(sys.argv[3])
img = ImageOps.exif_transpose(Image.open(src)).convert("RGB")
img = ImageOps.fit(img, (edge, edge), Image.LANCZOS, centering=(0.5, 0.5))
if dest.endswith(".png"):
    img.save(dest, "PNG", optimize=True)
else:
    img.save(dest, "JPEG", quality=88, optimize=True, progressive=True)
PY
  }
else
  echo "No image tool found."
  echo "  macOS:  sips is basically always there — otherwise: brew install imagemagick"
  echo "  Debian: sudo apt install imagemagick"
  echo "  or:     pip install Pillow"
  exit 1
fi

echo "Tool:   $TOOL"
echo "Source: $SOURCE"
echo

square "$DIR/favicon.png" 32
square "$DIR/apple-touch-icon.png" 180
square "$DIR/logo-mark.png" 128

echo "Done:"
ls -1sh "$DIR/favicon.png" "$DIR/apple-touch-icon.png" "$DIR/logo-mark.png"
echo
echo "sections/head.php and sections/header.php pick them up by themselves now."
echo "Take a look, then commit — and after that: make check"
