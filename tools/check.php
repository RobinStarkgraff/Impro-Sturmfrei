<?php declare(strict_types=1);
/* ============================================================
   Checks the pages without installing anything.

   No substitute for a real HTML validator, but it catches exactly the
   mistakes that have happened here before: a jump target that does not
   exist, an image path with a typo, a target="_blank" without rel, a
   mandatory detail still missing from content/legal.json.

   Since the site grew into several pages, the most common new mistake has
   joined them: a path that is right from the home page and points nowhere
   from a subfolder. Hence every reference is resolved relative to the page
   it stands in — exactly the way the browser does it.

   The search happens in public/: that is the root of the served site, and
   only what lies there is reachable in a browser.

   Nothing is built here. The pages are rendered just as they are on a
   request, only into memory instead of to the browser — so what gets
   checked is exactly what gets served.

   Run with:  php tools/check.php   (or: make check)
   ============================================================ */

require dirname(__DIR__) . '/lib/boot.php';

$problems = [];
$warnings = [];

$fail = function (string $message) use (&$problems): void { $problems[] = $message; };
$warn = function (string $message) use (&$warnings): void { $warnings[] = $message; };

$out = SITE_ROOT . '/public';

/* ------------------------------------------------------------
   Rendering the pages

   Per page: the file that serves it (for the message and as the starting
   point for relative paths), and its HTML.
   ------------------------------------------------------------ */

/** public/index.php or public/termine/index.php */
function file_for(string $slug): string
{
    return $slug === 'index' ? 'index.php' : "$slug/index.php";
}

$rendered = [];

foreach (pages() as $slug => $page) {
    // (string): PHP turns the key "404" into a number — see the note at
    // slugs() in lib/pages.php.
    $slug = (string) $slug;

    ob_start();

    try {
        render_page($slug);
        $rendered[$slug] = ['file' => file_for($slug), 'html' => (string) ob_get_clean()];
    } catch (Throwable $e) {
        ob_end_clean();
        $fail(file_for($slug) . ': ' . $e->getMessage());
    }
}

/* ------------------------------------------------------------
   1. Is there a file for every page — and a page for every file?

   This is the check that replaces the old "generated file against source"
   comparison: nothing is generated any more, but the two lines under
   public/ and the entry in lib/pages.php have to match each other.
   ------------------------------------------------------------ */

function check_page_files(callable $fail, string $out): void
{
    foreach (pages() as $slug => $page) {
        $slug = (string) $slug;
        $file = file_for($slug);
        $path = "$out/$file";

        if (!is_file($path)) {
            $fail("public/$file is missing — the page \"$slug\" is in lib/pages.php but cannot be reached");
            continue;
        }

        // Does the file actually call the page it claims to be? Otherwise a
        // copied folder with a forgotten name would be the same page twice
        // under two addresses.
        if (!preg_match("/render_page\(\s*'" . preg_quote($slug, '/') . "'\s*\)/", (string) file_get_contents($path))) {
            $fail("public/$file does not call render_page('$slug')");
        }
    }

    // The other way round: an index.php under public/ with no entry for it.
    foreach (glob("$out/*/index.php") ?: [] as $path) {
        $slug = basename(dirname($path));
        if (!isset(pages()[$slug])) {
            $fail("public/$slug/index.php exists, but there is no page \"$slug\" in lib/pages.php");
        }
    }
}

/* ------------------------------------------------------------
   2. Files and pages the pages point at

   Resolved as in the browser: relative to the folder of the page the
   reference stands in. A reference to a folder (or to "../") hits that
   folder's index.php.
   ------------------------------------------------------------ */

/** Like posix.normalize: "a/b/../c" → "a/c", without asking the disk. */
function normalize_path(string $path): string
{
    $parts = [];

    foreach (explode('/', $path) as $part) {
        if ($part === '' || $part === '.') continue;

        if ($part === '..') {
            if ($parts && end($parts) !== '..') array_pop($parts);
            else $parts[] = '..';

            continue;
        }

        $parts[] = $part;
    }

    return implode('/', $parts);
}

