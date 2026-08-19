#!/bin/sh
# ============================================================
# Der Befehl, den netcup nach jedem Deploy aufruft.
#
#   WCP → Git-Deployment → "Zusätzliche Deployment-Aktionen":
#
#     sh tools/deploy.sh
#
# Reines sh, kein make, kein npm: auf einem Webspace ist oft nichts davon da.
#
# Im Repo liegen nur die Quellen — public/ steht in .gitignore und entsteht
# hier. Node ist damit Voraussetzung und keine Kür: ohne Node gibt es keine
# Seite. Fehlt es, bricht dieses Skript ab und sagt, was zu tun ist; die
# vorige Fassung der Seite bleibt dabei unangetastet.
#
# Gebaut wird deshalb nie direkt in public/, sondern daneben:
#
#   1. .public-neu/ bauen        (SITE_OUT, public/ läuft weiter)
#   2. prüfen                    (check.mjs; schlägt es an, wird abgebrochen)
#   3. tauschen                  (zwei mv, dazwischen ist die Seite kurz weg)
#
# Ein kaputter Push nimmt die laufende Seite so nicht mit: er scheitert in
# Schritt 2, und ausgeliefert wird weiter, was vorher stand.
#
# Zwei Aufbauten, ein Skript:
#
#   sh tools/deploy.sh            Docroot IST der Checkout. Die .htaccess im
#                                 Projektstamm liefert public/ aus.
#   sh tools/deploy.sh <ziel>     Docroot liegt woanders: das geprüfte
#                                 Ergebnis wird zusätzlich dorthin gespiegelt.
#
# Aufruf von Hand tut dasselbe und ist zum Ausprobieren gedacht.
# ============================================================

set -eu

# Der Projektstamm liegt über diesem Skript — egal, aus welchem Verzeichnis
# netcup uns aufruft.
root=$(cd "$(dirname "$0")/.." && pwd)
cd "$root"

target=${1:-${SITE_TARGET:-}}
log="$root/deploy.log"
neu="$root/.public-neu"
alt="$root/.public-alt"

say() {
  echo "  $*"
}

fertig() {
  # $1 = Ergebnis fürs Protokoll, $2 = Exit-Code
  commit="unbekannt"
  if command -v git >/dev/null 2>&1 && [ -d .git ]; then
    commit=$(git rev-parse --short HEAD 2>/dev/null || echo "unbekannt")
  fi

  echo "$(date '+%Y-%m-%d %H:%M:%S')  commit=$commit  ziel=${target:-checkout}  $1" >> "$log"
  echo "Fertig: $1 (Protokoll: deploy.log)"
  exit "$2"
}

echo "Deploy: $root"

# ------------------------------------------------------------
# 1. Node — ohne das geht hier nichts mehr
# ------------------------------------------------------------
if ! command -v node >/dev/null 2>&1; then
  echo "FEHLER: kein node auf diesem Webspace."
  echo
  echo "  public/ liegt nicht im Repo, die Seite wird hier gebaut — ohne Node"
  echo "  gibt es also nichts auszuliefern. Zwei Wege:"
  echo
  echo "  a) Node in den Webspace legen: das linux-x64-Archiv von nodejs.org"
  echo "     nach ~/bin/ entpacken und diese Aktion auf"
  echo "     'PATH=\$HOME/bin:\$PATH sh tools/deploy.sh' ändern."
  echo "  b) public/ wieder mit ins Repo nehmen (aus .gitignore austragen,"
  echo "     lokal 'make build' vor jedem Commit) — dann prüft dieses Skript"
  echo "     nur noch, statt zu bauen."
  echo
  echo "  Die bisherige Fassung der Seite bleibt unverändert online."
  fertig "kein-node" 1
fi

say "Node $(node --version)"

# ------------------------------------------------------------
# 2. Bauen — daneben, nicht in public/
# ------------------------------------------------------------
rm -rf "$neu"

say "baue nach .public-neu/"
if ! SITE_OUT=.public-neu node tools/build.mjs; then
  rm -rf "$neu"
  echo "FEHLER: der Build ist gescheitert. Die bisherige Seite bleibt online."
  fertig "build-fehler" 1
fi

# ------------------------------------------------------------
# 3. Prüfen
#
# check.mjs baut im Speicher noch einmal und vergleicht mit .public-neu/,
# löst jeden Verweis auf, prüft die Pflichtangaben aus content/legal.json.
# Exit 1 heißt echter Fehler; Hinweise allein sind Exit 0.
# ------------------------------------------------------------
say "prüfe .public-neu/"
if ! SITE_OUT=.public-neu node tools/check.mjs; then
  rm -rf "$neu"
  echo
  echo "FEHLER: 'check.mjs' hat Probleme gemeldet (siehe oben)."
  echo "        Es wird nichts veröffentlicht — die bisherige Seite bleibt online."
  fertig "check-fehler" 1
fi

# ------------------------------------------------------------
# 4. Tauschen
#
# Zwei mv statt Kopieren: das ist eine Umbenennung im gleichen Dateisystem
# und damit so kurz, wie es ohne Symlink geht. Zwischen den beiden mv gibt es
# public/ für einen Moment nicht — geht das zweite schief, wird der alte
# Stand zurückgeholt, statt die Seite ohne Verzeichnis zu lassen.
# ------------------------------------------------------------
rm -rf "$alt"

if [ -d "$root/public" ]; then
  mv "$root/public" "$alt"
fi

if ! mv "$neu" "$root/public"; then
  echo "FEHLER: Umbenennen nach public/ gescheitert — hole den alten Stand zurück."
  [ -d "$alt" ] && mv "$alt" "$root/public"
  fertig "tausch-fehler" 1
fi

rm -rf "$alt"
say "public/ ist der neue Stand"

# ------------------------------------------------------------
# 5. Zusätzlich spiegeln, wenn der Docroot woanders liegt
# ------------------------------------------------------------
if [ -n "$target" ]; then
  if [ ! -d "$target" ]; then
    echo "FEHLER: Ziel '$target' ist kein Verzeichnis."
    fertig "ziel-fehlt" 1
  fi

  if command -v rsync >/dev/null 2>&1; then
    say "spiegle public/ → $target (rsync)"
    rsync -a --delete public/ "$target"/
  else
    # cp kann nicht löschen: was in public/ verschwindet, bleibt im Ziel
    # liegen. Das soll man wissen, statt es zu entdecken.
    say "kein rsync — kopiere mit cp; verwaiste Dateien im Ziel bleiben liegen"
    cp -R public/. "$target"/
  fi
else
  say "kein Ziel angegeben — Docroot liegt auf dem Checkout, .htaccess liefert public/ aus"
fi

fertig "ok" 0
