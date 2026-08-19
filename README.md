# Sturmfrei – Hamburger Impro Shows

Website für **Sturmfrei**, eine Improvisationstheater-Gruppe aus Hamburg.
Statische Seite, keine Abhängigkeiten — sie wird aber aus den Quellen
erzeugt (`make build`, braucht nur Node). Im Repo stehen die Quellen;
ausgeliefert wird genau ein Ordner, `public/`, und der entsteht beim Build.

🔗 Instagram: [@impro_sturmfrei](https://www.instagram.com/impro_sturmfrei/)

## Aufbau

Bearbeitet wird alles **oben** im Projektordner, ausgeliefert wird alles
**unter `public/`**. Im Repo stehen die Quellen — `public/` nicht: der Ordner
entsteht mit `make build`, lokal wie auf dem Server, und steht in
`.gitignore`.

Das ist die eine Regel, die das Projekt hat: **`public/` ist ein Ergebnis.**
Es wird nicht von Hand bearbeitet und nicht committet; wer dort editiert,
verliert es beim nächsten Build. Was daraus folgt, ist der erste Befehl in
einem frischen Klon:

```bash
make build          # ohne diesen Schritt gibt es kein public/
```

`make check` meldet danach beides: eine erzeugte Datei, die nicht mehr zu
ihrer Quelle passt, und eine Datei unter `public/`, die aus keiner Quelle
stammt.

```
QUELLEN — hier wird bearbeitet
content/site.json           Links, Navigation, E-Mail, Meta-Angaben, Fotos
content/shows.json          Alle Shows: Datum, Titel, Ort, Spieler, Fotos
content/booking.json        Formate, Voraussetzungen und Fragen für /buchen/
content/legal.json          Angaben für Impressum und Datenschutz
css/00-fonts.css …          Styles in Abschnitten; Tokens in 01-tokens.css
js/main.js …                die Module: header, reveal, crossfade, slider,
                            lightbox + config (Zahlen), classes (Vertrag zu
                            CSS und Markup), motion, images
fonts/                      Anton & Inter als woff2, selbst gehostet
images/logo/                Logo (auch als Favicon)
images/group/               Gruppenfotos für Hero und Überblendung
images/shows/<datum>/       Fotos je Show, Ordner nach Datum (YYYY-MM-DD)
tools/build.mjs             erzeugt Seiten, style.css und js/ nach public/
tools/check.mjs             prüft die fertigen Seiten
tools/lib.mjs               das, was beide brauchen (beide Wurzeln, JSON, Datum)
tools/deploy.sh             der Befehl, den netcup nach dem Deploy aufruft
tools/install-node.sh       legt einmalig ~/bin/node in den Webspace
tools/optimize-images.sh    verkleinert die Fotos
tools/fetch-fonts.sh        holt die Schriften
.htaccess                   liefert nur public/ aus (siehe "Deploy")
Makefile                    die Aufgaben (make help)

public/ — die fertige Seite. Vollständig erzeugt, nicht im Repo, wird
          ausgeliefert. Alles darin entsteht mit `make build`:

  index.html                Start: Hero, nächster Termin, Was ist Impro?, Über uns
  termine/index.html        Termine: nächste Show, wo neue Termine auftauchen
  buchen/index.html         Buchen: Formate, Voraussetzungen, Preis, Anfrage
  archiv/index.html         Archiv: vergangene Shows, Slider, Lightbox
  kontakt/index.html        Kontakt: E-Mail, Instagram, Anfrage, Ort
  impressum/index.html      Pflichtangaben nach § 5 DDG
  datenschutz/index.html    Datenschutzerklärung
  style.css                 css/*.css in Dateinamenfolge zusammengesetzt
  js/                       js/*.js, wortgleich kopiert
  fonts/ images/            aus fonts/ und images/ gespiegelt
  robots.txt sitemap.xml    nur, wenn in site.json eine Domain steht
```

`fonts/` und `images/` sind keine Textdateien und gehen deshalb nicht durch
`render()`, sondern werden gespiegelt — mit Aufräumen: was in der Quelle
verschwindet, verschwindet auch unter `public/`. Sonst lägen die Fotos einer
gelöschten Show dort weiter und gingen mit dem nächsten Deploy live.

`public/js/` sind wortgleiche Kopien von `js/` — keine Bündelung, keine
Umschreibung: der Browser lädt sie unkompiliert als ES-Module, und eine
Zeilennummer in seinem Stacktrace zeigt auf dieselbe Zeile in `js/`.

Warum sie überhaupt durch den Build gehen, obwohl sich dabei kein Byte
ändert: damit für sie gilt, was für jede erzeugte Datei gilt. `js/` lag
vorher unter `public/` und war dort der einzige Ordner, der von Hand
bearbeitet wurde — die Ausnahme, die die Regel jedes Mal einen Nebensatz
kostete. Teurer war das Schweigen: `make check` vergleicht jede erzeugte
Datei mit ihrer Quelle, und die zehn Module verglich es mit nichts. Ein
Tippfehler in `public/js/slider.js` fiel nirgends auf. Jetzt schon.

Warum ein Build-Schritt: eine Show stand vorher an vier Stellen in
`index.html` — als Artikel, als 8 bis 11 handgeschriebene `<img>`, im
`aria-label` des Sliders und als `TheaterEvent` im JSON-LD. Dazu die
Links: Instagram sechsmal, Eventbrite viermal, die Adresse dreimal, und
die Navigation je einmal in Kopfleiste und Footer. Jetzt steht jede
Angabe genau einmal in `content/` — und seit es sieben Seiten sind,
stünden Kopfleiste, Footer und `<head>` sonst siebenmal da.

## Die Seiten

Eine Seite ist ein Eintrag in `PAGES` in `tools/build.mjs`: Ordnername,
Titel, Beschreibung, und welche Abschnitte in welcher Reihenfolge darin
stehen. Kopfleiste, Footer, `<head>` und JSON-LD entstehen daraus.

```js
{
  slug: "termine",              // Ordner: termine/index.html
  navLabel: "Termine",
  title: "…",                   // <title> und og:title
  description: "…",             // meta description
  schema: ["upcoming"],         // welche Events ins JSON-LD gehören
  sections: (ctx) => [ pageHero({…}), dates(ctx) ]
}
```

**Pfade sind relativ, nicht absolut.** Jede Seite bekommt ein `base`:
`""` auf der Startseite, `"../"` in einem Unterordner. Ein Verweis auf
eine Datei läuft über `asset(ctx, "images/…")`, einer auf eine andere
Seite über `link(ctx, "archiv")`. Wer stattdessen `images/…` direkt ins
Markup schreibt, baut einen Link, der von der Startseite aus stimmt und
aus jedem Unterordner ins Leere zeigt — `make check` löst deshalb jeden
Verweis relativ zu der Seite auf, in der er steht.

`public/` kommt in keinem dieser Pfade vor: die Wurzel der ausgelieferten
Seite *ist* `public/`, und im Browser heißt sie `/`. Ein Pfad im Markup
ist immer relativ zur Seite, nie zum Projektordner. Genau deshalb war das
Verschieben nach `public/` auch keine Änderung am Markup — die Pfade
untereinander sind dieselben geblieben.

Die Startseite ist die einzige mit Hero. Alle anderen beginnen mit
`pageHero()` — demselben dunklen Band, nur ohne Foto und ohne volle
Fensterhöhe: es trägt Titel und Vorspann.

Die Kopfleiste ist ein eigener Abschnitt und kein Schleier über dem Hero:
sie bringt ihren dunklen Grund selbst mit, liegt fest oben, und ihre Höhe
steht als `--header-h` in `css/01-tokens.css`. Drei Stellen rechnen mit ihr
— `<body>` hält sie als `padding-top` frei, der Hero zieht sie von der
Fensterhöhe ab, und jedes Sprungziel hält sie als `scroll-margin-top` frei.
Wer die Leiste höher haben will, ändert genau dieses eine Token.

## Befehle

```bash
make build          # public/ erzeugen: Seiten, style.css, js/, fonts/, images/
make check          # Pfade, Sprungziele, alt-Texte, Stand der Ergebnisse
make serve          # public/ auf http://localhost:8000

make images         # zeigt, was die Fotos wiegen
make images-apply   # verkleinert sie auf 1600 px (überschreibt images/!)
make fonts          # Anton & Inter neu nach fonts/ holen
```

`make check` und `make serve` setzen ein gebautes `public/` voraus — im
frischen Klon also zuerst `make build`.

`make check` baut noch einmal — im Speicher, ohne etwas zu schreiben — und
vergleicht mit dem, was in `public/` liegt: wer aus Versehen direkt in einer
erzeugten Seite editiert, erfährt es dort, bevor der nächste Build es
überschreibt. Außerdem löst es jeden Verweis relativ zu der Seite auf, in der
er steht — gesucht wird dabei in `public/`, denn nur was dort liegt, ist im
Browser erreichbar —, prüft je Seite genau eine `<h1>` und einen eigenen
`<title>`, und meldet zwei Seiten mit demselben Titel.

**Nicht per Doppelklick öffnen.** Über `file://` blockiert Chrome die
Schriften aus `public/fonts/` — Schriftdateien werden immer im CORS-Modus
geladen, und eine `file://`-Seite hat keinen gültigen Origin. Dasselbe gilt
für `public/js/`: ES-Module brauchen ebenfalls einen echten Origin. Also
`make serve` — das liefert `public/` als Wurzel aus, also genauso wie live.

`fetch-fonts.sh` ist schon gelaufen — die Schriften liegen in `public/fonts/`
und sind in `css/00-fonts.css` eingebunden. Das Skript bleibt für ein
späteres Update der Schnitte da.

## Navigationspunkt ändern

`nav` in `content/site.json`: Reihenfolge und Beschriftung stehen dort
einmal. Der Footer zeigt alle Punkte, die Kopfleiste die mit
`"inHeader": true`. `"page"` ist der Ordnername der Seite — es muss ihn
in `PAGES` geben, sonst meldet `make check` es. Umgekehrt warnt `make
check` auch bei einer Seite, die in keiner Navigation steht: die findet
dann niemand.

`legalNav` sind Impressum und Datenschutz. Die stehen unten im Footer und
absichtlich nicht in der Kopfleiste.

## Eine Seite hinzufügen

1. Eine Funktion schreiben, die den Abschnitt als HTML zurückgibt — nach
   dem Muster von `contact()` oder `dates()`. Prosa gehört dorthin, nicht
   nach `content/`: dort läse sie sich als Ballast, hier als Text.
2. Einen Eintrag in `PAGES` ergänzen (`slug`, `title`, `description`,
   `sections`).
3. In `content/site.json` unter `nav` einen Punkt mit demselben `page`
   ergänzen.
4. `make build && make check`

## Neue Show hinzufügen

1. Ordner `images/shows/YYYY-MM-DD/` anlegen und die Fotos als `1.jpg`,
   `2.jpg`, … ablegen. Vorher verkleinern: `make images-apply`. (Nach
   `images/`, nicht nach `public/images/` — dorthin kopiert sie der Build.)
2. In `content/shows.json` unter `past` einen Block nach dem Muster der
   bestehenden ergänzen — Datum, Titel, Ort, Spieler, ein Eintrag je Foto.
3. `make build && make check`

Artikel, Slider, `aria-label`, alt-Texte und der JSON-LD-Eintrag entstehen
daraus automatisch; Slider-Pfeile und Lightbox kommen aus `public/js/`.

**alt-Texte.** Bleibt `"alt": ""` leer, steht als Notbehelf
„Impro-Szene 3 – Show vom 21.04.2026" im Bild. Für Screenreader ist eine
laufende Nummer fast nichts — ein halber Satz zum Bild ist deutlich
besser. `make check` sagt, wie viele noch offen sind.

## Nächste Show ankündigen

`upcoming` in `content/shows.json` von `null` auf einen Block setzen (das
Beispiel steht als Kommentar daneben). Der Abschnitt „Nächste Show" zeigt
dann Datum, Titel und einen Ticket-Button statt des Platzhalters, und das
JSON-LD bekommt ein weiteres `TheaterEvent`.

## Die Domain

`"url"` in `content/site.json` steht auf `https://www.impro-sturmfrei.de`.
Daraus entstehen `canonical` und `og:url` je Seite, absolute URLs im
JSON-LD, die Brotkrumen sowie `robots.txt` und `sitemap.xml`. Impressum und
Datenschutz stehen absichtlich nicht in der Sitemap: sie sind `noindex` und
gehören in den Footer, nicht in den Index.

Beim Aufsetzen bei netcup noch zu erledigen:

* **Das Git-Deployment einrichten** und klären, ob Node auf dem Webspace
  läuft — siehe „Deploy" unten. Fehlt Node (bei netcup ist das der
  Normalfall: der Webspace bringt PHP mit, sonst nichts), legt
  `sh tools/install-node.sh` es einmalig nach `~/bin/`. Danach ist ein
  Veröffentlichen ein `git push`.
