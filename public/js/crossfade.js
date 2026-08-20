/* ------------------------------------------------------------
   Crossfade of the group photos.

   The list of images does not live here; it arrives as data-crossfade from
   the markup — and thereby from content/site.json. That way the order does
   not have to be kept in sync between script and HTML.
   ------------------------------------------------------------ */

import { CSS_CLASS, DATA_HOOK, hook } from "./classes.js";
import { CROSSFADE } from "./config.js";
import { loadImage } from "./images.js";
import { onMotionPreferenceChange, prefersReducedMotion } from "./motion.js";

export function initCrossfade() {
  const media = document.querySelector(hook("crossfade"));
  if (!media) return;

  const layers = Array.from(media.querySelectorAll("img"));
  const photos = readPhotos(media);
  if (layers.length < 2 || photos.length < 2) return;

  let index = 0;
  let front = 0; // which layer is currently visible
  let loading = false;
  let timer = null;

  const swap = (next) => {
    const back = layers[1 - front];

    back.src = photos[next];
    back.classList.add(CSS_CLASS.active);
    layers[front].classList.remove(CSS_CLASS.active);

    front = 1 - front;
    index = next;
  };

  const step = async () => {
    // Nothing to crossfade in a background tab.
    if (document.hidden || loading) return;

    const next = (index + 1) % photos.length;
    loading = true;

    // Load first, then swap — that way the fade never shows an empty box.
    const { done } = loadImage(photos[next]);
    const ok = await done;

    loading = false;

    // A file that fails to load must not become a dead end: advance the
    // pointer anyway, so the next tick takes the image after it.
    if (ok) swap(next);
    else index = next;
  };

  const start = () => {
    if (timer === null && !prefersReducedMotion()) {
      timer = setInterval(step, CROSSFADE.holdMs);
    }
  };

  const stop = () => {
    if (timer !== null) {
      clearInterval(timer);
      timer = null;
    }
  };

  onMotionPreferenceChange(() => (prefersReducedMotion() ? stop() : start()));

  /* The tick only runs while the section is on screen. document.hidden
     covers the background tab, not the far more common case: visible tab,
     but scrolled well past "Wer sind wir?" — where the browser used to
     decode a photo for nobody every six seconds.

     Without IntersectionObserver it runs straight through as before: better
     the old tick than no crossfade at all. */
  if ("IntersectionObserver" in window) {
    new IntersectionObserver(
      ([entry]) => (entry.isIntersecting ? start() : stop()),
      { rootMargin: "200px" }
    ).observe(media);
  } else {
    start();
  }
}

function readPhotos(media) {
  try {
    const photos = JSON.parse(media.getAttribute(DATA_HOOK.crossfade));
    return Array.isArray(photos) ? photos : [];
  } catch {
    return [];
  }
}