/**
 * A reference, resolved as in the browser: relative to $from, the folder of
 * the file it stands in. $where is only there for the message.
 */
function check_target(string $target, string $from, string $where, callable $fail, string $out): void
{
    if ($target === '' || str_starts_with($target, '#')) return;
    if (preg_match('/^(https?:|mailto:|tel:|data:)/', $target)) return;

    // Anchors and modification stamps are not part of the filename.
    $clean = preg_replace('/[#?].*$/', '', $target);
    $joined = normalize_path(($from ? "$from/" : '') . $clean);

    // No valid reference points out of public/: what does not lie there is
    // not reachable in a browser.
    if (str_starts_with($joined, '..')) {
        $fail("$where: \"$target\" points out of public/");
        return;
    }

    $path = "$out/$joined";
    $shown = $joined;

    if (is_dir($path)) {
        $path .= '/index.php';
        $shown .= '/index.php';
    }

    if (!is_file($path)) {
        $fail("$where: \"$target\" does not exist (looked for: $shown)");
    }
}

function check_local_targets(array $rendered, callable $fail, string $out): void
{
    foreach ($rendered as $page) {
        $from = dirname($page['file']);
        $from = $from === '.' ? '' : $from;

        preg_match_all('/(?:src|href)="([^"]*)"/', $page['html'], $matches);

        foreach ($matches[1] as $target) {
            check_target($target, $from, $page['file'], $fail, $out);
        }

        // Not every path stands in src or href. The crossfade's image
        // sequence travels as JSON in a data attribute — a typo in there
        // would otherwise stay silent: the page looks intact, the swap
        // simply never happens.
        preg_match_all('/data-crossfade="([^"]*)"/', $page['html'], $lists);

        foreach ($lists[1] as $raw) {
            $photos = json_decode(html_entity_decode($raw, ENT_QUOTES, 'UTF-8'), true);

            if (!is_array($photos)) {
                $fail("{$page['file']}: data-crossfade is not valid JSON");
                continue;
            }

            foreach ($photos as $photo) {
                check_target((string) $photo, $from, "{$page['file']} (data-crossfade)", $fail, $out);
            }
        }
    }
}

/**
 * The url() values in the stylesheets.
 *
 * The second kind of path nobody used to check — and the only one
 * tools/fetch-fonts.sh explicitly warns about: if the google-webfonts-helper
 * names a file differently, @font-face points nowhere and the site falls
 * back to Arial Narrow in silence. The preload in the <head> would show up
 * (it stands in src/href), the four @font-face rules did not.
 *
 * Resolved relative to the CSS file itself, because that is exactly what
 * the browser does: hence "../fonts/…" in public/css/00-fonts.css.
 */
function check_css_urls(callable $fail, string $out): void
{
    foreach (glob("$out/css/*.css") ?: [] as $file) {
        $from = 'css';
        $name = 'css/' . basename($file);

        preg_match_all('/url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)/', (string) file_get_contents($file), $urls);

        foreach ($urls[1] as $target) {
            check_target(trim($target), $from, $name, $fail, $out);
        }
    }
}

/* Photos that are on disk but missing from content/shows.json — those show
   up nowhere on the site. The opposite case (listed but not there) is
   caught by check 2: the <img> then points nowhere. */
function check_photo_folders(callable $fail, callable $warn, string $out): void
{
    foreach (shows()['past'] as $show) {
        $dir = "images/shows/{$show['date']}";

        if (!is_dir("$out/$dir")) {
            $fail("Folder missing: public/$dir");
            continue;
        }

        $listed = array_column($show['photos'], 'file');

        foreach (scandir("$out/$dir") ?: [] as $name) {
            if (!preg_match('/\.jpe?g$/i', $name)) continue;
            if (!in_array($name, $listed, true)) {
                $warn("public/$dir/$name is on disk but not listed in shows.json");
            }
        }
    }
}

/* ------------------------------------------------------------
   3. Jump targets — per page, because an ID on another page is no help
      to an #anchor.
   ------------------------------------------------------------ */

