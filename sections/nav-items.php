<?php
/* ------------------------------------------------------------
   Die Punkte einer Navigationsliste.

   Beschriftung und Ziel stehen einmal in content/site.json. Der Footer
   zeigt alle Punkte, die Kopfleiste die mit "inHeader". aria-current
   markiert die Seite, auf der man gerade steht.

   $pad         Einrückung, damit der Quelltext der Seite lesbar bleibt
   $headerOnly  nur die Punkte mit "inHeader"
   $items       welche Liste (Standard: die Hauptnavigation)
   ------------------------------------------------------------ */

$pad = $pad ?? '';
$headerOnly = $headerOnly ?? false;
$items = $items ?? $site['nav'];

foreach ($items as $item):
    if ($headerOnly && empty($item['inHeader'])) continue;

    $here = $item['page'] === current_slug();
    ?>
<?= $pad ?><li><a href="<?= esc(page_link($item['page'])) ?>"<?= $here ? ' class="is-here" aria-current="page"' : '' ?>><?= esc($item['label']) ?></a></li>
<?php endforeach; ?>
