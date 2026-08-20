/* ------------------------------------------------------------
   Preloading images — for the crossfade, for the lightbox spinner, and to
   warm up neighbouring images.
   ------------------------------------------------------------ */

/**
 * Starts the download and says straight away whether the file was already
 * cached.
 *
 * `cached` matters for the lightbox: on a hit it must not show the spinner
 * only to take it away again on the next frame — that flickers on every
 * step back through a group of images.
 *
 * `done` always settles, on a cache hit as well as when the file is
 * missing; its value then says whether it worked.
 *
 * @param {string} src
 * @returns {{ cached: boolean, done: Promise<boolean> }}
 */
export function loadImage(src) {
  const image = new Image();
  image.src = src;

  const cached = image.complete;
  const done = cached
    ? Promise.resolve(image.naturalWidth > 0)
    : new Promise((resolve) => {
        image.onload = () => resolve(true);
        image.onerror = () => resolve(false);
      });

  return { cached, done };
}

/** Just kick it off; the result is of no interest. */
export function warmImage(src) {
  loadImage(src);
}
