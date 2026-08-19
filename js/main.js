/* ============================================================
   STURMFREI IMPRO — js/main.js

   Einstiegspunkt. Jedes Modul ist für sich lauffähig, prüft selbst, ob
   sein Markup vorhanden ist, und tut sonst nichts.

   Alles hier ist Progressive Enhancement: ohne JS bleibt die Seite
   vollständig lesbar, die Slider scrollen weiterhin, die Kacheln sind
   Links auf die Originalfotos, und nichts ist versteckt.
   ============================================================ */

import { CSS_CLASS } from "./classes.js";
import { initCrossfade } from "./crossfade.js";
import { initHeader } from "./header.js";
import { initLightbox } from "./lightbox.js";
import { initReveal } from "./reveal.js";
import { initSliders } from "./slider.js";

document.documentElement.classList.add(CSS_CLASS.js);

initHeader();
initReveal();
initCrossfade();
initSliders();
initLightbox();
