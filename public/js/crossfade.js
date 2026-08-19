/* ------------------------------------------------------------
   Überblendung der Gruppenfotos.

   Die Liste der Bilder steht nicht hier, sondern kommt als data-crossfade
   aus dem Markup — und damit aus content/site.json. So muss die
   Reihenfolge nicht zwischen Skript und HTML abgestimmt werden.
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
  let front = 0; // welche Ebene gerade sichtbar ist
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
    // Im Hintergrundtab gibt es nichts zu überblenden.
    if (document.hidden || loading) return;

    const next = (index + 1) % photos.length;
    loading = true;

    // Erst laden, dann tauschen — die Blende zeigt so nie eine leere Box.
    const { done } = loadImage(photos[next]);
    const ok = await done;

    loading = false;

    // Eine Datei, die nicht lädt, darf keine Endstation werden: den Zeiger
    // trotzdem weiterrücken, damit der nächste Takt das Bild danach nimmt.
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

  /* Der Takt läuft nur, solange der Abschnitt zu sehen ist. document.hidden
     deckt den Hintergrundtab ab, nicht den viel häufigeren Fall: sichtbarer
     Tab, aber weit an "Wer sind wir?" vorbeigescrollt — dort dekodierte der
     Browser bisher alle sechs Sekunden ein Foto für niemanden.

     Ohne IntersectionObserver läuft es wie vorher durch: lieber der alte
     Takt als gar keine Überblendung. */
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
