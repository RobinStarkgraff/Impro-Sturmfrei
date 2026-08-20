#!/bin/sh
# ============================================================
# The command netcup runs after every deploy.
#
#   WCP → Git deployment → "Zusätzliche Deployment-Aktionen":
#
#     sh tools/deploy.sh
#
# Plain sh and nothing else — a webspace rarely has more.
#
# Nothing is built here. What is in the repo is the site: the checkout is
# the deploy, and the moment netcup has pulled, the new state is already
# online. So this script can no longer stop it — it looks whether things
# are in order and writes the result to the log.
#
# Which dictates the order of work: `make check` belongs before the push,
# not after it. Here it is the second report, not the first.
#
# Two setups, one script:
#
#   sh tools/deploy.sh            The docroot IS the checkout. The .htaccess
#                                 in the project root serves public/.
#   sh tools/deploy.sh <target>   The docroot is elsewhere: the project
#                                 folder is additionally mirrored there —
#                                 including lib/ and content/, because the
#                                 site needs them next to public/.
#
# Running it by hand does the same and is meant for trying things out.
# ============================================================

set -eu

# The project root sits above this script — no matter which directory
# netcup calls us from.
root=$(cd "$(dirname "$0")/.." && pwd)
cd "$root"

target=${1:-${SITE_TARGET:-}}
log="$root/deploy.log"

say() {
  echo "  $*"
}

done_with() {
  # $1 = result for the log, $2 = exit code
  commit="unknown"
  if command -v git >/dev/null 2>&1 && [ -d .git ]; then
    commit=$(git rev-parse --short HEAD 2>/dev/null || echo "unknown")
  fi

  echo "$(date '+%Y-%m-%d %H:%M:%S')  commit=$commit  target=${target:-checkout}  $1" >> "$log"
  echo "Done: $1 (log: deploy.log)"
  exit "$2"
}

echo "Deploy: $root"

# ------------------------------------------------------------
# 1. Find PHP
#
# The PATH of a deployment action is sparse. Without php on the command line
# nothing can be checked here — the site itself still runs, because it only
# needs the web server's PHP.
# ------------------------------------------------------------
php=""
for candidate in php php8.3 php8.2 php8.1 php8.0; do
  if command -v "$candidate" >/dev/null 2>&1; then
    php=$candidate
    break
  fi
done

if [ -z "$php" ]; then
  echo "NOTE: no php on the command line — nothing will be checked."
  echo "      The site is unaffected: it only needs the web server's PHP,"
  echo "      not the shell's."
  done_with "unchecked" 0
fi

say "$($php -v | head -n 1)"

# ------------------------------------------------------------
# 2. Syntax
#
# A typo in a PHP file is not half a build here but an empty page. Which is
# why this check comes before everything else.
# ------------------------------------------------------------
broken=0

for file in $(find lib sections public tools -name '*.php' 2>/dev/null); do
  if ! $php -l "$file" >/dev/null 2>&1; then
    echo "ERROR: $file has a syntax error:"
    $php -l "$file" 2>&1 | sed 's/^/       /'
    broken=1
  fi
done

if [ "$broken" -ne 0 ]; then
  echo
  echo "       The site is broken right now. Way back: revert the commit"
  echo "       and push again."
  done_with "syntax-error" 1
fi

say "syntax is fine"

# ------------------------------------------------------------
# 3. Check
#
# check.php renders every page, resolves every reference and checks the
# mandatory details from content/legal.json. Exit 1 means a real error;
# notes alone are exit 0.
# ------------------------------------------------------------
say "checking the pages"
if ! $php tools/check.php; then
  echo
  echo "ERROR: 'check.php' reported problems (see above)."
  echo "       They are already online — this state needs fixing."
  done_with "check-error" 1
fi

# ------------------------------------------------------------
# 4. Mirror, if the docroot is elsewhere
# ------------------------------------------------------------
if [ -n "$target" ]; then
  if [ ! -d "$target" ]; then
    echo "ERROR: target '$target' is not a directory."
    done_with "target-missing" 1
  fi

  # What gets mirrored is public/ — but the site needs lib/, sections/ and
  # content/ next to it, and those live one level up. So everything travels
  # along, and the target ends up with the same .htaccess as here.
  if command -v rsync >/dev/null 2>&1; then
    say "mirroring the project folder → $target (rsync)"
    rsync -a --delete --exclude '.git' --exclude 'deploy.log' ./ "$target"/
  else
    # cp cannot delete: whatever disappears here stays behind in the target.
    # Better to know that than to discover it.
    say "no rsync — copying with cp; orphaned files in the target stay behind"
    cp -R ./. "$target"/
  fi
else
  say "no target given — docroot is the checkout, .htaccess serves public/"
fi

done_with "ok" 0
