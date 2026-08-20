/* ------------------------------------------------------------
   prefers-reduced-motion — queried, not frozen.

   The value is read on every access, not once at load time: the CSS
   switches over immediately when the setting changes, and the JS should
   not lag behind it until the next reload.
   ------------------------------------------------------------ */

const query = window.matchMedia("(prefers-reduced-motion: reduce)");

export const prefersReducedMotion = () => query.matches;

/* Scroll behaviour for scrollBy/scrollTo. */
export const scrollBehavior = () => (query.matches ? "auto" : "smooth");

export function onMotionPreferenceChange(handler) {
  query.addEventListener("change", handler);
}
