#!/usr/bin/env node
/* ============================================================
   Baut die Seiten und style.css.

   Jede Angabe, die mehr als einmal vorkommt, steht genau einmal in
   content/ und wird hier ausgegeben — warum, steht im README unter
   "Aufbau".

   Quellen                          Ergebnis (alles unter public/)
   ------------------------------   -----------------------------
   content/site.json                index.html
   content/shows.json               termine/  buchen/  archiv/  kontakt/
   content/booking.json             impressum/  datenschutz/
   content/legal.json               style.css
   css/*.css (in Dateinamenfolge)   js/*.js (wortgleich kopiert)
   js/*.js                          fonts/, images/ (gespiegelt)
   fonts/, images/                  robots.txt, sitemap.xml (nur mit Domain)

   Geschrieben wird ausschließlich nach public/, und public/ ist vollständig
   erzeugt: es liegt nicht im Repo, sondern entsteht hier — lokal wie auf dem
   Server. Die Pfade unten sind relativ zu public/, nicht zum Projektordner.

   Aufruf:  node tools/build.mjs   (oder: make build)

   Eine Seite ist ein Eintrag in PAGES weiter unten: Ordnername, Titel,
   Beschreibung, und welche Abschnitte in welcher Reihenfolge darin
   stehen. Alles andere — Kopfleiste, Footer, <head>, JSON-LD — entsteht
   daraus.

   Pfade sind relativ, nicht absolut: jede Seite bekommt ihr `base`
   ("" auf der Startseite, "../" in einem Unterordner). So läuft die Seite
   auch in einem Unterverzeichnis, nicht nur an der Wurzel einer Domain.

   render() gibt die Dateien nur zurück, geschrieben wird ausschließlich
   beim direkten Aufruf: so kann tools/check.mjs dasselbe Ergebnis
   erzeugen und vergleichen, ohne etwas zu überschreiben.
   ============================================================ */

import { cpSync, writeFileSync, mkdirSync, readdirSync, rmSync, existsSync } from "node:fs";
import { dirname, join } from "node:path";

import { dateDE, isMain, out, outName, photoCount, read, readJson, root, walk } from "./lib.mjs";

const site = readJson("content", "site.json");
const shows = readJson("content", "shows.json");
const booking = readJson("content", "booking.json");
const legal = readJson("content", "legal.json");

/* ------------------------------------------------------------
   Werkzeug
   ------------------------------------------------------------ */