function check_anchors(array $rendered, callable $fail): void
{
    foreach ($rendered as $page) {
        preg_match_all('/\bid="([^"]+)"/', $page['html'], $found);
        $ids = $found[1];

        preg_match_all('/href="#([^"]+)"/', $page['html'], $anchors);

        foreach ($anchors[1] as $id) {
            if (!in_array($id, $ids, true)) {
                $fail("{$page['file']}: jump target #$id does not exist there");
            }
        }

        preg_match_all('/aria-(?:labelledby|controls)="([^"]+)"/', $page['html'], $aria);

        foreach ($aria[1] as $value) {
            foreach (preg_split('/\s+/', $value) ?: [] as $id) {
                if ($id !== '' && !in_array($id, $ids, true)) {
                    $fail("{$page['file']}: aria reference to #$id does not exist there");
                }
            }
        }
    }
}

/* ------------------------------------------------------------
   4. Accessibility, external links, document outline
   ------------------------------------------------------------ */

function check_images_and_links(array $rendered, callable $fail): void
{
    $titles = [];

    foreach ($rendered as $page) {
        preg_match_all('/<img\b[^>]*>/', $page['html'], $images);

        foreach ($images[0] as $tag) {
            if (!str_contains($tag, ' alt="')) {
                $fail("{$page['file']}: <img> without alt: " . substr($tag, 0, 70) . '…');
            }
        }

        preg_match_all('/<a\b[^>]*>/', $page['html'], $links);

        foreach ($links[0] as $tag) {
            if (str_contains($tag, 'target="_blank"') && !preg_match('/rel="[^"]*noopener/', $tag)) {
                $fail("{$page['file']}: target=\"_blank\" without rel=\"noopener\": " . substr($tag, 0, 70) . '…');
            }
        }

        // Exactly one h1 per page: it is the page's name. Two are an outline
        // without a top, none is a page without a name.
        $h1s = preg_match_all('/<h1\b/', $page['html']);
        if ($h1s !== 1) $fail("{$page['file']}: $h1s <h1> — there must be exactly one");

        // One <title> per page, and not the same on all of them: that was
        // the whole reason for splitting the site up.
        if (!preg_match('/<title>([^<]+)<\/title>/', $page['html'], $title)) {
            $fail("{$page['file']}: <title> is missing or empty");
            continue;
        }

        $titles[$page['file']] = $title[1];
    }

    foreach (array_count_values($titles) as $title => $count) {
        if ($count > 1) $fail("two pages carry the same <title>: \"$title\"");
    }
}

/* ------------------------------------------------------------
   5. Navigation — does it point at pages that exist?
   ------------------------------------------------------------ */

function check_nav(callable $fail, callable $warn): void
{
    $items = array_merge(site()['nav'], site()['legalNav']);

    foreach ($items as $item) {
        if (!in_array($item['page'], slugs(), true)) {
            $fail("content/site.json: \"{$item['label']}\" points at the page \"{$item['page']}\", which does not exist in lib/pages.php");
        }
    }

    // The other way round: a page in no list is only reachable through a
    // direct link — usually an oversight.
    //
    // 404 is the exception and not sloppiness: it is not navigated to but
    // inserted by Apache (ErrorDocument in the .htaccess). A menu item
    // reading "Nicht gefunden" would be nonsense.
    $linked = array_column($items, 'page');
    $unlisted = ['404'];

    foreach (slugs() as $slug) {
        if (!in_array($slug, $linked, true) && !in_array($slug, $unlisted, true)) {
            $warn("the page \"$slug\" is in no navigation — nobody will find it");
        }
    }
}

/* ------------------------------------------------------------
   6. The contract from js/classes.js

   State classes and hooks live there once; CSS and markup have to know
   them. The source is read with a pattern — an import as before is not
   possible, PHP cannot do ES modules. So anyone reformatting that file has
   to look in here; the names themselves still live in one place only.
   ------------------------------------------------------------ */

/** The values of an object literal from js/classes.js. */
function js_values(string $source, string $name): array
{
    if (!preg_match('/export const ' . $name . ' = \{(.*?)\n\};/s', $source, $block)) return [];

    preg_match_all('/:\s*"([^"]+)"/', $block[1], $values);

    return $values[1];
}

