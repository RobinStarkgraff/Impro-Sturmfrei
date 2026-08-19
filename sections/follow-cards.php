<?php
/* ------------------------------------------------------------
   Karten mit den Kanälen, auf denen es weitergeht.

   Stehen im Abschnitt "Folgt uns" und noch einmal auf /termine/ —
   deshalb eine eigene Datei. $pad ist die Einrückung: die Karten sollen
   im Quelltext der Seite dort stehen, wo sie hingehören.
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
