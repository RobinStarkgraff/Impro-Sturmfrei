<?php
/* ------------------------------------------------------------
   The lightbox.

   Only present on pages with sliders ("lightbox" => true in
   lib/pages.php); js/lightbox.js finds it by its ID and does nothing
   without it.

   The three buttons differ in three values — hence a list and a loop. The
   name goes into the class and into the data attribute through which
   js/lightbox.js finds the button. The labels are read out to visitors, so
   they stay German.
   ------------------------------------------------------------ */

$buttons = [
    ['close', '✕', 'Schließen'],
    ['prev', '‹', 'Vorheriges Bild'],
    ['next', '›', 'Nächstes Bild'],
];
?>
<!-- ================= LIGHTBOX ================= -->
<dialog class="lightbox" id="lightbox" aria-label="Bildansicht">
  <div class="lightbox__stage">

    <!-- No src attribute at all, rather than an empty one: an empty one would
         load the page itself a second time as an image. js/lightbox.js sets
         source and alt text on opening. -->
    <img class="lightbox__img" id="lightbox-img" alt=""/>
<?php foreach ($buttons as [$kind, $glyph, $label]): ?>

    <button class="icon-btn icon-btn--glass lightbox__btn lightbox__btn--<?= esc($kind) ?>" type="button" data-lightbox-<?= esc($kind) ?>>
      <span aria-hidden="true"><?= esc($glyph) ?></span>
      <span class="visually-hidden"><?= esc($label) ?></span>
    </button>
<?php endforeach; ?>

    <!-- role="status": while paging through, image and alt text change
         silently. This way a screen reader at least announces where in the
         group you currently are. -->
    <p class="lightbox__counter" id="lightbox-counter" role="status"></p>

  </div>
</dialog>
