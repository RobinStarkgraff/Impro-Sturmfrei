/* ------------------------------------------------------------
   Alle abgestimmten Zahlen an einer Stelle.

   Was hier steht, ist aufeinander abgestimmt und trägt einen Namen: keine
   nackte 4 mitten in der Logik, über deren Grund man raten muss.
   ------------------------------------------------------------ */

export const HEADER = {
  /* ab so vielen Pixeln Scrollweg wird die Leiste undurchsichtig */
  solidAfterPx: 40
};

export const REVEAL = {
  /* etwas vor dem unteren Rand auslösen, damit die Bewegung noch
     im Blickfeld beginnt */
  rootMargin: "0px 0px -10% 0px",
  threshold: 0.1
};

export const CROSSFADE = {
  /* Standzeit je Gruppenfoto; die Blende selbst dauert 1.2s (CSS) */
  holdMs: 6000
};

export const SLIDER = {
  /* Anteil der Sliderbreite pro Pfeilklick */
  stepRatio: 0.8,
  /* Schlupf in px, damit ein Sub-Pixel-Scrollstand noch als
     "am Anschlag" gilt */
  edgeSlackPx: 4
};

export const LIGHTBOX = {
  /* kürzere Wischbewegungen sind Taps und gehören den Buttons */
  swipeMinPx: 45
};
