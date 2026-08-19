#!/bin/sh
# ============================================================
# Legt Node in den Webspace — einmalig, danach baut tools/deploy.sh dort.
#
# Hintergrund: public/ liegt nicht im Repo, die Seite entsteht auf dem Server
# (siehe README, "Deploy"). Ein Webspace bringt aber meist nur PHP mit. Dieses
# Skript holt deshalb die offizielle linux-Binärdatei von nodejs.org und legt
# genau eine Datei ab: ~/bin/node. npm und die Bibliotheken bleiben weg — die
# Werkzeuge in tools/ brauchen nur node selbst und keine Abhängigkeiten.
#
#   sh tools/install-node.sh              neueste v22-LTS nach ~/bin/node
#   sh tools/install-node.sh 24           anderer Hauptzweig
#   sh tools/install-node.sh v22.11.0     genau diese Fassung
#   ZIEL=$HOME/opt/bin sh tools/install-node.sh
#   SPIEGEL=https://npmmirror.com/mirrors/node sh tools/install-node.sh
#
# Zwei Wege, es auszuführen:
#
#   per SSH        einmal aufrufen, fertig.
#   ohne SSH       im WCP unter "Zusätzliche Deployment-Aktionen" vorüber-
#                  gehend 'sh tools/install-node.sh' eintragen, einmal
#                  deployen, danach wieder 'sh tools/deploy.sh' eintragen.
#
# tools/deploy.sh sucht node anschließend selbst in ~/bin — die Aktion braucht
# also kein 'PATH=' davor.
#
# Reines sh und nur curl/wget, sha256sum und tar: mehr ist auf einem Webspace
# nicht verlässlich vorhanden.
# ============================================================

set -eu

wahl=${1:-22}
zielverzeichnis=${ZIEL:-$HOME/bin}
ziel="$zielverzeichnis/node"

# Temporäres neben dem Home, nicht in /tmp: auf geteiltem Webspace ist /tmp
# gern klein, und das Archiv wiegt ~30 MB.
tmp="$HOME/.node-install.$$"

aufraeumen() {
  rm -rf "$tmp"
}
trap aufraeumen EXIT HUP INT TERM

fehler() {
  echo "FEHLER: $*" >&2
  exit 1
}

# ------------------------------------------------------------
# 1. Passt die Maschine überhaupt?
# ------------------------------------------------------------
[ "$(uname -s)" = "Linux" ] || fehler "das hier ist für Linux gedacht (uname -s: $(uname -s))."

case "$(uname -m)" in
  x86_64 | amd64) arch=x64 ;;
  aarch64 | arm64) arch=arm64 ;;
  armv7l) arch=armv7l ;;
  *) fehler "unbekannte Architektur $(uname -m) — nodejs.org hat dafür kein Archiv." ;;
esac

if command -v curl >/dev/null 2>&1; then
  hole() { curl -fsSL "$1" -o "$2"; }
elif command -v wget >/dev/null 2>&1; then
  hole() { wget -q "$1" -O "$2"; }
else
  fehler "weder curl noch wget vorhanden — ohne beides kommt hier nichts herein."
fi

# tar ruft für .xz das Programm xz auf; fehlt es, nehmen wir .tar.gz. Das
# Archiv ist dann größer, entpackt wird aber dasselbe.
if command -v xz >/dev/null 2>&1; then
  endung=xz
  tarflag=J
else
  endung=gz
  tarflag=z
fi

# ------------------------------------------------------------
# 2. Welche Fassung, und wie heißt ihr Archiv?
#
# Die Patch-Nummer steht nicht in diesem Skript: sie kommt aus der
# SHASUMS256.txt der gewählten Reihe. Die Datei brauchen wir ohnehin zum
# Prüfen — also lesen wir den Dateinamen gleich dort heraus.
# ------------------------------------------------------------
spiegel=${SPIEGEL:-https://nodejs.org/dist}

case "$wahl" in
  v*) basis="$spiegel/$wahl" ;;
  *) basis="$spiegel/latest-v${wahl}.x" ;;
esac

mkdir -p "$tmp"

echo "→ frage $basis"
hole "$basis/SHASUMS256.txt" "$tmp/SHASUMS256.txt" \
  || fehler "SHASUMS256.txt nicht erreichbar — gibt es '$wahl' auf nodejs.org?"

archiv=$(awk -v m="-linux-$arch.tar.$endung" 'index($2, m) { print $2; exit }' "$tmp/SHASUMS256.txt")
[ -n "$archiv" ] || fehler "kein Archiv für linux-$arch (.tar.$endung) in dieser Reihe."

