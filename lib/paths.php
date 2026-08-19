<?php declare(strict_types=1);
/* ------------------------------------------------------------
   Pfade

   Pfade sind relativ, nicht absolut: jede Seite kennt ihr `base`
   ("" auf der Startseite, "../" in einem Unterordner). So läuft die
   Seite auch in einem Unterverzeichnis, nicht nur an der Wurzel einer
   Domain.

   Alles läuft über base: Datei aus dem Projekt (Foto, Stylesheet) über
   asset(), Verweis auf eine andere Seite über page_link(). Wer
   "images/..." direkt ins Markup schreibt, baut einen Link, der auf
   jeder Unterseite ins Leere zeigt.

   Welche Seite gerade gebaut wird, steht in einer einzigen Variablen:
   ein Aufruf ist genau eine Seite. tools/check.php baut sie alle
   hintereinander und setzt sie dabei nacheinander — deshalb ist es
   eine Funktion und keine Konstante.
   ------------------------------------------------------------ */

/** Setzt oder liest die Seite, die gerade gebaut wird. */
function current_slug(?string $slug = null): string
{
    static $current = 'index';
    if ($slug !== null) $current = $slug;

    return $current;
}

/** Wie tief die Seite liegt — Startseite an der Wurzel, alles andere eine Ebene tiefer. */
function base(): string
{
    return current_slug() === 'index' ? '' : '../';
}

/** Datei aus dem Projekt, von dieser Seite aus gesehen. */
function asset(string $path): string
{
    return base() . $path;
}

/**
 * Datei mit Änderungsstempel: css/01-tokens.css?v=1750000000.
 *
 * Ohne Build gibt es keine Datei, deren Name sich ändert, wenn sich ihr
 * Inhalt ändert. Der Stempel erledigt dasselbe: der Browser darf jede
 * Datei beliebig lange behalten, holt sie aber sofort neu, sobald sie
 * bearbeitet wurde.
 */
function asset_versioned(string $path): string
{
    $file = SITE_ROOT . "/public/$path";
    $stamp = is_file($file) ? filemtime($file) : false;

    return asset($path) . ($stamp ? "?v=$stamp" : '');
}

/**
 * Bevorzugte Datei, sonst die Ausweichdatei.
 *
 * Für die Symbole: das Logo ist ein Foto in voller Größe (2240 px), und es
 * als Favicon, als Symbol fürs Homescreen und als 52-px-Marke in der
 * Kopfleiste auszuliefern heißt, auf jeder Seite ein Viertelmegabyte für
 * einen Kreis von 52 px zu laden. `make icons` legt daneben die kleinen
 * Fassungen; solange die fehlen, bleibt es beim Logo — statt eines toten
 * Verweises, den `make check` zu Recht melden würde.
 */
function asset_or(string $preferred, string $fallback): string
{
    return is_file(SITE_ROOT . "/public/$preferred") ? $preferred : $fallback;
}

/** Andere Seite, von dieser Seite aus gesehen. */
function page_link(string $slug): string
{
    if ($slug === 'index') return base() ?: './';

    return base() . "$slug/";
}

/** Absolute URL, oder null solange die Domain fehlt. */
function absolute(string $path): ?string
{
    $url = site()['url'] ?? null;
    if (!$url) return null;

    return rtrim($url, '/') . '/' . ltrim($path, '/');
}

/** Kanonische URL einer Seite, oder null solange die Domain fehlt. */
function canonical(string $slug): ?string
{
    return absolute($slug === 'index' ? '' : "$slug/");
}

/** Das Bild für die Social-Karte, absolut — oder null. */
function og_image(): ?string
{
    $path = site()['ogImage'] ?? null;

    return $path ? absolute($path) : null;
}

/**
 * Die Stylesheets, in Dateinamenfolge.
 *
 * Vorher setzte der Build die Abschnitte zu einer style.css zusammen.
 * Ohne Build verlinkt die Seite sie einzeln — die Reihenfolge (und damit
 * die Kaskade) macht der Dateiname, nicht eine Liste, die man pflegen
 * müsste. Ein neuer Abschnitt ist eine neue Datei in public/css/ und
 * sonst nichts.
 */
function stylesheets(): array
{
    $files = glob(SITE_ROOT . '/public/css/*.css') ?: [];
    sort($files);

    return array_map(fn(string $file) => 'css/' . basename($file), $files);
}
