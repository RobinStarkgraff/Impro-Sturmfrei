/* ------------------------------------------------------------
   Show slider: arrows and edge gradients.

   The tiles themselves live in the markup — this module only builds the
   controls that would make no sense without JS. Their labels are read out
   to visitors, so they stay German.
   ------------------------------------------------------------ */

import { CSS_CLASS, hook } from "./classes.js";
import { SLIDER } from "./config.js";
import { scrollBehavior } from "./motion.js";

export function initSliders() {
  document.querySelectorAll(hook("slider")).forEach(setUpSlider);
}

function setUpSlider(slider) {
  const wrap = slider.closest(".slider-wrap");
  if (!wrap) return;

  const prev = addArrow(wrap, slider, "prev", "Zurück", "‹");
  const next = addArrow(wrap, slider, "next", "Weiter", "›");

  // Reading scrollWidth and clientWidth forces a layout. Neither changes
  // while scrolling, so only measure when they can change: on resize, and
  // when an image arrives late.
  let maxScroll = 0;

  const update = () => {
    const atStart = slider.scrollLeft <= SLIDER.edgeSlackPx;
    const atEnd = slider.scrollLeft >= maxScroll - SLIDER.edgeSlackPx;

    wrap.classList.toggle(CSS_CLASS.sliderStart, atStart);
    wrap.classList.toggle(CSS_CLASS.sliderEnd, atEnd);

    prev.disabled = atStart;
    next.disabled = atEnd;
  };

  const measure = () => {
    maxScroll = slider.scrollWidth - slider.clientWidth;
    update();
  };

  measure();
  slider.addEventListener("scroll", update, { passive: true });
  window.addEventListener("resize", measure);

  slider.querySelectorAll("img").forEach((img) => {
    if (!img.complete) img.addEventListener("load", measure, { once: true });
  });
}

function addArrow(wrap, slider, direction, label, glyph) {
  const button = document.createElement("button");
  button.type = "button";
  button.className = `icon-btn icon-btn--light slider-nav slider-nav--${direction}`;

  // textContent rather than innerHTML: these are fixed characters, but a
  // kit that glues markup together from strings will sooner or later be fed
  // data from outside.
  const icon = document.createElement("span");
  icon.setAttribute("aria-hidden", "true");
  icon.textContent = glyph;

  const name = document.createElement("span");
  name.className = "visually-hidden";
  name.textContent = label;

  button.append(icon, name);

  button.addEventListener("click", () => {
    const step = slider.clientWidth * SLIDER.stepRatio;
    slider.scrollBy({
      left: direction === "prev" ? -step : step,
      behavior: scrollBehavior()
    });
  });

  wrap.append(button);
  return button;
}
