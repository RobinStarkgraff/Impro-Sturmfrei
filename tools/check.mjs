#!/usr/bin/env node
/* ============================================================
   Prüft die Seiten, ohne etwas zu installieren.

   Kein Ersatz für einen echten HTML-Validator, aber es fängt genau die
   Fehler, die hier schon vorgekommen sind: ein Sprungziel, das es nicht
   gibt, ein Bildpfad mit Tippfehler, ein target="_blank" ohne rel, eine
   erzeugte Datei, die nicht mehr zu content/ passt.

   Seit die Seite mehrere Seiten hat, kommt der häufigste neue Fehler dazu:
   ein Pfad, der von der Startseite aus stimmt und aus einem Unterordner
   ins Leere zeigt. Deshalb wird jeder Verweis relativ zu der Seite
   aufgelöst, in der er steht — genau so, wie der Browser es tut.

   Gesucht wird dabei in public/: das ist die Wurzel der ausgelieferten
   Seite, und nur was dort liegt, ist im Browser erreichbar.

   Aufruf:  node tools/check.mjs   (oder: make check)
   ============================================================ */

import { existsSync, readdirSync, statSync } from "node:fs";
import { dirname, join, posix, resolve } from "node:path";

import { ASSET_DIRS, assetFiles, render, SLUGS } from "./build.mjs";
import { CSS_CLASS, DATA_HOOK, DATA_KEY } from "../js/classes.js";
import { out, outName, photoCount, readJson, readOut, root, walk } from "./lib.mjs";

const problems = [];
const warnings = [];
const fail = (message) => problems.push(message);
const warn = (message) => warnings.push(message);

const site = readJson("content", "site.json");
const shows = readJson("content", "shows.json");
const booking = readJson("content", "booking.json");
const legal = readJson("content", "legal.json");

/* Die Seiten, wie build.mjs sie erzeugt hat — nicht die, die auf der Platte
   liegen. Sonst prüfte ein Lauf nach einem Umbau noch die alten Dateien. */
const generated = render();

const pages = Object.entries(generated).filter(([name]) => name.endsWith(".html"));

/* ------------------------------------------------------------
   1. Sind die erzeugten Dateien noch aktuell?
   ------------------------------------------------------------ */

/* Wo die erzeugte Datei herkommt — damit die Meldung dorthin zeigt, wo man
   sie wirklich bearbeitet, und nicht pauschal auf content/. */
function sourceFor(name) {
  if (name.startsWith("js/")) return name; // wortgleiche Kopie: js/slider.js
  if (name === "style.css") return "css/*.css";
  return "content/ und tools/build.mjs";
}

function checkUpToDate() {
  // Nochmal bauen — im Speicher, nicht auf die Platte — und vergleichen. So
  // verrät sich eine Änderung, die direkt in index.html gelandet ist und
  // beim nächsten Build verloren gehen würde.
  for (const [name, fresh] of Object.entries(generated)) {
    if (!existsSync(join(out, name))) {
      fail(`${outName}/${name} fehlt — "make build" laufen lassen`);
    } else if (readOut(name) !== fresh) {
      fail(`${outName}/${name} passt nicht zu ${sourceFor(name)} — "make build" laufen lassen`);
    }
  }
}

/* ------------------------------------------------------------
   2. Dateien und Seiten, auf die die Seiten zeigen

   Aufgelöst wie im Browser: relativ zum Ordner der Seite, in der der
   Verweis steht. Ein Verweis auf einen Ordner (oder auf "../") trifft
   dessen index.html.
   ------------------------------------------------------------ */

function resolveRef(pageFile, target) {
  const from = posix.dirname(pageFile.split("\\").join("/"));
  const joined = posix.normalize(posix.join(from, target));

  // Aus public/ heraus zeigt kein gültiger Verweis: was dort nicht liegt,
  // ist im Browser nicht erreichbar.
  if (joined.startsWith("..")) return null;

  const onDisk = resolve(out, joined);
  if (existsSync(onDisk) && statSync(onDisk).isDirectory()) {
    return { path: join(onDisk, "index.html"), shown: `${joined}/index.html` };
  }

  return { path: onDisk, shown: joined };
}

function checkLocalTargets() {
  for (const [pageFile, html] of pages) {
    for (const match of html.matchAll(/(?:src|href)="([^"]*)"/g)) {
      const target = match[1];

      if (!target || target.startsWith("#")) continue;
      if (/^(https?:|mailto:|tel:|data:)/.test(target)) continue;

      const ref = resolveRef(pageFile, target.split("#")[0]);

      if (!ref) {
        fail(`${pageFile}: "${target}" zeigt aus public/ heraus`);
      } else if (!existsSync(ref.path)) {
        fail(`${pageFile}: "${target}" gibt es nicht (gesucht: ${ref.shown})`);
      }
    }
  }
}

