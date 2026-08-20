<?php
/* ------------------------------------------------------------
   Cards for the channels where things carry on.

   Used in the "Folgt uns" section and once more on /termine/ — hence a
   file of their own. $pad is the indentation: the cards should sit where
   they belong in the page source.
   ------------------------------------------------------------ */

$pad = $pad ?? '';
$cards = [];

foreach ($site['links'] as $entry) {
    ob_start();
    ?>
<a class="follow-card" <?= ext($entry['url']) ?>>
  <span class="follow-card__name"><?= esc($entry['name']) ?></span>
  <span class="follow-card__hint"><?= esc($entry['hint']) ?></span>
  <span class="follow-card__arrow" aria-hidden="true">→</span>
</a>
<?php
    $cards[] = indent_block(rtrim((string) ob_get_clean(), "\n"), $pad);
}

echo implode("\n\n", $cards);
