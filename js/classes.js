/* ------------------------------------------------------------
   Der Vertrag zwischen JS, CSS und Markup.

   Zustandsklassen stehen auch in css/, die Haken auch in index.html: wer
   hier umbenennt, muss dort nachziehen. `make check` prüft beide Seiten.
   ------------------------------------------------------------ */

export const CSS_CLASS = {
  /* an <html>, sobald JS läuft — schaltet die Reveal-Animation frei */
  js: "js",
  /* [data-reveal] ist eingeblendet */
  revealed: "is-visible",
  /* Kopfleiste hat den Hero verlassen */
  scrolled: "is-scrolled",
  /* sichtbare Ebene der Foto-Überblendung */
  active: "is-active",
  /* Slider steht am linken bzw. rechten Anschlag */
  sliderStart: "is-start",
  sliderEnd: "is-end",
  /* Lightbox wartet noch auf die Originaldatei */
  loading: "is-loading"
};

/* Attribute im Markup, an denen die Module hängen. tools/build.mjs schreibt
   sie ins HTML, die Module suchen danach, `make check` vergleicht. */
export const DATA_HOOK = {
  reveal: "data-reveal",
  slider: "data-slider",
  crossfade: "data-crossfade",
  lightboxPrev: "data-lightbox-prev",
  lightboxNext: "data-lightbox-next",
  lightboxClose: "data-lightbox-close"
};

/** [data-reveal] → "[data-reveal]" */
export const hook = (name) => `[${DATA_HOOK[name]}]`;

/* dataset-Schlüssel, also data-nav-open im Markup. */
export const DATA_KEY = {
  navOpen: "navOpen"
};
