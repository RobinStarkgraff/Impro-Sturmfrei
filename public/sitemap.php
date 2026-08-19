<?php
/* ------------------------------------------------------------
   sitemap.xml

   Erreichbar als /sitemap.xml — die .htaccess schreibt die Adresse auf
   diese Datei um. Ohne Domain in content/site.json gibt es keine
   Sitemap: eine mit halben Adressen wäre schlechter als keine.

   Impressum und Datenschutz stehen absichtlich nicht drin: sie sind
   noindex und gehören in den Footer, nicht in den Index.
   ------------------------------------------------------------ */

require dirname(__DIR__) . '/lib/boot.php';

if (!site()['url']) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Keine sitemap.xml: in content/site.json steht noch keine Domain.\n";
    exit;
}

header('Content-Type: application/xml; charset=UTF-8');

/**
 * Wann diese Seite sich zuletzt geändert hat.
 *
 * Ohne Build gibt es kein Erzeugungsdatum, aber es gibt die Dateien, aus
 * denen die Seite entsteht: ihre Abschnitte, die Liste in lib/pages.php und
 * die Angaben aus content/ (aus denen Kopfleiste und Footer jeder Seite
 * kommen). Die jüngste davon ist die ehrliche Antwort.
 *
 * Das ist der Hinweis, wegen dessen ein Crawler nach einer neuen Show
 * zeitnah wiederkommt, statt nach seinem eigenen Takt.
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
