#!/usr/bin/env bash
# ============================================================
# Holt Anton und Inter als woff2 nach fonts/ und macht die Seite damit
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
# Danach die im Skript ausgegebenen zwei Änderungen an index.html und
# style.css übernehmen.
# ============================================================

set -euo pipefail

cd "$(dirname "$0")/.."
mkdir -p fonts

# Latin-Subsets aus dem google-webfonts-helper (liefert direkt woff2).
BASE="https://gwfh.mranftl.com/api/fonts"

fetch() { # $1 = family-slug, $2 = variant list
  local slug="$1" variants="$2"
  echo "→ $slug ($variants)"
  curl -fsSL "${BASE}/${slug}?download=zip&subsets=latin,latin-ext&formats=woff2&variants=${variants}" \
    -o "fonts/${slug}.zip"
  unzip -qo "fonts/${slug}.zip" -d fonts/
  rm "fonts/${slug}.zip"
}

fetch anton regular
fetch inter regular,600,700

echo
echo "Geladen:"
ls -1sh fonts/*.woff2

cat <<'NEXT'

Noch zwei Handgriffe:

1. index.html — diese vier Zeilen im <head> löschen:

     <link rel="preconnect" href="https://fonts.googleapis.com"/>
     <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
     <link rel="stylesheet"
           href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:...

   und stattdessen den Display-Schnitt vorladen (er steckt in der H1):

     <link rel="preload" as="font" type="font/woff2"
           href="fonts/anton-v25-latin-regular.woff2" crossorigin/>

   (Dateinamen an das anpassen, was oben wirklich in fonts/ liegt.)

2. style.css — @font-face-Block ganz oben einfügen, vor Abschnitt 1:

     @font-face {
       font-family: "Anton";
       src: url("fonts/anton-v25-latin-regular.woff2") format("woff2");
       font-weight: 400;
       font-style: normal;
       font-display: swap;
     }

     /* Inter je Schnitt: 400, 600, 700 */
     @font-face {
       font-family: "Inter";
       src: url("fonts/inter-v20-latin-regular.woff2") format("woff2");
       font-weight: 400;
       font-style: normal;
       font-display: swap;
     }
     /* ... 600 und 700 analog ... */

   --font-display und --font-body in :root bleiben unverändert.

Wichtig: erst nach Schritt 1+2 ist der Google-Abruf wirklich weg. Mit den
DevTools (Netzwerk-Tab, Filter "fonts.g") gegenprüfen, dass nichts mehr
an Google geht.
NEXT
