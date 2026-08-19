/* ------------------------------------------------------------
   Gemeinsames Werkzeug von build.mjs und check.mjs.

   Beide Skripte kennen dieselben zwei Wurzeln — `root` für die Quellen,
   `out` für die fertige Seite —, brauchen dieselbe Datumsform und
   dieselbe Fotozählung. Das steht hier einmal.
   ------------------------------------------------------------ */

import { existsSync, readdirSync, readFileSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { dirname, join, relative, resolve } from "node:path";

export const root = resolve(dirname(fileURLToPath(import.meta.url)), "..");

/**
 * Die fertige Seite — und nur die.
 *
 * Vollständig erzeugt: die Seiten, style.css und js/ schreibt build.mjs,
 * fonts/ und images/ spiegelt es aus den Quellen. Im Repo liegt der Ordner
 * nicht (siehe .gitignore) — er entsteht beim Build, lokal wie auf dem
 * Server. Bearbeitet wird daneben: content/, css/, js/, images/, fonts/,
 * tools/.
 *
 * SITE_OUT baut woanders hin. tools/deploy.sh nutzt das auf dem Server: erst
 * in einen Nebenordner bauen, prüfen, dann gegen public/ tauschen — so nimmt
 * ein fehlgeschlagener Build die laufende Seite nicht mit.
 */
export const out = resolve(root, process.env.SITE_OUT ?? "public");

/** Wie der Zielordner in Meldungen heißen soll: "public" oder das aus SITE_OUT. */
export const outName = relative(root, out);

export const read = (...parts) => readFileSync(join(root, ...parts), "utf8");

/**
 * Alle Dateien unter dir/, rekursiv, als Pfade relativ zu dir und immer mit
 * Schrägstrich.
 *
 * Von Hand statt mit readdirSync({ recursive: true }): das braucht Node 20.12
 * (wegen entry.parentPath), und auf welchem Node der Webspace läuft, wissen
 * wir nicht. So genügt jedes Node, das cpSync kennt.
 */
export function walk(dir, prefix = "") {
  if (!existsSync(dir)) return [];

  return readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
    const name = prefix ? `${prefix}/${entry.name}` : entry.name;
    return entry.isDirectory() ? walk(join(dir, entry.name), name) : [name];
  });
}

/** Wie read, aber aus dem fertigen Ordner. */
export const readOut = (...parts) => readFileSync(join(out, ...parts), "utf8");

/**
 * Wie read, aber als Daten — und ohne die Dokumentation.
 *
 * $comment-Schlüssel sind Kommentare in den Datendateien, keine Daten. Der
 * Reviver greift auf jeder Ebene: ein $comment in "links" würde sonst als
 * Follow-Karte, als Footer-Link und als sameAs-Eintrag im JSON-LD landen.
 */
export const readJson = (...parts) =>
  JSON.parse(read(...parts), (key, value) => (key.startsWith("$comment") ? undefined : value));

/** 2026-01-09 → 09.01.2026 */
export function dateDE(iso) {
  const [year, month, day] = iso.split("-");
  return `${day}.${month}.${year}`;
}

/** Fotos über alle vergangenen Shows. */
export const photoCount = (shows) =>
  shows.past.reduce((sum, show) => sum + show.photos.length, 0);

/** Läuft die Datei als Skript oder wurde sie nur importiert? */
export const isMain = (moduleUrl) => process.argv[1] === fileURLToPath(moduleUrl);
