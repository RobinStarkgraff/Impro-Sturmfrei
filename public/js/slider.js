/* ------------------------------------------------------------
   Show-Slider: Pfeile und Randverläufe.

   Die Kacheln selbst stehen im Markup — dieses Modul baut nur noch die
   Bedienelemente, die ohne JS keinen Sinn hätten.
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

  // scrollWidth und clientWidth abzufragen erzwingt ein Layout. Beim Scrollen
  // ändern sich beide nicht, also nur messen, wenn sie sich ändern können:
  // bei resize und wenn ein Bild nachträglich eintrifft.
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

  // textContent statt innerHTML: hier sind es feste Zeichen, aber ein
  // Bausatz, der Markup aus Zeichenketten zusammenklebt, wird irgendwann
  // mit Daten von außen gefüttert.
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
