# ============================================================
# Sturmfrei Impro — the project's tasks
#
# There is no build. public/ is the site and lives in the repo; every page
# in it is assembled on request from content/*.json and sections/*.php — by
# PHP, on the server. A fresh clone is therefore complete straight away:
# `make serve` is all it takes.
#
# What gets served is public/ — nothing else. On netcup the checkout is at
# the same time the web directory; .htaccess rewrites every request into it,
# and content/, lib/ and sections/ sit above it and are therefore not
# addressable (see README, "Deploy").
#
# Local things (devcontainer and the like) belong in Makefile.local — pulled
# in at the end and not part of the repo.
# ============================================================

.DEFAULT_GOAL := help
.PHONY: help check serve images images-apply icons mark fonts

help:
	@echo ""
	@echo "  make check          check the pages (syntax, paths, jump targets, alt texts)"
	@echo "  make serve          serve the site locally on http://localhost:8000"
	@echo ""
	@echo "  make images         show what the photos weigh"
	@echo "  make images-apply   shrink them to a 1600 px long edge (overwrites!)"
	@echo "  make icons          favicon, home-screen icon and header mark from the lighthouse"
	@echo "  make mark           cut the lighthouse out of the logo (only needed when the logo changes)"
	@echo "  make fonts          fetch Anton and Inter into public/fonts/ again"
	@echo ""
	@echo "  make dev-help       devcontainer targets (from Makefile.local)"
	@echo ""

# Two steps, and the order is not arbitrary: a syntax error in a PHP file is
# not half a page but an empty one. Hence `php -l` over everything first,
# then the content check.
check:
	@fail=0; for file in $$(find lib sections public tools -name '*.php'); do \
	  php -l "$$file" >/dev/null 2>&1 || { php -l "$$file" | sed 's/^/  /'; fail=1; }; \
	done; \
	[ $$fail -eq 0 ] || { echo; echo "  The syntax error first, then everything else."; exit 1; }
	@php tools/check.php

# Do not open this by double-clicking: over file:// nobody runs the PHP, and
# Chrome blocks the fonts and the ES modules there as well. The root is
# public/, so the paths are the same as live; tools/router.php takes over the
# two addresses that .htaccess rewrites on the server.
serve:
	@echo "→ http://localhost:8000"
	@php -S localhost:8000 -t public tools/router.php

images:
	@bash tools/optimize-images.sh

images-apply:
	@bash tools/optimize-images.sh --apply

# favicon, home-screen icon and the 52 px mark in the header bar, all scaled
# down from images/logo/lighthouse.png (see mark, below). Without them every
# page serves the 258 KB logo in all three places; `make check` points it out
# while they are missing.
icons:
	@bash tools/make-icons.sh

# The lighthouse on its own, keyed off the sky ground — the part of the logo
# that still reads at 32 px and does not repeat the wordmark standing next to
# it in the header bar. Writes images/logo/lighthouse.png, the master the
# three icons below are scaled from. Needs Pillow, `icons` does not, and the
# master is committed — so this only has to run when the logo itself changes.
mark:
	@python3 tools/make-mark.py

fonts:
	@bash tools/fetch-fonts.sh

-include Makefile.local
