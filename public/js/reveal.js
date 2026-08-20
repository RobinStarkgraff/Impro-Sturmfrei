/* ------------------------------------------------------------
   Fade in while scrolling.
   ------------------------------------------------------------ */

import { CSS_CLASS, hook } from "./classes.js";
import { REVEAL } from "./config.js";
import { onMotionPreferenceChange, prefersReducedMotion } from "./motion.js";

export function initReveal() {
  const targets = Array.from(document.querySelectorAll(hook("reveal")));
  if (!targets.length) return;

  const showAll = () => targets.forEach((el) => el.classList.add(CSS_CLASS.revealed));

  // No observer, or reduced motion: simply show everything.
  if (prefersReducedMotion() || !("IntersectionObserver" in window)) {
    showAll();
    return;
  }

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add(CSS_CLASS.revealed);
      observer.unobserve(entry.target);
    });
  }, REVEAL);

  targets.forEach((el) => observer.observe(el));

  // Switched mid-visit: stop hiding anything that is still to come.
  onMotionPreferenceChange(() => {
    if (!prefersReducedMotion()) return;
    observer.disconnect();
    showAll();
  });
}