* **`impro-sturmfrei.de` auf `www.impro-sturmfrei.de` umleiten** (301) und
  **HTTPS erzwingen** — Let's Encrypt reicht. Beides steht als
  auskommentierter Block in `.htaccess`; wenn netcup es im WCP anbietet,
  gehört es dorthin und der Block bleibt aus. Das `canonical` zeigt auf die
  www-Form; ohne Umleitung wären beide Formen erreichbar und Google müsste
  raten. Den HTTPS-Zwang erst einschalten, wenn das Zertifikat steht —
  vorher schaltet er die Seite ab.
* **Die Postfächer anlegen.** `info@impro-sturmfrei.de` steht jetzt im
  Impressum, in der Datenschutzerklärung, auf `/kontakt/` und hinter jedem
  „Anfrage schicken"-Knopf. Eine Adresse, die niemand liest, ist im
  Impressum schlimmer als keine.

Offen bleibt die Social-Karte: ein Bild als 1200×630 anlegen und
`"ogImage": "images/og.jpg"` setzen (die Datei gehört nach
`public/images/`; der Pfad in `site.json` ist relativ zur Wurzel der
Seite, also ohne `public/`). Ohne Bild bleibt `twitter:card`
absichtlich auf `summary`, weil eine große leere Karte schlechter aussieht
als eine kleine ohne Bild — `make check` erinnert daran.

