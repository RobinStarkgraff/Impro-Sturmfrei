<?php
/* ------------------------------------------------------------
   The items of a navigation list.

   Label and target live once in content/site.json. The footer shows every
   item, the header bar the ones marked "inHeader". aria-current marks the
   page you are currently on.

   $pad         indentation, to keep the page source readable
   $headerOnly  only the items marked "inHeader"
   $items       which list (default: the main navigation)
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
