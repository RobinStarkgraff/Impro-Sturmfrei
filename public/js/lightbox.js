/* ------------------------------------------------------------
   Lightbox.

   In the markup every tile is a link to the original file. This module
   intercepts the click and shows the image in a dialog; without JS the
   link stays a link. The position within the group comes from the order in
   the DOM, not from an attribute on the markup.
   ------------------------------------------------------------ */

import { CSS_CLASS, hook } from "./classes.js";
import { LIGHTBOX } from "./config.js";
import { loadImage, warmImage } from "./images.js";

export function initLightbox() {
  const dialog = document.getElementById("lightbox");
  const image = document.getElementById("lightbox-img");
  const counter = document.getElementById("lightbox-counter");

  if (!dialog || !image || typeof dialog.showModal !== "function") return;

  const arrows = [
    dialog.querySelector(hook("lightboxPrev")),
    dialog.querySelector(hook("lightboxNext"))
  ].filter(Boolean);

  let group = [];
  let current = 0;
  let opener = null;

  const render = async () => {
    const item = group[current];
    if (!item) return;

    image.src = item.src;
    image.alt = item.alt;

    /* The counter is role="status" (see sections/lightbox.php): whatever is
       written into it is read out by a screen reader while paging through.
       "3 / 9" stays visible; the alt text is added invisibly, otherwise the
       announcement would be a number without a subject. */
    if (counter) {
      counter.textContent = "";

      const position = document.createElement("span");
      position.textContent = `${current + 1} / ${group.length}`;

      const subject = document.createElement("span");
      subject.className = "visually-hidden";
      subject.textContent = item.alt ? ` – ${item.alt}` : "";

      counter.append(position, subject);
    }

    arrows.forEach((button) => {
      button.hidden = group.length < 2;
    });

    // Warm up the neighbours, so ‹ and › respond immediately.
    [current - 1, current + 1].forEach((i) => {
      const neighbour = group[(i + group.length) % group.length];
      if (neighbour && neighbour !== item) warmImage(neighbour.src);
    });

    // These are the full-size originals, so on a slow line there is real
    // waiting involved. Better to show a spinner then than to leave the old
    // photo standing — that reads like a control with no effect. If the file
    // is cached, tick it off immediately: going via the event would let one
    // frame slip through at opacity 0 and flicker.
    const { cached, done } = loadImage(item.src);

    if (cached) {
      dialog.classList.remove(CSS_CLASS.loading);
      return;
    }

    dialog.classList.add(CSS_CLASS.loading);
    await done;

    // Arrived late and the view has long moved on: ignore it.
    if (group[current] === item) dialog.classList.remove(CSS_CLASS.loading);
  };

  const move = (delta) => {
    current = (current + delta + group.length) % group.length;
    render();
  };

  // One delegated listener covers every slider on the page.
  document.addEventListener("click", (event) => {
    // Middle click, Cmd or Ctrl click means "open in a new tab" — that is
    // left to the browser; the link does point at the original.
    if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
      return;
    }

    const slide = event.target.closest(".slider .slide");
    if (!slide) return;

    const slider = slide.closest(hook("slider"));
    if (!slider) return;

    const slides = Array.from(slider.querySelectorAll(".slide"));

    group = slides.map((link) => ({
      src: link.href,
      alt: link.querySelector("img")?.alt ?? ""
    }));

    current = Math.max(0, slides.indexOf(slide));
    opener = slide;

    event.preventDefault();
    render();
    dialog.showModal();
  });

  const buttonActions = [
    [hook("lightboxClose"), () => dialog.close()],
    [hook("lightboxPrev"), () => move(-1)],
    [hook("lightboxNext"), () => move(1)]
  ];

  dialog.addEventListener("click", (event) => {
    for (const [selector, run] of buttonActions) {
      if (event.target.closest(selector)) {
        run();
        return;
      }
    }

    // A click on the surrounding area (the stage, not the photo) closes.
    if (event.target === dialog || event.target.classList.contains("lightbox__stage")) {
      dialog.close();
    }
  });

  dialog.addEventListener("keydown", (event) => {
    if (event.key === "ArrowLeft") {
      event.preventDefault();
      move(-1);
    } else if (event.key === "ArrowRight") {
      event.preventDefault();
      move(1);
    }
  });

  /* --- Swiping ---
     On touch devices the slider arrows are hidden, so the lightbox is the
     way through the photos. A gesture is expected there. */
  let startX = null;

  dialog.addEventListener("touchstart", (event) => {
    startX = event.changedTouches[0].clientX;
  }, { passive: true });

  dialog.addEventListener("touchend", (event) => {
    if (startX === null) return;

    const dx = event.changedTouches[0].clientX - startX;
    startX = null;

    if (group.length > 1 && Math.abs(dx) > LIGHTBOX.swipeMinPx) move(dx < 0 ? 1 : -1);
  }, { passive: true });

  // Focus back on the tile that opened it.
  dialog.addEventListener("close", () => {
    if (opener) opener.focus();
    opener = null;
  });
}
