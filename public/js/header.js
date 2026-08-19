/* ------------------------------------------------------------
   Kopfleiste: Scrollzustand und Mobilmenü.
   ------------------------------------------------------------ */

import { CSS_CLASS, DATA_KEY } from "./classes.js";
import { HEADER } from "./config.js";

export function initHeader() {
  const header = document.getElementById("site-header");
  const toggle = document.getElementById("nav-toggle");
  const nav = document.getElementById("site-nav");

  if (header) bindScrollState(header);
  if (toggle && nav) bindMobileMenu(toggle, nav);
}

function bindScrollState(header) {
  const onScroll = () => {
    header.classList.toggle(CSS_CLASS.scrolled, window.scrollY > HEADER.solidAfterPx);
  };

  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });
}

function bindMobileMenu(toggle, nav) {
  // Das offene Menü legt sich über die Seite, also geht alles dahinter auf
  // inert: damit bleiben Tab und Screenreader im Menü, ohne handgebauten
  // Focus-Trap. Browser ohne inert ignorieren die Eigenschaft einfach.
  const behind = [
    document.getElementById("main"),
    document.querySelector(".site-footer")
  ].filter(Boolean);

  const isOpen = () => toggle.getAttribute("aria-expanded") === "true";

  const setOpen = (open) => {
    toggle.setAttribute("aria-expanded", String(open));
    document.body.dataset[DATA_KEY.navOpen] = String(open);
    behind.forEach((el) => {
      el.inert = open;
    });
  };

  setOpen(false);

  toggle.addEventListener("click", () => setOpen(!isOpen()));

  // Nach der Zielwahl zu, und auf Escape.
  nav.addEventListener("click", (event) => {
    if (event.target.closest("a")) setOpen(false);
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && isOpen()) {
      setOpen(false);
      toggle.focus();
    }
  });
}
