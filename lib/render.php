<?php declare(strict_types=1);
/* ------------------------------------------------------------
   Assembling

   A page consists of sections, and a section is a file in sections/. This
   file puts them together: <head>, header bar, the page's sections in
   their order, footer.

   Every section sees $site, $shows, $booking and $legal — the data from
   content/ — plus whatever stands next to its name in lib/pages.php. It
   does not need to know more, and in particular not which page it is on.
   ------------------------------------------------------------ */

/** One section as a string, without trailing blank lines. */
function section_html(string $name, array $vars = []): string
{
    $file = SITE_ROOT . "/sections/$name.php";

    if (!is_file($file)) {
        throw new RuntimeException("Section missing: sections/$name.php");
    }

    $site = site();
    $shows = shows();
    $booking = booking();
    $legal = legal();

    // The values from lib/pages.php as variables: ['title' => 'Termine']
    // becomes $title. EXTR_SKIP, so that a section cannot accidentally
    // overwrite the data above.
    extract($vars, EXTR_SKIP);

    ob_start();
    require $file;

    return rtrim((string) ob_get_clean(), "\n");
}

/** The same section, printed straight out. */
function section(string $name, array $vars = []): void
{
    echo section_html($name, $vars);
}

/**
 * The sections of a page, with a blank line between them.
 *
 * An entry is either a name ("hero") or a pair of name and values
 * (['page-hero', [...]]).
 */
function sections_html(array $entries): string
{
    $parts = [];

    foreach ($entries as $entry) {
        [$name, $vars] = is_array($entry) ? $entry : [$entry, []];
        $parts[] = section_html($name, $vars);
    }

    return implode("\n\n", $parts);
}

/**
 * The whole page.
 *
 * This is what a file under public/ calls — nothing else stands in there
 * but its own name.
 */
function render_page(string $slug): void
{
    $pages = pages();

    if (!isset($pages[$slug])) {
        throw new RuntimeException("No page \"$slug\" in lib/pages.php");
    }

    current_slug($slug);
    $page = $pages[$slug];

    echo '<!DOCTYPE html>', "\n";
    echo '<html lang="', esc(site()['meta']['lang']), '">', "\n";
    echo <<<HTML
    <!--
         This page is assembled on every request — there is no build and no
         generated file. It consists of:

           content/*.json     the values (links, dates, formats, legal)
           sections/*.php     the sections it is assembled from
           public/…/index.php the two lines that call both together

         So that is where you edit, not here.
    -->
    HTML;
    echo "\n";

    echo "<head>\n";
    section('head', ['slug' => $slug, 'page' => $page]);
    echo "\n</head>\n\n";

    echo "<body>\n\n";
    echo '<a class="skip-link" href="#main">Zum Inhalt springen</a>', "\n\n";

    section('header');
    echo "\n\n";

    echo '<main id="main" tabindex="-1">', "\n\n";
    echo sections_html($page['sections']);
    echo "\n\n</main>\n\n";

    section('footer');
    echo "\n\n";

    if (!empty($page['lightbox'])) {
        section('lightbox');
        echo "\n\n";
    }

    // No modification stamp, unlike the stylesheets: main.js pulls in the
    // remaining modules itself, and their paths live in its source — so a
    // stamp on the parent would only appear to pass an update to slider.js
    // through. js/ therefore relies on the browser's normal revalidation
    // (Last-Modified).
    echo '<script type="module" src="', esc(asset('js/main.js')), '"></script>', "\n";
    echo "</body>\n";
    echo "</html>\n";
}
