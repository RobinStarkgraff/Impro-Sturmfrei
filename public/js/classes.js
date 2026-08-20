/* ------------------------------------------------------------
   The contract between JS, CSS and markup.

   State classes also appear in public/css/, the hooks in sections/: rename
   one here and you have to follow up there. `make check` verifies both
   sides.
   ------------------------------------------------------------ */

export const CSS_CLASS = {
  /* on <html> as soon as JS runs — unlocks the reveal animation */
  js: "js",
  /* [data-reveal] has faded in */
  revealed: "is-visible",
  /* the header bar has left the hero */
  scrolled: "is-scrolled",
  /* visible layer of the photo crossfade */
  active: "is-active",
  /* slider sits at its left or right end */
  sliderStart: "is-start",
  sliderEnd: "is-end",
  /* lightbox is still waiting for the original file */
  loading: "is-loading"
};

/* Attributes in the markup that the modules hook onto. The sections in
   sections/ write them into the HTML, the modules look for them, `make
   check` compares. */
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

/* dataset keys, i.e. data-nav-open in the markup. */
export const DATA_KEY = {
  navOpen: "navOpen"
};
