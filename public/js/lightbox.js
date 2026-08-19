/* ------------------------------------------------------------
   Lightbox.

   Jede Kachel ist im Markup ein Link auf die Originaldatei. Dieses Modul
   fängt den Klick ab und zeigt das Bild im Dialog; ohne JS bleibt der
   Link ein Link. Die Position innerhalb der Gruppe kommt aus der
   Reihenfolge im DOM, nicht aus einem Attribut am Markup.
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

    /* Der Zähler ist role="status" (siehe sections/lightbox.php): was hier
       hineingeschrieben wird, liest ein Screenreader beim Blättern vor.
       Sichtbar bleibt "3 / 9"; der alt-Text kommt unsichtbar dazu, sonst
       wäre die Ansage eine Zahl ohne Gegenstand. */
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

    // Die Nachbarn vorwärmen, damit ‹ und › sofort reagieren.
    [current - 1, current + 1].forEach((i) => {
      const neighbour = group[(i + group.length) % group.length];
      if (neighbour && neighbour !== item) warmImage(neighbour.src);
    });

    // Das sind die Originale in voller Größe, auf einer langsamen Leitung
    // also echtes Warten. Dann lieber einen Spinner zeigen als das alte
    // Foto stehen lassen — das liest sich wie ein Bedienelement ohne
    // Wirkung. Liegt die Datei im Cache, sofort abhaken: über das Event
    // würde ein Frame bei Deckkraft 0 durchfallen und flackern.
    const { cached, done } = loadImage(item.src);

    if (cached) {
      dialog.classList.remove(CSS_CLASS.loading);
      return;
    }

    dialog.classList.add(CSS_CLASS.loading);
    await done;

    // Spät eingetroffen und der Blick ist längst weiter: ignorieren.
    if (group[current] === item) dialog.classList.remove(CSS_CLASS.loading);
  };

  const move = (delta) => {
    current = (current + delta + group.length) % group.length;
    render();
  };

  // Ein delegierter Listener deckt alle Slider der Seite ab.
  document.addEventListener("click", (event) => {
    // Mittelklick, Cmd- oder Ctrl-Klick heißt "in neuem Tab öffnen" — das
    // bleibt dem Browser überlassen, der Link zeigt ja auf das Original.
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

    // Klick auf die Fläche drumherum (die Bühne, nicht das Foto) schließt.
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

  /* --- Wischen ---
     Auf Touchgeräten sind die Slider-Pfeile ausgeblendet, dort ist die
     Lightbox der Weg durch die Fotos. Eine Gestik wird da erwartet. */
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

  // Fokus zurück auf die Kachel, die geöffnet hat.
  dialog.addEventListener("close", () => {
    if (opener) opener.focus();
    opener = null;
  });
}
