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

# ------------------------------------------------------------
# Where a target runs
#
# Nearly everything below needs PHP, or Pillow, or both — and the machine
# this is usually driven from is a Mac, which ships neither. `make check`
# there printed "php: command not found" once per file in lib/, sections/,
# public/ and tools/, and not a word about the pages.
#
# So when there is no PHP on this machine, the targets in CONTAINED repeat
# themselves inside the devcontainer: `make check` on the Mac runs `make
# check` in there, and the output comes back as if it had run here. Where
# PHP is present — inside the container, or on anyone's own Linux box — the
# recipes are simply the recipes and no container is involved.
#
# PHP is the one question asked, not one question per tool: it is what the
# project runs on, and a machine that has it is a machine set up for this.
#
# Two targets stay out of it. `fonts` fetches from the web and the container
# has no outbound network — there it would fail where here it works. `help`
# needs nothing.
#
# Which container, which CLI, which published port: all of that is
# Makefile.local's business (git-ignored, pulled in at the end). This file
# only asks whether PHP is here.
# ------------------------------------------------------------

CONTAINED := check serve images images-apply icons mark

ifeq ($(shell command -v php 2>/dev/null),)
ELSEWHERE := yes
endif

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
ifdef ELSEWHERE
	@echo "  No PHP on this machine: everything above but fonts runs in the"
	@echo "  devcontainer by itself. Nothing to remember, it just takes longer."
	@echo ""
endif
	@echo "  make dev-help       devcontainer targets (from Makefile.local)"
	@echo ""

ifdef ELSEWHERE

# The base layer in Makefile.local sets WORKSPACE_FOLDER to an absolute
# path; without it, the directory make was started in. Recursive (=), so it
# is read when a recipe runs — by then the end of this file has been parsed.
WS = $(if $(WORKSPACE_FOLDER),$(WORKSPACE_FOLDER),.)

# Deliberately no "Container not running?" hint over the exit code: `check`
# exits non-zero whenever it finds something, and a hint printed on every
# non-zero exit would blame the container for the site's own errors. The CLI
# says for itself when it cannot reach the container.
$(CONTAINED):
	@command -v devcontainer >/dev/null 2>&1 || { \
	  echo "  This needs PHP. There is none on this machine, and the"; \
	  echo "  devcontainer CLI that would run it elsewhere is missing too."; \
	  echo "  → make dev-check-cli"; exit 1; }
	@devcontainer exec --workspace-folder $(WS) make $@

else

# Two steps, and the order is not arbitrary: a syntax error in a PHP file is
# not half a page but an empty one. Hence `php -l` over everything first,
# then the content check.
check:
	@fail=0; for file in $$(find lib sections public tools -name '*.php'); do \
	  php -l "$$file" >/dev/null 2>&1 || { php -l "$$file" | sed 's/^/  /'; fail=1; }; \
	done; \
	[ $$fail -eq 0 ] || { echo; echo "  The syntax error first, then everything else."; exit 1; }
	@php tools/check.php

# In a container the server has to bind 0.0.0.0 and not localhost: the port
# is published to the Mac's own 127.0.0.1, and the container's loopback is
# not reachable from there. On your own machine localhost is the right
# answer — nothing here needs to listen on every interface.
BIND := $(if $(wildcard /.dockerenv),0.0.0.0,localhost)

# Do not open this by double-clicking: over file:// nobody runs the PHP, and
# Chrome blocks the fonts and the ES modules there as well. The root is
# public/, so the paths are the same as live; tools/router.php takes over the
# two addresses that .htaccess rewrites on the server.
serve:
	@echo "→ http://localhost:8000"
	@php -S $(BIND):8000 -t public tools/router.php

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

endif

# Out here in both cases: this one fetches from the web, and the container
# has no way out. See "Where a target runs" above.
fonts:
	@bash tools/fetch-fonts.sh

-include Makefile.local