echo "→ hole $archiv (~30–45 MB)"
hole "$basis/$archiv" "$tmp/$archiv" || fehler "Download von $archiv gescheitert."

# ------------------------------------------------------------
# 3. Prüfsumme
#
# Ein Binary aus dem Netz, das gleich unseren Build ausführt: die Prüfung ist
# hier keine Zierde. Fehlt sha256sum/shasum, wird abgebrochen statt geraten —
# PRUEFUNG=aus hebt das auf, wer es wirklich will.
# ------------------------------------------------------------
if command -v sha256sum >/dev/null 2>&1; then
  pruefe() { (cd "$tmp" && sha256sum -c erwartet.txt >/dev/null); }
elif command -v shasum >/dev/null 2>&1; then
  pruefe() { (cd "$tmp" && shasum -a 256 -c erwartet.txt >/dev/null); }
else
  pruefe() { return 2; }
fi

grep "  $archiv\$" "$tmp/SHASUMS256.txt" > "$tmp/erwartet.txt" \
  || fehler "keine Prüfsumme für $archiv gefunden."

stand=0
pruefe || stand=$?

if [ "$stand" -eq 0 ]; then
  echo "→ Prüfsumme stimmt"
elif [ "${PRUEFUNG:-}" = "aus" ]; then
  if [ "$stand" -eq 2 ]; then
    echo "→ WARNUNG: weder sha256sum noch shasum vorhanden, ungeprüft (PRUEFUNG=aus)"
  else
    echo "→ WARNUNG: Prüfsumme stimmt NICHT, weitergemacht (PRUEFUNG=aus)"
  fi
elif [ "$stand" -eq 2 ]; then
  fehler "weder sha256sum noch shasum vorhanden — ungeprüft wird hier nichts installiert. Notfalls: PRUEFUNG=aus sh tools/install-node.sh"
else
  fehler "die Prüfsumme von $archiv stimmt nicht. Nichts installiert."
fi

# ------------------------------------------------------------
# 4. Nur die eine Datei herausholen
#
# tar -O schreibt das Mitglied nach stdout, damit nie das ganze Archiv
# (~200 MB entpackt) auf der Platte liegt. GNU tar braucht für das Muster
# --wildcards, bsdtar kennt die Option nicht — deshalb zwei Versuche.
# ------------------------------------------------------------
echo "→ entpacke bin/node"
if ! tar -x${tarflag}f "$tmp/$archiv" -O --wildcards "*/bin/node" > "$tmp/node" 2>/dev/null; then
  tar -x${tarflag}f "$tmp/$archiv" -O "*/bin/node" > "$tmp/node" \
    || fehler "konnte bin/node nicht aus dem Archiv holen."
fi

# Ein leeres oder winziges Ergebnis heißt: das Muster hat nichts getroffen.
groesse=$(wc -c < "$tmp/node" | tr -d ' ')
[ "$groesse" -gt 10000000 ] || fehler "die entpackte Datei ist nur $groesse Bytes groß — das ist nicht node."

chmod 755 "$tmp/node"

"$tmp/node" --version >/dev/null 2>&1 \
  || fehler "die Datei läuft auf diesem Webspace nicht (meist zu alte glibc). Mit einem älteren Hauptzweig versuchen: sh tools/install-node.sh 20"

# ------------------------------------------------------------
# 5. Ablegen — erst daneben, dann darüber
#
# Ein laufender Deploy soll nicht auf eine halb geschriebene Datei treffen.
# ------------------------------------------------------------
mkdir -p "$zielverzeichnis"
mv "$tmp/node" "$ziel.neu"
mv "$ziel.neu" "$ziel"

version=$("$ziel" --version)

echo
echo "Node $version liegt in $ziel ($(( groesse / 1048576 )) MB)."
echo

if [ "$zielverzeichnis" = "$HOME/bin" ]; then
  echo "  tools/deploy.sh findet es dort von selbst. Die Deployment-Aktion im"
  echo "  WCP bleibt also:"
  echo
  echo "    sh tools/deploy.sh"
else
  echo "  Dieses Verzeichnis sucht tools/deploy.sh nicht ab. Die Deployment-"
  echo "  Aktion im WCP lautet damit:"
  echo
  echo "    PATH=$zielverzeichnis:\$PATH sh tools/deploy.sh"
fi

echo
echo "  Für die eigene SSH-Sitzung: PATH=\"$zielverzeichnis:\$PATH\""
echo
