<?php
/* ------------------------------------------------------------
   robots.txt

   Erreichbar als /robots.txt — die .htaccess schreibt die Adresse auf
   diese Datei um. Der Verweis auf die Sitemap braucht eine absolute
   Adresse, also die Domain aus content/site.json.
   ------------------------------------------------------------ */

require dirname(__DIR__) . '/lib/boot.php';

header('Content-Type: text/plain; charset=UTF-8');

echo "User-agent: *\n";
echo "Allow: /\n";

if ($sitemap = absolute('sitemap.xml')) {
    echo "\nSitemap: $sitemap\n";
}
