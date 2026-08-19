#!/usr/bin/env bash
# ============================================================
# Verkleinert die Show- und Gruppenfotos auf Webmaß.
#
# Die Slider zeigen die Bilder mit rund 300 px Breite an, auf Retina also
# 600 px. Die Lightbox liefert dieselbe Datei aus und ist damit der einzige
# Ort, an dem mehr Auflösung überhaupt sichtbar wird — 1600 px reichen dafür
# mit Reserve. Aktuell liegen hier ~14 MB, danach sollten es unter 2 MB sein.
#
#   bash tools/optimize-images.sh            # nur berichten, nichts ändern
#   bash tools/optimize-images.sh --apply    # tatsächlich überschreiben
#
# --apply überschreibt die Originale. Vorher committen, dann ist ein
# `git checkout -- images/` immer noch der Rückweg.
# ============================================================

set -euo pipefail

MAX_WIDTH=1600
QUALITY=82

APPLY=0
[[ "${1:-}" == "--apply" ]] && APPLY=1

cd "$(dirname "$0")/.."

# --- Werkzeug suchen: ImageMagick, sonst Pillow ---------------------------
if command -v magick >/dev/null 2>&1; then
  resize() { magick "$1" -auto-orient -resize "${MAX_WIDTH}>" -strip -quality "$QUALITY" "$1"; }
  TOOL="ImageMagick (magick)"
elif command -v convert >/dev/null 2>&1; then
  resize() { convert "$1" -auto-orient -resize "${MAX_WIDTH}>" -strip -quality "$QUALITY" "$1"; }
  TOOL="ImageMagick (convert)"
elif python3 -c "import PIL" >/dev/null 2>&1; then
  resize() {
    python3 - "$1" "$MAX_WIDTH" "$QUALITY" <<'PY'
import sys
from PIL import Image, ImageOps
path, max_w, quality = sys.argv[1], int(sys.argv[2]), int(sys.argv[3])
img = ImageOps.exif_transpose(Image.open(path)).convert("RGB")
if img.width > max_w:
    img = img.resize((max_w, round(img.height * max_w / img.width)), Image.LANCZOS)
img.save(path, "JPEG", quality=quality, optimize=True, progressive=True)
PY
  }
  TOOL="Pillow"
else
  echo "Kein Bildwerkzeug gefunden."
  echo "  macOS:  brew install imagemagick"
  echo "  Debian: sudo apt install imagemagick"
  echo "  oder:   pip install Pillow"
  exit 1
fi

echo "Werkzeug:  $TOOL"
echo "Zielbreite: ${MAX_WIDTH}px, Qualität ${QUALITY}"
[[ $APPLY -eq 0 ]] && echo "Modus:     Testlauf (nichts wird geschrieben) — mit --apply ausführen"
echo

before=$(du -sk images | cut -f1)

while IFS= read -r -d '' file; do
  kb_before=$(( $(stat -f%z "$file" 2>/dev/null || stat -c%s "$file") / 1024 ))

  if [[ $APPLY -eq 1 ]]; then
    resize "$file"
    kb_after=$(( $(stat -f%z "$file" 2>/dev/null || stat -c%s "$file") / 1024 ))
    printf '%6d KB → %5d KB  %s\n' "$kb_before" "$kb_after" "$file"
  else
    printf '%6d KB  %s\n' "$kb_before" "$file"
  fi
done < <(find images -name '*.jpg' -print0 | sort -z)

echo
if [[ $APPLY -eq 1 ]]; then
  after=$(du -sk images | cut -f1)
  echo "images/: ${before} KB → ${after} KB"
  echo
  echo "Ergebnis im Browser prüfen, besonders die Lightbox. Danach committen —"
  echo "oder mit 'git checkout -- images/' zurückrollen."
else
  echo "images/ aktuell: ${before} KB"
  echo "Zum Anwenden:  bash tools/optimize-images.sh --apply"
fi
