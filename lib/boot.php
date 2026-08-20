<?php declare(strict_types=1);
/* ============================================================
   Where every page begins.

   A page under public/ is a two-line file: it pulls in this one and
   says which page it is. Everything else — data, tooling, <head>,
   header bar, sections, footer — hangs off here.

   There is no build. A page is assembled on every request from
   content/*.json and sections/*.php; the repo therefore holds exactly
   what gets served, and nothing beside it that would have to be
   generated first.

   What lives where:

     content/*.json    the values that occur more than once
     lib/              this tooling: data, paths, HTML, JSON-LD
     sections/*.php    one section of the page each, HTML with holes
     public/           what gets served: the pages, css/, js/,
                       images/, fonts/

   public/ is the docroot (see .htaccess). content/, lib/ and sections/
   sit above it and are therefore not addressable — not "locked off",
   but non-existent as far as the web server is concerned.
   ============================================================ */

define('SITE_ROOT', dirname(__DIR__));

/* ------------------------------------------------------------
   When something goes wrong

   Without a build, one comma too many in content/shows.json is not
   half a build but an exception in the middle of assembling the page.
   What the visitor sees of it must not depend on the webspace's PHP
   defaults: there display_errors is often On, and then the absolute
   path of this directory lands on the web along with a stack trace.

   So: one sentence and a 500 in the browser, the details in the error
   log. On the command line everything stays visible — `make check` is
   meant to be loud.
   ------------------------------------------------------------ */

if (PHP_SAPI !== 'cli') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');

    set_exception_handler(function (Throwable $e): void {
        error_log('Sturmfrei: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo "<!DOCTYPE html>\n";
        echo '<html lang="de"><head><meta charset="UTF-8"/>', "\n";
        echo '<title>Sturmfrei — da klemmt gerade etwas</title></head>', "\n";
        echo "<body style=\"font-family:system-ui,sans-serif;max-width:34rem;margin:4rem auto;padding:0 1.5rem;line-height:1.6\">\n";
        echo "<h1>Da klemmt gerade etwas.</h1>\n";
        echo "<p>Diese Seite lässt sich im Moment nicht zusammensetzen. Wir wissen davon —\n";
        echo "der Fehler steht im Protokoll des Servers.</p>\n";
        echo '<p><a href="/">Zurück zur Startseite</a></p>', "\n";
        echo "</body></html>\n";
    });
}

require SITE_ROOT . '/lib/data.php';
require SITE_ROOT . '/lib/html.php';
require SITE_ROOT . '/lib/paths.php';
require SITE_ROOT . '/lib/schema.php';
require SITE_ROOT . '/lib/pages.php';
require SITE_ROOT . '/lib/render.php';
