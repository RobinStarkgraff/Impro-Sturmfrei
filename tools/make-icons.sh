#!/usr/bin/env bash
# ============================================================
# Puts the small icons next to the logo.
#
# Why at all: images/logo/logo.jpg is 2240 x 1260 px and weighs 258 KB. That
# one file was the favicon, the home-screen icon AND the round 52 px mark in
# the header bar — on every page, the same reference three times. A quarter
# of a megabyte to draw a 52 px circle. For the home screen it was also the
# wrong shape: landscape instead of square.
#
# Three files are produced, all cropped square from the centre:
#
#   images/logo/favicon.png           32 px   browser tab
#   images/logo/apple-touch-icon.png 180 px   home screen on iOS
#   images/logo/logo-mark.jpg        128 px   the mark in the header bar
#
#   bash tools/make-icons.sh
#
# While they are missing it stays with the logo: asset_or() in lib/paths.php
# only takes the small version if it exists, and `make check` points it out.
# So nothing breaks if this script never runs — it just means a quarter of a
# megabyte too much loaded every time.
#
# After the run: commit the three files, they belong in the repo like
# everything under public/.
# ============================================================

set -euo pipefail

cd "$(dirname "$0")/.."

SOURCE="public/images/logo/logo.jpg"
DIR="public/images/logo"

[[ -f "$SOURCE" ]] || { echo "There is no $SOURCE."; exit 1; }

# --- Find a tool ---------------------------------------------------------
#
# sips comes first because it is already on every Mac and this project is
# driven from a Mac — no brew install for three files.
#
# sips cannot crop from the centre and scale in one step, hence two: crop
# square first, then bring it to size.

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
square "$DIR/logo-mark.jpg" 128

echo "Done:"
ls -1sh "$DIR/favicon.png" "$DIR/apple-touch-icon.png" "$DIR/logo-mark.jpg"
echo
echo "sections/head.php and sections/header.php pick them up by themselves now."
echo "Take a look, then commit — and after that: make check"
