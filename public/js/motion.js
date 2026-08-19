/* ------------------------------------------------------------
   prefers-reduced-motion — abgefragt, nicht eingefroren.

   Der Wert wird bei jedem Zugriff gelesen, nicht einmal beim Laden: das
   CSS schaltet sofort um, wenn die Einstellung sich ändert, und das JS
   soll dabei nicht bis zum nächsten Reload hinterherhängen.
   ------------------------------------------------------------ */

const query = window.matchMedia("(prefers-reduced-motion: reduce)");

export const prefersReducedMotion = () => query.matches;

/* Scrollverhalten für scrollBy/scrollTo. */
export const scrollBehavior = () => (query.matches ? "auto" : "smooth");

export function onMotionPreferenceChange(handler) {
  query.addEventListener("change", handler);
}
