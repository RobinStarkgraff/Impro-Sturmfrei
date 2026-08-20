<?php
/* ------------------------------------------------------------
   robots.txt

   Reachable as /robots.txt — the .htaccess rewrites the address onto this
   file. The pointer to the sitemap needs an absolute address, so it needs
   the domain from content/site.json.
   ------------------------------------------------------------ */

require dirname(__DIR__) . '/lib/boot.php';

header('Content-Type: text/plain; charset=UTF-8');

echo "User-agent: *\n";
echo "Allow: /\n";

if ($sitemap = absolute('sitemap.xml')) {
    echo "\nSitemap: $sitemap\n";
}
