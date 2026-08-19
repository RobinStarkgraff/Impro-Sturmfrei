#!/usr/bin/env bash
# ============================================================
# Legt die kleinen Symbole neben das Logo.
#
# Warum überhaupt: images/logo/logo.jpg ist 2240 x 1260 px und wiegt 258 KB.
# Diese eine Datei war Favicon, Symbol fürs Homescreen UND die runde Marke
# von 52 px in der Kopfleiste — auf jeder Seite, dreimal derselbe Verweis.
# Ein Viertelmegabyte, um einen Kreis von 52 px zu zeichnen. Fürs Homescreen
# war sie außerdem im falschen Verhältnis: quer statt quadratisch.
#
# Erzeugt werden drei Dateien, alle quadratisch aus der Mitte geschnitten:
#
#   images/logo/favicon.png           32 px   Reiter im Browser
#   images/logo/apple-touch-icon.png 180 px   Homescreen auf iOS
#   images/logo/logo-mark.jpg        128 px   die Marke in der Kopfleiste
#
#   bash tools/make-icons.sh
#
# Solange sie fehlen, bleibt es beim Logo: asset_or() in lib/paths.php nimmt
# die kleine Fassung nur, wenn es sie gibt, und `make check` weist darauf hin.
# Es geht also nichts kaputt, wenn dieses Skript nie läuft — es wird nur
# jedes Mal ein Viertelmegabyte zu viel geladen.
#
# Nach dem Lauf: die drei Dateien mit committen, sie gehören ins Repo wie
# alles unter public/.
# ============================================================

set -euo pipefail

cd "$(dirname "$0")/.."

SOURCE="public/images/logo/logo.jpg"
DIR="public/images/logo"

[[ -f "$SOURCE" ]] || { echo "Es gibt kein $SOURCE."; exit 1; }

# --- Werkzeug suchen ------------------------------------------------------
#
# sips steht zuerst, weil es auf jedem Mac schon da ist und dieses Projekt
# vom Mac aus bedient wird — kein brew install für drei Dateien.
#
# sips kann nicht aus der Mitte schneiden und skalieren in einem Schritt,
# deshalb zwei: erst quadratisch beschneiden, dann auf Maß bringen.

if command -v magick >/dev/null 2>&1 || command -v convert >/dev/null 2>&1; then
  IM=$(command -v magick || command -v convert)
  TOOL="ImageMagick"
  square() { # $1 = ziel, $2 = kantenlaenge
    "$IM" "$SOURCE" -auto-orient -gravity center -resize "$2x$2^" \
      -extent "$2x$2" -strip "$1"
  }
elif command -v sips >/dev/null 2>&1; then
  TOOL="sips (macOS)"
  square() {
    local ziel="$1" kante="$2"
    local tmp="${ziel%.*}.tmp.jpg"
    local kurz

    # Die kurze Kante der Vorlage — daraus wird das Quadrat.
    kurz=$(sips -g pixelHeight -g pixelWidth "$SOURCE" \
      | awk '/pixel(Height|Width)/ {print $2}' | sort -n | head -1)

    cp "$SOURCE" "$tmp"
    sips -c "$kurz" "$kurz" "$tmp" >/dev/null    # quadratisch, aus der Mitte
    sips -z "$kante" "$kante" "$tmp" >/dev/null  # auf Maß

    # Erst am Schluss ins Zielformat: sips liest den Inhalt, nicht die
    # Endung, und schriebe sonst ein JPEG in eine Datei namens .png.
    if [[ "$ziel" == *.png ]]; then
      sips -s format png "$tmp" --out "$ziel" >/dev/null
      rm -f "$tmp"
    else
      mv "$tmp" "$ziel"
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
  echo "Kein Bildwerkzeug gefunden."
  echo "  macOS:  sips ist eigentlich immer da — sonst: brew install imagemagick"
  echo "  Debian: sudo apt install imagemagick"
  echo "  oder:   pip install Pillow"
  exit 1
fi

echo "Werkzeug: $TOOL"
echo "Vorlage:  $SOURCE"
echo

square "$DIR/favicon.png" 32
square "$DIR/apple-touch-icon.png" 180
square "$DIR/logo-mark.jpg" 128

echo "Fertig:"
ls -1sh "$DIR/favicon.png" "$DIR/apple-touch-icon.png" "$DIR/logo-mark.jpg"
echo
echo "sections/head.php und sections/header.php nehmen sie ab jetzt von selbst."
echo "Ansehen, dann committen — und danach: make check"
