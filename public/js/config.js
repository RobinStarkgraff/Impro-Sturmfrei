/* ------------------------------------------------------------
   Every tuned number in one place.

   What stands here is tuned against everything else and carries a name: no
   bare 4 in the middle of the logic whose reason you have to guess at.
   ------------------------------------------------------------ */

export const HEADER = {
  /* the bar turns opaque after this many pixels of scrolling */
  solidAfterPx: 40
};

export const REVEAL = {
  /* trigger a little before the bottom edge, so the movement still
     starts within view */
  rootMargin: "0px 0px -10% 0px",
  threshold: 0.1
};

export const CROSSFADE = {
  /* hold time per group photo; the fade itself takes 1.2s (CSS) */
  holdMs: 6000
};

export const SLIDER = {
  /* share of the slider width per arrow click */
  stepRatio: 0.8,
  /* slack in px, so a sub-pixel scroll position still counts as
     "at the end" */
  edgeSlackPx: 4
};

export const LIGHTBOX = {
  /* shorter swipes are taps and belong to the buttons */
  swipeMinPx: 45
};
