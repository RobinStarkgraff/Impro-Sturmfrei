<?php
/* ------------------------------------------------------------
   Cards for the channels where things carry on.

   Used in the "Folgt uns" section and once more on /termine/ — hence a
   file of their own. $pad is the indentation: the cards should sit where
   they belong in the page source.
   ------------------------------------------------------------ */

$pad = $pad ?? '';
$cards = [];

/* The channels, their glyphs and their colours all come from
   content/site.json — see links() in lib/data.php. The platform's colour
   rides on the card as a custom property and nowhere else: it is the one
   colour on this site that is not ours, so it belongs with the channel it
   names and not in the stylesheet. Without one the glyph takes the accent,
   which is what 12-follow.css falls back to. */
foreach (links() as $entry) {
    ob_start();
    ?>
<a class="follow-card" <?= ext($entry['url']) ?><?= $entry['brand'] ? ' style="--brand: ' . esc($entry['brand']) . '"' : '' ?>>
<?php if ($glyph = icon($entry['icon'], 'follow-card__glyph')): ?>
  <span class="follow-card__icon"><?= $glyph ?></span>
<?php endif; ?>
  <span class="follow-card__name"><?= esc($entry['name']) ?></span>
  <span class="follow-card__hint"><?= esc($entry['hint']) ?></span>
  <span class="follow-card__arrow" aria-hidden="true">→</span>
</a>
<?php
    $cards[] = indent_block(rtrim((string) ob_get_clean(), "\n"), $pad);
}

echo implode("\n\n", $cards);