## Deploy

netcup zieht das Repo selbst (WCP → Git-Deployment) und ruft danach die
Befehle auf, die im Feld **„Zusätzliche Deployment-Aktionen"** stehen. Dort
steht genau eine Zeile:

```
sh tools/deploy.sh
```

Ein Veröffentlichen ist damit ein `git push`. Weil `public/` nicht im Repo
liegt, **baut der Server** — und das heißt: **ohne Node auf dem Webspace gibt
es keine Seite.** Das ist der Preis dafür, dass im Repo nur Quellen stehen.
Fehlt Node, bricht `tools/deploy.sh` mit einer Anleitung ab und lässt die
bisherige Fassung unangetastet online.

Ob Node da ist, klärt per SSH `command -v node; node -v` — oder das
Aktionsfeld selbst als Sonde: einmal
`{ command -v node && node -v || echo "kein node"; } > public/node-probe.txt 2>&1`
eintragen, deployen, `https://…/node-probe.txt` aufrufen, Datei wieder
löschen.

### Fehlt Node: `tools/install-node.sh`

Auf einem Webspace ist Node der Regelfall nicht. Das Skript holt es nach:

```
sh tools/install-node.sh
```

Es lädt die offizielle Linux-Binärdatei von nodejs.org, prüft die Prüfsumme
aus deren `SHASUMS256.txt` und legt **genau eine Datei** ab: `~/bin/node`
(~100 MB). npm und die Bibliotheken bleiben weg — `build.mjs` und `check.mjs`
haben keine Abhängigkeiten und brauchen nur `node` selbst. Die Patch-Nummer
steht nicht im Skript, sondern kommt aus der `SHASUMS256.txt` der gewählten
Reihe; `sh tools/install-node.sh 20` nimmt einen anderen Hauptzweig, wenn die
glibc des Webspace für den neueren zu alt ist.