/* Geprüft wird die Quelle, nicht die Kopie: dort legt man die Fotos ab, und
   dort behebt man es auch. Ob die Kopie unter public/ angekommen ist, fällt
   ohnehin auf — dann zeigt jedes <img> der Show ins Leere (Prüfung 2). */
function checkPhotoFolders() {
  for (const show of shows.past) {
    const dir = join("images", "shows", show.date);

    if (!existsSync(join(root, dir))) {
      fail(`Ordner fehlt: ${dir}`);
      continue;
    }

    const onDisk = readdirSync(join(root, dir)).filter((name) => /\.jpe?g$/i.test(name));
    const listed = show.photos.map((photo) => photo.file);

    for (const name of onDisk) {
      if (!listed.includes(name)) warn(`${dir}/${name} liegt da, steht aber nicht in shows.json`);
    }
  }
}

/* ------------------------------------------------------------
   3. Sprungziele — je Seite, denn eine ID auf einer anderen Seite hilft
      einem #anker nicht.
   ------------------------------------------------------------ */

function checkAnchors() {
  for (const [pageFile, html] of pages) {
    const ids = new Set([...html.matchAll(/\bid="([^"]+)"/g)].map((m) => m[1]));

    for (const match of html.matchAll(/href="#([^"]+)"/g)) {
      if (!ids.has(match[1])) fail(`${pageFile}: Sprungziel #${match[1]} gibt es dort nicht`);
    }

    for (const match of html.matchAll(/aria-(?:labelledby|controls)="([^"]+)"/g)) {
      for (const id of match[1].split(/\s+/)) {
        if (!ids.has(id)) fail(`${pageFile}: aria-Verweis auf #${id} gibt es dort nicht`);
      }
    }
  }
}

/* ------------------------------------------------------------
   4. Barrierefreiheit, externe Links, Gliederung
   ------------------------------------------------------------ */

function checkImagesAndLinks() {
  for (const [pageFile, html] of pages) {
    for (const tag of html.match(/<img\b[^>]*>/g) ?? []) {
      if (!/\balt="/.test(tag)) fail(`${pageFile}: <img> ohne alt: ${tag.slice(0, 70)}…`);
    }

    for (const tag of html.match(/<a\b[^>]*>/g) ?? []) {
      if (/target="_blank"/.test(tag) && !/rel="[^"]*noopener/.test(tag)) {
        fail(`${pageFile}: target="_blank" ohne rel="noopener": ${tag.slice(0, 70)}…`);
      }
    }

    // Genau eine h1 je Seite: sie ist der Name der Seite. Zwei sind eine
    // Gliederung ohne Spitze, keine ist eine Seite ohne Namen.
    const h1s = (html.match(/<h1\b/g) ?? []).length;
    if (h1s !== 1) fail(`${pageFile}: ${h1s} <h1> — es muss genau eine sein`);

    // Ein <title> pro Seite, und nicht auf allen derselbe: das war der Grund,
    // die Seite überhaupt aufzuteilen.
    if (!/<title>[^<]+<\/title>/.test(html)) fail(`${pageFile}: <title> fehlt oder ist leer`);
  }

  const titles = pages.map(([, html]) => html.match(/<title>([^<]+)<\/title>/)?.[1]);
  const duplicates = titles.filter((title, i) => titles.indexOf(title) !== i);
  for (const title of new Set(duplicates)) {
    fail(`zwei Seiten tragen denselben <title>: "${title}"`);
  }
}

/* ------------------------------------------------------------
   5. Navigation — zeigt sie auf Seiten, die es gibt?
   ------------------------------------------------------------ */

function checkNav() {
  for (const item of [...site.nav, ...site.legalNav]) {
    if (!SLUGS.includes(item.page)) {
      fail(`content/site.json: "${item.label}" zeigt auf die Seite "${item.page}", die es in PAGES (tools/build.mjs) nicht gibt`);
    }
  }

  // Umgekehrt: eine Seite, die in keiner Liste steht, ist nur über einen
  // direkten Link erreichbar — meist ein Versehen.
  const linked = new Set([...site.nav, ...site.legalNav].map((item) => item.page));
  for (const slug of SLUGS) {
    if (!linked.has(slug)) warn(`die Seite "${slug}" steht in keiner Navigation — niemand findet sie`);
  }
}

/* ------------------------------------------------------------
   6. Der Vertrag aus js/classes.js

   Direkt importiert, nicht aus dem Quelltext gelesen: eine andere
   Formatierung in classes.js darf diese Prüfung nicht aushebeln.
   ------------------------------------------------------------ */

