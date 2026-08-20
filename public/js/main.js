/* ============================================================
   STURMFREI IMPRO — js/main.js

   Entry point. Every module runs on its own, checks for its own markup,
   and otherwise does nothing.

   All of this is progressive enhancement: without JS the page stays fully
   readable, the sliders still scroll, the tiles are links to the original
   photos, and nothing is hidden.
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