Ausführen lässt sich das auf zwei Wegen:

* **per SSH** — einmal aufrufen, fertig.
* **ohne SSH** — im Aktionsfeld vorübergehend `sh tools/install-node.sh`
  eintragen, einmal deployen, danach wieder `sh tools/deploy.sh` eintragen.

Danach bleibt die Aktion die eine Zeile `sh tools/deploy.sh` und braucht
**kein** `PATH=` davor: `deploy.sh` sieht selbst in `~/bin`, `~/.local/bin`,
`~/node/bin` und `~/.nvm/versions/node/*/bin` nach, bevor es aufgibt — der
PATH einer Deployment-Aktion ist karg und enthält das Home nicht. Liegt Node
woanders, sagt `install-node.sh` die passende `PATH=…`-Zeile.

Ein Update ist derselbe Aufruf: die neue Datei wird daneben geschrieben und
erst dann übergebügelt, ein gleichzeitig laufender Deploy trifft also nie auf
eine halbe Datei. Schlägt Download oder Prüfsumme fehl, wird nichts
installiert und das alte `~/bin/node` bleibt unberührt.

**Der Checkout ist gleichzeitig das Webverzeichnis.** Ausgeliefert werden
soll aber nur `public/`. Das erledigt die `.htaccess` im Projektstamm, und
sie erledigt mit derselben Regel beides: jede Anfrage wird nach `public/`
umgeschrieben, und was dort nicht liegt — `content/`, `css/`, `js/`,
`images/`, `tools/`, `.git/` — ist damit nicht „gesperrt", sondern nicht
adressierbar und endet als 404. Eine Deny-Liste müsste man bei jeder neuen
Quelldatei nachziehen; hier gibt es nichts nachzuziehen.