const HTML_ESCAPES = { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" };

/** Für Text und Attributwerte gleichermaßen. */
const esc = (value) => String(value).replace(/[&<>"']/g, (c) => HTML_ESCAPES[c]);

/** Zeilen zusammensetzen; null heißt "entfällt", "" bleibt Leerzeile. */
const lines = (...items) => items.flat().filter((line) => line !== null).join("\n");

/** Jede Zeile um pad einrücken. */
function indent(text, pad) {
  return text.split("\n").map((line) => (line ? pad + line : line)).join("\n");
}

/** Attribute für einen Link, der die Seite verlässt. */
const ext = (url) => `href="${esc(url)}" target="_blank" rel="noopener"`;

/**
 * mailto mit vorbereitetem Betreff und Text.
 *
 * URLSearchParams codiert das Leerzeichen als "+" — in einem mailto: ist
 * das ein echtes Plus und landet so im Betreff. Deshalb zurück auf %20.
 */
function mailto(subject, body) {
  const params = new URLSearchParams();
  if (subject) params.set("subject", subject);
  if (body) params.set("body", body);

  const query = params.toString().replace(/\+/g, "%20");
  return `mailto:${site.email}${query ? `?${query}` : ""}`;
}

/* ------------------------------------------------------------
   Pfade

   Alles läuft über base: Datei aus dem Projekt (Foto, style.css) und
   Verweis auf eine andere Seite. Wer hier "images/..." direkt ins Markup
   schreibt, baut einen Link, der auf jeder Unterseite ins Leere zeigt.
   ------------------------------------------------------------ */

/** Datei aus dem Projekt, von dieser Seite aus gesehen. */
const asset = (ctx, path) => `${ctx.base}${path}`;

/** Andere Seite, von dieser Seite aus gesehen. */
const link = (ctx, slug) => (slug === "index" ? ctx.base || "./" : `${ctx.base}${slug}/`);

/** Was aus einem Ordnernamen als Dateiname wird. */
const fileFor = (slug) => (slug === "index" ? "index.html" : `${slug}/index.html`);

/** Wie tief die Seite liegt — Startseite an der Wurzel, alles andere eine Ebene tiefer. */
const baseFor = (slug) => (slug === "index" ? "" : "../");

/** Absolute URL, oder null solange die Domain fehlt. */
function absolute(path) {
  if (!site.url) return null;
  return `${site.url.replace(/\/+$/, "")}/${String(path).replace(/^\/+/, "")}`;
}

/** Kanonische URL einer Seite, oder null solange die Domain fehlt. */
const canonicalFor = (slug) => absolute(slug === "index" ? "" : `${slug}/`);

const ogImage = site.ogImage ? absolute(site.ogImage) : null;

/* ------------------------------------------------------------
   Fehlende Angaben

   Impressum und Datenschutz brauchen Angaben, die niemand erfinden
   darf. Fehlt eine, steht an ihrer Stelle sichtbar, dass sie fehlt —
   und `make check` meldet es. Eine Seite mit erfundener Anschrift wäre
   schlimmer als eine, die sagt, was noch fehlt.
   ------------------------------------------------------------ */

const missing = (what) =>
  `<mark class="todo">[ ${esc(what)} — noch einzutragen in content/legal.json ]</mark>`;

/** Wert oder sichtbarer Platzhalter. */
const orMissing = (value, what) => (value ? esc(value) : missing(what));

/* ------------------------------------------------------------
   JSON-LD

   Die Gruppe steht auf jeder Seite (sie ist überall dieselbe Entität),
   die Events nur dort, wo sie auch sichtbar sind: die nächste Show auf
   Start und Termine, die vergangenen im Archiv.
   ------------------------------------------------------------ */

/**
 * Die Anschrift der Gruppe.
 *
 * Standardmäßig nur Ort und Land. Die vollständige Anschrift steht im
 * Impressum, weil das Gesetz sie dort verlangt — sie zusätzlich als
 * strukturierte Daten auszugeben, ist eine andere Entscheidung: Google
 * könnte sie dann als Anschrift der Gruppe in Karten und Wissenspanel
 * anzeigen, und es ist eine Privatanschrift, keine Spielstätte.
 *
 * Wer das will, setzt in content/legal.json "publishAddressInSchema": true.
 */
function groupAddress() {
  const { street, postalCode, city } = legal.impressum;

  const address = {
    "@type": "PostalAddress",
    addressLocality: city ?? site.city,
    addressCountry: "DE"
  };

  if (!legal.impressum.publishAddressInSchema) return address;

  if (street) address.streetAddress = street;
  if (postalCode) address.postalCode = postalCode;

  return address;
}

/**
 * Die Anschrift einer Spielstätte.
 *
 * Nur Ort und Land: content/shows.json kennt den Namen des Hauses, nicht
 * dessen Straße. Hier die Anschrift der Gruppe einzusetzen wäre bequem und
 * falsch — sie würde behaupten, das Kulturschloss Wandsbek liege in der
 * Schlettstadter Straße.
 */
const venueAddress = () => ({
  "@type": "PostalAddress",
  addressLocality: site.city,
  addressCountry: "DE"
});

function theaterEvent(show) {
  const performer = { "@type": "TheaterGroup", name: site.brand.name };

  const event = {
    "@type": "TheaterEvent",
    name: show.title,
    startDate: show.time ? `${show.date}T${show.time}` : show.date,
    eventStatus: "https://schema.org/EventScheduled",
    eventAttendanceMode: "https://schema.org/OfflineEventAttendanceMode",
    performer,
    organizer: performer,
    location: {
      "@type": "Place",
      name: show.venue,
      address: venueAddress()
    }
  };

  if (ogImage) event.image = ogImage;

  if (show.ticketUrl) {
    event.offers = {
      "@type": "Offer",
      url: show.ticketUrl,
      availability: "https://schema.org/InStock"
    };
  }

  return event;
}

function theaterGroup() {
  const group = {
    "@type": "TheaterGroup",
    name: site.brand.name,
    alternateName: site.brand.alternateName,
    description: site.meta.schemaDescription,
    email: site.email,
    address: groupAddress(),
    sameAs: Object.values(site.links).map((entry) => entry.url)
  };

  const home = canonicalFor("index");
  if (home) group.url = home;
  if (ogImage) group.image = ogImage;
  if (legal.impressum.phone) group.telephone = legal.impressum.phone;

  return group;
}

/** Brotkrumen — sagt Google, wo die Unterseite in der Seite hängt. */
function breadcrumbs(page) {
  if (page.slug === "index" || !site.url) return null;

  return {
    "@type": "BreadcrumbList",
    itemListElement: [
      { "@type": "ListItem", position: 1, name: "Start", item: canonicalFor("index") },
      { "@type": "ListItem", position: 2, name: page.navLabel ?? page.title, item: canonicalFor(page.slug) }
    ]
  };
}

function structuredData(page) {
  const graph = [theaterGroup()];

  if (page.schema?.includes("upcoming") && shows.upcoming) graph.push(theaterEvent(shows.upcoming));
  if (page.schema?.includes("past")) graph.push(...shows.past.map(theaterEvent));

  const crumbs = breadcrumbs(page);
  if (crumbs) graph.push(crumbs);

  return { "@context": "https://schema.org", "@graph": graph };
}

/* ------------------------------------------------------------
   <head>
   ------------------------------------------------------------ */

function head(page, ctx) {
  const { meta, brand } = site;
  const canonical = canonicalFor(page.slug);

  const domainNote = canonical
    ? null
    : `  <!-- og:url, canonical, robots.txt und sitemap.xml entstehen automatisch,
       sobald in content/site.json "url" auf die endgültige Domain zeigt.
       Für die Social-Karte zusätzlich "ogImage" setzen (1200x630). -->`;

  // Nur die Startseite zeigt das Hero-Foto sofort; auf den Unterseiten
  // wäre ein Preload dafür verschwendete Bandbreite.
  const heroPreload = page.slug === "index"
    ? [``, `  <link rel="preload" as="image" href="${esc(asset(ctx, site.hero.photo))}" fetchpriority="high"/>`]
    : null;

  return lines(
    `  <meta charset="UTF-8"/>`,
    `  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>`,
    `  <title>${esc(page.title)}</title>`,
    `  <meta name="description" content="${esc(page.description)}"/>`,
    `  <meta name="theme-color" content="${esc(meta.themeColor)}"/>`,
    page.noindex ? `  <meta name="robots" content="noindex, follow"/>` : null,
    ``,
    domainNote,
    canonical ? `  <link rel="canonical" href="${esc(canonical)}"/>` : null,
    ``,
    `  <meta property="og:type" content="website"/>`,
    `  <meta property="og:site_name" content="${esc(brand.alternateName)}"/>`,
    `  <meta property="og:locale" content="${esc(meta.locale)}"/>`,
    `  <meta property="og:title" content="${esc(page.title)}"/>`,
    `  <meta property="og:description" content="${esc(page.ogDescription ?? page.description)}"/>`,
    canonical ? `  <meta property="og:url" content="${esc(canonical)}"/>` : null,
    ogImage ? `  <meta property="og:image" content="${esc(ogImage)}"/>` : null,
    ogImage ? `  <meta property="og:image:width" content="1200"/>` : null,
    ogImage ? `  <meta property="og:image:height" content="630"/>` : null,
    // Ohne Bild bleibt es bei der kleinen Karte: eine große leere ist schlechter.
    `  <meta name="twitter:card" content="${ogImage ? "summary_large_image" : "summary"}"/>`,
    ``,
    `  <link rel="icon" href="${esc(asset(ctx, brand.logo))}"/>`,
    `  <link rel="apple-touch-icon" href="${esc(asset(ctx, brand.logo))}"/>`,
    ``,
    `  <!-- Fonts: ein Display-Schnitt für Headlines, ein Grotesk für den Text.`,
    `       Selbst gehostet aus fonts/ — @font-face steht in css/00-fonts.css.`,
    `       Bitte nichts wieder auf fonts.googleapis.com umstellen: das überträgt`,
    `       die IP jedes Besuchers an Google. Anton wird vorgeladen, weil es in der`,
    `       H1 jeder Seite direkt über dem Falz steckt. -->`,
    `  <link rel="preload" as="font" type="font/woff2"`,
    `        href="${esc(asset(ctx, "fonts/anton-v27-latin_latin-ext-regular.woff2"))}" crossorigin/>`,
    heroPreload,
    `  <link rel="stylesheet" href="${esc(asset(ctx, "style.css"))}"/>`,
    ``,
    `  <!-- Strukturierte Daten: damit Google die Gruppe als Entität und die Shows`,
    `       als Events versteht (Voraussetzung für Event-Rich-Results). Erzeugt aus`,
    `       content/shows.json — neue Show dort eintragen, nicht hier.`,
    `       Die Spielernamen stehen bewusst nicht als Person-Entitäten drin —`,
    `       im Sichtbaren reichen die Vornamen, maschinenlesbar müssen sie nicht. -->`,
    `  <script type="application/ld+json">`,
    indent(JSON.stringify(structuredData(page), null, 2), "  "),
    `  </script>`
  );
}

/* ------------------------------------------------------------
   Navigation

   Beschriftung und Ziel stehen einmal in content/site.json. Der Footer
   zeigt alle Punkte, die Kopfleiste die mit "inHeader". aria-current
   markiert die Seite, auf der man gerade steht — das ist der Grund, aus
   dem die Navigation überhaupt weiß, welche Seite gerade gebaut wird.
   ------------------------------------------------------------ */

function navItems(ctx, pad, { headerOnly = false, items = site.nav } = {}) {
  return items
    .filter((item) => !headerOnly || item.inHeader)
    .map((item) => {
      const here = item.page === ctx.slug;
      return `${pad}<li><a href="${esc(link(ctx, item.page))}"${
        here ? ` class="is-here" aria-current="page"` : ""
      }>${esc(item.label)}</a></li>`;
    })
    .join("\n");
}

/* ------------------------------------------------------------
   Kopfleiste und Footer
   ------------------------------------------------------------ */

function header(ctx) {
  return `<header class="site-header" id="site-header">
  <div class="wrap site-header__inner">

    <a class="brand" href="${esc(link(ctx, "index"))}">
      <img class="brand__mark" src="${esc(asset(ctx, site.brand.logo))}" alt="" width="52" height="52"/>
      ${esc(site.brand.name)}
    </a>

    <button class="nav-toggle"
            id="nav-toggle"
            type="button"
            aria-expanded="false"
            aria-controls="site-nav">
      <span class="nav-toggle__bars"></span>
      <span class="visually-hidden">Menü</span>
    </button>

    <nav class="site-nav" id="site-nav" aria-label="Hauptnavigation">
      <ul class="nav-list">
${navItems(ctx, "        ", { headerOnly: true })}
      </ul>
    </nav>

  </div>
</header>`;
}

function footer(ctx) {
  const social = Object.values(site.links)
    .map((entry) => `          <li><a ${ext(entry.url)}>${esc(entry.name)}</a></li>`)
    .join("\n");

  const legalLinks = site.legalNav
    .map((item) => `      <li><a href="${esc(link(ctx, item.page))}">${esc(item.label)}</a></li>`)
    .join("\n");

  return `<!-- ================= FOOTER ================= -->
<footer class="site-footer">
  <div class="wrap">

    <div class="footer-grid">

      <div class="footer-col">
        <p class="footer-brand">${esc(site.brand.name)}</p>
        <p class="meta">
          Improvisationstheater aus ${esc(site.city)}.<br>
          Kein Drehbuch, kein Plan B.
        </p>
      </div>

      <!-- Die Linklisten sind <nav> mit Namen: so tauchen sie in der
           Landmark-Übersicht von Screenreadern auf. -->
      <nav class="footer-col" aria-labelledby="footer-site-heading">
        <p class="footer-col__title" id="footer-site-heading">Seite</p>
        <ul>
${navItems(ctx, "          ")}
        </ul>
      </nav>

      <nav class="footer-col" aria-labelledby="footer-social-heading">
        <p class="footer-col__title" id="footer-social-heading">Folgt uns</p>
        <ul>
${social}
        </ul>
      </nav>

      <div class="footer-col">
        <p class="footer-col__title">Kontakt</p>
        <ul>
          <li><a href="mailto:${esc(site.email)}">${esc(site.email)}</a></li>
          <li><a href="${esc(link(ctx, "buchen"))}">Sturmfrei buchen</a></li>
        </ul>
      </div>

    </div>

    <div class="footer-bottom">
      <p class="footer-bottom__note">
        © ${site.copyrightYear} ${esc(site.brand.name)}. Alles improvisiert, nichts garantiert.
      </p>
      <nav aria-label="Rechtliches">
        <ul class="footer-legal">
${legalLinks}
        </ul>
      </nav>
    </div>

  </div>
</footer>`;
}

/* ------------------------------------------------------------
   Bausteine, die mehrere Seiten teilen
   ------------------------------------------------------------ */

/**
 * Dunkles Band am Kopf jeder Unterseite.
 *
 * Nicht nur Gestaltung: die Kopfleiste ist über dem Hero durchsichtig und
 * setzt ihren Text auf Weiß. Ohne dunkle Pixel darunter stünde weiß auf
 * creme — deshalb hat jede Seite ohne Hero dieses Band.
 */
function pageHero({ eyebrow, title, lead = null, actions = null }) {
  return `  <!-- ================= SEITENKOPF ================= -->
  <section class="page-hero">
    <div class="wrap wrap--prose">
      <p class="eyebrow">${esc(eyebrow)}</p>
      <h1>${title}</h1>
${lead ? `      <p class="lead page-hero__lead">${lead}</p>` : ""}
${actions ? indent(actions, "      ") : ""}
    </div>
  </section>`;
}

/** Karten mit den Kanälen, auf denen es weitergeht. */
function followCards(pad) {
  return Object.values(site.links)
    .map((entry) => `${pad}<a class="follow-card" ${ext(entry.url)}>
${pad}  <span class="follow-card__name">${esc(entry.name)}</span>
${pad}  <span class="follow-card__hint">${esc(entry.hint)}</span>
${pad}  <span class="follow-card__arrow" aria-hidden="true">→</span>
${pad}</a>`)
    .join("\n\n");
}

function follow() {
  return `  <!-- ================= FOLLOW ================= -->
  <section class="section" id="follow">
    <div class="wrap">

      <div class="section-head section-head--center" data-reveal>
        <p class="eyebrow">Bleibt dran</p>
        <h2>Folgt uns</h2>
        <p class="lead">
          Neue Termine stehen zuerst hier — und dann sofort auf Instagram.
        </p>
      </div>

      <div class="follow-grid" data-reveal>

${followCards("        ")}

      </div>

    </div>
  </section>`;
}

/* ------------------------------------------------------------
   Abschnitte: Startseite
   ------------------------------------------------------------ */

function hero(ctx) {
  const { photo, alt } = site.hero;

  return `  <!-- ================= HERO ================= -->
  <section class="section hero" id="top">

    <!-- Dasselbe Foto, stark weichgezeichnet: liefert die Bühnenfarben als
         Atmosphäre, ohne dass die Auflösung eine Rolle spielt. -->
    <img class="hero__backdrop" src="${esc(asset(ctx, photo))}" alt="" aria-hidden="true"/>

    <div class="wrap hero__inner">

      <div class="hero__content">

        <p class="eyebrow">${esc(site.brand.tagline)}</p>

        <h1>
          Kein Drehbuch.<br>
          Kein Plan B.<br>
          <span class="accent">Nur dieser Abend.</span>
        </h1>

        <p class="hero__text">
          Kommt zu den Sturmfrei-Impro-Shows und erlebt einen Abend mit klarer Aussicht auf beste
          Unterhaltung. Alles entsteht auf der Bühne und im Moment. Ihr gebt den Impuls — wir machen
          daraus unvergessliche Geschichten.
        </p>

        <div class="btn-row">
          <a class="btn btn--primary" href="${esc(link(ctx, "termine"))}">Termine ansehen</a>
          <a class="btn btn--on-dark" href="${esc(link(ctx, "buchen"))}">Uns buchen</a>
        </div>

        <a class="scroll-cue" href="#naechste-show">
          Vorhang auf
          <span class="scroll-cue__arrow" aria-hidden="true">↓</span>
        </a>

      </div>

      <figure class="hero__figure">
        <img class="hero__photo"
             src="${esc(asset(ctx, photo))}"
             alt="${esc(alt)}"
             fetchpriority="high"/>
      </figure>

    </div>

  </section>`;
}

/**
 * Kurzer Blick auf den nächsten Termin — die ganze Liste steht auf
 * /termine/. Hier reicht: steht einer an oder nicht.
 */
function nextShowTeaser(ctx) {
  const body = shows.upcoming
    ? lines(
        `        <p class="date-line">${esc(whenLine(shows.upcoming))}</p>`,
        ``,
        `        <h2>${esc(shows.upcoming.title)}</h2>`,
        ``,
        `        <p class="lead">${esc(shows.upcoming.venue)} · ${esc(site.city)}</p>`,
        ``,
        `        <div class="btn-row btn-row--center">`,
        `          <a class="btn btn--primary" ${ext(shows.upcoming.ticketUrl ?? site.links.eventbrite.url)}>Tickets sichern</a>`,
        `          <a class="btn btn--ghost" href="${esc(link(ctx, "termine"))}">Alle Termine</a>`,
        `        </div>`
      )
    : `        <h2>Bald kommt wieder was!</h2>

        <p class="lead">
          Der nächste Termin steht noch nicht — aber er kommt. Wo er zuerst auftaucht,
          steht auf der Termin-Seite.
        </p>

        <div class="btn-row btn-row--center">
          <a class="btn btn--primary" href="${esc(link(ctx, "termine"))}">Zu den Terminen</a>
          <a class="btn btn--ghost" ${ext(site.links.instagram.url)}>Auf Instagram folgen</a>
        </div>`;

  return `  <!-- ================= NÄCHSTE SHOW (Teaser) ================= -->
  <section class="section section--tight" id="naechste-show">
    <div class="wrap wrap--prose">

      <div class="next-show" data-reveal>

        <span class="pill">
          <span class="pill__dot" aria-hidden="true"></span>
          Nächste Show
        </span>

${body}

      </div>

    </div>
  </section>`;
}

function impro(ctx) {
  return `  <!-- ================= IMPRO ================= -->
  <section class="section" id="impro">
    <div class="wrap wrap--prose">

      <div class="section-head" data-reveal>
        <p class="eyebrow">Für alle, die neu dabei sind</p>
        <h2>Was ist Impro?</h2>
      </div>

      <div data-reveal>
        <p class="lead">
          Bei einer Improshow entsteht alles live auf der Bühne. Das Publikum gibt die Ideen –
          wir machen daraus Szenen, Geschichten und Chaos, in einem humorvollen, auflockernden Style.
        </p>

        <p>
          PS: Auf den Plätzen seid ihr bei uns sicher und werdet nicht unfreiwillig ausgefragt oder
          auf die Bühne geschleppt. Für freiwillige, abenteuerlustige Zuschauer gibt es jedoch bei den
          meisten Shows die Möglichkeit, eine kurze Impro-Szene mit uns gemeinsam zu spielen.
        </p>

        <p class="meta">
          Wie das aussieht, zeigt der <a href="${esc(link(ctx, "archiv"))}">Rückblick auf vergangene Shows</a>.
        </p>
      </div>

    </div>
  </section>`;
}

function about(ctx) {
  const { crossfade, photoAlt } = site.about;
  const layers = crossfade.map((path) => asset(ctx, path));

  return `  <!-- ================= ABOUT ================= -->
  <section class="section" id="about">
    <div class="wrap">

      <div class="section-head" data-reveal>
        <p class="eyebrow">Das Ensemble</p>
        <h2>Wer sind wir?</h2>
      </div>

      <div class="about-layout">

        <!-- Zwei gestapelte Ebenen: die Überblendung zeigt so nie eine leere Box.
             Die Bilderfolge steht in content/site.json und wird von
             js/crossfade.js aus data-crossfade gelesen. -->
        <div class="about-media"
             data-reveal
             data-crossfade="${esc(JSON.stringify(layers))}">
          <img class="about-media__layer is-active"
               src="${esc(layers[0])}"
               alt="${esc(photoAlt)}"
               loading="lazy"/>
          <img class="about-media__layer" src="" alt="" aria-hidden="true"/>
        </div>

        <div data-reveal>
          <p class="about-statement">
            Frei im Kopf.<br>
            Frei im Spiel.<br>
            <span class="accent">Sturmfrei auf der Bühne!</span>
          </p>

          <p class="lead">
            Voller Impro-Leidenschaft, Spielfreude und mit Herz bringen wir jede Bühne in Bewegung.
          </p>

          <div class="btn-row">
            <a class="btn btn--primary" href="${esc(link(ctx, "buchen"))}">Sturmfrei buchen</a>
            <a class="btn btn--ghost" href="${esc(link(ctx, "kontakt"))}">Kontakt</a>
          </div>
        </div>

      </div>

    </div>
  </section>`;
}

/* ------------------------------------------------------------
   Abschnitte: Termine
   ------------------------------------------------------------ */

const whenLine = (show) => (show.time ? `${dateDE(show.date)} · ${show.time} Uhr` : dateDE(show.date));

function upcomingCard(ctx) {
  const show = shows.upcoming;
  const tickets = show.ticketUrl ?? site.links.eventbrite.url;

  return lines(
    `      <div class="next-show" data-reveal>`,
    ``,
    `        <span class="pill">`,
    `          <span class="pill__dot" aria-hidden="true"></span>`,
    `          Nächste Show`,
    `        </span>`,
    ``,
    `        <p class="date-line">${esc(whenLine(show))}</p>`,
    ``,
    `        <h2>${esc(show.title)}</h2>`,
    ``,
    `        <p class="lead">${esc(show.venue)} · ${esc(site.city)}</p>`,
    show.note ? [``, `        <p class="meta">${esc(show.note)}</p>`] : null,
    ``,
    `        <div class="btn-row btn-row--center">`,
    `          <a class="btn btn--primary" ${ext(tickets)}>Tickets sichern</a>`,
    `          <a class="btn btn--ghost" ${ext(site.links.instagram.url)}>Auf Instagram folgen</a>`,
    `        </div>`,
    ``,
    `      </div>`
  );
}

function noUpcomingCard() {
  return `      <div class="next-show" data-reveal>

        <span class="pill">
          <span class="pill__dot" aria-hidden="true"></span>
          Nächste Show
        </span>

        <h2>Bald kommt wieder was!</h2>

        <p class="lead">
          Der nächste Termin steht noch nicht fest. Sobald er steht, taucht er hier auf —
          und gleichzeitig auf den drei Kanälen darunter.
        </p>

        <div class="btn-row btn-row--center">
          <a class="btn btn--primary" ${ext(site.links.instagram.url)}>Auf Instagram folgen</a>
          <a class="btn btn--ghost" ${ext(site.links.eventbrite.url)}>Eventbrite ansehen</a>
        </div>

      </div>`;
}

function dates(ctx) {
  return `  <!-- ================= TERMINE ================= -->
  <section class="section" id="termine">
    <div class="wrap wrap--prose">

${shows.upcoming ? upcomingCard(ctx) : noUpcomingCard()}

      <div class="section-head" data-reveal>
        <h2>Wo die Termine zuerst stehen</h2>
        <p class="lead">
          Wir spielen unregelmäßig — es lohnt sich also, einen der drei Kanäle im Blick zu
          behalten. Auf Eventbrite gibt es die Tickets, auf MeetUp die Termine samt Zusagen,
          auf Instagram alles andere.
        </p>
      </div>

      <div class="follow-grid follow-grid--stack" data-reveal>

${followCards("        ")}

      </div>

      <div class="section-head" data-reveal>
        <h2>Ihr habt einen Anlass?</h2>
        <p class="lead">
          Neben den öffentlichen Shows spielen wir auch dort, wo ihr feiert — Firmenfeier,
          Geburtstag, Vereinsfest. Was das heißt, steht auf
          <a href="${esc(link(ctx, "buchen"))}">Buchen</a>.
        </p>

        <div class="btn-row">
          <a class="btn btn--primary" href="${esc(link(ctx, "buchen"))}">Sturmfrei buchen</a>
          <a class="btn btn--ghost" href="${esc(link(ctx, "archiv"))}">Vergangene Shows</a>
        </div>
      </div>

    </div>
  </section>`;
}

/* ------------------------------------------------------------
   Abschnitte: Buchen
   ------------------------------------------------------------ */

const BOOKING_SUBJECT = "Anfrage: Sturmfrei buchen";

/**
 * Der Text, der schon in der Mail steht.
 *
 * Nicht Höflichkeit, sondern Zweck: eine Anfrage ohne Datum, Ort und
 * Anlass kostet zwei Mails Rückfragen. Wer die Zeilen überschreibt, hat
 * genau die Angaben geliefert, die für eine Zahl gebraucht werden.
 */
const BOOKING_BODY = [
  "Hallo Sturmfrei,",
  "",
  "wir würden euch gern buchen. Hier unsere Angaben:",
  "",
  "Anlass:",
  "Datum (oder Zeitraum):",
  "Uhrzeit:",
  "Ort / Adresse:",
  "Erwartete Zuschauerzahl:",
  "Gewünschtes Format:",
  "Spielfläche vorhanden:",
  "",
  "Sonstiges:",
  "",
  "Viele Grüße"
].join("\n");

const bookingMailto = () => mailto(BOOKING_SUBJECT, BOOKING_BODY);

function bookingFormats() {
  const cards = booking.formats
    .map((format) => `        <article class="format" data-reveal>
          <p class="format__duration">${esc(format.duration)}</p>
          <h3>${esc(format.name)}</h3>
          <p class="lead format__summary">${esc(format.summary)}</p>
          <p>${esc(format.detail)}</p>
          <p class="meta format__cast">${esc(format.cast)}</p>
        </article>`)
    .join("\n\n");

  return `  <!-- ================= FORMATE ================= -->
  <section class="section" id="formate">
    <div class="wrap">

      <div class="section-head section-head--center" data-reveal>
        <p class="eyebrow">Was möglich ist</p>
        <h2>Drei Formate</h2>
        <p class="lead">
          Alles davon lässt sich verschieben — die Formate sind ein Startpunkt für das
          Gespräch, keine Speisekarte.
        </p>
      </div>

      <div class="format-grid">

${cards}

      </div>

    </div>
  </section>`;
}

function bookingNeeds() {
  const items = booking.needs
    .map((need) => `          <div class="need">
            <dt class="need__item">${esc(need.item)}</dt>
            <dd class="need__detail">${esc(need.detail)}</dd>
          </div>`)
    .join("\n\n");

  return `  <!-- ================= WAS WIR BRAUCHEN ================= -->
  <section class="section" id="voraussetzungen">
    <div class="wrap">

      <div class="section-head" data-reveal>
        <p class="eyebrow">Vor Ort</p>
        <h2>Was wir brauchen</h2>
        <p class="lead">
          Wenig. Impro braucht keine Kulisse — aber diese sechs Dinge sollten stimmen,
          damit der Abend läuft.
        </p>
      </div>

      <dl class="need-grid" data-reveal>

${items}

      </dl>

      <p class="meta need-grid__note" data-reveal>
        Passt etwas davon nicht? Fragt trotzdem. Wir haben schon in Räumen gespielt,
        in denen laut Liste nichts gehen dürfte.
      </p>

    </div>
  </section>`;
}

function bookingPrice() {
  return `  <!-- ================= PREIS ================= -->
  <section class="section section--tight" id="preis">
    <div class="wrap wrap--prose">

      <div class="section-head" data-reveal>
        <p class="eyebrow">Ehrlich gesagt</p>
        <h2>Was es kostet</h2>
        <p class="lead">
          Auf Anfrage — und das ist keine Ausrede. Der Preis hängt an Format, Dauer,
          Anfahrt und Anlass; eine Vereinsfeier im Stadtteil und ein Firmenjubiläum
          außerhalb Hamburgs sind zwei verschiedene Zahlen.
        </p>
        <p>
          Schreibt uns kurz, was ihr vorhabt. Ihr bekommt eine feste Zahl zurück,
          nicht eine Spanne, die sich später nach oben bewegt.
        </p>
      </div>

    </div>
  </section>`;
}

function bookingFaq() {
  const items = booking.faq
    .map((entry) => `        <details class="faq__item">
          <summary>${esc(entry.q)}</summary>
          <p>${esc(entry.a)}</p>
        </details>`)
    .join("\n\n");

  return `  <!-- ================= FAQ ================= -->
  <section class="section" id="fragen">
    <div class="wrap wrap--prose">

      <div class="section-head" data-reveal>
        <p class="eyebrow">Kurz beantwortet</p>
        <h2>Häufige Fragen</h2>
      </div>

      <div class="faq" data-reveal>

${items}

      </div>

    </div>
  </section>`;
}

function bookingEnquiry(ctx) {
  return `  <!-- ================= ANFRAGE ================= -->
  <section class="section" id="anfrage">
    <div class="wrap wrap--prose">

      <div class="cta" data-reveal>

        <p class="eyebrow">Der einzige Schritt</p>
        <h2>Anfrage schicken</h2>

        <p class="lead">
          Ein Klick öffnet eine Mail, in der die Fragen schon stehen — Anlass, Datum, Ort,
          Zuschauerzahl. Ausfüllen, absenden, fertig. Wir antworten in der Regel innerhalb
          von zwei Tagen.
        </p>

        <div class="btn-row btn-row--center">
          <a class="btn btn--primary" href="${esc(bookingMailto())}">Anfrage per E-Mail</a>
          <a class="btn btn--ghost" href="${esc(link(ctx, "kontakt"))}">Andere Wege</a>
        </div>

        <p class="meta cta__note">
          Lieber ohne Mailprogramm? Alle Kontaktwege stehen auf
          <a href="${esc(link(ctx, "kontakt"))}">Kontakt</a> — auch die Direktnachricht
          auf Instagram.
        </p>

      </div>

    </div>
  </section>`;
}

/* ------------------------------------------------------------
   Abschnitte: Archiv

   Die Slider gehen bewusst über die ganze Breite und stehen nicht mehr
   in einer Karte: die Fotos sind der Inhalt dieser Seite, alles andere
   ist Beschriftung.
   ------------------------------------------------------------ */

function slide(ctx, show, photo, position) {
  const src = asset(ctx, `images/shows/${show.date}/${photo.file}`);
  // Ohne eigenen Text bleibt die laufende Nummer als Notbehelf. Ein echter
  // Satz gehört in content/shows.json; `make check` zählt die offenen.
  const alt = photo.alt || `Impro-Szene ${position} – Show vom ${dateDE(show.date)}`;

  return `        <a class="slide" href="${esc(src)}" aria-label="${esc(alt)} – Bild vergrößern">
          <img src="${esc(src)}" alt="${esc(alt)}" loading="lazy" width="1600" height="2000">
        </a>`;
}

function pastShow(ctx, show) {
  const slides = show.photos.map((photo, i) => slide(ctx, show, photo, i + 1)).join("\n");

  return `    <article class="show" data-reveal>

      <div class="wrap show__head">
        <div>
          <p class="date-line">${esc(dateDE(show.date))}</p>
          <h3>${esc(show.title)}</h3>
        </div>
        <p class="meta">
          ${esc(show.venue)} · ${esc(site.city)}<br>
          Spieler: ${esc(show.cast.join(", "))}<br>
          ${show.photos.length} Fotos
        </p>
      </div>

      <!-- Voller Breite und ohne Karte: der Slider ist selbst der Scrollbereich,
           die Innenabstände richten die erste Kachel am Text darüber aus. -->
      <div class="slider-wrap slider-wrap--bleed">
        <div class="slider" data-slider role="group" aria-label="Fotos der Show vom ${esc(dateDE(show.date))}">
${slides}
        </div>
      </div>

    </article>`;
}

function archive(ctx) {
  // Neueste zuerst: im Archiv sucht man den letzten Abend, nicht den ersten.
  // Die Reihenfolge in content/shows.json bleibt chronologisch — dort ist
  // sie beim Eintragen die natürliche.
  const ordered = [...shows.past].sort((a, b) => b.date.localeCompare(a.date));

  return `  <!-- ================= ARCHIV ================= -->
  <section class="section" id="shows">

${ordered.map((show) => pastShow(ctx, show)).join("\n\n")}

  </section>`;
}

/* ------------------------------------------------------------
   Abschnitte: Kontakt
   ------------------------------------------------------------ */

function contact(ctx) {
  return `  <!-- ================= KONTAKT ================= -->
  <section class="section" id="kontakt">
    <div class="wrap wrap--prose">

      <div class="contact-ways" data-reveal>

        <div class="contact-way">
          <p class="contact-way__label">E-Mail</p>
          <p class="lead">
            <a href="mailto:${esc(site.email)}">${esc(site.email)}</a>
          </p>
          <p class="meta">Der direkteste Weg. Wir antworten meist innerhalb von zwei Tagen.</p>
        </div>

${legal.impressum.phone ? `        <div class="contact-way">
          <p class="contact-way__label">Telefon</p>
          <p class="lead">
            <a href="tel:${esc(legal.impressum.phone.replace(/[^+0-9]/g, ""))}">${esc(legal.impressum.phone)}</a>
          </p>
          <p class="meta">
            Für Fragen, die schneller gehen als eine Mail. Wenn niemand abnimmt,
            stehen wir vermutlich auf einer Bühne — schreibt einfach.
          </p>
        </div>

` : ""}        <div class="contact-way">
          <p class="contact-way__label">Instagram</p>
          <p class="lead">
            <a ${ext(site.links.instagram.url)}>${esc(site.links.instagram.handle)}</a>
          </p>
          <p class="meta">Direktnachricht geht auch — für kurze Fragen oft der schnellere Weg.</p>
        </div>

        <div class="contact-way">
          <p class="contact-way__label">Anfrage für einen Anlass</p>
          <p class="lead">
            <a href="${esc(bookingMailto())}">Anfrage mit vorbereiteten Fragen</a>
          </p>
          <p class="meta">
            Öffnet eine Mail, in der Anlass, Datum und Ort schon als Zeilen stehen.
            Worum es dabei geht, steht auf <a href="${esc(link(ctx, "buchen"))}">Buchen</a>.
          </p>
        </div>

        <div class="contact-way">
          <p class="contact-way__label">Wo wir sind</p>
          <p class="lead">${esc(site.city)}</p>
          <p class="meta">
            Wir spielen in Hamburg und im Umland. Weiter weg geht auch — dann kommt
            die Anfahrt dazu.
          </p>
        </div>

      </div>

    </div>
  </section>`;
}

/* ------------------------------------------------------------
   Abschnitte: Impressum und Datenschutz
   ------------------------------------------------------------ */

/** Sichtbarer Hinweis, solange in content/legal.json noch Angaben fehlen. */
function legalGapNotice(fields) {
  const open = fields.filter(([, value]) => !value).map(([label]) => label);
  if (!open.length) return "";

  return `      <p class="notice" data-reveal>
        <strong>Diese Seite ist noch nicht vollständig.</strong>
        Es fehlen: ${esc(open.join(", "))}. Die Angaben stehen in
        <code>content/legal.json</code> und müssen von der Gruppe kommen —
        eine erfundene Anschrift wäre schlimmer als diese Lücke.
      </p>

`;
}

function impressum() {
  const { entity, responsible, street, postalCode, city, phone, vatId } = legal.impressum;

  const address = [
    entity ? `        ${esc(entity)}<br>` : null,
    `        ${orMissing(responsible, "Name der vertretungsberechtigten Person")}<br>`,
    `        ${orMissing(street, "Straße und Hausnummer")}<br>`,
    `        ${postalCode || city
      ? `${orMissing(postalCode, "PLZ")} ${orMissing(city, "Ort")}`
      : `${missing("PLZ")} ${missing("Ort")}`}`
  ];

  return `  <!-- ================= IMPRESSUM ================= -->
  <section class="section" id="impressum">
    <div class="wrap wrap--prose prose">

${legalGapNotice([
  ["Name", responsible],
  ["Straße", street],
  ["PLZ", postalCode],
  ["Ort", city]
])}      <h2>Angaben gemäß § 5 DDG</h2>

      <p>
${lines(address)}
      </p>

      <h2>Kontakt</h2>

      <p>
        E-Mail: <a href="mailto:${esc(site.email)}">${esc(site.email)}</a>${phone
          ? `<br>\n        Telefon: ${esc(phone)}`
          : ""}
      </p>

      <h2>Verantwortlich für den Inhalt</h2>

      <p>
        ${orMissing(responsible, "Name der verantwortlichen Person")}, Anschrift wie oben.
      </p>
${vatId ? `
      <h2>Umsatzsteuer-Identifikationsnummer</h2>

      <p>${esc(vatId)}</p>
` : ""}
      <h2>Bildrechte</h2>

      <p>
        Alle Fotos auf dieser Seite zeigen Shows von ${esc(site.brand.name)} und werden mit
        Einverständnis der abgebildeten Personen verwendet. Eine Weiterverwendung ohne
        Rückfrage ist nicht gestattet.
      </p>

      <h2>Haftung für Links</h2>

      <p>
        Diese Seite verlinkt auf Instagram, Eventbrite und MeetUp. Für die Inhalte dieser
        Seiten sind allein deren Anbieter verantwortlich. Zum Zeitpunkt der Verlinkung waren
        dort keine rechtswidrigen Inhalte erkennbar; eine laufende Kontrolle fremder Seiten
        ist ohne konkreten Anlass nicht zumutbar. Wird uns eine Rechtsverletzung bekannt,
        entfernen wir den Link.
      </p>

      <h2>Streitschlichtung</h2>

      <p>
        Wir sind nicht verpflichtet und nicht bereit, an einem Streitbeilegungsverfahren vor
        einer Verbraucherschlichtungsstelle teilzunehmen.
      </p>

    </div>
  </section>`;
}

function privacy(ctx) {
  const { host, serverLocation, hostOutsideEU, logRetentionDays, processingAgreement } = legal.privacy;
  const { responsible, street, postalCode, city } = legal.impressum;

  const retention = logRetentionDays
    ? `Diese Protokolle werden nach ${esc(String(logRetentionDays))} Tagen gelöscht.`
    : `Wie lange der Anbieter diese Protokolle aufbewahrt, richtet sich nach dessen eigenen Angaben.`;

  const where = serverLocation
    ? `; die Server stehen in ${esc(serverLocation)}`
    : "";

  // Nur eine Übermittlung in ein Drittland braucht eine Rechtsgrundlage.
  // Ein Anbieter in der EU braucht dazu keinen Satz — und einen zu schreiben,
  // der behauptet, es sei einer nötig gewesen, wäre falsch.
  const outsideEU = hostOutsideEU
    ? `
      <p>
        Der Anbieter sitzt außerhalb der Europäischen Union. Die Übermittlung erfolgt auf
        Grundlage der Standardvertragsklauseln der EU-Kommission.
      </p>`
    : "";

  // Wird erst behauptet, wenn der Vertrag wirklich geschlossen ist.
  const avv = processingAgreement
    ? `
      <p>
        Mit dem Anbieter besteht ein Vertrag über die Auftragsverarbeitung nach
        Art. 28 DSGVO.
      </p>`
    : "";

  return `  <!-- ================= DATENSCHUTZ ================= -->
  <section class="section" id="datenschutz">
    <div class="wrap wrap--prose prose">

${legalGapNotice([
  ["verantwortliche Person", responsible],
  ["Anschrift", street],
  ["Hosting-Anbieter", host]
])}      <p class="lead">
        Kurz vorweg: Diese Seite setzt keine Cookies, bindet keine Schriften oder Skripte
        von fremden Servern ein, hat kein Formular und keine Zugriffsmessung. Es gibt
        deshalb wenig zu erklären — aber das Wenige gehört hierhin.
      </p>

      <h2>Verantwortlich</h2>

      <p>
        ${orMissing(responsible, "Name der verantwortlichen Person")}<br>
        ${orMissing(street, "Straße und Hausnummer")}<br>
        ${orMissing(postalCode, "PLZ")} ${orMissing(city, "Ort")}<br>
        E-Mail: <a href="mailto:${esc(site.email)}">${esc(site.email)}</a>
      </p>

      <p class="meta">
        Die vollständigen Angaben stehen im <a href="${esc(link(ctx, "impressum"))}">Impressum</a>.
      </p>

      <h2>Aufruf der Seite (Server-Logs)</h2>

      <p>
        Diese Seite wird von ${orMissing(host, "Hosting-Anbieter")} ausgeliefert${where}. Wie bei
        jedem Webserver protokolliert der Anbieter dabei technische Daten: IP-Adresse,
        Zeitpunkt, abgerufene Datei, übertragene Menge, Browser- und Betriebssystemkennung
        sowie die zuvor besuchte Seite, falls euer Browser sie mitsendet.
      </p>

      <p>
        Diese Daten sind nötig, damit die Seite überhaupt bei euch ankommt, und sie helfen
        gegen Angriffe und Störungen. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO —
        unser berechtigtes Interesse an einem funktionierenden, sicheren Auftritt.
        ${retention} Wir selbst werten diese Protokolle nicht aus und führen sie mit nichts
        zusammen.
      </p>${outsideEU}${avv}

      <h2>Keine Cookies, keine Zugriffsmessung</h2>

      <p>
        Die Seite speichert nichts auf euren Geräten: keine Cookies, kein Local Storage,
        keine Zählpixel. Es läuft keine Analysesoftware — wir wissen nicht, wer hier war
        und wie lange.
      </p>

      <h2>Schriften und Bilder</h2>

      <p>
        Die Schriften Anton und Inter liegen auf demselben Server wie die Seite und werden
        nicht von Google Fonts geladen. Es entsteht also keine Verbindung zu Google und
        keine Übermittlung eurer IP-Adresse dorthin. Auch alle Fotos kommen von diesem
        Server; es sind keine Inhalte fremder Anbieter eingebettet.
      </p>

      <h2>E-Mail-Kontakt</h2>

      <p>
        Schreibt ihr uns, verarbeiten wir eure Adresse und den Inhalt der Nachricht, um sie
        zu beantworten — Rechtsgrundlage ist Art. 6 Abs. 1 lit. b bzw. lit. f DSGVO. Wir
        behalten die Nachricht, solange wir sie für die Sache brauchen, und geben sie nicht
        weiter. Der E-Mail-Versand selbst läuft über euren und unseren Anbieter; diese Seite
        ist daran nicht beteiligt.
      </p>

      <h2>Links zu Instagram, Eventbrite und MeetUp</h2>

      <p>
        Auf dieser Seite stehen ausschließlich normale Links dorthin — keine eingebetteten
        Inhalte, keine Buttons, die im Hintergrund Daten senden. Solange ihr nicht klickt,
        erfährt keiner der drei Anbieter etwas von euch. Klickt ihr, gelten deren
        Datenschutzbestimmungen; auf diese Verarbeitung haben wir keinen Einfluss.
      </p>

      <h2>Fotos von Shows</h2>

      <p>
        Im <a href="${esc(link(ctx, "archiv"))}">Archiv</a> sind Menschen zu sehen. Diese
        Aufnahmen entstehen bei unseren Shows und werden mit Einverständnis der Abgebildeten
        veröffentlicht. Wer sich auf einem Foto sieht und es nicht dort haben möchte,
        schreibt uns — wir nehmen es heraus, ohne Begründung und ohne Rückfrage.
      </p>

      <h2>Eure Rechte</h2>

      <p>
        Ihr habt jederzeit das Recht auf Auskunft über die zu euch gespeicherten Daten
        (Art. 15 DSGVO), auf Berichtigung (Art. 16), auf Löschung (Art. 17), auf
        Einschränkung der Verarbeitung (Art. 18), auf Datenübertragbarkeit (Art. 20) und
        auf Widerspruch gegen eine Verarbeitung, die auf einem berechtigten Interesse
        beruht (Art. 21). Eine Mail an
        <a href="mailto:${esc(site.email)}">${esc(site.email)}</a> genügt.
      </p>

      <p>
        Außerdem könnt ihr euch bei einer Datenschutz-Aufsichtsbehörde beschweren
        (Art. 77 DSGVO) — für ${esc(site.city)} ist das der Hamburgische Beauftragte für
        Datenschutz und Informationsfreiheit.
      </p>

      <h2>Änderungen</h2>

      <p>
        Ändert sich etwas an der Seite, ändert sich dieser Text mit. Es gilt immer die
        Fassung, die hier steht.
      </p>

    </div>
  </section>`;
}

/* ------------------------------------------------------------
   Die Lightbox

   Steht nur auf Seiten mit Slidern; js/lightbox.js findet sie über die
   ID und tut ohne sie nichts.
   ------------------------------------------------------------ */

function lightbox() {
  const button = (kind, glyph, label) =>
    `    <button class="icon-btn icon-btn--glass lightbox__btn lightbox__btn--${kind}" type="button" data-lightbox-${kind}>
      <span aria-hidden="true">${glyph}</span>
      <span class="visually-hidden">${label}</span>
    </button>`;

  return `<!-- ================= LIGHTBOX ================= -->
<dialog class="lightbox" id="lightbox" aria-label="Bildansicht">
  <div class="lightbox__stage">

    <img class="lightbox__img" id="lightbox-img" src="" alt=""/>

${button("close", "✕", "Schließen")}

${button("prev", "‹", "Vorheriges Bild")}

${button("next", "›", "Nächstes Bild")}

    <p class="lightbox__counter" id="lightbox-counter"></p>

  </div>
</dialog>`;
}

/* ------------------------------------------------------------
   Die Seiten

   Eine Seite: Ordnername, was im Titel steht, welche Abschnitte darin
   stehen. "schema" sagt, welche Events ins JSON-LD gehören — nur die,
   die auf der Seite auch sichtbar sind.
   ------------------------------------------------------------ */

const PAGES = [
  {
    slug: "index",
    navLabel: "Start",
    title: site.meta.title,
    description: site.meta.description,
    ogDescription: site.meta.ogDescription,
    schema: ["upcoming"],
    sections: (ctx) => [hero(ctx), nextShowTeaser(ctx), impro(ctx), about(ctx), follow()]
  },

  {
    slug: "termine",
    navLabel: "Termine",
    title: `Termine – ${site.brand.alternateName}`,
    description:
      "Die nächsten Impro-Shows von Sturmfrei in Hamburg: Termine, Tickets und die Kanäle, " +
      "auf denen neue Abende zuerst auftauchen.",
    schema: ["upcoming"],
    sections: (ctx) => [
      pageHero({
        eyebrow: "Wann wir spielen",
        title: "Termine",
        lead:
          "Wir spielen unregelmäßig, und meistens in Hamburg. Was ansteht, steht hier — " +
          "und wenn nichts ansteht, steht das auch hier."
      }),
      dates(ctx)
    ]
  },

  {
    slug: "buchen",
    navLabel: "Buchen",
    title: `Sturmfrei buchen – Impro für euren Anlass`,
    description:
      "Improvisationstheater für Firmenfeier, Geburtstag oder Vereinsfest: Formate, " +
      "Voraussetzungen und der direkte Weg zur Anfrage.",
    ogDescription:
      "Wir kommen zu euch: Impro für Firmenfeier, Geburtstag oder Vereinsfest. Formate, " +
      "was wir vor Ort brauchen, und eine Anfrage in einem Klick.",
    sections: (ctx) => [
      pageHero({
        eyebrow: "Wir kommen zu euch",
        title: "Sturmfrei buchen",
        lead:
          "Firmenfeier, Geburtstag, Vereinsfest, Jubiläum: Wir bringen eine Show mit, die es " +
          "vorher nicht gab und danach nie wieder gibt — aus dem, was euer Abend hergibt.",
        actions: `<div class="btn-row">
  <a class="btn btn--primary" href="${esc(bookingMailto())}">Anfrage schicken</a>
  <a class="btn btn--on-dark" href="#formate">Formate ansehen</a>
</div>`
      }),
      bookingFormats(),
      bookingNeeds(),
      bookingPrice(),
      bookingFaq(),
      bookingEnquiry(ctx)
    ]
  },

  {
    slug: "archiv",
    navLabel: "Archiv",
    title: `Archiv – vergangene Shows von ${site.brand.name}`,
    description:
      `Rückblick auf die Impro-Shows von ${site.brand.name}: ${shows.past.length} Abende, ` +
      `${photoCount(shows)} Fotos aus dem Kulturschloss Wandsbek und anderswo.`,
    schema: ["past"],
    lightbox: true,
    sections: (ctx) => [
      pageHero({
        eyebrow: "Rückblick",
        title: "Archiv",
        lead:
          `${shows.past.length} Shows, ${photoCount(shows)} Fotos. Kein Abend davon lässt sich ` +
          `wiederholen — deshalb steht er hier.`
      }),
      archive(ctx)
    ]
  },

  {
    slug: "kontakt",
    navLabel: "Kontakt",
    title: `Kontakt – ${site.brand.alternateName}`,
    description:
      `Sturmfrei aus ${site.city} erreichen: E-Mail, Instagram und der Weg zur Anfrage ` +
      `für einen eigenen Anlass.`,
    sections: (ctx) => [
      pageHero({
        eyebrow: "Sagt Hallo",
        title: "Kontakt",
        lead:
          "Fragen, Buchungen oder einfach Hallo sagen. Wir lesen alles und antworten meist " +
          "innerhalb von zwei Tagen."
      }),
      contact(ctx),
      follow()
    ]
  },

  {
    slug: "impressum",
    navLabel: "Impressum",
    title: `Impressum – ${site.brand.alternateName}`,
    description: `Anbieterkennzeichnung nach § 5 DDG für ${site.brand.alternateName}.`,
    noindex: true,
    sections: () => [
      pageHero({ eyebrow: "Pflichtangaben", title: "Impressum" }),
      impressum()
    ]
  },

  {
    slug: "datenschutz",
    navLabel: "Datenschutz",
    title: `Datenschutzerklärung – ${site.brand.alternateName}`,
    description:
      "Was diese Seite an Daten verarbeitet — und was nicht: keine Cookies, keine " +
      "Zugriffsmessung, keine fremden Schriften.",
    noindex: true,
    sections: (ctx) => [
      pageHero({ eyebrow: "Pflichtangaben", title: "Datenschutz" }),
      privacy(ctx)
    ]
  }
];

/** Damit check.mjs prüfen kann, ob content/site.json auf echte Seiten zeigt. */
export const SLUGS = PAGES.map((page) => page.slug);

/* ------------------------------------------------------------
   Zusammensetzen
   ------------------------------------------------------------ */

const GENERATED = (sources) =>
  `ERZEUGT von tools/build.mjs — nicht direkt bearbeiten.
     Quellen: ${sources}
     Nach dem Bearbeiten: make build`;

function pageHtml(page) {
  const ctx = { slug: page.slug, base: baseFor(page.slug) };

  return `<!DOCTYPE html>
<html lang="${esc(site.meta.lang)}">
<!--
     ${GENERATED("content/*.json und tools/build.mjs")}
     Diese Seite: ${page.slug}
-->
<head>
${head(page, ctx)}
</head>

<body>

<a class="skip-link" href="#main">Zum Inhalt springen</a>

<!-- ================= HEADER ================= -->
${header(ctx)}

<main id="main" tabindex="-1">

${page.sections(ctx).join("\n\n")}

</main>

${footer(ctx)}
${page.lightbox ? `\n${lightbox()}\n` : ""}
<script type="module" src="${esc(asset(ctx, "js/main.js"))}"></script>
</body>
</html>
`;
}

function css() {
  const files = readdirSync(join(root, "css")).filter((name) => name.endsWith(".css")).sort();

  const parts = files.map((name) => read("css", name).trim());

  return `/* ${GENERATED("css/*.css")}\n\n   Zusammengesetzt aus:\n${
    files.map((name) => `     css/${name}`).join("\n")
  }\n */\n\n${parts.join("\n\n\n")}\n`;
}

/* Die Module aus js/ landen wortgleich unter public/js/: der Browser lädt sie
   unkompiliert als ES-Module, sie *sind* das Ergebnis. Sie gehen trotzdem
   durch render(), damit für sie gilt, was für jede erzeugte Datei gilt —
   `make check` vergleicht sie mit der Quelle, und unter public/ wird nichts
   von Hand bearbeitet. Genau diese Ausnahme gab es vorher.

   Kein ERZEUGT-Banner in den Kopien: wortgleich heißt wortgleich, damit eine
   Zeilennummer im Stacktrace des Browsers auf dieselbe Zeile in js/ zeigt. */
function modules() {
  const files = readdirSync(join(root, "js")).filter((name) => name.endsWith(".js")).sort();

  return Object.fromEntries(files.map((name) => [`js/${name}`, read("js", name)]));
}

/* ------------------------------------------------------------
   fonts/ und images/

   Keine Textdateien, also nichts für render(): die beiden Ordner werden
   gespiegelt. Mit Aufräumen — was in der Quelle verschwindet, verschwindet
   auch unter public/. Sonst lägen die Fotos einer gelöschten Show dort
   weiter und gingen mit dem nächsten Deploy live.
   ------------------------------------------------------------ */

export const ASSET_DIRS = ["fonts", "images"];

/** Alle Dateien unter dir/, als Pfade relativ zum Projektordner. */
export function assetFiles(dir) {
  return walk(join(root, dir)).map((name) => `${dir}/${name}`);
}

function mirror(dir) {
  const source = join(root, dir);
  if (!existsSync(source)) return;

  prune(source, join(out, dir));
  cpSync(source, join(out, dir), { recursive: true });
}

/** Was es in der Quelle nicht mehr gibt, muss im Ziel auch weg. */
function prune(source, target) {
  if (!existsSync(target)) return;

  for (const entry of readdirSync(target, { withFileTypes: true })) {
    const from = join(source, entry.name);
    const to = join(target, entry.name);

    if (!existsSync(from)) rmSync(to, { recursive: true, force: true });
    else if (entry.isDirectory()) prune(from, to);
  }
}

function sitemap() {
  // Impressum und Datenschutz stehen absichtlich nicht drin: sie sind
  // noindex und gehören nicht in den Index, nur in den Footer.
  const urls = PAGES.filter((page) => !page.noindex)
    .map((page) => `  <url>\n    <loc>${esc(canonicalFor(page.slug))}</loc>\n  </url>`)
    .join("\n");

  return `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${urls}
</urlset>
`;
}

function robots() {
  return `User-agent: *
Allow: /

Sitemap: ${absolute("sitemap.xml")}
`;
}

/* Erzeugt alles, schreibt nichts: Name → Inhalt. */
export function render() {
  const files = { "style.css": css(), ...modules() };

  for (const page of PAGES) {
    files[fileFor(page.slug)] = pageHtml(page);
  }

  if (site.url) {
    files["sitemap.xml"] = sitemap();
    files["robots.txt"] = robots();
  }

  return files;
}

/* ------------------------------------------------------------
   Schreiben
   ------------------------------------------------------------ */

function main() {
  const files = render();

  for (const [name, content] of Object.entries(files)) {
    const target = join(out, name);
    mkdirSync(dirname(target), { recursive: true });
    writeFileSync(target, content);
  }

  // Die Binärordner danach: render() kennt nur Text.
  for (const dir of ASSET_DIRS) mirror(dir);

  // Ohne Domain wären sitemap.xml und robots.txt falsch — dann lieber weg damit.
  for (const name of ["sitemap.xml", "robots.txt"]) {
    if (!files[name] && existsSync(join(out, name))) rmSync(join(out, name));
  }

  const pageCount = PAGES.length;
  const moduleCount = Object.keys(files).filter((name) => name.startsWith("js/")).length;
  const assetCount = ASSET_DIRS.reduce((sum, dir) => sum + assetFiles(dir).length, 0);

  console.log(
    `Gebaut nach ${outName}/: ${pageCount} Seiten (${SLUGS.join(", ")}), style.css, ` +
    `${moduleCount} Module aus js/ und ${assetCount} Dateien aus fonts/ und images/\n` +
    `  ${shows.past.length} Shows, ${photoCount(shows)} Fotos` +
    (shows.upcoming ? `, nächste Show am ${dateDE(shows.upcoming.date)}` : ", kein nächster Termin") +
    (site.url ? `\n  Domain: ${canonicalFor("index")}` : `\n  Domain: noch nicht gesetzt (content/site.json → "url")`)
  );
}

if (isMain(import.meta.url)) main();
