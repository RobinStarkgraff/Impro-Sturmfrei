#!/bin/sh
# ============================================================
# Der Befehl, den netcup nach jedem Deploy aufruft.
#
#   WCP → Git-Deployment → "Zusätzliche Deployment-Aktionen":
#
#     sh tools/deploy.sh
#
# Reines sh und sonst nichts — auf einem Webspace ist selten mehr da.
#
# Gebaut wird hier nichts. Was im Repo steht, ist die Seite: der
# Checkout ist der Deploy, und in dem Moment, in dem netcup gezogen hat,
# ist der neue Stand schon online. Dieses Skript kann ihn deshalb nicht
# mehr aufhalten — es sieht nach, ob er in Ordnung ist, und schreibt das
# Ergebnis ins Protokoll.
#
# Daraus folgt die Reihenfolge der Arbeit: `make check` gehört vor den
# Push, nicht danach. Hier ist es die zweite Meldung, nicht die erste.
#
# Zwei Aufbauten, ein Skript:
#
#   sh tools/deploy.sh            Docroot IST der Checkout. Die .htaccess im
#                                 Projektstamm liefert public/ aus.
#   sh tools/deploy.sh <ziel>     Docroot liegt woanders: der Projektordner
#                                 wird zusätzlich dorthin gespiegelt — mit
#                                 lib/ und content/, denn die Seite braucht
#                                 sie neben public/.
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
# 1. PHP suchen
#
# Der PATH einer Deployment-Aktion ist karg. Ohne php auf der Kommandozeile
# lässt sich hier nichts prüfen — die Seite selbst läuft dann trotzdem, denn
# sie braucht nur das PHP des Webservers.
# ------------------------------------------------------------
php=""
for kandidat in php php8.3 php8.2 php8.1 php8.0; do
  if command -v "$kandidat" >/dev/null 2>&1; then
    php=$kandidat
    break
  fi
done

if [ -z "$php" ]; then
  echo "HINWEIS: kein php auf der Kommandozeile — es wird nichts geprüft."
  echo "         Die Seite läuft davon unberührt: sie braucht nur das PHP"
  echo "         des Webservers, nicht das der Shell."
  fertig "ungeprueft" 0
fi

say "$($php -v | head -n 1)"

# ------------------------------------------------------------
# 2. Syntax
#
# Ein Tippfehler in einer PHP-Datei ist hier kein halber Build, sondern
# eine leere Seite. Deshalb steht diese Prüfung vor allem anderen.
# ------------------------------------------------------------
kaputt=0

for datei in $(find lib sections public tools -name '*.php' 2>/dev/null); do
  if ! $php -l "$datei" >/dev/null 2>&1; then
    echo "FEHLER: $datei hat einen Syntaxfehler:"
    $php -l "$datei" 2>&1 | sed 's/^/        /'
    kaputt=1
  fi
done

if [ "$kaputt" -ne 0 ]; then
  echo
  echo "        Die Seite ist damit gerade kaputt. Zurück: den Commit"
  echo "        rückgängig machen und neu pushen."
  fertig "syntax-fehler" 1
fi

say "Syntax in Ordnung"

# ------------------------------------------------------------
# 3. Prüfen
#
# check.php rendert jede Seite, löst jeden Verweis auf und prüft die
# Pflichtangaben aus content/legal.json. Exit 1 heißt echter Fehler;
# Hinweise allein sind Exit 0.
# ------------------------------------------------------------
say "prüfe die Seiten"
if ! $php tools/check.php; then
  echo
  echo "FEHLER: 'check.php' hat Probleme gemeldet (siehe oben)."
  echo "        Sie sind schon online — dieser Stand gehört korrigiert."
  fertig "check-fehler" 1
fi

# ------------------------------------------------------------
# 4. Spiegeln, wenn der Docroot woanders liegt
# ------------------------------------------------------------
if [ -n "$target" ]; then
  if [ ! -d "$target" ]; then
    echo "FEHLER: Ziel '$target' ist kein Verzeichnis."
    fertig "ziel-fehlt" 1
  fi

  # Gespiegelt wird public/ — aber die Seite braucht lib/, sections/ und
  # content/ daneben, und die liegen eine Ebene höher. Deshalb wandert
  # alles mit, und im Ziel liegt dieselbe .htaccess wie hier.
  if command -v rsync >/dev/null 2>&1; then
    say "spiegle den Projektordner → $target (rsync)"
    rsync -a --delete --exclude '.git' --exclude 'deploy.log' ./ "$target"/
  else
    # cp kann nicht löschen: was hier verschwindet, bleibt im Ziel liegen.
    # Das soll man wissen, statt es zu entdecken.
    say "kein rsync — kopiere mit cp; verwaiste Dateien im Ziel bleiben liegen"
    cp -R ./. "$target"/
  fi
else
  say "kein Ziel angegeben — Docroot liegt auf dem Checkout, .htaccess liefert public/ aus"
fi

fertig "ok" 0