**`tools/deploy.sh` baut nie direkt in `public/`**, sondern daneben:

1. `.public-neu/` bauen (`SITE_OUT`) — `public/` läuft dabei weiter.
2. Prüfen: `check.mjs` gegen `.public-neu/`. Meldet es einen **Fehler**
   (toter Pfad, fehlende Pflichtangabe aus `content/legal.json`, verwaiste
   Datei), wird abgebrochen, `.public-neu/` weggeräumt und **nichts**
   veröffentlicht. Hinweise allein halten nichts auf.
3. Tauschen: zwei `mv` im selben Dateisystem — so kurz, wie es ohne Symlink
   geht. Geht das zweite schief, kommt der alte Stand zurück.
4. Eine Zeile nach `deploy.log` (Zeitstempel, Commit, Ergebnis). Die Datei
   liegt im Projektstamm, ist über die Rewrite-Regel nicht abrufbar und steht
   nicht in git.

Ein kaputter Push nimmt die laufende Seite damit nicht mit: er scheitert in
Schritt 2, und ausgeliefert wird weiter, was vorher stand. Die einzige Lücke
ist der Moment zwischen den beiden `mv` in Schritt 3.

Liegt das Webverzeichnis später **neben** dem Checkout, nimmt das Skript das
Ziel als Argument: `sh tools/deploy.sh ~/httpdocs` spiegelt das geprüfte
Ergebnis zusätzlich dorthin (`rsync -a --delete`, sonst `cp`).

