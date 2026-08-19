#!/usr/bin/env bash
# ============================================================
# Holt Anton und Inter als woff2 nach public/fonts/ und macht die Seite damit
# unabhängig von Googles Servern.
#
# Warum: Der <link> auf fonts.googleapis.com überträgt bei jedem Besuch die
# IP-Adresse der Besucher an Google — ohne Einwilligung. Das LG München I hat
# genau dafür Schadensersatz zugesprochen (3 O 17493/20). Selbst gehostet ist
# das Thema erledigt, und die Seite lädt schneller: zwei preconnects und ein
# blockierendes Stylesheet fallen weg.
#
#   bash tools/fetch-fonts.sh
#
# Danach die im Skript ausgegebenen zwei Änderungen an sections/head.php
# und public/css/00-fonts.css übernehmen.
# ============================================================

set -euo pipefail

cd "$(dirname "$0")/.."
mkdir -p public/fonts

# Latin-Subsets aus dem google-webfonts-helper (liefert direkt woff2).
BASE="https://gwfh.mranftl.com/api/fonts"

fetch() { # $1 = family-slug, $2 = variant list
  local slug="$1" variants="$2"
  echo "→ $slug ($variants)"
  curl -fsSL "${BASE}/${slug}?download=zip&subsets=latin,latin-ext&formats=woff2&variants=${variants}" \
    -o "public/fonts/${slug}.zip"
  unzip -qo "public/fonts/${slug}.zip" -d public/fonts/
  rm "public/fonts/${slug}.zip"
}

fetch anton regular
fetch inter regular,600,700

echo
echo "Geladen:"
ls -1sh public/fonts/*.woff2

cat <<'NEXT'

Wenn sich dabei Dateinamen geändert haben, hängen zwei Stellen daran:

1. sections/head.php — der Preload des Display-Schnitts (er steckt in der
   H1 jeder Seite):

     <link rel="preload" as="font" type="font/woff2"
           href="<?= esc(asset('fonts/…woff2')) ?>" crossorigin/>

2. public/css/00-fonts.css — die @font-face-Blöcke. Die url() dort sind
   relativ zu dieser Datei, deshalb mit "../":

     src: url("../fonts/…woff2") format("woff2");

Beide Namen müssen zu dem passen, was oben wirklich in public/fonts/ liegt;
`make check` merkt einen falschen Preload-Pfad, einen falschen im CSS nicht.

Es geht keine Anfrage an Google: die Schriften liegen auf demselben Server
wie die Seite. Mit den DevTools (Netzwerk-Tab, Filter "fonts.g")
gegenprüfen, wenn hier etwas geändert wurde.
NEXT
