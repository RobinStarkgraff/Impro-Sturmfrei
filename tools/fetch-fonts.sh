#!/usr/bin/env bash
# ============================================================
# Fetches Anton and Inter as woff2 into public/fonts/, making the site
# independent of Google's servers.
#
# Why: the <link> to fonts.googleapis.com hands every visitor's IP address to
# Google on every visit — without consent. The LG München I awarded damages
# for exactly that (3 O 17493/20). Self-hosted, the matter is settled, and
# the page loads faster: two preconnects and one blocking stylesheet go away.
#
#   bash tools/fetch-fonts.sh
#
# Afterwards, apply the two changes to sections/head.php and
# public/css/00-fonts.css that the script prints out.
# ============================================================

set -euo pipefail

cd "$(dirname "$0")/.."
mkdir -p public/fonts

# Latin subsets from the google-webfonts-helper (serves woff2 directly).
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
echo "Fetched:"
ls -1sh public/fonts/*.woff2

cat <<'NEXT'

If any filenames changed in the process, two places depend on them:

1. sections/head.php — the preload of the display face (it sits in the
   H1 of every page):

     <link rel="preload" as="font" type="font/woff2"
           href="<?= esc(asset('fonts/…woff2')) ?>" crossorigin/>

2. public/css/00-fonts.css — the @font-face blocks. The url() values there
   are relative to that file, hence the "../":

     src: url("../fonts/…woff2") format("woff2");

Both names have to match what actually ended up in public/fonts/ above;
`make check` notices a wrong preload path, but not a wrong one in the CSS.

No request goes to Google: the fonts sit on the same server as the site.
Verify with the DevTools (network tab, filter "fonts.g") whenever something
here was changed.
NEXT