**Node-Version:** gebraucht wird `cpSync`, also Node 16.7 oder neuer. Die
Werkzeuge vermeiden absichtlich die neueren Bequemlichkeiten
(`readdirSync({ recursive: true })`, `entry.parentPath`) — auf welchem Node
ein Webspace läuft, weiß man vorher nicht.

## Impressum und Datenschutz

Beide Seiten stehen und sind vollständig. Die Angaben kommen aus
`content/legal.json`, nicht aus dem Markup:

* `impressum.responsible`, `street`, `postalCode`, `city` — die
  ladungsfähige Anschrift. Steht auf `/impressum/`, wie § 5 DDG es verlangt.
* `impressum.entity` — nur, wenn es eine Rechtsform gibt (e. V., GbR).
  Solange `null`, steht dort nur die Person.
* `impressum.publishAddressInSchema` — steht auf `false`. Die vollständige
  Anschrift bleibt damit auf dem Impressum und wandert **nicht** ins
  JSON-LD. Dort trägt die Gruppe nur Ort und Land: es ist eine
  Privatanschrift und keine Spielstätte, und Google würde sie sonst als
  Anschrift der Gruppe in Karten und Wissenspanel anzeigen können.
* `privacy.host`, `serverLocation` — netcup GmbH, Server in Nürnberg.
  Gehört in die Erklärung, weil dort die Server-Logs anfallen.
* `privacy.logRetentionDays` — noch `null`. Solange bleibt der Satz
  allgemein („richtet sich nach dessen eigenen Angaben"). Steht die Frist
  in netcups Datenschutzangaben, hier eintragen — eine konkrete Zahl ist
  besser als ein Verweis.
* `privacy.processingAgreement` — steht auf `false`. Sobald der Vertrag
  über die Auftragsverarbeitung (Art. 28 DSGVO) mit netcup geschlossen ist,
  auf `true` setzen; dann nennt die Erklärung ihn. Vorher behauptet die
  Seite ihn nicht.

Fehlt eine Pflichtangabe, steht an ihrer Stelle **sichtbar auf der Seite**,
dass sie fehlt, und `make check` meldet es als **FEHLER** — nicht als
Hinweis. Eine Seite mit erfundener Anschrift wäre schlimmer als eine, die
sagt, was noch fehlt.

Der Text der Datenschutzerklärung beschreibt, was diese Seite tatsächlich
tut: keine Cookies, keine Zugriffsmessung, keine fremden Schriften, kein
Formular. Das ist eine belastbare Grundlage, aber kein Rechtsrat — lasst
ihn einmal ansehen, bevor die Domain live geht.

**`content/booking.json` ist ein Entwurf.** Formate, Dauer, Personenzahl
und die Antworten unter `faq` sind geraten. Prüft sie und setzt dann
`"reviewed": true` — bis dahin weist `make check` darauf hin.

## Konventionen

* **Keine Farbe im Klartext** in den Komponenten-CSS-Dateien. Wer eine
  andere Deckkraft braucht, nimmt das Kanal-Token:
  `rgb(var(--accent-rgb) / 0.35)`. Sonst wandert die Farbe im Dark Mode
  nicht mit.
* **Zustandsklassen** (`is-visible`, `is-scrolled`, …) und **Haken im
  Markup** (`data-reveal`, `data-slider`, …) stehen in `js/classes.js` und
  sind der Vertrag zwischen JS, CSS und HTML. Die Module bauen ihre
  Selektoren daraus (`hook("slider")`), `make check` importiert dieselbe
  Datei und meldet, wenn eine Seite davon fehlt.
* **Abgestimmte Zahlen** (Scrollschwellen, Standzeiten, Wischdistanz)
  gehören in `js/config.js`, nicht mitten in die Logik.
* **Progressive Enhancement:** ohne JS bleibt die Seite vollständig
  lesbar, die Slider scrollen weiter, und jede Kachel ist ein Link auf
  das Originalfoto. Bedienelemente, die ohne JS nichts täten (die
  Slider-Pfeile), baut erst das JS.
