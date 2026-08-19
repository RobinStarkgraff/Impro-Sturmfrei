/* ------------------------------------------------------------
   Bilder vorladen — für die Überblendung, für den Spinner der Lightbox
   und zum Vorwärmen der Nachbarbilder.
   ------------------------------------------------------------ */

/**
 * Startet den Download und sagt sofort, ob die Datei schon im Cache lag.
 *
 * `cached` ist wichtig für die Lightbox: bei einem Treffer darf sie den
 * Spinner nicht erst zeigen und im nächsten Frame wieder wegnehmen —
 * das flackert bei jedem Schritt zurück durch die Bildergruppe.
 *
 * `done` erfüllt sich immer, auch bei einem Cache-Treffer und auch wenn
 * die Datei fehlt; der Wert sagt dann, ob es geklappt hat.
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

/** Nur anstoßen, Ergebnis interessiert nicht. */
export function warmImage(src) {
  loadImage(src);
}
