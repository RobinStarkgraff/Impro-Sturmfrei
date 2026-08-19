# ============================================================
# Sturmfrei Impro — Aufgaben des Projekts
#
# public/ ist die fertige Seite und liegt nicht im Repo: sie entsteht mit
# `make build` aus content/*.json, css/*.css, js/*.js, images/ und fonts/ —
# lokal wie auf dem Server. Deshalb ist der erste Befehl in einem frischen
# Klon `make build`; ohne ihn gibt es kein public/.
#
# Ausgeliefert wird genau public/ — sonst nichts. Bei netcup baut und
# veröffentlicht tools/deploy.sh als Deployment-Aktion (siehe README, "Deploy").
#
# Lokales (devcontainer o.ä.) gehört in Makefile.local — wird am Ende
# eingebunden und ist nicht Teil des Repos.
# ============================================================

.DEFAULT_GOAL := help
.PHONY: help build check serve images images-apply fonts

help:
	@echo ""
	@echo "  make build          Seiten, style.css, js/ und die Bilder nach public/ bauen"
	@echo "  make check          Seiten prüfen (Pfade, Sprungziele, alt-Texte, Stand)"
	@echo "  make serve          public/ lokal auf http://localhost:8000 ausliefern"
	@echo ""
	@echo "  make images         zeigt, was die Fotos wiegen"
	@echo "  make images-apply   verkleinert sie auf 1600 px (überschreibt!)"
	@echo "  make fonts          Anton und Inter neu nach fonts/ holen"
	@echo ""
	@echo "  make dev-help       devcontainer-Ziele (aus Makefile.local)"
	@echo ""

build:
	@node tools/build.mjs

check:
	@node tools/check.mjs

# Nicht per Doppelklick öffnen: über file:// blockiert Chrome die Schriften
# und die ES-Module. Wurzel ist public/, damit die Pfade stimmen wie live.
serve:
	@echo "→ http://localhost:8000"
	@python3 -m http.server 8000 --directory public

images:
	@bash tools/optimize-images.sh

images-apply:
	@bash tools/optimize-images.sh --apply

fonts:
	@bash tools/fetch-fonts.sh

-include Makefile.local
