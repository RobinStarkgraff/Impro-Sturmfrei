<?php declare(strict_types=1);
/* ============================================================
   Nur für `make serve`.

   Der eingebaute Server von PHP kennt keine .htaccess. Zwei Adressen
   sind dort umgeschrieben — /sitemap.xml und /robots.txt zeigen auf
   PHP-Dateien —, und das erledigt dieses Skript, damit lokal dasselbe
   erreichbar ist wie live.

   Alles andere gibt es unverändert weiter: `return false` heißt für den
   eingebauten Server "liefere die Datei selbst aus".

   Auf dem Webspace läuft diese Datei nicht. Sie liegt in tools/ und ist
   damit ohnehin nicht adressierbar.
   ============================================================ */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$public = dirname(__DIR__) . '/public';

if ($path === '/sitemap.xml') { require "$public/sitemap.php"; return; }
if ($path === '/robots.txt')  { require "$public/robots.php"; return; }

// Was es nicht gibt, ist ein 404 — auch lokal. Der eingebaute Server würde
// sonst für /gibtsnicht/ die Startseite ausliefern, und ein toter Link fiele
// erst live auf.
//
// Gezeigt wird dieselbe Seite, die auf dem Server das ErrorDocument der
// .htaccess einsetzt: sonst sähe man die 404-Seite lokal nie und merkte
// nicht, wenn sie kaputt ist.
$ziel = rtrim($public . rawurldecode($path), '/');

if ($path !== '/' && !file_exists($ziel)) {
    http_response_code(404);
    require "$public/404/index.php";

    return;
}

return false;
