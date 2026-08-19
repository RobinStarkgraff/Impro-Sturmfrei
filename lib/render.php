<?php declare(strict_types=1);
/* ------------------------------------------------------------
   Zusammensetzen

   Eine Seite besteht aus Abschnitten, und ein Abschnitt ist eine Datei
   in sections/. Diese Datei setzt sie zusammen: <head>, Kopfleiste, die
   Abschnitte der Seite in ihrer Reihenfolge, Footer.

   Jeder Abschnitt sieht $site, $shows, $booking und $legal — die Daten
   aus content/ — und zusätzlich das, was in lib/pages.php neben seinem
   Namen steht. Mehr braucht er nicht zu wissen, insbesondere nicht, auf
   welcher Seite er steht.
   ------------------------------------------------------------ */

/** Ein Abschnitt als Zeichenkette, ohne Leerzeilen am Ende. */
function section_html(string $name, array $vars = []): string
{
    $file = SITE_ROOT . "/sections/$name.php";

    if (!is_file($file)) {
        throw new RuntimeException("Abschnitt fehlt: sections/$name.php");
    }

    $site = site();
    $shows = shows();
    $booking = booking();
    $legal = legal();

    // Die Angaben aus lib/pages.php als Variablen: aus ['title' => 'Termine']
    // wird $title. EXTR_SKIP, damit ein Abschnitt die Daten oben nicht
    // versehentlich überschreiben kann.
    extract($vars, EXTR_SKIP);

    ob_start();
    require $file;

    return rtrim((string) ob_get_clean(), "\n");
}

/** Denselben Abschnitt direkt ausgeben. */
function section(string $name, array $vars = []): void
{
    echo section_html($name, $vars);
}

/**
 * Die Abschnitte einer Seite, mit einer Leerzeile dazwischen.
 *
 * Ein Eintrag ist entweder ein Name ("hero") oder ein Paar aus Name und
 * Angaben (['page-hero', [...]]).
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
 * Die ganze Seite.
 *
 * Das ist, was eine Datei unter public/ aufruft — mehr steht dort nicht
 * drin als ihr eigener Name.
 */
function render_page(string $slug): void
{
    $pages = pages();

    if (!isset($pages[$slug])) {
        throw new RuntimeException("Keine Seite \"$slug\" in lib/pages.php");
    }

    current_slug($slug);
    $page = $pages[$slug];

    echo '<!DOCTYPE html>', "\n";
    echo '<html lang="', esc(site()['meta']['lang']), '">', "\n";
    echo <<<HTML
    <!--
         Diese Seite wird bei jedem Aufruf zusammengesetzt — es gibt keinen
         Build und keine erzeugte Datei. Sie besteht aus:

           content/*.json     die Angaben (Links, Termine, Formate, Rechtliches)
           sections/*.php     die Abschnitte, aus denen sie zusammengesetzt ist
           public/…/index.php die zwei Zeilen, die beides zusammenrufen

         Bearbeitet wird also dort, nicht hier.
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

    // Ohne Änderungsstempel, anders als die Stylesheets: main.js lädt die
    // übrigen Module selbst nach, und deren Pfade stehen in seinem
    // Quelltext — ein Stempel am Elternteil würde ein Update von
    // slider.js also nur scheinbar durchreichen. Für js/ gilt daher die
    // normale Rückfrage des Browsers (Last-Modified).
    echo '<script type="module" src="', esc(asset('js/main.js')), '"></script>', "\n";
    echo "</body>\n";
    echo "</html>\n";
}