function kebab(string $value): string
{
    return strtolower(preg_replace('/([A-Z])/', '-$1', $value));
}

function check_contract(array $rendered, callable $fail, callable $warn, string $out): void
{
    $source = (string) file_get_contents("$out/js/classes.js");

    $css = '';
    foreach (glob("$out/css/*.css") ?: [] as $file) $css .= file_get_contents($file);

    // Every state name has to appear in the CSS, otherwise the JS toggles
    // something nobody renders.
    foreach (array_merge(js_values($source, 'CSS_CLASS'), js_values($source, 'DATA_KEY')) as $name) {
        if ($name === 'js') continue; // appears in the CSS as the prefix .js, not as a class

        if (!str_contains($css, ".$name") && !str_contains($css, 'data-' . kebab($name))) {
            $warn("js/classes.js knows \"$name\", the CSS says nothing about it");
        }
    }

    // And the other way round: the hooks the modules hang on have to appear
    // in the markup. On which page is left open — the lightbox belongs to
    // the archive only. But on none at all the module would be dead code.
    foreach (js_values($source, 'DATA_HOOK') as $hook) {
        $found = false;

        foreach ($rendered as $page) {
            if (str_contains($page['html'], $hook)) { $found = true; break; }
        }

        if (!$found) $fail("js/ looks for $hook, no page has it");
    }
}

/* ------------------------------------------------------------
   7. Sections nobody calls

   Not an error, but worth a note: a file in sections/ that does not appear
   in lib/pages.php is never served. A missing section already shows up
   during the rendering above.
   ------------------------------------------------------------ */

function check_sections(callable $warn): void
{
    // The sections a page names directly …
    $used = [];

    foreach (pages() as $page) {
        foreach ($page['sections'] as $entry) {
            $used[] = is_array($entry) ? $entry[0] : $entry;
            // … and those passed alongside as a value (page-hero → actions).
            if (is_array($entry) && isset($entry[1]['actions'])) $used[] = $entry[1]['actions'];
        }
    }

    // lib/render.php calls the first four for every page, so they appear in
    // no list. The rest are called by another section: nav-items from header
    // and footer, follow-cards from follow and dates, legal-gap from
    // impressum and privacy.
    $always = [
        'head', 'header', 'footer', 'lightbox',
        'nav-items', 'follow-cards', 'legal-gap',
    ];

    foreach (glob(SITE_ROOT . '/sections/*.php') ?: [] as $file) {
        $name = basename($file, '.php');

        if (!in_array($name, $used, true) && !in_array($name, $always, true)) {
            $warn("no page calls sections/$name.php — left over?");
        }
    }
}

/* ------------------------------------------------------------
   8. The dates themselves

   Two things nobody else notices: a date that is not one, and a "next"
   date that passed long ago.
   ------------------------------------------------------------ */

function check_shows(callable $fail, callable $warn): void
{
    $next = shows()['upcoming'] ?? null;

    if ($next) {
        if (empty($next['date']) || !is_iso_date((string) $next['date'])) {
            $fail('content/shows.json: "upcoming.date" is not a date in the form 2026-09-19');
        } elseif ($next['date'] < today()) {
            // Not an error: thanks to upcoming_show() the site already stops
            // showing the date. But the entry belongs moved, or it will sit
            // in the file as a corpse forever.
            $warn(
                'content/shows.json: the show on ' . date_de((string) $next['date']) . ' is over. ' .
                'The site no longer shows it as the next date — the entry now belongs ' .
                'under "past" (with photos in public/images/shows/' . $next['date'] . '/), and ' .
                '"upcoming" back to null.'
            );
        }
    }

    foreach (shows()['past'] as $show) {
        if (empty($show['date']) || !is_iso_date((string) $show['date'])) {
            $fail(
                'content/shows.json: "' . ($show['title'] ?? '?') . '" has no date in the form ' .
                '2026-01-09 — the photo folder name and the JSON-LD both hang off it'
            );
            continue;
        }

        if ($show['date'] > today()) {
            $warn(
                'content/shows.json: "' . $show['title'] . '" is listed under "past", but its date ' .
                date_de((string) $show['date']) . ' is still in the future'
            );
        }
    }
}

