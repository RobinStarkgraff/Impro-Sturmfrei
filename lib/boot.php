<?php declare(strict_types=1);
/* ============================================================
   Der Anfang jeder Seite.

   Eine Seite unter public/ ist eine Datei mit zwei Zeilen: sie holt
   diese Datei und sagt, welche Seite sie ist. Alles andere — Daten,
   Werkzeug, <head>, Kopfleiste, Abschnitte, Footer — hängt hier dran.

   Es gibt keinen Build. Die Seite entsteht bei jedem Aufruf aus
   content/*.json und sections/*.php; im Repo steht damit genau das,
   was ausgeliefert wird, und nichts daneben, das erst erzeugt werden
   müsste.

   Was wo liegt:

     content/*.json    die Angaben, die mehr als einmal vorkommen
     lib/              dieses Werkzeug: Daten, Pfade, HTML, JSON-LD
     sections/*.php    je ein Abschnitt der Seite, als HTML mit Löchern
     public/           was ausgeliefert wird: die Seiten, css/, js/,
                       images/, fonts/

   public/ ist der Docroot (siehe .htaccess). content/, lib/ und
   sections/ liegen darüber und sind damit nicht adressierbar — nicht
   „gesperrt", sondern für den Webserver nicht vorhanden.
   ============================================================ */

define('SITE_ROOT', dirname(__DIR__));

/* ------------------------------------------------------------
   Wenn etwas schiefgeht

   Ohne Build ist ein Komma zu viel in content/shows.json kein halber
   Build, sondern eine Ausnahme mitten im Seitenaufbau. Was der Besucher
   davon sieht, darf nicht vom PHP-Standard des Webspace abhängen: dort
   steht display_errors oft auf On, und dann steht der absolute Pfad
   dieses Verzeichnisses samt Aufrufliste im Netz.

   Also: im Browser ein Satz und ein 500er, im Fehlerprotokoll die
   Einzelheiten. Auf der Kommandozeile bleibt alles sichtbar — `make
   check` soll laut sein.
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
