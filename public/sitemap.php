<?php
/* ------------------------------------------------------------
   sitemap.xml

   Reachable as /sitemap.xml — the .htaccess rewrites the address onto this
   file. Without a domain in content/site.json there is no sitemap: one
   with half-finished addresses would be worse than none.

   Impressum and privacy policy are deliberately absent: they are noindex
   and belong in the footer, not in the index.
   ------------------------------------------------------------ */

require dirname(__DIR__) . '/lib/boot.php';

if (!site()['url']) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "No sitemap.xml: content/site.json does not name a domain yet.\n";
    exit;
}

header('Content-Type: application/xml; charset=UTF-8');

/**
 * When this page last changed.
 *
 * Without a build there is no generation date, but there are the files the
 * page is made from: its sections, the list in lib/pages.php and the values
 * from content/ (which every page's header bar and footer come from). The
 * most recent of those is the honest answer.
 *
 * This is the hint that brings a crawler back promptly after a new show
 * instead of on its own schedule.
 */
function last_change(array $page): int
{
    $files = glob(SITE_ROOT . '/content/*.json') ?: [];
    $files[] = SITE_ROOT . '/lib/pages.php';

    foreach ($page['sections'] as $entry) {
        $files[] = SITE_ROOT . '/sections/' . (is_array($entry) ? $entry[0] : $entry) . '.php';
    }

    $times = array_filter(array_map(fn(string $f) => is_file($f) ? (int) filemtime($f) : 0, $files));

    return $times ? max($times) : time();
}

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";

foreach (pages() as $slug => $page) {
    if (!empty($page['noindex'])) continue;

    $slug = (string) $slug;

    echo "  <url>\n";
    echo "    <loc>", esc(canonical($slug)), "</loc>\n";
    echo "    <lastmod>", esc(date('Y-m-d', last_change($page))), "</lastmod>\n";
    echo "  </url>\n";
}

echo "</urlset>\n";
