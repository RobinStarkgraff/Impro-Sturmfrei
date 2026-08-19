<?php
/* ------------------------------------------------------------
   Die Lightbox.

   Steht nur auf Seiten mit Slidern ("lightbox" => true in lib/pages.php);
   js/lightbox.js findet sie über die ID und tut ohne sie nichts.

   Die drei Knöpfe unterscheiden sich in drei Werten — deshalb eine Liste
   und eine Schleife. Der Name steht in der Klasse und im data-Attribut,
   über das js/lightbox.js den Knopf findet.
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

    <!-- Ohne src-Attribut, nicht mit einem leeren: ein leeres lädt sonst die
         Seite selbst noch einmal als Bild. js/lightbox.js setzt Quelle und
         alt-Text beim Öffnen. -->
    <img class="lightbox__img" id="lightbox-img" alt=""/>
<?php foreach ($buttons as [$kind, $glyph, $label]): ?>

    <button class="icon-btn icon-btn--glass lightbox__btn lightbox__btn--<?= esc($kind) ?>" type="button" data-lightbox-<?= esc($kind) ?>>
      <span aria-hidden="true"><?= esc($glyph) ?></span>
      <span class="visually-hidden"><?= esc($label) ?></span>
    </button>
<?php endforeach; ?>

    <!-- role="status": beim Blättern wechseln Bild und alt-Text lautlos.
         So liest ein Screenreader wenigstens vor, an welcher Stelle der
         Gruppe man gerade steht. -->
    <p class="lightbox__counter" id="lightbox-counter" role="status"></p>

  </div>
</dialog>
