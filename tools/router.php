<?php declare(strict_types=1);
/* ============================================================
   For `make serve` only.

   PHP's built-in server knows nothing about .htaccess. Two addresses are
   rewritten there — /sitemap.xml and /robots.txt point at PHP files — and
   this script takes care of that, so the same things are reachable locally
   as they are live.

   Everything else it passes through unchanged: `return false` tells the
   built-in server "serve the file yourself".

   This file does not run on the webspace. It sits in tools/ and is
   therefore not addressable anyway.
   ============================================================ */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$public = dirname(__DIR__) . '/public';

if ($path === '/sitemap.xml') { require "$public/sitemap.php"; return; }
if ($path === '/robots.txt')  { require "$public/robots.php"; return; }

// What does not exist is a 404 — locally too. Otherwise the built-in server
// would serve the home page for /doesnotexist/, and a dead link would only
// show up live.
//
// What gets shown is the same page the .htaccess ErrorDocument inserts on
// the server: otherwise you would never see the 404 page locally and would
// not notice when it breaks.
$target = rtrim($public . rawurldecode($path), '/');

if ($path !== '/' && !file_exists($target)) {
    http_response_code(404);
    require "$public/404/index.php";

    return;
}

return false;
