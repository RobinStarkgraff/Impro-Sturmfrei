# ============================================================
# Sturmfrei Impro — Aufgaben des Projekts
#
# Es gibt keinen Build. public/ ist die Seite und liegt im Repo; jede Seite
# darin wird bei ihrem Aufruf aus content/*.json und sections/*.php
# zusammengesetzt — von PHP, auf dem Server. Ein frischer Klon ist damit
# sofort vollständig: `make serve` genügt.
#
# Ausgeliefert wird public/ — sonst nichts. Bei netcup ist der Checkout
# gleichzeitig das Webverzeichnis; .htaccess schreibt jede Anfrage dorthin
# um, und content/, lib/ und sections/ liegen darüber und sind deshalb
# nicht adressierbar (siehe README, "Deploy").
#
# Lokales (devcontainer o.ä.) gehört in Makefile.local — wird am Ende
# eingebunden und ist nicht Teil des Repos.
# ============================================================

.DEFAULT_GOAL := help
.PHONY: help check serve images images-apply icons fonts

help:
	@echo ""
	@echo "  make check          Seiten prüfen (Syntax, Pfade, Sprungziele, alt-Texte)"
	@echo "  make serve          Seite lokal auf http://localhost:8000 ausliefern"
	@echo ""
	@echo "  make images         zeigt, was die Fotos wiegen"
	@echo "  make images-apply   verkleinert sie auf 1600 px lange Seite (überschreibt!)"
	@echo "  make icons          Favicon, Homescreen-Symbol und Marke aus dem Logo"
	@echo "  make fonts          Anton und Inter neu nach public/fonts/ holen"
	@echo ""
	@echo "  make dev-help       devcontainer-Ziele (aus Makefile.local)"
	@echo ""

# Zwei Schritte, und die Reihenfolge ist nicht beliebig: ein Syntaxfehler in
# einer PHP-Datei ist keine halbe Seite, sondern eine leere. Deshalb erst
# `php -l` über alles, dann die inhaltliche Prüfung.
check:
	@fail=0; for datei in $$(find lib sections public tools -name '*.php'); do \
	  php -l "$$datei" >/dev/null 2>&1 || { php -l "$$datei" | sed 's/^/  /'; fail=1; }; \
	done; \
	[ $$fail -eq 0 ] || { echo; echo "  Erst den Syntaxfehler, dann alles andere."; exit 1; }
	@php tools/check.php

# Nicht per Doppelklick öffnen: über file:// tut PHP nichts, und Chrome
# blockiert dort die Schriften und die ES-Module. Wurzel ist public/, damit
# die Pfade stimmen wie live; tools/router.php übernimmt die zwei Adressen,
# die auf dem Server die .htaccess umschreibt.
serve:
	@echo "→ http://localhost:8000"
	@php -S localhost:8000 -t public tools/router.php

images:
	@bash tools/optimize-images.sh

images-apply:
	@bash tools/optimize-images.sh --apply

# Aus dem Logo die drei kleinen Fassungen schneiden. Ohne sie liefert jede
# Seite das 258-KB-Logo als Favicon, als Homescreen-Symbol und als 52-px-Marke
# aus; `make check` weist darauf hin, solange sie fehlen.
icons:
	@bash tools/make-icons.sh

fonts:
	@bash tools/fetch-fonts.sh

-include Makefile.local