function checkContract() {
  const css = readOut("style.css");

  // Jeder Zustandsname muss im CSS vorkommen, sonst schaltet das JS etwas,
  // das niemand darstellt.
  for (const name of [...Object.values(CSS_CLASS), ...Object.values(DATA_KEY)]) {
    if (name === CSS_CLASS.js) continue; // steht im CSS als Präfix .js, nicht als Klasse
    if (!css.includes(`.${name}`) && !css.includes(`data-${kebab(name)}`)) {
      warn(`js/classes.js kennt "${name}", im CSS steht dazu nichts`);
    }
  }

  // Und umgekehrt: die Haken, an denen die Module hängen, müssen im Markup
  // stehen. Auf welcher Seite, ist offen — die Lightbox gehört nur ins
  // Archiv. Aber auf keiner einzigen wäre das Modul toter Code.
  for (const hook of Object.values(DATA_HOOK)) {
    const found = pages.filter(([, html]) => html.includes(hook)).map(([name]) => name);
    if (!found.length) fail(`js/ sucht ${hook}, keine erzeugte Seite hat es`);
  }
}

const kebab = (value) => value.replace(/[A-Z]/g, (c) => `-${c.toLowerCase()}`);

/* ------------------------------------------------------------
   7. Verwaistes im Zielordner

   public/ ist vollständig erzeugt und liegt nicht im Repo. Was dort liegt
   und weder aus render() noch aus fonts/ oder images/ stammt, ist übrig
   geblieben — eine Seite, die es nicht mehr gibt, oder etwas von Hand.
   `make build` überschreibt, aber räumt außerhalb von fonts/ und images/
   nicht auf; ein Deploy nähme es mit.
   ------------------------------------------------------------ */

function checkOrphans() {
  const expected = new Set([...Object.keys(generated), ...ASSET_DIRS.flatMap(assetFiles)]);

  for (const name of walk(out)) {
    if (!expected.has(name)) warn(`${outName}/${name} stammt aus keiner Quelle — übrig geblieben?`);
  }
}

/* ------------------------------------------------------------
   8. Offene Punkte im Inhalt
   ------------------------------------------------------------ */

function reportContentGaps() {
  const missingAlt = shows.past.flatMap((show) => show.photos.filter((photo) => !photo.alt));

  if (missingAlt.length) {
    warn(
      `${missingAlt.length} von ${photoCount(shows)} Fotos haben noch keinen eigenen alt-Text ` +
      `(content/shows.json). Ersatz ist die laufende Nummer — für Screenreader ` +
      `ist das fast nichts.`
    );
  }

  if (!site.url) warn('content/site.json: "url" ist noch nicht gesetzt, also gibt es kein canonical, kein og:url und keine sitemap.xml');
  else if (!site.ogImage) warn('content/site.json: "ogImage" fehlt — Links in Instagram und WhatsApp zeigen eine Karte ohne Bild');
  else if (!existsSync(join(out, site.ogImage))) {
    // Steht nur als absolute URL im Markup, fällt dort also nicht auf.
    fail(`content/site.json verweist auf ${site.ogImage}, ${outName}/${site.ogImage} gibt es nicht`);
  }

  // Pflichtangaben: FEHLER, nicht Hinweis. Ohne sie darf die Seite nicht
  // öffentlich gehen (§5 DDG), und der Platzhalter steht sichtbar auf der
  // Seite — das soll niemand versehentlich veröffentlichen.
  const required = [
    ["impressum.responsible", legal.impressum.responsible, "Name der vertretungsberechtigten Person"],
    ["impressum.street", legal.impressum.street, "Straße und Hausnummer"],
    ["impressum.postalCode", legal.impressum.postalCode, "PLZ"],
    ["impressum.city", legal.impressum.city, "Ort"],
    ["privacy.host", legal.privacy.host, "Hosting-Anbieter für die Datenschutzerklärung"]
  ];

  for (const [key, value, what] of required) {
    if (!value) fail(`content/legal.json: "${key}" fehlt — ${what}. Solange steht auf der Seite ein sichtbarer Platzhalter.`);
  }

  if (!booking.reviewed) {
    warn(
      'content/booking.json: "reviewed" steht auf false — Formate, Dauer, Personenzahl und ' +
      "die Antworten unter faq sind Entwürfe und noch von niemandem geprüft."
    );
  }
}

/* ------------------------------------------------------------
   Los
   ------------------------------------------------------------ */

checkUpToDate();
checkLocalTargets();
checkPhotoFolders();
checkAnchors();
checkImagesAndLinks();
checkNav();
checkContract();
checkOrphans();
reportContentGaps();

for (const message of warnings) console.log(`  Hinweis: ${message}`);
for (const message of problems) console.log(`  FEHLER:  ${message}`);

console.log(
  `\n${pages.length} Seiten geprüft.` +
  (problems.length
    ? ` ${problems.length} Fehler, ${warnings.length} Hinweise.`
    : ` Alles in Ordnung${warnings.length ? `, ${warnings.length} Hinweise` : ""}.`)
);

process.exit(problems.length ? 1 : 0);