/* ------------------------------------------------------------
   8. Open points in the content
   ------------------------------------------------------------ */

function report_content_gaps(callable $fail, callable $warn, string $out): void
{
    $open = 0;

    foreach (shows()['past'] as $show) {
        foreach ($show['photos'] as $photo) if (!$photo['alt']) $open++;
    }

    if ($open) {
        $warn(
            "$open of " . photo_count() . ' photos still have no alt text of their own ' .
            '(content/shows.json). The stand-in is the running number — for screen readers ' .
            'that is next to nothing.'
        );
    }

    if (!site()['url']) {
        $warn('content/site.json: "url" is not set yet, so there is no canonical, no og:url and no sitemap.xml');
    } elseif (!site()['ogImage']) {
        $warn('content/site.json: "ogImage" is missing — links in Instagram and WhatsApp show a card without an image');
    } elseif (!is_file($out . '/' . site()['ogImage'])) {
        // Only appears in the markup as an absolute URL, so it does not show
        // up there.
        $fail('content/site.json points at ' . site()['ogImage'] . ', public/' . site()['ogImage'] . ' does not exist');
    }

    // Mandatory details: ERROR, not a note. Without them the site must not go
    // public (§ 5 DDG), and the placeholder stands visibly on the page — that
    // is not something anyone should publish by accident.
    $required = [
        ['impressum.responsible', legal()['impressum']['responsible'], 'name of the authorised representative'],
        ['impressum.street', legal()['impressum']['street'], 'street and house number'],
        ['impressum.postalCode', legal()['impressum']['postalCode'], 'postal code'],
        ['impressum.city', legal()['impressum']['city'], 'town'],
        ['privacy.host', legal()['privacy']['host'], 'hosting provider for the privacy policy'],
    ];

    foreach ($required as [$key, $value, $what]) {
        if (!$value) {
            $fail("content/legal.json: \"$key\" is missing — $what. Until then the page shows a visible placeholder.");
        }
    }

    // While the small icons are missing, every page serves the full-size logo
    // as the favicon, as the home-screen icon and as the 52 px mark in the
    // header bar (asset_or in lib/paths.php catches it).
    $icons = ['images/logo/favicon.png', 'images/logo/apple-touch-icon.png', 'images/logo/logo-mark.jpg'];
    $missing_icons = array_values(array_filter($icons, fn(string $i) => !is_file("$out/$i")));

    if ($missing_icons) {
        $warn(
            count($missing_icons) . ' of 3 icon files are missing (' . implode(', ', $missing_icons) . '). ' .
            'Until then every page serves ' . site()['brand']['logo'] . ' at full size instead. ' .
            'Create them with: make icons'
        );
    }

    if (!booking()['reviewed']) {
        $warn(
            'content/booking.json: "reviewed" is false — formats, durations, head counts and ' .
            'the answers under faq are drafts and have not been reviewed by anyone yet.'
        );
    }
}

/* ------------------------------------------------------------
   Go
   ------------------------------------------------------------ */

check_page_files($fail, $out);
check_local_targets($rendered, $fail, $out);
check_css_urls($fail, $out);
check_photo_folders($fail, $warn, $out);
check_anchors($rendered, $fail);
check_images_and_links($rendered, $fail);
check_nav($fail, $warn);
check_contract($rendered, $fail, $warn, $out);
check_sections($warn);
check_shows($fail, $warn);
report_content_gaps($fail, $warn, $out);

foreach ($warnings as $message) echo "  Note:  $message\n";
foreach ($problems as $message) echo "  ERROR: $message\n";

echo "\n" . count($rendered) . ' pages checked.';
echo $problems
    ? ' ' . count($problems) . ' errors, ' . count($warnings) . " notes.\n"
    : ' All good' . ($warnings ? ', ' . count($warnings) . ' notes' : '') . ".\n";

exit($problems ? 1 : 0);
